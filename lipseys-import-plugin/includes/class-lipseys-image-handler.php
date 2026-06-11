<?php
/**
 * Image Handler - Downloads and attaches product images
 */

if (!defined('ABSPATH')) {
    exit;
}

class Lipseys_Image_Handler {
    
    // Lipsey's image server (per API docs: https://www.lipseyscloud.com/images/[image name])
    private static $image_base_url = 'https://www.lipseyscloud.com/images/';
    
    /**
     * Set custom image base URL
     */
    public static function set_image_base_url($url) {
        self::$image_base_url = trailingslashit($url);
    }
    
    /**
     * Get image base URL
     */
    public static function get_image_base_url() {
        return self::$image_base_url;
    }
    
    /**
     * Download and attach image to product
     */
    public static function attach_image_to_product($product_id, $image_name, $itemno = '') {
        if (empty($image_name)) {
            return false;
        }
        
        // Check if image already exists in media library
        $existing_id = self::find_image_by_filename($image_name);
        
        if ($existing_id) {
            // Image already exists, attach it
            set_post_thumbnail($product_id, $existing_id);
            return $existing_id;
        }
        
        // Download image
        $image_url = self::$image_base_url . $image_name;
        $attachment_id = self::download_image($image_url, $product_id, $image_name);
        
        if ($attachment_id && !is_wp_error($attachment_id)) {
            set_post_thumbnail($product_id, $attachment_id);
            return $attachment_id;
        }
        
        // If download failed, try alternative methods
        error_log("Failed to download image: {$image_url} for product {$product_id}");
        
        return false;
    }
    
    /**
     * Find image in media library by filename
     */
    private static function find_image_by_filename($filename) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_wp_attached_file' 
            AND meta_value LIKE %s 
            LIMIT 1",
            '%' . $wpdb->esc_like($filename)
        );
        
        $attachment_id = $wpdb->get_var($query);
        
        return $attachment_id ? intval($attachment_id) : false;
    }
    
    /**
     * Download image from URL
     */
    private static function download_image($url, $post_id = 0, $filename = '') {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // 2s timeout so request finishes in ~4s total (EasyWP 502s quickly). Slow images get _lipseys_image_attach_failed.
        $tmp = download_url($url, 2);
        
        if (is_wp_error($tmp)) {
            return $tmp;
        }
        
        // Set up file array
        $file_array = array(
            'name' => $filename ? basename($filename) : basename($url),
            'tmp_name' => $tmp
        );
        
        // If error storing temporarily, unlink
        if (is_wp_error($tmp)) {
            @unlink($file_array['tmp_name']);
            return $tmp;
        }
        
        // Do the validation and storage stuff
        $id = media_handle_sideload($file_array, $post_id);
        
        // If error storing permanently, unlink
        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return $id;
        }
        
        return $id;
    }
    
    /**
     * Batch download images (for testing)
     */
    public static function test_image_download($image_name) {
        $url = self::$image_base_url . $image_name;
        
        // Test if URL is accessible
        $response = wp_remote_head($url);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code === 200) {
            return array(
                'success' => true,
                'url' => $url,
                'size' => wp_remote_retrieve_header($response, 'content-length')
            );
        }
        
        return array(
            'success' => false,
            'error' => "HTTP {$code}",
            'url' => $url
        );
    }
}
