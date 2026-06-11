<?php
/**
 * CSV Processor for Lipsey's catalog
 */

if (!defined('ABSPATH')) {
    exit;
}

class Lipseys_CSV_Processor {
    
    private $file_path;
    private $headers = array();
    private $rows = array();
    private $errors = array();
    
    public function __construct($file_path) {
        $this->file_path = $file_path;
    }
    
    /**
     * Parse CSV file
     */
    public function parse() {
        if (!file_exists($this->file_path)) {
            $this->errors[] = 'CSV file not found: ' . $this->file_path;
            return false;
        }
        
        $handle = fopen($this->file_path, 'r');
        if ($handle === false) {
            $this->errors[] = 'Could not open CSV file';
            return false;
        }
        
        // Read header row
        $this->headers = fgetcsv($handle);
        if ($this->headers === false) {
            $this->errors[] = 'Could not read header row';
            fclose($handle);
            return false;
        }
        
        // Clean headers (remove BOM if present)
        $this->headers = array_map(function($header) {
            return trim(str_replace("\xEF\xBB\xBF", '', $header));
        }, $this->headers);
        
        // Read data rows
        $row_number = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            // Combine headers with row data
            $data = array_combine($this->headers, $row);
            
            // Filter: Only include products with QUANTITY > 0
            $quantity = isset($data['QUANTITY']) ? intval($data['QUANTITY']) : 0;
            if ($quantity > 0) {
                $this->rows[] = $data;
            }
        }
        
        fclose($handle);
        
        return true;
    }
    
    /**
     * Get all rows
     */
    public function get_rows() {
        return $this->rows;
    }
    
    /**
     * Get row count
     */
    public function get_count() {
        return count($this->rows);
    }
    
    /**
     * Get headers
     */
    public function get_headers() {
        return $this->headers;
    }
    
    /**
     * Get errors
     */
    public function get_errors() {
        return $this->errors;
    }
    
    /**
     * Get a specific row by index
     */
    public function get_row($index) {
        return isset($this->rows[$index]) ? $this->rows[$index] : null;
    }
    
    /**
     * Validate required fields in a row
     */
    public function validate_row($row) {
        $required = array('ITEMNO', 'DESCRIPTION1', 'CURRENTPRICE');
        $missing = array();
        
        foreach ($required as $field) {
            if (empty($row[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            return array(
                'valid' => false,
                'missing' => $missing
            );
        }
        
        return array('valid' => true);
    }
    
    /**
     * Get preview of first N rows
     */
    public function get_preview($limit = 10) {
        return array_slice($this->rows, 0, $limit);
    }
}
