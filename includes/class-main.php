<?php

/**
 * The main plugin class.
 *
 * This is the class that orchestrates the entire plugin.
 * It initializes all other components and registers hooks.
 *
 * @since      1.1.0
 * @package    Gastro_Rechner
 */

class Gastro_Rechner_Main {

    /**
     * The admin instance.
     *
     * @since    1.1.0
     * @access   protected
     * @var      Gastro_Rechner_Admin    $admin    Handles all admin functionality.
     */
    protected $admin;

    /**
     * The API instance.
     *
     * @since    1.1.0
     * @access   protected
     * @var      Gastro_Rechner_Api    $api    Handles all AJAX endpoints.
     */
    protected $api;

    /**
     * The shortcode instance.
     *
     * @since    1.1.0
     * @access   protected
     * @var      Gastro_Rechner_Shortcode    $shortcode    Handles the shortcode.
     */
    protected $shortcode;

    /**
     * Initialize the plugin.
     *
     * @since    1.1.0
     */
    public function init() {
        $this->load_dependencies();
        $this->initialize_components();
        $this->register_hooks();
    }

    /**
     * Load all required dependencies.
     *
     * @since    1.1.0
     * @access   private
     */
    private function load_dependencies() {
        // All dependencies are loaded via the autoloader
    }

    /**
     * Initialize all components.
     *
     * @since    1.1.0
     * @access   private
     */
    private function initialize_components() {
        $this->admin = new Gastro_Rechner_Admin();
        $this->api = new Gastro_Rechner_Api();
        $this->shortcode = new Gastro_Rechner_Shortcode();
    }

    /**
     * Register all hooks.
     *
     * @since    1.1.0
     * @access   private
     */
    private function register_hooks() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Check plugin database updates
        add_action('plugins_loaded', array($this, 'check_for_updates'));
    }

    /**
     * Enqueue scripts and styles for the front-end.
     *
     * @since    1.1.0
     */
    public function enqueue_scripts() {
        // Enqueue Font Awesome
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );
        
        // Enqueue the React bundle
        wp_enqueue_script(
            'gastro-rechner-script',
            GASTRO_RECHNER_PLUGIN_URL . 'build/bundle.js',
            ['wp-element'], // Ensures compatibility with WordPress React system
            filemtime(GASTRO_RECHNER_PLUGIN_DIR . 'build/bundle.js'), // Version based on file modification time
            true // Load in footer
        );

        // Enqueue the CSS styles
        wp_enqueue_style(
            'gastro-rechner-styles',
            GASTRO_RECHNER_PLUGIN_URL . 'build/styles.css',
            [],
            filemtime(GASTRO_RECHNER_PLUGIN_DIR . 'build/styles.css') // Version based on file modification time
        );

        // Localize script to pass data to React
        wp_localize_script('gastro-rechner-script', 'gastroRechnerData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gastro_rechner_nonce'),
            'settings' => $this->get_settings_for_frontend(),
            'loginUrl' => wp_login_url(get_permalink())
        ]);
    }

    /**
     * Get settings for frontend.
     *
     * @since    1.1.0
     * @return   array    Settings for the frontend.
     */
    public function get_settings_for_frontend() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gastro_rechner_admin';
        
        $settings = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");
        
        // Get current user information
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $user_roles = $current_user->roles;
        
        // Check if user has author or admin role
        $can_submit = false;
        $is_admin = false;
        
        if (!empty($user_roles)) {
            $can_submit = in_array('administrator', $user_roles) || in_array('author', $user_roles);
            $is_admin = in_array('administrator', $user_roles);
        }
        
        return [
            'flowCash' => isset($settings->flow_cash) ? floatval($settings->flow_cash) : 100.00,
            'tipFactor' => isset($settings->tip_factor) ? floatval($settings->tip_factor) : 2.00,
            'showFlowCashToggle' => isset($settings->flow_cash_toggle) ? (bool)$settings->flow_cash_toggle : true,
            'defaultColorScheme' => isset($settings->default_color_scheme) ? $settings->default_color_scheme : 'light-cold',
            'currentUser' => $current_user->display_name,
            'userId' => $user_id,
            'isLoggedIn' => is_user_logged_in(),
            'canSubmit' => $can_submit,
            'isAdmin' => $is_admin
        ];
    }

    /**
     * Check for database updates.
     *
     * @since    1.1.0
     */
    public function check_for_updates() {
        global $wpdb;
        $admin_table_name = $wpdb->prefix . 'gastro_rechner_admin';

        // Check if the flow_cash_toggle column already exists
        $flow_cash_toggle_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = %s AND COLUMN_NAME = %s",
                $admin_table_name,
                'flow_cash_toggle'
            )
        );

        if (empty($flow_cash_toggle_exists)) {
            // Add the flow_cash_toggle column
            $wpdb->query("ALTER TABLE $admin_table_name ADD flow_cash_toggle BOOLEAN NOT NULL DEFAULT TRUE");
        }

        // Check if the default_color_scheme column already exists
        $color_scheme_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = %s AND COLUMN_NAME = %s",
                $admin_table_name,
                'default_color_scheme'
            )
        );

        if (empty($color_scheme_exists)) {
            // Add the default_color_scheme column
            $wpdb->query("ALTER TABLE $admin_table_name ADD default_color_scheme varchar(20) NOT NULL DEFAULT 'light-cold'");
        }
    }
}