document.addEventListener('DOMContentLoaded', function () {
    // Cache DOM elements
    const ecocashMobileNumberField = document.getElementById('ecocash_mobile_number_field');
    const ecocashNumber = document.getElementById('ecocash_mobile_number');
    const paynowEmail = document.getElementById('paynow_email');
    const paynowAuthEmail = document.getElementById('paynow_auth_email');
    const billingEmailInput = document.querySelector('#billing_email');
    const radioButtons = document.querySelectorAll('input[name="paynow_payment_method"]');
    const paymentMethodCards = document.querySelectorAll('.paynow_ecocash_onemoney_method, .paynow_innbucks, .paynow_paynow');

    // Initialize state
    if (ecocashMobileNumberField) ecocashMobileNumberField.style.display = 'none';
    if (paynowEmail) paynowEmail.style.display = 'none';

    // Enhanced payment method selection with animations
    function updatePaymentMethodDisplay(selectedValue) {
        // Hide all additional fields first
        if (ecocashMobileNumberField) {
            ecocashMobileNumberField.style.display = 'none';
            ecocashMobileNumberField.setAttribute('aria-hidden', 'true');
        }
        if (paynowEmail) {
            paynowEmail.style.display = 'none';
            paynowEmail.setAttribute('aria-hidden', 'true');
        }

        // Update card states and show relevant fields
        paymentMethodCards.forEach(card => {
            const input = card.querySelector('input[type="radio"]');
            if (input) {
                const isSelected = input.value === selectedValue;
                card.setAttribute('aria-checked', isSelected.toString());
                card.classList.toggle('selected', isSelected);
            }
        });        // Show appropriate field based on selection
        if (selectedValue === 'paynow') {
            console.log('Paynow selected, showing email field'); // Debug log
            if (paynowEmail) {
                paynowEmail.style.display = 'block';
                paynowEmail.setAttribute('aria-hidden', 'false');
                console.log('Email field displayed'); // Debug log
                setTimeout(() => paynowAuthEmail?.focus(), 100);
            } else {
                console.log('Paynow email field not found'); // Debug log
            }
        } else if (selectedValue === 'ecocash_onemoney' || selectedValue === 'innbucks') {
            if (ecocashMobileNumberField) {
                ecocashMobileNumberField.style.display = 'block';
                ecocashMobileNumberField.setAttribute('aria-hidden', 'false');
                setTimeout(() => ecocashNumber?.focus(), 100);
            }
        }
    }

    // Enhanced event handlers for radio buttons
    radioButtons.forEach(function (radioButton) {
        radioButton.addEventListener('change', function () {
            if (this.checked) {
                updatePaymentMethodDisplay(this.value);
                // Announce change to screen readers
                const label = document.querySelector(`label[for="${this.id}"]`);
                if (label) {
                    announceToScreenReader(`Selected payment method: ${label.textContent.trim()}`);
                }
            }
        });
    });

    // Card click handlers for better UX
    paymentMethodCards.forEach(card => {
        card.addEventListener('click', function (e) {
            if (e.target.tagName !== 'INPUT') {
                const radio = this.querySelector('input[type="radio"]');
                if (radio && !radio.checked) {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change'));
                }
            }
        });

        // Keyboard navigation support
        card.addEventListener('keydown', function (e) {
            if (e.key === ' ' || e.key === 'Enter') {
                e.preventDefault();
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change'));
                }
            }
        });
    });

    // Enhanced email synchronization
    function syncBillingEmail() {
        if (billingEmailInput && paynowAuthEmail) {
            paynowAuthEmail.value = billingEmailInput.value;
        }
    }

    if (billingEmailInput && paynowAuthEmail) {
        // Initial sync
        syncBillingEmail();
        
        // Real-time sync
        billingEmailInput.addEventListener('input', syncBillingEmail);
        billingEmailInput.addEventListener('blur', syncBillingEmail);
    }

    // Input validation and formatting
    if (ecocashNumber) {
        ecocashNumber.addEventListener('input', function (e) {
            let value = e.target.value.replace(/[^\d+\-\s\(\)]/g, '');
            e.target.value = value;
            
            // Visual feedback for valid/invalid format
            const isValid = /^[+]?[0-9\s\-\(\)]{10,}$/.test(value);
            e.target.classList.toggle('valid', isValid && value.length > 0);
            e.target.classList.toggle('invalid', !isValid && value.length > 0);
        });
    }

    if (paynowAuthEmail) {
        paynowAuthEmail.addEventListener('input', function (e) {
            const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e.target.value);
            e.target.classList.toggle('valid', isValid);
            e.target.classList.toggle('invalid', !isValid && e.target.value.length > 0);
        });
    }

    // Screen reader announcements
    function announceToScreenReader(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.style.position = 'absolute';
        announcement.style.left = '-10000px';
        announcement.style.width = '1px';
        announcement.style.height = '1px';
        announcement.style.overflow = 'hidden';
        announcement.textContent = message;
        
        document.body.appendChild(announcement);
        setTimeout(() => document.body.removeChild(announcement), 1000);
    }

    // Enhanced WooCommerce integration with improved error handling
    (function ($) {
        'use strict';
    
        if (typeof $ === 'undefined') return;

        $(document).ready(function () {
            updatePaymentGateway();
            
            // Enhanced checkout form event handling
            $('form.checkout').on('change', 'input[name="payment_method"]', function () {    
                updatePaymentGateway();
            });

            // Handle checkout errors gracefully
            $(document.body).on('checkout_error', function() {
                // Re-show payment fields if there's an error
                updatePaymentGateway();
                
                // Focus on the first error field
                setTimeout(() => {
                    const firstError = $('.woocommerce-error, .woocommerce-message').first();
                    if (firstError.length) {
                        firstError[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 100);
            });
        });
    
        function updatePaymentGateway() {
            const current = $('form[name="checkout"] input[name="payment_method"]:checked').val();
            const paynowField = $("#paynow_custom_checkout_field");
            
            if (current === 'paynow') {
                paynowField.slideDown(300).attr('aria-hidden', 'false');
                // Ensure proper focus management
                setTimeout(() => {
                    const firstRadio = paynowField.find('input[name="paynow_payment_method"]:first');
                    if (firstRadio.length && !paynowField.find('input[name="paynow_payment_method"]:checked').length) {
                        firstRadio.focus();
                    }
                }, 350);
            } else {
                paynowField.slideUp(300).attr('aria-hidden', 'true');
            }
        }
    })(window.jQuery);
});