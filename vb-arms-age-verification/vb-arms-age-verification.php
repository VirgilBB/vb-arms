<?php
/**
 * Plugin Name: VB Arms Age Verification
 * Plugin URI: https://vb-arms.com
 * Description: Mandatory 18+ age verification disclaimer for VB Arms website
 * Version: 1.0.0
 * Author: VB Arms
 * Author URI: https://vb-arms.com
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('VB_ARMS_AGE_VERIFY_VERSION', '1.0.0');
define('VB_ARMS_AGE_VERIFY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VB_ARMS_AGE_VERIFY_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main Age Verification Class
 */
class VB_Arms_Age_Verification {
    
    private static $instance = null;
    private $cookie_name = 'vb_arms_age_verified';
    private $cookie_duration_days = 1; // Show again after 24 hours (same browser). New browser = verify again.
    private $force_show_every_time = false;
    
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
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Add age verification modal to footer
        add_action('wp_footer', array($this, 'render_age_verification_modal'));
        
        // Handle AJAX verification
        add_action('wp_ajax_vb_arms_verify_age', array($this, 'handle_age_verification'));
        add_action('wp_ajax_nopriv_vb_arms_verify_age', array($this, 'handle_age_verification'));
        
        // Add admin menu for easy editing
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'register_settings'));
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'options-general.php',
            'Age Verification Settings',
            'Age Verification',
            'manage_options',
            'vb-arms-age-verification',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('vb_arms_age_verify_settings', 'vb_arms_age_verify_title');
        register_setting('vb_arms_age_verify_settings', 'vb_arms_age_verify_disclaimer');
        register_setting('vb_arms_age_verify_settings', 'vb_arms_age_verify_question');
        register_setting('vb_arms_age_verify_settings', 'vb_arms_age_verify_yes_text');
        register_setting('vb_arms_age_verify_settings', 'vb_arms_age_verify_no_text');
        register_setting('vb_arms_age_verify_settings', 'vb_arms_age_verify_custom_css');
        // Visual settings
        register_setting('vb_arms_age_verify_settings', 'vb_arms_age_verify_button_width');
        register_setting('vb_arms_age_verify_settings', 'vb_arms_age_verify_logo_size');
        register_setting('vb_arms_age_verify_settings', 'vb_arms_age_verify_button_padding');
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>Age Verification Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('vb_arms_age_verify_settings'); ?>
                <?php do_settings_sections('vb_arms_age_verify_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="vb_arms_age_verify_title">Modal Title</label></th>
                        <td>
                            <input type="text" id="vb_arms_age_verify_title" name="vb_arms_age_verify_title" 
                                   value="<?php echo esc_attr(get_option('vb_arms_age_verify_title', 'VERIFY YOUR AGE')); ?>" 
                                   class="regular-text" />
                            <p class="description">The heading text at the top of the modal</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vb_arms_age_verify_disclaimer">Disclaimer Text</label></th>
                        <td>
                            <textarea id="vb_arms_age_verify_disclaimer" name="vb_arms_age_verify_disclaimer" 
                                      rows="4" class="large-text"><?php echo esc_textarea(get_option('vb_arms_age_verify_disclaimer', "Certain states prohibit advertising or marketing of firearms, ammunition, and related firearm components to minors. We'd like to ensure that you're 18 years of age or older before allowing you to shop our site.")); ?></textarea>
                            <p class="description">The legal disclaimer text</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vb_arms_age_verify_question">Question Text</label></th>
                        <td>
                            <input type="text" id="vb_arms_age_verify_question" name="vb_arms_age_verify_question" 
                                   value="<?php echo esc_attr(get_option('vb_arms_age_verify_question', 'Are you 18 years of age or older?')); ?>" 
                                   class="regular-text" />
                            <p class="description">The question displayed to users</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vb_arms_age_verify_yes_text">Yes Button Text</label></th>
                        <td>
                            <input type="text" id="vb_arms_age_verify_yes_text" name="vb_arms_age_verify_yes_text" 
                                   value="<?php echo esc_attr(get_option('vb_arms_age_verify_yes_text', 'Yes')); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vb_arms_age_verify_no_text">No Button Text</label></th>
                        <td>
                            <input type="text" id="vb_arms_age_verify_no_text" name="vb_arms_age_verify_no_text" 
                                   value="<?php echo esc_attr(get_option('vb_arms_age_verify_no_text', 'No')); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            </form>
            
            <div class="card" style="margin-top: 20px; padding: 20px; background: #fff; border: 1px solid #ccc;">
                <h2>Visual Style Settings</h2>
                <p><strong>Easy options - just pick from dropdowns, no code needed!</strong></p>
                
                <form method="post" action="options.php">
                    <?php settings_fields('vb_arms_age_verify_settings'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="vb_arms_age_verify_button_width">Button Width</label></th>
                            <td>
                                <select id="vb_arms_age_verify_button_width" name="vb_arms_age_verify_button_width" style="width: 300px;">
                                    <option value="90" <?php selected(get_option('vb_arms_age_verify_button_width', '90'), '90'); ?>>Thin (90px) - Current</option>
                                    <option value="120" <?php selected(get_option('vb_arms_age_verify_button_width', '90'), '120'); ?>>Medium (120px)</option>
                                    <option value="150" <?php selected(get_option('vb_arms_age_verify_button_width', '90'), '150'); ?>>Wide (150px)</option>
                                    <option value="180" <?php selected(get_option('vb_arms_age_verify_button_width', '90'), '180'); ?>>Extra Wide (180px)</option>
                                </select>
                                <p class="description">How wide should the Yes/No buttons be?</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="vb_arms_age_verify_logo_size">Logo Size</label></th>
                            <td>
                                <select id="vb_arms_age_verify_logo_size" name="vb_arms_age_verify_logo_size" style="width: 300px;">
                                    <option value="150" <?php selected(get_option('vb_arms_age_verify_logo_size', '200'), '150'); ?>>Small (150px)</option>
                                    <option value="200" <?php selected(get_option('vb_arms_age_verify_logo_size', '200'), '200'); ?>>Medium (200px) - Current</option>
                                    <option value="250" <?php selected(get_option('vb_arms_age_verify_logo_size', '200'), '250'); ?>>Large (250px)</option>
                                    <option value="300" <?php selected(get_option('vb_arms_age_verify_logo_size', '200'), '300'); ?>>Extra Large (300px)</option>
                                </select>
                                <p class="description">How big should the logo be?</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="vb_arms_age_verify_button_padding">Button Thickness</label></th>
                            <td>
                                <select id="vb_arms_age_verify_button_padding" name="vb_arms_age_verify_button_padding" style="width: 300px;">
                                    <option value="thin" <?php selected(get_option('vb_arms_age_verify_button_padding', 'normal'), 'thin'); ?>>Thin</option>
                                    <option value="normal" <?php selected(get_option('vb_arms_age_verify_button_padding', 'normal'), 'normal'); ?>>Normal - Current</option>
                                    <option value="thick" <?php selected(get_option('vb_arms_age_verify_button_padding', 'normal'), 'thick'); ?>>Thick</option>
                                </select>
                                <p class="description">How thick/tall should the buttons be?</p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button('Save Style Settings', 'primary', 'submit', false); ?>
                </form>
            </div>
            
            <div class="card" style="margin-top: 20px; padding: 20px; background: #f9f9f9; border: 1px solid #ddd;">
                <h3>Advanced: Custom CSS (Optional - Skip This!)</h3>
                <p><em><strong>You probably don't need this!</strong> Only use if you know CSS code. Most people can ignore this section.</em></p>
                <form method="post" action="options.php">
                    <?php settings_fields('vb_arms_age_verify_settings'); ?>
                    <textarea name="vb_arms_age_verify_custom_css" rows="5" class="large-text code" style="font-family: monospace; font-size: 12px; background: #fff;"><?php echo esc_textarea(get_option('vb_arms_age_verify_custom_css', '')); ?></textarea>
                    <p class="description">Leave this empty unless you know what you're doing.</p>
                    <?php submit_button('Save CSS', 'secondary', 'submit', false); ?>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_assets() {
        // Only load if not already verified
        if (!$this->is_age_verified()) {
            // Inline CSS to avoid ad blocker issues
            add_action('wp_head', array($this, 'inline_styles'), 999);
            
            // Enqueue script with different handle to avoid ad blockers
            wp_enqueue_script(
                'vb-arms-verify',
                VB_ARMS_AGE_VERIFY_PLUGIN_URL . 'assets/age-verification.js',
                array('jquery'),
                VB_ARMS_AGE_VERIFY_VERSION,
                true
            );
            
            wp_localize_script('vb-arms-verify', 'vbArmsAgeVerify', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vb_arms_age_verify_nonce'),
                'cookie_name' => $this->cookie_name,
                'cookie_duration_days' => $this->cookie_duration_days
            ));
        }
    }
    
    /**
     * Output inline styles to avoid ad blocker issues
     */
    public function inline_styles() {
        $css_file = VB_ARMS_AGE_VERIFY_PLUGIN_DIR . 'assets/age-verification.css';
        if (file_exists($css_file)) {
            echo '<style id="vb-arms-age-verify-styles">';
            echo file_get_contents($css_file);
            
            // Add visual settings CSS
            $button_width = get_option('vb_arms_age_verify_button_width', '90');
            $logo_size = get_option('vb_arms_age_verify_logo_size', '200');
            $button_padding = get_option('vb_arms_age_verify_button_padding', 'normal');
            
            echo "\n/* Visual Settings */\n";
            echo ".vb-arms-age-btn { min-width: {$button_width}px; height: 28px !important; padding: 0.25rem 1.25rem !important; line-height: 1.2; box-sizing: border-box; font-size: 0.95rem; }\n";
            echo ".vb-arms-age-logo img { max-width: {$logo_size}px; }\n";
            
            // Add custom CSS from settings
            $custom_css = get_option('vb_arms_age_verify_custom_css', '');
            if (!empty($custom_css)) {
                echo "\n/* Custom CSS */\n";
                echo $custom_css;
            }
            
            echo '</style>';
        }
    }
    
    /**
     * Check if age is already verified
     */
    private function is_age_verified() {
        // If force show every time is enabled, always return false
        if ($this->force_show_every_time) {
            return false;
        }
        
        // Check cookie
        if (isset($_COOKIE[$this->cookie_name]) && $_COOKIE[$this->cookie_name] === 'yes') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Render age verification modal
     */
    public function render_age_verification_modal() {
        // Only show if not verified
        if ($this->is_age_verified()) {
            return;
        }
        ?>
        <div id="vb-arms-age-verification" class="vb-arms-age-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 999999;">
            <div class="vb-arms-age-modal-overlay"></div>
            <div class="vb-arms-age-modal-content" style="position: relative;">
                <?php
                // Add logo as background underlay
                $logo_bg_url = '';
                if (has_custom_logo()) {
                    $logo_id = get_theme_mod('custom_logo');
                    $logo_bg_url = wp_get_attachment_image_url($logo_id, 'full');
                } else {
                    $logo_url = get_template_directory_uri() . '/assets/logo.png';
                    if (file_exists(get_template_directory() . '/assets/logo.png')) {
                        $logo_bg_url = $logo_url;
                    } else {
                        $plugin_logo = VB_ARMS_AGE_VERIFY_PLUGIN_DIR . 'assets/logo.png';
                        if (file_exists($plugin_logo)) {
                            $logo_bg_url = VB_ARMS_AGE_VERIFY_PLUGIN_URL . 'assets/logo.png';
                        }
                    }
                }
                if ($logo_bg_url) {
                    echo '<div class="vb-arms-age-logo-bg" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-image: url(' . esc_url($logo_bg_url) . '); background-size: 80%; background-position: center 55%; background-repeat: no-repeat; opacity: 0.15; pointer-events: none; z-index: 0;"></div>';
                }
                ?>
                <div class="vb-arms-age-modal-header" style="position: relative; z-index: 1;">
                    <h2><?php echo esc_html(get_option('vb_arms_age_verify_title', 'VERIFY YOUR AGE')); ?></h2>
                </div>
                <div class="vb-arms-age-modal-body" style="position: relative; z-index: 1;">
                    <p class="vb-arms-age-disclaimer">
                        <?php echo esc_html(get_option('vb_arms_age_verify_disclaimer', "Certain states prohibit advertising or marketing of firearms, ammunition, and related firearm components to minors. We'd like to ensure that you're 18 years of age or older before allowing you to shop our site.")); ?>
                    </p>
                    <p class="vb-arms-age-question">
                        <strong><?php echo esc_html(get_option('vb_arms_age_verify_question', 'Are you 18 years of age or older?')); ?></strong>
                    </p>
                </div>
                <div class="vb-arms-age-modal-footer" style="position: relative; z-index: 1;">
                    <button id="vb-arms-confirm-age" class="vb-arms-age-btn vb-arms-age-btn-primary">
                        <?php echo esc_html(get_option('vb_arms_age_verify_yes_text', 'Yes')); ?>
                    </button>
                    <button id="vb-arms-exit-site" class="vb-arms-age-btn vb-arms-age-btn-secondary">
                        <?php echo esc_html(get_option('vb_arms_age_verify_no_text', 'No')); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle age verification AJAX request
     */
    public function handle_age_verification() {
        check_ajax_referer('vb_arms_age_verify_nonce', 'nonce');
        
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
        
        if ($action === 'confirm') {
            // Set cookie for 24 hours (same browser). New browser = no cookie = verify again.
            $expire = time() + ( $this->cookie_duration_days * DAY_IN_SECONDS );
            setcookie(
                $this->cookie_name,
                'yes',
                $expire,
                '/',
                '',
                is_ssl(),
                true
            );
            
            // Also set in $_COOKIE for immediate access
            $_COOKIE[$this->cookie_name] = 'yes';
            
            wp_send_json_success(array(
                'message' => 'Age verified',
                'verified' => true
            ));
        } elseif ($action === 'exit') {
            // Redirect to exit page or external site
            wp_send_json_success(array(
                'redirect' => 'https://www.google.com' // Change to your exit URL
            ));
        }
        
        wp_send_json_error(array(
            'message' => 'Invalid action'
        ));
    }
}

// Initialize plugin
function vb_arms_age_verification_init() {
    return VB_Arms_Age_Verification::get_instance();
}
add_action('plugins_loaded', 'vb_arms_age_verification_init', 10);
