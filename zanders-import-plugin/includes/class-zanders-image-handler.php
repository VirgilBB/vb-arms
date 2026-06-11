<?php
/**
 * Image Handler - Downloads product images from Zanders FTP
 */

if (!defined('ABSPATH')) {
    exit;
}

class Zanders_Image_Handler {
    
    private static $ftp_handler = null;
    private static $upload_dir = null;
    
    /**
     * Set FTP handler instance
     */
    public static function set_ftp_handler($ftp_handler) {
        self::$ftp_handler = $ftp_handler;
    }
    
    /**
     * Get upload directory for Zanders images
     */
    private static function get_upload_dir() {
        if (self::$upload_dir === null) {
            $upload_dir = wp_upload_dir();
            self::$upload_dir = $upload_dir['basedir'] . '/zanders-images';
            
            // Create directory if it doesn't exist
            if (!file_exists(self::$upload_dir)) {
                wp_mkdir_p(self::$upload_dir);
            }
        }
        
        return self::$upload_dir;
    }
    
    /**
     * Download and attach image to product from FTP
     */
    public static function attach_image_to_product($product_id, $item_number, $ftp_handler = null) {
        if (empty($item_number)) {
            return false;
        }
        
        // Use provided FTP handler or stored one
        $ftp = $ftp_handler ? $ftp_handler : self::$ftp_handler;
        
        if (!$ftp) {
            error_log("Zanders Image Handler: No FTP handler available for product {$product_id}");
            return false;
        }
        
        // Check if image already exists in media library
        $existing_id = self::find_image_by_item_number($item_number);
        
        if ($existing_id) {
            // Image already exists, attach it
            set_post_thumbnail($product_id, $existing_id);
            return $existing_id;
        }
        
        // Download image from FTP
        $local_path = self::get_upload_dir() . '/' . $item_number;
        $downloaded_file = $ftp->download_image($item_number, $local_path);
        
        if ($downloaded_file && file_exists($downloaded_file)) {
            // Import into WordPress media library
            $attachment_id = self::import_image_to_media_library($downloaded_file, $product_id, $item_number);
            
            if ($attachment_id && !is_wp_error($attachment_id)) {
                set_post_thumbnail($product_id, $attachment_id);
                
                // Clean up local file after import
                @unlink($downloaded_file);
                
                return $attachment_id;
            }
        }
        
        error_log("Failed to download/import image for Item#: {$item_number} (Product ID: {$product_id})");
        return false;
    }
    
    /**
     * Find image in media library by Item#
     */
    private static function find_image_by_item_number($item_number) {
        global $wpdb;
        
        // Search in post meta for custom field
        $query = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_zanders_item_number' 
            AND meta_value = %s 
            LIMIT 1",
            $item_number
        );
        
        $attachment_id = $wpdb->get_var($query);
        
        if ($attachment_id) {
            return intval($attachment_id);
        }
        
        // Also search by filename
        $query = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_wp_attached_file' 
            AND meta_value LIKE %s 
            LIMIT 1",
            '%' . $wpdb->esc_like($item_number) . '%'
        );
        
        $attachment_id = $wpdb->get_var($query);
        
        return $attachment_id ? intval($attachment_id) : false;
    }
    
    /**
     * Import image file into WordPress media library
     */
    private static function import_image_to_media_library($file_path, $post_id = 0, $item_number = '') {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', 'Image file not found: ' . $file_path);
        }
        
        // Get file info
        $file_name = basename($file_path);
        $file_type = wp_check_filetype($file_name);
        
        // Set up file array
        $file_array = array(
            'name' => $file_name,
            'tmp_name' => $file_path
        );
        
        // Import into media library
        $attachment_id = media_handle_sideload($file_array, $post_id);
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }
        
        // Store Item# in attachment meta for future lookups
        if (!empty($item_number)) {
            update_post_meta($attachment_id, '_zanders_item_number', $item_number);
        }
        
        return $attachment_id;
    }
    
    /**
     * Batch download images (for testing)
     */
    public static function test_image_download($item_number, $ftp_handler) {
        if (!$ftp_handler) {
            return array(
                'success' => false,
                'error' => 'FTP handler not provided'
            );
        }
        
        $local_path = self::get_upload_dir() . '/' . $item_number;
        $result = $ftp_handler->download_image($item_number, $local_path);
        
        if ($result && file_exists($result)) {
            $file_size = filesize($result);
            $file_type = wp_check_filetype($result);
            
            // Clean up test file
            @unlink($result);
            
            return array(
                'success' => true,
                'item_number' => $item_number,
                'file_size' => $file_size,
                'file_type' => $file_type['type']
            );
        }
        
        $errors = $ftp_handler->get_errors();
        return array(
            'success' => false,
            'error' => !empty($errors) ? implode(', ', $errors) : 'Image not found on FTP server'
        );
    }
}
