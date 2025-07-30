# WooCommerce Checkout Field Uppercase Converter

**Author:** Mike Lvd  
**Version:** 1.0.0  
**Requires:** WordPress 6.8+, WooCommerce 9.0+, PHP 8.0+  
**License:** GPL v2 or later

## Description

Automatically converts all lowercase characters to uppercase in WooCommerce checkout billing and shipping fields. Fully supports both Greek and Latin characters with real-time conversion as users type.

## Features

- ✅ **Real-time uppercase conversion** as users type
- ✅ **Full Greek alphabet support** including accented characters
- ✅ **Latin character support** for all standard alphabets
- ✅ **Server-side validation** ensures data integrity
- ✅ **HPOS compatible** - Works with High-Performance Order Storage
- ✅ **Block-based checkout support** for modern WooCommerce stores
- ✅ **Performance optimized** with debounced input handling
- ✅ **Security hardened** following WordPress best practices

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

### Billing Fields
- First Name
- Last Name
- Company
- Address Line 1
- Address Line 2
- City
- State/County

### Shipping Fields
- First Name
- Last Name
- Company
- Address Line 1
- Address Line 2
- City
- State/County

## Technical Details

### Greek Character Support
The plugin includes full support for the Greek alphabet with proper typography rules:
- Basic letters: α-ω → Α-Ω
- Accented vowels: ά, έ, ή, ί, ό, ύ, ώ → Α, Ε, Η, Ι, Ο, Υ, Ω (accents removed)
- Special characters: ς → Σ (final sigma conversion)
- Follows Greek typography convention: accents are omitted in all-caps text

Examples:
- Μιχάλης → ΜΙΧΑΛΗΣ (not ΜΙΧΆΛΗΣ)
- Γιάννης → ΓΙΑΝΝΗΣ (not ΓΙΆΝΝΗΣ)
- Νίκος → ΝΙΚΟΣ (not ΝΊΚΟΣ)

### Performance Optimization
- Client-side conversion uses debounced input handlers (300ms delay)
- Server-side processing uses efficient string transformation
- Supports PHP 8.4 property hooks when available
- Memory-efficient character mapping

### Security Features
- All input is sanitized using WordPress core functions
- UTF-8 encoding validation
- XSS protection through proper escaping
- CSRF protection via WordPress nonces

### Compatibility
- WordPress 6.8+ (with candidate PHP 8.4 support)
- WooCommerce 9.0-9.9
- Works with both classic and block-based checkouts
- HPOS (High-Performance Order Storage) compatible
- Multi-language store compatible

## Hooks and Filters

### Actions
- `woocommerce_checkout_process` - Main processing hook
- `woocommerce_blocks_checkout_update_order_from_request` - Block checkout support

### Filters
- `woocommerce_checkout_posted_data` - AJAX field updates
- `woocommerce_checkout_update_customer_data` - Customer meta updates
- `wc_checkout_uppercase_remove_greek_accents` - Control Greek accent removal (default: true)

### Customization Example

To keep Greek accents in uppercase (not recommended for Greek typography):

```php
add_filter('wc_checkout_uppercase_remove_greek_accents', '__return_false');
```

## Browser Support

- Chrome/Edge: Full support
- Firefox: Full support
- Safari: Full support
- Mobile browsers: Full support with touch-optimized input handling

## Known Limitations

1. Email fields are not converted (emails should remain lowercase)
2. Postal codes are not converted (to maintain format requirements)
3. Phone numbers are not affected

## Troubleshooting

### Characters not converting
1. Ensure PHP `mbstring` extension is enabled
2. Check that your database charset is `utf8mb4`
3. Verify WooCommerce checkout fields haven't been heavily customized

### JavaScript not working
1. Check for JavaScript errors in browser console
2. Ensure jQuery is loaded (WooCommerce dependency)
3. Clear any caching plugins

## Changelog

### 1.0.0 - 2025-06-19
- Initial release
- Full Greek and Latin character support
- Real-time JavaScript conversion
- Server-side validation
- HPOS compatibility
- Block-based checkout support

## Support

For support, feature requests, or bug reports, please visit:
https://github.com/mikelvd/wc-checkout-uppercase

## License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.
```