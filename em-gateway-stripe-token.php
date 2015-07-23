<?php
/**
 * Created by IntelliJ IDEA.
 * User: pauloconnell
 * Date: 22/07/15
 * Time: 13:34
 */
class EM_Gateway_Stripe_Token extends EM_Gateway {

    var $gateway = 'em_pro_stripe_token';
    var $title = 'Stripe Token';
    var $status = 4;
    var $status_txt = 'Processing (Stripe)';
    var $button_enabled = false; //we can's use a button here
    var $supports_multiple_bookings = true;
    var $registered_timer = 0;

    function __construct() {
        parent::__construct();
        error_log('EM_Gateway_Stripe_Token');

        if ($this->is_active()) {
            //Force SSL for booking submissions, since we have card info
            if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')
                || (get_option('em_' . $this->gateway . '_mode') == 'live')
            ) {
                /* modify booking script, force SSL for all */
                add_filter('em_wp_localize_script', array($this, 'em_wp_localize_script'), 10, 1);
                add_filter('em_booking_form_action_url', array($this, 'force_ssl'), 10, 1);
            }
        }
    }

    /**
     * Outputs extra custom content e.g. information about this gateway or extra form fields to be requested if
     * this gateway is selected (not applicable with Quick Pay Buttons).
     */
    public function booking_form() {
        echo get_option('em_' . $this->gateway . '_form');
        include ( plugin_dir_path( __FILE__ ) . 'views/booking_form.php' );
    }

    /**
     * Called by $this->settings(), override this to output your own gateway options on this gateway settings page
     */
    public function mysettings() {
        global $EM_options;
        include ( plugin_dir_path( __FILE__ ) . 'views/settings.php' );
    }

    /**
     * Save the plugin properties
     * @return bool
     */
    function update() {
        parent::update();
        $gateway_options = array(
            $this->gateway . "_mode" => $_REQUEST[ '_mode' ],
            $this->gateway . "_test_publishable_key" => $_REQUEST[ '_test_publishable_key' ],
            $this->gateway . "_test_secret_key" => $_REQUEST[ '_test_secret_key' ],
            $this->gateway . "_live_publishable_key" => $_REQUEST[ '_live_publishable_key' ],
            $this->gateway . "_live_secret_key" => ($_REQUEST[ '_live_secret_key' ])
            //$this->gateway . "_email_customer" => ($_REQUEST[ '_email_customer' ]),
            //$this->gateway . "_header_email_receipt" => $_REQUEST[ '_header_email_receipt' ],
            //$this->gateway . "_footer_email_receipt" => $_REQUEST[ '_footer_email_receipt' ],
            //$this->gateway . "_manual_approval" => $_REQUEST[ '_manual_approval' ],
            //$this->gateway . "_booking_feedback" => wp_kses_data($_REQUEST[ '_booking_feedback' ]),
            //$this->gateway . "_booking_feedback_free" => wp_kses_data($_REQUEST[ '_booking_feedback_free' ]),
            //$this->gateway . "_debug" => $_REQUEST['_debug' ]
        );
        foreach($gateway_options as $key=>$option){
            update_option('em_'.$key, stripslashes($option));
        }
        //default action is to return true
        return true;
    }

    /**
     * Enable https
     * @param $url
     * @return mixed
     */
    public function force_ssl($url) {
        return str_replace('http://', 'https://', $url);
    }
}
?>