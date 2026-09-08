# Changelog

## 1.1.0 - 2026-09-08

- Added Admin CP options for Twitter card type, social title format, page-type metadata toggles, forum description usage, thread description source, maximum description length, thread image metadata, site name, optional locale, and default image dimensions.
- Added a default-on thread image metadata setting so admins can disable first-post/thread image previews while keeping configured fallback images.
- Changed new-install default metadata to use the installing board name and active theme logo when legacy Revolution settings are unavailable.
- Synchronize the `{$social_meta_tags}` template variable after themes are added, imported, or duplicated while the plugin is active.
- Expanded tests for configurable metadata output, setting synchronization, and theme lifecycle hook registration.

## 1.0.3 - 2026-09-07

- Fixed page-specific social previews so forum pages keep forum metadata even when a thread row exists in scope.
- Preserved custom setting values while synchronizing setting metadata on install and activation.
- Added release packaging automation for GitHub and Gitea.

## 1.0.0 - 2026-08-28

- Added configurable Open Graph and Twitter Card metadata for MyBB board, forum, and thread pages.
- Added board-level fallback description and image settings.
- Added thread title, description, URL, article type, and embedded image handling.
- Added automatic `{$social_meta_tags}` insertion into `headerinclude`.
- Added legacy Revolution theme metadata import for existing boards.
