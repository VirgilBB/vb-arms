<?php
/**
 * Product Importer - Creates/updates WooCommerce products
 */

if (!defined('ABSPATH')) {
    exit;
}

class Lipseys_Product_Importer {
    
    private $stats = array(
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0
    );
    
    private $errors = array();
    
    /** Skip image download when true (faster import; add images later) */
    private $skip_images = false;
    
    public function set_skip_images($skip) {
        $this->skip_images = (bool) $skip;
    }
    
    /**
     * Import product from CSV row
     */
    public function import_product($row) {
        try {
            // Validate required fields
            if (empty($row['ITEMNO']) || empty($row['DESCRIPTION1'])) {
                $this->stats['skipped']++;
                $this->errors[] = "Skipped: Missing ITEMNO or DESCRIPTION1";
                return false;
            }
            
            $sku = sanitize_text_field($row['ITEMNO']);
            
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
            $product->set_stock_quantity(intval($row['QUANTITY']));
            $product->set_stock_status('instock');
            $product->set_catalog_visibility('visible');
            $product->set_status('publish');
            
            // Set categories
            $categories = Lipseys_Category_Mapper::map_type_to_categories(
                $row['TYPE'],
                isset($row['ITEMGROUP']) ? $row['ITEMGROUP'] : ''
            );
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
            
            // Handle image (skip if set for faster import to avoid 502)
            if (! $this->skip_images && ! empty($row['IMAGENAME'])) {
                Lipseys_Image_Handler::attach_image_to_product(
                    $product_id,
                    $row['IMAGENAME'],
                    $sku
                );
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
            $this->errors[] = "Exception for {$row['ITEMNO']}: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Get product name
     */
    private function get_product_name($row) {
        $name = trim($row['DESCRIPTION1']);
        if (!empty($row['DESCRIPTION2'])) {
            $name .= ' - ' . trim($row['DESCRIPTION2']);
        }
        return sanitize_text_field($name);
    }
    
    /**
     * Get product description
     */
    private function get_product_description($row) {
        $description = array();
        
        if (!empty($row['MANUFACTURER'])) {
            $description[] = '<strong>Manufacturer:</strong> ' . esc_html($row['MANUFACTURER']);
        }
        
        if (!empty($row['MODEL'])) {
            $description[] = '<strong>Model:</strong> ' . esc_html($row['MODEL']);
        }
        
        if (!empty($row['CALIBERGAUGE'])) {
            $description[] = '<strong>Caliber/Gauge:</strong> ' . esc_html($row['CALIBERGAUGE']);
        }
        
        if (!empty($row['BARRELLENGTH'])) {
            $description[] = '<strong>Barrel Length:</strong> ' . esc_html($row['BARRELLENGTH']);
        }
        
        if (!empty($row['CAPACITY'])) {
            $description[] = '<strong>Capacity:</strong> ' . esc_html($row['CAPACITY']);
        }
        
        if (!empty($row['FINISH'])) {
            $description[] = '<strong>Finish:</strong> ' . esc_html($row['FINISH']);
        }
        
        // Add FFL notice if required
        if (!empty($row['FFLREQUIRED']) && strtoupper($row['FFLREQUIRED']) === 'TRUE') {
            $description[] = '<p><strong>⚠️ FFL Required:</strong> This item must be shipped to a licensed FFL dealer.</p>';
        }
        
        // Add SOT notice if required (NFA items)
        if (!empty($row['SOTREQUIRED']) && strtoupper($row['SOTREQUIRED']) === 'TRUE') {
            $description[] = '<p><strong>⚠️ NFA Item:</strong> This item requires SOT transfer and ATF Form 4 approval.</p>';
        }
        
        return implode('<br>', $description);
    }
    
    /**
     * Get short description
     */
    private function get_short_description($row) {
        return !empty($row['DESCRIPTION2']) ? sanitize_text_field($row['DESCRIPTION2']) : '';
    }
    
    /**
     * Get product price
     */
    private function get_price($row) {
        // Prefer CURRENTPRICE, fallback to PRICE, then MSRP
        if (!empty($row['CURRENTPRICE']) && floatval($row['CURRENTPRICE']) > 0) {
            return floatval($row['CURRENTPRICE']);
        }
        
        if (!empty($row['PRICE']) && floatval($row['PRICE']) > 0) {
            return floatval($row['PRICE']);
        }
        
        if (!empty($row['MSRP']) && floatval($row['MSRP']) > 0) {
            return floatval($row['MSRP']);
        }
        
        return 0;
    }
    
    /**
     * Set product meta fields
     */
    private function set_product_meta($product_id, $row) {
        // FFL Required
        $ffl_required = (!empty($row['FFLREQUIRED']) && strtoupper($row['FFLREQUIRED']) === 'TRUE') ? 'yes' : 'no';
        update_post_meta($product_id, '_ffl_required', $ffl_required);
        
        // SOT Required (NFA items)
        $sot_required = (!empty($row['SOTREQUIRED']) && strtoupper($row['SOTREQUIRED']) === 'TRUE') ? 'yes' : 'no';
        update_post_meta($product_id, '_sot_required', $sot_required);
        
        // MSRP
        if (!empty($row['MSRP'])) {
            update_post_meta($product_id, '_msrp', floatval($row['MSRP']));
        }
        
        // UPC
        if (!empty($row['UPC'])) {
            update_post_meta($product_id, '_upc', sanitize_text_field($row['UPC']));
        }
        
        // Manufacturer
        if (!empty($row['MANUFACTURER'])) {
            update_post_meta($product_id, '_manufacturer', sanitize_text_field($row['MANUFACTURER']));
        }
        
        // Model (short name for specs) and MFG MDL # (part number for top block)
        if (!empty($row['MODEL'])) {
            update_post_meta($product_id, '_model', sanitize_text_field($row['MODEL']));
        }
        $mfg_no = isset($row['MFGMODELNO']) && (string) trim($row['MFGMODELNO']) !== '' ? trim($row['MFGMODELNO']) : (isset($row['MODEL']) ? trim($row['MODEL']) : '');
        if ($mfg_no !== '') {
            update_post_meta($product_id, '_manufacturer_part_number', sanitize_text_field($mfg_no));
        }
        
        // Lipsey's image name (used when skipping images so "Attach images later" can run)
        if (!empty($row['IMAGENAME'])) {
            update_post_meta($product_id, '_lipseys_image_name', sanitize_text_field($row['IMAGENAME']));
        }
        
        // Lipsey's web-style display title (show above product title on single product)
        $display_title = isset($row['LIPSEYS_DISPLAY_TITLE']) && trim((string) $row['LIPSEYS_DISPLAY_TITLE']) !== '' ? trim($row['LIPSEYS_DISPLAY_TITLE']) : '';
        if ($display_title === '' && (!empty($row['DESCRIPTION1']) || !empty($row['DESCRIPTION2']))) {
            $display_title = trim((string) ($row['DESCRIPTION1'] ?? ''));
            $d2 = trim((string) ($row['DESCRIPTION2'] ?? ''));
            if ($d2 !== '') {
                $display_title = $display_title === '' ? $d2 : $display_title . ' | ' . $d2;
            }
        }
        if ($display_title !== '') {
            update_post_meta($product_id, '_lipseys_display_title', sanitize_text_field($display_title));
        }
        
        // Store TYPE and ITEMGROUP so "Recategorize by TYPE" can fix categories without re-import
        if (isset($row['TYPE']) && (string) $row['TYPE'] !== '') {
            update_post_meta($product_id, '_lipseys_type', sanitize_text_field($row['TYPE']));
        }
        if (isset($row['ITEMGROUP']) && (string) $row['ITEMGROUP'] !== '') {
            update_post_meta($product_id, '_lipseys_itemgroup', sanitize_text_field($row['ITEMGROUP']));
        }
        
        // Payment gateway flag (for Zen Payments)
        if ($ffl_required === 'yes' && $sot_required === 'no') {
            // Firearms (not NFA) use Zen Payments
            update_post_meta($product_id, '_use_zen_payments', 'yes');
        } else {
            update_post_meta($product_id, '_use_zen_payments', 'no');
        }
    }
    
    /**
     * Set product attributes
     */
    private function set_product_attributes($product_id, $row) {
        $attributes = array();
        
        // Manufacturer
        if (!empty($row['MANUFACTURER'])) {
            $attributes['pa_manufacturer'] = array(
                'name' => 'pa_manufacturer',
                'value' => sanitize_text_field($row['MANUFACTURER']),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        
        // Caliber/Gauge
        if (!empty($row['CALIBERGAUGE'])) {
            $attributes['pa_caliber'] = array(
                'name' => 'pa_caliber',
                'value' => sanitize_text_field($row['CALIBERGAUGE']),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        
        // Action Type
        if (!empty($row['ACTION'])) {
            $attributes['pa_action'] = array(
                'name' => 'pa_action',
                'value' => sanitize_text_field($row['ACTION']),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        
        // Barrel Length
        if (!empty($row['BARRELLENGTH'])) {
            $attributes['pa_barrel_length'] = array(
                'name' => 'pa_barrel_length',
                'value' => sanitize_text_field($row['BARRELLENGTH']),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        
        // Capacity
        if (!empty($row['CAPACITY'])) {
            $attributes['pa_capacity'] = array(
                'name' => 'pa_capacity',
                'value' => sanitize_text_field($row['CAPACITY']),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        
        // Finish
        if (!empty($row['FINISH'])) {
            $attributes['pa_finish'] = array(
                'name' => 'pa_finish',
                'value' => sanitize_text_field($row['FINISH']),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        
        // Stock/Frame/Grips (displayed as Stock)
        if (!empty($row['STOCKFRAMEGRIPS'])) {
            $attributes['pa_stock'] = array(
                'name' => 'pa_stock',
                'value' => sanitize_text_field($row['STOCKFRAMEGRIPS']),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        
        // Family
        if (!empty($row['FAMILY'])) {
            $attributes['pa_family'] = array(
                'name' => 'pa_family',
                'value' => sanitize_text_field($row['FAMILY']),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        
        // Overall Length
        if (!empty($row['OVERALLLENGTH'])) {
            $attributes['pa_overall_length'] = array(
                'name' => 'pa_overall_length',
                'value' => sanitize_text_field($row['OVERALLLENGTH']),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        
        // Rate of Twist
        if (!empty($row['RATEOFTWIST'])) {
            $attributes['pa_rate_of_twist'] = array(
                'name' => 'pa_rate_of_twist',
                'value' => sanitize_text_field($row['RATEOFTWIST']),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        
        // Weight
        if (!empty($row['WEIGHT'])) {
            $attributes['pa_weight'] = array(
                'name' => 'pa_weight',
                'value' => sanitize_text_field($row['WEIGHT']),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        
        // Shipping Weight
        if (!empty($row['SHIPPINGWEIGHT'])) {
            $attributes['pa_shipping_weight'] = array(
                'name' => 'pa_shipping_weight',
                'value' => sanitize_text_field($row['SHIPPINGWEIGHT']),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        
        // Receiver
        if (!empty($row['RECEIVER'])) {
            $attributes['pa_receiver'] = array(
                'name' => 'pa_receiver',
                'value' => sanitize_text_field($row['RECEIVER']),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        
        // Safety (or Safety Features)
        $safety = !empty($row['SAFETYFEATURES']) ? $row['SAFETYFEATURES'] : (isset($row['SAFETY']) ? $row['SAFETY'] : '');
        if ($safety !== '') {
            $attributes['pa_safety'] = array(
                'name' => 'pa_safety',
                'value' => sanitize_text_field($safety),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        
        // Sights
        if (!empty($row['SIGHTS'])) {
            $attributes['pa_sights'] = array(
                'name' => 'pa_sights',
                'value' => sanitize_text_field($row['SIGHTS']),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        
        // Sight Type
        if (!empty($row['SIGHTTYPE'])) {
            $attributes['pa_sight_type'] = array(
                'name' => 'pa_sight_type',
                'value' => sanitize_text_field($row['SIGHTTYPE']),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        
        // Magazine
        if (!empty($row['MAGAZINE'])) {
            $attributes['pa_magazine'] = array(
                'name' => 'pa_magazine',
                'value' => sanitize_text_field($row['MAGAZINE']),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        
        // # of Magazines / Mag Description
        if (!empty($row['NUMBEROFMAGAZINES'])) {
            $attributes['pa_number_of_magazines'] = array(
                'name' => 'pa_number_of_magazines',
                'value' => sanitize_text_field($row['NUMBEROFMAGAZINES']),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        if (!empty($row['MAGDESCRIPTION'])) {
            $attributes['pa_mag_description'] = array(
                'name' => 'pa_mag_description',
                'value' => sanitize_text_field($row['MAGDESCRIPTION']),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        
        // Chamber
        if (!empty($row['CHAMBER'])) {
            $attributes['pa_chamber'] = array(
                'name' => 'pa_chamber',
                'value' => sanitize_text_field($row['CHAMBER']),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        
        // Finish Type
        if (!empty($row['FINISHTYPE'])) {
            $attributes['pa_finish_type'] = array(
                'name' => 'pa_finish_type',
                'value' => sanitize_text_field($row['FINISHTYPE']),
                'is_visible' => 1,
                'is_variation' => 0
            );
        }
        
        // Additional Info (combine features 1–3)
        $adnl = array_filter(array(
            isset($row['ADDITIONALFEATURE1']) ? trim($row['ADDITIONALFEATURE1']) : '',
            isset($row['ADDITIONALFEATURE2']) ? trim($row['ADDITIONALFEATURE2']) : '',
            isset($row['ADDITIONALFEATURE3']) ? trim($row['ADDITIONALFEATURE3']) : '',
        ));
        if (!empty($adnl)) {
            $attributes['pa_additional_info'] = array(
                'name' => 'pa_additional_info',
                'value' => sanitize_text_field(implode('; ', $adnl)),
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
