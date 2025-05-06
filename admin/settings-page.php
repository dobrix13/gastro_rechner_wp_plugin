<div class="wrap">
    <h1>Gastro Rechner Einstellungen</h1>
    <h4>Hier können Sie die Einstellungen für den Gastro-Rechner anpassen.</h4>
    
    <!-- Instructions on how to add to front page -->
    <div class="notice notice-info">
        <h3>So fügen Sie den Gastro-Rechner zu Ihrer Webseite hinzu:</h3>
        <ol>
            <li>Fügen Sie den Shortcode <code>[gastro_rechner]</code> in einen Beitrag oder eine Seite ein.</li>
            <li>Für angepasste Darstellung können Sie Attribute hinzufügen, z.B.: <code>[gastro_rechner title="Mein Rechner" theme="dark-cold"]</code></li>
            <li>Verfügbare Attribute: <code>title</code> (Titel), <code>show_name</code> (true/false), <code>theme</code> (light-cold/light-warm/dark-cold/dark-warm)</li>
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
            <tr>
                <th><label for="default_color_scheme">Default Color Scheme</label></th>
                <td>
                    <select name="default_color_scheme" id="default_color_scheme">
                        <option value="light-cold" <?php selected(isset($settings->default_color_scheme) ? $settings->default_color_scheme : 'light-cold', 'light-cold'); ?>>Light Cold</option>
                        <option value="light-warm" <?php selected(isset($settings->default_color_scheme) ? $settings->default_color_scheme : 'light-cold', 'light-warm'); ?>>Light Warm</option>
                        <option value="dark-cold" <?php selected(isset($settings->default_color_scheme) ? $settings->default_color_scheme : 'light-cold', 'dark-cold'); ?>>Dark Cold</option>
                        <option value="dark-warm" <?php selected(isset($settings->default_color_scheme) ? $settings->default_color_scheme : 'light-cold', 'dark-warm'); ?>>Dark Warm</option>
                    </select>
                </td>
            </tr>
        </table>

        <?php submit_button('Save'); ?>
    </form>
</div>