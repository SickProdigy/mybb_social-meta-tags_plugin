<?php

define('IN_MYBB', 1);
define('MYBB_ROOT', dirname(__DIR__) . '/');

class SocialMetaTagsTestPlugins
{
    public $hooks = array();

    public function add_hook($hook, $callback)
    {
        $this->hooks[$hook] = $callback;
    }
}

class SocialMetaTagsTestQuery
{
    public $row;

    public function __construct($row)
    {
        $this->row = $row;
    }
}

class SocialMetaTagsTestDatabase
{
    public $group_id = 0;
    public $settings = array();
    private $next_setting_id = 1;

    public function simple_select($table, $fields, $where, $options = array())
    {
        preg_match("/name='([^']+)'/", $where, $matches);
        $name = isset($matches[1]) ? stripslashes($matches[1]) : '';

        if ($table === 'settinggroups') {
            return new SocialMetaTagsTestQuery(
                $this->group_id === 0 ? array() : array('gid' => $this->group_id)
            );
        }

        return new SocialMetaTagsTestQuery(
            isset($this->settings[$name]) ? $this->settings[$name] : array()
        );
    }

    public function fetch_array($query)
    {
        return $query->row;
    }

    public function insert_query($table, $values)
    {
        if ($table === 'settinggroups') {
            $this->group_id = 7;
            return $this->group_id;
        }

        $values['sid'] = $this->next_setting_id++;
        $this->settings[$values['name']] = $values;

        return $values['sid'];
    }

    public function update_query($table, $values, $where, $limit = 0)
    {
        preg_match("/sid='([0-9]+)'/", $where, $matches);
        $sid = isset($matches[1]) ? (int)$matches[1] : 0;

        foreach ($this->settings as $name => $setting) {
            if ((int)$setting['sid'] === $sid) {
                $this->settings[$name] = array_merge($setting, $values);
                return;
            }
        }
    }

    public function escape_string($value)
    {
        return addslashes($value);
    }
}

function htmlspecialchars_uni($value)
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function get_forum_link($fid)
{
    return 'forum-' . $fid . '.html';
}

function get_thread_link($tid)
{
    return 'thread-' . $tid . '.html';
}

function get_post($pid)
{
    global $social_meta_tags_test_posts;

    return isset($social_meta_tags_test_posts[$pid])
        ? $social_meta_tags_test_posts[$pid]
        : array();
}

function my_strlen($value)
{
    return strlen($value);
}

function my_substr($value, $start, $length = null)
{
    return substr($value, $start, $length);
}

function rebuild_settings()
{
}

$social_meta_tags_template_replacements = array();

function find_replace_templatesets($title, $find, $replace)
{
    global $social_meta_tags_template_replacements;

    $social_meta_tags_template_replacements[] = array($title, $find, $replace);
}

function social_meta_tags_test_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

function social_meta_tags_test_render($settings, $forum_data = array(), $thread_data = array(), $foruminfo_data = array())
{
    global $mybb, $forum, $foruminfo, $thread, $social_meta_tags, $headerinclude, $social_meta_tags_page_context;

    $mybb = (object)array('settings' => $settings);
    $forum = array();
    $foruminfo = array();
    $thread = array();
    $social_meta_tags = '';
    $social_meta_tags_page_context = array();
    $headerinclude = '';
    social_meta_tags_build();
    $headerinclude = "<head>\n" . $social_meta_tags . "</head>";
    $forum = array();
    $foruminfo = !empty($foruminfo_data) ? $foruminfo_data : $forum_data;
    $thread = $thread_data;
    social_meta_tags_build();

    return $headerinclude;
}

$plugins = new SocialMetaTagsTestPlugins();
require dirname(__DIR__) . '/Upload/inc/plugins/social_meta_tags.php';

social_meta_tags_test_assert(
    social_meta_tags_absolute_url('https://cdn.example.com/image.jpg', 'https://example.com') === 'https://cdn.example.com/image.jpg',
    'absolute URLs should remain unchanged'
);
social_meta_tags_test_assert(
    social_meta_tags_absolute_url('/thread-42.html', 'https://example.com') === 'https://example.com/thread-42.html',
    'relative URLs should use the board URL'
);

$settings = array(
    'bbname' => 'Example & Board',
    'bburl' => 'https://example.com',
    'social_meta_tags_default_description' => 'Games <and> discussion',
    'social_meta_tags_default_image_url' => 'https://example.com/default.jpg'
);

