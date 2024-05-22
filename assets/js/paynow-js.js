document.addEventListener('DOMContentLoaded', function () {
    // Initially hide the field
    var ecocashMobileNumberField = document.getElementById('ecocash_mobile_number_field');
    var ecocash_nummber = document.getElementById('ecocash_mobile_number')
    ecocashMobileNumberField.style.display = 'none';
    var paynow_email = document.getElementById('paynow_email');
    var paynow_auth_email = document.getElementById('paynow_auth_email');

    // Event handler for radio button click
    var radioButtons = document.querySelectorAll('input[name="paynow_payment_method"]');
    radioButtons.forEach(function (radioButton) {
        radioButton.addEventListener('change', function () {
            if (this.value === 'paynow') {
                ecocashMobileNumberField.style.display = 'none';
                paynow_email.style.display = 'block';
                paynow_auth_email.focus();
            } else {
                paynow_email.style.display = 'none';
                ecocashMobileNumberField.style.display = 'block';

                ecocash_nummber.focus();
            }
        });
    });
    var billingEmailInput = document.querySelector('#billing_email');
    var paynowAuthEmailInput = document.querySelector('#paynow_auth_email');
    paynowAuthEmailInput.value = billingEmailInput.value;

    // Add an event listener to the billingEmailInput field
    billingEmailInput.addEventListener('keyup', function () {
        // Copy the value from billingEmailInput to paynowAuthEmailInput
        paynowAuthEmailInput.value = billingEmailInput.value;
    });
    


    (function ($) {
        'use strict';
    
        $(document).ready(function () {
            updatedPaymentGateway();
            $('form.checkout').on('change', 'input[name="payment_method"]', function () {    
                updatedPaymentGateway();
            });
        });
    
        function updatedPaymentGateway() {
            const current = $('form[name="checkout"] input[name="payment_method"]:checked').val();
    
            if (current == 'paynow') {
                $("#paynow_custom_checkout_field").show()
            }else{
                $("#paynow_custom_checkout_field").hide()
            }
        }
    })(jQuery);
});