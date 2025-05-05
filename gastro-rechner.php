<?php

/*
 * Plugin Name: Gastro-Rechner
 * Description: Ein professionelles Plugin für Gastronomen zur Berechnung von Umsätzen und Trinkgeldern.
 * Version: 1.1
 * Author: Your Company
 * Author URI: https://yourcompany.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gastro-rechner
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.2
 * Last updated: May 5, 2025
*/

// Verhindern, dass das Plugin direkt aufgerufen wird
if ( ! defined( 'ABSPATH' ) ) {
	exit("Not with me..."); // Exit if accessed directly
}

// On activation: Create the database and add default values
function gastro_rechner_activate() {
	global $wpdb;
	$admin_table_name = $wpdb->prefix . 'gastro_rechner_admin';
	$submissions_table_name = $wpdb->prefix . 'gastro_rechner_submissions';
	$charset_collate = $wpdb->get_charset_collate();
	
	// Create admin settings table with improved structure
	$sql1 = "CREATE TABLE IF NOT EXISTS $admin_table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		flow_cash decimal(10,2) NOT NULL DEFAULT 100.00,
		tip_factor decimal(5,2) NOT NULL DEFAULT 2.00,
		flow_cash_toggle BOOLEAN NOT NULL DEFAULT TRUE,
		timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		last_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	) $charset_collate;";

	// Create submissions table with improved structure and added flow_cash column
	$sql2 = "CREATE TABLE IF NOT EXISTS $submissions_table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL,
		total_sales decimal(10,2) NOT NULL,
		sales_cash decimal(10,2) NOT NULL,
		team_tip decimal(10,2) NOT NULL DEFAULT 0.00,
		flow_cash_received BOOLEAN NOT NULL DEFAULT FALSE,
		flow_cash decimal(10,2) NULL DEFAULT NULL,
		user_id bigint(20) NULL,
		timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY user_id (user_id),
		KEY date_idx (timestamp)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql1 );
	dbDelta( $sql2 );

	// Only insert default values if table is empty
	$exists = $wpdb->get_var("SELECT COUNT(*) FROM $admin_table_name");
	if ($exists == 0) {
		$wpdb->insert( $admin_table_name, array(
			'flow_cash' => 100.00,
			'tip_factor' => 2.0,
			'flow_cash_toggle' => true,
			'timestamp' => current_time('mysql'),
		));
	}
}
register_activation_hook( __FILE__, 'gastro_rechner_activate' );

// On deactivation: Clean up the database
function gastro_rechner_deactivate() {
	global $wpdb;
	$admin_table_name = $wpdb->prefix . 'gastro_rechner_admin';
	$submissions_table_name = $wpdb->prefix . 'gastro_rechner_submissions';
	// Löschen der Tabelle für die Rezeptdaten
	$sql1 = "DROP TABLE IF EXISTS $admin_table_name;";
	$sql2 = "DROP TABLE IF EXISTS $submissions_table_name;";
	$wpdb->query($sql1);
	$wpdb->query($sql2);
}
register_deactivation_hook( __FILE__, 'gastro_rechner_deactivate' );

// Add a new column `flow_cash_toggle` to the `gastro_rechner_admin` table with a default value of true.
function gastro_rechner_update_table() {
    global $wpdb;
    $admin_table_name = $wpdb->prefix . 'gastro_rechner_admin';

    // Check if the column already exists
    $column_exists = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = %s AND COLUMN_NAME = %s",
            $admin_table_name,
            'flow_cash_toggle'
        )
    );

    if (empty($column_exists)) {
        // Add the new column
        $wpdb->query("ALTER TABLE $admin_table_name ADD flow_cash_toggle BOOLEAN NOT NULL DEFAULT TRUE");
    }
}
add_action('plugins_loaded', 'gastro_rechner_update_table');

// Define the gastro_create_admin_menu function to register the admin menu settings page.
function gastro_create_admin_menu() {
	add_menu_page(
        'Gastro Rechner Einstellungen',  // Page Title
        'Gastro Rechner',                // Menu Title
        'manage_options',                // Capability required
        'gastro-rechner-settings',       // Menu Slug (used in the URL)
        'gastro_settings_page',          // Callback function to render the page
        'dashicons-calculator',          // Icon for the menu entry
        4                                // Position in the WordPress Admin Menu
    );
}
add_action('admin_menu', 'gastro_create_admin_menu'); // Hook the function into WordPress Admin Menu

