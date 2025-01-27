<?php
/**
 * Plugin Name: Advanced Shipping Rules
 * Description: Adds advanced shipping rules for WooCommerce based on custom logic like distance and weight.
 * Version: 1.0
 * Author: Your Name
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="error"><p><strong>' . __( 'WooCommerce is not active. Please activate WooCommerce to use Advanced Shipping Rules.', 'advanced-shipping-rules' ) . '</strong></p></div>';
    });
    return;
}

// Hook into WooCommerce's shipping initialization process
function advanced_shipping_rules_init() {
    if ( ! class_exists( 'WC_Shipping_Method' ) ) {
        return; // Abort if WooCommerce classes are not loaded
    }

    class WC_Shipping_Advanced_Rules extends WC_Shipping_Method {
        public function __construct() {
            $this->id                 = 'advanced_shipping_rules';
            $this->method_title       = __( 'Advanced Shipping Rules', 'advanced-shipping-rules' );
            $this->method_description = __( 'Custom shipping rates based on rules like distance and weight.', 'advanced-shipping-rules' );

            $this->enabled            = "yes";
            $this->title              = __( 'Advanced Shipping', 'advanced-shipping-rules' );

            $this->init();
        }

        public function init() {
            $this->init_form_fields();
            $this->init_settings();

            add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'process_admin_options' ] );
        }

        public function init_form_fields() {
            $this->form_fields = [
                'enabled' => [
                    'title'       => __( 'Enable', 'advanced-shipping-rules' ),
                    'type'        => 'checkbox',
                    'description' => __( 'Enable this shipping method', 'advanced-shipping-rules' ),
                    'default'     => 'yes',
                ],
                'base_cost' => [
                    'title'       => __( 'Base Cost', 'advanced-shipping-rules' ),
                    'type'        => 'number',
                    'description' => __( 'Base shipping cost', 'advanced-shipping-rules' ),
                    'default'     => 1000, // Base cost in RWF
                ],
                'extra_cost_per_km' => [
                    'title'       => __( 'Extra Cost per KM', 'advanced-shipping-rules' ),
                    'type'        => 'number',
                    'description' => __( 'Additional cost for each kilometer beyond 5 KM.', 'advanced-shipping-rules' ),
                    'default'     => 200, // Extra cost in RWF
                ],
            ];
        }

        public function calculate_shipping( $package = [] ) {
            // Settings
            $base_cost = $this->get_option( 'base_cost', 1000 ); // Default base cost for 5 km
            $distance_cost -= 0;
            $weight_cost = 0;
            $extra_cost_per_km = $this->get_option( 'extra_cost_per_km', 200 ); // Additional cost per km

            // Hardcoded distance logic
            $distance_km = 10; // Hardcoded distance for testing (10 KM)

            // Calculate additional cost if distance exceeds 5 km
            $additional_cost = 0;
            if ( $distance_km > 5 ) {
                $additional_km = $distance_km - 5;
                $additional_cost = $additional_km * $extra_cost_per_km;
            }

            // Total cost
            $total_cost = $base_cost + $additional_cost;

            // Add the shipping rate
            $this->add_rate([
                'id'    => $this->id,
                'label' => $this->title . " (10 km hardcoded)",
                'cost'  => $total_cost,
            ]);
        }
    }
}
add_action( 'woocommerce_shipping_init', 'advanced_shipping_rules_init' );

// Register the custom shipping method
function add_advanced_shipping_method( $methods ) {
    $methods['advanced_shipping_rules'] = 'WC_Shipping_Advanced_Rules';
    return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'add_advanced_shipping_method' );

// Debugging: Check if the shipping method class is loaded
add_action( 'init', function() {
    if ( class_exists( 'WC_Shipping_Advanced_Rules' ) ) {
        error_log( 'Advanced Shipping Rules class loaded successfully.' );
    } else {
        error_log( 'Advanced Shipping Rules class NOT loaded.' );
    }
});

