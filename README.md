# MyBB Social Meta Tags Plugin

MyBB 1.8 plugin that adds configurable Open Graph and Twitter Card metadata to board, forum, and thread pages. It works independently of the active theme and provides sensible board-level fallbacks when page-specific metadata is unavailable.

## Features

- Board-level metadata fallbacks for pages without forum or thread data.
- Forum titles, descriptions, and absolute forum URLs.
- Thread titles, article type, absolute thread URLs, and optional thread images.
- Configurable default description and image URL in Admin CP.
- MyBB friendly-URL support through `get_forum_link()` and `get_thread_link()`.
- HTML-safe template values.
- One-time import of legacy `revolution_theme_default_meta` and `revolution_theme_logo_url` values when the new settings are first created.

## Requirements

- MyBB 1.8.x
- A theme whose `headerinclude` template contains `{$stylesheets}`, or manual template integration

## Install

Copy the contents of `Upload/` into the MyBB installation root:

```text
Upload/inc/plugins/social_meta_tags.php -> public_html/inc/plugins/social_meta_tags.php
```

Then install and activate `Social Meta Tags` under Admin CP → Configuration → Plugins.

## Theme Integration

Activation removes any marker-delimited fallback metadata block and inserts `{$social_meta_tags}` before `{$stylesheets}` in each theme's `headerinclude` template. The injected variable renders the configured Open Graph and Twitter metadata block. Deactivation removes the plugin variable and restores a basic fallback built from the board name, board URL, and active theme logo.

If a customized `headerinclude` template lacks `{$stylesheets}`, automatic insertion cannot run. Add this variable manually and remove any duplicate social metadata:

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

When neither a thread image nor a configured default image is available, the plugin omits both `og:image` and `twitter:image` instead of rendering empty image tags.

## Settings

The plugin creates a `Social Meta Tags` setting group containing:

- `Default Meta Description`
- `Default Image URL`

On first installation, values from the old Revolution plugin metadata settings are copied when available. The legacy settings are not deleted automatically.

## Output

The generated block includes:

- `og:title`, `og:description`, `og:url`, `og:type`, and `og:image`
- `twitter:card`, `twitter:title`, `twitter:description`, and `twitter:image`

The plugin supplies social-sharing URLs through `og:url`; it does not add a separate HTML `rel="canonical"` link.

## Testing

Run the dependency-free test suite from the repository root:

```bash
php tests/social_meta_tags_test.php
```

The suite uses lightweight MyBB stubs and does not require a MyBB installation.

## Uninstall

Uninstalling removes the `Social Meta Tags` setting group and its settings. MyBB deactivates the plugin first, removing its template insertion. Legacy Revolution settings are not modified.

## License

Copyright (C) 2026 SickProdigy.

This project is licensed under the GNU General Public License, version 3 or any later version.
