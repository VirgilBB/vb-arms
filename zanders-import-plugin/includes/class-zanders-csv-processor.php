<?php
/**
 * CSV Processor for Zanders inventory files
 * Handles both ZandersInv.csv and LiveInv.csv formats
 */

if (!defined('ABSPATH')) {
    exit;
}

class Zanders_CSV_Processor {
    
    private $file_path;
    private $file_type; // 'full' for ZandersInv.csv, 'live' for LiveInv.csv
    private $headers = array();
    private $rows = array();
    private $errors = array();
    private $debug_stats = array();
    
    public function __construct($file_path, $file_type = 'full') {
        $this->file_path = $file_path;
        $this->file_type = $file_type;
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
        $total_rows_read = 0;
        $rows_with_avail_y = 0;
        $sample_all_rows = array(); // Store first 10 rows regardless of Avail status
        $unique_item_numbers = array(); // Track unique item numbers
        
        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            $total_rows_read++;
            
            // Combine headers with row data
            $data = array_combine($this->headers, $row);
            
            // Track unique item numbers
            $item_no = isset($data['itemnumber']) ? $data['itemnumber'] : (isset($data['Item#']) ? $data['Item#'] : '');
            if (!empty($item_no)) {
                $unique_item_numbers[$item_no] = true;
            }
            
            // Store sample rows for debugging (first 10, regardless of availability)
            if (count($sample_all_rows) < 10) {
                $sample_all_rows[] = $data;
            }
            
            // Filter: Only include products with Avail = 'Y' or Qty > 0
            $available = false;
            
            if ($this->file_type === 'full') {
                // ZandersInv.csv has 'available' field (lowercase) OR check quantities
                // Try both 'available' and 'Avail' for compatibility
                $avail_value = '';
                if (isset($data['available'])) {
                    $avail_value = trim($data['available']);
                } elseif (isset($data['Avail'])) {
                    $avail_value = trim($data['Avail']);
                }
                
                // Check if Avail is numeric (quantity) or 'Y'/'N'
                // Some CSVs use Avail as quantity directly (10, 9, 0, etc.)
                $avail_numeric_value = 0;
                $avail_is_y = false;
                
                if (!empty($avail_value)) {
                    $avail_value_upper = strtoupper(trim($avail_value));
                    if ($avail_value_upper === 'Y') {
                        $avail_is_y = true;
                        $rows_with_avail_y++;
                    } elseif (is_numeric($avail_value)) {
                        $avail_numeric_value = intval($avail_value);
                    }
                }
                
                // Also check quantities as fallback
                $qty1 = 0;
                $qty2 = 0;
                $qty3 = 0;
                
                if (isset($data['qty1'])) {
                    $qty1 = intval($data['qty1']);
                } elseif (isset($data['Qty1'])) {
                    $qty1 = intval($data['Qty1']);
                }
                
                if (isset($data['qty2'])) {
                    $qty2 = intval($data['qty2']);
                } elseif (isset($data['Qty2'])) {
                    $qty2 = intval($data['Qty2']);
                }
                
                if (isset($data['qty3'])) {
                    $qty3 = intval($data['qty3']);
                } elseif (isset($data['Qty3'])) {
                    $qty3 = intval($data['Qty3']);
                }
                
                // Product is available if: 
                // - available='Y' OR 
                // - Avail is numeric and > 0 (treat numeric Avail as quantity) OR
                // - any quantity field > 0
                $available = ($avail_is_y || $avail_numeric_value > 0 || $qty1 > 0 || $qty2 > 0 || $qty3 > 0);
            } else {
                // LiveInv.csv - check Qty1, Qty2, Qty3 (try both cases)
                $qty1 = isset($data['qty1']) ? intval($data['qty1']) : (isset($data['Qty1']) ? intval($data['Qty1']) : 0);
                $qty2 = isset($data['qty2']) ? intval($data['qty2']) : (isset($data['Qty2']) ? intval($data['Qty2']) : 0);
                $qty3 = isset($data['qty3']) ? intval($data['qty3']) : (isset($data['Qty3']) ? intval($data['Qty3']) : 0);
                $available = ($qty1 > 0 || $qty2 > 0 || $qty3 > 0);
            }
            
            if ($available) {
                $this->rows[] = $data;
            }
        }
        
        // Store debug stats
        $this->debug_stats = array(
            'total_rows_read' => $total_rows_read,
            'rows_with_avail_y' => $rows_with_avail_y,
            'rows_after_avail_filter' => count($this->rows),
            'unique_item_numbers' => count($unique_item_numbers),
            'sample_all_rows' => $sample_all_rows
        );
        
        fclose($handle);
        
