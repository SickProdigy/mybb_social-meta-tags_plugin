# MyBB Social Meta Tags Plugin

Standalone Open Graph and Twitter metadata plugin for MyBB 1.8.

It was extracted from the Sick Gaming Revolution theme plugin so social metadata can be installed and maintained independently of any theme.

## Features

- Board-level metadata fallbacks for pages without forum or thread data.
- Forum titles, descriptions, and canonical forum URLs.
- Thread titles, article type, canonical thread URLs, and optional thread images.
- Configurable default description and image URL in Admin CP.
- MyBB friendly-URL support through `get_forum_link()` and `get_thread_link()`.
- HTML-safe template values.
- One-time import of legacy `revolution_theme_default_meta` and `revolution_theme_logo_url` values when the new settings are first created.

## Install

Copy the contents of `Upload/` into the MyBB installation root:

```text
Upload/inc/plugins/social_meta_tags.php -> public_html/inc/plugins/social_meta_tags.php
```

Then install and activate `Social Meta Tags` under Admin CP → Configuration → Plugins.

## Theme Integration

Add these tags to the theme's `headerinclude` template:

```html
<meta property="og:title" content="{$open_meta_title}" />
<meta property="og:description" content="{$open_meta_description}" />
<meta property="og:url" content="{$open_meta_url}" />
<meta property="og:type" content="{$open_meta_type}" />
<meta property="og:image" content="{$open_meta_image}" />

<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{$open_meta_title}" />
<meta name="twitter:description" content="{$open_meta_description}" />
<meta name="twitter:image" content="{$open_meta_image}" />
```

The Sick Gaming Revolution theme already contains these template references.

## Template Variables

- `{$open_meta_title}` — board, forum, or thread title.
- `{$open_meta_description}` — forum description or configured default.
- `{$open_meta_url}` — board, forum, or thread URL.
- `{$open_meta_type}` — `website` or `article`.
- `{$open_meta_image}` — thread image when available, otherwise the configured default image.

## Settings

The plugin creates a `Social Meta Tags` setting group containing:

- `Default Meta Description`
- `Default Image URL`

On first installation, values from the old Revolution plugin metadata settings are copied when available. The legacy settings are not deleted automatically.

## Uninstall

Uninstalling removes the `Social Meta Tags` setting group and its settings. It does not modify theme templates or legacy Revolution settings.
