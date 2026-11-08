<?php
/**
 * Plugin Name: NOVACELL Manager
 * Plugin URI: https://github.com/rafikpor-prog/WP-NovaCell
 * Description: Panel zarządzania treściami kliniki NOVACELL. Wydanie 0.6.2: oczyszczone opisy medyczne, przywracalne nadpisanie importu i dwujęzyczny CMS.
 * Version: 0.6.2
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Text Domain: novacell-manager
 */

defined( 'ABSPATH' ) || exit;

define( 'NC_MANAGER_VERSION', '0.6.2' );
define( 'NC_MANAGER_FILE', __FILE__ );
define( 'NC_MANAGER_PATH', plugin_dir_path( __FILE__ ) );

require_once NC_MANAGER_PATH . 'includes/class-content-types.php';
require_once NC_MANAGER_PATH . 'includes/class-metadata.php';
require_once NC_MANAGER_PATH . 'includes/class-editors.php';
require_once NC_MANAGER_PATH . 'includes/class-admin.php';
require_once NC_MANAGER_PATH . 'includes/class-installer.php';
require_once NC_MANAGER_PATH . 'includes/class-starter-import.php';
require_once NC_MANAGER_PATH . 'includes/class-bilingual.php';
require_once NC_MANAGER_PATH . 'includes/class-site-builder.php';
require_once NC_MANAGER_PATH . 'includes/class-full-import.php';
require_once NC_MANAGER_PATH . 'includes/class-language-editor.php';
require_once NC_MANAGER_PATH . 'includes/class-main-menu.php';
require_once NC_MANAGER_PATH . 'includes/class-preview-settings.php';
require_once NC_MANAGER_PATH . 'includes/class-diagnostics.php';
require_once NC_MANAGER_PATH . 'includes/class-preview-links.php';
require_once NC_MANAGER_PATH . 'includes/class-readiness.php';
require_once NC_MANAGER_PATH . 'includes/class-cleanup-update.php';
require_once NC_MANAGER_PATH . 'includes/class-plugin.php';

add_action( 'plugins_loaded', array( \NovaCell\Manager\Plugin::class, 'boot' ) );
register_activation_hook( __FILE__, array( \NovaCell\Manager\Installer::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \NovaCell\Manager\Installer::class, 'deactivate' ) );
