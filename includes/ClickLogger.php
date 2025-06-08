<?php
// ClickLogger.php – Handles anonymous click logging for cloaked links

namespace Cloakmaker;

defined('ABSPATH') || exit;

class ClickLogger
{
    /**
     * Logs a click event by saving slug and timestamp.
     *
     * @param string $slug The slug of the cloaked link.
     */
    public static function log($slug)
    {
        if (empty($slug)) {
            return;
        }

        global $wpdb;

        $table = $wpdb->prefix . 'cloakmaker_clicks';

        $wpdb->insert(
            $table,
            [
                'slug' => sanitize_text_field($slug),
                'clicked_at' => current_time('mysql', 1), // Stored as GMT
            ],
            ['%s', '%s']
        );
    }
}
