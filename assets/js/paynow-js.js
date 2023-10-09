document.addEventListener('DOMContentLoaded', function() {
    // Initially hide the field
    var ecocashMobileNumberField = document.getElementById('ecocash_mobile_number_field');
    ecocashMobileNumberField.style.display = 'none';

    // Event handler for radio button click
    var radioButtons = document.querySelectorAll('input[name="paynow_payment_method"]');
    radioButtons.forEach(function(radioButton) {
        radioButton.addEventListener('change', function() {
            if (this.value === 'paynow') {
                ecocashMobileNumberField.style.display = 'none';
            } else {
                ecocashMobileNumberField.style.display = 'block';
            }
        });
    });
});