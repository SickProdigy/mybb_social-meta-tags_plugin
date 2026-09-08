<?php
/**
 * Social Meta Tags
 *
 * Open Graph and Twitter metadata for MyBB pages.
 *
 * Copyright (C) 2026 SickProdigy
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

function social_meta_tags_info()
{
    return array(
        'name' => 'Social Meta Tags',
        'description' => 'Provides configurable Open Graph and Twitter metadata variables for MyBB templates.',
        'website' => 'https://www.sickgaming.net',
        'author' => 'SickProdigy',
        'authorsite' => 'https://www.sickgaming.net',
        'version' => '1.1.0',
        'compatibility' => '18*'
    );
}

function social_meta_tags_install()
{
    social_meta_tags_ensure_settings();
}

function social_meta_tags_is_installed()
{
    global $db;

    $query = $db->simple_select('settinggroups', 'gid', "name='social_meta_tags'");
    $group = $db->fetch_array($query);

    return !empty($group['gid']);
}

function social_meta_tags_uninstall()
{
    global $db;

    $query = $db->simple_select('settinggroups', 'gid', "name='social_meta_tags'");
    $group = $db->fetch_array($query);

    if (!empty($group['gid'])) {
        $gid = (int)$group['gid'];
        $db->delete_query('settings', "gid='{$gid}'");
        $db->delete_query('settinggroups', "gid='{$gid}'");
        social_meta_tags_rebuild_settings();
    }
}

function social_meta_tags_activate()
{
    social_meta_tags_ensure_settings();
    social_meta_tags_sync_headerinclude_templates();
}

function social_meta_tags_deactivate()
{
    require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

    find_replace_templatesets(
        'headerinclude',
        '#' . preg_quote('{$social_meta_tags}') . '#i',
        ''
    );
    find_replace_templatesets(
        'headerinclude',
        social_meta_tags_fallback_pattern(),
        ''
    );
    find_replace_templatesets(
        'headerinclude',
        '#' . preg_quote('{$stylesheets}') . '#i',
        social_meta_tags_fallback_markup() . '{$stylesheets}'
    );
}

function social_meta_tags_sync_headerinclude_templates()
{
    if (!function_exists('find_replace_templatesets')) {
        require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
    }

    find_replace_templatesets(
        'headerinclude',
        social_meta_tags_fallback_pattern(),
        ''
    );
    find_replace_templatesets(
        'headerinclude',
        '#' . preg_quote('{$social_meta_tags}') . '#i',
        ''
    );
    find_replace_templatesets(
        'headerinclude',
        '#' . preg_quote('{$stylesheets}') . '#i',
        '{$social_meta_tags}{$stylesheets}'
    );
}

function social_meta_tags_fallback_pattern()
{
    return '#<!-- social-meta-tags-fallback:start -->.*?<!-- social-meta-tags-fallback:end -->\s*#s';
}

function social_meta_tags_fallback_markup()
{
    return '<!-- social-meta-tags-fallback:start -->' . "\n"
        . '<meta property="og:title" content="{$mybb->settings[' . "'bbname'" . ']}" />' . "\n"
        . '<meta property="og:description" content="{$mybb->settings[' . "'bbname'" . ']}" />' . "\n"
        . '<meta property="og:url" content="{$mybb->settings[' . "'bburl'" . ']}" />' . "\n"
        . '<meta property="og:type" content="website" />' . "\n"
        . '<meta property="og:image" content="{$mybb->settings[' . "'bburl'" . ']}/{$theme[' . "'logo'" . ']}" />' . "\n\n"
        . '<meta name="twitter:card" content="summary_large_image" />' . "\n"
        . '<meta name="twitter:title" content="{$mybb->settings[' . "'bbname'" . ']}" />' . "\n"
        . '<meta name="twitter:description" content="{$mybb->settings[' . "'bbname'" . ']}" />' . "\n"
        . '<meta name="twitter:image" content="{$mybb->settings[' . "'bburl'" . ']}/{$theme[' . "'logo'" . ']}" />' . "\n"
        . '<!-- social-meta-tags-fallback:end -->' . "\n\n";
}

function social_meta_tags_ensure_settings()
{
    global $db;

    $query = $db->simple_select('settinggroups', 'gid', "name='social_meta_tags'");
    $group = $db->fetch_array($query);

    if (!empty($group['gid'])) {
        $gid = (int)$group['gid'];
    } else {
        $gid = (int)$db->insert_query('settinggroups', array(
            'name' => 'social_meta_tags',
            'title' => 'Social Meta Tags',
            'description' => 'Settings for Open Graph and Twitter metadata.',
            'disporder' => 1,
            'isdefault' => 0
        ));
    }

    $legacy_description = social_meta_tags_legacy_setting('revolution_theme_default_meta');
    $legacy_image_url = social_meta_tags_legacy_setting('revolution_theme_logo_url');
    $default_description = $legacy_description !== ''
        ? $legacy_description
        : social_meta_tags_default_description();
    $default_image_url = $legacy_image_url !== ''
        ? $legacy_image_url
        : social_meta_tags_default_image_url();

    $settings = array(
        array(
            'name' => 'social_meta_tags_default_description',
            'title' => 'Default Meta Description',
            'description' => 'Fallback description when a forum description is unavailable.',
            'optionscode' => 'textarea',
            'value' => $default_description,
            'disporder' => 1,
            'gid' => $gid
        ),
        array(
            'name' => 'social_meta_tags_default_image_url',
            'title' => 'Default Image URL',
            'description' => 'Absolute fallback image URL used for Open Graph and Twitter cards.',
            'optionscode' => 'text',
            'value' => $default_image_url,
            'disporder' => 2,
            'gid' => $gid
        ),
        array(
            'name' => 'social_meta_tags_twitter_card_type',
            'title' => 'Twitter Card Type',
            'description' => 'Twitter/X card format used by generated metadata.',
            'optionscode' => 'select
summary_large_image=Summary with large image
summary=Summary',
            'value' => 'summary_large_image',
            'disporder' => 3,
            'gid' => $gid
        ),
        array(
            'name' => 'social_meta_tags_title_format',
            'title' => 'Title Format',
            'description' => 'Order used for forum and thread social titles.',
            'optionscode' => 'select
page_board=Page - Board
board_page=Board - Page
page_only=Page only',
            'value' => 'page_board',
            'disporder' => 4,
            'gid' => $gid
        ),
        array(
            'name' => 'social_meta_tags_enable_board',
            'title' => 'Enable Board Metadata',
            'description' => 'Generate metadata for board-level pages.',
            'optionscode' => 'yesno',
            'value' => '1',
            'disporder' => 5,
            'gid' => $gid
        ),
        array(
            'name' => 'social_meta_tags_enable_forums',
            'title' => 'Enable Forum Metadata',
            'description' => 'Generate forum-specific metadata on forum pages.',
            'optionscode' => 'yesno',
            'value' => '1',
            'disporder' => 6,
            'gid' => $gid
        ),
        array(
            'name' => 'social_meta_tags_enable_threads',
            'title' => 'Enable Thread Metadata',
            'description' => 'Generate thread-specific metadata on thread pages.',
            'optionscode' => 'yesno',
            'value' => '1',
            'disporder' => 7,
            'gid' => $gid
        ),
        array(
            'name' => 'social_meta_tags_use_forum_descriptions',
            'title' => 'Use Forum Descriptions',
            'description' => 'Use forum descriptions as social descriptions when available.',
            'optionscode' => 'yesno',
            'value' => '1',
            'disporder' => 8,
            'gid' => $gid
        ),
        array(
            'name' => 'social_meta_tags_thread_description_source',
            'title' => 'Thread Description Source',
            'description' => 'Choose whether thread descriptions use a first-post excerpt or the default description.',
            'optionscode' => 'select
excerpt=First-post excerpt
default=Default description',
            'value' => 'excerpt',
            'disporder' => 9,
            'gid' => $gid
        ),
        array(
            'name' => 'social_meta_tags_max_description_length',
            'title' => 'Maximum Description Length',
            'description' => 'Maximum length for generated forum and thread descriptions. Use 0 to disable trimming.',
            'optionscode' => 'numeric',
            'value' => '200',
            'disporder' => 10,
            'gid' => $gid
        ),
        array(
            'name' => 'social_meta_tags_enable_thread_images',
            'title' => 'Enable Thread Image Metadata',
            'description' => 'Use image metadata from thread content when available.',
            'optionscode' => 'yesno',
            'value' => '1',
            'disporder' => 11,
            'gid' => $gid
        ),
        array(
            'name' => 'social_meta_tags_enable_site_name',
            'title' => 'Enable Site Name Metadata',
            'description' => 'Include og:site_name using the board name.',
            'optionscode' => 'yesno',
            'value' => '1',
            'disporder' => 12,
            'gid' => $gid
        ),
        array(
            'name' => 'social_meta_tags_locale',
            'title' => 'Open Graph Locale',
            'description' => 'Optional og:locale value, such as en_US.',
            'optionscode' => 'text',
            'value' => '',
            'disporder' => 13,
            'gid' => $gid
        ),
        array(
            'name' => 'social_meta_tags_default_image_width',
            'title' => 'Default Image Width',
            'description' => 'Optional og:image:width value for the configured default image.',
            'optionscode' => 'numeric',
            'value' => '',
            'disporder' => 14,
            'gid' => $gid
        ),
        array(
            'name' => 'social_meta_tags_default_image_height',
            'title' => 'Default Image Height',
            'description' => 'Optional og:image:height value for the configured default image.',
            'optionscode' => 'numeric',
            'value' => '',
            'disporder' => 15,
            'gid' => $gid
        )
    );

    foreach ($settings as $setting) {
        $name = $db->escape_string($setting['name']);
        $query = $db->simple_select('settings', 'sid', "name='{$name}'", array('limit' => 1));
        $existing = $db->fetch_array($query);

        if (empty($existing['sid'])) {
            $db->insert_query('settings', $setting);
            continue;
        }

        $sid = (int)$existing['sid'];
        unset($setting['value']);
        $db->update_query('settings', $setting, "sid='{$sid}'", 1);
    }

    social_meta_tags_rebuild_settings();
}

function social_meta_tags_legacy_setting($name)
{
    global $db;

    $query = $db->simple_select('settings', 'value', "name='" . $db->escape_string($name) . "'");
    $setting = $db->fetch_array($query);

    return isset($setting['value']) ? trim($setting['value']) : '';
}

function social_meta_tags_default_description()
{
    global $mybb;

    return !empty($mybb->settings['bbname']) ? $mybb->settings['bbname'] : '';
}

function social_meta_tags_default_image_url()
{
    global $mybb, $theme;

    if (empty($mybb->settings['bburl']) || empty($theme['logo'])) {
        return '';
    }

    return social_meta_tags_absolute_url($theme['logo'], rtrim($mybb->settings['bburl'], '/'));
}

$plugins->add_hook('global_start', 'social_meta_tags_build');
$plugins->add_hook('forumdisplay_end', 'social_meta_tags_build');
$plugins->add_hook('showthread_end', 'social_meta_tags_build');
$plugins->add_hook('admin_style_themes_add_commit', 'social_meta_tags_sync_headerinclude_templates');
$plugins->add_hook('admin_style_themes_import_commit', 'social_meta_tags_sync_headerinclude_templates');
$plugins->add_hook('admin_style_themes_duplicate_commit', 'social_meta_tags_sync_headerinclude_templates');
function social_meta_tags_build()
{
    global $thread, $forum, $foruminfo, $mybb, $headerinclude;
    global $open_meta_title, $open_meta_description, $open_meta_url, $open_meta_type, $open_meta_image, $social_meta_tags;

    $previous_social_meta_tags = isset($social_meta_tags) ? $social_meta_tags : '';
    $forum_context = !empty($foruminfo['fid']) ? $foruminfo : $forum;
    $is_thread_page = !empty($thread['tid'])
        && (defined('THIS_SCRIPT') ? THIS_SCRIPT === 'showthread.php' : empty($forum_context['fid']));

    $board_name = isset($mybb->settings['bbname']) ? $mybb->settings['bbname'] : '';
    $board_url = isset($mybb->settings['bburl']) ? rtrim($mybb->settings['bburl'], '/') : '';

    $open_meta_title = $board_name;
    $open_meta_description = !empty($mybb->settings['social_meta_tags_default_description'])
        ? $mybb->settings['social_meta_tags_default_description']
        : '';
    $open_meta_url = $board_url;
    $open_meta_type = 'website';
    $open_meta_image = !empty($mybb->settings['social_meta_tags_default_image_url'])
        ? $mybb->settings['social_meta_tags_default_image_url']
        : '';

    if (
        empty($forum_context['fid'])
        && !$is_thread_page
        && !social_meta_tags_setting_enabled('social_meta_tags_enable_board', true)
    ) {
        $social_meta_tags = '';

        if ($previous_social_meta_tags !== '' && isset($headerinclude)) {
            $headerinclude = str_replace($previous_social_meta_tags, $social_meta_tags, $headerinclude);
        }

        return;
    }

    if (
        !empty($forum_context['fid'])
        && social_meta_tags_setting_enabled('social_meta_tags_enable_forums', true)
    ) {
        $open_meta_title = social_meta_tags_format_title($forum_context['name'], $board_name);
        $open_meta_description = !empty($forum_context['description'])
            && social_meta_tags_setting_enabled('social_meta_tags_use_forum_descriptions', true)
            ? $forum_context['description']
            : $open_meta_description;
        $open_meta_url = social_meta_tags_absolute_url(get_forum_link((int)$forum_context['fid']), $board_url);
    }

    if (
        $is_thread_page
        && social_meta_tags_setting_enabled('social_meta_tags_enable_threads', true)
    ) {
        $open_meta_title = social_meta_tags_format_title($thread['subject'], $board_name);
        if (social_meta_tags_thread_description_source() === 'excerpt') {
            $open_meta_description = social_meta_tags_thread_description($thread, $open_meta_description);
        }
        $open_meta_url = social_meta_tags_absolute_url(get_thread_link((int)$thread['tid']), $board_url);
        $open_meta_type = 'article';

        if (!empty($thread['image']) && social_meta_tags_setting_enabled('social_meta_tags_enable_thread_images', true)) {
            $open_meta_image = $thread['image'];
        } elseif (social_meta_tags_setting_enabled('social_meta_tags_enable_thread_images', true)) {
            $thread_image = social_meta_tags_thread_image($thread, $board_url);
            if ($thread_image !== '') {
                $open_meta_image = $thread_image;
            }
        }
    }

    $open_meta_title = htmlspecialchars_uni(strip_tags($open_meta_title));
    $open_meta_description = htmlspecialchars_uni(strip_tags($open_meta_description));
    $open_meta_url = htmlspecialchars_uni($open_meta_url);
    $open_meta_type = htmlspecialchars_uni($open_meta_type);
    $is_default_image = social_meta_tags_is_default_image($open_meta_image);
    $open_meta_image = htmlspecialchars_uni($open_meta_image);
    $twitter_card_type = htmlspecialchars_uni(social_meta_tags_twitter_card_type());
    $image_meta_tags = '';
    $extra_meta_tags = '';

    if ($open_meta_image !== '') {
        $image_meta_tags = '<meta property="og:image" content="' . $open_meta_image . '" />' . "\n"
            . '<meta name="twitter:image" content="' . $open_meta_image . '" />' . "\n";

        if ($is_default_image) {
            $image_width = social_meta_tags_positive_integer_setting('social_meta_tags_default_image_width');
            $image_height = social_meta_tags_positive_integer_setting('social_meta_tags_default_image_height');

            if ($image_width > 0) {
                $image_meta_tags .= '<meta property="og:image:width" content="' . $image_width . '" />' . "\n";
            }

            if ($image_height > 0) {
                $image_meta_tags .= '<meta property="og:image:height" content="' . $image_height . '" />' . "\n";
            }
        }
    }

    if (social_meta_tags_setting_enabled('social_meta_tags_enable_site_name', true) && $board_name !== '') {
        $extra_meta_tags .= '<meta property="og:site_name" content="' . htmlspecialchars_uni(strip_tags($board_name)) . '" />' . "\n";
    }

    $locale = social_meta_tags_locale();
    if ($locale !== '') {
        $extra_meta_tags .= '<meta property="og:locale" content="' . htmlspecialchars_uni($locale) . '" />' . "\n";
    }

    $social_meta_tags = '<meta property="og:title" content="' . $open_meta_title . '" />' . "\n"
        . '<meta property="og:description" content="' . $open_meta_description . '" />' . "\n"
        . '<meta property="og:url" content="' . $open_meta_url . '" />' . "\n"
        . '<meta property="og:type" content="' . $open_meta_type . '" />' . "\n"
        . $extra_meta_tags
        . '<meta name="twitter:card" content="' . $twitter_card_type . '" />' . "\n"
        . '<meta name="twitter:title" content="' . $open_meta_title . '" />' . "\n"
        . '<meta name="twitter:description" content="' . $open_meta_description . '" />' . "\n"
        . $image_meta_tags;

    if ($previous_social_meta_tags !== '' && isset($headerinclude)) {
        $headerinclude = str_replace($previous_social_meta_tags, $social_meta_tags, $headerinclude);
    }
}

function social_meta_tags_thread_description($thread, $fallback)
{
    if (empty($thread['firstpost']) || !function_exists('get_post')) {
        return $fallback;
    }

    $post = social_meta_tags_first_post($thread);

    if (empty($post['message'])) {
        return $fallback;
    }

    $description = $post['message'];
    $description = preg_replace(
        '#\[(quote|code|php|img|video)(?:=[^\]]*)?\].*?\[/\1\]#is',
        ' ',
        $description
    );
    $description = preg_replace('#\[/?[a-z][^\]]*\]#i', ' ', $description);
    $description = html_entity_decode(strip_tags($description), ENT_QUOTES, 'UTF-8');
    $description = trim(preg_replace('/\s+/u', ' ', $description));

    if ($description === '') {
        return $fallback;
    }

    $max_length = social_meta_tags_max_description_length();

    if ($max_length > 0 && my_strlen($description) > $max_length) {
        $suffix = $max_length > 3 ? '...' : '';
        $trim_length = $max_length > 3 ? $max_length - 3 : $max_length;
        $description = rtrim(my_substr($description, 0, $trim_length)) . $suffix;
    }

    return $description;
}

function social_meta_tags_thread_image($thread, $board_url)
{
    $post = social_meta_tags_first_post($thread);

    if (empty($post['message'])) {
        return '';
    }

    $image_url = '';

    if (preg_match('#\[img(?:=[^\]]*)?\](.*?)\[/img\]#is', $post['message'], $matches)) {
        $image_url = trim($matches[1]);
    } elseif (preg_match('#<img[^>]+src=["\']([^"\']+)["\']#i', $post['message'], $matches)) {
        $image_url = trim($matches[1]);
    }

    $image_url = html_entity_decode($image_url, ENT_QUOTES, 'UTF-8');

    if ($image_url === '' || preg_match('#^(?:data|javascript):#i', $image_url)) {
        return '';
    }

    if (strpos($image_url, '//') === 0) {
        return 'https:' . $image_url;
    }

    return social_meta_tags_absolute_url($image_url, $board_url);
}

function social_meta_tags_first_post($thread)
{
    static $posts = array();

    if (empty($thread['firstpost']) || !function_exists('get_post')) {
        return array();
    }

    $pid = (int)$thread['firstpost'];

    if (!isset($posts[$pid])) {
        $posts[$pid] = get_post($pid);
    }

    return is_array($posts[$pid]) ? $posts[$pid] : array();
}

function social_meta_tags_absolute_url($url, $board_url)
{
    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }

    return $board_url . '/' . ltrim($url, '/');
}

function social_meta_tags_format_title($page_title, $board_name)
{
    global $mybb;

    if ($board_name === '') {
        return $page_title;
    }

    $format = isset($mybb->settings['social_meta_tags_title_format'])
        ? $mybb->settings['social_meta_tags_title_format']
        : 'page_board';

    if ($format === 'board_page') {
        return $board_name . ' - ' . $page_title;
    }

    if ($format === 'page_only') {
        return $page_title;
    }

    return $page_title . ' - ' . $board_name;
}

function social_meta_tags_setting_enabled($name, $default)
{
    global $mybb;

    if (!isset($mybb->settings[$name]) || $mybb->settings[$name] === '') {
        return (bool)$default;
    }

    return (int)$mybb->settings[$name] === 1;
}

function social_meta_tags_twitter_card_type()
{
    global $mybb;

    if (
        isset($mybb->settings['social_meta_tags_twitter_card_type'])
        && $mybb->settings['social_meta_tags_twitter_card_type'] === 'summary'
    ) {
        return 'summary';
    }

    return 'summary_large_image';
}

function social_meta_tags_thread_description_source()
{
    global $mybb;

    if (
        isset($mybb->settings['social_meta_tags_thread_description_source'])
        && $mybb->settings['social_meta_tags_thread_description_source'] === 'default'
    ) {
        return 'default';
    }

    return 'excerpt';
}

function social_meta_tags_max_description_length()
{
    global $mybb;

    if (!isset($mybb->settings['social_meta_tags_max_description_length'])) {
        return 200;
    }

    $max_length = social_meta_tags_positive_integer_setting('social_meta_tags_max_description_length');

    return $max_length > 0 ? $max_length : 0;
}

function social_meta_tags_positive_integer_setting($name)
{
    global $mybb;

    if (empty($mybb->settings[$name])) {
        return 0;
    }

    return max(0, (int)$mybb->settings[$name]);
}

function social_meta_tags_is_default_image($image_url)
{
    global $mybb;

    return !empty($mybb->settings['social_meta_tags_default_image_url'])
        && $image_url === $mybb->settings['social_meta_tags_default_image_url'];
}

function social_meta_tags_locale()
{
    global $mybb;

    if (empty($mybb->settings['social_meta_tags_locale'])) {
        return '';
    }

    $locale = trim($mybb->settings['social_meta_tags_locale']);

    return preg_match('/^[a-z]{2}_[A-Z]{2}$/', $locale) ? $locale : '';
}

function social_meta_tags_rebuild_settings()
{
    if (!function_exists('rebuild_settings')) {
        require_once MYBB_ROOT . 'inc/functions.php';
    }

    rebuild_settings();
}
