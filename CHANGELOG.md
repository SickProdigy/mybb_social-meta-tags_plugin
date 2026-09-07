# Changelog

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
