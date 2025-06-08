<?php
/**
 * Class Cloakmaker_Admin
 *
 * Handles the admin interface and custom post type registration.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Cloakmaker_Admin
{

    public function __construct()
    {
        add_action('init', [$this, 'register_cloaked_link_cpt']);
        add_action('add_meta_boxes', [$this, 'add_redirect_url_meta_box']);
        add_action('save_post_cloaked_link', [$this, 'save_redirect_url']);
        add_filter('manage_cloaked_link_posts_columns', [$this, 'add_clicks_column']);
        add_action('manage_cloaked_link_posts_custom_column', [$this, 'render_clicks_column'], 10, 2);
        add_action('add_meta_boxes', [$this, 'add_link_status_meta_box']);
        add_action('save_post_cloaked_link', [$this, 'save_link_status']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        $this->register_ajax_toggle();

    }

    public function register_cloaked_link_cpt()
    {
        $labels = [
            'name' => __('Cloaked Links', 'cloakmaker'),
            'singular_name' => __('Cloaked Link', 'cloakmaker'),
            'add_new' => __('Add New', 'cloakmaker'),
            'add_new_item' => __('Add New Cloaked Link', 'cloakmaker'),
            'edit_item' => __('Edit Cloaked Link', 'cloakmaker'),
            'new_item' => __('New Cloaked Link', 'cloakmaker'),
            'view_item' => __('View Cloaked Link', 'cloakmaker'),
            'search_items' => __('Search Cloaked Links', 'cloakmaker'),
            'not_found' => __('No cloaked links found', 'cloakmaker'),
            'not_found_in_trash' => __('No cloaked links found in Trash', 'cloakmaker'),
            'menu_name' => __('Cloaked Links', 'cloakmaker'),
        ];

        $args = [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-admin-links',
            'supports' => ['title'],
            'has_archive' => false,
            'rewrite' => false,
            'capability_type' => 'post',
        ];

        register_post_type('cloaked_link', $args);
    }

    /**
     * Adds the meta box for target URL
     */
    public function add_redirect_url_meta_box()
    {
        add_meta_box(
            'cloakmaker_target_url',
            __('Redirect Target URL', 'cloakmaker'),
            [$this, 'render_redirect_url_meta_box'],
            'cloaked_link',
            'normal',
            'default'
        );
    }

    /**
     * Renders the input field for the target URL
     */
    public function render_redirect_url_meta_box($post)
    {
        $value = get_post_meta($post->ID, '_cloakmaker_target_url', true);
        ?>
        <label
            for="cloakmaker_target_url"><?php _e('Enter the destination URL for this cloaked link:', 'cloakmaker'); ?></label>
        <input type="url" id="cloakmaker_target_url" name="cloakmaker_target_url" value="<?php echo esc_attr($value); ?>"
            style="width:100%;" placeholder="https://example.com/affiliate-link" />
        <?php
    }

    /**
     * Saves the target URL when the post is saved
     */
    public function save_redirect_url($post_id)
    {
        if (array_key_exists('cloakmaker_target_url', $_POST)) {
            $url = esc_url_raw($_POST['cloakmaker_target_url']);
            update_post_meta($post_id, '_cloakmaker_target_url', $url);
        }
    }

    /**
     * Adds a new column for click count.
     */
    public function add_clicks_column($columns)
    {
        $columns['clicks'] = 'Clicks';
        $columns['enabled'] = 'Enabled';
        return $columns;
    }

    /**
     * Renders the click count in the custom column.
     */
    public function render_clicks_column($column, $post_id)
    {
        if ($column === 'clicks') {
            global $wpdb;
            $slug = get_post_field('post_name', $post_id);
            $table = $wpdb->prefix . 'cloakmaker_clicks';
            $count = $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE slug = %s", $slug)
            );
            echo intval($count);
        }

        if ($column === 'enabled') {
            $enabled = get_post_meta($post_id, '_cloakmaker_link_enabled', true);
            $enabled = ($enabled !== '0') ? 'checked' : '';
            ?>
            <label class="cloakmaker-switch">
                <input type="checkbox" class="cloakmaker-toggle" data-post-id="<?php echo $post_id; ?>" <?php echo $enabled; ?>>
                <span class="cloakmaker-slider"></span>
            </label>
            <?php
        }
    }


    public function add_link_status_meta_box()
    {
        add_meta_box(
            'cloakmaker_link_status',
            __('Link Status', 'cloakmaker'),
            [$this, 'render_link_status_meta_box'],
            'cloaked_link',
            'side',
            'high'
        );
    }

    public function render_link_status_meta_box($post)
    {
        $enabled = get_post_meta($post->ID, '_cloakmaker_link_enabled', true);
        $enabled = ($enabled !== '0'); // Default: enabled

        wp_nonce_field('cloakmaker_save_link_status', 'cloakmaker_link_status_nonce');
        ?>
        <label>
            <input type="checkbox" name="cloakmaker_link_enabled" value="1" <?php checked($enabled); ?>>
            <?php _e('Enable this link', 'cloakmaker'); ?>
        </label>
        <?php
    }

    public function save_link_status($post_id)
    {
        if (!isset($_POST['cloakmaker_link_status_nonce']) || !wp_verify_nonce($_POST['cloakmaker_link_status_nonce'], 'cloakmaker_save_link_status')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        if (!current_user_can('edit_post', $post_id))
            return;

        $enabled = isset($_POST['cloakmaker_link_enabled']) ? '1' : '0';
        update_post_meta($post_id, '_cloakmaker_link_enabled', $enabled);
    }

    public function enqueue_admin_assets()
    {
        wp_enqueue_style(
            'cloakmaker-admin-style',
            plugin_dir_url(__FILE__) . 'css/cloakmaker-admin.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'cloakmaker-admin-js',
            plugin_dir_url(__FILE__) . 'js/cloakmaker-admin.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }

    public function register_ajax_toggle()
    {
        add_action('wp_ajax_cloakmaker_toggle_enabled', [$this, 'ajax_toggle_enabled']);
    }

    public function ajax_toggle_enabled()
    {
        if (!current_user_can('edit_post', $_POST['post_id'])) {
            wp_send_json_error('No permission');
        }

        $post_id = intval($_POST['post_id']);
        $enabled = ($_POST['enabled'] === '1') ? '1' : '0';

        update_post_meta($post_id, '_cloakmaker_link_enabled', $enabled);

        wp_send_json_success('Updated');
    }


}
