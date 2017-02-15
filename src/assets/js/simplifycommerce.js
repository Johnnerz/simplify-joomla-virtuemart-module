

jQuery(document).ready(function( $ ) {

    var checkoutForm = jQuery('#checkoutForm');

    function simplifyResponseHandler(data) {

        console.log("###### simplifyResponseHandler data = ", data);

        alert("Token Generated ");

        var simplifyPaymentForm = jQuery("#checkoutForm");
        jQuery('#sys-error-container').remove();

        // Check for errors
        if (data.error) {
            var errorMessages = {
                'card.number': 'The credit card number you entered is invalid.',
                'card.expYear': 'The expiry year on the credit card is invalid.'
            };

            // Show any validation errors
            if (data.error.code == "validation") {
                var fieldErrors = data.error.fieldErrors,
                    fieldErrorsLength = fieldErrors.length,
                    errorList = "";
                for (var i = 0; i < fieldErrorsLength; i++) {
                    errorList += "<div class='error'>" + errorMessages[fieldErrors[i].field] +
                        " " + fieldErrors[i].message + ".</div>";
                }
                // Display the errors
                jQuery('#error-container').html(errorList);
            } else {
                jQuery('#error-container').html('<div>' + data.error.message + '</div>');
            }
            // Re-enable the submit button
            jQuery("#process-payment-btn").removeAttr("disabled");
            jQuery('.simplify-processing').hide();
        } else {
            // The token contains id, last4, and card type
            var token = data["id"];
            // Insert the token into the form so it gets submitted to the server
            simplifyPaymentForm.append("<input type='hidden' name='simplifyToken' value='" + token + "' />");
            // Submit the form to the server
            simplifyPaymentForm.get(0).submit();
        }
    }

    checkoutForm.on("submit", function() {

        alert("Submitting form")

        if(jQuery('input[type=radio]:checked').parent().find('span.vmpayment_name').html() === 'Simplify Commerce'){

            jQuery(this).vm2front("startVmLoading");

            // Generate a card token & handle the response
            SimplifyCommerce.generateToken({
                key: jQuery(document).data('simplify_commerce_public_key'),
                card: {
                    number: jQuery("#cc-number").val(),
                    cvc: jQuery("#cc-cvc").val(),
                    expMonth: jQuery("#cc-exp-month").val(),
                    expYear: jQuery("#cc-exp-year").val()
                }
            }, simplifyResponseHandler);
            // Prevent the form from submitting
            return false;
        }

        console.log("Submitting form");
    });
    console.log("##### Loading DONE!");
});
