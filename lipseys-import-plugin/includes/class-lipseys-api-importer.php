<?php
/**
 * Lipsey's API Importer - Import products directly from Lipsey's API
 * 
 * Fetches catalog from API and converts to format compatible with existing product importer.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Lipseys_API_Importer {
    
    private $stats = array(
        'fetched' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0
    );
    
    private $errors = array();
    
    /**
     * Fetch catalog from Lipsey's API
     * 
     * @return array|false Array of products or false on failure
     */
    public function fetch_catalog() {
        $response = Lipseys_API_Client::catalog_feed();
        
        if (!$response['success']) {
            $this->errors[] = 'API request failed: ' . implode(', ', $response['errors']);
            return false;
        }
        
        if (!$response['authorized']) {
            $this->errors[] = 'Not authorized. Check API credentials and server IP whitelist.';
            return false;
        }
        
        if (empty($response['data']) || !is_array($response['data'])) {
            $this->errors[] = 'No products returned from API.';
            return false;
        }
        
        $this->stats['fetched'] = count($response['data']);
        return $response['data'];
    }

    /**
     * Fetch catalog from API, apply filters, store in transient. No product processing.
     * Tries pricing feed first (smaller/faster); if that works, we use batch details. Otherwise full catalog_feed (may 502).
     *
     * @param array $options filter_manufacturer, filter_type, filter_in_stock_only
     * @return array { success, total, errors, stats, use_batch_details?: bool }
     */
    public function fetch_and_cache_catalog($options = array()) {
        $defaults = array(
            'filter_manufacturer' => '',
            'filter_type' => '',
            'filter_in_stock_only' => true,
        );
        $options = array_merge($defaults, $options);

        // 1) Try pricing/quantity feed first (smaller payload, often under host timeout)
        $pq = Lipseys_API_Client::pricing_quantity_feed();
        if (! empty($pq['success']) && ! empty($pq['authorized']) && ! empty($pq['data'])) {
            $items = isset($pq['data']['items']) && is_array($pq['data']['items'])
                ? $pq['data']['items']
                : ( is_array($pq['data']) ? $pq['data'] : array() );
            $item_numbers = array();
            foreach ($items as $item) {
                $no = isset($item['itemNumber']) ? trim((string) $item['itemNumber']) : '';
                if ($no === '') {
                    continue;
                }
                if ($options['filter_in_stock_only']) {
                    $qty = isset($item['quantity']) ? intval($item['quantity']) : 0;
                    if ($qty <= 0) {
                        continue;
                    }
                }
                $item_numbers[] = $no;
            }
            if (! empty($item_numbers)) {
                set_transient('lipseys_api_import_item_list', $item_numbers, 3600);
                set_transient('lipseys_api_import_item_list_options', $options, 3600);
                set_transient('lipseys_api_import_catalog', array(), 3600); // start empty; batch fetcher will fill
                return array(
                    'success' => true,
                    'total' => count($item_numbers),
                    'errors' => array(),
                    'stats' => array('fetched' => count($item_numbers)),
                    'use_batch_details' => true,
                );
            }
        }

        // 2) Fallback: full catalog feed (may 502 on slow hosts)
        $catalog = $this->fetch_catalog();
        if ($catalog === false) {
            return array(
                'success' => false,
                'total' => 0,
                'errors' => $this->errors,
                'stats' => $this->stats,
            );
        }

        $catalog = $this->apply_filters($catalog, $options);
        set_transient('lipseys_api_import_catalog', $catalog, 3600);

        return array(
            'success' => true,
            'total' => count($catalog),
            'errors' => array(),
            'stats' => array('fetched' => count($catalog)),
        );
    }

    /**
     * Fetch full catalog details for one batch of item numbers (avoids one huge catalog_feed request).
     * Works in chunks of chunk_size (e.g. 500): after each chunk we import, then fetch next chunk.
     *
     * @param int $batch_index  0-based batch index within the current chunk
     * @param int $batch_size  items per batch (e.g. 15)
     * @param int $chunk_offset start index into full item list (0, 500, 1000, ...)
     * @param int $chunk_size  items per chunk (e.g. 500)
     * @return array { success, batch_index, fetched, catalog_count, is_chunk_complete, is_complete, total_items, chunk_offset }
     */
    public function fetch_catalog_details_batch($batch_index, $batch_size = 15, $chunk_offset = 0, $chunk_size = 500) {
        $item_numbers = get_transient('lipseys_api_import_item_list');
        $options = get_transient('lipseys_api_import_item_list_options');
        if (! is_array($item_numbers) || empty($item_numbers)) {
            return array(
                'success' => false,
                'errors' => array('Item list missing. Click "Fetch catalog" again.'),
                'is_complete' => false,
            );
        }
        if (! is_array($options)) {
            $options = array(
                'filter_manufacturer' => '',
                'filter_type' => '',
                'filter_in_stock_only' => true,
            );
        }

        $total_items = count($item_numbers);
        $item_slice = array_slice($item_numbers, $chunk_offset, $chunk_size);
        $total_in_chunk = count($item_slice);

        // Start of a new chunk: clear catalog so we only hold this chunk
        if ($chunk_offset > 0 && $batch_index === 0) {
            set_transient('lipseys_api_import_catalog', array(), 3600);
        }

        $catalog = get_transient('lipseys_api_import_catalog');
        if (! is_array($catalog)) {
            $catalog = array();
        }

        $offset_within_chunk = $batch_index * $batch_size;
        $batch = array_slice($item_slice, $offset_within_chunk, $batch_size);

        $fetched = 0;
        foreach ($batch as $item_no) {
            try {
                $res = Lipseys_API_Client::catalog_feed_item($item_no);
                if (empty($res['success']) || empty($res['data'])) {
                    continue;
                }
                $product = is_array($res['data']) ? $res['data'] : (array) $res['data'];
                $filtered = $this->apply_filters(array( $product ), $options);
                if (! empty($filtered)) {
                    $catalog[] = $filtered[0];
                    $fetched++;
                }
            } catch (Throwable $e) {
                // Skip this item so one bad response doesn't kill the whole batch
                continue;
            }
        }

        set_transient('lipseys_api_import_catalog', $catalog, 3600);
        $next_offset_within_chunk = $offset_within_chunk + count($batch);
        $is_chunk_complete = $next_offset_within_chunk >= $total_in_chunk;
        $all_items_done = ( $chunk_offset + $total_in_chunk ) >= $total_items;

        return array(
            'success' => true,
            'batch_index' => $batch_index,
            'fetched' => $fetched,
            'catalog_count' => count($catalog),
            'is_chunk_complete' => $is_chunk_complete,
            'is_complete' => $is_chunk_complete && $all_items_done,
            'total_items' => $total_items,
            'chunk_offset' => $chunk_offset,
            'chunk_size' => $chunk_size,
        );
    }


    /**
     * Convert API product to CSV format for existing importer
     * 
     * API uses different field names than CSV. This maps them.
     * 
     * @param array $api_product Product from API
     * @return array Product in CSV format
     */
    private function api_to_csv_format($api_product) {
        return array(
            // Basic fields
            'ITEMNO' => isset($api_product['itemNo']) ? $api_product['itemNo'] : '',
            'DESCRIPTION1' => isset($api_product['description1']) ? $api_product['description1'] : '',
            'DESCRIPTION2' => isset($api_product['description2']) ? $api_product['description2'] : '',
            // Lipsey's web-style title (e.g. "M1A TANKER 308WIN... | ADJ SIGHTS") for display above product title
            'LIPSEYS_DISPLAY_TITLE' => $this->build_lipseys_display_title($api_product),
            'UPC' => isset($api_product['upc']) ? $api_product['upc'] : '',
            'MANUFACTURER' => isset($api_product['manufacturer']) ? $api_product['manufacturer'] : '',
            'MODEL' => isset($api_product['model']) && (string) $api_product['model'] !== '' ? $api_product['model'] : (isset($api_product['mfgModelNumber']) ? $api_product['mfgModelNumber'] : ''),
            'MFGMODELNO' => isset($api_product['mfgModelNumber']) ? $api_product['mfgModelNumber'] : '',
            'FAMILY' => isset($api_product['family']) ? $api_product['family'] : (isset($api_product['productFamily']) ? $api_product['productFamily'] : ''),
            'TYPE' => isset($api_product['type']) ? $api_product['type'] : '',
            'ITEMGROUP' => isset($api_product['itemType']) ? $api_product['itemType'] : '',
            
            // Pricing
            'PRICE' => isset($api_product['price']) ? $api_product['price'] : 0,
            'CURRENTPRICE' => isset($api_product['currentPrice']) ? $api_product['currentPrice'] : (isset($api_product['price']) ? $api_product['price'] : 0),
            'MSRP' => isset($api_product['msrp']) ? $api_product['msrp'] : 0,
            'RETAILMAP' => isset($api_product['retailMap']) ? $api_product['retailMap'] : 0,
            
            // Inventory
            'QUANTITY' => isset($api_product['quantity']) ? $api_product['quantity'] : 0,
            'ALLOCATED' => isset($api_product['allocated']) ? ($api_product['allocated'] ? 'TRUE' : 'FALSE') : 'FALSE',
            'ONSALE' => isset($api_product['onSale']) ? ($api_product['onSale'] ? 'TRUE' : 'FALSE') : 'FALSE',
            
            // Specifications
            'CALIBERGAUGE' => isset($api_product['caliberGauge']) ? $api_product['caliberGauge'] : '',
            'ACTION' => isset($api_product['action']) ? $api_product['action'] : '',
            'BARRELLENGTH' => isset($api_product['barrelLength']) ? $api_product['barrelLength'] : '',
            'CAPACITY' => isset($api_product['capacity']) ? $api_product['capacity'] : '',
            'FINISH' => isset($api_product['finish']) ? $api_product['finish'] : '',
            'OVERALLLENGTH' => isset($api_product['overallLength']) ? $api_product['overallLength'] : '',
            'WEIGHT' => isset($api_product['weight']) ? $api_product['weight'] : '',
            'RECEIVER' => isset($api_product['receiver']) ? $api_product['receiver'] : '',
            'SAFETY' => isset($api_product['safety']) ? $api_product['safety'] : '',
            'SIGHTS' => isset($api_product['sights']) ? $api_product['sights'] : '',
            'STOCKFRAMEGRIPS' => isset($api_product['stockFrameGrips']) ? $api_product['stockFrameGrips'] : '',
            'MAGAZINE' => isset($api_product['magazine']) ? $api_product['magazine'] : '',
            'CHAMBER' => isset($api_product['chamber']) ? $api_product['chamber'] : '',
            'DRILLEDANDTAPPED' => isset($api_product['drilledAndTapped']) ? ($api_product['drilledAndTapped'] ? 'TRUE' : 'FALSE') : 'FALSE',
            'RATEOFTWIST' => isset($api_product['rateOfTwist']) ? $api_product['rateOfTwist'] : '',
            'FINISHTYPE' => isset($api_product['finishType']) ? $api_product['finishType'] : '',
            'SIGHTTYPE' => isset($api_product['sightType']) ? $api_product['sightType'] : '',
            'NUMBEROFMAGAZINES' => isset($api_product['numberOfMagazines']) ? $api_product['numberOfMagazines'] : '',
            'MAGDESCRIPTION' => isset($api_product['magDescription']) ? $api_product['magDescription'] : (isset($api_product['magazineDescription']) ? $api_product['magazineDescription'] : ''),
            'SAFETYFEATURES' => isset($api_product['safetyFeatures']) ? $api_product['safetyFeatures'] : '',
            
            // Compliance
            'FFLREQUIRED' => isset($api_product['fflRequired']) ? ($api_product['fflRequired'] ? 'TRUE' : 'FALSE') : 'FALSE',
            'SOTREQUIRED' => isset($api_product['sotRequired']) ? ($api_product['sotRequired'] ? 'TRUE' : 'FALSE') : 'FALSE',
            
            // Image
            'IMAGENAME' => isset($api_product['imageName']) ? $api_product['imageName'] : '',
            
            // Additional features
            'ADDITIONALFEATURE1' => isset($api_product['additionalFeature1']) ? $api_product['additionalFeature1'] : '',
            'ADDITIONALFEATURE2' => isset($api_product['additionalFeature2']) ? $api_product['additionalFeature2'] : '',
            'ADDITIONALFEATURE3' => isset($api_product['additionalFeature3']) ? $api_product['additionalFeature3'] : '',
            
            // Shipping
            'SHIPPINGWEIGHT' => isset($api_product['shippingWeight']) ? $api_product['shippingWeight'] : '',
            'CANDROPSHIP' => isset($api_product['canDropship']) ? ($api_product['canDropship'] ? 'TRUE' : 'FALSE') : 'FALSE',
            
            // Special fields
            'EXCLUSIVE' => isset($api_product['exclusive']) ? ($api_product['exclusive'] ? 'TRUE' : 'FALSE') : 'FALSE',
            'SPECIAL' => isset($api_product['special']) ? $api_product['special'] : '',
        );
    }

    /**
     * Build Lipsey's web-style display title (e.g. "M1A TANKER 308WIN... | ADJ SIGHTS").
     * Uses API displayName/title if present, else description1 | description2.
     *
     * @param array $api_product Product from API
     * @return string
     */
    private function build_lipseys_display_title($api_product) {
        if (isset($api_product['displayName']) && trim((string) $api_product['displayName']) !== '') {
            return trim($api_product['displayName']);
        }
        if (isset($api_product['title']) && trim((string) $api_product['title']) !== '') {
            return trim($api_product['title']);
        }
        $d1 = isset($api_product['description1']) ? trim((string) $api_product['description1']) : '';
        $d2 = isset($api_product['description2']) ? trim((string) $api_product['description2']) : '';
        if ($d1 === '' && $d2 === '') {
            return '';
        }
        if ($d2 === '') {
            return $d1;
        }
        return $d1 . ' | ' . $d2;
    }
    
    /**
     * Import products from API
     * 
     * @param array $options Import options (batch_size, offset, filters, etc.)
     * @return array Stats and status
     */
    public function import_from_api($options = array()) {
        $defaults = array(
            'batch_size' => 50,
            'offset' => 0,
            'update_existing' => true,
            'skip_images' => true,
            'filter_manufacturer' => '',
            'filter_type' => '',
            'filter_min_price' => 0,
            'filter_max_price' => 0,
            'filter_in_stock_only' => true,
        );
        
        $options = array_merge($defaults, $options);
        
        // Use cached catalog only (never fetch in this request — avoids 502 on long catalog fetch)
        $catalog = get_transient('lipseys_api_import_catalog');
        if ($catalog === false || ! is_array($catalog)) {
            $this->errors[] = 'Catalog not loaded. Click "Fetch catalog" first (may take 1–2 min). If that returns 502, your host is killing long requests.';
            return array(
                'success' => false,
                'errors' => $this->errors,
                'stats' => $this->stats,
            );
        }
        
        $total = count($catalog);
        $batch = array_slice($catalog, $options['offset'], $options['batch_size']);
        
        // Import batch using existing product importer (skip_images keeps requests under gateway timeout)
        $product_importer = new Lipseys_Product_Importer();
        $product_importer->set_skip_images(! empty($options['skip_images']));
        
        foreach ($batch as $api_product) {
            $csv_row = $this->api_to_csv_format($api_product);
            $result = $product_importer->import_product($csv_row);
            
            if ($result === false) {
                $this->stats['errors']++;
            }
        }
        
        // Merge stats from product importer
        $importer_stats = $product_importer->get_stats();
        $this->stats['created'] += $importer_stats['created'];
        $this->stats['updated'] += $importer_stats['updated'];
        $this->stats['skipped'] += $importer_stats['skipped'];
        
        // Get errors from product importer
        $importer_errors = $product_importer->get_errors();
        $this->errors = array_merge($this->errors, $importer_errors);
        
        $new_offset = $options['offset'] + count($batch);
        $is_complete = $new_offset >= $total;
        
        // Clean up transient if complete
        if ($is_complete) {
            delete_transient('lipseys_api_import_catalog');
        }
        
        return array(
            'success' => true,
            'offset' => $new_offset,
            'total' => $total,
            'is_complete' => $is_complete,
            'stats' => $this->stats,
            'errors' => array_slice($this->errors, -10) // Last 10 errors
        );
    }
    
    /**
     * Apply filters to catalog
     * 
     * @param array $catalog Full catalog
     * @param array $options Filter options
     * @return array Filtered catalog
     */
    private function apply_filters($catalog, $options) {
        $filtered = array();
        
        foreach ($catalog as $product) {
            // In stock only
            if ($options['filter_in_stock_only']) {
                $qty = isset($product['quantity']) ? intval($product['quantity']) : 0;
                if ($qty <= 0) {
                    continue;
                }
            }
            
            // Manufacturer filter
            if (!empty($options['filter_manufacturer'])) {
                $mfr = isset($product['manufacturer']) ? strtolower($product['manufacturer']) : '';
                if (strpos($mfr, strtolower($options['filter_manufacturer'])) === false) {
                    continue;
                }
            }
            
            // Type filter
            if (!empty($options['filter_type'])) {
                $type = isset($product['type']) ? strtolower($product['type']) : '';
                if (strpos($type, strtolower($options['filter_type'])) === false) {
                    continue;
                }
            }
            
            // Price filter
            if ($options['filter_min_price'] > 0 || $options['filter_max_price'] > 0) {
                $price = isset($product['currentPrice']) ? floatval($product['currentPrice']) : 0;
                if ($price <= 0) {
                    $price = isset($product['price']) ? floatval($product['price']) : 0;
                }
                
                if ($options['filter_min_price'] > 0 && $price < $options['filter_min_price']) {
                    continue;
                }
                
                if ($options['filter_max_price'] > 0 && $price > $options['filter_max_price']) {
                    continue;
                }
            }
            
            $filtered[] = $product;
        }
        
        return $filtered;
    }
    
    /**
     * Update pricing and inventory from API (faster than full catalog)
     * 
     * @return array Result with stats
     */
    public function update_pricing_inventory() {
        $response = Lipseys_API_Client::pricing_quantity_feed();
        
        if (!$response['success'] || !$response['authorized']) {
            return array(
                'success' => false,
                'message' => 'API request failed: ' . implode(', ', $response['errors'])
            );
        }
        
        $items = isset($response['data']['items']) ? $response['data']['items'] : array();
        $updated = 0;
        $not_found = 0;
        
        foreach ($items as $item) {
            $item_number = isset($item['itemNumber']) ? $item['itemNumber'] : '';
            if (empty($item_number)) {
                continue;
            }
            
            // Find product by SKU
            $product_id = wc_get_product_id_by_sku($item_number);
            if (!$product_id) {
                $not_found++;
                continue;
            }
            
            $product = wc_get_product($product_id);
            if (!$product) {
                $not_found++;
                continue;
            }
            
            // Update price
            $price = isset($item['currentPrice']) ? floatval($item['currentPrice']) : 0;
            if ($price <= 0) {
                $price = isset($item['price']) ? floatval($item['price']) : 0;
            }
            if ($price > 0) {
                $product->set_regular_price($price);
            }
            
            // Update inventory
            $quantity = isset($item['quantity']) ? intval($item['quantity']) : 0;
            $product->set_stock_quantity($quantity);
            $product->set_stock_status($quantity > 0 ? 'instock' : 'outofstock');
            
            $product->save();
            $updated++;
        }
        
        return array(
            'success' => true,
            'message' => "Updated {$updated} products. {$not_found} not found in your store.",
            'updated' => $updated,
            'not_found' => $not_found,
            'total_items' => count($items)
        );
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
}