$board_output = social_meta_tags_test_render($settings);
social_meta_tags_test_assert(
    strpos($board_output, 'content="Example &amp; Board"') !== false,
    'board titles should be escaped'
);
social_meta_tags_test_assert(
    strpos($board_output, 'content="Games  discussion"') !== false,
    'description HTML should be stripped'
);
social_meta_tags_test_assert(
    substr_count($board_output, 'https://example.com/default.jpg') === 2,
    'the configured default should produce both image tags'
);

$forum_output = social_meta_tags_test_render(
    $settings,
    array('fid' => 5, 'name' => 'News & Help', 'description' => 'Forum <b>description</b>')
);
social_meta_tags_test_assert(
    strpos($forum_output, 'content="News &amp; Help - Example &amp; Board"') !== false,
    'forum output should use and escape the forum title'
);
social_meta_tags_test_assert(
    strpos($forum_output, 'content="Forum description"') !== false,
    'forum output should use the stripped forum description'
);
social_meta_tags_test_assert(
    strpos($forum_output, 'content="https://example.com/forum-5.html"') !== false,
    'forum output should contain an absolute forum URL'
);

$forum_with_thread_row_output = social_meta_tags_test_render(
    $settings,
    array('fid' => 5, 'name' => 'News & Help', 'description' => 'Forum description'),
    array('tid' => 99, 'subject' => 'Last thread row')
);
social_meta_tags_test_assert(
    strpos($forum_with_thread_row_output, 'content="News &amp; Help - Example &amp; Board"') !== false,
    'forum output should keep the forum title when a thread row remains in global scope'
);
social_meta_tags_test_assert(
    strpos($forum_with_thread_row_output, 'thread-99.html') === false,
    'forum output should not use the last listed thread URL'
);

$social_meta_tags_test_posts = array(
    100 => array('message' => '[b]Release notes[/b] [quote]Old quoted text[/quote] This update fixes sharing previews. [img]https://cdn.example.com/thread-first.jpg[/img]')
);
$thread_output = social_meta_tags_test_render(
    $settings,
    array(),
    array(
        'tid' => 42,
        'firstpost' => 100,
        'subject' => 'Thread "Title"',
    )
);
social_meta_tags_test_assert(
    strpos($thread_output, 'content="Thread &quot;Title&quot; - Example &amp; Board"') !== false,
    'thread titles should be escaped'
);
social_meta_tags_test_assert(
    strpos($thread_output, 'content="Release notes This update fixes sharing previews."') !== false,
    'thread descriptions should use a clean excerpt from the first post'
);
social_meta_tags_test_assert(
    strpos($thread_output, 'content="article"') !== false,
    'thread output should use the article type'
);
social_meta_tags_test_assert(
    strpos($thread_output, 'content="https://example.com/thread-42.html"') !== false,
    'thread output should contain an absolute thread URL'
);
social_meta_tags_test_assert(
    substr_count($thread_output, 'https://cdn.example.com/thread-first.jpg') === 2,
    'the first embedded post image should override the default in both image tags'
);
social_meta_tags_test_assert(
    strpos($thread_output, 'default.jpg') === false,
    'a thread image should fully replace the configured default'
);

$thread_images_disabled_settings = $settings;
$thread_images_disabled_settings['social_meta_tags_enable_thread_images'] = '0';
$thread_images_disabled_output = social_meta_tags_test_render(
    $thread_images_disabled_settings,
    array(),
    array(
        'tid' => 42,
        'firstpost' => 100,
        'subject' => 'Thread Title',
    )
);
social_meta_tags_test_assert(
    strpos($thread_images_disabled_output, 'https://cdn.example.com/thread-first.jpg') === false,
    'disabled thread image metadata should ignore first-post images'
);
social_meta_tags_test_assert(
    substr_count($thread_images_disabled_output, 'https://example.com/default.jpg') === 2,
    'disabled thread image metadata should fall back to the default image'
);

$thread_default_description_settings = $settings;
$thread_default_description_settings['social_meta_tags_thread_description_source'] = 'default';
$thread_default_description_output = social_meta_tags_test_render(
    $thread_default_description_settings,
    array(),
    array(
        'tid' => 42,
        'firstpost' => 100,
        'subject' => 'Thread Title',
    )
);
social_meta_tags_test_assert(
    strpos($thread_default_description_output, 'content="Games  discussion"') !== false,
    'thread description source can use the configured default description'
);

$board_page_title_settings = $settings;
$board_page_title_settings['social_meta_tags_title_format'] = 'board_page';
$board_page_title_output = social_meta_tags_test_render(
    $board_page_title_settings,
    array(),
    array(
        'tid' => 42,
        'firstpost' => 100,
        'subject' => 'Thread Title',
    )
);
social_meta_tags_test_assert(
    strpos($board_page_title_output, 'content="Example &amp; Board - Thread Title"') !== false,
    'title format can put the board name first'
);

