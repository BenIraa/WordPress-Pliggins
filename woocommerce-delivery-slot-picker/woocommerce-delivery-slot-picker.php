<?php
/**
 * Plugin Name: WooCommerce Delivery Slot Picker
 * Plugin URI: 
 * Description: Allows customers to select a delivery slot during checkout and prevents overbooking.
 * Version: 1.0
 * Author: Ben Iraa
 * Author URI: 
 * License: GPL2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add a delivery slot field to checkout
function wc_delivery_slot_picker_checkout_field( $checkout ) {
    echo '<div id="wc_delivery_slot_picker"><h3>' . __('Choose Your Delivery Slot') . '</h3>';
    
    $available_slots = get_option( 'wc_available_delivery_slots', array(
        'Morning 9 AM - 12 PM',
        'Afternoon 1 PM - 4 PM',
        'Evening 6 PM - 9 PM'
    ));
    
    woocommerce_form_field( 'delivery_slot', array(
        'type'    => 'select',
        'class'   => array('form-row-wide'),
        'label'   => __('Preferred Delivery Slot'),
        'options' => array_combine($available_slots, $available_slots),
        'required' => true,
    ), $checkout->get_value( 'delivery_slot' ));
    
    echo '</div>';
}
add_action( 'woocommerce_after_order_notes', 'wc_delivery_slot_picker_checkout_field' );

// Save selected delivery slot to order meta
function wc_delivery_slot_picker_save_field( $order_id ) {
    if ( ! empty( $_POST['delivery_slot'] ) ) {
        update_post_meta( $order_id, '_delivery_slot', sanitize_text_field( $_POST['delivery_slot'] ) );
    }
}
add_action( 'woocommerce_checkout_update_order_meta', 'wc_delivery_slot_picker_save_field' );

// Display delivery slot in order details (Admin View)
function wc_delivery_slot_picker_display_admin_order_meta( $order ) {
    $delivery_slot = get_post_meta( $order->get_id(), '_delivery_slot', true );
    if ( ! empty( $delivery_slot ) ) {
        echo '<p><strong>' . __('Delivery Slot:') . '</strong> ' . esc_html( $delivery_slot ) . '</p>';
    }
}
add_action( 'woocommerce_admin_order_data_after_billing_address', 'wc_delivery_slot_picker_display_admin_order_meta', 10, 1 );

// Prevent overbooking by limiting slots
function wc_delivery_slot_picker_limit_slots( $fields ) {
    $orders = wc_get_orders( array( 'status' => array('processing', 'completed', 'on-hold') ) );
    $slot_counts = array();
    
    foreach ( $orders as $order ) {
        $slot = get_post_meta( $order->get_id(), '_delivery_slot', true );
        if ( ! empty( $slot ) ) {
            if ( ! isset( $slot_counts[$slot] ) ) {
                $slot_counts[$slot] = 0;
            }
            $slot_counts[$slot]++;
        }
    }
    
    $max_orders_per_slot = get_option( 'wc_max_orders_per_slot', 5 );
    foreach ( $fields['delivery_slot']['options'] as $slot => $label ) {
        if ( isset( $slot_counts[$slot] ) && $slot_counts[$slot] >= $max_orders_per_slot ) {
            unset( $fields['delivery_slot']['options'][$slot] );
        }
    }
    
    return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'wc_delivery_slot_picker_limit_slots' );
