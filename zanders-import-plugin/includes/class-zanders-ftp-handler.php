<?php
/**
 * FTP Handler for Zanders inventory access
 */

if (!defined('ABSPATH')) {
    exit;
}

class Zanders_FTP_Handler {
    
    private $host;
    private $username;
    private $password;
    private $folder;
    private $port;
    private $use_ssl;
    private $timeout;
    private $connection = null;
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
     * Check if FTP extension is available
     */
    public function check_ftp_extension() {
        if (!function_exists('ftp_connect')) {
            $this->errors[] = 'PHP FTP extension is not enabled. Please enable the FTP extension in your PHP configuration.';
            return false;
        }
        
        if ($this->use_ssl && !function_exists('ftp_ssl_connect')) {
            $this->errors[] = 'PHP FTP SSL extension is not enabled. Please enable OpenSSL support for FTP.';
            return false;
        }
        
        return true;
    }
    
    /**
     * Connect to FTP server
     */
    public function connect() {
        if ($this->connection !== null) {
            return true;
        }
        
        // Check FTP extension
        if (!$this->check_ftp_extension()) {
            return false;
        }
        
        if (empty($this->username) || empty($this->password)) {
            $this->errors[] = 'FTP credentials not configured';
            return false;
        }
        
        // Try to connect (with or without SSL)
        if ($this->use_ssl) {
            $this->connection = @ftp_ssl_connect($this->host, $this->port, $this->timeout);
        } else {
            $this->connection = @ftp_connect($this->host, $this->port, $this->timeout);
        }
        
        if ($this->connection === false) {
            $error_msg = 'Failed to connect to FTP server: ' . $this->host . ':' . $this->port;
            
            // Try to get more specific error
            $last_error = error_get_last();
            if ($last_error && strpos($last_error['message'], 'ftp') !== false) {
                $error_msg .= ' - ' . $last_error['message'];
            }
            
            // If SSL failed, try regular FTP
            if ($this->use_ssl) {
                $this->errors[] = $error_msg . ' (SSL connection failed, try without SSL)';
            } else {
                $this->errors[] = $error_msg;
            }
            
            return false;
        }
        
        // Set timeout
        @ftp_set_option($this->connection, FTP_TIMEOUT_SEC, $this->timeout);
        
        // Login
        $login = @ftp_login($this->connection, $this->username, $this->password);
        
        if ($login === false) {
            $this->errors[] = 'Failed to login to FTP server. Check username and password.';
            @ftp_close($this->connection);
            $this->connection = null;
            return false;
        }
        
        // Enable passive mode (required for most firewalls)
        $pasv_result = @ftp_pasv($this->connection, true);
        if ($pasv_result === false) {
            $this->errors[] = 'Warning: Failed to enable passive mode. Connection may still work.';
            // Don't fail - some servers don't support passive mode
        }
        
        // Change to dedicated folder if specified
        if (!empty($this->folder)) {
            // Try with and without leading/trailing slashes
            $folders_to_try = array(
                $this->folder,
                '/' . trim($this->folder, '/'),
                trim($this->folder, '/'),
                './' . trim($this->folder, '/')
            );
            
            $changed = false;
            foreach ($folders_to_try as $try_folder) {
                if (@ftp_chdir($this->connection, $try_folder)) {
                    $changed = true;
                    break;
                }
            }
            
            if (!$changed) {
                $this->errors[] = 'Warning: Failed to change to folder: ' . $this->folder . ' (continuing from root)';
                // Don't fail - folder might not exist or might be root
            }
        }
        
        return true;
    }
    
    /**
     * Disconnect from FTP server
     */
    public function disconnect() {
        if ($this->connection !== null) {
            ftp_close($this->connection);
            $this->connection = null;
        }
    }
    
    /**
     * Download a file from FTP
     */
    public function download_file($remote_file, $local_file) {
        if (!$this->connect()) {
            return false;
        }
        
        // Create directory if it doesn't exist
        $local_dir = dirname($local_file);
        if (!file_exists($local_dir)) {
            wp_mkdir_p($local_dir);
        }
        
        // Download file
        $result = @ftp_get($this->connection, $local_file, $remote_file, FTP_BINARY);
        
        if ($result === false) {
            $this->errors[] = 'Failed to download file: ' . $remote_file;
            return false;
        }
        
        return true;
    }
    
    /**
     * List files in a directory
     */
    public function list_files($remote_dir = '.') {
        if (!$this->connect()) {
            return array();
        }
        
        $files = @ftp_nlist($this->connection, $remote_dir);
        
        if ($files === false) {
            $this->errors[] = 'Failed to list files in: ' . $remote_dir;
            return array();
        }
        
        // Filter out . and ..
        $files = array_filter($files, function($file) {
            return !in_array(basename($file), array('.', '..'));
        });
        
        return array_values($files);
    }
    
