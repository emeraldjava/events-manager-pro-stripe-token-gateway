<?php
/*
Plugin Name:	 	    Events Manager Pro - Stripe Token Gateway
Plugin URI: 		    https://github.com/emeraldjava/events-manager-pro-stripe-token-gateway
Description: 		    Payment gateway for Stripe, which uses a front-end token.
Version:          	2015.12.24
Author: 			      paul.t.oconnell@gmail.com
Author URI: 		    https://github.com/emeraldjava
Text Domain:        events-manager-pro-stripe-token-gateway
License:          	GPL-2.0+
License URI:      	http://www.gnu.org/licenses/gpl-2.0.txt
Domain Path:        /languages
GitHub Plugin URI:  https://github.com/emeraldjava/events-manager-pro-stripe-token-gateway
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}
/**
 * events manager pro is a pre-requirements
 */
function em_pro_prereq() {
    ?> <div class="error"><p><?php _e('Please ensure you have <a href="http://eventsmanagerpro.com/">Events Manager Pro</a> installed, as this is a requirement for the PayPal Advanced add-on.','events-manager-paypal-advanced'); ?></p>
    </div>
    <?php
}

/**
 * initialise plugin once other plugins are loaded
 */
function em_gateway_stripe_token_register() {
    //check that EM Pro is installed
    if( ! defined( 'EMP_VERSION' ) ) {
        add_action( 'admin_notices', 'em_pro_prereq' );
        return false; //don't load plugin further
    }

    if (class_exists('EM_Gateways')) {
        require_once( plugin_dir_path( __FILE__ ) . 'em-gateway-stripe-token.php' );
        EM_Gateways::register_gateway('em_pro_stripe_token', 'EM_Gateway_Stripe_Token');
    }

}
add_action( 'plugins_loaded', 'em_gateway_stripe_token_register', 1000);
?>
