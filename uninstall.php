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

// Clean up any transients created by the plugin
delete_transient('wc_checkout_uppercase_notices');

// Clean up any options if we add them in future versions
$option_names = [
    'wc_checkout_uppercase_version',
    'wc_checkout_uppercase_settings'
];

foreach ($option_names as $option_name) {
    delete_option($option_name);
    delete_site_option($option_name); // For multisite
}

// Clear any scheduled cron jobs if added in future
$timestamp = wp_next_scheduled('wc_checkout_uppercase_cleanup');
if ($timestamp) {
    wp_unschedule_event($timestamp, 'wc_checkout_uppercase_cleanup');
}

// Clear object cache for our plugin
wp_cache_delete('wc_checkout_uppercase_data', 'wc_checkout_uppercase');

// Flush rewrite rules to clean up any custom endpoints
flush_rewrite_rules();

// Clear any cached data related to the plugin
wp_cache_flush();