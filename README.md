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

Activation automatically inserts `{$social_meta_tags}` before `{$stylesheets}` in the `headerinclude` template. The injected variable renders the complete Open Graph and Twitter metadata block. Deactivation removes the insertion.

If a customized `headerinclude` template does not contain `{$stylesheets}`, add this variable manually:

```html
{$social_meta_tags}
```

The individual `{$open_meta_*}` variables remain available for themes that need custom tag markup.

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

Uninstalling removes the `Social Meta Tags` setting group and its settings. It removes its activation-time template insertion before deleting settings and does not modify legacy Revolution settings.
