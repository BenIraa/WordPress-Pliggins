<?php
/**
 * Plugin Name: WooCommerce Inventory Expiry Tracker
 * Description: Adds expiry tracking for WooCommerce products, hiding expired items and notifying the admin.
 * Version: 1.0
 * Author: Ben Iraa
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add custom field for expiry date
function add_expiry_date_field() {
    woocommerce_wp_text_input(
        array(
            'id'          => '_expiry_date',
            'label'       => __('Expiry Date', 'woocommerce'),
            'placeholder' => 'YYYY-MM-DD',
            'desc_tip'    => true,
            'description' => __('Set an expiry date for this product.', 'woocommerce'),
            'type'        => 'date'
        )
    );
}
add_action('woocommerce_product_options_general_product_data', 'add_expiry_date_field');

// Save expiry date
function save_expiry_date_field($post_id) {
    $expiry_date = isset($_POST['_expiry_date']) ? sanitize_text_field($_POST['_expiry_date']) : '';
    update_post_meta($post_id, '_expiry_date', $expiry_date);
}
add_action('woocommerce_process_product_meta', 'save_expiry_date_field');

// Hide expired products
function hide_expired_products($query) {
    if (is_admin() || ! $query->is_main_query()) {
        return;
    }

    $meta_query = array(
        array(
            'key'     => '_expiry_date',
            'value'   => date('Y-m-d'),
            'compare' => '>=',
            'type'    => 'DATE'
        )
    );

    $query->set('meta_query', $meta_query);
}
add_action('pre_get_posts', 'hide_expired_products');

// Notify admin of expiring products
function notify_admin_expiring_products() {
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => '_expiry_date',
                'value'   => date('Y-m-d', strtotime('+7 days')),
                'compare' => '<=',
                'type'    => 'DATE'
            )
        )
    );

    $query = new WP_Query($args);
    if ($query->have_posts()) {
        $message = "The following products are expiring soon:\n";
        while ($query->have_posts()) {
            $query->the_post();
            $message .= get_the_title() . " (Expiry: " . get_post_meta(get_the_ID(), '_expiry_date', true) . ")\n";
        }
        wp_mail(get_option('admin_email'), 'Expiring Products Notification', $message);
    }
}
add_action('wp', 'notify_admin_expiring_products');
