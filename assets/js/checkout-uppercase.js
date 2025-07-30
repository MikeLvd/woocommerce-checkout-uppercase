/**
 * WooCommerce Checkout Field Uppercase Converter - Frontend Script
 * Author: Mike Lvd
 * Version: 1.0.1
 */

(function($) {
    'use strict';
    
    // Check if wcCheckoutUppercase object exists
    if (typeof wcCheckoutUppercase === 'undefined') {
        console.warn('WC Checkout Uppercase: Configuration object not found');
        return;
    }
    
    // Track processed fields to avoid duplicate bindings
    const processedFields = new Set();
    
    /**
     * Convert string to uppercase with Greek character support
     */
    function toUpperCase(text) {
        if (!text || typeof text !== 'string') {
            return '';
        }
        
        try {
            // First, handle Greek characters using the map
            let result = text;
            if (wcCheckoutUppercase.greekMap) {
                for (let lowercase in wcCheckoutUppercase.greekMap) {
                    if (wcCheckoutUppercase.greekMap.hasOwnProperty(lowercase)) {
                        const uppercase = wcCheckoutUppercase.greekMap[lowercase];
                        // Use global replace for all occurrences
                        const regex = new RegExp(lowercase.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g');
                        result = result.replace(regex, uppercase);
                    }
                }
            }
            
            // Then convert using native toUpperCase (handles Latin and most Unicode)
            result = result.toUpperCase();
            
            return result;
        } catch (error) {
            console.error('WC Checkout Uppercase: Error converting text', error);
            return text.toUpperCase(); // Fallback to simple uppercase
        }
    }
    
    /**
     * Apply uppercase conversion to a field
     */
    function applyUppercase(field) {
        if (!field || field.readOnly || field.disabled) {
            return;
        }
        
        const $field = $(field);
        const currentValue = $field.val();
        
        if (!currentValue || typeof currentValue !== 'string') {
            return;
        }
        
        // Get cursor position before conversion
        const cursorPosition = field.selectionStart || currentValue.length;
        const selectionEnd = field.selectionEnd || cursorPosition;
        
        // Convert to uppercase
        const uppercaseValue = toUpperCase(currentValue);
        
        // Only update if value changed
        if (uppercaseValue !== currentValue) {
            $field.val(uppercaseValue);
            
            // Restore cursor position
            if (field.setSelectionRange) {
                try {
                    field.setSelectionRange(cursorPosition, selectionEnd);
                } catch (e) {
                    // Ignore cursor positioning errors
                }
            }
        }
    }
    
    /**
     * Handle input event - immediate conversion
     */
    function handleInput(e) {
        applyUppercase(e.target);
    }
    
    /**
     * Handle paste event
     */
    function handlePaste(e) {
        const field = e.target;
        // Small timeout to allow paste to complete
        setTimeout(function() {
            applyUppercase(field);
        }, 10);
    }
    
    /**
     * Initialize field with uppercase conversion
     */
    function initializeField(fieldName) {
        const $field = $('#' + fieldName);
        
        // Skip if field doesn't exist, is a select, or already processed
        if (!$field.length || $field.is('select') || processedFields.has(fieldName)) {
            return;
        }
        
        // Mark as processed
        processedFields.add(fieldName);
        
        // Remove any existing handlers to avoid duplicates
        $field.off('.wcuppercase');
        
        // Bind events for immediate conversion
        $field.on('input.wcuppercase', handleInput);
        $field.on('paste.wcuppercase', handlePaste);
        
        // Also handle blur event to ensure conversion
        $field.on('blur.wcuppercase', function() {
            applyUppercase(this);
        });
        
        // Apply to existing value
        if ($field.val()) {
            applyUppercase($field[0]);
        }
    }
    
    /**
     * Initialize all uppercase fields
     */
    function initializeAllFields() {
        const fields = wcCheckoutUppercase.fields || [];
        
        // Clear processed fields set to allow re-initialization
        processedFields.clear();
        
        // Initialize each field
        fields.forEach(function(fieldName) {
            initializeField(fieldName);
        });
    }
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Initial setup
        initializeAllFields();
        
        // Re-initialize on checkout update
        $(document.body).on('updated_checkout', function() {
            setTimeout(initializeAllFields, 100);
        });
        
        // Re-initialize on checkout initialization
        $(document.body).on('init_checkout', function() {
            setTimeout(initializeAllFields, 100);
        });
        
        // Handle country/state changes
        $(document.body).on('country_to_state_changed', function() {
            setTimeout(initializeAllFields, 100);
        });
        
        // Handle shipping address toggle
        $(document.body).on('change', '#ship-to-different-address-checkbox', function() {
            if ($(this).is(':checked')) {
                setTimeout(initializeAllFields, 100);
            }
        });
        
        // For checkout field that might be added dynamically
        $(document.body).on('focus', 'input[type="text"], input[type="tel"], textarea', function() {
            const fieldId = $(this).attr('id');
            if (fieldId && wcCheckoutUppercase.fields.includes(fieldId)) {
                initializeField(fieldId);
            }
        });
    });
    
    // Support for WooCommerce Blocks
    if (window.wp && window.wp.data) {
        // Listen for block-based checkout updates
        if (window.wp.data.subscribe) {
            window.wp.data.subscribe(function() {
                // Check if we're on block checkout
                if ($('.wc-block-checkout').length || $('.wp-block-woocommerce-checkout').length) {
                    // Re-initialize fields for block checkout
                    setTimeout(initializeAllFields, 200);
                }
            });
        }
    }
    
    // Additional support for dynamically loaded content
    if (window.MutationObserver) {
        let observer = null;
        
        function setupObserver() {
            const checkoutForm = document.querySelector('.woocommerce-checkout, .wc-block-checkout, .wp-block-woocommerce-checkout');
            
            if (!checkoutForm || observer) {
                return;
            }
            
            observer = new MutationObserver(function(mutations) {
                let shouldReinitialize = false;
                
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1) { // Element node
                                // Check if any of our fields were added
                                wcCheckoutUppercase.fields.forEach(function(fieldName) {
                                    if (node.id === fieldName || node.querySelector('#' + fieldName)) {
                                        shouldReinitialize = true;
                                    }
                                });
                            }
                        });
                    }
                });
                
                if (shouldReinitialize) {
                    initializeAllFields();
                }
            });
            
            observer.observe(checkoutForm, {
                childList: true,
                subtree: true
            });
        }
        
        // Set up observer when checkout form is available
        $(document).ready(function() {
            setupObserver();
            
            // Also try to set up observer after checkout updates
            $(document.body).on('updated_checkout init_checkout', function() {
                setTimeout(setupObserver, 100);
            });
        });
    }
    
})(jQuery);