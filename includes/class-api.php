<?php

/**
 * The API functionality of the plugin.
 *
 * Handles all AJAX calls between frontend and backend.
 *
 * @since      1.1.0
 * @package    Gastro_Rechner
 */

class Gastro_Rechner_Api {

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
        // Register AJAX handlers
        add_action('wp_ajax_gastro_rechner_submit', array($this, 'submit_data'));
        add_action('wp_ajax_nopriv_gastro_rechner_submit', array($this, 'submit_data'));
        
        add_action('wp_ajax_gastro_rechner_get_settings', array($this, 'get_settings'));
        add_action('wp_ajax_nopriv_gastro_rechner_get_settings', array($this, 'get_settings'));
        
        add_action('wp_ajax_gastro_rechner_get_submissions', array($this, 'get_submissions'));
        add_action('wp_ajax_nopriv_gastro_rechner_get_submissions', array($this, 'get_submissions'));
        
        add_action('wp_ajax_gastro_rechner_delete_submission', array($this, 'delete_submission'));
        add_action('wp_ajax_nopriv_gastro_rechner_delete_submission', array($this, 'delete_submission'));
        
        add_action('wp_ajax_gastro_rechner_update_submission', array($this, 'update_submission'));
        add_action('wp_ajax_nopriv_gastro_rechner_update_submission', array($this, 'update_submission'));
        
