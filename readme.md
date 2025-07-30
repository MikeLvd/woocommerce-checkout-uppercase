# WooCommerce Checkout Field Uppercase Converter

**Author:** Mike Lvd  
**Version:** 1.0.1  
**Requires:** WordPress 6.8+, WooCommerce 9.0+, PHP 8.0+  
**License:** GPL v2 or later

## Description

Automatically converts all lowercase characters to uppercase in WooCommerce checkout billing and shipping fields. Fully supports both Greek and Latin characters with real-time conversion as users type.

## Features

- ✅ **Real-time uppercase conversion** as users type with intelligent debouncing
- ✅ **Full Greek alphabet support** including accented characters and ancient Greek
- ✅ **Latin character support** for all standard alphabets
- ✅ **Server-side validation** ensures data integrity
- ✅ **HPOS compatible** - Works with High-Performance Order Storage
- ✅ **Block-based checkout support** for modern WooCommerce stores
- ✅ **Performance optimized** with MutationObserver and efficient event handling
- ✅ **Security hardened** following WordPress best practices
- ✅ **Accessibility compliant** with proper cursor management

## Installation

1. Download the plugin files
2. Create the following directory structure:
   ```
woocommerce-checkout-uppercase/
├── woocommerce-checkout-uppercase.php
├── uninstall.php
├── assets/
│   └── js/
│       └── checkout-uppercase.js
└── readme.md
   ```
3. Upload the `woocommerce-checkout-uppercase` folder to `/wp-content/plugins/`
4. Activate the plugin through the 'Plugins' menu in WordPress
5. No configuration needed - it works automatically!

## Fields Affected

### Billing Fields
- First Name
- Last Name
- Company
- Address Line 1
- Address Line 2  
- City

### Shipping Fields
- First Name
- Last Name
- Company
- Address Line 1
- Address Line 2
- City

### Other Fields
- Order Comments / Customer Notes

**Note:** Country and State fields are NOT converted as they are select dropdowns, not text inputs.

## Technical Details

### Greek Character Support
The plugin includes comprehensive support for the Greek alphabet with proper typography rules:
- Basic letters: α-ω → Α-Ω
- Accented vowels: ά, έ, ή, ί, ό, ύ, ώ → Α, Ε, Η, Ι, Ο, Υ, Ω (accents removed)
- Special characters: ς → Σ (final sigma conversion)
- Ancient Greek: Full support for polytonic Greek
- Follows Greek typography convention: accents are omitted in all-caps text

Examples:
- Μιχάλης → ΜΙΧΑΛΗΣ (not ΜΙΧΆΛΗΣ)
- Γιάννης → ΓΙΑΝΝΗΣ (not ΓΙΆΝΝΗΣ)
- Νίκος → ΝΙΚΟΣ (not ΝΊΚΟΣ)
- Ἀθῆναι → ΑΘΗΝΑΙ

### Performance Optimization
- Client-side conversion uses debounced input handlers (300ms delay)
- MutationObserver for efficient dynamic field detection
- WeakSet for memory-efficient element tracking
- Server-side processing uses cached Transliterator instances
- Smart event handling prevents recursive updates
- Optimized Greek character mapping with regex caching

### Security Features
- All input is sanitized using WordPress core functions
- UTF-8 encoding validation with proper fallbacks
- XSS protection through proper escaping
- CSRF protection via WordPress nonces
- No direct database queries
- Strict input type checking

### Compatibility
- WordPress 6.8+ 
- WooCommerce 9.0-9.9
- PHP 8.0+ with enhanced PHP 8.2+ optimizations
- Works with both classic and block-based checkouts
- HPOS (High-Performance Order Storage) compatible
- Multi-language store compatible
- PHP intl extension utilized when available

## Hooks and Filters

### Actions
- `woocommerce_checkout_process` - Main processing hook
- `woocommerce_blocks_checkout_update_order_from_request` - Block checkout support
- `woocommerce_checkout_create_order` - Order data processing

### Filters
- `woocommerce_checkout_posted_data` - AJAX field updates
- `wc_checkout_uppercase_remove_greek_accents` - Control Greek accent removal (default: true)

### Customization Examples

To keep Greek accents in uppercase (not recommended for Greek typography):

```php
add_filter('wc_checkout_uppercase_remove_greek_accents', '__return_false');
```

To add custom fields for processing:

```php
add_filter('wc_checkout_uppercase_fields', function($fields) {
    $fields['billing'][] = 'billing_custom_field';
    return $fields;
});
```

# Browser Support

Chrome/Edge: Full support with optimal performance
Firefox: Full support with optimal performance
Safari: Full support including iOS
Mobile browsers: Full support with touch-optimized input handling

# Performance Metrics

JavaScript bundle size: ~4KB minified
Average processing time: <5ms per field
Memory footprint: Minimal with WeakSet usage
No impact on page load time (async loading)

# Known Limitations

Email fields are not converted (emails must remain lowercase)
Postal codes are not converted (to maintain format requirements)
Phone numbers are not affected
Select dropdowns (Country/State) are not processed

# Troubleshooting
Characters not converting

Ensure PHP mbstring extension is enabled
For best results, install PHP intl extension
Check that your database charset is utf8mb4
Verify WooCommerce checkout fields haven't been heavily customized

# JavaScript not working

Check for JavaScript errors in browser console
Ensure jQuery is loaded (WooCommerce dependency)
Clear any caching plugins and browser cache
Check for conflicts with other checkout field plugins

# Block checkout issues

Ensure you're using WooCommerce 8.3+ for full block support
Check that the block checkout is properly rendered
Look for console errors specific to React/blocks

# Changelog
1.0.1 - 2025-01-30

Improved: Removed country/state fields from processing (select fields)
Enhanced: Better Greek character support with ancient Greek
Performance: Optimized with MutationObserver and WeakSet
Performance: Debounced input handling
Performance: Cached Transliterator instances
Fixed: Recursive event handling issues
Fixed: Cursor position preservation
Added: Comprehensive error handling
Added: Retry mechanism for initialization
Security: Enhanced input validation

1.0.0 - 2025-01-29

Initial release

# Developer Notes
Testing

# Run PHP CodeSniffer
phpcs --standard=WordPress woocommerce-checkout-uppercase.php

# Run PHPStan
phpstan analyse woocommerce-checkout-uppercase.php --level=8

# JavaScript linting
eslint assets/js/checkout-uppercase.js

# Run PHP CodeSniffer
phpcs --standard=WordPress woocommerce-checkout-uppercase.php

# Run PHPStan
phpstan analyse woocommerce-checkout-uppercase.php --level=8

# JavaScript linting
eslint assets/js/checkout-uppercase.js

# Performance Testing
The plugin has been tested with:

1000+ simultaneous field updates
Large Greek texts (Lorem Ipsum style)
Various Unicode edge cases
Mobile devices with slower processors

# Support
For support, feature requests, or bug reports, please visit:
https://github.com/mikelvd/wc-checkout-uppercase

# License
This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.
```