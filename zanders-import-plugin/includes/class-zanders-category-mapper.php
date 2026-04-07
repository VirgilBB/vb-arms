<?php
/**
 * Category Mapper - Maps Zanders Category to WooCommerce categories
 */

if (!defined('ABSPATH')) {
    exit;
}

class Zanders_Category_Mapper {
    
    private static $category_mapping = array(
        // Firearms
        'Rifle' => array('parent' => 'Firearms', 'category' => 'Rifles'),
        'Handgun' => array('parent' => 'Firearms', 'category' => 'Handguns'),
        'Pistol' => array('parent' => 'Firearms', 'category' => 'Handguns'),
        'Revolver' => array('parent' => 'Firearms', 'category' => 'Handguns'),
        'Shotgun' => array('parent' => 'Firearms', 'category' => 'Shotguns'),
        'Suppressor' => array('parent' => 'Firearms', 'category' => 'Suppressors'),
        'Silencer' => array('parent' => 'Firearms', 'category' => 'Suppressors'),
        
        // Accessories
        'Magazine' => array('parent' => 'Accessories', 'category' => 'Magazines'),
        'Scope' => array('parent' => 'Accessories', 'category' => 'Optics'),
        'Optic' => array('parent' => 'Accessories', 'category' => 'Optics'),
        'Sight' => array('parent' => 'Accessories', 'category' => 'Sights & Lasers'),
        'Laser' => array('parent' => 'Accessories', 'category' => 'Sights & Lasers'),
        'Light' => array('parent' => 'Accessories', 'category' => 'Lights'),
        'Mount' => array('parent' => 'Accessories', 'category' => 'Mounts & Rings'),
        'Ring' => array('parent' => 'Accessories', 'category' => 'Mounts & Rings'),
        'Bipod' => array('parent' => 'Accessories', 'category' => 'Bipods'),
        'Accessory' => array('parent' => 'Accessories', 'category' => 'Other Accessories'),
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
     * Map Zanders Category to WooCommerce categories
     */
    public static function map_category_to_categories($category) {
        $categories = array();
        
        if (empty($category)) {
            return $categories;
        }
        
        // Try direct mapping
        if (isset(self::$category_mapping[$category])) {
            $mapping = self::$category_mapping[$category];
            $parent_id = self::get_or_create_category($mapping['parent']);
            $category_id = self::get_or_create_category($mapping['category'], $mapping['parent']);
            $categories[] = $category_id;
        } else {
            // Try partial match (e.g., "Rifle - Bolt Action" contains "Rifle")
            foreach (self::$category_mapping as $key => $mapping) {
                if (stripos($category, $key) !== false) {
                    $parent_id = self::get_or_create_category($mapping['parent']);
                    $category_id = self::get_or_create_category($mapping['category'], $mapping['parent']);
                    $categories[] = $category_id;
                    break;
                }
            }
            
            // If no match, create category under Firearms or Accessories
            if (empty($categories)) {
                // Determine if it's likely a firearm or accessory
                $firearm_keywords = array('rifle', 'pistol', 'handgun', 'revolver', 'shotgun', 'suppressor', 'silencer');
                $is_firearm = false;
                
                foreach ($firearm_keywords as $keyword) {
                    if (stripos($category, $keyword) !== false) {
                        $is_firearm = true;
                        break;
                    }
                }
                
                $parent = $is_firearm ? 'Firearms' : 'Accessories';
                $category_id = self::get_or_create_category($category, $parent);
                $categories[] = $category_id;
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
                'Suppressor Accessories',
                'Bipods',
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
