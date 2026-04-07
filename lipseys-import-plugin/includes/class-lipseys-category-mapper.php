<?php
/**
 * Category Mapper - Maps Lipsey's TYPE and ITEMGROUP to WooCommerce categories
 */

if (!defined('ABSPATH')) {
    exit;
}

class Lipseys_Category_Mapper {
    
    private static $type_mapping = array(
        // Firearms
        'Rifle' => array('parent' => 'Firearms', 'category' => 'Rifles'),
        'Semi-Auto Pistol' => array('parent' => 'Firearms', 'category' => 'Handguns'),
        'Revolver' => array('parent' => 'Firearms', 'category' => 'Handguns'),
        'Shotgun' => array('parent' => 'Firearms', 'category' => 'Shotguns'),
        'Bolt Action Rifle' => array('parent' => 'Firearms', 'category' => 'Rifles'),
        'Semi-Auto Rifle' => array('parent' => 'Firearms', 'category' => 'Rifles'),
        
        // Accessories
        'Accessory-Magazines' => array('parent' => 'Accessories', 'category' => 'Magazines'),
        'Accessory-Scopes' => array('parent' => 'Accessories', 'category' => 'Optics'),
        'Accessory-Lasers and Sights' => array('parent' => 'Accessories', 'category' => 'Sights & Lasers'),
        'Accessory-Lights' => array('parent' => 'Accessories', 'category' => 'Lights'),
        'Accessory-Rings & Ring Mounts' => array('parent' => 'Accessories', 'category' => 'Mounts & Rings'),
        'Accessory-Silencer Accessories' => array('parent' => 'Accessories', 'category' => 'Suppressor Accessories'),
        'Accessory-Bipods' => array('parent' => 'Accessories', 'category' => 'Bipods'),
        'Accessory-Holsters' => array('parent' => 'Accessories', 'category' => 'Holsters'),
        'Accessory-Triggers' => array('parent' => 'Accessories', 'category' => 'Triggers'),
        'Accessory-Grips' => array('parent' => 'Accessories', 'category' => 'Grips'),
        'Accessory-Parts' => array('parent' => 'Accessories', 'category' => 'Parts'),
        'Accessory' => array('parent' => 'Accessories', 'category' => 'Other Accessories'),
        // Alternate TYPE strings Lipsey's may send (API/CSV variance)
        'Holsters' => array('parent' => 'Accessories', 'category' => 'Holsters'),
        'Holsters and Related Items' => array('parent' => 'Accessories', 'category' => 'Holsters'),
        'HOLSTERS AND RELATED ITEMS' => array('parent' => 'Accessories', 'category' => 'Holsters'),
        'Triggers' => array('parent' => 'Accessories', 'category' => 'Triggers'),
        'Grips' => array('parent' => 'Accessories', 'category' => 'Grips'),
        // Space after dash (API/CSV variance)
        'Accessory - Holsters' => array('parent' => 'Accessories', 'category' => 'Holsters'),
        'Accessory - Triggers' => array('parent' => 'Accessories', 'category' => 'Triggers'),
        'Accessory - Grips' => array('parent' => 'Accessories', 'category' => 'Grips'),
        'Accessory - Parts' => array('parent' => 'Accessories', 'category' => 'Parts'),
        // More holster variants (API/CSV may send singular or other strings)
        'Holster' => array('parent' => 'Accessories', 'category' => 'Holsters'),
        'Accessory-Holster' => array('parent' => 'Accessories', 'category' => 'Holsters'),
        'Accessory - Holster' => array('parent' => 'Accessories', 'category' => 'Holsters'),
        'Pistol Holsters' => array('parent' => 'Accessories', 'category' => 'Holsters'),
        'Gun Holsters' => array('parent' => 'Accessories', 'category' => 'Holsters'),
    );
    
    private static $itemgroup_mapping = array(
        'Sporting Semi-Auto Rimfire Rifles' => 'Rifles',
        'Tactical Centerfire Semi-Auto Rifles' => 'Rifles',
        'Sporting Bolt Action Centerfire Rifles' => 'Rifles',
        'Tactical Bolt Action Rifles' => 'Rifles',
        'Sporting Pump Shotguns' => 'Shotguns',
        'Sporting Semi-Auto Shotguns' => 'Shotguns',
        'Tactical Semi-Auto Shotguns' => 'Shotguns',
        'Metal Frame Centerfire Pistols' => 'Handguns',
        'Polymer Frame Centerfire Pistols' => 'Handguns',
        'Revolvers' => 'Handguns',
    );
    
