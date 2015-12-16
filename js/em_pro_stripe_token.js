(function($) {

    /**
     * https://pippinsplugins.com/stripe-integration-part-1-building-the-settings-and-a-simple-payment-form/
     * Add the stripe token to the form
     **/

    /**
     * Generate the stripe token on form submit
     */
    $('.em-booking-form').submit(function(event) {

        console.log('form submitted');
        //var $form = $(this);//$('.em-booking-form');
        //console.log('get form '.$form.attr('name'));

        // Disable the submit button to prevent repeated clicks
        $('.em-booking-submit').prop('disabled', true);
        console.log('disable button');

        var $form = $(this);
        Stripe.card.createToken($form, stripeResponseHandler);
        //console.log('create token - return false');

        // Prevent the form from submitting with the default action
        //$('.em-booking-submit').prop('disabled', false);
        //console.log('enable button');

        return false;
    });


    function stripeResponseHandler(status, response) {
        var $form = $('.em-booking-form');
        console.log('stripeResponseHandler get form');

        if (response.error) {
            console.log('stripeResponseHandler error '.response.error.message);
            // Show the errors on the form
            $form.find('#em-booking-buttons').text(response.error.message);
            $form.find('.em-booking-submit').prop('disabled', false);
        } else {
            console.log('stripeResponseHandler');
            // response contains id and card, which contains additional card details
            var token = response.id;
            console.log('stripeResponseHandler token');
            // Insert the token into the form so it gets submitted to the server
            $form.append($('<input type="hidden" name="stripeToken"/>').val(token));
            console.log('stripeResponseHandler add token to form ');
            // and submit
            $form.get(0).submit();
        }
    };

    // Stripe.setPublishableKey(stripe_vars.publishable_key);

    /**
     *wp_enqueue_script('stripe-processing', STRIPE_BASE_URL . 'includes/js/stripe-processing.js');
     wp_localize_script('stripe-processing', 'stripe_vars', array(
     'publishable_key' => $publishable,
     )
     );
     *
     */

})(jQuery);