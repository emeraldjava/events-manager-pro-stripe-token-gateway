<?php
/**
 * Created by IntelliJ IDEA.
 * User: pauloconnell
 * Date: 22/07/15
 * Time: 13:34
 */
require_once('vendor/autoload.php');

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

            add_action('wp_head',array($this,'em_pro_stripe_token_set_publishable_key'));
            add_action('wp_enqueue_scripts',array($this,'em_pro_stripe_token_enqueue_scripts'));

            //add_action('em_gateway_js', array(&$this,'em_gateway_js'));
        }
    }

    public function booking_add($EM_Event,$EM_Booking, $post_validation = false){
        $this->registered_timer = current_time('timestamp', 1);
        parent::booking_add($EM_Event, $EM_Booking, $post_validation);

        if ( $post_validation && empty($EM_Booking->booking_id) ) {
            if ( get_option('dbem_multiple_bookings') && get_class($EM_Booking) == 'EM_Multiple_Booking' ) {
                add_filter( 'em_multiple_booking_save', array( &$this, 'em_booking_save' ), 2, 2 );
            } else {
                add_filter( 'em_booking_save', array( &$this, 'em_booking_save' ), 2, 2 );
            }
        }
    }

    /**
     * @param $result
     * @param $EM_Booking
     * @return bool
     */
    public function em_booking_save($result, $EM_Booking) {
        global $wpdb, $wp_rewrite, $EM_Notices;

        error_log('em_booking_save('.$result.')');
        //make sure booking save was successful before we try anything
        if ($result) {
            if ($EM_Booking->get_price() > 0) {
                //handle results
                $capture = $this->processStripePayment( $EM_Booking );
                if ($capture) {
                    //Set booking status, but no emails sent
                    if (!get_option('em_' . $this->gateway . '_manual_approval', false) || !get_option('dbem_bookings_approval')) {
                        $EM_Booking->set_status(1, false); //Approve
                    } else {
                        $EM_Booking->set_status(0, false); //Set back to normal "pending"
                    }
                } else {
                    //not good.... error inserted into booking in capture function. Delete this booking from db
                    if (!is_user_logged_in() && get_option('dbem_bookings_anonymous') && !get_option('dbem_bookings_registration_disable') && !empty($EM_Booking->person_id)) {
                        //delete the user we just created, only if created after em_booking_add filter is called (which is when a new user for this booking would be created)
                        $EM_Person = $EM_Booking->get_person();
                        if( strtotime($EM_Person->data->user_registered) >= $this->registered_timer ){
                            if( is_multisite() ){
                                include_once(ABSPATH.'/wp-admin/includes/ms.php');
                                wpmu_delete_user($EM_Person->ID);
                            }else{
                                include_once(ABSPATH.'/wp-admin/includes/user.php');
                                wp_delete_user($EM_Person->ID);
                            }
                            //remove email confirmation
                            global $EM_Notices;
                            $EM_Notices->notices['confirms'] = array();
                        }
                    }
                    $EM_Booking->delete();
                    return false;
                }
            }
        }
        return $result;
    }

    /**
     * @param $EM_Booking
     * @return mixed
     *  // Get the credit card details submitted by the form
    $token = $_POST['stripeToken'];

    print_r($_POST);
    echo 'stripeToken::'.$_POST["stripeToken"];

    \Stripe\Stripe::setApiKey($stripe['secret_key']);

    // Create the charge on Stripe's servers - this will charge the user's card
    try {
    $charge = \Stripe\Charge::create(array(
    "amount" => 1000, // amount in cents, again
    "currency" => "eur",
    "source" => $token,
    "description" => "Example charge")
    );
    echo 'striped';
    } catch(\Stripe\Error\Card $e) {
    echo $e;// The card has been declined
    }
     *
     */
    private function processStripePayment($EM_Booking) {
        global $EM_Notices;
        global $current_user;

        //$this->__set_api();

        /* check if account has customer token already, if not then create one */
        get_currentuserinfo();
        $user_id = $current_user->ID; //logged in user's ID

        //$cc_token = get_user_meta($user_id, 'stripe_customer_token', true);

        try {


//            if($this->debug=='yes'){
//                EM_Pro::log(sprintf( __( 'Credit Card token create', 'emp_stripe' )));
//            }

            // Email Info
            //$email_customer = get_option('em_'.$this->gateway.'_header_email_customer',0) ? '1':'0'; //for later
            //$header_email_receipt = get_option('em_'.$this->gateway.'_header_email_receipt');
            //$footer_email_receipt = get_option('em_'.$this->gateway.'_footer_email_receipt');

            if (get_option($this->gateway.'_mode') == 'live') {
                $key = get_option($this->gateway.'_live_secret_key');
            } else {
                $key = get_option($this->gateway.'_test_secret_key');
            }
            error_log('key '.$key);
            //Basic Credentials
            \Stripe\Stripe::setApiKey($key);

            $token = $_REQUEST['x_stripeToken'];
            error_log('$token '.$token);

            $amount = $EM_Booking->get_price(false, false, true);
            error_log('$amount '.$amount);

            //Order Info
            $booking_id = $EM_Booking->booking_id;
            $booking_description = preg_replace('/[^a-zA-Z0-9\s]/i', "", $EM_Booking->get_event()->event_name); //clean event name
            $charge = \Stripe\Charge::create(array(
                "amount" => $amount*100,
                "currency" => 'eu',//get_option('dbem_bookings_currency', 'USD'),
                "source" => $token,
//                "metadata" => array("order_id" => $booking_id),
                "description"=> $booking_description
            ));

//            if($this->debug=='yes'){
//                EM_Pro::log(sprintf( __( 'Return Response from Stripe: %s', 'emp_stripe'),print_r($charge,true)));
//            }

            if($token !=''){
                if ($charge->paid == true) {
                    if($this->debug=='yes') {
                        EM_Pro::log(sprintf( __( 'Payment Received...', 'emp_stripe' )));
                    }
                    $EM_Booking->booking_meta[$this->gateway] = array('txn_id'=>$charge->id, 'amount' => $amount);
                    $this->record_transaction($EM_Booking, $amount, get_option('dbem_bookings_currency', 'USD'), date('Y-m-d H:i:s', current_time('timestamp')), $charge->id, 'Completed', '');
                    $result = true;
                } else {
                    if($this->debug=='yes'){
                        EM_Pro::log(sprintf( __( 'Stripe payment failed. Payment declined.', 'emp_stripe' )));
                    }
                    $EM_Booking->add_error('Stripe payment failed. Payment declined.');
                    $result =  false;
                }
            }
            else
            {
                if($this->debug=='yes'){
                    EM_Pro::log(sprintf( __( 'Stripe payment failed. Payment declined. Please Check your Admin settings', 'emp_stripe' )));
                }
                $EM_Booking->add_error('Stripe payment failed. Payment declined. Please Check your Admin settings');
            }
            //Return transaction_id or false
            return apply_filters('em_gateway_stripe_capture', $result, $EM_Booking, $this);
        }catch(\Stripe\Error\Card $e) {
            error_log($e);// The card has been declined
            $EM_Booking->add_error(__('Connection error:', 'em-pro') . ': "' . $e->getMessage() . '"');
            return false;
        }
    }

    /**
     * Adds the stripe publishable key to as a javascript element
     */
    function em_pro_stripe_token_set_publishable_key() {
        if (get_option($this->gateway.'_mode') == 'live') {
            $key = get_option($this->gateway.'_live_publishable_key');
        } else {
            $key = get_option($this->gateway.'_test_publishable_key');
        }
        error_log('em_pro_stripe_token_set_publishable_key() '.$key);
        echo "<script type='text/javascript'>Stripe.setPublishableKey('$key');</script>";
    }

    /**
     * enqueue scripts
     *  <script type="text/javascript" src="https://js.stripe.com/v2/"></script>
     *  <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
     */
    public function em_pro_stripe_token_enqueue_scripts() {
        //error_log('em_pro_stripe_token_enqueue_scripts');
        /*
         * Stripe
         */
		wp_register_script(
			'stripe',
			'https://js.stripe.com/v2/');
		wp_enqueue_script('stripe');

        //this->em_pro_stripe_token_set_publishable_key();

        wp_register_script(
            'em_pro_stripe_token',
            plugins_url('js/em_pro_stripe_token.js',__FILE__),
            array('jquery'),
            '1.0.0',
            false);
        wp_enqueue_script('em_pro_stripe_token');
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
            update_option($key,stripslashes($option));
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