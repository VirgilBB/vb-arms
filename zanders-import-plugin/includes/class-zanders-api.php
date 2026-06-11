<?php
/**
 * REST API endpoints for automated file uploads
 */

if (!defined('ABSPATH')) {
    exit;
}

class Zanders_Import_API {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
        
        // Ensure rewrite rules are flushed when routes are registered
        add_action('rest_api_init', array($this, 'maybe_flush_rewrite_rules'), 999);
    }
    
    /**
     * Flush rewrite rules if needed (only once per day to avoid performance issues)
     */
    public function maybe_flush_rewrite_rules() {
        $last_flush = get_option('zanders_import_api_last_flush', 0);
        $one_hour_ago = time() - (60 * 60); // Flush once per hour max
        
        if ($last_flush < $one_hour_ago) {
            flush_rewrite_rules(false); // Soft flush (faster)
            update_option('zanders_import_api_last_flush', time());
        }
    }
    
    public function register_routes() {
        // Register POST endpoint
        register_rest_route('zanders-import/v1', '/upload-files', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_api_upload'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
        
        // Register GET endpoint (for testing)
        register_rest_route('zanders-import/v1', '/upload-files', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_api_test'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
        
        // Diagnostic endpoint to check file status
        register_rest_route('zanders-import/v1', '/check-files', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_check_files'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
        
        // Endpoint to update plugin files
        register_rest_route('zanders-import/v1', '/update-plugin-file', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_update_plugin_file'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
        
        // Endpoint to get all product SKUs
        register_rest_route('zanders-import/v1', '/get-product-skus', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_get_product_skus'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
        
        // Endpoint to find product by SKU
        register_rest_route('zanders-import/v1', '/find-product-by-sku', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_find_product_by_sku'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
        
        // Endpoint to upload product image
        register_rest_route('zanders-import/v1', '/upload-image', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_upload_image'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
    }
    
    public function handle_check_files($request) {
        $upload_dir = wp_upload_dir();
        $zanders_dir = $upload_dir['basedir'] . '/zanders-import';
        
        $file_info = array();
        $files_to_check = array('ZandersInv.csv', 'LiveInv.csv', 'ZandersInv.xml', 'Qtypricingout.xml');
        
        foreach ($files_to_check as $file) {
            $path = $zanders_dir . '/' . $file;
            if (file_exists($path)) {
                $file_info[$file] = array(
                    'exists' => true,
                    'size' => filesize($path),
                    'size_formatted' => size_format(filesize($path)),
                    'modified' => date('Y-m-d H:i:s', filemtime($path)),
                    'readable' => is_readable($path),
                    'path' => $path
                );
                
                // Get first few lines for CSV files
                if (substr($file, -4) === '.csv' && is_readable($path)) {
                    $handle = fopen($path, 'r');
                    $lines = array();
                    for ($i = 0; $i < 3 && !feof($handle); $i++) {
                        $line = fgets($handle);
                        $lines[] = substr($line, 0, 200); // First 200 chars
                    }
                    fclose($handle);
                    $file_info[$file]['first_lines'] = $lines;
                }
            } else {
                $file_info[$file] = array('exists' => false);
            }
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'directory' => $zanders_dir,
            'directory_exists' => is_dir($zanders_dir),
            'directory_writable' => is_writable($zanders_dir),
            'files' => $file_info,
            'timestamp' => current_time('mysql')
        ));
    }
    
    public function handle_api_test($request) {
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Zanders Import API endpoint is working',
            'timestamp' => current_time('mysql'),
        ));
    }
    
    public function check_permissions($request) {
        // Check if user is authenticated via Application Password
        if (!is_user_logged_in()) {
            // Try Basic Auth (for Application Passwords)
            $auth_header = $request->get_header('Authorization');
            if ($auth_header && strpos($auth_header, 'Basic ') === 0) {
                $credentials = base64_decode(substr($auth_header, 6));
                list($username, $password) = explode(':', $credentials, 2);
                
                $user = wp_authenticate_application_password(null, $username, $password);
                if (!is_wp_error($user)) {
                    wp_set_current_user($user->ID);
                    
                    // Allow specific API user (ZandersAuto) or users with proper capabilities
                    if ($username === 'ZandersAuto' || 
                        current_user_can('manage_woocommerce') || 
                        current_user_can('manage_options') ||
                        user_can($user->ID, 'edit_posts')) { // Lower requirement - any editor
                        return true;
                    }
                    return new WP_Error('rest_forbidden', 'Insufficient permissions. User needs manage_woocommerce, manage_options, or edit_posts capability.', array('status' => 403));
                }
                return new WP_Error('rest_forbidden', 'Invalid credentials', array('status' => 401));
            }
            return new WP_Error('rest_forbidden', 'Authentication required', array('status' => 401));
        }
        
        // For logged-in users, check capabilities
        if (current_user_can('manage_woocommerce') || 
            current_user_can('manage_options') || 
            current_user_can('edit_posts')) {
            return true;
        }
        
        return new WP_Error('rest_forbidden', 'Insufficient permissions', array('status' => 403));
    }
    
    public function handle_api_upload($request) {
        $upload_dir = wp_upload_dir();
        $zanders_dir = $upload_dir['basedir'] . '/zanders-import';
        wp_mkdir_p($zanders_dir);
        
        $uploaded_files = array();
        $errors = array();
        
        // Handle file uploads from multipart form data
        $files = $request->get_file_params();
        
        if (empty($files)) {
            // Try to get from raw POST data (multipart/form-data)
            $raw_data = $request->get_body();
            $content_type = $request->get_header('Content-Type');
            
            if (!empty($raw_data) && strpos($content_type, 'multipart/form-data') !== false) {
                // Extract boundary from Content-Type header
                if (preg_match('/boundary=([^\s;]+)/', $content_type, $matches)) {
                    $boundary = '--' . trim($matches[1]);
                    $this->parse_multipart_data($raw_data, $boundary, $zanders_dir, $uploaded_files, $errors);
                } else {
                    // Try to find boundary in data
                    $this->parse_multipart_data($raw_data, null, $zanders_dir, $uploaded_files, $errors);
                }
            }
        } else {
            // Standard file upload
            foreach ($files as $key => $file) {
                if (isset($file['error']) && $file['error'] === UPLOAD_ERR_OK) {
                    $filename = sanitize_file_name($file['name']);
                    $file_path = $zanders_dir . '/' . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $file_path)) {
                        $uploaded_files[] = $filename;
                    } else {
                        $errors[] = "Failed to save {$filename}";
                    }
                }
            }
        }
        
        if (empty($uploaded_files)) {
            return new WP_Error('no_files', 'No files uploaded', array('status' => 400));
        }
        
        // Determine main file
        $main_file = null;
        foreach ($uploaded_files as $file) {
            if (strpos($file, 'ZandersInv.csv') !== false || strpos($file, 'ZandersInv.xml') !== false) {
                $main_file = $zanders_dir . '/' . $file;
                break;
            }
        }
        
        if (!$main_file) {
            $main_file = $zanders_dir . '/' . $uploaded_files[0];
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Files uploaded successfully: ' . implode(', ', $uploaded_files),
            'files' => $uploaded_files,
            'main_file' => $main_file,
            'errors' => $errors
        ));
    }
    
    private function parse_multipart_data($data, $boundary, $target_dir, &$uploaded_files, &$errors) {
        // If boundary not provided, try to extract it
        if (!$boundary) {
            // Look for boundary pattern in data
            if (preg_match('/--([a-zA-Z0-9]+)\r\n/', $data, $matches)) {
                $boundary = '--' . $matches[1];
            } else {
                return; // Can't parse without boundary
            }
        }
        
        // Split by boundary
        $parts = explode($boundary, $data);
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part) || $part === '--' || $part === '') {
                continue;
            }
            
            // Extract filename from Content-Disposition header
            if (preg_match('/filename="([^"]+)"/', $part, $filename_matches)) {
                $filename = $filename_matches[1];
                
                // Find the start of file content (after \r\n\r\n)
                $content_start = strpos($part, "\r\n\r\n");
                if ($content_start !== false) {
                    $file_content = substr($part, $content_start + 4);
                    // Remove trailing boundary markers and whitespace
                    $file_content = rtrim($file_content, "\r\n- \t");
                    
                    $file_path = $target_dir . '/' . sanitize_file_name($filename);
                    
                    if (file_put_contents($file_path, $file_content)) {
                        $uploaded_files[] = $filename;
                    } else {
                        $errors[] = "Failed to save {$filename}";
                    }
                }
            }
        }
    }
    
    public function handle_update_plugin_file($request) {
        $params = $request->get_json_params();
        
        if (empty($params['file_path']) || empty($params['file_content'])) {
            return new WP_Error('missing_params', 'file_path and file_content are required', array('status' => 400));
        }
        
        $file_path = sanitize_text_field($params['file_path']);
        $file_content = base64_decode($params['file_content']);
        
        // Security: Only allow updating files in the plugin directory
        $plugin_dir = WP_PLUGIN_DIR . '/zanders-import-plugin/';
        $full_path = realpath($plugin_dir . $file_path);
        $plugin_dir_real = realpath($plugin_dir);
        
        if (!$full_path || strpos($full_path, $plugin_dir_real) !== 0) {
            return new WP_Error('invalid_path', 'File path must be within the plugin directory', array('status' => 403));
        }
        
        // Create directory if it doesn't exist
        $dir = dirname($full_path);
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }
        
        // Write file
        if (file_put_contents($full_path, $file_content) !== false) {
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'File updated successfully',
                'file_path' => $file_path,
                'file_size' => strlen($file_content),
            ));
        } else {
            return new WP_Error('write_failed', 'Failed to write file', array('status' => 500));
        }
    }
    
    public function handle_get_product_skus($request) {
        global $wpdb;
        
        // Get all WooCommerce products with SKU
        $products = $wpdb->get_results(
            "SELECT p.ID, pm.meta_value as sku 
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'product'
             AND p.post_status = 'publish'
             AND pm.meta_key = '_sku'
             AND pm.meta_value != ''
             ORDER BY pm.meta_value"
        );
        
        $skus = array();
        foreach ($products as $product) {
            $skus[] = $product->sku;
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'skus' => $skus,
            'count' => count($skus)
        ));
    }
    
    public function handle_find_product_by_sku($request) {
        $sku = $request->get_param('sku');
        
        if (empty($sku)) {
            return new WP_Error('missing_sku', 'SKU parameter is required', array('status' => 400));
        }
        
        $product_id = wc_get_product_id_by_sku($sku);
        
        if ($product_id) {
            return rest_ensure_response(array(
                'success' => true,
                'product_id' => $product_id,
                'sku' => $sku
            ));
        } else {
            return rest_ensure_response(array(
                'success' => false,
                'product_id' => null,
                'sku' => $sku
            ));
        }
    }
    
    public function handle_upload_image($request) {
        $item_number = $request->get_param('item_number');
        $product_id = intval($request->get_param('product_id'));
        
        if (empty($item_number) || empty($product_id)) {
            return new WP_Error('missing_params', 'item_number and product_id are required', array('status' => 400));
        }
        
        // Get uploaded file
        $files = $request->get_file_params();
        
        if (empty($files['image']) || $files['image']['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('no_file', 'No image file uploaded', array('status' => 400));
        }
        
        $file = $files['image'];
        
        // Validate file type
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif');
        
        if (!in_array($file['type'], $allowed_types)) {
            return new WP_Error('invalid_type', 'Invalid image type. Allowed: jpg, png, gif', array('status' => 400));
        }
        
        // Import image into WordPress media library
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $attachment_id = media_handle_upload('image', $product_id);
        
        if (is_wp_error($attachment_id)) {
            return new WP_Error('upload_failed', $attachment_id->get_error_message(), array('status' => 500));
        }
        
        // Set as product featured image
        set_post_thumbnail($product_id, $attachment_id);
        
        // Store item number in attachment meta
        update_post_meta($attachment_id, '_zanders_item_number', $item_number);
        
        return rest_ensure_response(array(
            'success' => true,
            'attachment_id' => $attachment_id,
            'product_id' => $product_id,
            'item_number' => $item_number
        ));
    }
}
