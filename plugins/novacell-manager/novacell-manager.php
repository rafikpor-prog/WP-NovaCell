<?php
/**
 * Plugin Name: NOVACELL Manager
 * Plugin URI: https://github.com/rafikpor-prog/WP-NovaCell
 * Description: Wewnętrzny system zarządzania informacjami kliniki NOVACELL.
 * Version: 0.1.1
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Text Domain: novacell-manager
 */

defined( 'ABSPATH' ) || exit;

define( 'NC_MANAGER_VERSION', '0.1.1' );
define( 'NC_MANAGER_FILE', __FILE__ );
define( 'NC_MANAGER_PATH', plugin_dir_path( __FILE__ ) );

require_once NC_MANAGER_PATH . 'includes/class-content-types.php';
require_once NC_MANAGER_PATH . 'includes/class-metadata.php';
require_once NC_MANAGER_PATH . 'includes/class-editors.php';
require_once NC_MANAGER_PATH . 'includes/class-admin.php';
require_once NC_MANAGER_PATH . 'includes/class-installer.php';
require_once NC_MANAGER_PATH . 'includes/class-plugin.php';

add_action( 'plugins_loaded', array( '\\NovaCell\\Manager\\Plugin', 'boot' ) );

register_activation_hook( __FILE__, array( \NovaCell\Manager\Installer::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \NovaCell\Manager\Installer::class, 'deactivate' ) );