// Add submenu for viewing submissions
function gastro_rechner_add_submissions_page() {
    add_submenu_page(
        'gastro-rechner-settings',       // Parent slug
        'Submissions',                   // Page title
        'Submissions',                   // Menu title
        'manage_options',                // Capability
        'gastro-rechner-submissions',    // Menu slug
        'gastro_rechner_submissions_page' // Function
    );
}
add_action('admin_menu', 'gastro_rechner_add_submissions_page');

// Define the gastro_settings_page function to render the settings page.
function gastro_settings_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gastro_rechner_admin'; // Define the table name

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

        // Save values to the database
        if ($settings) {
            $wpdb->update(
                $table_name,
                ['flow_cash' => $flow_cash_amount, 'tip_factor' => $tip_factor, 'flow_cash_toggle' => $flow_cash_toggle],
                ['id' => $settings->id]
            );
        } else {
            $wpdb->insert(
                $table_name,
                ['flow_cash' => $flow_cash_amount, 'tip_factor' => $tip_factor, 'flow_cash_toggle' => $flow_cash_toggle]
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

    // Display the settings page
    ?>
    <div class="wrap">
        <h1>Gastro Rechner Einstellungen</h1>
		<h4>Hier können Sie die Einstellungen für den Gastro-Rechner anpassen.</h4>
        
        <!-- Instructions on how to add to front page -->
        <div class="notice notice-info">
            <h3>So fügen Sie den Gastro-Rechner zu Ihrer Webseite hinzu:</h3>
            <ol>
                <li>Fügen Sie den Shortcode <code>[gastro_rechner]</code> in einen Beitrag oder eine Seite ein.</li>
                <li>Für angepasste Darstellung können Sie Attribute hinzufügen, z.B.: <code>[gastro_rechner title="Mein Rechner" theme="dark"]</code></li>
                <li>Verfügbare Attribute: <code>title</code> (Titel), <code>show_name</code> (true/false), <code>theme</code> (light/dark)</li>
            </ol>
        </div>
        
        <form method="POST">
            <?php wp_nonce_field('gastro_update_settings_nonce', 'gastro_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th><label for="flow_cash">Wechselgeld (€)</label></th>
                    <td>
                        <input type="number" name="flow_cash" step="0.01" value="<?php echo esc_attr($default_flow_cash); ?>" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="tip_factor">Team-Tip %</label></th>
                    <td>
                        <input type="number" name="tip_factor" step="0.1" value="<?php echo esc_attr($default_team_tip); ?>" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="flow_cash_toggle">Show Flow Cash Switch on Front End?</label></th>
                    <td>
                        <input type="checkbox" name="flow_cash_toggle" id="flow_cash_toggle" value="1" <?php checked(isset($settings->flow_cash_toggle) ? $settings->flow_cash_toggle : true); ?>>
                    </td>
                </tr>
            </table>

            <?php submit_button('Save'); ?>
        </form>
    </div>
    <?php
}

// Function to render the submissions page
function gastro_rechner_submissions_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gastro_rechner_submissions';
    
    // Handle export request
    if (isset($_POST['export_submissions']) && check_admin_referer('gastro_export_submissions_nonce')) {
        gastro_rechner_export_submissions();
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
    
    // Display the page
    ?>
    <div class="wrap">
        <h1>Gastro Rechner Submissions</h1>
        
        <!-- Export Form -->
        <form method="post" action="">
            <?php wp_nonce_field('gastro_export_submissions_nonce'); ?>
            <input type="hidden" name="export_submissions" value="1">
            <p>
                <button type="submit" class="button button-primary">Export to CSV</button>
            </p>
        </form>
        
        <!-- Submissions Table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Total Sales (€)</th>
                    <th>Cash Sales (€)</th>
                    <th>Team Tip (€)</th>
                    <th>Flow Cash Received</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)): ?>
                    <tr>
                        <td colspan="7">No submissions found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?php echo esc_html($submission->id); ?></td>
                            <td><?php echo esc_html($submission->name); ?></td>
                            <td><?php echo number_format($submission->total_sales, 2); ?> €</td>
                            <td><?php echo number_format($submission->sales_cash, 2); ?> €</td>
                            <td><?php echo number_format($submission->team_tip, 2); ?> €</td>
                            <td><?php echo $submission->flow_cash_received ? 'Yes' : 'No'; ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($submission->timestamp)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="pagination-links">
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $total_pages,
                            'current' => $current_page
                        ));
                        ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

// Function to export submissions to CSV
function gastro_rechner_export_submissions() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gastro_rechner_submissions';
    
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

