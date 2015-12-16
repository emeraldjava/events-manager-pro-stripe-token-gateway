<?php
/*
Plugin Name:	 	Events Manager Pro - Stripe Token Gateway
Plugin URI: 		https://github.com/emeraldjava/bhaawp
Description: 		Events Manager Pro - Stripe Token Gateway
Version:          	2015.06.19
Author: 			paul.t.oconnell@gmail.com
Author URI: 		https://github.com/emeraldjava
Text Domain:      	bhaawp
License:          	GPL-2.0+
License URI:      	http://www.gnu.org/licenses/gpl-2.0.txt
Domain Path:      	/languages
GitHub Plugin URI: https://github.com/emeraldjava/bhaawp
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
 * Set meta links in the plugins page
 */
//function emp_stripe_metalinks( $actions, $file, $plugin_data ) {
//    $new_actions = array();
//    $new_actions[] = sprintf( '<a href="'.EM_ADMIN_URL.'&amp;page=events-manager-gateways&amp;action=edit&amp;gateway=emp_stripe">%s</a>', __('Settings', 'dbem') );
//    $new_actions = array_merge( $new_actions, $actions );
//    return $new_actions;
//}
//add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'emp_stripe_metalinks', 10, 3 );


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