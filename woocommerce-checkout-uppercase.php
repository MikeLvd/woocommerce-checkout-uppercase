<?php
/**
 * Plugin Name: WooCommerce Checkout Field Uppercase Converter
 * Plugin URI: https://github.com/mikelvd/wc-checkout-uppercase
 * Description: Automatically converts all lowercase characters to uppercase in WooCommerce checkout billing and shipping fields. Supports both Greek and Latin characters.
 * Version: 1.0.0
 * Requires at least: 6.8
 * Requires PHP: 8.0
 * Author: Mike Lvd
 * Author URI: https://mikelvd.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wc-checkout-uppercase
 * Domain Path: /languages
 * WC requires at least: 9.0
 * WC tested up to: 9.9
 */

namespace WCCheckoutUppercase;

// Prevent direct access
defined('ABSPATH') || exit;

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

/**
 * Main plugin class
 */
final class CheckoutFieldUppercase {
    
    /**
     * Plugin version
     */
    const VERSION = '1.0.0';
    
    /**
     * Plugin instance
     */
    private static ?self $instance = null;
    
    /**
     * Fields to process (only text inputs, not selects)
     */
    private array $fields_to_process = [
        'billing' => [
            'billing_first_name',
            'billing_last_name',
            'billing_country',
            'billing_address_1',
            'billing_city',
            'billing_state'
        ],
        'shipping' => [
            'shipping_first_name',
            'shipping_last_name',
            'shipping_country',
            'shipping_address_1',
            'shipping_city',
            'shipping_state'
        ],
        'order' => [
            'order_comments'
        ]
    ];
    
    /**
     * Greek to uppercase mapping (without accents as per Greek typography rules)
     */
    private array $greek_uppercase_map = [
        'α' => 'Α', 'β' => 'Β', 'γ' => 'Γ', 'δ' => 'Δ', 'ε' => 'Ε',
        'ζ' => 'Ζ', 'η' => 'Η', 'θ' => 'Θ', 'ι' => 'Ι', 'κ' => 'Κ',
        'λ' => 'Λ', 'μ' => 'Μ', 'ν' => 'Ν', 'ξ' => 'Ξ', 'ο' => 'Ο',
        'π' => 'Π', 'ρ' => 'Ρ', 'σ' => 'Σ', 'ς' => 'Σ', 'τ' => 'Τ',
        'υ' => 'Υ', 'φ' => 'Φ', 'χ' => 'Χ', 'ψ' => 'Ψ', 'ω' => 'Ω',
        // Accented vowels convert to non-accented uppercase
        'ά' => 'Α', 'έ' => 'Ε', 'ή' => 'Η', 'ί' => 'Ι', 'ό' => 'Ο',
        'ύ' => 'Υ', 'ώ' => 'Ω', 'ΐ' => 'Ι', 'ΰ' => 'Υ',
        // Handle uppercase accented letters (remove accents)
        'Ά' => 'Α', 'Έ' => 'Ε', 'Ή' => 'Η', 'Ί' => 'Ι', 'Ό' => 'Ο',
        'Ύ' => 'Υ', 'Ώ' => 'Ω'
    ];
    
    /**
     * Get plugin instance
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize plugin hooks
     */
    private function init_hooks(): void {
        // Process checkout fields on submission
        add_action('woocommerce_checkout_process', [$this, 'process_checkout_fields'], 5);
        
        // Process fields during AJAX update
        add_filter('woocommerce_checkout_posted_data', [$this, 'filter_posted_data'], 10, 1);
        
        // Add JavaScript for real-time conversion
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // Process block-based checkout fields
        add_filter('woocommerce_blocks_checkout_update_order_from_request', [$this, 'process_block_checkout_fields'], 10, 2);
        
        // Add HPOS compatibility
        add_action('before_woocommerce_init', [$this, 'declare_hpos_compatibility']);
        
        // Process order meta data
        add_action('woocommerce_checkout_create_order', [$this, 'process_order_data'], 10, 2);
        
        // Convert country names to uppercase
        add_filter('woocommerce_countries', [$this, 'convert_countries_to_uppercase'], 999);
        
        // Convert state names to uppercase
        add_filter('woocommerce_states', [$this, 'convert_states_to_uppercase'], 999);
    }
    
