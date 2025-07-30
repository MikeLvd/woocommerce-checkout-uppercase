<?php
/**
 * Uninstall WooCommerce Checkout Field Uppercase Converter
 *
 * @package WCCheckoutUppercase
 * @since 1.0.0
 */

// Exit if accessed directly or not called from WordPress uninstall
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clean up any transients if we add them in future versions
// Currently, this plugin doesn't store any options or custom tables

// Clear any cached data related to the plugin
wp_cache_flush();