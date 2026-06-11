<?php
/**
 * cURL-based FTP Handler - Alternative to PHP FTP extension
 * Uses cURL which is usually enabled by default
 */

if (!defined('ABSPATH')) {
    exit;
}

class Zanders_CURL_FTP_Handler {
    
    private $host;
    private $username;
    private $password;
    private $folder;
    private $port;
    private $use_ssl;
    private $timeout;
    private $errors = array();
    
    public function __construct($host = 'ftp2.gzanders.com', $username = '', $password = '', $folder = '', $port = 21, $use_ssl = false, $timeout = 30) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->folder = $folder;
        $this->port = $port;
        $this->use_ssl = $use_ssl;
        $this->timeout = $timeout;
    }
    
    /**
     * Check if cURL is available
     */
    public function check_curl_available() {
        if (!function_exists('curl_init')) {
            $this->errors[] = 'cURL is not available. Please enable the cURL extension.';
            return false;
        }
        
        // Check if cURL supports FTP
        $curl_version = curl_version();
        if (!isset($curl_version['protocols']) || !in_array('ftp', $curl_version['protocols'])) {
            $this->errors[] = 'cURL does not support FTP protocol.';
            return false;
        }
        
        return true;
    }
    
    /**
     * Build FTP URL
     */
    private function build_ftp_url($path = '') {
        $protocol = $this->use_ssl ? 'ftps' : 'ftp';
        $url = $protocol . '://' . $this->host;
        
        if ($this->port != 21 && $this->port != 990) {
            $url .= ':' . $this->port;
        }
        
        if (!empty($this->folder)) {
            $url .= '/' . trim($this->folder, '/');
        }
        
        if (!empty($path)) {
            $url .= '/' . ltrim($path, '/');
        }
        
        return $url;
    }
    
    /**
     * Download a file from FTP using cURL
     */
    public function download_file($remote_file, $local_file) {
        if (!$this->check_curl_available()) {
            return false;
        }
        
        if (empty($this->username) || empty($this->password)) {
            $this->errors[] = 'FTP credentials not configured';
            return false;
        }
        
        // Create directory if it doesn't exist
        $local_dir = dirname($local_file);
        if (!file_exists($local_dir)) {
            wp_mkdir_p($local_dir);
        }
        
        // Build FTP URL
        $remote_path = !empty($this->folder) ? $this->folder . '/' . $remote_file : $remote_file;
        $ftp_url = $this->build_ftp_url($remote_path);
        
        // Initialize cURL
        $ch = curl_init();
        
        // Open local file for writing
        $fp = fopen($local_file, 'w');
        if ($fp === false) {
            $this->errors[] = 'Failed to create local file: ' . $local_file;
            curl_close($ch);
            return false;
        }
        
        // Set cURL options
        curl_setopt_array($ch, array(
            CURLOPT_URL => $ftp_url,
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FILE => $fp,
            CURLOPT_FTPSSLAUTH => $this->use_ssl ? CURLFTPSSL_ALL : CURLFTPSSL_NONE,
            CURLOPT_FTP_SSL => $this->use_ssl ? CURLFTPSSL_ALL : CURLFTPSSL_NONE,
            CURLOPT_FTPPORT => '-', // Use passive mode
            CURLOPT_FTP_USE_EPSV => true, // Use EPSV for passive mode (better compatibility)
            CURLOPT_TIMEOUT => $this->timeout * 2, // Longer timeout for downloads
            CURLOPT_CONNECTTIMEOUT => 10, // Shorter connect timeout
            CURLOPT_SSL_VERIFYPEER => false, // Some FTP servers have self-signed certs
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_VERBOSE => false, // Set to true for debugging
        ));
        
        // Execute download
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        
        fclose($fp);
        curl_close($ch);
        
        if ($result === false || !empty($curl_error)) {
            $this->errors[] = 'Failed to download file: ' . $remote_file . ' - ' . $curl_error;
            if (file_exists($local_file)) {
                @unlink($local_file);
            }
            return false;
        }
        
        if (!file_exists($local_file) || filesize($local_file) == 0) {
            $this->errors[] = 'Downloaded file is empty or missing: ' . $remote_file;
            if (file_exists($local_file)) {
                @unlink($local_file);
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * List files in a directory using cURL
     */
    public function list_files($remote_dir = '.') {
        if (!$this->check_curl_available()) {
            return array();
        }
        
        if (empty($this->username) || empty($this->password)) {
            $this->errors[] = 'FTP credentials not configured';
            return array();
        }
        
        // Build FTP URL for directory listing
        $path = !empty($this->folder) ? $this->folder . '/' . $remote_dir : $remote_dir;
        $ftp_url = $this->build_ftp_url($path);
        
        // Try multiple methods
        $methods = array(
            array('use_epsv' => true, 'use_pasv' => true),
            array('use_epsv' => false, 'use_pasv' => true),
            array('use_epsv' => true, 'use_pasv' => false),
        );
        
        foreach ($methods as $method) {
            // Initialize cURL
            $ch = curl_init();
            
            $options = array(
                CURLOPT_URL => $ftp_url,
                CURLOPT_USERPWD => $this->username . ':' . $this->password,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FTPLISTONLY => true, // Only list filenames
                CURLOPT_TIMEOUT => 15, // Shorter timeout for listing
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
            );
            
            // Configure passive mode
            if ($method['use_pasv']) {
                $options[CURLOPT_FTPPORT] = '-'; // Passive mode
            }
            
            if ($method['use_epsv']) {
                $options[CURLOPT_FTP_USE_EPSV] = true;
            } else {
                $options[CURLOPT_FTP_USE_EPSV] = false;
            }
            
            curl_setopt_array($ch, $options);
            
            // Execute
            $result = curl_exec($ch);
            $curl_error = curl_error($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            curl_close($ch);
            
            if ($result !== false && empty($curl_error)) {
                // Parse file list
                $files = array_filter(array_map('trim', explode("\n", $result)));
                $files = array_filter($files, function($file) {
                    return !in_array($file, array('.', '..', ''));
                });
                
                if (!empty($files)) {
                    return array_values($files);
                }
            }
        }
        
        // All methods failed
        $this->errors[] = 'Failed to list files after trying multiple methods: ' . ($curl_error ?: 'No files returned');
        return array();
    }
    
    /**
     * Get file size using cURL
     */
    public function get_file_size($remote_file) {
        if (!$this->check_curl_available()) {
            return false;
        }
        
        $remote_path = !empty($this->folder) ? $this->folder . '/' . $remote_file : $remote_file;
        $ftp_url = $this->build_ftp_url($remote_path);
        
        $ch = curl_init();
        
        curl_setopt_array($ch, array(
            CURLOPT_URL => $ftp_url,
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_NOBODY => true, // HEAD request
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FTPPORT => '-',
            CURLOPT_TIMEOUT => $this->timeout,
        ));
        
        curl_exec($ch);
        $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);
        
        return $size > 0 ? $size : false;
    }
    
    /**
     * Test FTP connection using cURL
     */
    public function test_connection() {
        $this->clear_errors();
        
        if (!$this->check_curl_available()) {
            return array(
                'success' => false,
                'message' => 'cURL is not available: ' . implode(', ', $this->errors),
                'method' => 'curl'
            );
        }
        
        if (empty($this->username) || empty($this->password)) {
            return array(
                'success' => false,
                'message' => 'FTP credentials not configured',
                'method' => 'curl'
            );
        }
        
        // Build test URL
        $test_url = $this->build_ftp_url('');
        
        // Try multiple connection methods
        $connection_methods = array(
            array('epsv' => true, 'pasv' => true, 'desc' => 'Passive mode with EPSV'),
            array('epsv' => false, 'pasv' => true, 'desc' => 'Passive mode without EPSV'),
            array('epsv' => false, 'pasv' => false, 'desc' => 'Active mode'),
        );
        
        $last_error = '';
        $last_errno = 0;
        
        foreach ($connection_methods as $method) {
            $ch = curl_init();
            
            $options = array(
                CURLOPT_URL => $test_url,
                CURLOPT_USERPWD => $this->username . ':' . $this->password,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_NOBODY => true, // HEAD request
                CURLOPT_TIMEOUT => 20,
                CURLOPT_CONNECTTIMEOUT => 15,
            );
            
            if ($method['pasv']) {
                $options[CURLOPT_FTPPORT] = '-'; // Passive mode
            }
            
            $options[CURLOPT_FTP_USE_EPSV] = $method['epsv'];
            
            curl_setopt_array($ch, $options);
            
            $result = curl_exec($ch);
            $curl_error = curl_error($ch);
            $curl_errno = curl_errno($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($result !== false && empty($curl_error)) {
                // Connection successful with this method
                break;
            }
            
            $last_error = $curl_error;
            $last_errno = $curl_errno;
        }
        
        if ($result === false || !empty($last_error)) {
            // Get server IP for diagnostics
            $server_ip = gethostbyname($this->host);
            $server_ip = ($server_ip === $this->host) ? 'Could not resolve' : $server_ip;
            
            return array(
                'success' => false,
                'message' => 'Connection failed: ' . $last_error . ' (Error code: ' . $last_errno . ')',
                'method' => 'curl',
                'errors' => $this->errors,
                'diagnostics' => array(
                    'host' => $this->host,
                    'resolved_ip' => $server_ip,
                    'port' => $this->port,
                    'ssl' => $this->use_ssl,
                    'timeout' => '15 seconds',
                    'tried_methods' => count($connection_methods)
                ),
                'suggestion' => $this->get_connection_troubleshooting_suggestion($last_errno)
            );
        }
        
        // Try to list files
        $files = $this->list_files('.');
        
        if (empty($files) && !empty($this->errors)) {
            return array(
                'success' => false,
                'message' => 'Connected but failed to list files: ' . implode(', ', $this->errors),
                'method' => 'curl',
                'errors' => $this->errors,
                'suggestion' => 'Connection works but directory listing failed. Check folder path and permissions.'
            );
        }
        
        if (empty($files)) {
            return array(
                'success' => false,
                'message' => 'Connected but no files found. Check folder path.',
                'method' => 'curl',
                'suggestion' => 'Verify the "Dedicated Folder" path is correct, or leave it empty for root directory.'
            );
        }
        
        // Check for expected files
        $expected_files = array('ZandersInv.csv', 'LiveInv.csv', 'ZandersInv.xml', 'Qtypricingout.xml', 'Images');
        $found_files = array();
        foreach ($expected_files as $expected) {
            if (in_array($expected, $files)) {
                $found_files[] = $expected;
            }
        }
        
        return array(
            'success' => true,
            'message' => 'Connection successful using cURL! Found ' . count($files) . ' files.',
            'files' => array_slice($files, 0, 10),
            'found_expected' => $found_files,
            'method' => 'curl'
        );
    }
    
    /**
     * Download ZandersInv.csv
     */
    public function download_inventory_csv($local_path) {
        return $this->download_file('ZandersInv.csv', $local_path);
    }
    
    /**
     * Download LiveInv.csv
     */
    public function download_live_inventory_csv($local_path) {
        return $this->download_file('LiveInv.csv', $local_path);
    }
    
    /**
     * Download ZandersInv.xml
     */
    public function download_inventory_xml($local_path) {
        return $this->download_file('ZandersInv.xml', $local_path);
    }
    
    /**
     * Download Qtypricingout.xml
     */
    public function download_pricing_xml($local_path) {
        return $this->download_file('Qtypricingout.xml', $local_path);
    }
    
    /**
     * Download product image by Item#
     */
    public function download_image($item_number, $local_path) {
        if (!$this->check_curl_available()) {
            return false;
        }
        
        // Try common image extensions
        $extensions = array('jpg', 'jpeg', 'png', 'gif');
        
        foreach ($extensions as $ext) {
            $remote_file = 'Images/' . $item_number . '.' . $ext;
            $size = $this->get_file_size($remote_file);
            
            if ($size !== false && $size > 0) {
                $local_dir = dirname($local_path);
                if (!file_exists($local_dir)) {
                    wp_mkdir_p($local_dir);
                }
                
                $local_file = $local_dir . '/' . $item_number . '.' . $ext;
                if ($this->download_file($remote_file, $local_file)) {
                    return $local_file;
                }
            }
        }
        
        $this->errors[] = 'Image not found for Item#: ' . $item_number;
        return false;
    }
    
    /**
     * Get troubleshooting suggestion based on error code
     */
    private function get_connection_troubleshooting_suggestion($errno) {
        $suggestions = array(
            28 => 'Connection timeout. This usually means: 1) Your hosting provider is blocking outbound FTP connections (contact EasyWP support), 2) Zanders needs to whitelist your server IP (contact Zanders support), 3) Firewall is blocking the connection.',
            7 => 'Failed to connect to host. Check if the FTP host address is correct and your server can reach it.',
            9 => 'Access denied. Check your FTP username and password.',
            67 => 'Login denied. Verify your FTP credentials are correct.',
        );
        
        if (isset($suggestions[$errno])) {
            return $suggestions[$errno];
        }
        
        return 'Check FTP credentials, host, port, and firewall settings. Contact your hosting provider if outbound FTP connections are blocked.';
    }
    
    /**
     * Get errors
     */
    public function get_errors() {
        return $this->errors;
    }
    
    /**
     * Clear errors
     */
    public function clear_errors() {
        $this->errors = array();
    }
}
