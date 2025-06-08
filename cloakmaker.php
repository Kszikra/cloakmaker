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

    // Create or update the table structure
    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        slug VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        clicked_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        INDEX (slug),
        INDEX (clicked_at),
        INDEX (ip_address)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql); // Will alter the table if columns are missing

    // Extra safety: manually check and add 'ip_address' column if dbDelta misses it
    $columns = $wpdb->get_col("DESC $table_name", 0);
    if (!in_array('ip_address', $columns)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN ip_address VARCHAR(45) NOT NULL AFTER slug");
    }
}


