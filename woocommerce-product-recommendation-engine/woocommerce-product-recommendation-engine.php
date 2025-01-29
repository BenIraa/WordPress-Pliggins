<?php
/**
 * Plugin Name: WooCommerce Product Recommendation Engine
 * Description: Recommends products based on customer behavior such as purchase history, viewing history, and popular items.
 * Version: 1.0
 * Author: Ben Iraa
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Display "Frequently Bought Together" on product pages
function recommend_frequently_bought_together( $product_id ) {
    $related_products = wc_get_related_products( $product_id, 3 ); // Fetch 3 related products
    
    if ( ! empty( $related_products ) ) {
        echo '<h2>' . __( 'Frequently Bought Together', 'woocommerce' ) . '</h2><ul class="products">';
        
        foreach ( $related_products as $related_product_id ) {
            $product = wc_get_product( $related_product_id );
            echo '<li><a href="' . get_permalink( $related_product_id ) . '">' . $product->get_image() . '<br>' . $product->get_name() . '</a></li>';
        }
        
        echo '</ul>';
    }
}
add_action( 'woocommerce_after_single_product', function() {
    global $product;
    recommend_frequently_bought_together( $product->get_id() );
}, 20 );

// Display "Customers Who Bought This Also Bought" based on order data
function recommend_customers_also_bought( $product_id ) {
    global $wpdb;
    
    $query = "SELECT DISTINCT order_items.order_item_id, order_items.order_id FROM {$wpdb->prefix}woocommerce_order_items as order_items
              JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_meta ON order_items.order_item_id = order_meta.order_item_id
              WHERE order_meta.meta_key = '_product_id' AND order_meta.meta_value = %d";
    
    $order_ids = $wpdb->get_col( $wpdb->prepare( $query, $product_id ) );
    
    if ( empty( $order_ids ) ) {
        return;
    }
    
    $query = "SELECT DISTINCT order_meta.meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta as order_meta
              JOIN {$wpdb->prefix}woocommerce_order_items as order_items ON order_meta.order_item_id = order_items.order_item_id
              WHERE order_items.order_id IN (" . implode( ',', array_map( 'absint', $order_ids ) ) . ")
              AND order_meta.meta_key = '_product_id' AND order_meta.meta_value != %d";
    
    $recommended_products = $wpdb->get_col( $wpdb->prepare( $query, $product_id ) );
    
    if ( ! empty( $recommended_products ) ) {
        echo '<h2>' . __( 'Customers Who Bought This Also Bought', 'woocommerce' ) . '</h2><ul class="products">';
        
        foreach ( array_unique( $recommended_products ) as $recommended_product_id ) {
            $product = wc_get_product( $recommended_product_id );
            echo '<li><a href="' . get_permalink( $recommended_product_id ) . '">' . $product->get_image() . '<br>' . $product->get_name() . '</a></li>';
        }
        
        echo '</ul>';
    }
}
add_action( 'woocommerce_after_single_product', function() {
    global $product;
    recommend_customers_also_bought( $product->get_id() );
}, 30 );

// Placeholder for AI/ML-based recommendation system (future enhancement)
function recommend_ai_based_products( $user_id ) {
    // Integrate with TensorFlow.js or other ML libraries here in future iterations
}