// Enqueue scripts and styles for the front-end
function gastro_rechner_enqueue_scripts() {
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
        plugins_url('/build/bundle.js', __FILE__),
        ['wp-element'], // Ensures compatibility with WordPress React system
        filemtime(plugin_dir_path(__FILE__) . 'build/bundle.js'), // Version based on file modification time
        true // Load in footer
    );

    // Enqueue the CSS styles
    wp_enqueue_style(
        'gastro-rechner-styles',
        plugins_url('/build/styles.css', __FILE__),
        [],
        filemtime(plugin_dir_path(__FILE__) . 'build/styles.css') // Version based on file modification time
    );

    // Localize script to pass data to React
    wp_localize_script('gastro-rechner-script', 'gastroRechnerData', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gastro_rechner_nonce'),
        'settings' => gastro_get_settings_for_frontend()
    ]);
}
add_action('wp_enqueue_scripts', 'gastro_rechner_enqueue_scripts');

// Get settings for frontend
function gastro_get_settings_for_frontend() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gastro_rechner_admin';
    
    $settings = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");
    
    return [
        'flowCash' => isset($settings->flow_cash) ? floatval($settings->flow_cash) : 100.00,
        'tipFactor' => isset($settings->tip_factor) ? floatval($settings->tip_factor) : 2.00,
        'showFlowCashToggle' => isset($settings->flow_cash_toggle) ? (bool)$settings->flow_cash_toggle : true,
        'currentUser' => wp_get_current_user()->display_name, // Add current user's display name
        'isLoggedIn' => is_user_logged_in() // Add login status
    ];
}

// Improved shortcode with attributes
function gastro_rechner_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts([
        'title' => 'Gastro-Rechner',
        'show_name' => 'true',
        'theme' => 'light'
    ], $atts, 'gastro_rechner');
    
    // Convert attributes to data attributes for React
    $data_atts = '';
    foreach ($atts as $key => $value) {
        $data_atts .= ' data-' . $key . '="' . esc_attr($value) . '"';
    }
    
    // Return the container with data attributes
    return '<div id="gastro-rechner-root"' . $data_atts . '></div>';
}
add_shortcode('gastro_rechner', 'gastro_rechner_shortcode');

// AJAX handler for form submissions
function gastro_rechner_submit_data() {
    // Check nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gastro_rechner_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }
    
    // Validate and sanitize input data
    $name = sanitize_text_field($_POST['name'] ?? '');
    $total_sales = floatval($_POST['totalSales'] ?? 0);
    $sales_cash = floatval($_POST['salesCash'] ?? 0);
    
    // Get settings for calculations
    global $wpdb;
    $admin_table_name = $wpdb->prefix . 'gastro_rechner_admin';
    $settings = $wpdb->get_row("SELECT * FROM $admin_table_name LIMIT 1");
    $tip_factor = isset($settings->tip_factor) ? floatval($settings->tip_factor) : 2.00;
    $flow_cash = isset($settings->flow_cash) ? floatval($settings->flow_cash) : 100.00;
    
    // Calculate team tip using the new formula
    $team_tip = $total_sales * ($tip_factor / 100);
    
    // Process flow cash received status
    $flow_cash_received = isset($_POST['flowCashReceived']) ? (bool)$_POST['flowCashReceived'] : false;
    
    // Basic validation - updated to only check for positive total_sales
    if (empty($name) || $total_sales <= 0) {
        wp_send_json_error(['message' => 'Invalid submission data. Name is required and Total Sales must be positive.']);
        return;
    }
    
    // Insert submission into database
    $submissions_table_name = $wpdb->prefix . 'gastro_rechner_submissions';
    
    $submission_data = [
        'name' => $name,
        'total_sales' => $total_sales,
        'sales_cash' => $sales_cash,
        'team_tip' => $team_tip,
        'flow_cash_received' => $flow_cash_received,
        'user_id' => get_current_user_id(),
        'timestamp' => current_time('mysql')
    ];
    
    // Store the actual flow cash amount when received
    if ($flow_cash_received) {
        $submission_data['flow_cash'] = $flow_cash;
    }
    
    $result = $wpdb->insert(
        $submissions_table_name,
        $submission_data
    );
    
    if ($result === false) {
        wp_send_json_error(['message' => 'Failed to save submission']);
        return;
    }
    
    // Return success response
    wp_send_json_success([
        'message' => 'Submission saved successfully',
        'submissionId' => $wpdb->insert_id
    ]);
}
add_action('wp_ajax_gastro_rechner_submit', 'gastro_rechner_submit_data');
add_action('wp_ajax_nopriv_gastro_rechner_submit', 'gastro_rechner_submit_data'); // Allow non-logged in users

