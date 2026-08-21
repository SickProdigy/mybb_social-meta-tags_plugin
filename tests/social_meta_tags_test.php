<?php

define('IN_MYBB', 1);
define('MYBB_ROOT', dirname(__DIR__) . '/');

class SocialMetaTagsTestPlugins
{
    public function add_hook($hook, $callback)
    {
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

function social_meta_tags_test_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

function social_meta_tags_test_render($settings, $forum_data = array(), $thread_data = array(), $foruminfo_data = array())
{
    global $mybb, $forum, $foruminfo, $thread, $social_meta_tags, $headerinclude;

    $mybb = (object)array('settings' => $settings);
    $forum = array();
    $foruminfo = array();
    $thread = array();
    $social_meta_tags = '';
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

$settings['social_meta_tags_default_image_url'] = '';
$no_image_output = social_meta_tags_test_render($settings);
social_meta_tags_test_assert(
    strpos($no_image_output, 'og:image') === false && strpos($no_image_output, 'twitter:image') === false,
    'an empty resolved image should omit both image tags'
);

$db = new SocialMetaTagsTestDatabase();
social_meta_tags_ensure_settings();
social_meta_tags_test_assert(
    count($db->settings) === 2,
    'a new install should create both settings'
);

$db->settings['social_meta_tags_default_description']['value'] = 'Administrator description';
$db->settings['social_meta_tags_default_description']['title'] = 'Outdated title';
$db->settings['social_meta_tags_default_image_url']['value'] = 'https://example.com/custom.jpg';
$db->settings['social_meta_tags_default_image_url']['gid'] = 99;
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

echo "Social Meta Tags tests passed.\n";
