<?php
/**
 * Plugin Name: Cloakmaker
 * Plugin URI: https://yourdomain.com
 * Description: Affiliate link cloaking, tracking, and management for WordPress.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourdomain.com
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: cloakmaker
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Don't allow direct access
}

// Plugin constants
define('CLOAKMAKER_VERSION', '1.0.0');
define('CLOAKMAKER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CLOAKMAKER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Placeholder: Include loader/init files here later

// Load the loader class
require_once CLOAKMAKER_PLUGIN_DIR . 'includes/class-cloakmaker-loader.php';
require_once CLOAKMAKER_PLUGIN_DIR . 'includes/ClickLogger.php';

// Run the plugin
function run_cloakmaker()
{
    $loader = new Cloakmaker_Loader();
}
run_cloakmaker();

register_activation_hook(__FILE__, 'cloakmaker_create_clicks_table');

function cloakmaker_create_clicks_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'cloakmaker_clicks';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        slug VARCHAR(255) NOT NULL,
        clicked_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        INDEX (slug),
        INDEX (clicked_at)
    ) $charset_collate;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

