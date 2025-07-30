# WooCommerce Checkout Field Uppercase Converter

**Author:** Mike Lvd  
**Version:** 1.0.2  
**Requires:** WordPress 6.8+, WooCommerce 9.0+, PHP 8.0+  
**License:** GPL v2 or later

## Description

A comprehensive WooCommerce checkout field formatter that automatically converts text fields to uppercase, formats phone numbers for readability, and ensures email addresses are lowercase. Fully supports both Greek and Latin characters with real-time conversion as users type.

## Features

- ✅ **Real-time uppercase conversion** for text fields with intelligent cursor handling
- ✅ **Full Greek alphabet support** including accented characters and ancient Greek
- ✅ **Latin character support** for all standard alphabets
- ✅ **Smart phone number formatting** for Greek numbers
- ✅ **Automatic country code removal** (+30, 0030, 30)
- ✅ **Readable phone format** (697 123 4567)
- ✅ **Email lowercase enforcement** - Ensures email addresses are always lowercase
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
   ├── assets/
   │   └── js/
   │       └── checkout-uppercase.js
   └── readme.md
   ```
3. Upload the `woocommerce-checkout-uppercase` folder to `/wp-content/plugins/`
4. Activate the plugin through the 'Plugins' menu in WordPress
5. No configuration needed - it works automatically!

## Fields Affected

### Text Fields (Uppercase Conversion)
- **Billing**: First Name, Last Name, Company, Address Line 1, Address Line 2, City
- **Shipping**: First Name, Last Name, Company, Address Line 1, Address Line 2, City
- **Order**: Comments / Customer Notes

### Phone Fields (Formatting Only)
- Billing Phone
- Shipping Phone

### Email Fields (Lowercase Conversion)
- Billing Email

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

### Phone Number Formatting
The plugin intelligently formats Greek phone numbers:
- Removes country codes: +30, 0030, or just 30
- Formats 10-digit numbers as XXX XXX XXXX
- Works with both mobile (6xx) and landline (2xx) numbers
- Handles various input formats seamlessly

Examples:
- +306971234567 → 697 123 4567
- 00306971234567 → 697 123 4567
- 306971234567 → 697 123 4567
- 6971234567 → 697 123 4567

### Email Handling
- Converts all characters to lowercase
- Trims whitespace
- Applies WordPress email sanitization

### Performance Optimization
- Client-side conversion uses immediate event handlers
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
- `wc_checkout_uppercase_format_phones` - Enable/disable phone formatting (default: true)
- `wc_checkout_uppercase_country_codes` - Customize country codes to remove (default: ['+30', '0030'])

### Customization Examples

To keep Greek accents in uppercase (not recommended for Greek typography):
```php
add_filter('wc_checkout_uppercase_remove_greek_accents', '__return_false');
```

To disable phone number formatting:
```php
add_filter('wc_checkout_uppercase_format_phones', '__return_false');
```

To add additional country codes for removal:
```php
add_filter('wc_checkout_uppercase_country_codes', function($codes) {
    $codes[] = '+1';  // USA
    $codes[] = '+44'; // UK
    return $codes;
});
```

To add custom fields for processing:

```php
add_filter('wc_checkout_uppercase_fields', function($fields) {
    $fields['billing'][] = 'billing_custom_field';
    return $fields;
});
```

### Browser Support

Chrome/Edge: Full support with optimal performance
Firefox: Full support with optimal performance
Safari: Full support including iOS
Mobile browsers: Full support with touch-optimized input handling

### Performance Metrics

JavaScript bundle size: ~6KB minified
Average processing time: <5ms per field
Memory footprint: Minimal with WeakSet usage
No impact on page load time (async loading)

### Known Limitations

Country and State fields are not processed (they're select dropdowns)
Postal codes are not converted (to maintain format requirements)
Credit card fields are not affected (for security)
Select dropdowns and radio buttons are not processed

### Troubleshooting
Characters not converting

Ensure PHP mbstring extension is enabled
For best results, install PHP intl extension
Check that your database charset is utf8mb4
Verify WooCommerce checkout fields haven't been heavily customized

### JavaScript not working

Check for JavaScript errors in browser console
Ensure jQuery is loaded (WooCommerce dependency)
Clear any caching plugins and browser cache
Check for conflicts with other checkout field plugins

### Phone formatting issues

Ensure the phone number is entered without spaces initially
Check if custom validation is interfering
Verify the number follows Greek format (10 digits)

### Block checkout issues

Ensure you're using WooCommerce 8.3+ for full block support
Check that the block checkout is properly rendered
Look for console errors specific to React/blocks

### Frequently Asked Questions
Q: Can I use this for other countries besides Greece?
A: Yes! The uppercase conversion works for all countries. Phone formatting is currently optimized for Greece, but you can extend it using the provided filters.
Q: Will this work with my custom checkout fields?
A: The plugin processes standard WooCommerce fields. For custom fields, use the wc_checkout_uppercase_fields filter.
Q: Does it work with multi-step checkout plugins?
A: Yes, the plugin uses MutationObserver to detect dynamically loaded fields.
Q: Is the plugin GDPR compliant?
A: Yes, the plugin only formats data and doesn't store or transmit any personal information.

### Changelog

See CHANGELOG.md for detailed version history.
Support
For support, feature requests, or bug reports, please visit:
https://github.com/mikelvd/wc-checkout-uppercase

### Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

### License
This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.
```