    /**
     * Declare HPOS compatibility
     */
    public function declare_hpos_compatibility(): void {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                __FILE__,
                true
            );
        }
    }
    
    /**
     * Convert string to uppercase with Greek support (removes accents)
     */
    private function to_uppercase(string $text): string {
        if (empty($text)) {
            return $text;
        }
        
        /**
         * Filter to control whether Greek accents should be removed in uppercase
         * 
         * @param bool $remove_accents Whether to remove accents (default: true)
         * @return bool
         */
        $remove_greek_accents = apply_filters('wc_checkout_uppercase_remove_greek_accents', true);
        
        // Apply Greek character conversion
        if ($remove_greek_accents) {
            // Use map that removes accents
            $text = strtr($text, $this->greek_uppercase_map);
        }
        
        // Use Transliterator if available (PHP intl extension)
        if (class_exists('\Transliterator')) {
            if ($remove_greek_accents) {
                // First remove diacritics/accents
                $removeAccents = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');
                if ($removeAccents !== null) {
                    $text = $removeAccents->transliterate($text);
                }
            }
            
            // Then convert to uppercase
            $toUpper = \Transliterator::create('Any-Upper');
            if ($toUpper !== null) {
                $result = $toUpper->transliterate($text);
                if ($result !== false) {
                    return $result;
                }
            }
        }
        
        // Use mb_strtoupper if available
        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($text, 'UTF-8');
        }
        
        // Fallback: Convert Latin characters
        return strtoupper($text);
    }
    
    /**
     * Sanitize and convert field value
     */
    private function sanitize_and_convert(mixed $value): string {
        if (!is_string($value)) {
            return '';
        }
        
        // Sanitize input
        $value = wp_strip_all_tags($value);
        $value = trim($value);
        
        // Check if the string is UTF-8
        if (!seems_utf8($value)) {
            $value = utf8_encode($value);
        }
        
        // Convert to uppercase
        return $this->to_uppercase($value);
    }
    
    /**
     * Process checkout fields
     */
    public function process_checkout_fields(): void {
        if (!is_checkout() || !WC()->checkout()) {
            return;
        }
        
        // Process billing fields
        foreach ($this->fields_to_process['billing'] as $field) {
            if (isset($_POST[$field])) {
                $_POST[$field] = $this->sanitize_and_convert($_POST[$field]);
            }
        }
        
        // Process shipping fields if ship to different address is checked
        if (!empty($_POST['ship_to_different_address'])) {
            foreach ($this->fields_to_process['shipping'] as $field) {
                if (isset($_POST[$field])) {
                    $_POST[$field] = $this->sanitize_and_convert($_POST[$field]);
                }
            }
        }
        
        // Process order fields
        if (isset($this->fields_to_process['order'])) {
            foreach ($this->fields_to_process['order'] as $field) {
                if (isset($_POST[$field])) {
                    $_POST[$field] = $this->sanitize_and_convert($_POST[$field]);
                }
            }
        }
    }
    
    /**
     * Filter posted data during AJAX updates
     */
    public function filter_posted_data(array $data): array {
        // Process all relevant fields
        $all_fields = array_merge(
            $this->fields_to_process['billing'],
            $this->fields_to_process['shipping'],
            $this->fields_to_process['order'] ?? []
        );
        
        foreach ($all_fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = $this->sanitize_and_convert($data[$field]);
            }
        }
        
        return $data;
    }
    
    /**
     * Process order data before saving
     */
    public function process_order_data(\WC_Order $order, array $data): void {
        // Process billing first name
        $billing_first_name = $order->get_billing_first_name();
        if (!empty($billing_first_name)) {
            $order->set_billing_first_name($this->sanitize_and_convert($billing_first_name));
        }
        
        // Process billing last name
        $billing_last_name = $order->get_billing_last_name();
        if (!empty($billing_last_name)) {
            $order->set_billing_last_name($this->sanitize_and_convert($billing_last_name));
        }
        
        // Process billing country
        $billing_country = $order->get_billing_country();
        if (!empty($billing_country)) {
            $order->set_billing_country($this->sanitize_and_convert($billing_country));
        }
        
        // Process billing address 1
        $billing_address_1 = $order->get_billing_address_1();
        if (!empty($billing_address_1)) {
            $order->set_billing_address_1($this->sanitize_and_convert($billing_address_1));
        }
        
        // Process billing city
        $billing_city = $order->get_billing_city();
        if (!empty($billing_city)) {
            $order->set_billing_city($this->sanitize_and_convert($billing_city));
        }
        
        // Process billing state
        $billing_state = $order->get_billing_state();
        if (!empty($billing_state)) {
            $order->set_billing_state($this->sanitize_and_convert($billing_state));
        }
        
        // Process shipping first name
        $shipping_first_name = $order->get_shipping_first_name();
        if (!empty($shipping_first_name)) {
            $order->set_shipping_first_name($this->sanitize_and_convert($shipping_first_name));
        }
        
        // Process shipping last name
        $shipping_last_name = $order->get_shipping_last_name();
        if (!empty($shipping_last_name)) {
            $order->set_shipping_last_name($this->sanitize_and_convert($shipping_last_name));
        }
        
        // Process shipping country
        $shipping_country = $order->get_shipping_country();
        if (!empty($shipping_country)) {
            $order->set_shipping_country($this->sanitize_and_convert($shipping_country));
        }
        
        // Process shipping address 1
        $shipping_address_1 = $order->get_shipping_address_1();
        if (!empty($shipping_address_1)) {
            $order->set_shipping_address_1($this->sanitize_and_convert($shipping_address_1));
        }
        
        // Process shipping city
        $shipping_city = $order->get_shipping_city();
        if (!empty($shipping_city)) {
            $order->set_shipping_city($this->sanitize_and_convert($shipping_city));
        }
        
        // Process shipping state
        $shipping_state = $order->get_shipping_state();
        if (!empty($shipping_state)) {
            $order->set_shipping_state($this->sanitize_and_convert($shipping_state));
        }
        
        // Process order comments (customer note)
        $customer_note = $order->get_customer_note();
        if (!empty($customer_note)) {
            $order->set_customer_note($this->sanitize_and_convert($customer_note));
        }
    }
    
    /**
     * Convert all country names to uppercase
     */
    public function convert_countries_to_uppercase(array $countries): array {
        foreach ($countries as $code => $name) {
            $countries[$code] = $this->to_uppercase($name);
        }
        return $countries;
    }
    
    /**
     * Convert all state names to uppercase
     */
    public function convert_states_to_uppercase(array $states): array {
        foreach ($states as $country_code => $country_states) {
            if (is_array($country_states)) {
                foreach ($country_states as $state_code => $state_name) {
                    $states[$country_code][$state_code] = $this->to_uppercase($state_name);
                }
            }
        }
        return $states;
    }
    
    /**
     * Process block-based checkout fields
     */
    public function process_block_checkout_fields(\WC_Order $order, \WP_REST_Request $request): void {
        $billing_data = $request->get_param('billing_address') ?? [];
        $shipping_data = $request->get_param('shipping_address') ?? [];
        
        // Process billing fields
        foreach ($billing_data as $key => $value) {
            if (in_array('billing_' . $key, $this->fields_to_process['billing'])) {
                $order->set_billing_prop($key, $this->sanitize_and_convert($value));
            }
        }
        
        // Process shipping fields
        foreach ($shipping_data as $key => $value) {
            if (in_array('shipping_' . $key, $this->fields_to_process['shipping'])) {
                $order->set_shipping_prop($key, $this->sanitize_and_convert($value));
            }
        }
    }
    
    /**
     * Enqueue scripts for real-time conversion
     */
    public function enqueue_scripts(): void {
        if (!is_checkout()) {
            return;
        }
        
        wp_enqueue_script(
            'wc-checkout-uppercase',
            plugin_dir_url(__FILE__) . 'assets/js/checkout-uppercase.js',
            ['jquery'],
            self::VERSION,
            true
        );
        
        // Pass field configuration to JavaScript
        wp_localize_script('wc-checkout-uppercase', 'wcCheckoutUppercase', [
            'fields' => array_merge(
                $this->fields_to_process['billing'],
                $this->fields_to_process['shipping'],
                $this->fields_to_process['order'] ?? []
            ),
            'greekMap' => $this->greek_uppercase_map,
            'nonce' => wp_create_nonce('wc-checkout-uppercase')
        ]);
    }
}

// Initialize plugin
add_action('plugins_loaded', function() {
    CheckoutFieldUppercase::get_instance();
}, 10);

// Activation hook
register_activation_hook(__FILE__, function() {
    // Check PHP version
    if (version_compare(PHP_VERSION, '8.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('This plugin requires PHP 8.0 or higher.', 'wc-checkout-uppercase'),
            esc_html__('Plugin Activation Error', 'wc-checkout-uppercase'),
            ['response' => 200, 'back_link' => true]
        );
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('This plugin requires WooCommerce to be installed and active.', 'wc-checkout-uppercase'),
            esc_html__('Plugin Activation Error', 'wc-checkout-uppercase'),
            ['response' => 200, 'back_link' => true]
        );
    }
});