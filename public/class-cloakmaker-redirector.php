<?php
/**
 * Class Cloakmaker_Redirector
 *
 * Handles redirection for cloaked links via /go/{slug}
 */

use Cloakmaker\ClickLogger;

if (!defined('ABSPATH')) {
    exit;
}

class Cloakmaker_Redirector
{

    public function __construct()
    {
        add_action('init', [$this, 'add_redirect_endpoint']);
        add_action('template_redirect', [$this, 'maybe_redirect']);
    }

    /**
     * Registers the /go/{slug} rewrite rule.
     */
    public function add_redirect_endpoint()
    {
        add_rewrite_rule(
            '^go/([^/]*)/?',
            'index.php?cloakmaker_redirect=$matches[1]',
            'top'
        );

        add_rewrite_tag('%cloakmaker_redirect%', '([^&]+)');
    }

    /**
     * Checks if a redirect is requested and performs it.
     */
    public function maybe_redirect()
    {
        $slug = get_query_var('cloakmaker_redirect');

        if (!$slug) {
            return;
        }

        $post = get_page_by_path($slug, OBJECT, 'cloaked_link');

        if (!$post || $post->post_status !== 'publish') {
            wp_die('Link not found', 'Cloakmaker Error', ['response' => 404]);
        }

        $enabled = get_post_meta($post->ID, '_cloakmaker_link_enabled', true);
        if ($enabled === '0') {
            wp_die('This link is currently disabled.', 'Cloakmaker Blocked', ['response' => 403]);
        }

        // Rate limiting: block if more than 5 clicks today from same IP on same slug
        $rate_limit_enabled = get_option('cloakmaker_rate_limit_enabled');
        $max_clicks = intval(get_option('cloakmaker_rate_limit_max_clicks', 5));

        if ($rate_limit_enabled) {
            global $wpdb;
            $ip = ClickLogger::get_user_ip();
            $table = $wpdb->prefix . 'cloakmaker_clicks';

            $clicks_today = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table 
             WHERE slug = %s 
             AND ip_address = %s 
             AND DATE(clicked_at) = CURDATE()",
                    $slug,
                    $ip
                )
            );

            if ($clicks_today >= $max_clicks) {
                wp_die('Click limit exceeded for today. Try again tomorrow.', 'Cloakmaker Limit', ['response' => 429]);
            }
        }

        // Log the click (GDPR-compliant)
        ClickLogger::log($slug);

        // Get destination URL from a custom field
        $target_url = get_post_meta($post->ID, '_cloakmaker_target_url', true);

        if (!$target_url || !filter_var($target_url, FILTER_VALIDATE_URL)) {
            wp_die('Invalid or missing destination URL.', 'Cloakmaker Error', ['response' => 400]);
        }

        wp_redirect($target_url, 301);
        exit;
    }

}
