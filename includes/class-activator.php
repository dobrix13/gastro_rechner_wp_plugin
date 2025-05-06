<?php

/**
 * Fired during plugin activation and deactivation.
 *
 * This class handles database table creation and cleanup.
 *
 * @since      1.1.0
 * @package    Gastro_Rechner
 */

class Gastro_Rechner_Activator {

    /**
     * Admin table name without prefix.
     *
     * @since    1.1.0
     * @access   private
     * @var      string    $admin_table    Admin table name without prefix.
     */
    private static $admin_table = 'gastro_rechner_admin';

    /**
     * Submissions table name without prefix.
     *
     * @since    1.1.0
     * @access   private
     * @var      string    $submissions_table    Submissions table name without prefix.
     */
    private static $submissions_table = 'gastro_rechner_submissions';

    /**
     * Activate the plugin.
     *
     * Create database tables and set default values.
     *
     * @since    1.1.0
     */
    public static function activate() {
        global $wpdb;
        
        $admin_table_name = $wpdb->prefix . self::$admin_table;
        $submissions_table_name = $wpdb->prefix . self::$submissions_table;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create admin settings table with improved structure
        $sql1 = "CREATE TABLE IF NOT EXISTS $admin_table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            flow_cash decimal(10,2) NOT NULL DEFAULT 100.00,
            tip_factor decimal(5,2) NOT NULL DEFAULT 2.00,
            flow_cash_toggle BOOLEAN NOT NULL DEFAULT TRUE,
            default_color_scheme varchar(20) NOT NULL DEFAULT 'light-cold',
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

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);

        // Only insert default values if table is empty
        $exists = $wpdb->get_var("SELECT COUNT(*) FROM $admin_table_name");
        if ($exists == 0) {
            $wpdb->insert($admin_table_name, array(
                'flow_cash' => 100.00,
                'tip_factor' => 2.0,
                'flow_cash_toggle' => true,
                'default_color_scheme' => 'light-cold',
                'timestamp' => current_time('mysql'),
            ));
        }
    }

    /**
     * Deactivate the plugin.
     *
     * Clean up database tables.
     *
     * @since    1.1.0
     */
    public static function deactivate() {
        global $wpdb;
        
        $admin_table_name = $wpdb->prefix . self::$admin_table;
        $submissions_table_name = $wpdb->prefix . self::$submissions_table;
        
        // Drop tables
        $sql1 = "DROP TABLE IF EXISTS $admin_table_name;";
        $sql2 = "DROP TABLE IF EXISTS $submissions_table_name;";
        
        $wpdb->query($sql1);
        $wpdb->query($sql2);
    }
}