$forum_description_disabled_settings = $settings;
$forum_description_disabled_settings['social_meta_tags_use_forum_descriptions'] = '0';
$forum_description_disabled_output = social_meta_tags_test_render(
    $forum_description_disabled_settings,
    array('fid' => 5, 'name' => 'News', 'description' => 'Forum description')
);
social_meta_tags_test_assert(
    strpos($forum_description_disabled_output, 'content="Games  discussion"') !== false,
    'forum descriptions can be disabled'
);

$short_description_settings = $settings;
$short_description_settings['social_meta_tags_max_description_length'] = '20';
$short_description_output = social_meta_tags_test_render(
    $short_description_settings,
    array(),
    array(
        'tid' => 42,
        'firstpost' => 100,
        'subject' => 'Thread Title',
    )
);
social_meta_tags_test_assert(
    strpos($short_description_output, 'content="Release notes Thi..."') !== false,
    'thread descriptions should honor the configured maximum length'
);

$metadata_options_settings = $settings;
$metadata_options_settings['social_meta_tags_twitter_card_type'] = 'summary';
$metadata_options_settings['social_meta_tags_enable_site_name'] = '1';
$metadata_options_settings['social_meta_tags_locale'] = 'en_US';
$metadata_options_settings['social_meta_tags_default_image_width'] = '1200';
$metadata_options_settings['social_meta_tags_default_image_height'] = '630';
$metadata_options_output = social_meta_tags_test_render($metadata_options_settings);
social_meta_tags_test_assert(
    strpos($metadata_options_output, 'name="twitter:card" content="summary"') !== false,
    'twitter card type should be configurable'
);
social_meta_tags_test_assert(
    strpos($metadata_options_output, 'property="og:site_name" content="Example &amp; Board"') !== false,
    'site name metadata should be optional'
);
social_meta_tags_test_assert(
    strpos($metadata_options_output, 'property="og:locale" content="en_US"') !== false,
    'locale metadata should be optional'
);
social_meta_tags_test_assert(
    strpos($metadata_options_output, 'property="og:image:width" content="1200"') !== false
        && strpos($metadata_options_output, 'property="og:image:height" content="630"') !== false,
    'default image dimensions should be optional'
);

$board_disabled_settings = $settings;
$board_disabled_settings['social_meta_tags_enable_board'] = '0';
$board_disabled_output = social_meta_tags_test_render($board_disabled_settings);
social_meta_tags_test_assert(
    strpos($board_disabled_output, 'og:title') === false,
    'board metadata can be disabled for board-level pages'
);

$settings['social_meta_tags_default_image_url'] = '';
$no_image_output = social_meta_tags_test_render($settings);
social_meta_tags_test_assert(
    strpos($no_image_output, 'og:image') === false && strpos($no_image_output, 'twitter:image') === false,
    'an empty resolved image should omit both image tags'
);

$mybb = (object)array('settings' => array(
    'bbname' => 'Installing Board',
    'bburl' => 'https://install.example.com/forum',
));
$theme = array('logo' => 'images/logo.png');
$db = new SocialMetaTagsTestDatabase();
social_meta_tags_ensure_settings();
social_meta_tags_test_assert(
    count($db->settings) === 15,
    'a new install should create all settings'
);
social_meta_tags_test_assert(
    $db->settings['social_meta_tags_default_description']['value'] === 'Installing Board',
    'a new install should use the board name as the default description'
);
social_meta_tags_test_assert(
    $db->settings['social_meta_tags_default_image_url']['value'] === 'https://install.example.com/forum/images/logo.png',
    'a new install should use the active theme logo as the default image URL'
);
social_meta_tags_test_assert(
    $db->settings['social_meta_tags_enable_thread_images']['value'] === '1',
    'thread image metadata should default to enabled'
);
social_meta_tags_test_assert(
    $db->settings['social_meta_tags_enable_site_name']['value'] === '1',
    'site name metadata should default to enabled'
);

