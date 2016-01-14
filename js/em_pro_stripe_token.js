jQuery(document).ready(function($) {

    /**
     * Validation of the credit card
     * http://bootsnipp.com/snippets/featured/credit-card-payment-with-stripe
     * https://www.wpkb.com/how-to-code-your-own-wordpress-contact-form-with-jquery-validation/
     */
    $(".em-booking-form").validate({
        debug: true,
        rules: {
            stripe_number: { required: true, digits: true, minlength: 16 },
            stripe_exp_month: { required: true, digits: true, minlength: 2 },
            stripe_exp_year: { required: true, digits: true, minlength: 4 },
            stripe_cvc: { required: true, digits: true, minlength: 3 }
        },
        messages: {
            stripe_number: "Invalid Card Number",
            stripe_exp_month: "Invalid Card Month",
            stripe_exp_year: "Invalid Card Year",
            stripe_cvc: "Invalid CVC"
        }
    });

    /**
     * https://pippinsplugins.com/stripe-integration-part-1-building-the-settings-and-a-simple-payment-form/
     * v17.12.15 - 9.55
     * Generate the stripe token on form submit
     */
    $('.em-booking-form').submit(function(event) {

        //$(".em-booking-form").valid();
        var cc = $('.stripe_number').val();
        console.log('cc number '.cc);

        if($(".em-booking-form").valid()){   // test for validity
            console.log('form submitted');
            // do stuff if form is valid

            // Print inoput values
            var $inputs = $('.em-booking-form :input');
            var values = {};
            $inputs.each(function() {
                values[this.name] = $(this).val();
            });
            console.log(values);

            var $form = $(this);

            // Disable the submit button to prevent repeated clicks
            $('.em-booking-submit').prop('disabled', true);
            //console.log('disable button');

            Stripe.card.createToken($form, stripeResponseHandler);
            //console.log('create token - return false');

            // Prevent the form from submitting with the default action
            //$('.em-booking-submit').prop('disabled', false);
            //console.log('enable button');

            return false;
        }
        //else {
        //    // do stuff if form is not valid
        //    console.log('form has invalid values');
        //    return false;
        //}
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