        add_action('wp_ajax_gastro_rechner_clear_submissions', array($this, 'clear_submissions'));
        add_action('wp_ajax_nopriv_gastro_rechner_clear_submissions', array($this, 'clear_submissions'));
    }

    /**
     * Common method to verify nonce.
     *
     * @since    1.1.0
     * @param    string    $nonce_name    The nonce name to verify.
     * @return   bool      True if nonce is valid, false otherwise.
     */
    private function verify_nonce($nonce_name = 'nonce') {
        return isset($_REQUEST[$nonce_name]) && wp_verify_nonce($_REQUEST[$nonce_name], 'gastro_rechner_nonce');
    }

    /**
     * Get settings for frontend.
     *
     * @since    1.1.0
     */
    public function get_settings() {
        // Check nonce for security
        if (!$this->verify_nonce()) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        $main = new Gastro_Rechner_Main();
        wp_send_json_success($main->get_settings_for_frontend());
    }

    /**
     * Handle form submissions.
     *
     * @since    1.1.0
     */
    public function submit_data() {
        // Check nonce for security
        if (!$this->verify_nonce()) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check if user has permission to submit
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        $can_submit = false;
        
        if (!empty($user_roles)) {
            $can_submit = in_array('administrator', $user_roles) || in_array('author', $user_roles);
        }
        
        if (!$can_submit) {
            wp_send_json_error(array('message' => 'You do not have permission to submit data please login'));
            return;
        }
        
        // Validate and sanitize input data
        $name = sanitize_text_field($_POST['name'] ?? '');
        $total_sales = floatval($_POST['totalSales'] ?? 0);
        $sales_cash = floatval($_POST['salesCash'] ?? 0);
        
        // Get settings for calculations
        global $wpdb;
        $admin_table_name = $wpdb->prefix . $this->admin_table;
        $settings = $wpdb->get_row("SELECT * FROM $admin_table_name LIMIT 1");
        $tip_factor = isset($settings->tip_factor) ? floatval($settings->tip_factor) : 2.00;
        $flow_cash = isset($settings->flow_cash) ? floatval($settings->flow_cash) : 100.00;
        
        // Calculate team tip using the formula
        $team_tip = $total_sales * ($tip_factor / 100);
        
        // Process flow cash received status
        $flow_cash_received = isset($_POST['flowCashReceived']) ? (bool)$_POST['flowCashReceived'] : false;
        
        // Basic validation
        if (empty($name) || $total_sales <= 0) {
            wp_send_json_error(array('message' => 'Invalid submission data. Name is required and Total Sales must be positive.'));
            return;
        }
        
        // Insert submission into database
        $submissions_table_name = $wpdb->prefix . $this->submissions_table;
        
        $submission_data = array(
            'name' => $name,
            'total_sales' => $total_sales,
            'sales_cash' => $sales_cash,
            'team_tip' => $team_tip,
            'flow_cash_received' => $flow_cash_received,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        );
        
        // Store the actual flow cash amount when received
        if ($flow_cash_received) {
            $submission_data['flow_cash'] = $flow_cash;
        }
        
        $result = $wpdb->insert($submissions_table_name, $submission_data);
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to save submission'));
            return;
        }
        
        // Return success response
        wp_send_json_success(array(
            'message' => 'Submission saved successfully',
            'submissionId' => $wpdb->insert_id
        ));
    }

    /**
     * Get all submissions.
     *
     * @since    1.1.0
     */
    public function get_submissions() {
        // Verify nonce
        if (!$this->verify_nonce()) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . $this->submissions_table;
        
        $submissions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC");
        
        wp_send_json_success($submissions);
    }

    /**
     * Delete a submission.
     *
     * @since    1.1.0
     */
    public function delete_submission() {
        // Verify nonce
        if (!$this->verify_nonce()) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Validate submission ID
        if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            wp_send_json_error(array('message' => 'Invalid submission ID'));
            return;
        }
        
        $submission_id = intval($_POST['id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . $this->submissions_table;
        
        // Get the submission to check ownership
        $submission = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $submission_id));
        
        if (!$submission) {
            wp_send_json_error(array('message' => 'Submission not found'));
            return;
        }
        
        // Check if user has permission to delete this submission
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        $is_admin = !empty($user_roles) && in_array('administrator', $user_roles);
        $is_owner = $submission->user_id == get_current_user_id();
        
        if (!$is_admin && !$is_owner) {
            wp_send_json_error(array('message' => 'You do not have permission to delete this submission'));
            return;
        }
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $submission_id),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to delete submission'));
            return;
        }
        
        wp_send_json_success(array('message' => 'Submission deleted successfully'));
    }

    /**
     * Update a submission.
     *
     * @since    1.1.0
     */
    public function update_submission() {
        // Verify nonce
        if (!$this->verify_nonce()) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Validate submission ID
        if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            wp_send_json_error(array('message' => 'Invalid submission ID'));
            return;
        }
        
        $submission_id = intval($_POST['id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . $this->submissions_table;
        
        // Get the submission to check ownership
        $submission = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $submission_id));
        
        if (!$submission) {
            wp_send_json_error(array('message' => 'Submission not found'));
            return;
        }
        
        // Check if user has permission to update this submission
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        $is_admin = !empty($user_roles) && in_array('administrator', $user_roles);
        $is_owner = $submission->user_id == get_current_user_id();
        
        if (!$is_admin && !$is_owner) {
            wp_send_json_error(array('message' => 'You do not have permission to update this submission'));
            return;
        }
        
        // Validate input data
        $name = sanitize_text_field($_POST['name'] ?? '');
        $total_sales = floatval($_POST['totalSales'] ?? 0);
        $sales_cash = floatval($_POST['salesCash'] ?? 0);
        $team_tip = floatval($_POST['teamTip'] ?? 0);
        $flow_cash_received = isset($_POST['flowCashReceived']) && $_POST['flowCashReceived'] === '1';
        
        // Basic validation
        if (empty($name) || $total_sales <= 0 || $sales_cash < 0) {
            wp_send_json_error(array('message' => 'Invalid submission data'));
            return;
        }
        
        $result = $wpdb->update(
            $table_name,
            array(
                'name' => $name,
                'total_sales' => $total_sales,
                'sales_cash' => $sales_cash,
                'team_tip' => $team_tip,
                'flow_cash_received' => $flow_cash_received,
                'timestamp' => current_time('mysql')
            ),
            array('id' => $submission_id),
            array('%s', '%f', '%f', '%f', '%d', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to update submission'));
            return;
        }
        
        wp_send_json_success(array('message' => 'Submission updated successfully'));
    }

    /**
     * Clear all submissions.
     *
     * @since    1.1.0
     */
    public function clear_submissions() {
        // Verify nonce
        if (!$this->verify_nonce()) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check if user is administrator
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        $is_admin = !empty($user_roles) && in_array('administrator', $user_roles);
        
        if (!$is_admin) {
            wp_send_json_error(array('message' => 'You do not have permission to clear all submissions'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . $this->submissions_table;
        
        $result = $wpdb->query("TRUNCATE TABLE $table_name");
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to clear submissions'));
            return;
        }
        
        wp_send_json_success(array('message' => 'All submissions cleared successfully'));
    }
}