    /**
     * Get file size
     */
    public function get_file_size($remote_file) {
        if (!$this->connect()) {
            return false;
        }
        
        $size = @ftp_size($this->connection, $remote_file);
        
        if ($size === -1) {
            return false;
        }
        
        return $size;
    }
    
    /**
     * Get file modification time
     */
    public function get_file_time($remote_file) {
        if (!$this->connect()) {
            return false;
        }
        
        $time = @ftp_mdtm($this->connection, $remote_file);
        
        return $time !== -1 ? $time : false;
    }
    
    /**
     * Download ZandersInv.csv
     */
    public function download_inventory_csv($local_path) {
        return $this->download_file('ZandersInv.csv', $local_path);
    }
    
    /**
     * Download LiveInv.csv (updated every 5 minutes)
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
     * Download Qtypricingout.xml (updated every 5 minutes)
     */
    public function download_pricing_xml($local_path) {
        return $this->download_file('Qtypricingout.xml', $local_path);
    }
    
    /**
     * Download product image by Item#
     */
    public function download_image($item_number, $local_path) {
        if (!$this->connect()) {
            return false;
        }
        
        // Try common image extensions
        $extensions = array('jpg', 'jpeg', 'png', 'gif');
        
        foreach ($extensions as $ext) {
            $remote_file = 'Images/' . $item_number . '.' . $ext;
            
            // Check if file exists
            $size = $this->get_file_size($remote_file);
            if ($size !== false && $size > 0) {
                // Create directory if needed
                $local_dir = dirname($local_path);
                if (!file_exists($local_dir)) {
                    wp_mkdir_p($local_dir);
                }
                
                // Download with correct extension
                $local_file = dirname($local_path) . '/' . $item_number . '.' . $ext;
                if ($this->download_file($remote_file, $local_file)) {
                    return $local_file;
                }
            }
        }
        
        $this->errors[] = 'Image not found for Item#: ' . $item_number;
        return false;
    }
    
    /**
     * List all images in Images folder
     */
    public function list_images() {
        return $this->list_files('Images');
    }
    
    /**
     * Test FTP connection with detailed diagnostics
     */
    public function test_connection() {
        // Clear previous errors
        $this->clear_errors();
        
        // Check FTP extension first
        if (!$this->check_ftp_extension()) {
            return array(
                'success' => false,
                'message' => 'PHP FTP extension not available: ' . implode(', ', $this->errors),
                'diagnostics' => $this->get_diagnostics()
            );
        }
        
        // Try to connect
        if (!$this->connect()) {
            $errors = $this->get_errors();
            return array(
                'success' => false,
                'message' => 'Failed to connect: ' . implode(', ', $errors),
                'diagnostics' => $this->get_diagnostics(),
                'errors' => $errors
            );
        }
        
        // Get current directory
        $current_dir = @ftp_pwd($this->connection);
        if ($current_dir === false) {
            $current_dir = 'Unable to determine';
        }
        
        // Try to list files
        $files = $this->list_files('.');
        $file_count = count($files);
        
        // Check for common Zanders files
        $expected_files = array('ZandersInv.csv', 'LiveInv.csv', 'ZandersInv.xml', 'Qtypricingout.xml', 'Images');
        $found_files = array();
        foreach ($expected_files as $expected) {
            if (in_array($expected, $files) || in_array('./' . $expected, $files)) {
                $found_files[] = $expected;
            }
        }
        
        if ($file_count === 0) {
            return array(
                'success' => false,
                'message' => 'Connected but no files found. Current directory: ' . $current_dir . '. Check folder path.',
                'diagnostics' => $this->get_diagnostics(),
                'current_dir' => $current_dir
            );
        }
        
        return array(
            'success' => true,
            'message' => 'Connection successful! Found ' . $file_count . ' files in ' . $current_dir,
            'files' => array_slice($files, 0, 10), // First 10 files
            'found_expected' => $found_files,
            'current_dir' => $current_dir,
            'diagnostics' => $this->get_diagnostics()
        );
    }
    
    /**
     * Get system diagnostics
     */
    private function get_diagnostics() {
        $diagnostics = array(
            'php_version' => PHP_VERSION,
            'ftp_extension' => function_exists('ftp_connect') ? 'Enabled' : 'Not Enabled',
            'ftp_ssl_extension' => function_exists('ftp_ssl_connect') ? 'Enabled' : 'Not Enabled',
            'host' => $this->host,
            'port' => $this->port,
            'ssl' => $this->use_ssl ? 'Yes' : 'No',
            'timeout' => $this->timeout,
            'folder' => $this->folder ?: 'Root',
            'username_set' => !empty($this->username) ? 'Yes' : 'No',
            'password_set' => !empty($this->password) ? 'Yes' : 'No'
        );
        
        return $diagnostics;
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
    
    /**
     * Destructor - ensure connection is closed
     */
    public function __destruct() {
        $this->disconnect();
    }
}