        return true;
    }
    
    /**
     * Apply additional filters to rows
     */
    public function apply_filters($filters = array()) {
        if (empty($filters)) {
            return;
        }
        
        $filtered_rows = array();
        
        foreach ($this->rows as $row) {
            $include = true;
            
            // Filter by minimum quantity
            if (isset($filters['min_quantity']) && $filters['min_quantity'] > 0) {
                $qty = $this->get_total_quantity($row);
                if ($qty < intval($filters['min_quantity'])) {
                    $include = false;
                }
            }
            
            // Filter by category (try both 'category' and 'Category')
            if (isset($filters['categories']) && !empty($filters['categories'])) {
                $category = isset($row['category']) ? strtolower($row['category']) : (isset($row['Category']) ? strtolower($row['Category']) : '');
                $category_match = false;
                foreach ($filters['categories'] as $filter_cat) {
                    if (stripos($category, strtolower($filter_cat)) !== false) {
                        $category_match = true;
                        break;
                    }
                }
                if (!$category_match) {
                    $include = false;
                }
            }
            
            // Filter by minimum price
            if (isset($filters['min_price']) && $filters['min_price'] > 0) {
                $price = $this->get_best_price($row, 1);
                if ($price < floatval($filters['min_price'])) {
                    $include = false;
                }
            }
            
            // Filter by maximum price
            if (isset($filters['max_price']) && $filters['max_price'] > 0) {
                $price = $this->get_best_price($row, 1);
                if ($price > floatval($filters['max_price'])) {
                    $include = false;
                }
            }
            
            // Filter by manufacturer (try 'manufacturer', 'MFG', and descriptions)
            if (isset($filters['manufacturers']) && !empty($filters['manufacturers'])) {
                $mfg = isset($row['manufacturer']) ? strtolower($row['manufacturer']) : (isset($row['MFG']) ? strtolower($row['MFG']) : '');
                $desc = isset($row['desc1']) ? strtolower($row['desc1']) : (isset($row['Description']) ? strtolower($row['Description']) : '');
                $desc2 = isset($row['desc2']) ? strtolower($row['desc2']) : (isset($row['Description2']) ? strtolower($row['Description2']) : '');
                $mfg_match = false;
                
                foreach ($filters['manufacturers'] as $filter_mfg) {
                    $filter_lower = strtolower($filter_mfg);
                    // Check manufacturer field, desc1, and desc2 for manufacturer name
                    if (stripos($mfg, $filter_lower) !== false || 
                        stripos($desc, $filter_lower) !== false || 
                        stripos($desc2, $filter_lower) !== false) {
                        $mfg_match = true;
                        break;
                    }
                }
                if (!$mfg_match) {
                    $include = false;
                }
            }
            
            // Filter by product type (firearms only, accessories only, etc.)
            if (isset($filters['product_type']) && !empty($filters['product_type'])) {
                $category = isset($row['category']) ? strtolower($row['category']) : (isset($row['Category']) ? strtolower($row['Category']) : '');
                $is_firearm = (stripos($category, 'rifle') !== false || 
                              stripos($category, 'handgun') !== false || 
                              stripos($category, 'pistol') !== false || 
                              stripos($category, 'revolver') !== false || 
                              stripos($category, 'shotgun') !== false);
                
                if ($filters['product_type'] === 'firearms' && !$is_firearm) {
                    $include = false;
                } elseif ($filters['product_type'] === 'accessories' && $is_firearm) {
                    $include = false;
                }
            }
            
            // Limit total count
            if (isset($filters['limit']) && $filters['limit'] > 0) {
                if (count($filtered_rows) >= intval($filters['limit'])) {
                    break; // Stop processing once limit is reached
                }
            }
            
            if ($include) {
                $filtered_rows[] = $row;
            }
        }
        
        $this->rows = $filtered_rows;
    }
    
    /**
     * Merge LiveInv.csv data with ZandersInv.csv data
     * LiveInv has updated pricing and quantities
     */
    public function merge_live_data($live_data) {
        // Create lookup by Item# (try both 'itemnumber' and 'Item#')
        $live_lookup = array();
        foreach ($live_data as $live_row) {
            $item_no = isset($live_row['itemnumber']) ? $live_row['itemnumber'] : (isset($live_row['Item#']) ? $live_row['Item#'] : '');
            if (!empty($item_no)) {
                $live_lookup[$item_no] = $live_row;
            }
        }
        
        // Merge into main rows
        foreach ($this->rows as &$row) {
            $item_no = isset($row['itemnumber']) ? $row['itemnumber'] : (isset($row['Item#']) ? $row['Item#'] : '');
            
            if (!empty($item_no) && isset($live_lookup[$item_no])) {
                $live = $live_lookup[$item_no];
                
                // Update prices (try both cases)
                if (isset($live['price1']) || isset($live['Price1'])) {
                    $row['price1'] = isset($live['price1']) ? $live['price1'] : $live['Price1'];
                    $row['Price1'] = $row['price1']; // Keep both for compatibility
                }
                if (isset($live['price2']) || isset($live['Price2'])) {
                    $row['price2'] = isset($live['price2']) ? $live['price2'] : $live['Price2'];
                    $row['Price2'] = $row['price2'];
                }
                if (isset($live['price3']) || isset($live['Price3'])) {
                    $row['price3'] = isset($live['price3']) ? $live['price3'] : $live['Price3'];
                    $row['Price3'] = $row['price3'];
                }
                
                // Update quantities (try both cases)
                if (isset($live['qty1']) || isset($live['Qty1'])) {
                    $row['qty1'] = isset($live['qty1']) ? $live['qty1'] : $live['Qty1'];
                    $row['Qty1'] = $row['qty1'];
                }
                if (isset($live['qty2']) || isset($live['Qty2'])) {
                    $row['qty2'] = isset($live['qty2']) ? $live['qty2'] : $live['Qty2'];
                    $row['Qty2'] = $row['qty2'];
                }
                if (isset($live['qty3']) || isset($live['Qty3'])) {
                    $row['qty3'] = isset($live['qty3']) ? $live['qty3'] : $live['Qty3'];
                    $row['Qty3'] = $row['qty3'];
                }
                
                // Update available status
                $qty1 = isset($live['qty1']) ? intval($live['qty1']) : (isset($live['Qty1']) ? intval($live['Qty1']) : 0);
                $qty2 = isset($live['qty2']) ? intval($live['qty2']) : (isset($live['Qty2']) ? intval($live['Qty2']) : 0);
                $qty3 = isset($live['qty3']) ? intval($live['qty3']) : (isset($live['Qty3']) ? intval($live['Qty3']) : 0);
                $row['available'] = ($qty1 > 0 || $qty2 > 0 || $qty3 > 0) ? 'Y' : 'N';
                $row['Avail'] = $row['available']; // Keep both for compatibility
            }
        }
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
    
    public function get_debug_stats() {
        return $this->debug_stats;
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
        // Try both 'itemnumber' and 'Item#' for compatibility
        $item_no = isset($row['itemnumber']) ? $row['itemnumber'] : (isset($row['Item#']) ? $row['Item#'] : '');
        
        if (empty($item_no)) {
            return array(
                'valid' => false,
                'missing' => array('itemnumber')
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
    
    /**
     * Get best available price for a product
     * Returns Price1, Price2, or Price3 based on quantity
     */
    public function get_best_price($row, $quantity = 1) {
        $qty1 = isset($row['qty1']) ? intval($row['qty1']) : (isset($row['Qty1']) ? intval($row['Qty1']) : 0);
        $qty2 = isset($row['qty2']) ? intval($row['qty2']) : (isset($row['Qty2']) ? intval($row['Qty2']) : 0);
        $qty3 = isset($row['qty3']) ? intval($row['qty3']) : (isset($row['Qty3']) ? intval($row['Qty3']) : 0);
        
        // Determine which price tier to use (try both lowercase and uppercase)
        $price3 = isset($row['price3']) ? $row['price3'] : (isset($row['Price3']) ? $row['Price3'] : '');
        $price2 = isset($row['price2']) ? $row['price2'] : (isset($row['Price2']) ? $row['Price2'] : '');
        $price1 = isset($row['price1']) ? $row['price1'] : (isset($row['Price1']) ? $row['Price1'] : '');
        
        if ($quantity >= $qty3 && !empty($price3) && floatval($price3) > 0) {
            return floatval($price3);
        } elseif ($quantity >= $qty2 && !empty($price2) && floatval($price2) > 0) {
            return floatval($price2);
        } elseif (!empty($price1) && floatval($price1) > 0) {
            return floatval($price1);
        }
        
        // Fallback to MSRP (try both cases)
        $msrp = isset($row['msrp']) ? $row['msrp'] : (isset($row['MSRP']) ? $row['MSRP'] : '');
        if (!empty($msrp) && floatval($msrp) > 0) {
            return floatval($msrp);
        }
        
        return 0;
    }
    
    /**
     * Get total available quantity
     */
    public function get_total_quantity($row) {
        $qty1 = isset($row['qty1']) ? intval($row['qty1']) : (isset($row['Qty1']) ? intval($row['Qty1']) : 0);
        $qty2 = isset($row['qty2']) ? intval($row['qty2']) : (isset($row['Qty2']) ? intval($row['Qty2']) : 0);
        $qty3 = isset($row['qty3']) ? intval($row['qty3']) : (isset($row['Qty3']) ? intval($row['Qty3']) : 0);
        
        // Return the highest quantity tier that has stock
        return max($qty1, $qty2, $qty3);
    }
}