// AJAX handler to get settings for the front-end
function gastro_rechner_get_settings() {
    // Check nonce for security
    if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'gastro_rechner_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }
    
    // Return settings
    wp_send_json_success(gastro_get_settings_for_frontend());
}
add_action('wp_ajax_gastro_rechner_get_settings', 'gastro_rechner_get_settings');
add_action('wp_ajax_nopriv_gastro_rechner_get_settings', 'gastro_rechner_get_settings');

// AJAX handler to get all submissions
function gastro_rechner_get_submissions_ajax() {
    // Verify nonce
    if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'gastro_rechner_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'gastro_rechner_submissions';
    
    $submissions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC");
    
    wp_send_json_success($submissions);
}
add_action('wp_ajax_gastro_rechner_get_submissions', 'gastro_rechner_get_submissions_ajax');
add_action('wp_ajax_nopriv_gastro_rechner_get_submissions', 'gastro_rechner_get_submissions_ajax');

// AJAX handler to delete a submission
function gastro_rechner_delete_submission_ajax() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gastro_rechner_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }
    
    // Validate submission ID
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        wp_send_json_error(['message' => 'Invalid submission ID']);
        return;
    }
    
    $submission_id = intval($_POST['id']);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'gastro_rechner_submissions';
    
    $result = $wpdb->delete(
        $table_name,
        ['id' => $submission_id],
        ['%d']
    );
    
    if ($result === false) {
        wp_send_json_error(['message' => 'Failed to delete submission']);
        return;
    }
    
    wp_send_json_success(['message' => 'Submission deleted successfully']);
}
add_action('wp_ajax_gastro_rechner_delete_submission', 'gastro_rechner_delete_submission_ajax');
add_action('wp_ajax_nopriv_gastro_rechner_delete_submission', 'gastro_rechner_delete_submission_ajax');

// AJAX handler to update a submission
function gastro_rechner_update_submission_ajax() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gastro_rechner_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }
    
    // Validate submission ID
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        wp_send_json_error(['message' => 'Invalid submission ID']);
        return;
    }
    
    $submission_id = intval($_POST['id']);
    
    // Validate input data
    $name = sanitize_text_field($_POST['name'] ?? '');
    $total_sales = floatval($_POST['totalSales'] ?? 0);
    $sales_cash = floatval($_POST['salesCash'] ?? 0);
    $team_tip = floatval($_POST['teamTip'] ?? 0);
    $flow_cash_received = isset($_POST['flowCashReceived']) && $_POST['flowCashReceived'] === '1';
    
    // Basic validation
    if (empty($name) || $total_sales <= 0 || $sales_cash < 0) {
        wp_send_json_error(['message' => 'Invalid submission data']);
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'gastro_rechner_submissions';
    
    $result = $wpdb->update(
        $table_name,
        [
            'name' => $name,
            'total_sales' => $total_sales,
            'sales_cash' => $sales_cash,
            'team_tip' => $team_tip,
            'flow_cash_received' => $flow_cash_received,
            'timestamp' => current_time('mysql')
        ],
        ['id' => $submission_id],
        ['%s', '%f', '%f', '%f', '%d', '%s'],
        ['%d']
    );
    
    if ($result === false) {
        wp_send_json_error(['message' => 'Failed to update submission']);
        return;
    }
    
    wp_send_json_success(['message' => 'Submission updated successfully']);
}
add_action('wp_ajax_gastro_rechner_update_submission', 'gastro_rechner_update_submission_ajax');
add_action('wp_ajax_nopriv_gastro_rechner_update_submission', 'gastro_rechner_update_submission_ajax');

// AJAX handler to clear all submissions
function gastro_rechner_clear_submissions_ajax() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gastro_rechner_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'gastro_rechner_submissions';
    
    $result = $wpdb->query("TRUNCATE TABLE $table_name");
    
    if ($result === false) {
        wp_send_json_error(['message' => 'Failed to clear submissions']);
        return;
    }
    
    wp_send_json_success(['message' => 'All submissions cleared successfully']);
}
add_action('wp_ajax_gastro_rechner_clear_submissions', 'gastro_rechner_clear_submissions_ajax');
add_action('wp_ajax_nopriv_gastro_rechner_clear_submissions', 'gastro_rechner_clear_submissions_ajax');