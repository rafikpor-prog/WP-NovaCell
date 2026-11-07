<?php
/**
 * Plugin Name: NOVACELL Manager
 * Plugin URI: https://github.com/rafikpor-prog/WP-NovaCell
 * Description: Wewnętrzny system zarządzania informacjami kliniki NOVACELL.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Text Domain: novacell-manager
 */

defined( 'ABSPATH' ) || exit;

define( 'NC_MANAGER_VERSION', '0.1.0' );
define( 'NC_MANAGER_FILE', __FILE__ );
define( 'NC_MANAGER_PATH', plugin_dir_path( __FILE__ ) );

require_once NC_MANAGER_PATH . 'includes/class-content-types.php';
require_once NC_MANAGER_PATH . 'includes/class-metadata.php';
require_once NC_MANAGER_PATH . 'includes/class-editors.php';
require_once NC_MANAGER_PATH . 'includes/class-admin.php';
require_once NC_MANAGER_PATH . 'includes/class-plugin.php';

add_action( 'plugins_loaded', array( '\\NovaCell\\Manager\\Plugin', 'boot' ) );

register_activation_hook(
    __FILE__,
    static function (): void {
        \NovaCell\Manager\Content_Types::register();
        flush_rewrite_rules();
    }
);

register_deactivation_hook(
    __FILE__,
    static function (): void {
        flush_rewrite_rules();
    }
);