    /**
     * Get or create WooCommerce category
     */
    public static function get_or_create_category($category_name, $parent_name = null) {
        $parent_id = 0;
        
        // Get parent category if specified
        if ($parent_name) {
            $parent_term = get_term_by('name', $parent_name, 'product_cat');
            if (!$parent_term) {
                // Create parent category
                $parent_term = wp_insert_term($parent_name, 'product_cat');
                if (!is_wp_error($parent_term)) {
                    $parent_id = $parent_term['term_id'];
                }
            } else {
                $parent_id = $parent_term->term_id;
            }
        }
        
        // Check if category exists
        $term = get_term_by('name', $category_name, 'product_cat');
        
        if ($term) {
            return $term->term_id;
        }
        
        // Create category
        $term_data = wp_insert_term($category_name, 'product_cat', array(
            'parent' => $parent_id
        ));
        
        if (is_wp_error($term_data)) {
            error_log('Error creating category: ' . $term_data->get_error_message());
            return 0;
        }
        
        return $term_data['term_id'];
    }
    
    /**
     * Map Lipsey's TYPE to WooCommerce categories
     */
    public static function map_type_to_categories($type, $itemgroup = '') {
        $categories = array();
        $type = is_string($type) ? trim($type) : '';

        // Try exact match, then title-case match (API may send "ACCESSORY-TRIGGERS" or "Accessory-Triggers")
        $mapping = null;
        if (isset(self::$type_mapping[$type])) {
            $mapping = self::$type_mapping[$type];
        } elseif ($type !== '') {
            $normalized = ucwords(strtolower($type), " -\t");
            if (isset(self::$type_mapping[$normalized])) {
                $mapping = self::$type_mapping[$normalized];
            }
        }

        if ($mapping) {
            $parent_id = self::get_or_create_category($mapping['parent']);
            $category_id = self::get_or_create_category($mapping['category'], $mapping['parent']);
            $categories[] = $category_id;
        } else {
            // Fallback: if TYPE contains holster/trigger/grip, map to that category (catches API variants we didn't list)
            $type_lower = strtolower($type);
            if (strpos($type_lower, 'holster') !== false) {
                $mapping = array('parent' => 'Accessories', 'category' => 'Holsters');
                $category_id = self::get_or_create_category($mapping['category'], $mapping['parent']);
                $categories[] = $category_id;
            } elseif (strpos($type_lower, 'trigger') !== false) {
                $mapping = array('parent' => 'Accessories', 'category' => 'Triggers');
                $category_id = self::get_or_create_category($mapping['category'], $mapping['parent']);
                $categories[] = $category_id;
            } elseif (strpos($type_lower, 'grip') !== false) {
                $mapping = array('parent' => 'Accessories', 'category' => 'Grips');
                $category_id = self::get_or_create_category($mapping['category'], $mapping['parent']);
                $categories[] = $category_id;
            } else {
                // Map all other unmapped TYPEs to "Other Accessories"
                $category_id = self::get_or_create_category('Other Accessories', 'Accessories');
                $categories[] = $category_id;
            }
        }
        
        // Also map ITEMGROUP if provided
        if (!empty($itemgroup)) {
            if (isset(self::$itemgroup_mapping[$itemgroup])) {
                $parent_cat = self::$itemgroup_mapping[$itemgroup];
                $category_id = self::get_or_create_category($itemgroup, $parent_cat);
                if (!in_array($category_id, $categories)) {
                    $categories[] = $category_id;
                }
            }
        }
        
        return array_filter($categories); // Remove any 0 values
    }
    
    /**
     * Initialize default category structure
     */
    public static function init_category_structure() {
        $structure = array(
            'Firearms' => array(
                'Handguns',
                'Rifles',
                'Shotguns',
                'Suppressors'
            ),
            'Accessories' => array(
                'Magazines',
                'Optics',
                'Sights & Lasers',
                'Lights',
                'Mounts & Rings',
                'Holsters',
                'Suppressor Accessories',
                'Bipods',
                'Grips',
                'Triggers',
                'Parts',
                'Other Accessories'
            )
        );
        
        foreach ($structure as $parent => $children) {
            $parent_id = self::get_or_create_category($parent);
            foreach ($children as $child) {
                self::get_or_create_category($child, $parent);
            }
        }
    }
}
