jQuery(document).ready(function($) {

    /**
     * https://pippinsplugins.com/stripe-integration-part-1-building-the-settings-and-a-simple-payment-form/
     * v17.12.15 - 9.55
     * Generate the stripe token on form submit
     */
    $('.em-booking-form').submit(function(event) {

        console.log('form submitted');

        var $inputs = $('.em-booking-form :input');

        // not sure if you wanted this, but I thought I'd add it.
        // get an associative array of just the values.
        var values = {};
        $inputs.each(function() {
            values[this.name] = $(this).val();
        });
        console.log(values);

        var $form = $(this);

        //var $form = $(this);//$('.em-booking-form');
        //console.log('get form '.$form);

        // Disable the submit button to prevent repeated clicks
        $('.em-booking-submit').prop('disabled', true);
        console.log('disable button');


        Stripe.card.createToken($form, stripeResponseHandler);
        //console.log('create token - return false');

        // Prevent the form from submitting with the default action
        //$('.em-booking-submit').prop('disabled', false);
        //console.log('enable button');

        return false;
    });


    function stripeResponseHandler(status, response) {
        console.log('stripeResponseHandler');
        var $form = $('.em-booking-form');

        if (response.error) {
            console.log('stripeResponseHandler error '.response.error.message);
            // Show the errors on the form
            $('<div class="em-booking-message-error em-booking-message">'+response.error.message+'</div>').insertBefore($form);

            $form.find('#em-booking-buttons').text(response.error.message);
            $form.find('.em-booking-submit').prop('disabled', false);
        } else {
            console.log('stripeResponseHandler');
            //alert(response.id);
            console.log(response.card);
            console.log(response.id);
            // response contains id and card, which contains additional card details
            //var token = response.id;
            //console.log('stripeResponseHandler token '.token);
            // Insert the token into the form so it gets submitted to the server
            $form.append($('<input type="hidden" name="stripeToken"/>').val(response.id));
            console.log('stripeResponseHandler add token to form ');
            // and submit
            $form.get(0).submit();
            console.log('form submitted');
        }
    };
});