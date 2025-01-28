<?php
/**
 * Plugin Name: WooCommerce Inventory Expiry Tracker
 * Description: Tracks product expiry dates, notifies admins of soon-to-expire products, and moves expiring products to a promotion page with a discount.
 * Version: 1.2
 * Author: Ben Iraa
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add expiry date field to products
function expiry_tracker_add_custom_field() {
    woocommerce_wp_text_input( array(
        'id' => '_expiry_date',
        'label' => __( 'Expiry Date', 'woocommerce' ),
        'type' => 'date',
        'description' => __( 'Set the expiry date for this product.', 'woocommerce' ),
        'desc_tip' => true,
    ) );
}
add_action( 'woocommerce_product_options_general_product_data', 'expiry_tracker_add_custom_field' );

// Save expiry date field
function expiry_tracker_save_custom_field( $post_id ) {
    $expiry_date = isset( $_POST['_expiry_date'] ) ? sanitize_text_field( $_POST['_expiry_date'] ) : '';
    update_post_meta( $post_id, '_expiry_date', $expiry_date );
}
add_action( 'woocommerce_process_product_meta', 'expiry_tracker_save_custom_field' );

// Move products expiring in 3 days to the Promotion category and apply 50% discount
function expiry_tracker_check_expiring_products() {
    $today = date( 'Y-m-d' );
    $three_days_later = date( 'Y-m-d', strtotime( '+3 days' ) );
    
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => '_expiry_date',
                'value'   => $three_days_later,
                'compare' => '<=',
                'type'    => 'DATE'
            )
        )
    );
    
    $query = new WP_Query( $args );
    
    while ( $query->have_posts() ) {
        $query->the_post();
        $product_id = get_the_ID();
        
        // Apply a 50% discount
        $regular_price = get_post_meta( $product_id, '_regular_price', true );
        update_post_meta( $product_id, '_sale_price', $regular_price * 0.5 );
        update_post_meta( $product_id, '_price', $regular_price * 0.5 );
        
        // Assign product to the Promotion category
        wp_set_object_terms( $product_id, 'promotion-products', 'product_cat', true );
    }
    
    wp_reset_postdata();
}
add_action( 'woocommerce_init', 'expiry_tracker_check_expiring_products' );

// Hide expired products from the shop
function expiry_tracker_hide_expired_products( $query ) {
    if ( is_admin() || ! $query->is_main_query() || ! $query->is_shop() ) {
        return;
    }
    
    $today = date( 'Y-m-d' );
    $meta_query = array(
        array(
            'key'     => '_expiry_date',
            'value'   => $today,
            'compare' => '>=',
            'type'    => 'DATE'
        )
    );
    
    $query->set( 'meta_query', $meta_query );
}
add_action( 'pre_get_posts', 'expiry_tracker_hide_expired_products' );

// Notify admin about expiring products
function expiry_tracker_admin_notice() {
    $today = date( 'Y-m-d' );
    $three_days_later = date( 'Y-m-d', strtotime( '+3 days' ) );
    
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => '_expiry_date',
                'value'   => array( $today, $three_days_later ),
                'compare' => 'BETWEEN',
                'type'    => 'DATE'
            )
        )
    );
    
    $query = new WP_Query( $args );
    
    if ( $query->have_posts() ) {
        echo '<div class="notice notice-warning"><p><strong>' . __( 'Some products are expiring soon! Check the Promotion Products category.', 'woocommerce' ) . '</strong></p></div>';
    }
}
add_action( 'admin_notices', 'expiry_tracker_admin_notice' );

// Change "Sale" badge to "Promotion" for expiring products
function expiry_tracker_custom_sale_badge( $html, $post, $product ) {
    if ( has_term( 'promotion-products', 'product_cat', $post->ID ) ) {
        $html = '<span class="onsale">Promotion</span>';
    }
    return $html;
}
add_filter( 'woocommerce_sale_flash', 'expiry_tracker_custom_sale_badge', 10, 3 );