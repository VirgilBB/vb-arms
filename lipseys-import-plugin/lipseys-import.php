<?php
/**
 * Plugin Name: Lipsey's Product Importer
 * Plugin URI: https://vb-arms.com
 * Description: Import products from Lipsey's CSV catalog into WooCommerce with image handling and Zen Payments integration
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

// Declare HPOS compatibility before WooCommerce builds its plugin list (required for "compatible" badge).
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Define plugin constants
define('LIPSEYS_IMPORT_VERSION', '1.0.0');
define('LIPSEYS_IMPORT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LIPSEYS_IMPORT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once LIPSEYS_IMPORT_PLUGIN_DIR . 'includes/class-lipseys-csv-processor.php';
require_once LIPSEYS_IMPORT_PLUGIN_DIR . 'includes/class-lipseys-category-mapper.php';
require_once LIPSEYS_IMPORT_PLUGIN_DIR . 'includes/class-lipseys-image-handler.php';
require_once LIPSEYS_IMPORT_PLUGIN_DIR . 'includes/class-lipseys-product-importer.php';
require_once LIPSEYS_IMPORT_PLUGIN_DIR . 'includes/class-lipseys-admin.php';
require_once LIPSEYS_IMPORT_PLUGIN_DIR . 'includes/class-lipseys-api-client.php';
require_once LIPSEYS_IMPORT_PLUGIN_DIR . 'includes/class-lipseys-api-importer.php';
require_once LIPSEYS_IMPORT_PLUGIN_DIR . 'includes/class-lipseys-api-admin.php';
require_once LIPSEYS_IMPORT_PLUGIN_DIR . 'includes/class-lipseys-api-order-handler.php';

if (defined('WP_CLI') && WP_CLI) {
    require_once LIPSEYS_IMPORT_PLUGIN_DIR . 'includes/class-lipseys-cli.php';
}

/**
 * Main plugin class
 */
class Lipseys_Import {
    
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
        // Check if WooCommerce is active
        add_action('plugins_loaded', array($this, 'check_woocommerce'));
        
        // Initialize admin
        if (is_admin()) {
            new Lipseys_Import_Admin();
            new Lipseys_API_Admin();
        }
        // Lipsey's API: submit paid orders when enabled
        if (class_exists('WooCommerce')) {
            new Lipseys_API_Order_Handler();
        }
    }
    
    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
    }

    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p><strong>Lipsey's Import:</strong> WooCommerce is required for this plugin to work. Please install and activate WooCommerce.</p>
        </div>
        <?php
    }
}

// Initialize plugin
function lipseys_import_init() {
    return Lipseys_Import::get_instance();
}
add_action('plugins_loaded', 'lipseys_import_init', 10);
