# Changelog

All notable changes to WooCommerce Checkout Field Uppercase Converter will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.2] - 2025-01-30

### Added
- Smart phone number formatting for Greek numbers
- Automatic country code removal (+30, 0030, 30)
- Readable phone format (XXX XXX XXXX) for both mobile and landline numbers
- Email field lowercase enforcement with sanitization
- Filters for customizing phone formatting behavior
- Support for handling numbers pasted with just "30" prefix

### Enhanced
- Improved cursor position handling for all field types
- Better paste event handling across all fields
- More robust field initialization system

### Fixed
- Phone numbers starting with "30" (without + or 00) now properly formatted
- Email field cursor position when spaces are trimmed

## [1.0.1] - 2025-01-30

### Added
- Comprehensive error handling throughout the plugin
- Retry mechanism for field initialization
- MutationObserver for efficient dynamic field detection
- WeakSet for memory-efficient element tracking
- Cached Transliterator instances for better performance
- Support for ancient Greek characters

### Changed
- Removed country/state fields from processing (they are select fields)
- Replaced 5-second interval with event-driven approach
- Optimized Greek character mapping with regex caching

### Fixed
- Recursive event handling issues
- Cursor position preservation during typing
- Memory leaks from duplicate event bindings
- Field initialization on dynamic checkout updates

### Removed
- Inefficient 5-second polling interval
- Redundant code in process_order_data method

## [1.0.0] - 2025-01-29

### Added
- Initial release
- Real-time uppercase conversion for WooCommerce checkout fields
- Full Greek alphabet support with accent removal
- Latin character support
- Server-side validation
- Client-side JavaScript for instant conversion
- HPOS (High-Performance Order Storage) compatibility
- WooCommerce Blocks checkout support
- WordPress 6.8+ and WooCommerce 9.0+ compatibility
- PHP 8.0+ support with modern coding practices

[1.0.2]: https://github.com/mikelvd/wc-checkout-uppercase/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/mikelvd/wc-checkout-uppercase/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/mikelvd/wc-checkout-uppercase/releases/tag/v1.0.0