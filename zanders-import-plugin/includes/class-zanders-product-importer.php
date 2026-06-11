<?php
/**
 * Product Importer - Creates/updates WooCommerce products from Zanders data
 */

if (!defined('ABSPATH')) {
    exit;
}

class Zanders_Product_Importer {
    
    private $stats = array(
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0
    );
    
    private $errors = array();
    private $ftp_handler = null;
    
    public function __construct($ftp_handler = null) {
        $this->ftp_handler = $ftp_handler;
    }
    
    /**
     * Import product from Zanders row
     */
    public function import_product($row) {
        try {
            // Validate required fields (try both 'itemnumber' and 'Item#')
            $item_no = isset($row['itemnumber']) ? $row['itemnumber'] : (isset($row['Item#']) ? $row['Item#'] : '');
            if (empty($item_no)) {
                $this->stats['skipped']++;
                $this->errors[] = "Skipped: Missing itemnumber";
                return false;
            }
            
            $sku = sanitize_text_field($item_no);
            
            // Check if product exists
            $product_id = wc_get_product_id_by_sku($sku);
            
            if ($product_id) {
                $product = wc_get_product($product_id);
                $is_new = false;
            } else {
                $product = new WC_Product_Simple();
                $is_new = true;
            }
            
            // Set basic product data
            $product->set_sku($sku);
            $product->set_name($this->get_product_name($row));
            $product->set_description($this->get_product_description($row));
            $product->set_short_description($this->get_short_description($row));
            $product->set_regular_price($this->get_price($row));
            $product->set_manage_stock(true);
            $product->set_stock_quantity($this->get_quantity($row));
            $product->set_stock_status('instock');
            $product->set_catalog_visibility('visible');
            $product->set_status('publish');
            
            // Set weight if available (try both cases)
            $weight_val = isset($row['weight']) ? $row['weight'] : (isset($row['Weight']) ? $row['Weight'] : '');
            if (!empty($weight_val)) {
                $weight = floatval($weight_val);
                if ($weight > 0) {
                    $product->set_weight($weight);
                }
            }
            
            // Set categories (try both cases)
            $category = isset($row['category']) ? $row['category'] : (isset($row['Category']) ? $row['Category'] : '');
            $categories = Zanders_Category_Mapper::map_category_to_categories($category);
            if (!empty($categories)) {
                $product->set_category_ids($categories);
            }
            
            // Save product
            $product_id = $product->save();
            
            if (is_wp_error($product_id)) {
                $this->stats['errors']++;
                $this->errors[] = "Error saving product {$sku}: " . $product_id->get_error_message();
                return false;
            }
            
            // Set product meta
            $this->set_product_meta($product_id, $row);
            
            // Handle image from FTP
            if ($this->ftp_handler) {
                Zanders_Image_Handler::set_ftp_handler($this->ftp_handler);
                Zanders_Image_Handler::attach_image_to_product($product_id, $sku, $this->ftp_handler);
            }
            
            // Set product attributes
            $this->set_product_attributes($product_id, $row);
            
            // Update stats
            if ($is_new) {
                $this->stats['created']++;
            } else {
                $this->stats['updated']++;
            }
            
            return $product_id;
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            $item_no = isset($row['itemnumber']) ? $row['itemnumber'] : (isset($row['Item#']) ? $row['Item#'] : 'unknown');
            $this->errors[] = "Exception for {$item_no}: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Get product name
     */
    private function get_product_name($row) {
        $name = '';
        
        // Try both 'desc1' and 'Desc1'
        $desc1 = isset($row['desc1']) ? $row['desc1'] : (isset($row['Desc1']) ? $row['Desc1'] : '');
        if (!empty($desc1)) {
            $name = trim($desc1);
        }
        
        // Try both 'desc2' and 'Desc2'
        $desc2 = isset($row['desc2']) ? $row['desc2'] : (isset($row['Desc2']) ? $row['Desc2'] : '');
        if (!empty($desc2)) {
            if (!empty($name)) {
                $name .= ' - ' . trim($desc2);
            } else {
                $name = trim($desc2);
            }
        }
        
        // Add manufacturer if available (try both 'manufacturer' and 'MFG')
        $mfg = isset($row['manufacturer']) ? $row['manufacturer'] : (isset($row['MFG']) ? $row['MFG'] : '');
        if (!empty($mfg) && stripos($name, $mfg) === false) {
            $name = trim($mfg) . ' ' . $name;
        }
        
        return sanitize_text_field($name);
    }
    
    /**
     * Get product description
     */
    private function get_product_description($row) {
        $description = array();
        
        // Try both 'manufacturer' and 'MFG'
        $mfg = isset($row['manufacturer']) ? $row['manufacturer'] : (isset($row['MFG']) ? $row['MFG'] : '');
        if (!empty($mfg)) {
            $description[] = '<strong>Manufacturer:</strong> ' . esc_html($mfg);
        }
        
        // Try both 'mfgpnumber' and 'MFGPNum'
        $mfgpnum = isset($row['mfgpnumber']) ? $row['mfgpnumber'] : (isset($row['MFGPNum']) ? $row['MFGPNum'] : '');
        if (!empty($mfgpnum)) {
            $description[] = '<strong>Manufacturer Part Number:</strong> ' . esc_html($mfgpnum);
        }
        
        // Try both 'desc2' and 'Desc2'
        $desc2 = isset($row['desc2']) ? $row['desc2'] : (isset($row['Desc2']) ? $row['Desc2'] : '');
        if (!empty($desc2)) {
            $description[] = '<strong>Description:</strong> ' . esc_html($desc2);
        }
        
        // Try both 'category' and 'Category'
        $category_val = isset($row['category']) ? $row['category'] : (isset($row['Category']) ? $row['Category'] : '');
        if (!empty($category_val)) {
            $description[] = '<strong>Category:</strong> ' . esc_html($category_val);
        }
        
        // Try both 'weight' and 'Weight'
        $weight_val = isset($row['weight']) ? $row['weight'] : (isset($row['Weight']) ? $row['Weight'] : '');
        if (!empty($weight_val)) {
            $description[] = '<strong>Weight:</strong> ' . esc_html($weight_val) . ' lbs';
        }
        
        // Serialized flag (try both cases)
        $serialized = isset($row['serialized']) ? $row['serialized'] : (isset($row['Serialized']) ? $row['Serialized'] : '');
        if (!empty($serialized) && strtoupper($serialized) === 'Y') {
            $description[] = '<p><strong>⚠️ Serialized Item:</strong> This item has a serial number and requires proper documentation.</p>';
        }
        
        // FFL Required (typically firearms)
        $category = isset($row['category']) ? strtolower($row['category']) : (isset($row['Category']) ? strtolower($row['Category']) : '');
        $firearm_categories = array('rifle', 'handgun', 'pistol', 'revolver', 'shotgun');
        $is_firearm = false;
        foreach ($firearm_categories as $fc) {
            if (stripos($category, $fc) !== false) {
                $is_firearm = true;
                break;
            }
        }
        
        if ($is_firearm) {
            $description[] = '<p><strong>⚠️ FFL Required:</strong> This item must be shipped to a licensed FFL dealer.</p>';
        }
        
        // Suppressor/NFA items
        if (stripos($category, 'suppressor') !== false || stripos($category, 'silencer') !== false) {
            $description[] = '<p><strong>⚠️ NFA Item:</strong> This item requires SOT transfer and ATF Form 4 approval.</p>';
        }
        
        return implode('<br>', $description);
    }
    
    /**
     * Get short description
     */
    private function get_short_description($row) {
        if (!empty($row['Desc2'])) {
            return sanitize_text_field($row['Desc2']);
        }
        
        // Try both 'desc1' and 'Desc1'
        $desc1 = isset($row['desc1']) ? $row['desc1'] : (isset($row['Desc1']) ? $row['Desc1'] : '');
        if (!empty($desc1)) {
            return sanitize_text_field($desc1);
        }
        
        return '';
    }
    
    /**
     * Get product price
     */
    private function get_price($row) {
        // Use Price1 as base price (try both cases)
        $price1 = isset($row['price1']) ? $row['price1'] : (isset($row['Price1']) ? $row['Price1'] : '');
        if (!empty($price1) && floatval($price1) > 0) {
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
     * Get available quantity
     */
    private function get_quantity($row) {
        $qty1 = isset($row['qty1']) ? intval($row['qty1']) : (isset($row['Qty1']) ? intval($row['Qty1']) : 0);
        $qty2 = isset($row['qty2']) ? intval($row['qty2']) : (isset($row['Qty2']) ? intval($row['Qty2']) : 0);
        $qty3 = isset($row['qty3']) ? intval($row['qty3']) : (isset($row['Qty3']) ? intval($row['Qty3']) : 0);
        
        // Return highest available quantity
        return max($qty1, $qty2, $qty3);
    }
    
    /**
     * Set product meta fields
     */
    private function set_product_meta($product_id, $row) {
        // MSRP (try both cases)
        $msrp = isset($row['msrp']) ? $row['msrp'] : (isset($row['MSRP']) ? $row['MSRP'] : '');
        if (!empty($msrp)) {
            update_post_meta($product_id, '_msrp', floatval($msrp));
        }
        
        // UPC (try both cases)
        $upc = isset($row['upc']) ? $row['upc'] : (isset($row['UPC']) ? $row['UPC'] : '');
        if (!empty($upc)) {
            update_post_meta($product_id, '_upc', sanitize_text_field($upc));
        }
        
        // Manufacturer (try both cases)
        $mfg = isset($row['manufacturer']) ? $row['manufacturer'] : (isset($row['MFG']) ? $row['MFG'] : '');
        if (!empty($mfg)) {
            update_post_meta($product_id, '_manufacturer', sanitize_text_field($mfg));
        }
        
        // Manufacturer Part Number (try both cases)
        $mfgpnum = isset($row['mfgpnumber']) ? $row['mfgpnumber'] : (isset($row['MFGPNum']) ? $row['MFGPNum'] : '');
        if (!empty($mfgpnum)) {
            update_post_meta($product_id, '_manufacturer_part_number', sanitize_text_field($mfgpnum));
        }
        
        // Tier pricing (try both cases)
        $price1 = isset($row['price1']) ? $row['price1'] : (isset($row['Price1']) ? $row['Price1'] : '');
        if (!empty($price1)) {
            update_post_meta($product_id, '_zanders_price1', floatval($price1));
        }
        $price2 = isset($row['price2']) ? $row['price2'] : (isset($row['Price2']) ? $row['Price2'] : '');
        if (!empty($price2)) {
            update_post_meta($product_id, '_zanders_price2', floatval($price2));
        }
        $price3 = isset($row['price3']) ? $row['price3'] : (isset($row['Price3']) ? $row['Price3'] : '');
        if (!empty($price3)) {
            update_post_meta($product_id, '_zanders_price3', floatval($price3));
        }
        
        $qty1 = isset($row['qty1']) ? $row['qty1'] : (isset($row['Qty1']) ? $row['Qty1'] : '');
        if (!empty($qty1)) {
            update_post_meta($product_id, '_zanders_qty1', intval($qty1));
        }
        $qty2 = isset($row['qty2']) ? $row['qty2'] : (isset($row['Qty2']) ? $row['Qty2'] : '');
        if (!empty($qty2)) {
            update_post_meta($product_id, '_zanders_qty2', intval($qty2));
        }
        $qty3 = isset($row['qty3']) ? $row['qty3'] : (isset($row['Qty3']) ? $row['Qty3'] : '');
        if (!empty($qty3)) {
            update_post_meta($product_id, '_zanders_qty3', intval($qty3));
        }
        
        // Serialized flag (try both cases)
        $serialized_val = isset($row['serialized']) ? $row['serialized'] : (isset($row['Serialized']) ? $row['Serialized'] : '');
        $serialized = (!empty($serialized_val) && strtoupper($serialized_val) === 'Y') ? 'yes' : 'no';
        update_post_meta($product_id, '_serialized', $serialized);
        
        // FFL Required (determine from category)
        $category = isset($row['category']) ? strtolower($row['category']) : (isset($row['Category']) ? strtolower($row['Category']) : '');
        $firearm_categories = array('rifle', 'handgun', 'pistol', 'revolver', 'shotgun');
        $is_firearm = false;
        foreach ($firearm_categories as $fc) {
            if (stripos($category, $fc) !== false) {
                $is_firearm = true;
                break;
            }
        }
        
        $ffl_required = $is_firearm ? 'yes' : 'no';
        update_post_meta($product_id, '_ffl_required', $ffl_required);
        
        // SOT Required (suppressors)
        $sot_required = (stripos($category, 'suppressor') !== false || stripos($category, 'silencer') !== false) ? 'yes' : 'no';
        update_post_meta($product_id, '_sot_required', $sot_required);
        
        // Payment gateway flag (for Zen Payments)
        if ($ffl_required === 'yes' && $sot_required === 'no') {
            // Firearms (not NFA) use Zen Payments
            update_post_meta($product_id, '_use_zen_payments', 'yes');
        } else {
            update_post_meta($product_id, '_use_zen_payments', 'no');
        }
        
        // Zanders Item# (try both cases)
        $item_no = isset($row['itemnumber']) ? $row['itemnumber'] : (isset($row['Item#']) ? $row['Item#'] : '');
        if (!empty($item_no)) {
            update_post_meta($product_id, '_zanders_item_number', sanitize_text_field($item_no));
        }
    }
    
    /**
     * Set product attributes
     */
    private function set_product_attributes($product_id, $row) {
        $attributes = array();
        
        // Manufacturer (try both cases)
        $mfg = isset($row['manufacturer']) ? $row['manufacturer'] : (isset($row['MFG']) ? $row['MFG'] : '');
        if (!empty($mfg)) {
            $attributes['pa_manufacturer'] = array(
                'name' => 'pa_manufacturer',
                'value' => sanitize_text_field($mfg),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        
        // Category (try both cases)
        $category = isset($row['category']) ? $row['category'] : (isset($row['Category']) ? $row['Category'] : '');
        if (!empty($category)) {
            $attributes['pa_category'] = array(
                'name' => 'pa_category',
                'value' => sanitize_text_field($category),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        
        // Set attributes on product
        if (!empty($attributes)) {
            $product = wc_get_product($product_id);
            $product->set_attributes($attributes);
            $product->save();
        }
    }
    
    /**
     * Get import statistics
     */
    public function get_stats() {
        return $this->stats;
    }
    
    /**
     * Get errors
     */
    public function get_errors() {
        return $this->errors;
    }
    
    /**
     * Reset stats
     */
    public function reset_stats() {
        $this->stats = array(
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0
        );
        $this->errors = array();
    }
}