$db->settings['social_meta_tags_default_description']['value'] = 'Administrator description';
$db->settings['social_meta_tags_default_description']['title'] = 'Outdated title';
$db->settings['social_meta_tags_default_image_url']['value'] = 'https://example.com/custom.jpg';
$db->settings['social_meta_tags_default_image_url']['gid'] = 99;
$db->settings['social_meta_tags_twitter_card_type']['value'] = 'summary';
social_meta_tags_ensure_settings();
social_meta_tags_test_assert(
    $db->settings['social_meta_tags_default_description']['value'] === 'Administrator description',
    'setting synchronization should preserve a custom description'
);
social_meta_tags_test_assert(
    $db->settings['social_meta_tags_default_image_url']['value'] === 'https://example.com/custom.jpg',
    'setting synchronization should preserve a custom image URL'
);
social_meta_tags_test_assert(
    $db->settings['social_meta_tags_twitter_card_type']['value'] === 'summary',
    'setting synchronization should preserve new custom options'
);
social_meta_tags_test_assert(
    $db->settings['social_meta_tags_default_description']['title'] === 'Default Meta Description'
        && $db->settings['social_meta_tags_default_image_url']['gid'] === 7,
    'setting synchronization should refresh metadata'
);

$synchronized_settings = serialize($db->settings);
social_meta_tags_ensure_settings();
social_meta_tags_test_assert(
    serialize($db->settings) === $synchronized_settings,
    'repeated setting synchronization should be idempotent'
);

social_meta_tags_test_assert(
    isset($plugins->hooks['admin_style_themes_add_commit'])
        && isset($plugins->hooks['admin_style_themes_import_commit'])
        && isset($plugins->hooks['admin_style_themes_duplicate_commit'])
        && isset($plugins->hooks['admin_style_themes_set_default_commit']),
    'theme lifecycle hooks should be registered'
);

social_meta_tags_test_assert(
    isset($plugins->hooks['global_end'])
        && isset($plugins->hooks['misc_help_helpdoc_end'])
        && isset($plugins->hooks['misc_help_section_end'])
        && isset($plugins->hooks['portal_start'])
        && isset($plugins->hooks['stats_start'])
        && isset($plugins->hooks['showteam_start']),
    'runtime recovery and board page hooks should be registered'
);

social_meta_tags_test_render($settings);
$headerinclude = '<link rel="stylesheet" href="theme.css" />';
social_meta_tags_inject_runtime();
social_meta_tags_inject_runtime();
social_meta_tags_test_assert(
    substr_count($headerinclude, 'property="og:title"') === 1
        && strpos($headerinclude, 'property="og:title"') < strpos($headerinclude, '<link'),
    'runtime recovery should prepend missing metadata once'
);

$lang = (object)array(
    'nav_helpdocs' => 'Help Center',
    'nav_portal' => 'Community Portal',
    'nav_stats' => 'Community Statistics',
    'nav_showteam' => 'Community Team'
);
$mybb = (object)array('settings' => $settings);
$forum = array();
$foruminfo = array();
$thread = array();
$social_meta_tags_page_context = array();
$social_meta_tags = '';
$headerinclude = '';
social_meta_tags_build();
social_meta_tags_inject_runtime();
$helpdoc = array('hid' => 8, 'name' => 'Posting Help', 'description' => 'How to <b>post</b>');
social_meta_tags_build_help_document();
social_meta_tags_test_assert(
    strpos($headerinclude, 'content="Posting Help - Example &amp; Board"') !== false
        && strpos($headerinclude, 'content="How to post"') !== false
        && strpos($headerinclude, 'content="https://example.com/misc.php?action=help&amp;hid=8"') !== false,
    'help documents should use their own title, description, and URL'
);

$page_cases = array(
    array('social_meta_tags_build_help_index', 'Help Center', 'misc.php?action=help'),
    array('social_meta_tags_build_portal_page', 'Community Portal', 'portal.php'),
    array('social_meta_tags_build_stats_page', 'Community Statistics', 'stats.php'),
    array('social_meta_tags_build_team_page', 'Community Team', 'showteam.php')
);

foreach ($page_cases as $page_case) {
    call_user_func($page_case[0]);
    social_meta_tags_test_assert(
        strpos($headerinclude, 'content="' . $page_case[1] . ' - Example &amp; Board"') !== false
            && strpos($headerinclude, 'content="https://example.com/' . $page_case[2] . '"') !== false,
        $page_case[1] . ' should use a page-specific title and URL'
    );
}

social_meta_tags_sync_headerinclude_templates();
social_meta_tags_test_assert(
    count($social_meta_tags_template_replacements) === 3,
    'theme synchronization should perform fallback removal, duplicate removal, and insertion'
);
social_meta_tags_test_assert(
    $social_meta_tags_template_replacements[2][0] === 'headerinclude'
        && $social_meta_tags_template_replacements[2][2] === '{$social_meta_tags}{$stylesheets}',
    'theme synchronization should insert the plugin variable before stylesheets'
);

echo "Social Meta Tags tests passed.\n";
