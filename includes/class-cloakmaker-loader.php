<?php
/**
 * Class Cloakmaker_Loader
 *
 * Initializes the Cloakmaker plugin components.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Cloakmaker_Loader
{

    public function __construct()
    {
        $this->load_dependencies();
        $this->initialize_hooks();
    }

    /**
     * Load required files for the plugin
     */
    private function load_dependencies()
    {
        require_once CLOAKMAKER_PLUGIN_DIR . 'admin/class-cloakmaker-admin.php';
        new Cloakmaker_Admin();
        require_once CLOAKMAKER_PLUGIN_DIR . 'public/class-cloakmaker-redirector.php';
        new Cloakmaker_Redirector();

    }

    /**
     * Register WordPress hooks and actions
     */
    private function initialize_hooks()
    {
        // Example: add_action( 'init', [ $this, 'custom_init' ] );
        // Hook registrations will go here
    }
}
