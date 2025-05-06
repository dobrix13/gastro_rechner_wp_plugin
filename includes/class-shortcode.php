<?php

/**
 * Handles shortcode functionality of the plugin.
 *
 * @since      1.1.0
 * @package    Gastro_Rechner
 */

class Gastro_Rechner_Shortcode {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.1.0
     */
    public function __construct() {
        // Register shortcode
        add_shortcode('gastro_rechner', array($this, 'render_shortcode'));
    }

    /**
     * Render the shortcode.
     *
     * @since    1.1.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string   HTML output.
     */
    public function render_shortcode($atts) {
        global $wpdb;
        $admin_table = $wpdb->prefix . 'gastro_rechner_admin';
        
        // Get default color scheme from database
        $settings = $wpdb->get_row("SELECT * FROM $admin_table LIMIT 1");
        $default_theme = isset($settings->default_color_scheme) ? $settings->default_color_scheme : 'light-cold';
        
        // Extract shortcode attributes
        $atts = shortcode_atts(array(
            'title' => 'Gastro-Rechner',
            'show_name' => 'true',
            'theme' => $default_theme
        ), $atts, 'gastro_rechner');
        
        // Backward compatibility for old theme values
        if ($atts['theme'] === 'light') {
            $atts['theme'] = 'light-cold';
        } elseif ($atts['theme'] === 'dark') {
            $atts['theme'] = 'dark-cold';
        }
        
        // Validate theme attribute
        $valid_themes = array('light-cold', 'light-warm', 'dark-cold', 'dark-warm');
        if (!in_array($atts['theme'], $valid_themes)) {
            $atts['theme'] = $default_theme;
        }
        
        // Convert attributes to data attributes for React
        $data_atts = '';
        foreach ($atts as $key => $value) {
            $data_atts .= ' data-' . $key . '="' . esc_attr($value) . '"';
        }
        
        // Return the container with data attributes
        return '<div id="gastro-rechner-root"' . $data_atts . '></div>';
    }
}