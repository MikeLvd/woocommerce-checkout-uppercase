/**
 * WooCommerce Checkout Field Uppercase Converter - Frontend Script
 * Author: Mike Lvd
 * Version: 1.0.2
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
     * Convert string to lowercase
     */
    function toLowerCase(text) {
        if (!text || typeof text !== 'string') {
            return '';
        }
        
        return text.toLowerCase();
    }
    
    /**
     * Format phone number
     * Removes country code and formats as XXX XXX XXXX
     */
    function formatPhoneNumber(phone) {
        if (!phone || typeof phone !== 'string' || !wcCheckoutUppercase.formatPhones) {
            return phone;
        }
        
        // Remove all non-numeric characters except + at the beginning
        let cleaned = phone.replace(/[^\d+]/g, '');
        cleaned = cleaned.replace(/\+(?!^)/g, '');
        
        // Remove country codes
        const countryCodes = wcCheckoutUppercase.countryCodes || ['+30', '0030'];
        for (let code of countryCodes) {
            if (cleaned.startsWith(code)) {
                cleaned = cleaned.substring(code.length);
                break;
            }
        }
        
        // Special handling for Greek numbers starting with 30 (without + or 00)
        // Check if it starts with 30 followed by a mobile (6) or landline (2) prefix
        if (cleaned.length === 12 && cleaned.startsWith('30') && 
            (cleaned.charAt(2) === '6' || cleaned.charAt(2) === '2')) {
            cleaned = cleaned.substring(2);
        }
        
        // Remove any leading zeros
        cleaned = cleaned.replace(/^0+/, '');
        
        // Format Greek mobile numbers (10 digits starting with 6)
        if (cleaned.length === 10 && cleaned.startsWith('6')) {
            return cleaned.substring(0, 3) + ' ' + 
                   cleaned.substring(3, 6) + ' ' + 
                   cleaned.substring(6, 10);
        }
        // Format Greek landline numbers (10 digits starting with 2)
        else if (cleaned.length === 10 && cleaned.startsWith('2')) {
            return cleaned.substring(0, 3) + ' ' + 
                   cleaned.substring(3, 6) + ' ' + 
                   cleaned.substring(6, 10);
        }
        
        // Return cleaned number if it doesn't match expected patterns
        return cleaned;
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
     * Apply lowercase conversion to email field
     */
    function applyLowercase(field) {
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
        
        // Convert to lowercase and trim spaces
        const lowercaseValue = toLowerCase(currentValue.trim());
        
        // Only update if value changed
        if (lowercaseValue !== currentValue) {
            $field.val(lowercaseValue);
            
            // Restore cursor position
            if (field.setSelectionRange) {
                try {
                    // Adjust cursor position if spaces were trimmed
                    const adjustedPosition = Math.min(cursorPosition, lowercaseValue.length);
                    field.setSelectionRange(adjustedPosition, adjustedPosition);
                } catch (e) {
                    // Ignore cursor positioning errors
                }
            }
        }
    }
    
    /**
     * Apply phone formatting to a field
     */
    function applyPhoneFormat(field) {
        if (!field || field.readOnly || field.disabled) {
            return;
        }
        
        const $field = $(field);
        const currentValue = $field.val();
        
        if (!currentValue || typeof currentValue !== 'string') {
            return;
        }
        
        // Get cursor position before formatting
        const cursorPosition = field.selectionStart || currentValue.length;
        
        // Format the phone number
        const formattedValue = formatPhoneNumber(currentValue);
        
        // Only update if value changed
        if (formattedValue !== currentValue) {
            $field.val(formattedValue);
            
            // Adjust cursor position based on formatting
            if (field.setSelectionRange) {
                try {
                    // Calculate new cursor position considering added spaces
                    let newPosition = cursorPosition;
                    const spacesBeforeCursor = (currentValue.substring(0, cursorPosition).match(/ /g) || []).length;
                    const spacesInFormatted = (formattedValue.substring(0, cursorPosition + spacesBeforeCursor).match(/ /g) || []).length;
                    newPosition = cursorPosition + (spacesInFormatted - spacesBeforeCursor);
                    
                    // Make sure position is within bounds
                    newPosition = Math.min(newPosition, formattedValue.length);
                    newPosition = Math.max(0, newPosition);
                    
                    field.setSelectionRange(newPosition, newPosition);
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
     * Handle email input event - immediate lowercase conversion
     */
    function handleEmailInput(e) {
        applyLowercase(e.target);
    }
    
    /**
     * Handle phone input event - immediate formatting
     */
    function handlePhoneInput(e) {
        applyPhoneFormat(e.target);
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
     * Handle email paste event
     */
    function handleEmailPaste(e) {
        const field = e.target;
        // Small timeout to allow paste to complete
        setTimeout(function() {
            applyLowercase(field);
        }, 10);
    }
    
    /**
     * Handle phone paste event
     */
    function handlePhonePaste(e) {
        const field = e.target;
        // Small timeout to allow paste to complete
        setTimeout(function() {
            applyPhoneFormat(field);
        }, 10);
    }
    
    /**
     * Initialize field with appropriate conversion
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
        
        // Check if this is an email field
        if (wcCheckoutUppercase.emailFields && wcCheckoutUppercase.emailFields.includes(fieldName)) {
            // Bind email lowercase conversion events
            $field.on('input.wcuppercase', handleEmailInput);
            $field.on('paste.wcuppercase', handleEmailPaste);
            $field.on('blur.wcuppercase', function() {
                applyLowercase(this);
            });
            
            // Apply to existing value
            if ($field.val()) {
                applyLowercase($field[0]);
            }
        }
        // Check if this is a phone field
        else if (wcCheckoutUppercase.phoneFields && wcCheckoutUppercase.phoneFields.includes(fieldName)) {
            // Bind phone formatting events
            $field.on('input.wcuppercase', handlePhoneInput);
            $field.on('paste.wcuppercase', handlePhonePaste);
            $field.on('blur.wcuppercase', function() {
                applyPhoneFormat(this);
            });
            
            // Apply to existing value
            if ($field.val()) {
                applyPhoneFormat($field[0]);
            }
        } else {
            // Bind uppercase conversion events
            $field.on('input.wcuppercase', handleInput);
            $field.on('paste.wcuppercase', handlePaste);
            $field.on('blur.wcuppercase', function() {
                applyUppercase(this);
            });
            
            // Apply to existing value
            if ($field.val()) {
                applyUppercase($field[0]);
            }
        }
    }
    
    /**
     * Initialize all fields
     */
    function initializeAllFields() {
        // Clear processed fields set to allow re-initialization
        processedFields.clear();
        
        // Initialize uppercase fields
        const fields = wcCheckoutUppercase.fields || [];
        fields.forEach(function(fieldName) {
            initializeField(fieldName);
        });
        
        // Initialize phone fields
        const phoneFields = wcCheckoutUppercase.phoneFields || [];
        phoneFields.forEach(function(fieldName) {
            initializeField(fieldName);
        });
        
        // Initialize email fields
        const emailFields = wcCheckoutUppercase.emailFields || [];
        emailFields.forEach(function(fieldName) {
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
        $(document.body).on('focus', 'input[type="text"], input[type="tel"], input[type="email"], textarea', function() {
            const fieldId = $(this).attr('id');
            if (fieldId) {
                const allFields = (wcCheckoutUppercase.fields || [])
                    .concat(wcCheckoutUppercase.phoneFields || [])
                    .concat(wcCheckoutUppercase.emailFields || []);
                if (allFields.includes(fieldId)) {
                    initializeField(fieldId);
                }
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
                                const allFields = (wcCheckoutUppercase.fields || [])
                                    .concat(wcCheckoutUppercase.phoneFields || [])
                                    .concat(wcCheckoutUppercase.emailFields || []);
                                allFields.forEach(function(fieldName) {
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