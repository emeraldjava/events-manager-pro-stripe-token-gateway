<?php
/**
 * Events Manager Pro - Stripe Token Gateway
 * @package   EM_Gateway_Stripe_Token
 * @author    Paul O'Connell <paul.t.oconnell@gmail.com>
 * @license   GPL-2.0+
 * Date: 18/12/15
 * Time: 13:34
 */
require_once('vendor/autoload.php');

class EM_Gateway_Stripe_Token extends EM_Gateway {

    var $gateway = 'em_pro_stripe_token';
    var $title = 'Stripe Token';
    var $status = 4;
    var $status_txt = 'Processing (Stripe)';
    var $button_enabled = false; //we can's use a button here
    var $supports_multiple_bookings = false;
    var $registered_timer = 0;

    function __construct() {
        parent::__construct();

        if ($this->is_active()) {
            //Force SSL for booking submissions, since we have card info
            if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')
                || (get_option('em_' . $this->gateway . '_mode') == 'live')
            ) {
                /* modify booking script, force SSL for all */
                add_filter('em_booking_form_action_url', array($this, 'force_ssl'), 10, 1);
            }
            add_action('wp_head',array($this,'em_pro_stripe_token_set_publishable_key'));
            add_action('wp_enqueue_scripts',array($this,'em_pro_stripe_token_enqueue_scripts'));
            add_action('wp_enqueue_scripts',array($this,'em_pro_stripe_token_enqueue_style'));
            //add_action('em_gateway_js', array(&$this,'em_gateway_js'));
        }
    }

    /**
     * https://wordpress.org/support/topic/em-datepicker-style-overwrites-the-theme-datepicker-style
     * https://wordpress.org/support/topic/css-conflict-with-wp-fullcalendar-09-10
     * @param $localized_array
     * @return mixed
     */
    public function em_wp_localize_script($localized_array) {
        $localized_array['bookingajaxurl'] = $this->force_ssl($localized_array['bookingajaxurl']);
        if ( ! is_admin() )
            $localized_array['ajaxurl'] = $this->force_ssl($localized_array['ajaxurl']);
        return $localized_array;
    }

    public function booking_add($EM_Event,$EM_Booking, $post_validation = false){
        $this->registered_timer = current_time('timestamp', 1);
        parent::booking_add($EM_Event, $EM_Booking, $post_validation);
        if ( $post_validation && empty($EM_Booking->booking_id) ) {
            if ( get_option('dbem_multiple_bookings') && get_class($EM_Booking) == 'EM_Multiple_Booking' ) {
                add_filter( 'em_multiple_booking_save', array( &$this, 'bhaa_em_booking_save' ), 2, 2 );
            } else {
                add_filter( 'em_booking_save', array( &$this, 'bhaa_em_booking_save' ), 2, 2 );
            }
        }
    }

    /**
     * @param $result
     * @param $EM_Booking
     * @return bool
     */
    public function bhaa_em_booking_save($result, $EM_Booking) {
        global $wpdb, $wp_rewrite, $EM_Notices;

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
                    error_log('deleting booking '.json_encode($EM_Booking->person_id));
                    $EM_Booking->delete();
                    return false;
                }
            }
        }
        return $result;
    }

    private function setStripeApiKey() {
        if (get_option($this->gateway.'_mode') == 'live') {
            $key = get_option($this->gateway.'_live_secret_key');
        } else {
            $key = get_option($this->gateway.'_test_secret_key');
        }
        \Stripe\Stripe::setApiKey($key);
    }

    /**
     * https://stripe.com/docs/api?lang=php#create_customer
     * https://pippinsplugins.com/stripe-integration-part-7-creating-and-storing-customers/
     */
    private function getStripeCustomer($bhaa_id,$token,$displayname,$email) {
        $stripe_customer_id = get_user_meta( $bhaa_id,'stripe_customer_id', true );
        if($bhaa_id==0) {
            return 'bhaa id cannot be 0';
        }

        // if we have no stripe token saved
        if(!$stripe_customer_id) {
            // create a new customer if our current user doesn't have one
            $stripe_customer = \Stripe\Customer::create(array(
                    'source' => $token,
                    'description' => $displayname,
                    'metadata' => array('BHAA_ID'=>$bhaa_id),
                    'email' => $email
                )
            );
            $stripe_customer_id=$stripe_customer->id;
            update_user_meta( $bhaa_id, 'stripe_customer_id', $stripe_customer_id );
        } else {
            $customer = \Stripe\Customer::retrieve($stripe_customer_id);
            $new_card = $customer->sources->create(array("source" => $token));

            // http://stackoverflow.com/questions/31627961/stripe-change-credit-card-number
            // set the default card
            $customer->default_source = $new_card->id;
            $customer->save();
        }
        error_log('getStripeCustomer() '.$bhaa_id.' '.$displayname.' '.$email.' '.$stripe_customer_id);
        return $stripe_customer_id;
    }

    /**
     * https://stripe.com/docs/api?lang=php#create_customer
     * https://pippinsplugins.com/stripe-integration-part-7-creating-and-storing-customers/
     */
    private function processStripePayment($EM_Booking) {
        global $EM_Booking;
        global $current_user;

        $this->setStripeApiKey();
        $token = $_REQUEST['stripeToken'];
        error_log('Start token :: '.$token);
        error_log('$EM_Booking->booking_id :: '.$EM_Booking->booking_id);
        $booking_id = isset($EM_Booking->booking_id)?$EM_Booking->booking_id:0;

        $charge ='';
        try {

            // get the current user
            get_currentuserinfo();
            if($current_user->ID!=0) {
                // existing bhaa user
                $user_id = $current_user->ID;
                $email = $current_user->user_email;
                $name = $current_user->display_name;
                $type = 'Existing';
            } else {
                // new member
                $user_id = $EM_Booking->person_id;
                $email = $EM_Booking->person->user_email;
                $name = $EM_Booking->person->user_email;
                $type = 'New';
            }
            error_log(sprintf('%s user, email %s, ID %s',$type, $email, $user_id));

            $customerStripeToken = $this->getStripeCustomer($user_id,$token,$name,$email);
            $amount = $EM_Booking->get_price(false, false, true);

            //Order Info
            $booking_id = $EM_Booking->booking_id;
            $booking_description = preg_replace('/[^a-zA-Z0-9\s]/i', "", $EM_Booking->get_event()->event_name);

            // https://stripe.com/docs/api?lang=php#create_charge
            $charge = \Stripe\Charge::create(array(
                'amount' => $amount*100,
                'currency' => 'eur',
                'customer' => $customerStripeToken,
                //'source' => $token,
                'description' => 'BHAA '.$booking_description,
                'metadata' => array(
                    'booking_id' => $booking_id,
                    'bhaa'=>$user_id,
                    'event_name' => $booking_description,
                    'email' => $email,
                    'fee' => $amount),
                'statement_descriptor' => "BHAA",
                'receipt_email' => $email
            ));

        } catch(\Stripe\Error\Card $e) {
            error_log( $body = $e->getJsonBody());// The card has been declined
            $err  = $body['error'];
            $error= '';//Status is:' . $e->getHttpStatus() . "\n";
            $error.='Type is:' . $err['type'] . "\n";
            $error.='Code is:' . $err['code'] . "\n";
            // param is '' in this case
            $error.='Param is:' . $err['param'] . "\n";
            $error.='Message is:' . $err['message'] . "\n";
            error_log(sprintf( __( 'Stripe payment failed. Payment declined. Please Check your Admin settings'.$error, 'emp_stripe' )));
            $EM_Booking->add_error('Stripe payment failed. Reason - '.$err['message']);
            //$EM_Booking->add_error(__('Connection error:', 'em-pro') . ': "' . $e->getMessage() . '"');
            return false;
        } catch (\Stripe\Error\InvalidRequest $e) {
            // Invalid parameters were supplied to Stripe's API
            error_log( $e );
        } catch (\Stripe\Error\Authentication $e) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            error_log( $e->getJsonBody());
        } catch (\Stripe\Error\ApiConnection $e) {
            // Network communication with Stripe failed
            error_log( $e->getJsonBody());
        } catch (\Stripe\Error\Base $e) {
            // Display a very generic error to the user, and maybe send
            // yourself an email
            error_log( $e->getJsonBody());
        } catch (Exception $e) {
            // Something else happened, completely unrelated to Stripe
            error_log($e);
        }

        if ($charge->paid == true) {
            $EM_Booking->booking_meta[$this->gateway] = array('txn_id'=>$charge->id, 'amount' => $amount);
            $this->record_transaction($EM_Booking, $amount, get_option('dbem_bookings_currency', 'USD'),
                date('Y-m-d H:i:s', current_time('timestamp')), $charge->id, 'Completed', '');

            $EM_Booking->get_tickets();
            //$EM_Booking = new EM_Booking($booking_id);
            // based on the ticket set the
            foreach($EM_Booking->tickets as $ticket){
                switch(strtolower($ticket->ticket_name)) {
                    case "annual membership":
                        //process membership
                        $status_res = update_user_meta( $user_id, "bhaa_runner_status", "M");
                        error_log('End Annual Membership - bhaa_runner_status='.$user_id.':'.$status_res);
                        $timestamp = date('Y-m-d');
                        $date_res = update_user_meta( $user_id, "bhaa_runner_dateofrenewal", $timestamp);
                        //error_log('End Annual membership - bhaa_runner_dateofrenewal='.$timestamp.':'.$date_res);
                        break;
                    case "day member ticket":
                        $status_res = update_user_meta( $user_id, "bhaa_runner_status", "D");
                        error_log('End Day Member bhaa_runner_status='.$user_id.':'.$status_res);
                    default:
                        error_log($ticket->ticket_name);
                        break;
                }
            }
            $result = true;
        } else {
            error_log('Stripe payment failed. Payment declined.');
            $EM_Booking->add_error('Stripe payment failed. Payment declined.');
            $result =  false;
        }
        $EM_Booking->get_person()->user_email = $current_user->user_email;
        //Return transaction_id or false
        return apply_filters('em_gateway_stripe_capture', $result, $EM_Booking, $this);
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
        echo "<!-- Stripe --><script type='text/javascript'>Stripe.setPublishableKey('$key');</script>";
    }

    /**
     * enqueue scripts
     *  <script type="text/javascript" src="https://js.stripe.com/v2/"></script>
     *  <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
     */
    public function em_pro_stripe_token_enqueue_scripts() {
        /*
         * Stripe
         */
		wp_register_script(
			'stripe',
			'https://js.stripe.com/v2/');
		wp_enqueue_script('stripe');

        // http://bootsnipp.com/snippets/featured/credit-card-payment-with-stripe
        wp_register_script(
            'jquery.validate',
            'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.13.1/jquery.validate.min.js');
        wp_enqueue_script('jquery.validate');

//        wp_register_script(
//            'jquery.payment',
//            'https://cdnjs.cloudflare.com/ajax/libs/jquery.payment/1.2.3/jquery.payment.min.js');
//        wp_enqueue_script('jquery.payment');

        wp_register_script(
            'em_pro_stripe_token',
            plugins_url('js/em_pro_stripe_token.js',__FILE__)
            ,array('jquery')
        );
        wp_enqueue_script('em_pro_stripe_token');
    }

    public function em_pro_stripe_token_enqueue_style() {
        wp_enqueue_style(
            'em_pro_stripe_token',
            plugins_url('css/em_pro_stripe_token.css',__FILE__),
            false);
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
            $this->gateway . "_live_secret_key" => $_REQUEST[ '_live_secret_key' ],
            $this->gateway . "_email_customer" => $_REQUEST[ '_email_customer' ],
            $this->gateway . "_header_email_receipt" => $_REQUEST[ '_header_email_receipt' ],
            $this->gateway . "_footer_email_receipt" => $_REQUEST[ '_footer_email_receipt' ],
            $this->gateway . "_manual_approval" => $_REQUEST[ '_manual_approval' ],
            $this->gateway . "_booking_feedback" => wp_kses_data($_REQUEST[ '_booking_feedback' ]),
            $this->gateway . "_booking_feedback_free" => wp_kses_data($_REQUEST[ '_booking_feedback_free' ]),
            $this->gateway . "_debug" => $_REQUEST['_debug' ]
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