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
        'version' => '1.0.1',
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

    require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

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

    $settings = array(
        array(
            'name' => 'social_meta_tags_default_description',
            'title' => 'Default Meta Description',
            'description' => 'Fallback description when a forum description is unavailable.',
            'optionscode' => 'textarea',
            'value' => $legacy_description !== ''
                ? $legacy_description
                : 'Sick Gaming is an exclusive community for console and PC enthusiasts. Connect with like-minded people to compete, program, develop, mod, and more.',
            'disporder' => 1,
            'gid' => $gid
        ),
        array(
            'name' => 'social_meta_tags_default_image_url',
            'title' => 'Default Image URL',
            'description' => 'Absolute fallback image URL used for Open Graph and Twitter cards.',
            'optionscode' => 'text',
            'value' => $legacy_image_url !== ''
                ? $legacy_image_url
                : 'https://www.sickgaming.net/images/logo.png',
            'disporder' => 2,
            'gid' => $gid
        )
    );

    foreach ($settings as $setting) {
        $query = $db->simple_select('settings', 'sid', "name='" . $db->escape_string($setting['name']) . "'");
        $existing = $db->fetch_array($query);

        if (empty($existing['sid'])) {
            $db->insert_query('settings', $setting);
        }
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

$plugins->add_hook('global_start', 'social_meta_tags_build');
$plugins->add_hook('forumdisplay_start', 'social_meta_tags_build');
$plugins->add_hook('showthread_start', 'social_meta_tags_build');
function social_meta_tags_build()
{
    global $thread, $forum, $mybb;
    global $open_meta_title, $open_meta_description, $open_meta_url, $open_meta_type, $open_meta_image, $social_meta_tags;

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

    if (!empty($forum['fid'])) {
        $open_meta_title = $forum['name'] . ($board_name !== '' ? ' - ' . $board_name : '');
        $open_meta_description = !empty($forum['description'])
            ? $forum['description']
            : $open_meta_description;
        $open_meta_url = social_meta_tags_absolute_url(get_forum_link((int)$forum['fid']), $board_url);
    }

    if (!empty($thread['tid'])) {
        $open_meta_title = $thread['subject'] . ($board_name !== '' ? ' - ' . $board_name : '');
        $open_meta_url = social_meta_tags_absolute_url(get_thread_link((int)$thread['tid']), $board_url);
        $open_meta_type = 'article';

        if (!empty($thread['image'])) {
            $open_meta_image = $thread['image'];
        }
    }

    $open_meta_title = htmlspecialchars_uni(strip_tags($open_meta_title));
    $open_meta_description = htmlspecialchars_uni(strip_tags($open_meta_description));
    $open_meta_url = htmlspecialchars_uni($open_meta_url);
    $open_meta_type = htmlspecialchars_uni($open_meta_type);
    $open_meta_image = htmlspecialchars_uni($open_meta_image);
    $image_meta_tags = '';

    if ($open_meta_image !== '') {
        $image_meta_tags = '<meta property="og:image" content="' . $open_meta_image . '" />' . "\n"
            . '<meta name="twitter:image" content="' . $open_meta_image . '" />' . "\n";
    }

    $social_meta_tags = '<meta property="og:title" content="' . $open_meta_title . '" />' . "\n"
        . '<meta property="og:description" content="' . $open_meta_description . '" />' . "\n"
        . '<meta property="og:url" content="' . $open_meta_url . '" />' . "\n"
        . '<meta property="og:type" content="' . $open_meta_type . '" />' . "\n"
        . '<meta name="twitter:card" content="summary_large_image" />' . "\n"
        . '<meta name="twitter:title" content="' . $open_meta_title . '" />' . "\n"
        . '<meta name="twitter:description" content="' . $open_meta_description . '" />' . "\n"
        . $image_meta_tags;
}

function social_meta_tags_absolute_url($url, $board_url)
{
    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }

    return $board_url . '/' . ltrim($url, '/');
}

function social_meta_tags_rebuild_settings()
{
    if (!function_exists('rebuild_settings')) {
        require_once MYBB_ROOT . 'inc/functions.php';
    }

    rebuild_settings();
}
