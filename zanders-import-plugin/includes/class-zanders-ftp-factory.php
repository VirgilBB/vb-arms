<?php
/**
 * FTP Handler Factory - Creates appropriate FTP handler based on available extensions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Zanders_FTP_Factory {
    
    /**
     * Create FTP handler instance (PHP FTP extension or cURL fallback)
     */
    public static function create($host, $username, $password, $folder = '', $port = 21, $use_ssl = false, $timeout = 30) {
        // Try PHP FTP extension first
        if (function_exists('ftp_connect')) {
            return new Zanders_FTP_Handler($host, $username, $password, $folder, $port, $use_ssl, $timeout);
        }
        
        // Fallback to cURL
        if (function_exists('curl_init')) {
            $curl_version = curl_version();
            if (isset($curl_version['protocols']) && in_array('ftp', $curl_version['protocols'])) {
                return new Zanders_CURL_FTP_Handler($host, $username, $password, $folder, $port, $use_ssl, $timeout);
            }
        }
        
        // Neither available
        return null;
    }
    
    /**
     * Check if any FTP method is available
     */
    public static function is_available() {
        // Check PHP FTP extension
        if (function_exists('ftp_connect')) {
            return true;
        }
        
        // Check cURL with FTP support
        if (function_exists('curl_init')) {
            $curl_version = curl_version();
            if (isset($curl_version['protocols']) && in_array('ftp', $curl_version['protocols'])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get available method name
     */
    public static function get_available_method() {
        if (function_exists('ftp_connect')) {
            return 'php_ftp';
        }
        
        if (function_exists('curl_init')) {
            $curl_version = curl_version();
            if (isset($curl_version['protocols']) && in_array('ftp', $curl_version['protocols'])) {
                return 'curl';
            }
        }
        
        return 'none';
    }
}
