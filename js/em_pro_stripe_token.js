jQuery(document).ready( function() {

    /**
     * Generate the stripe token on form submit
     */
    jQuery('.em-booking-form').submit(function(event) {

        //console.log('form submitted');
        var $form = jQuery(this);

        // Disable the submit button to prevent repeated clicks
        //$form.find('.em-booking-submit').prop('disabled', true);

        Stripe.card.createToken($form, stripeResponseHandler);

        console.log('create token');

        // Prevent the form from submitting with the default action
        return false;
    });

    /**
     * Add the stripe token to the form
     **/
    function stripeResponseHandler(status, response) {
        var $form = jQuery('.em-booking-form');
        if (response.error) {
            console.log('stripeResponseHandler error '.response.error.message);
            // Show the errors on the form
            $form.find('.em-booking-buttons').text(response.error.message);
            $form.find('.em-booking-submit').prop('disabled', false);
        } else {
            // response contains id and card, which contains additional card details
            var token = response.id;
            // Insert the token into the form so it gets submitted to the server
            $form.append($('<input type="hidden" name="x_stripeToken" />').val(token));
            console.log('stripeResponseHandler add token to form '.token);
            // and submit
            $form.get(0).submit();
        }
    };
});
