<?php
/** Regression test: NOVACELL admin submenus must be registered after parent. */
declare(strict_types=1);
define('ABSPATH', '/wordpress/');
$GLOBALS['hooks'] = $GLOBALS['admin_parents'] = $GLOBALS['admin_screens'] = $GLOBALS['calls'] = [];
function plugin_dir_path(string $p): string { return dirname($p) . '/'; }
function add_action(string $hook, $cb, int $priority=10, int $argc=1): void {
    $GLOBALS['hooks'][$hook][$priority][] = $cb;
}
function add_filter(string $hook, $cb, int $priority=10, int $argc=1): void {
    add_action($hook,$cb,$priority,$argc);
}
function register_activation_hook($file,$cb): void {}
function register_deactivation_hook($file,$cb): void {}
function current_user_can(string $cap): bool { return $cap === 'manage_options'; }
function admin_trigger(string $hook): void {
    $list = $GLOBALS['hooks'][$hook] ?? [];
    ksort($list, SORT_NUMERIC);
    foreach ($list as $callbacks) {
        foreach ($callbacks as $cb) { $cb(); }
    }
}
function add_menu_page($title,$label,$cap,$slug,$cb,$icon='',$pos=null): string {
    $GLOBALS['admin_parents'][$slug] = strtolower(str_replace(' ', '-', $label));
    $GLOBALS['calls'][] = ['parent',$slug];
    return 'toplevel_page_' . $slug;
}
function add_submenu_page($parent,$title,$label,$cap,$slug,$cb,$pos=null): string {
    // Simplified get_plugin_page_hookname() behavior from WordPress core.
    $type = $GLOBALS['admin_parents'][$parent] ?? 'admin';
    $hook = $type . '_page_' . $slug;
    $GLOBALS['admin_screens'][$slug] = $hook;
    $GLOBALS['calls'][] = ['child',$slug,$hook];
    return $hook;
}
require dirname(__DIR__) . '/plugins/novacell-manager/novacell-manager.php';
admin_trigger('plugins_loaded');
admin_trigger('admin_menu');
foreach ([
    'novacell-pages','novacell-home-builder','novacell-starter',
    'novacell-preview-settings','novacell-diagnostics',
    'novacell-full-import','novacell-main-menu','novacell-readiness'
] as $slug) {
    $expected = 'novacell-manager_page_' . $slug;
    $actual = $GLOBALS['admin_screens'][$slug] ?? 'not-registered';
    if ($actual !== $expected) {
        throw new RuntimeException($slug . ': ' . $actual . ' instead of ' . $expected);
    }
}
if ($GLOBALS['calls'][0] !== ['parent','novacell-manager']) {
    throw new RuntimeException('Submenu was registered before its parent.');
}
echo "PASS: NOVACELL admin parent first and 8/8 valid submenu hooks.\n";
