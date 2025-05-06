<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.1.0
 * @package    Gastro_Rechner
 */

class Gastro_Rechner_Admin {

    /**
     * Admin table name without prefix.
     *
     * @since    1.1.0
     * @access   private
     * @var      string    $admin_table    Admin table name without prefix.
     */
    private $admin_table = 'gastro_rechner_admin';

    /**
     * Submissions table name without prefix.
     *
     * @since    1.1.0
     * @access   private
     * @var      string    $submissions_table    Submissions table name without prefix.
     */
    private $submissions_table = 'gastro_rechner_submissions';

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.1.0
     */
    public function __construct() {
        // Register the admin menu
        add_action('admin_menu', array($this, 'create_admin_menu'));
        add_action('admin_menu', array($this, 'add_submissions_page'));
    }

    /**
     * Register the admin menu.
     *
     * @since    1.1.0
     */
    public function create_admin_menu() {
        add_menu_page(
            'Gastro Rechner Einstellungen',  // Page Title
            'Gastro Rechner',                // Menu Title
            'manage_options',                // Capability required
            'gastro-rechner-settings',       // Menu Slug (used in the URL)
            array($this, 'render_settings_page'),  // Callback function to render the page
            'dashicons-calculator',          // Icon for the menu entry
            4                                // Position in the WordPress Admin Menu
        );
    }

    /**
     * Add submenu for viewing submissions.
     *
     * @since    1.1.0
     */
    public function add_submissions_page() {
        add_submenu_page(
            'gastro-rechner-settings',       // Parent slug
            'Submissions',                   // Page title
            'Submissions',                   // Menu title
            'manage_options',                // Capability
            'gastro-rechner-submissions',    // Menu slug
            array($this, 'render_submissions_page') // Function
        );
    }

    /**
     * Render the settings page.
     *
     * @since    1.1.0
     */
    public function render_settings_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->admin_table;

        // Fetch current settings from the database
        $settings = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");

        // If no settings exist, use default values
        $default_flow_cash = isset($settings->flow_cash) ? $settings->flow_cash : 100.00;
        $default_team_tip = isset($settings->tip_factor) ? $settings->tip_factor : 2.00;

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flow_cash'], $_POST['tip_factor'])) {
            // Verify nonce for security
            if (!isset($_POST['gastro_nonce']) || !wp_verify_nonce($_POST['gastro_nonce'], 'gastro_update_settings_nonce')) {
                wp_die('Security check failed'); // Exit if nonce verification fails
            }

            // Sanitize and validate input
            $flow_cash_amount = floatval($_POST['flow_cash']);
            $tip_factor = floatval($_POST['tip_factor']);
            $flow_cash_toggle = isset($_POST['flow_cash_toggle']) ? 1 : 0;
            $default_color_scheme = sanitize_text_field($_POST['default_color_scheme']);

            // Save values to the database
            if ($settings) {
                $wpdb->update(
                    $table_name,
                    array(
                        'flow_cash' => $flow_cash_amount, 
                        'tip_factor' => $tip_factor, 
                        'flow_cash_toggle' => $flow_cash_toggle,
                        'default_color_scheme' => $default_color_scheme
                    ),
                    array('id' => $settings->id)
                );
            } else {
                $wpdb->insert(
                    $table_name,
                    array(
                        'flow_cash' => $flow_cash_amount, 
                        'tip_factor' => $tip_factor, 
                        'flow_cash_toggle' => $flow_cash_toggle,
                        'default_color_scheme' => $default_color_scheme
                    )
                );
            }

            // Fetch updated settings from the database
            $settings = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");
            $default_flow_cash = $settings->flow_cash;
            $default_team_tip = $settings->tip_factor;

            // Add a success message using WordPress settings API
            add_settings_error('gastro_rechner_settings', 'gastro_rechner_settings_updated', 'Settings updated successfully!', 'updated');
            settings_errors('gastro_rechner_settings');
        }

        // Include the admin template
        include GASTRO_RECHNER_PLUGIN_DIR . 'admin/settings-page.php';
    }

    /**
     * Render the submissions page.
     *
     * @since    1.1.0
     */
    public function render_submissions_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->submissions_table;
        
        // Handle export request
        if (isset($_POST['export_submissions']) && check_admin_referer('gastro_export_submissions_nonce')) {
            $this->export_submissions();
            return;
        }
        
        // Get submissions from database with pagination
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($current_page - 1) * $per_page;
        
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_pages = ceil($total_items / $per_page);
        
        $submissions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d OFFSET %d",
                $per_page, $offset
            )
        );
        
        // Include the submissions template
        include GASTRO_RECHNER_PLUGIN_DIR . 'admin/submissions-page.php';
    }

    /**
     * Export submissions to CSV.
     *
     * @since    1.1.0
     */
    public function export_submissions() {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->submissions_table;
        
        $submissions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC");
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=gastro-rechner-submissions-' . date('Y-m-d') . '.csv');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, array('ID', 'Name', 'Total Sales (€)', 'Cash Sales (€)', 'Team Tip (€)', 'Flow Cash Received', 'Date'));
        
        // Add data rows
        foreach ($submissions as $submission) {
            fputcsv($output, array(
                $submission->id,
                $submission->name,
                $submission->total_sales,
                $submission->sales_cash,
                $submission->team_tip,
                $submission->flow_cash_received ? 'Yes' : 'No',
                $submission->timestamp
            ));
        }
        
        fclose($output);
        exit;
    }
}