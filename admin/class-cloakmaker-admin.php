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
    }

}
