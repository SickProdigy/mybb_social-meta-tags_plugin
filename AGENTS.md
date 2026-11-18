# MyBB template scope

- MyBB may be bootstrapped by a scoped `require`. Hook output uses globals, but core template evaluation can run in the caller scope. Keep plugin output references scope-safe through the `global_intermediate` cached-template preparation, after core template caching and before header evaluation.
- Run `php tests/template_scope_test.php` when changing template output integration.
