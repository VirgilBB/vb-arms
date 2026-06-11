<?php
/**
 * XML Processor for Zanders inventory files
 * Handles both ZandersInv.xml and Qtypricingout.xml formats
 */

if (!defined('ABSPATH')) {
    exit;
}

class Zanders_XML_Processor {
    
    private $file_path;
    private $file_type; // 'full' for ZandersInv.xml, 'pricing' for Qtypricingout.xml
    private $data = array();
    private $errors = array();
    
    public function __construct($file_path, $file_type = 'full') {
        $this->file_path = $file_path;
        $this->file_type = $file_type;
    }
    
    /**
     * Parse XML file
     */
    public function parse() {
        if (!file_exists($this->file_path)) {
            $this->errors[] = 'XML file not found: ' . $this->file_path;
            return false;
        }
        
        // Load XML file
        libxml_use_internal_errors(true);
        $xml = @simplexml_load_file($this->file_path);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                $this->errors[] = 'XML Error: ' . trim($error->message);
            }
            libxml_clear_errors();
            return false;
        }
        
        // Convert XML to array
        if ($this->file_type === 'full') {
            $this->parse_full_inventory($xml);
        } else {
            $this->parse_pricing($xml);
        }
        
        return true;
    }
    
    /**
     * Parse ZandersInv.xml (full inventory)
     */
    private function parse_full_inventory($xml) {
        $this->data = array();
        
        // XML structure may vary - handle common formats
        if (isset($xml->ITEM)) {
            foreach ($xml->ITEM as $item) {
                $row = array();
                
                // Map XML fields to array
                $row['Item#'] = (string)$item->ITEMNO;
                $row['Desc1'] = (string)$item->ITEMDESCRIPTION;
                $row['Desc2'] = '';
                $row['UPC'] = (string)$item->ITEMUPC;
                $row['MFGPNum'] = (string)$item->ITEMPN;
                $row['MFG'] = (string)$item->ITEMANUFACTURER;
                $row['MSRP'] = (string)$item->ITEMMSRP;
                $row['Price1'] = (string)$item->ITEMPRICE;
                $row['Qty1'] = (string)$item->ITEMQTYAVAIL;
                $row['Category'] = (string)$item->ITEMCATEGORY;
                $row['Weight'] = (string)$item->ITEMWEIGHT;
                $row['Serialized'] = (string)$item->ITEMSERIALIZED;
                $row['BOFlag'] = (string)$item->ITEMBOFLAG;
                
                // Sale pricing
                if (isset($item->ITEMSALESTART) && isset($item->ITEMSALESEND)) {
                    $sale_start = strtotime((string)$item->ITEMSALESTART);
                    $sale_end = strtotime((string)$item->ITEMSALESEND);
                    $now = time();
                    
                    if ($now >= $sale_start && $now <= $sale_end && !empty($item->ITEMSALEPRICE)) {
                        $row['Price1'] = (string)$item->ITEMSALEPRICE;
                    }
                }
                
                // Only include available items
                $qty = intval($row['Qty1']);
                if ($qty > 0 || strtoupper($row['BOFlag']) === 'Y') {
                    $row['Avail'] = 'Y';
                    $this->data[] = $row;
                }
            }
        }
    }
    
    /**
     * Parse Qtypricingout.xml (pricing updates)
     */
    private function parse_pricing($xml) {
        $this->data = array();
        
        // This file has tier pricing - each item may have multiple rows
        if (isset($xml->ITEM)) {
            $items = array();
            
            foreach ($xml->ITEM as $item) {
                $item_no = (string)$item->ITEMNO;
                $qty = intval($item->ITEMQTY);
                $price = floatval($item->ITEMPRICE);
                
                if (!isset($items[$item_no])) {
                    $items[$item_no] = array(
                        'Item#' => $item_no,
                        'Price1' => 0,
                        'Price2' => 0,
                        'Price3' => 0,
                        'Qty1' => 0,
                        'Qty2' => 0,
                        'Qty3' => 0
                    );
                }
                
                // Determine which tier (1, 2, or 3)
                // Typically: Qty1 < Qty2 < Qty3
                if ($items[$item_no]['Qty1'] == 0) {
                    $items[$item_no]['Price1'] = $price;
                    $items[$item_no]['Qty1'] = $qty;
                } elseif ($qty > $items[$item_no]['Qty1'] && $items[$item_no]['Qty2'] == 0) {
                    $items[$item_no]['Price2'] = $price;
                    $items[$item_no]['Qty2'] = $qty;
                } elseif ($qty > $items[$item_no]['Qty2']) {
                    $items[$item_no]['Price3'] = $price;
                    $items[$item_no]['Qty3'] = $qty;
                }
            }
            
            $this->data = array_values($items);
        }
    }
    
    /**
     * Merge pricing data with full inventory
     */
    public function merge_pricing_data($full_inventory) {
        // Create lookup by Item#
        $pricing_lookup = array();
        foreach ($this->data as $pricing_row) {
            if (isset($pricing_row['Item#'])) {
                $pricing_lookup[$pricing_row['Item#']] = $pricing_row;
            }
        }
        
        // Merge into full inventory
        foreach ($full_inventory as &$row) {
            $item_no = isset($row['Item#']) ? $row['Item#'] : '';
            
            if (!empty($item_no) && isset($pricing_lookup[$item_no])) {
                $pricing = $pricing_lookup[$item_no];
                
                // Update prices
                if (isset($pricing['Price1']) && floatval($pricing['Price1']) > 0) {
                    $row['Price1'] = $pricing['Price1'];
                }
                if (isset($pricing['Price2']) && floatval($pricing['Price2']) > 0) {
                    $row['Price2'] = $pricing['Price2'];
                }
                if (isset($pricing['Price3']) && floatval($pricing['Price3']) > 0) {
                    $row['Price3'] = $pricing['Price3'];
                }
                
                // Update quantities
                if (isset($pricing['Qty1'])) {
                    $row['Qty1'] = $pricing['Qty1'];
                }
                if (isset($pricing['Qty2'])) {
                    $row['Qty2'] = $pricing['Qty2'];
                }
                if (isset($pricing['Qty3'])) {
                    $row['Qty3'] = $pricing['Qty3'];
                }
            }
        }
        
        return $full_inventory;
    }
    
    /**
     * Get all data
     */
    public function get_data() {
        return $this->data;
    }
    
    /**
     * Get row count
     */
    public function get_count() {
        return count($this->data);
    }
    
    /**
     * Get errors
     */
    public function get_errors() {
        return $this->errors;
    }
    
    /**
     * Get preview of first N rows
     */
    public function get_preview($limit = 10) {
        return array_slice($this->data, 0, $limit);
    }
}
