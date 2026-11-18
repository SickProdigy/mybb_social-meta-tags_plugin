<?php
define('IN_MYBB', 1);
class ScopeTestPlugins { public function add_hook($hook, $callback) {} }
$plugins = new ScopeTestPlugins();
require __DIR__ . '/../Upload/inc/plugins/social_meta_tags.php';
set_error_handler(function ($severity, $message) { throw new RuntimeException($message); });
$templates = (object) array('cache' => array());
$names = array('social_meta_tags');
foreach ($names as $name) {
    $templates->cache['headerinclude'] = '{$' . $name . '}';
    $templates->cache['index'] = '{$' . $name . '}';
    unset($GLOBALS[$name]);
    social_meta_tags_prepare_template_scope();
    // Match MyBB templates::get() escaping before core eval().
    $render = function ($source) {
        $source = str_replace("\\'", "'", addslashes($source));
        return eval('return "' . $source . '";');
    };
    if ($render($templates->cache['headerinclude']) !== '') { throw new RuntimeException('Missing output fallback'); }
    $GLOBALS[$name] = '<span>generated output</span>';
    social_meta_tags_prepare_template_scope();
    foreach ($templates->cache as $source) {
        if ($render($source) !== $GLOBALS[$name]) { throw new RuntimeException('Scoped output lost'); }
    }
}
echo "PASS scoped template output and absent-output fallback\n";
