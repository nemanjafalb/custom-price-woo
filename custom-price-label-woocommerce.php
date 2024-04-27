<?php
/*
Plugin Name: Custom Price Label for Product
Description: Allows users to customize the price label for products. Created by PixelBloom
Version: 1.0
Author: Nemanja Falb - PixelBloom
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

*/

// Add custom fields for price label options
function custom_price_label_fields() {
    woocommerce_wp_select(
        array(
            'id' => '_custom_price_label_option',
            'label' => 'Price Label Option',
            'options' => array(
                'default' => 'Use Default Label',
                'custom' => 'Custom Label'
            )
        )
    );

    woocommerce_wp_text_input(
        array(
            'id' => '_custom_price_label_text',
            'label' => 'Custom Label',
            'desc_tip' => 'true',
            'description' => 'Enter custom price label here.'
        )
    );

    woocommerce_wp_text_input(
        array(
            'id' => '_custom_price_currency',
            'label' => 'Currency Symbol',
            'desc_tip' => 'true',
            'description' => 'Enter currency symbol to display.'
        )
    );
}
add_action('woocommerce_product_options_general_product_data', 'custom_price_label_fields');

// Save custom field data
function save_custom_price_label_fields($product_id) {
    $custom_label_option = isset($_POST['_custom_price_label_option']) ? sanitize_text_field($_POST['_custom_price_label_option']) : '';
    $custom_label_text = isset($_POST['_custom_price_label_text']) ? sanitize_text_field($_POST['_custom_price_label_text']) : '';
    $custom_price_currency = isset($_POST['_custom_price_currency']) ? sanitize_text_field($_POST['_custom_price_currency']) : '';

    update_post_meta($product_id, '_custom_price_label_option', $custom_label_option);
    update_post_meta($product_id, '_custom_price_label_text', $custom_label_text);
    update_post_meta($product_id, '_custom_price_currency', $custom_price_currency);
}
add_action('woocommerce_process_product_meta', 'save_custom_price_label_fields');

// Add custom price label after regular price on product page
function add_custom_price_label_after_price() {
    global $product;

    $custom_label_option = get_post_meta($product->get_id(), '_custom_price_label_option', true);
    $custom_label_text = get_post_meta($product->get_id(), '_custom_price_label_text', true);
    $custom_price_currency = get_post_meta($product->get_id(), '_custom_price_currency', true);

    if ($custom_label_option === 'custom' && !empty($custom_label_text) && !empty($custom_price_currency)) {
        echo '<div class="woocommerce-custom-price-label">' . esc_html($custom_price_currency) . ' ' . esc_html($custom_label_text) . '</div>';
    }
}
add_action('woocommerce_after_shop_loop_item_title', 'add_custom_price_label_after_price', 15);

// Add settings page
function custom_price_label_settings_page() {
    ?>
    <div class="wrap">
        <h1>Custom Price Label Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('custom_price_label_settings_group'); ?>
            <?php do_settings_sections('custom_price_label_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Price Label Option</th>
                    <td>
                        <select name="_custom_price_label_option">
                            <option value="default" <?php selected(get_option('_custom_price_label_option'), 'default'); ?>>Use Default Label</option>
                            <option value="custom" <?php selected(get_option('_custom_price_label_option'), 'custom'); ?>>Custom Label</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Custom Label</th>
                    <td><input type="text" name="_custom_price_label_text" value="<?php echo esc_attr(get_option('_custom_price_label_text')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Currency Symbol</th>
                    <td><input type="text" name="_custom_price_currency" value="<?php echo esc_attr(get_option('_custom_price_currency')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register settings
function custom_price_label_register_settings() {
    register_setting('custom_price_label_settings_group', '_custom_price_label_option');
    register_setting('custom_price_label_settings_group', '_custom_price_label_text');
    register_setting('custom_price_label_settings_group', '_custom_price_currency');
}
add_action('admin_init', 'custom_price_label_register_settings');

// Add plugin settings page
function custom_price_label_add_settings_page() {
    add_submenu_page(
        'options-general.php', // parent menu slug
        'Custom Price Label Settings', // page title
        'Price Label', // menu title
        'manage_options', // capability
        'custom-price-label-settings', // menu slug
        'custom_price_label_settings_page' // function
    );
}
add_action('admin_menu', 'custom_price_label_add_settings_page');

// Display notice upon plugin activation
function custom_price_label_activation_notice() {
    // Check if the current user has the 'activate_plugins' capability
    if (!current_user_can('activate_plugins')) {
        return;
    }

    // Check if the plugin has just been activated
    if (!get_transient('custom_price_label_activation_notice')) {
        return;
    }

    ?>
    <div class="notice notice-info is-dismissible">
        <p><?php _e('If you want to customize the currency symbol, click <a href="' . admin_url('admin.php?page=custom-price-label-settings') . '">here</a> to modify the settings.', 'custom-price-label'); ?></p>
    </div>
    <?php
    // Delete the transient to prevent the notice from being displayed again
    delete_transient('custom_price_label_activation_notice');
}
add_action('admin_notices', 'custom_price_label_activation_notice');

// Create transient upon plugin activation
function custom_price_label_activation_notice_transient() {
    set_transient('custom_price_label_activation_notice', true, 5);
}
register_activation_hook(__FILE__, 'custom_price_label_activation_notice_transient');

// Clear WooCommerce transients to refresh currency data
function custom_price_label_clear_woocommerce_transients() {
    if (function_exists('wc_delete_product_transients')) {
        wc_delete_product_transients();
    }
}
add_action('admin_init', 'custom_price_label_clear_woocommerce_transients');

// Change currency symbol
function custom_price_currency_symbol($currency_symbol, $currency) {
    // Get the custom currency symbol from the plugin settings
    $custom_currency = get_option('_custom_price_currency');

    // If a custom currency symbol is set, use it
    if (!empty($custom_currency)) {
        return $custom_currency;
    }

    // Otherwise, return the default currency symbol
    return $currency_symbol;
}
add_filter('woocommerce_currency_symbol', 'custom_price_currency_symbol', 10, 2);
