<?php
// ClickLogger.php – Handles anonymous click logging for cloaked links

namespace Cloakmaker;

defined('ABSPATH') || exit;

class ClickLogger
{

    public static function log($slug)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'cloakmaker_clicks';

        // Get user IP address
        $ip_address = self::get_user_ip();

        $wpdb->insert(
            $table_name,
            [
                'slug' => sanitize_text_field($slug),
                'ip_address' => $ip_address,
                'clicked_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s']
        );
    }

    // Retrieve the user's real IP address
    public static function get_user_ip()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
}

