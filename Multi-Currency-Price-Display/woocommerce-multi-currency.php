<?php
/**
 * Plugin Name: WooCommerce Multi-Currency
 * Description: Adds multi-currency support for WooCommerce, allowing customers to select their preferred currency.
 * Version: 1.0
 * Author: BenIraa
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="error"><p><strong>' . __( 'WooCommerce is not active. Please activate WooCommerce to use the Multi-Currency plugin.', 'woocommerce-multi-currency' ) . '</strong></p></div>';
    });
    return;
}

// Add a settings page for the Multi-Currency plugin
function multi_currency_add_settings_page() {
    add_submenu_page(
        'woocommerce',
        __( 'Multi-Currency Settings', 'woocommerce-multi-currency' ),
        __( 'Multi-Currency', 'woocommerce-multi-currency' ),
        'manage_options',
        'multi-currency-settings',
        'multi_currency_render_settings_page'
    );
}
add_action( 'admin_menu', 'multi_currency_add_settings_page' );

// Render the settings page
function multi_currency_render_settings_page() {
    if ( isset( $_POST['multi_currency_save'] ) ) {
        update_option( 'multi_currency_currencies', sanitize_text_field( $_POST['multi_currency_currencies'] ) );
        update_option( 'multi_currency_exchange_rates', sanitize_text_field( $_POST['multi_currency_exchange_rates'] ) );
        echo '<div class="updated"><p>' . __( 'Settings saved.', 'woocommerce-multi-currency' ) . '</p></div>';
    }

    $currencies = get_option( 'multi_currency_currencies', 'USD,EUR,RWF' );
    $exchange_rates = get_option( 'multi_currency_exchange_rates', 'USD:1,EUR:0.85,RWF:1000' );

    echo '<div class="wrap">
        <h1>' . __( 'Multi-Currency Settings', 'woocommerce-multi-currency' ) . '</h1>
        <form method="POST">
            <table class="form-table">
                <tr>
                    <th scope="row">' . __( 'Supported Currencies', 'woocommerce-multi-currency' ) . '</th>
                    <td>
                        <input type="text" name="multi_currency_currencies" value="' . esc_attr( $currencies ) . '" class="regular-text">
                        <p class="description">' . __( 'Comma-separated list of currency codes (e.g., USD, EUR, RWF).', 'woocommerce-multi-currency' ) . '</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">' . __( 'Exchange Rates', 'woocommerce-multi-currency' ) . '</th>
                    <td>
                        <input type="text" name="multi_currency_exchange_rates" value="' . esc_attr( $exchange_rates ) . '" class="regular-text">
                        <p class="description">' . __( 'Exchange rates in the format: currency:rate (e.g., USD:1, EUR:0.85, RWF:1000).', 'woocommerce-multi-currency' ) . '</p>
                    </td>
                </tr>
            </table>
            <p><input type="submit" name="multi_currency_save" class="button-primary" value="' . __( 'Save Settings', 'woocommerce-multi-currency' ) . '"></p>
        </form>
    </div>';
}

// Add currency switcher to the frontend
function multi_currency_add_switcher() {
    $currencies = explode( ',', get_option( 'multi_currency_currencies', 'USD,EUR,RWF' ) );
    $current_currency = isset( $_COOKIE['multi_currency'] ) ? $_COOKIE['multi_currency'] : 'USD';

    echo '<form method="POST" id="multi-currency-switcher">
        <select name="currency" onchange="this.form.submit()">';

    foreach ( $currencies as $currency ) {
        $currency = trim( $currency );
        echo '<option value="' . esc_attr( $currency ) . '" ' . selected( $current_currency, $currency, false ) . '>' . esc_html( $currency ) . '</option>';
    }

    echo '</select>
    </form>';
}
add_action( 'woocommerce_before_shop_loop', 'multi_currency_add_switcher' );

// Handle currency selection and set a cookie
function multi_currency_handle_switch() {
    if ( isset( $_POST['currency'] ) ) {
        setcookie( 'multi_currency', sanitize_text_field( $_POST['currency'] ), time() + 3600, '/' );
        $_COOKIE['multi_currency'] = sanitize_text_field( $_POST['currency'] );
    }
}
add_action( 'init', 'multi_currency_handle_switch' );

// Convert prices to the selected currency
function multi_currency_convert_price( $price, $product ) {
    $current_currency = isset( $_COOKIE['multi_currency'] ) ? $_COOKIE['multi_currency'] : 'USD';
    $exchange_rates = get_option( 'multi_currency_exchange_rates', 'USD:1,EUR:0.85,RWF:1000' );
    $rates = [];

    foreach ( explode( ',', $exchange_rates ) as $rate ) {
        list( $currency, $value ) = explode( ':', $rate );
        $rates[ trim( $currency ) ] = floatval( $value );
    }

    if ( isset( $rates[ $current_currency ] ) ) {
        $price = $price * $rates[ $current_currency ];
    }

    return $price;
}
add_filter( 'woocommerce_product_get_price', 'multi_currency_convert_price', 10, 2 );
add_filter( 'woocommerce_product_get_regular_price', 'multi_currency_convert_price', 10, 2 );
add_filter( 'woocommerce_product_get_sale_price', 'multi_currency_convert_price', 10, 2 );
