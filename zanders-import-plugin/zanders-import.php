<?php
/**
 * Plugin Name: Zanders Inventory Importer
 * Plugin URI: https://vb-arms.com
 * Description: Import products from Zanders inventory via FTP with automatic image handling and Zen Payments integration
 * Version: 1.0.0
 * Author: VB Arms
 * Author URI: https://vb-arms.com
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('ZANDERS_IMPORT_VERSION', '1.0.0');
define('ZANDERS_IMPORT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ZANDERS_IMPORT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once ZANDERS_IMPORT_PLUGIN_DIR . 'includes/class-zanders-ftp-handler.php';
require_once ZANDERS_IMPORT_PLUGIN_DIR . 'includes/class-zanders-curl-ftp-handler.php';
require_once ZANDERS_IMPORT_PLUGIN_DIR . 'includes/class-zanders-ftp-factory.php';
require_once ZANDERS_IMPORT_PLUGIN_DIR . 'includes/class-zanders-csv-processor.php';
require_once ZANDERS_IMPORT_PLUGIN_DIR . 'includes/class-zanders-api.php';
require_once ZANDERS_IMPORT_PLUGIN_DIR . 'includes/class-zanders-xml-processor.php';
require_once ZANDERS_IMPORT_PLUGIN_DIR . 'includes/class-zanders-category-mapper.php';
require_once ZANDERS_IMPORT_PLUGIN_DIR . 'includes/class-zanders-image-handler.php';
require_once ZANDERS_IMPORT_PLUGIN_DIR . 'includes/class-zanders-product-importer.php';
require_once ZANDERS_IMPORT_PLUGIN_DIR . 'includes/class-zanders-admin.php';

/**
 * Main plugin class
 */
class Zanders_Import {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Initialize REST API immediately (hooks into rest_api_init in constructor)
        // This ensures routes are registered when REST API initializes
        new Zanders_Import_API();
        
        // Check if WooCommerce is active
        add_action('plugins_loaded', array($this, 'check_woocommerce'), 20);
        
        // Declare WooCommerce compatibility
        add_action('before_woocommerce_init', array($this, 'declare_woocommerce_compatibility'));
    }
    
    /**
     * Declare WooCommerce compatibility
     */
    public function declare_woocommerce_compatibility() {
        // Declare compatibility with WooCommerce features (HPOS, Cart/Checkout Blocks)
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
        }
        
        // For older WooCommerce versions, use legacy method
        if (class_exists('WC_Install')) {
            // Plugin is compatible with all WooCommerce features
        }
    }
    
    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Initialize admin only if WooCommerce is active
        if (is_admin()) {
            new Zanders_Import_Admin();
        }
        
        // REST API is initialized via init_rest_api() method above
    }
    
    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p><strong>Zanders Import:</strong> WooCommerce is required for this plugin to work. Please install and activate WooCommerce.</p>
        </div>
        <?php
    }
}

// Initialize plugin
function zanders_import_init() {
    return Zanders_Import::get_instance();
}
add_action('plugins_loaded', 'zanders_import_init', 15);

/**
 * Flush rewrite rules on plugin activation
 */
function zanders_import_activate() {
    // Flush rewrite rules to register REST API routes
    flush_rewrite_rules();
    
    // Set a flag to flush again on next load (in case activation hook runs too early)
    update_option('zanders_import_flush_rewrite_rules', true);
}
register_activation_hook(__FILE__, 'zanders_import_activate');

/**
 * Flush rewrite rules after plugin loads (if needed)
 */
function zanders_import_maybe_flush_rewrite_rules() {
    if (get_option('zanders_import_flush_rewrite_rules')) {
        flush_rewrite_rules();
        delete_option('zanders_import_flush_rewrite_rules');
    }
}
add_action('init', 'zanders_import_maybe_flush_rewrite_rules', 999);

/**
 * Auto-flush rewrite rules when REST API routes are registered
 */
function zanders_import_auto_flush_on_route_registration() {
    // Only flush once per day to avoid performance issues
    $last_flush = get_option('zanders_import_last_rewrite_flush', 0);
    $one_day_ago = time() - (24 * 60 * 60);
    
    if ($last_flush < $one_day_ago) {
        flush_rewrite_rules(false); // false = don't hard flush, just update
        update_option('zanders_import_last_rewrite_flush', time());
    }
}
add_action('rest_api_init', 'zanders_import_auto_flush_on_route_registration', 999);
