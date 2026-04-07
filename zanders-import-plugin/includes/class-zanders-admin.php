<?php
/**
 * Admin Interface for Zanders Import
 */

if (!defined('ABSPATH')) {
    exit;
}

class Zanders_Import_Admin {
    
    private $option_name = 'zanders_import_settings';
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_zanders_test_ftp', array($this, 'handle_test_ftp'));
        add_action('wp_ajax_zanders_download_files', array($this, 'handle_download_files'));
        add_action('wp_ajax_zanders_upload_files', array($this, 'handle_manual_upload'));
        add_action('wp_ajax_zanders_check_existing_files', array($this, 'handle_check_existing_files'));
        add_action('wp_ajax_zanders_import_preview', array($this, 'handle_preview'));
        add_action('wp_ajax_zanders_import_process', array($this, 'handle_import'));
        add_action('wp_ajax_zanders_test_image', array($this, 'test_image_download'));
        
        // Save settings
        add_action('admin_init', array($this, 'save_settings'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Zanders Import',
            'Zanders Import',
            'manage_woocommerce',
            'zanders-import',
            array($this, 'render_admin_page')
        );
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'woocommerce_page_zanders-import') {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_style('zanders-import-admin', ZANDERS_IMPORT_PLUGIN_URL . 'assets/admin.css', array(), ZANDERS_IMPORT_VERSION);
        wp_enqueue_script('zanders-import-admin', ZANDERS_IMPORT_PLUGIN_URL . 'assets/admin.js', array('jquery'), ZANDERS_IMPORT_VERSION, true);
        
        wp_localize_script('zanders-import-admin', 'zandersImport', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('zanders_import_nonce')
        ));
    }
    
    public function save_settings() {
        if (!isset($_POST['zanders_save_settings']) || !current_user_can('manage_woocommerce')) {
            return;
        }
        
        check_admin_referer('zanders_save_settings');
        
        $settings = array(
            'ftp_host' => sanitize_text_field($_POST['ftp_host'] ?? 'ftp2.gzanders.com'),
            'ftp_username' => sanitize_text_field($_POST['ftp_username'] ?? ''),
            'ftp_password' => sanitize_text_field($_POST['ftp_password'] ?? ''),
            'ftp_folder' => sanitize_text_field($_POST['ftp_folder'] ?? ''),
            'ftp_port' => isset($_POST['ftp_port']) ? intval($_POST['ftp_port']) : 21,
            'ftp_use_ssl' => isset($_POST['ftp_use_ssl']) ? 1 : 0,
            'file_format' => sanitize_text_field($_POST['file_format'] ?? 'csv'),
            'use_live_inventory' => isset($_POST['use_live_inventory']) ? 1 : 0,
        );
        
        update_option($this->option_name, $settings);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        });
    }
    
    private function get_settings() {
        $defaults = array(
            'ftp_host' => 'ftp2.gzanders.com',
            'ftp_username' => '',
            'ftp_password' => '',
            'ftp_folder' => '',
            'ftp_port' => 21,
            'ftp_use_ssl' => 0,
            'file_format' => 'csv',
            'use_live_inventory' => 1,
        );
        
        return wp_parse_args(get_option($this->option_name, array()), $defaults);
    }
    
    public function render_admin_page() {
        $settings = $this->get_settings();
        
        // Initialize category structure
        if (isset($_POST['init_categories'])) {
            Zanders_Category_Mapper::init_category_structure();
            echo '<div class="notice notice-success"><p>Category structure initialized!</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1>Zanders Inventory Import</h1>
            
            <div class="zanders-import-container">
                <!-- FTP Configuration Section -->
                <div class="postbox">
                    <h2 class="hndle">FTP Configuration</h2>
                    <div class="inside">
                        <form method="post" action="">
                            <?php wp_nonce_field('zanders_save_settings'); ?>
                            <table class="form-table">
                                <tr>
                                    <th><label for="ftp_host">FTP Host</label></th>
                                    <td>
                                        <input type="text" id="ftp_host" name="ftp_host" class="regular-text" 
                                               value="<?php echo esc_attr($settings['ftp_host']); ?>" 
                                               placeholder="ftp2.gzanders.com">
                                        <p class="description">FTP server address</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="ftp_username">Username</label></th>
                                    <td>
                                        <input type="text" id="ftp_username" name="ftp_username" class="regular-text" 
                                               value="<?php echo esc_attr($settings['ftp_username']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="ftp_password">Password</label></th>
                                    <td>
                                        <input type="password" id="ftp_password" name="ftp_password" class="regular-text" 
                                               value="<?php echo esc_attr($settings['ftp_password']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="ftp_folder">Dedicated Folder</label></th>
                                    <td>
                                        <input type="text" id="ftp_folder" name="ftp_folder" class="regular-text" 
                                               value="<?php echo esc_attr($settings['ftp_folder']); ?>">
                                        <p class="description">Optional: Your dedicated folder path on the FTP server</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="ftp_port">FTP Port</label></th>
                                    <td>
                                        <input type="number" id="ftp_port" name="ftp_port" class="small-text" 
                                               value="<?php echo esc_attr($settings['ftp_port']); ?>" min="1" max="65535">
                                        <p class="description">Default: 21 (use 990 for FTPS)</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="ftp_use_ssl">Use SSL/TLS (FTPS)</label></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" id="ftp_use_ssl" name="ftp_use_ssl" value="1" 
                                                   <?php checked($settings['ftp_use_ssl'], 1); ?>>
                                            Enable secure FTP connection (FTPS)
                                        </label>
                                        <p class="description">Try this if regular FTP connection fails</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="file_format">File Format</label></th>
                                    <td>
                                        <select id="file_format" name="file_format">
                                            <option value="csv" <?php selected($settings['file_format'], 'csv'); ?>>CSV (ZandersInv.csv)</option>
                                            <option value="xml" <?php selected($settings['file_format'], 'xml'); ?>>XML (ZandersInv.xml)</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="use_live_inventory">Use Live Inventory</label></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" id="use_live_inventory" name="use_live_inventory" value="1" 
                                                   <?php checked($settings['use_live_inventory'], 1); ?>>
                                            Download and merge LiveInv.csv / Qtypricingout.xml for real-time pricing (updated every 5 minutes)
                                        </label>
                                    </td>
                                </tr>
                            </table>
                            
                            <div class="notice notice-info" style="margin: 15px 0;">
                                <p><strong>⚠️ Connection Timeout Issues?</strong></p>
                                <p>If FTP connection times out, it's likely because:</p>
                                <ul style="margin-left: 20px;">
                                    <li><strong>EasyWP blocks outbound FTP</strong> - Contact Namecheap support to allow FTP connections</li>
                                    <li><strong>Zanders requires IP whitelist</strong> - Contact Zanders support to whitelist your server IP</li>
                                    <li><strong>Firewall blocking</strong> - Your hosting firewall may be blocking FTP port 21</li>
                                </ul>
                                <p><strong>Alternative:</strong> Use the "Manual File Upload" option below to upload files directly.</p>
                            </div>
                            <p class="submit">
                                <button type="submit" name="zanders_save_settings" class="button button-primary">Save Settings</button>
                                <button type="button" id="test-ftp-btn" class="button button-secondary">Test FTP Connection</button>
                                <span id="ftp-test-result"></span>
                            </p>
                        </form>
                        
                        <?php
                        // Check if FTP extension or cURL is available
                        $ftp_available = function_exists('ftp_connect');
                        $curl_available = function_exists('curl_init');
                        $curl_supports_ftp = false;
                        
                        if ($curl_available) {
                            $curl_version = curl_version();
                            $curl_supports_ftp = isset($curl_version['protocols']) && in_array('ftp', $curl_version['protocols']);
                        }
                        
                        if (!$ftp_available && (!$curl_available || !$curl_supports_ftp)) {
                            // Detect hosting type
                            $is_easywp = (defined('EASYWP') || strpos($_SERVER['SERVER_SOFTWARE'] ?? '', 'EasyWP') !== false || strpos(ABSPATH, 'easywp') !== false);
                            ?>
                            <div class="notice notice-error" style="margin-top: 20px;">
                                <p><strong>⚠️ FTP Access Not Available</strong></p>
                                <p>Neither PHP FTP extension nor cURL with FTP support is available.</p>
                                
                                <?php if ($is_easywp) { ?>
                                    <p><strong>🔵 EasyWP (Namecheap) Detected - Using cURL Alternative:</strong></p>
                                    <p>The plugin will automatically try to use cURL instead of the FTP extension. Click "Test FTP Connection" to try it now!</p>
                                    <p><strong>If cURL also fails:</strong> Contact Namecheap support to enable cURL with FTP support, or use the manual file upload option below.</p>
                                <?php } else { ?>
                                    <p><strong>Quick Steps:</strong></p>
                                    <ol>
                                        <li><strong>cPanel/Shared Hosting:</strong> Go to "Select PHP Version" → Extensions → Enable "ftp"</li>
                                        <li><strong>VPS/Dedicated:</strong> Install with: <code>sudo apt-get install php-ftp</code> (Ubuntu) or <code>sudo yum install php-ftp</code> (CentOS)</li>
                                        <li><strong>Managed Hosting:</strong> Contact your hosting support to enable the FTP extension</li>
                                    </ol>
                                    <p><strong>Need detailed instructions?</strong> See <code>FTP-EXTENSION-GUIDE.md</code> in the plugin folder.</p>
                                <?php } ?>
                            </div>
                            <?php
                        } elseif (!$ftp_available && $curl_available && $curl_supports_ftp) {
                            ?>
                            <div class="notice notice-warning" style="margin-top: 20px;">
                                <p><strong>ℹ️ Using cURL Instead of FTP Extension</strong></p>
                                <p>The PHP FTP extension is not available, but cURL with FTP support is detected. The plugin will automatically use cURL for FTP connections.</p>
                                <p><strong>This should work fine!</strong> Click "Test FTP Connection" to verify.</p>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Manual File Upload Section - Prominent for EasyWP users -->
                <div class="postbox" style="border-left: 4px solid #2271b1;">
                    <h2 class="hndle" style="background: #2271b1; color: white; padding: 10px;">
                        📤 Manual File Upload (Recommended for EasyWP)
                    </h2>
                    <div class="inside">
                        <div class="notice notice-info" style="margin: 0 0 15px 0;">
                            <p><strong>💡 Easy Solution:</strong> Since EasyWP blocks outbound FTP connections, download files from Zanders FTP on your computer, then upload them here.</p>
                        </div>
                        
                        <h3>Step 1: Download Files from Zanders FTP</h3>
                        <ol style="margin-left: 20px; margin-bottom: 20px;">
                            <li><strong>Download FileZilla to your computer</strong> (not the server):
                                <ul style="margin-top: 5px;">
                                    <li>Go to: <a href="https://filezilla-project.org/" target="_blank">https://filezilla-project.org/</a></li>
                                    <li>Click "Download FileZilla Client" (for Windows/Mac/Linux)</li>
                                    <li>Install it on <strong>your local computer</strong> (where you're browsing WordPress)</li>
                                    <li>This is a desktop application - you run it on your computer, not on the server</li>
                                </ul>
                            </li>
                            <li><strong>Open FileZilla on your computer</strong> and connect to Zanders FTP:
                                <ul style="margin-top: 5px;">
                                    <li>At the top of FileZilla, enter:
                                        <ul style="margin-top: 5px; margin-left: 15px;">
                                            <li><strong>Host:</strong> <code>ftp2.gzanders.com</code></li>
                                            <li><strong>Username:</strong> [Your Zanders username]</li>
                                            <li><strong>Password:</strong> [Your Zanders password]</li>
                                            <li><strong>Port:</strong> <code>21</code></li>
                                        </ul>
                                    </li>
                                    <li>Click <strong>"Quickconnect"</strong></li>
                                    <li>You should see Zanders' files on the right side</li>
                                </ul>
                            </li>
                            <li><strong>Download the files:</strong>
                                <ul style="margin-top: 5px;">
                                    <li>On the right side (remote server), find and select: <code>ZandersInv.csv</code></li>
                                    <li>Right-click → <strong>"Download"</strong> (or drag to left side)</li>
                                    <li>Repeat for <code>LiveInv.csv</code> (optional - for live pricing)</li>
                                    <li>Files will download to your computer's default Downloads folder</li>
                                </ul>
                            </li>
                            <li><strong>Note where you saved the files</strong> - you'll need to find them in Step 2</li>
                        </ol>
                        
                        <h3>Step 2: Upload Files Here</h3>
                        <form id="manual-upload-form" enctype="multipart/form-data">
                            <table class="form-table">
                                <tr>
                                    <th><label for="manual_csv_file">Inventory CSV File <span style="color: red;">*</span></label></th>
                                    <td>
                                        <input type="file" id="manual_csv_file" name="manual_csv_file" accept=".csv" required>
                                        <p class="description">Upload ZandersInv.csv (required)</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="manual_live_csv">Live Inventory CSV (Optional)</label></th>
                                    <td>
                                        <input type="file" id="manual_live_csv" name="manual_live_csv" accept=".csv">
                                        <p class="description">Upload LiveInv.csv for real-time pricing updates (optional but recommended)</p>
                                    </td>
                                </tr>
                            </table>
                            <p class="submit">
                                <button type="button" id="upload-files-btn" class="button button-primary" style="font-size: 14px; padding: 8px 20px;">
                                    📤 Upload Files
                                </button>
                                <span id="upload-result" style="margin-left: 15px;"></span>
                            </p>
                        </form>
                        
                        <div id="upload-success" style="display: none; margin-top: 15px; padding: 10px; background: #d4edda; border-left: 4px solid #28a745;">
                            <p><strong>✓ Files uploaded successfully!</strong> You can now use "Preview Inventory" and "Start Import" buttons below.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Import Section -->
                <div class="postbox">
                    <h2 class="hndle">Import Products</h2>
                    <div class="inside">
                        <form id="zanders-import-form">
                            <h3>Import Settings</h3>
                            <table class="form-table">
                                <tr>
                                    <th><label for="batch_size">Batch Size</label></th>
                                    <td>
                                        <input type="number" id="batch_size" name="batch_size" value="50" min="1" max="500">
                                        <p class="description">Number of products to process per batch (lower = safer for large imports)</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="update_existing">Update Existing Products</label></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" id="update_existing" name="update_existing" checked>
                                            Update existing products (by SKU) if they already exist
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="download_images">Download Product Images</label></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" id="download_images" name="download_images" checked>
                                            Download images from FTP Images folder
                                        </label>
                                        <p class="description"><strong>⚠️ Uncheck this for faster imports!</strong> You can import products first, then download images later in a separate run.</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <h3 style="margin-top: 30px;">Filter Products (Optional)</h3>
                            <p class="description">Use filters to import only specific products. This helps manage large inventories and speeds up imports.</p>
                            <table class="form-table">
                                <tr>
                                    <th><label for="filter_product_type">Product Type</label></th>
                                    <td>
                                        <select id="filter_product_type" name="filter_product_type">
                                            <option value="">All Products</option>
                                            <option value="firearms">Firearms Only (Rifles, Handguns, Shotguns)</option>
                                            <option value="accessories">Accessories Only</option>
                                        </select>
                                        <p class="description">Filter by product category type</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="filter_min_quantity">Minimum Quantity</label></th>
                                    <td>
                                        <input type="number" id="filter_min_quantity" name="filter_min_quantity" value="0" min="0">
                                        <p class="description">Only import products with at least this quantity in stock (0 = no filter)</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="filter_min_price">Minimum Price</label></th>
                                    <td>
                                        <input type="number" id="filter_min_price" name="filter_min_price" value="0" min="0" step="0.01">
                                        <p class="description">Only import products priced at or above this amount ($0 = no filter)</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="filter_max_price">Maximum Price</label></th>
                                    <td>
                                        <input type="number" id="filter_max_price" name="filter_max_price" value="0" min="0" step="0.01">
                                        <p class="description">Only import products priced at or below this amount ($0 = no filter)</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="filter_manufacturer">Manufacturer Filter</label></th>
                                    <td>
                                        <input type="text" id="filter_manufacturer" name="filter_manufacturer" placeholder="e.g., Glock, Smith & Wesson" style="width: 100%; max-width: 400px;">
                                        <p class="description">Filter by manufacturer name (partial match). Leave empty for all manufacturers. <strong>Example: "Glock" to import only Glock products.</strong></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="filter_limit">Limit Total Products</label></th>
                                    <td>
                                        <input type="number" id="filter_limit" name="filter_limit" value="50" min="0">
                                        <p class="description">Import only the first N products (0 = no limit). <strong>Useful for testing!</strong> Set to 50 for your Glock test import.</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p class="submit">
                                <button type="button" id="download-files-btn" class="button button-secondary">Download Files from FTP</button>
                                <button type="button" id="preview-btn" class="button button-secondary">Preview Inventory</button>
                                <button type="button" id="import-btn" class="button button-primary">Start Import</button>
                            </p>
                        </form>
                    </div>
                </div>
                
                <!-- Preview Section -->
                <div id="preview-section" class="postbox" style="display: none;">
                    <h2 class="hndle">Inventory Preview</h2>
                    <div class="inside">
                        <div id="preview-content"></div>
                    </div>
                </div>
                
                <!-- Progress Section -->
                <div id="progress-section" class="postbox" style="display: none;">
                    <h2 class="hndle">Import Progress</h2>
                    <div class="inside">
                        <div id="progress-bar-container">
                            <div id="progress-bar" style="width: 0%; background: #2271b1; height: 30px; line-height: 30px; text-align: center; color: white;">
                                0%
                            </div>
                        </div>
                        <div id="progress-stats" style="margin-top: 15px;">
                            <p><strong>Status:</strong> <span id="progress-status">Ready</span></p>
                            <p><strong>Processed:</strong> <span id="processed-count">0</span> / <span id="total-count">0</span></p>
                            <p><strong>Created:</strong> <span id="created-count">0</span></p>
                            <p><strong>Updated:</strong> <span id="updated-count">0</span></p>
                            <p><strong>Errors:</strong> <span id="error-count">0</span></p>
                        </div>
                        <div id="error-log" style="margin-top: 15px; max-height: 200px; overflow-y: auto; display: none;">
                            <h4>Error Log:</h4>
                            <ul id="error-list"></ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="postbox" style="margin-top: 20px;">
                <h2 class="hndle">Category Management</h2>
                <div class="inside">
                    <form method="post" action="">
                        <p>
                            <button type="submit" name="init_categories" class="button button-secondary">
                                Initialize Category Structure
                            </button>
                            <span class="description">Creates default WooCommerce categories (Firearms, Accessories, etc.)</span>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function handle_test_ftp() {
        check_ajax_referer('zanders_import_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $host = isset($_POST['host']) ? sanitize_text_field($_POST['host']) : 'ftp2.gzanders.com';
        $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
        $password = isset($_POST['password']) ? sanitize_text_field($_POST['password']) : '';
        $folder = isset($_POST['folder']) ? sanitize_text_field($_POST['folder']) : '';
        $port = isset($_POST['port']) ? intval($_POST['port']) : (isset($_POST['ftp_port']) ? intval($_POST['ftp_port']) : 21);
        $use_ssl = isset($_POST['use_ssl']) ? (bool)$_POST['use_ssl'] : (isset($_POST['ftp_use_ssl']) ? (bool)$_POST['ftp_use_ssl'] : false);
        
        if (empty($username) || empty($password)) {
            wp_send_json_error(array('message' => 'Username and password are required'));
        }
        
        $result = null;
        $method_used = 'none';
        
        // Try PHP FTP extension first (if available)
        if (function_exists('ftp_connect')) {
            $ftp = new Zanders_FTP_Handler($host, $username, $password, $folder, $port, $use_ssl, 30);
            $result = $ftp->test_connection();
            $method_used = 'php_ftp';
            
            // If SSL was requested and failed, try without SSL
            if (!$result['success'] && $use_ssl) {
                $ftp_no_ssl = new Zanders_FTP_Handler($host, $username, $password, $folder, $port, false, 30);
                $result_no_ssl = $ftp_no_ssl->test_connection();
                if ($result_no_ssl['success']) {
                    $result = $result_no_ssl;
                    $result['message'] = 'SSL connection failed, but regular FTP works: ' . $result_no_ssl['message'];
                    $result['suggestion'] = 'Try without SSL enabled';
                }
            }
        }
        
        // Fallback to cURL if FTP extension not available or failed
        if (!$result || !$result['success']) {
            if (function_exists('curl_init')) {
                $curl_ftp = new Zanders_CURL_FTP_Handler($host, $username, $password, $folder, $port, $use_ssl, 30);
                $curl_result = $curl_ftp->test_connection();
                
                if ($curl_result['success']) {
                    $result = $curl_result;
                    $method_used = 'curl';
                } elseif (!$result) {
                    // Only use cURL result if we don't have a PHP FTP result
                    $result = $curl_result;
                    $method_used = 'curl';
                }
            }
        }
        
        if (!$result) {
            $result = array(
                'success' => false,
                'message' => 'Neither PHP FTP extension nor cURL is available. Please enable one of them.',
                'method' => 'none'
            );
        } else {
            $result['method_used'] = $method_used;
        }
        
        wp_send_json($result);
    }
    
    public function handle_manual_upload() {
        check_ajax_referer('zanders_import_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        if (!isset($_FILES['manual_csv_file']) || $_FILES['manual_csv_file']['error'] !== UPLOAD_ERR_OK) {
            $error_msg = 'No file uploaded';
            if (isset($_FILES['manual_csv_file']['error'])) {
                $error_codes = array(
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'PHP extension stopped the file upload'
                );
                $error_msg = $error_codes[$_FILES['manual_csv_file']['error']] ?? 'Upload error: ' . $_FILES['manual_csv_file']['error'];
            }
            wp_send_json_error(array('message' => $error_msg));
        }
        
        $file = $_FILES['manual_csv_file'];
        
        // Validate file type
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_ext !== 'csv') {
            wp_send_json_error(array('message' => 'Invalid file type. Please upload a CSV file.'));
        }
        
        // Check file size (limit to 50MB)
        if ($file['size'] > 50 * 1024 * 1024) {
            wp_send_json_error(array('message' => 'File too large. Maximum size is 50MB.'));
        }
        
        $upload_dir = wp_upload_dir();
        $zanders_dir = $upload_dir['basedir'] . '/zanders-import';
        wp_mkdir_p($zanders_dir);
        
        // Sanitize filename
        $filename = sanitize_file_name($file['name']);
        $file_path = $zanders_dir . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            wp_send_json_error(array('message' => 'Failed to move uploaded file. Check file permissions.'));
        }
        
        $uploaded_files = array($filename);
        $main_file = $file_path;
        
        // Handle live inventory file if provided
        if (isset($_FILES['manual_live_csv']) && $_FILES['manual_live_csv']['error'] === UPLOAD_ERR_OK) {
            $live_file = $_FILES['manual_live_csv'];
            $live_ext = strtolower(pathinfo($live_file['name'], PATHINFO_EXTENSION));
            
            if ($live_ext === 'csv') {
                $live_filename = sanitize_file_name($live_file['name']);
                $live_path = $zanders_dir . '/' . $live_filename;
                
                if (move_uploaded_file($live_file['tmp_name'], $live_path)) {
                    $uploaded_files[] = $live_filename;
                }
            }
        }
        
        wp_send_json_success(array(
            'message' => 'Files uploaded successfully: ' . implode(', ', $uploaded_files),
            'main_file' => $main_file,
            'files' => $uploaded_files,
            'file_size' => size_format(filesize($main_file))
        ));
    }
    
    public function handle_download_files() {
        check_ajax_referer('zanders_import_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $settings = $this->get_settings();
        
        if (empty($settings['ftp_username']) || empty($settings['ftp_password'])) {
            wp_send_json_error(array('message' => 'FTP credentials not configured'));
        }
        
        $ftp = Zanders_FTP_Factory::create(
            $settings['ftp_host'],
            $settings['ftp_username'],
            $settings['ftp_password'],
            $settings['ftp_folder'],
            isset($settings['ftp_port']) ? intval($settings['ftp_port']) : 21,
            isset($settings['ftp_use_ssl']) ? (bool)$settings['ftp_use_ssl'] : false,
            30
        );
        
        if (!$ftp) {
            wp_send_json_error(array('message' => 'Neither PHP FTP extension nor cURL with FTP support is available'));
        }
        
        $upload_dir = wp_upload_dir();
        $zanders_dir = $upload_dir['basedir'] . '/zanders-import';
        wp_mkdir_p($zanders_dir);
        
        $file_format = $settings['file_format'];
        $use_live = $settings['use_live_inventory'];
        
        $downloaded = array();
        $errors = array();
        
        // Check for existing files first (from automated sync)
        $existing_files = array();
        if ($file_format === 'csv') {
            $local_file = $zanders_dir . '/ZandersInv.csv';
            if (file_exists($local_file) && filesize($local_file) > 0) {
                $existing_files[] = 'ZandersInv.csv';
                $downloaded[] = 'ZandersInv.csv (already exists)';
            }
            
            if ($use_live) {
                $live_file = $zanders_dir . '/LiveInv.csv';
                if (file_exists($live_file) && filesize($live_file) > 0) {
                    $existing_files[] = 'LiveInv.csv';
                    $downloaded[] = 'LiveInv.csv (already exists)';
                }
            }
        } else {
            $local_file = $zanders_dir . '/ZandersInv.xml';
            if (file_exists($local_file) && filesize($local_file) > 0) {
                $existing_files[] = 'ZandersInv.xml';
                $downloaded[] = 'ZandersInv.xml (already exists)';
            }
            
            if ($use_live) {
                $pricing_file = $zanders_dir . '/Qtypricingout.xml';
                if (file_exists($pricing_file) && filesize($pricing_file) > 0) {
                    $existing_files[] = 'Qtypricingout.xml';
                    $downloaded[] = 'Qtypricingout.xml (already exists)';
                }
            }
        }
        
        // If all required files exist, skip FTP download
        $required_files = ($file_format === 'csv') ? array('ZandersInv.csv') : array('ZandersInv.xml');
        if ($use_live) {
            $required_files[] = ($file_format === 'csv') ? 'LiveInv.csv' : 'Qtypricingout.xml';
        }
        
        $all_exist = true;
        foreach ($required_files as $req_file) {
            if (!in_array($req_file, $existing_files)) {
                $all_exist = false;
                break;
            }
        }
        
        if ($all_exist && !empty($existing_files)) {
            wp_send_json_success(array(
                'message' => 'Using existing files (uploaded via automated sync): ' . implode(', ', $existing_files),
                'files' => $existing_files,
                'main_file' => $local_file,
                'errors' => array()
            ));
            return;
        }
        
        // Download main inventory file (if not already exists)
        if ($file_format === 'csv') {
            $local_file = $zanders_dir . '/ZandersInv.csv';
            if (!in_array('ZandersInv.csv', $existing_files)) {
                if ($ftp->download_inventory_csv($local_file)) {
                    $downloaded[] = 'ZandersInv.csv';
                } else {
                    $errors = array_merge($errors, $ftp->get_errors());
                }
            }
            
            // Download live inventory if enabled (if not already exists)
            if ($use_live) {
                $live_file = $zanders_dir . '/LiveInv.csv';
                if (!in_array('LiveInv.csv', $existing_files)) {
                    if ($ftp->download_live_inventory_csv($live_file)) {
                        $downloaded[] = 'LiveInv.csv';
                    } else {
                        $errors = array_merge($errors, $ftp->get_errors());
                    }
                }
            }
        } else {
            $local_file = $zanders_dir . '/ZandersInv.xml';
            if (!in_array('ZandersInv.xml', $existing_files)) {
                if ($ftp->download_inventory_xml($local_file)) {
                    $downloaded[] = 'ZandersInv.xml';
                } else {
                    $errors = array_merge($errors, $ftp->get_errors());
                }
            }
            
            // Download pricing XML if enabled (if not already exists)
            if ($use_live) {
                $pricing_file = $zanders_dir . '/Qtypricingout.xml';
                if (!in_array('Qtypricingout.xml', $existing_files)) {
                    if ($ftp->download_pricing_xml($pricing_file)) {
                        $downloaded[] = 'Qtypricingout.xml';
                    } else {
                        $errors = array_merge($errors, $ftp->get_errors());
                    }
                }
            }
        }
        
        // Combine existing files with newly downloaded files
        $all_files = array_unique(array_merge($existing_files, $downloaded));
        
        // Check if we have at least the main file
        $main_file_exists = false;
        if ($file_format === 'csv') {
            $main_file_exists = file_exists($zanders_dir . '/ZandersInv.csv') && filesize($zanders_dir . '/ZandersInv.csv') > 0;
        } else {
            $main_file_exists = file_exists($zanders_dir . '/ZandersInv.xml') && filesize($zanders_dir . '/ZandersInv.xml') > 0;
        }
        
        if (!$main_file_exists && empty($downloaded)) {
            // No files exist and download failed
            $error_msg = 'Failed to download files. ';
            if (!empty($errors)) {
                $error_msg .= 'Errors: ' . implode('; ', array_unique($errors));
            } else {
                $error_msg .= 'EasyWP blocks outbound FTP connections. Use "Manual File Upload" or wait for automated sync.';
            }
            wp_send_json_error(array(
                'message' => $error_msg,
                'errors' => $errors,
                'hint' => 'Files may already exist from automated sync. Try clicking "Preview Inventory" instead.'
            ));
            return;
        }
        
        // Success - we have files (either existing or newly downloaded)
        $message = 'Files ready: ' . implode(', ', $all_files);
        if (!empty($existing_files)) {
            $message .= ' (some from automated sync)';
        }
        if (!empty($downloaded)) {
            $message .= ' (some newly downloaded)';
        }
        
        wp_send_json_success(array(
            'message' => $message,
            'files' => $all_files,
            'main_file' => $local_file,
            'errors' => $errors
        ));
    }
    
    public function handle_check_existing_files() {
        check_ajax_referer('zanders_import_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $upload_dir = wp_upload_dir();
        $zanders_dir = $upload_dir['basedir'] . '/zanders-import';
        
        // Ensure directory exists
        if (!file_exists($zanders_dir)) {
            wp_mkdir_p($zanders_dir);
        }
        
        $settings = $this->get_settings();
        $file_format = $settings['file_format'] ?? 'csv';
        $use_live = $settings['use_live_inventory'] ?? false;
        
        $main_file = null;
        $live_file = null;
        $found_files = array();
        
        // Check for CSV files (always check both formats to be safe)
        $csv_main = $zanders_dir . '/ZandersInv.csv';
        $csv_live = $zanders_dir . '/LiveInv.csv';
        $xml_main = $zanders_dir . '/ZandersInv.xml';
        $xml_pricing = $zanders_dir . '/Qtypricingout.xml';
        
        // Check CSV files
        if (file_exists($csv_main) && filesize($csv_main) > 0) {
            $main_file = $csv_main;
            $file_format = 'csv';
            $found_files[] = 'ZandersInv.csv';
        }
        
        if (file_exists($csv_live) && filesize($csv_live) > 0) {
            $live_file = $csv_live;
            $found_files[] = 'LiveInv.csv';
        }
        
        // Check XML files (if CSV not found)
        if (!$main_file && file_exists($xml_main) && filesize($xml_main) > 0) {
            $main_file = $xml_main;
            $file_format = 'xml';
            $found_files[] = 'ZandersInv.xml';
        }
        
        if (file_exists($xml_pricing) && filesize($xml_pricing) > 0) {
            if (!$live_file) {
                $live_file = $xml_pricing;
            }
            $found_files[] = 'Qtypricingout.xml';
        }
        
        if ($main_file) {
            wp_send_json_success(array(
                'message' => 'Found existing files from automated sync: ' . implode(', ', $found_files),
                'main_file' => $main_file,
                'live_file' => $live_file,
                'file_format' => $file_format,
                'found_files' => $found_files
            ));
        } else {
            // List what's actually in the directory for debugging
            $dir_contents = array();
            if (is_dir($zanders_dir)) {
                $files = scandir($zanders_dir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && is_file($zanders_dir . '/' . $file)) {
                        $dir_contents[] = $file . ' (' . size_format(filesize($zanders_dir . '/' . $file)) . ')';
                    }
                }
            }
            
            wp_send_json_error(array(
                'message' => 'No existing files found in ' . $zanders_dir,
                'directory' => $zanders_dir,
                'contents' => $dir_contents
            ));
        }
    }
    
    public function handle_preview() {
        check_ajax_referer('zanders_import_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $file_path = isset($_POST['file_path']) ? sanitize_text_field($_POST['file_path']) : '';
        $file_format = isset($_POST['file_format']) ? sanitize_text_field($_POST['file_format']) : 'csv';
        $live_file = isset($_POST['live_file']) ? sanitize_text_field($_POST['live_file']) : '';
        
        if (empty($file_path) || !file_exists($file_path)) {
            wp_send_json_error(array('message' => 'File not found: ' . $file_path));
        }
        
        // Get filter options from POST
        $filters = array();
        if (isset($_POST['filter_min_quantity']) && intval($_POST['filter_min_quantity']) > 0) {
            $filters['min_quantity'] = intval($_POST['filter_min_quantity']);
        }
        if (isset($_POST['filter_product_type']) && !empty($_POST['filter_product_type'])) {
            $filters['product_type'] = sanitize_text_field($_POST['filter_product_type']);
        }
        if (isset($_POST['filter_min_price']) && floatval($_POST['filter_min_price']) > 0) {
            $filters['min_price'] = floatval($_POST['filter_min_price']);
        }
        if (isset($_POST['filter_max_price']) && floatval($_POST['filter_max_price']) > 0) {
            $filters['max_price'] = floatval($_POST['filter_max_price']);
        }
        if (isset($_POST['filter_manufacturer']) && !empty($_POST['filter_manufacturer'])) {
            $filters['manufacturers'] = array(sanitize_text_field($_POST['filter_manufacturer']));
        }
        if (isset($_POST['filter_limit']) && intval($_POST['filter_limit']) > 0) {
            $filters['limit'] = intval($_POST['filter_limit']);
        }
        
        if ($file_format === 'csv') {
            $processor = new Zanders_CSV_Processor($file_path, 'full');
            
            if (!$processor->parse()) {
                wp_send_json_error(array(
                    'message' => 'Failed to parse CSV',
                    'errors' => $processor->get_errors()
                ));
            }
            
            // Get count before filtering for debugging
            $total_before_filter = $processor->get_count();
            
            // Merge live inventory if provided
            if (!empty($live_file) && file_exists($live_file)) {
                $live_processor = new Zanders_CSV_Processor($live_file, 'live');
                if ($live_processor->parse()) {
                    $processor->merge_live_data($live_processor->get_rows());
                    $total_before_filter = $processor->get_count(); // Update count after merge
                }
            }
            
            // Get CSV parsing debug stats
            $csv_debug = $processor->get_debug_stats();
            
            // Calculate unique products (by item number)
            $unique_products = array();
            foreach ($processor->get_rows() as $row) {
                $item_no = isset($row['itemnumber']) ? $row['itemnumber'] : (isset($row['Item#']) ? $row['Item#'] : '');
                if (!empty($item_no)) {
                    $unique_products[$item_no] = true;
                }
            }
            $unique_count = count($unique_products);
            
            // Debug: Always get sample data before filtering
            $sample_rows_before = array_slice($processor->get_rows(), 0, 20);
            
            // Apply filters for preview
            if (!empty($filters)) {
                $processor->apply_filters($filters);
            }
            
            $total_after_filter = $processor->get_count();
            
            // Debug info - show if 0 products found (whether due to filters or empty file)
            $debug_info = null;
            if ($total_after_filter === 0) {
                $debug_info = array(
                    'total_before_filter' => $total_before_filter,
                    'total_after_filter' => $total_after_filter,
                    'filters_applied' => $filters,
                    'csv_stats' => $csv_debug,
                    'sample_mfg_values' => array(),
                    'sample_categories' => array(),
                    'sample_descriptions' => array(),
                    'sample_items' => array()
                );
                
                // Use sample rows from CSV debug stats (includes rows regardless of Avail status)
                if ($csv_debug && isset($csv_debug['sample_all_rows'])) {
                    foreach ($csv_debug['sample_all_rows'] as $sample) {
                        // Try both 'manufacturer' and 'MFG'
                        $mfg = isset($sample['manufacturer']) ? $sample['manufacturer'] : (isset($sample['MFG']) ? $sample['MFG'] : '');
                        if (!empty($mfg)) {
                            $debug_info['sample_mfg_values'][] = $mfg;
                        }
                        // Try both 'category' and 'Category'
                        $category = isset($sample['category']) ? $sample['category'] : (isset($sample['Category']) ? $sample['Category'] : '');
                        if (!empty($category)) {
                            $debug_info['sample_categories'][] = $category;
                        }
                        // Try both 'desc1' and 'Desc1'
                        $desc1 = isset($sample['desc1']) ? $sample['desc1'] : (isset($sample['Desc1']) ? $sample['Desc1'] : '');
                        if (!empty($desc1)) {
                            $debug_info['sample_descriptions'][] = substr($desc1, 0, 50);
                        }
                        // Store a few complete sample items for debugging
                        if (count($debug_info['sample_items']) < 5) {
                            $debug_info['sample_items'][] = array(
                                'Item#' => isset($sample['itemnumber']) ? $sample['itemnumber'] : (isset($sample['Item#']) ? $sample['Item#'] : ''),
                                'MFG' => isset($sample['manufacturer']) ? $sample['manufacturer'] : (isset($sample['MFG']) ? $sample['MFG'] : ''),
                                'Desc1' => isset($sample['desc1']) ? substr($sample['desc1'], 0, 50) : (isset($sample['Desc1']) ? substr($sample['Desc1'], 0, 50) : ''),
                                'Category' => isset($sample['category']) ? $sample['category'] : (isset($sample['Category']) ? $sample['Category'] : ''),
                                'Avail' => isset($sample['available']) ? $sample['available'] : (isset($sample['Avail']) ? $sample['Avail'] : ''),
                                'Qty1' => isset($sample['qty1']) ? $sample['qty1'] : (isset($sample['Qty1']) ? $sample['Qty1'] : '')
                            );
                        }
                    }
                } else {
                    // Fallback to filtered rows if debug stats not available
                    foreach ($sample_rows_before as $sample) {
                        // Try both 'manufacturer' and 'MFG'
                        $mfg = isset($sample['manufacturer']) ? $sample['manufacturer'] : (isset($sample['MFG']) ? $sample['MFG'] : '');
                        if (!empty($mfg)) {
                            $debug_info['sample_mfg_values'][] = $mfg;
                        }
                        // Try both 'category' and 'Category'
                        $category = isset($sample['category']) ? $sample['category'] : (isset($sample['Category']) ? $sample['Category'] : '');
                        if (!empty($category)) {
                            $debug_info['sample_categories'][] = $category;
                        }
                    }
                }
                $debug_info['sample_mfg_values'] = array_values(array_unique($debug_info['sample_mfg_values']));
                $debug_info['sample_categories'] = array_values(array_unique($debug_info['sample_categories']));
            }
        } else {
            $processor = new Zanders_XML_Processor($file_path, 'full');
            
            if (!$processor->parse()) {
                wp_send_json_error(array(
                    'message' => 'Failed to parse XML',
                    'errors' => $processor->get_errors()
                ));
            }
            
            // Merge pricing XML if provided
            if (!empty($live_file) && file_exists($live_file)) {
                $pricing_processor = new Zanders_XML_Processor($live_file, 'pricing');
                if ($pricing_processor->parse()) {
                    $data = $processor->get_data();
                    $data = $pricing_processor->merge_pricing_data($data);
                    // Note: XML processor doesn't have set_data method, so we'd need to handle this differently
                }
            }
        }
        
        $preview = $file_format === 'csv' ? $processor->get_preview(10) : array_slice($processor->get_data(), 0, 10);
        $total_count = $file_format === 'csv' ? $processor->get_count() : $processor->get_count();
        
        // Calculate unique products for CSV (by item number)
        $unique_count = null;
        if ($file_format === 'csv') {
            $unique_products = array();
            foreach ($processor->get_rows() as $row) {
                $item_no = isset($row['itemnumber']) ? $row['itemnumber'] : (isset($row['Item#']) ? $row['Item#'] : '');
                if (!empty($item_no)) {
                    $unique_products[$item_no] = true;
                }
            }
            $unique_count = count($unique_products);
        }
        
        $response_data = array(
            'preview' => $preview,
            'total_count' => $total_count,
            'unique_products' => $unique_count, // Number of unique item numbers (actual products)
            'headers' => $file_format === 'csv' ? $processor->get_headers() : array(),
            'filters_applied' => !empty($filters),
            'file_path' => $file_path,
            'file_size' => file_exists($file_path) ? filesize($file_path) : 0
        );
        
        // Always add debug info if 0 products (helps diagnose the issue)
        if ($total_count === 0) {
            if (!isset($debug_info)) {
                // Create basic debug info if not already created
                $debug_info = array(
                    'total_before_filter' => $total_before_filter ?? 0,
                    'total_after_filter' => 0,
                    'filters_applied' => $filters,
                    'file_exists' => file_exists($file_path),
                    'file_size' => file_exists($file_path) ? filesize($file_path) : 0,
                    'sample_mfg_values' => array(),
                    'sample_categories' => array()
                );
            }
            $response_data['debug_info'] = $debug_info;
        } else if (isset($debug_info)) {
            $response_data['debug_info'] = $debug_info;
        }
        
        wp_send_json_success($response_data);
    }
    
    public function handle_import() {
        check_ajax_referer('zanders_import_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $file_path = isset($_POST['file_path']) ? sanitize_text_field($_POST['file_path']) : '';
        $file_format = isset($_POST['file_format']) ? sanitize_text_field($_POST['file_format']) : 'csv';
        $live_file = isset($_POST['live_file']) ? sanitize_text_field($_POST['live_file']) : '';
        $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 50;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $download_images = isset($_POST['download_images']) ? intval($_POST['download_images']) : 0;
        
        if (empty($file_path) || !file_exists($file_path)) {
            wp_send_json_error(array('message' => 'File not found'));
        }
        
        $settings = $this->get_settings();
        $ftp = null;
        
        // Setup FTP handler if images need to be downloaded
        if ($download_images && !empty($settings['ftp_username']) && !empty($settings['ftp_password'])) {
            $ftp = Zanders_FTP_Factory::create(
                $settings['ftp_host'],
                $settings['ftp_username'],
                $settings['ftp_password'],
                $settings['ftp_folder'],
                isset($settings['ftp_port']) ? intval($settings['ftp_port']) : 21,
                isset($settings['ftp_use_ssl']) ? (bool)$settings['ftp_use_ssl'] : false,
                30
            );
        }
        
        // Get filter options
        $filters = array();
        if (isset($_POST['filter_min_quantity']) && intval($_POST['filter_min_quantity']) > 0) {
            $filters['min_quantity'] = intval($_POST['filter_min_quantity']);
        }
        if (isset($_POST['filter_product_type']) && !empty($_POST['filter_product_type'])) {
            $filters['product_type'] = sanitize_text_field($_POST['filter_product_type']);
        }
        if (isset($_POST['filter_min_price']) && floatval($_POST['filter_min_price']) > 0) {
            $filters['min_price'] = floatval($_POST['filter_min_price']);
        }
        if (isset($_POST['filter_max_price']) && floatval($_POST['filter_max_price']) > 0) {
            $filters['max_price'] = floatval($_POST['filter_max_price']);
        }
        if (isset($_POST['filter_manufacturer']) && !empty($_POST['filter_manufacturer'])) {
            $filters['manufacturers'] = array(sanitize_text_field($_POST['filter_manufacturer']));
        }
        if (isset($_POST['filter_limit']) && intval($_POST['filter_limit']) > 0) {
            $filters['limit'] = intval($_POST['filter_limit']);
        }
        
        // Parse file
        if ($file_format === 'csv') {
            $processor = new Zanders_CSV_Processor($file_path, 'full');
            
            if (!$processor->parse()) {
                wp_send_json_error(array(
                    'message' => 'Failed to parse CSV',
                    'errors' => $processor->get_errors()
                ));
            }
            
            // Merge live inventory if provided
            if (!empty($live_file) && file_exists($live_file)) {
                $live_processor = new Zanders_CSV_Processor($live_file, 'live');
                if ($live_processor->parse()) {
                    $processor->merge_live_data($live_processor->get_rows());
                }
            }
            
            // Get total rows before filtering for debugging
            $total_before_filter = count($processor->get_rows());
            
            // Apply filters BEFORE getting rows
            if (!empty($filters)) {
                $processor->apply_filters($filters);
            }
            
            $rows = $processor->get_rows();
            $total_after_filter = count($rows);
            
            // Debug info if no products found
            $debug_info = array();
            if ($total_after_filter === 0 && $total_before_filter > 0) {
                // Get sample rows to see what data looks like
                $processor_before = new Zanders_CSV_Processor($file_path, 'full');
                $processor_before->parse();
                $sample_rows = array_slice($processor_before->get_rows(), 0, 5);
                
                $debug_info = array(
                    'total_before_filter' => $total_before_filter,
                    'total_after_filter' => $total_after_filter,
                    'filters_applied' => $filters,
                    'sample_mfg_values' => array(),
                    'sample_categories' => array()
                );
                
                foreach ($sample_rows as $sample) {
                    if (isset($sample['MFG'])) {
                        $debug_info['sample_mfg_values'][] = $sample['MFG'];
                    }
                    if (isset($sample['Category'])) {
                        $debug_info['sample_categories'][] = $sample['Category'];
                    }
                }
                $debug_info['sample_mfg_values'] = array_unique($debug_info['sample_mfg_values']);
                $debug_info['sample_categories'] = array_unique($debug_info['sample_categories']);
            }
        } else {
            $processor = new Zanders_XML_Processor($file_path, 'full');
            
            if (!$processor->parse()) {
                wp_send_json_error(array(
                    'message' => 'Failed to parse XML',
                    'errors' => $processor->get_errors()
                ));
            }
            
            $rows = $processor->get_data();
        }
        
        $total = count($rows);
        $batch = array_slice($rows, $offset, $batch_size);
        
        $importer = new Zanders_Product_Importer($ftp);
        
        foreach ($batch as $row) {
            $importer->import_product($row);
        }
        
        $stats = $importer->get_stats();
        $errors = $importer->get_errors();
        
        $new_offset = $offset + count($batch);
        $is_complete = $new_offset >= $total;
        
        wp_send_json_success(array(
            'offset' => $new_offset,
            'total' => $total,
            'is_complete' => $is_complete,
            'stats' => $stats,
            'errors' => array_slice($errors, -10) // Last 10 errors
        ));
    }
    
    public function test_image_download() {
        check_ajax_referer('zanders_import_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $item_number = isset($_POST['item_number']) ? sanitize_text_field($_POST['item_number']) : '';
        
        if (empty($item_number)) {
            wp_send_json_error(array('message' => 'Item number required'));
        }
        
        $settings = $this->get_settings();
        
        if (empty($settings['ftp_username']) || empty($settings['ftp_password'])) {
            wp_send_json_error(array('message' => 'FTP credentials not configured'));
        }
        
        $ftp = Zanders_FTP_Factory::create(
            $settings['ftp_host'],
            $settings['ftp_username'],
            $settings['ftp_password'],
            $settings['ftp_folder'],
            isset($settings['ftp_port']) ? intval($settings['ftp_port']) : 21,
            isset($settings['ftp_use_ssl']) ? (bool)$settings['ftp_use_ssl'] : false,
            30
        );
        
        if (!$ftp) {
            wp_send_json_error(array('message' => 'Neither PHP FTP extension nor cURL with FTP support is available'));
        }
        
        $result = Zanders_Image_Handler::test_image_download($item_number, $ftp);
        
        wp_send_json($result);
    }
}
