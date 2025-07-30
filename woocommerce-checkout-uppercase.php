<?php
/**
 * Plugin Name: WooCommerce Checkout Field Uppercase Converter
 * Plugin URI: https://github.com/mikelvd/wc-checkout-uppercase
 * Description: Automatically converts all lowercase characters to uppercase in WooCommerce checkout billing and shipping fields. Supports both Greek and Latin characters.
 * Version: 1.0.1
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
    private const VERSION = '1.0.1';
    
    /**
     * Plugin instance
     */
    private static ?self $instance = null;
    
    /**
     * Transliterator instance cache
     */
    private static ?\Transliterator $transliterator = null;
    
    /**
     * Fields to process (only text inputs, not selects)
     * Removed country and state as they are select fields
     */
    private array $fields_to_process = [
        'billing' => [
            'billing_first_name',
            'billing_last_name',
            'billing_company',
            'billing_address_1',
            'billing_address_2',
            'billing_city'
        ],
        'shipping' => [
            'shipping_first_name',
            'shipping_last_name',
            'shipping_company',
            'shipping_address_1',
            'shipping_address_2',
            'shipping_city'
        ],
        'order' => [
            'order_comments'
        ]
    ];
    
    /**
     * Comprehensive Greek to uppercase mapping (without accents as per Greek typography rules)
     */
    private array $greek_uppercase_map = [
        // Basic lowercase to uppercase
        'α' => 'Α', 'β' => 'Β', 'γ' => 'Γ', 'δ' => 'Δ', 'ε' => 'Ε',
        'ζ' => 'Ζ', 'η' => 'Η', 'θ' => 'Θ', 'ι' => 'Ι', 'κ' => 'Κ',
        'λ' => 'Λ', 'μ' => 'Μ', 'ν' => 'Ν', 'ξ' => 'Ξ', 'ο' => 'Ο',
        'π' => 'Π', 'ρ' => 'Ρ', 'σ' => 'Σ', 'ς' => 'Σ', 'τ' => 'Τ',
        'υ' => 'Υ', 'φ' => 'Φ', 'χ' => 'Χ', 'ψ' => 'Ψ', 'ω' => 'Ω',
        
        // Accented vowels convert to non-accented uppercase
        'ά' => 'Α', 'έ' => 'Ε', 'ή' => 'Η', 'ί' => 'Ι', 'ό' => 'Ο',
        'ύ' => 'Υ', 'ώ' => 'Ω', 'ΐ' => 'Ι', 'ΰ' => 'Υ',
        'ἀ' => 'Α', 'ἁ' => 'Α', 'ἂ' => 'Α', 'ἃ' => 'Α', 'ἄ' => 'Α', 'ἅ' => 'Α',
        'ἐ' => 'Ε', 'ἑ' => 'Ε', 'ἒ' => 'Ε', 'ἓ' => 'Ε', 'ἔ' => 'Ε', 'ἕ' => 'Ε',
        'ἠ' => 'Η', 'ἡ' => 'Η', 'ἢ' => 'Η', 'ἣ' => 'Η', 'ἤ' => 'Η', 'ἥ' => 'Η',
        'ἰ' => 'Ι', 'ἱ' => 'Ι', 'ἲ' => 'Ι', 'ἳ' => 'Ι', 'ἴ' => 'Ι', 'ἵ' => 'Ι',
        'ὀ' => 'Ο', 'ὁ' => 'Ο', 'ὂ' => 'Ο', 'ὃ' => 'Ο', 'ὄ' => 'Ο', 'ὅ' => 'Ο',
        'ὐ' => 'Υ', 'ὑ' => 'Υ', 'ὒ' => 'Υ', 'ὓ' => 'Υ', 'ὔ' => 'Υ', 'ὕ' => 'Υ',
        'ὠ' => 'Ω', 'ὡ' => 'Ω', 'ὢ' => 'Ω', 'ὣ' => 'Ω', 'ὤ' => 'Ω', 'ὥ' => 'Ω',
        
        // Handle uppercase accented letters (remove accents)
        'Ά' => 'Α', 'Έ' => 'Ε', 'Ή' => 'Η', 'Ί' => 'Ι', 'Ό' => 'Ο',
        'Ύ' => 'Υ', 'Ώ' => 'Ω', 'Ϊ' => 'Ι', 'Ϋ' => 'Υ'
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
     * Get or create Transliterator instance
     */
    private function get_transliterator(): ?\Transliterator {
        if (self::$transliterator === null && class_exists('\Transliterator')) {
            $remove_greek_accents = apply_filters('wc_checkout_uppercase_remove_greek_accents', true);
            
            if ($remove_greek_accents) {
                // Create a compound transliterator that removes accents and converts to uppercase
                self::$transliterator = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC; Any-Upper');
            } else {
                // Just uppercase
                self::$transliterator = \Transliterator::create('Any-Upper');
            }
        }
        
        return self::$transliterator;
    }
    
    /**
     * Convert string to uppercase with Greek support (removes accents)
     */
    private function to_uppercase(string $text): string {
        if (empty($text)) {
            return $text;
        }
        
        // Filter to control whether Greek accents should be removed
        $remove_greek_accents = apply_filters('wc_checkout_uppercase_remove_greek_accents', true);
        
        // Try Transliterator first (most efficient for Unicode)
        $transliterator = $this->get_transliterator();
        if ($transliterator !== null) {
            $result = $transliterator->transliterate($text);
            if ($result !== false) {
                return $result;
            }
        }
        
        // Fallback: Apply Greek character conversion manually
        if ($remove_greek_accents) {
            $text = strtr($text, $this->greek_uppercase_map);
        }
        
        // Use mb_strtoupper if available
        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($text, 'UTF-8');
        }
        
        // Last resort: strtoupper (only works for ASCII)
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
            $value = mb_convert_encoding($value, 'UTF-8', mb_detect_encoding($value));
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
        foreach ($this->fields_to_process['order'] as $field) {
            if (isset($_POST[$field])) {
                $_POST[$field] = $this->sanitize_and_convert($_POST[$field]);
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
            $this->fields_to_process['order']
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
        // Define field mappings for better maintainability
        $field_mappings = [
            'billing' => [
                'first_name' => 'billing_first_name',
                'last_name' => 'billing_last_name',
                'company' => 'billing_company',
                'address_1' => 'billing_address_1',
                'address_2' => 'billing_address_2',
                'city' => 'billing_city'
            ],
            'shipping' => [
                'first_name' => 'shipping_first_name',
                'last_name' => 'shipping_last_name',
                'company' => 'shipping_company',
                'address_1' => 'shipping_address_1',
                'address_2' => 'shipping_address_2',
                'city' => 'shipping_city'
            ]
        ];
        
        // Process billing fields
        foreach ($field_mappings['billing'] as $prop => $field_name) {
            if (in_array($field_name, $this->fields_to_process['billing'])) {
                $getter = 'get_billing_' . $prop;
                $setter = 'set_billing_' . $prop;
                
                if (method_exists($order, $getter) && method_exists($order, $setter)) {
                    $value = $order->$getter();
                    if (!empty($value)) {
                        $order->$setter($this->sanitize_and_convert($value));
                    }
                }
            }
        }
        
        // Process shipping fields
        foreach ($field_mappings['shipping'] as $prop => $field_name) {
            if (in_array($field_name, $this->fields_to_process['shipping'])) {
                $getter = 'get_shipping_' . $prop;
                $setter = 'set_shipping_' . $prop;
                
                if (method_exists($order, $getter) && method_exists($order, $setter)) {
                    $value = $order->$getter();
                    if (!empty($value)) {
                        $order->$setter($this->sanitize_and_convert($value));
                    }
                }
            }
        }
        
        // Process order comments (customer note)
        $customer_note = $order->get_customer_note();
        if (!empty($customer_note)) {
            $order->set_customer_note($this->sanitize_and_convert($customer_note));
        }
    }
    
    /**
     * Process block-based checkout fields
     */
    public function process_block_checkout_fields(\WC_Order $order, \WP_REST_Request $request): void {
        $billing_data = $request->get_param('billing_address') ?? [];
        $shipping_data = $request->get_param('shipping_address') ?? [];
        
        // Process billing fields
        foreach ($billing_data as $key => $value) {
            $field_name = 'billing_' . $key;
            if (in_array($field_name, $this->fields_to_process['billing']) && !empty($value)) {
                $setter = 'set_billing_' . $key;
                if (method_exists($order, $setter)) {
                    $order->$setter($this->sanitize_and_convert($value));
                }
            }
        }
        
        // Process shipping fields
        foreach ($shipping_data as $key => $value) {
            $field_name = 'shipping_' . $key;
            if (in_array($field_name, $this->fields_to_process['shipping']) && !empty($value)) {
                $setter = 'set_shipping_' . $key;
                if (method_exists($order, $setter)) {
                    $order->$setter($this->sanitize_and_convert($value));
                }
            }
        }
        
        // Process order comments if provided
        $order_comments = $request->get_param('customer_note');
        if (!empty($order_comments)) {
            $order->set_customer_note($this->sanitize_and_convert($order_comments));
        }
    }
    
    /**
     * Enqueue scripts for real-time conversion
     */
    public function enqueue_scripts(): void {
        if (!is_checkout()) {
            return;
        }
        
        $asset_file = plugin_dir_path(__FILE__) . 'assets/js/checkout-uppercase.js';
        $asset_version = file_exists($asset_file) ? filemtime($asset_file) : self::VERSION;
        
        wp_enqueue_script(
            'wc-checkout-uppercase',
            plugin_dir_url(__FILE__) . 'assets/js/checkout-uppercase.js',
            ['jquery'],
            $asset_version,
            true
        );
        
        // Pass field configuration to JavaScript
        wp_localize_script('wc-checkout-uppercase', 'wcCheckoutUppercase', [
            'fields' => array_merge(
                $this->fields_to_process['billing'],
                $this->fields_to_process['shipping'],
                $this->fields_to_process['order']
            ),
            'greekMap' => $this->greek_uppercase_map,
            'nonce' => wp_create_nonce('wc-checkout-uppercase'),
            'isBlockCheckout' => has_block('woocommerce/checkout')
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
    
    // Flush rewrite rules on activation
    flush_rewrite_rules();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Clean up any transients
    delete_transient('wc_checkout_uppercase_notices');
    
    // Flush rewrite rules
    flush_rewrite_rules();
});