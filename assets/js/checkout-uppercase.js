/**
 * WooCommerce Checkout Field Uppercase Converter - Frontend Script
 * Author: Mike Lvd
 * Version: 1.0.0
 */

(function($) {
    'use strict';
    
    // Check if wcCheckoutUppercase object exists
    if (typeof wcCheckoutUppercase === 'undefined') {
        return;
    }
    
    /**
     * Convert string to uppercase with Greek character support
     */
    function toUpperCase(text) {
        if (!text || typeof text !== 'string') {
            return '';
        }
        
        // First, handle Greek characters using the map
        let result = text;
        if (wcCheckoutUppercase.greekMap) {
            for (let lowercase in wcCheckoutUppercase.greekMap) {
                if (wcCheckoutUppercase.greekMap.hasOwnProperty(lowercase)) {
                    const uppercase = wcCheckoutUppercase.greekMap[lowercase];
                    result = result.replace(new RegExp(lowercase, 'g'), uppercase);
                }
            }
        }
        
        // Then convert using native toUpperCase (handles Latin and most Unicode)
        result = result.toUpperCase();
        
        return result;
    }
    
    /**
     * Apply uppercase conversion to a field
     */
    function applyUppercase(field) {
        const $field = $(field);
        const currentValue = $field.val();
        
        if (!currentValue || typeof currentValue !== 'string') {
            return;
        }
        
        // Get cursor position before conversion
        const cursorPosition = field.selectionStart;
        const lengthBefore = currentValue.length;
        
        // Convert to uppercase
        const uppercaseValue = toUpperCase(currentValue);
        
        // Only update if value changed
        if (uppercaseValue !== currentValue) {
            $field.val(uppercaseValue);
            
            // Restore cursor position
            const lengthAfter = uppercaseValue.length;
            const newPosition = cursorPosition + (lengthAfter - lengthBefore);
            
            if (field.setSelectionRange) {
                field.setSelectionRange(newPosition, newPosition);
            }
        }
    }
    
    /**
     * Handle input event
     */
    function handleInput(e) {
        applyUppercase(e.target);
    }
    
    /**
     * Initialize uppercase conversion using event delegation
     */
    function initializeUppercaseFields() {
        const fields = wcCheckoutUppercase.fields || [];
        
        // Remove any existing delegated handlers to prevent duplicates
        $(document.body).off('.wcuppercase');
        
        // Use event delegation for better performance and reliability
        fields.forEach(function(fieldName) {
            const selector = '#' + fieldName;
            
            // Only process text inputs, not selects
            $(document.body).on('input.wcuppercase', selector + ':not(select)', handleInput);
            
            // Handle paste events
            $(document.body).on('paste.wcuppercase', selector + ':not(select)', function(e) {
                const field = e.target;
                setTimeout(function() {
                    applyUppercase(field);
                }, 10);
            });
            
            // Apply to existing values
            const $field = $(selector);
            if ($field.length && !$field.is('select') && $field.val()) {
                applyUppercase($field[0]);
            }
        });
        
        // Also apply to any existing field values after a short delay
        setTimeout(function() {
            fields.forEach(function(fieldName) {
                const $field = $('#' + fieldName);
                if ($field.length && !$field.is('select') && $field.val()) {
                    applyUppercase($field[0]);
                }
            });
        }, 100);
    }
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Initial setup
        initializeUppercaseFields();
        
        // Reinitialize on checkout update
        $(document.body).on('updated_checkout', function() {
            // Small delay to ensure fields are ready
            setTimeout(initializeUppercaseFields, 100);
        });
        
        // Handle country/state changes
        $(document.body).on('country_to_state_changed', function() {
            setTimeout(initializeUppercaseFields, 200);
        });
        
        // Handle shipping address toggle
        $(document.body).on('change', '#ship-to-different-address-checkbox', function() {
            if ($(this).is(':checked')) {
                setTimeout(initializeUppercaseFields, 200);
            }
        });
        
        // Reinitialize periodically to catch any missed updates
        setInterval(function() {
            // Check if we're still on checkout
            if ($('.woocommerce-checkout').length || $('.wc-block-checkout').length) {
                initializeUppercaseFields();
            }
        }, 5000);
    });
    
    // Support for WooCommerce Blocks
    if (window.wp && window.wp.data && window.wc && window.wc.blocksCheckout) {
        // For block-based checkout
        const { subscribe } = wp.data;
        
        subscribe(function() {
            if ($('.wc-block-checkout').length) {
                initializeUppercaseFields();
            }
        });
    }
    
})(jQuery);