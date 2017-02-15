jQuery(document).ready(function ($) {

    var checkoutForm = jQuery('#checkoutForm');

    function simplifyResponseHandler(data) {

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

            console.log("Simplify Card Token = ", token);
            // Insert the token into the form so it gets submitted to the server
            jQuery('#checkoutForm').append("<input type='hidden' id='simplifyToken' name='simplifyToken' value='" + token + "' />");
            // Submit the form to the server
            jQuery('#checkoutForm').submit();
        }
    }

    checkoutForm.on("submit", function (e) {

        if (jQuery('#simplifyToken').val()) {
            //if we are submitting using the token, just move on
            console.log("Simplify Card Token available");
            return true;
        }
        else if (jQuery('input[type=radio]:checked').parent().find('span.vmpayment_name').html() === 'Simplify Commerce') {

            var ccNumber = jQuery("#cc-number").val();
            var expMonth = jQuery("#cc-exp-month").val();
            var expYear = jQuery("#cc-exp-year").val();

            if (ccNumber && expMonth && expYear) {

                jQuery(this).vm2front("startVmLoading");

                // Generate a card token & handle the response
                SimplifyCommerce.generateToken({
                    key: jQuery(document).data('simplify_commerce_public_key'),
                    card: {
                        number: ccNumber,
                        cvc: jQuery("#cc-cvc").val(),
                        expMonth: expMonth,
                        expYear: expYear
                    }
                }, simplifyResponseHandler);
                // Prevent the form from submitting
                return false;
            }
        }
    });
});
