<?php
/**
 * VB Arms - Shop Page (Product Archive)
 * Sleek Search, Firearms First, Image Contain, Symmetrical Pills
 */

show_admin_bar(false);

$upload_dir = wp_upload_dir();
$logo_paths = array(
    get_template_directory() . '/vbarms-black-logo_512.png',
    get_stylesheet_directory() . '/vbarms-black-logo_512.png',
    ABSPATH . 'wp-content/uploads/vbarms-black-logo_512.png'
);
$logo_url = '';
foreach ($logo_paths as $path) {
    if (file_exists($path)) {
        $logo_url = str_replace(ABSPATH, home_url('/'), $path);
        $logo_url = str_replace('\\', '/', $logo_url);
        break;
    }
}
if (!$logo_url) {
    $logo_url = $upload_dir['baseurl'] . '/vbarms-black-logo_512.png';
}

$current_cat = get_queried_object();
$is_category = is_product_category();

// For display only (dropdown selected value, "Showing X–Y of Z"). Per-page/orderby query is set in functions.php.
$per_page = isset($_GET['per_page']) ? sanitize_text_field($_GET['per_page']) : '12';
if (!in_array($per_page, array('12', '50', '100', 'all'), true)) $per_page = '12';
$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'menu_order';

// Hide products that have no thumbnail (set true to skip them in the loop).
$hide_products_without_image = apply_filters('vb_arms_hide_products_without_image', false);

// Query (per_page, orderby, whitelist, Firearms subcats) is handled in functions.php at priority 99.
// Always use shop listing URL (never a product URL). Filter can override.
$shop_url = apply_filters('vb_arms_breadcrumb_shop_url', home_url('/shop/'));
$shop_url = (empty($shop_url) || trim($shop_url) === '') ? home_url('/shop/') : $shop_url;
$ffl_url = 'https://www.ffls.com/ffl/583021019a04518/benin-llc';
$cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
$cart_count = (function_exists('WC') && WC()->cart) ? WC()->cart->get_cart_contents_count() : 0;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title><?php woocommerce_page_title(); ?> | <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-bg: #0a0a0a;
            --secondary-bg: #141414;
            --text-primary: #ffffff;
            --text-secondary: #b8b8b8;
            --accent-green: #00ff94;
            --border-subtle: rgba(255, 255, 255, 0.08);
            /* All pills/buttons: softer outline (30% green) */
            --border-pill: rgba(0, 255, 148, 0.3);
            --border-accent: rgba(0, 255, 148, 0.3);
            --accent-soft: rgba(0, 255, 148, 0.68);
            --accent-soft-bg: rgba(0, 255, 148, 0.06);
            --accent-soft-bg-hover: rgba(0, 255, 148, 0.1);
            --accent-soft-glow: rgba(0, 255, 148, 0.08);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        * { -webkit-tap-highlight-color: transparent; tap-highlight-color: transparent; }
        body {
            background: var(--primary-bg);
            color: var(--text-primary);
            font-family: 'JetBrains Mono', monospace;
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .page-main { flex: 1; }
        .product-card, .product-card a, .product-image, .product-image a, .product-image img { outline: none !important; }
        .product-card:focus, .product-card:active { outline: none !important; box-shadow: none !important; }

        .nav-header {
            position: fixed; top: 0; left: 0; right: 0;
            height: clamp(65px, 10vw, 75px);
            background: rgba(0,0,0,0.95); backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--border-subtle); z-index: 1000;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 clamp(1rem, 5vw, 4rem);
            transition: transform 0.4s ease;
        }
        .nav-logo { display: flex; align-items: center; gap: clamp(0.5rem, 2vw, 1rem); flex-shrink: 1; min-width: 0; }
        .nav-logo a { display: flex; align-items: center; gap: clamp(0.5rem, 2vw, 1rem); text-decoration: none; }
        .nav-logo img { height: clamp(35px, 8vw, 50px); width: auto; }
        .nav-logo-text { font-weight: 700; color: #fff; letter-spacing: 0.1em; text-transform: uppercase; font-size: clamp(0.9rem, 4vw, 1.3rem); white-space: nowrap; }
        .nav-actions { display: flex; gap: 0.75rem; align-items: center; flex-shrink: 0; }
        .nav-contact-btn {
            flex-shrink: 0; white-space: nowrap;
            background: rgba(0, 255, 148, 0.1); border: 1px solid var(--border-accent) !important;
            padding: 0.35rem 0; border-radius: 50px; color: var(--accent-green);
            text-decoration: none; font-weight: 600; font-size: 0.75rem; transition: 0.3s;
            text-transform: none; width: 110px; text-align: center;
        }
        .nav-contact-btn:hover { background: var(--accent-green); color: #000; transform: translateY(-2px); }
        .nav-cart-pill {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(0, 255, 148, 0.15);
            border: 1px solid var(--accent-green);
            border-radius: 12px;
            color: var(--accent-green);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: 0.3s;
        }
        .nav-cart-pill:hover { background: rgba(0, 255, 148, 0.25); box-shadow: 0 0 20px rgba(0, 255, 148, 0.2); transform: translateY(-2px); }
        .nav-cart-pill .nav-cart-emoji { font-size: 1.1rem; line-height: 1; }
        .nav-cart-pill .nav-cart-count { font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; }
        .nav-contact-btn.primary-cta {
            background: rgba(0, 255, 148, 0.25); color: var(--accent-green); font-weight: 700;
            border: 1px solid var(--border-accent) !important; box-shadow: 0 0 15px rgba(0, 255, 148, 0.2);
        }
        .nav-contact-btn.primary-cta:hover {
            background: rgba(0, 255, 148, 0.35); box-shadow: 0 0 25px rgba(0, 255, 148, 0.3);
            transform: translateY(-2px) scale(1.02);
        }

        .shop-container { padding-top: 100px; max-width: 1400px; margin: 0 auto; padding-left: 2rem; padding-right: 2rem; }
        .shop-header { text-align: center; margin-bottom: 1.5rem; }
        .shop-header h1 { font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--accent-soft); }
        .shop-header .shop-tagline { font-size: 1.25rem; color: rgba(255, 255, 255, 0.95); margin: 0.5rem 0 1rem; font-weight: 400; line-height: 1.4; }
        .shop-header p { color: var(--text-secondary); }
        .ffl-badge {
            display: inline-block; margin-top: 0.75rem; padding: 0.35rem 1rem;
            background: var(--accent-soft-bg); border: 1px solid var(--border-pill);
            border-radius: 50px; color: var(--accent-soft); font-size: 0.8rem; font-weight: 600;
            text-decoration: none; transition: 0.3s; cursor: pointer; position: relative; z-index: 1; pointer-events: auto;
        }
        .ffl-badge:hover { opacity: 0.9; border-color: var(--accent-soft); }
        .ffl-badge .ffl-location { color: #fff; }

        /* Symmetrical 2-line pills: flex 1 1 auto + max-width container forces two balanced rows; buttons grow/shrink to fill row */
        .category-filter {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            width: 100%;
            max-width: 1300px;
            margin: 0 auto 0.75rem;
        }
        .category-btn {
            flex: 1 1 auto;
            min-width: 120px;
            padding: 0.6rem 0.5rem;
            background: transparent;
            border: 1px solid var(--border-pill);
            border-radius: 50px;
            color: var(--accent-soft);
            text-decoration: none;
            font-size: 0.72rem;
            text-transform: uppercase;
            text-align: center;
            white-space: nowrap;
            letter-spacing: 0.05em;
            transition: all 0.3s ease;
        }
        .category-btn:hover {
            border-color: var(--border-pill);
            color: var(--accent-soft);
            background: var(--accent-soft-bg-hover);
        }
        .category-btn.active {
            border-color: rgba(0, 255, 148, 0.5);
            color: #fff;
            background: rgba(0, 255, 148, 0.14);
        }
        .category-btn:focus,
        .category-btn:active {
            outline: none !important;
            box-shadow: none !important;
            -webkit-tap-highlight-color: transparent;
            tap-highlight-color: transparent;
        }

        .filter-controls {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            margin-top: 1rem;
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-pill);
            border-radius: 100px;
        }
        .product-count {
            font-size: 0.85rem;
            color: #fff;
            font-family: inherit;
            white-space: nowrap;
            justify-self: start;
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-pill);
            border-radius: 50px;
            background: transparent;
        }
        .product-count strong { color: var(--accent-soft); }
        .product-count .product-count-bright { color: #fff; }

        .sleek-search {
            position: relative;
            display: flex;
            align-items: center;
            justify-self: center;
            align-self: center;
            min-width: 200px;
            max-width: 420px;
            width: 100%;
            margin: 0;
        }
        .sleek-search input {
            width: 100%;
            background: transparent !important;
            border: 1px solid var(--border-pill);
            border-radius: 50px;
            padding: 0.5rem 2.75rem 0.5rem 1.5rem;
            min-height: 38px;
            line-height: 1.25;
            box-sizing: border-box;
            color: #fff !important;
            outline: none;
            transition: 0.3s;
            font-size: 0.85rem;
        }
        .sleek-search input::placeholder { color: rgba(255,255,255,0.65); }
        .sleek-search input:focus {
            border-color: var(--accent-soft);
            box-shadow: 0 0 12px var(--accent-soft-glow);
            background: transparent !important;
            color: #fff !important;
        }
        /* Prevent browser autofill from turning the search box white */
        .sleek-search input:-webkit-autofill,
        .sleek-search input:-webkit-autofill:hover,
        .sleek-search input:-webkit-autofill:focus {
            -webkit-text-fill-color: #fff !important;
            -webkit-box-shadow: 0 0 0 1000px rgba(20, 20, 20, 0.98) inset !important;
            box-shadow: 0 0 0 1000px rgba(20, 20, 20, 0.98) inset !important;
        }
        .sleek-search button {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 24px;
            height: 24px;
            padding: 0;
            border: none;
            background: transparent;
            color: var(--accent-soft);
            cursor: pointer;
            transition: 0.3s;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .sleek-search button svg {
            width: 20px;
            height: 20px;
            fill: currentColor;
        }
        .sleek-search button:hover {
            color: #00ff94;
        }

        .filter-select {
            background: transparent;
            border: 1px solid var(--border-pill);
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            min-height: 38px;
            box-sizing: border-box;
            font-size: 0.85rem;
            cursor: pointer;
            flex-shrink: 0;
        }
        .filter-select:hover,
        .filter-select:focus {
            border-color: var(--accent-soft);
            outline: none;
            box-shadow: 0 0 10px var(--accent-soft-glow);
        }
        .filter-dropdowns {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
            justify-self: end;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }
        .product-card {
            background: rgba(255,255,255,0.02); border: 1px solid var(--border-subtle);
            border-radius: 15px; overflow: hidden; transition: 0.3s;
            display: flex; flex-direction: column;
        }
        .product-card:hover { border-color: var(--border-pill); transform: translateY(-4px); }

        /* Image containment: white box, object-fit contain so long rifles fit without black bars */
        /* Padding reduced ~25px per side to zoom product in and reduce white space */
        .product-image {
            height: 340px; /* Slightly taller to keep the gun prominent despite padding */
            background: #ffffff !important; /* Pure white background */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem !important; /* Tighter than 3rem to show more product, less margin */
            overflow: hidden;
            border-radius: 12px 12px 0 0;
            box-sizing: border-box;
        }

        .product-image a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }

        .product-image img {
            /* Use max-width/height so the image respects the padding without stretching */
            max-width: 100% !important;
            max-height: 100% !important;
            width: auto !important;
            height: auto !important;
            object-fit: contain !important;
            object-position: center !important;
            display: block;
        }

        .product-meta { padding: 1.5rem; flex-grow: 1; display: flex; flex-direction: column; }
        .stock-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem; }
        /* X LEFT stock bubble: inline-flex so number is perfectly centered inside green circle */
        .stock-pill-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border-pill);
            border-radius: 50px;
            padding: 2px 10px;
            color: var(--accent-soft);
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            background: transparent;
        }
        .stock-pill-container .left-num {
            background: var(--accent-soft);
            color: #0a0a0a;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            min-width: 18px;
            min-height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin: 0 5px;
        }
        .product-category { font-size: 0.75rem; color: var(--accent-soft); text-transform: uppercase; margin-bottom: 0.5rem; }
        .product-title { font-size: 1.05rem; margin-bottom: 0.75rem; min-height: 3.5em; line-height: 1.4; overflow-wrap: break-word; word-wrap: break-word; }
        .product-title a { color: #ffffff; text-decoration: none; }
        .product-title a:hover { color: var(--accent-soft); }
        .product-price { font-size: 1.4rem; color: var(--accent-soft); font-weight: 700; font-family: 'JetBrains Mono'; margin-bottom: 1rem; }
        .view-details {
            display: block; width: 100%; text-align: center; padding: 0.8rem;
            border: 1px solid var(--border-pill); color: var(--accent-soft);
            text-decoration: none; border-radius: 8px; font-weight: 600; transition: 0.3s; margin-top: auto;
        }
        .view-details:hover { background: var(--accent-soft-bg-hover); }

        .no-products { text-align: center; padding: 4rem 2rem; color: var(--accent-green); }
        .no-products h3 { margin-bottom: 1rem; color: var(--accent-green); }
        .no-products p { color: var(--accent-soft); }
        .no-products a { display: inline-block; margin-top: 1rem; padding: 0.8rem 1.5rem; border: 1px solid var(--border-pill); color: var(--accent-soft); text-decoration: none; border-radius: 8px; }

        @media (max-width: 1100px) {
            .filter-controls { grid-template-columns: 1fr; }
            .product-count { justify-self: start; }
            .sleek-search { justify-self: center; max-width: 100%; }
            .filter-dropdowns { justify-self: start; }
        }
        @media (max-width: 768px) {
            /* Mobile: search bar above the fold — reduce top padding and put filter row first */
            .shop-container {
                display: flex;
                flex-direction: column;
                padding-top: 62px !important;
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
            .shop-container .filter-controls {
                order: -1;
                margin-top: 0 !important;
                margin-bottom: 0.5rem !important;
                border-radius: 12px;
            }
            .shop-container .shop-header { order: 0; margin-bottom: 0.5rem !important; }
            .shop-container .category-filter { order: 1; margin-bottom: 0.5rem !important; }
            .shop-container .products-grid { order: 2; }
            .shop-header h1 { font-size: 1.75rem !important; }
            .shop-header .shop-tagline { font-size: 0.9rem !important; margin: 0.25rem 0 !important; }
            .category-btn { flex: 1 1 30%; font-size: 0.65rem; }
            .filter-controls { grid-template-columns: 1fr; align-items: stretch; }
            .product-count, .sleek-search, .filter-dropdowns { justify-self: stretch; }
            .sleek-search { max-width: none; }
            .products-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1.5rem; }
            .product-image { height: 300px; }
        }
        @media (max-width: 500px) {
            .products-grid { grid-template-columns: 1fr; }
        }
        .payment-strip { display: flex; flex-wrap: wrap; justify-content: center; gap: 2rem; padding: 0 2rem 0.5rem; }
        .payment-strip .pay-item { display: flex; align-items: center; gap: 0.3rem; color: #fff; font-size: clamp(0.85rem, 2vw, 0.95rem); font-family: 'JetBrains Mono'; }
        .payment-strip .pay-item img { height: 20px; width: auto; }
        .signature-tagline { text-align: center; font-size: clamp(1.1rem, 3vw, 1.4rem); font-style: italic; color: var(--accent-soft); margin: 2rem 0; font-weight: 300; }
        .vb-footer {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            margin: 0;
            margin-top: auto;
            padding: 15px 2rem 40px;
            border-top: 1px solid var(--border-subtle);
            background: var(--primary-bg);
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex-shrink: 0;
        }
        .vb-footer p { font-size: 0.8rem; margin-bottom: 8px; color: var(--text-secondary); }
        .vb-footer .footer-links { display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap; }
        .footer-link { color: rgba(0, 255, 148, 0.75); text-decoration: none; font-size: 0.75rem; transition: 0.2s; }
        .footer-link:hover { color: var(--accent-green); }
    </style>
</head>
<body <?php body_class(); ?>>

<header class="nav-header">
    <div class="nav-logo">
        <a href="<?php echo esc_url(home_url('/')); ?>">
            <img src="<?php echo esc_url($logo_url); ?>" alt="VB Arms">
            <span class="nav-logo-text">VB ARMS</span>
        </a>
    </div>
    <div class="nav-actions">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="nav-contact-btn">Home</a>
        <a href="<?php echo esc_url($shop_url); ?>" class="nav-contact-btn">Shop</a>
        <a href="<?php echo esc_url(home_url('/white-glove/')); ?>" class="nav-contact-btn">White Glove</a>
        <a href="<?php echo esc_url($cart_url); ?>" class="nav-cart-pill" aria-label="Cart"><span class="nav-cart-emoji" aria-hidden="true">🛒</span><span class="nav-cart-count"><?php echo (int) $cart_count; ?></span></a>
    </div>
</header>

<div class="page-main">
<?php
$category_display_names = array(
    'holsters-and-related-items' => 'HOLSTERS',
    'holsters' => 'HOLSTERS',
    'accessory-holsters' => 'HOLSTERS',
    'accessory-triggers' => 'TRIGGERS',
    'triggers' => 'TRIGGERS',
    'accessory-grips' => 'GRIPS',
    'grips' => 'GRIPS',
    'barrels' => 'BARRELS',
    'accessory-cleaning' => 'CLEANING',
    'cleaning-accessories' => 'CLEANING',
    'cleaning' => 'CLEANING',
    'accessory-parts' => 'PARTS',
    'parts' => 'PARTS',
    'accessory-barrels' => 'BARRELS',
    'optics' => 'OPTICS',
    'sights-lasers' => 'SIGHTS & LASERS',
    'sights-and-lasers' => 'SIGHTS & LASERS',
    'mounts-rings' => 'MOUNTS & RINGS',
    'mounts-and-rings' => 'MOUNTS & RINGS',
    'magazines' => 'MAGAZINES',
    'lights' => 'LIGHTS',
    'bipods' => 'BIPODS',
    'handguns' => 'HANDGUNS',
    'rifles' => 'RIFLES',
    'shotguns' => 'SHOTGUNS',
    'other-accessories' => 'OTHER ACCESSORIES',
);
// Helper: never show "Accessory-" on buttons/headings — use map or strip prefix
$vb_arms_cat_label = function( $slug, $name, $map ) {
    if ( isset( $map[ $slug ] ) ) return $map[ $slug ];
    $name = html_entity_decode( $name, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    if ( preg_match( '/^accessory[- ](.+)$/i', trim( $name ), $m ) ) $name = $m[1];
    return strtoupper( $name );
};
$current_cat_label = 'All Products';
if ( $is_category && ! empty( $current_cat ) ) {
    $current_cat_label = $vb_arms_cat_label( $current_cat->slug, $current_cat->name, $category_display_names );
}
?>

<div class="shop-container">
    <div class="shop-header">
        <h1><?php echo esc_html( $current_cat_label ); ?></h1>
        <p class="shop-tagline">Premium firearms and accessories from the industry's top manufacturers.</p>
        <a href="<?php echo esc_url($ffl_url); ?>" target="_blank" rel="noopener noreferrer" class="ffl-badge">Licensed FFL Dealer • <span class="ffl-location">Cheyenne, Wyoming</span></a>
    </div>

    <div class="category-filter">
        <a href="<?php echo esc_url($shop_url); ?>" class="category-btn <?php echo !$is_category ? 'active' : ''; ?>">All Products</a>
        <?php
        // Curated category pills only (per VB-ARMS-MAIN tracking: Firearms + Accessories subcats, no per-TYPE categories)
        $parent_order = array('Firearms', 'Accessories');
        $child_order = array(
            'Firearms' => array('Handguns', 'Rifles', 'Shotguns'),
            'Accessories' => array('Optics', 'Sights & Lasers', 'Mounts & Rings', 'Holsters', 'Magazines', 'Lights', 'Bipods', 'Grips', 'Triggers', 'Other Accessories'),
        );
        // Alternate slugs for terms that may exist under Lipsey's/legacy names (e.g. ACCESSORY-HOLSTERS)
        $child_slug_alternates = array(
            'Holsters' => array('holsters', 'accessory-holsters', 'holsters-and-related-items'),
            'Grips' => array('grips', 'accessory-grips'),
            'Triggers' => array('triggers', 'accessory-triggers'),
            'Parts' => array('parts', 'accessory-parts'),
        );
        $ordered = array();
        foreach ($parent_order as $parent_name) {
            $parent = get_term_by('name', $parent_name, 'product_cat') ?: get_term_by('slug', sanitize_title($parent_name), 'product_cat');
            if (!$parent) continue;
            foreach (isset($child_order[$parent_name]) ? $child_order[$parent_name] : array() as $child_name) {
                $child = get_term_by('name', $child_name, 'product_cat') ?: get_term_by('slug', sanitize_title($child_name), 'product_cat');
                if (!$child && isset($child_slug_alternates[$child_name])) {
                    foreach ($child_slug_alternates[$child_name] as $alt_slug) {
                        $child = get_term_by('slug', $alt_slug, 'product_cat');
                        if ($child && $child->parent == $parent->term_id) break;
                        if ($child && $child->parent != $parent->term_id) $child = null;
                    }
                }
                // If canonical term has 0 products but an alternate slug's term has products, use that term so the pill links to the populated category
                if ($child && $child->parent == $parent->term_id && isset($child_slug_alternates[$child_name])) {
                    $canonical_term = get_term( $child->term_id, 'product_cat' );
                    $canonical_count = ( $canonical_term && ! is_wp_error( $canonical_term ) ) ? (int) $canonical_term->count : 0;
                    if ($canonical_count === 0) {
                        foreach ($child_slug_alternates[$child_name] as $alt_slug) {
                            $alt_term = get_term_by('slug', $alt_slug, 'product_cat');
                            if ($alt_term && ! is_wp_error($alt_term) && $alt_term->parent == $parent->term_id && (int) $alt_term->count > 0) {
                                $child = $alt_term;
                                break;
                            }
                        }
                    }
                }
                if ($child && $child->parent == $parent->term_id) $ordered[] = $child;
            }
        }
        foreach ($ordered as $category) {
            $active = ($is_category && $current_cat->term_id === $category->term_id) ? 'active' : '';
            $label = $vb_arms_cat_label( $category->slug, $category->name, $category_display_names );
            echo '<a href="' . esc_url(get_term_link($category)) . '" class="category-btn ' . esc_attr($active) . '">' . esc_html($label) . '</a>';
        }
        ?>
    </div>

    <div class="filter-controls">
        <div class="product-count">
            <?php
            $total = isset($GLOBALS['wp_query']->found_posts) ? (int) $GLOBALS['wp_query']->found_posts : 0;
            $per_page_num = ( $per_page === 'all' ) ? max( 1, $total ) : (int) $per_page;
            if ($per_page === 'all' || $total === 0) {
                echo 'Showing <strong>all ' . esc_html( $total ) . '</strong> of <span class="product-count-bright">products</span>';
            } else {
                $paged = get_query_var('paged') ? get_query_var('paged') : 1;
                $showing_start = ( $paged - 1 ) * $per_page_num + 1;
                $showing_end = min( $showing_start + $per_page_num - 1, $total );
                echo 'Showing <strong>' . esc_html( $showing_start ) . '-' . esc_html( $showing_end ) . '</strong> of <strong>' . esc_html( $total ) . '</strong> <span class="product-count-bright">products</span>';
            }
            ?>
        </div>

        <form role="search" method="get" class="sleek-search" action="<?php echo esc_url( $shop_url ); ?>">
            <input type="search" placeholder="Search products..." name="s" value="<?php echo esc_attr( get_search_query() ); ?>">
            <input type="hidden" name="post_type" value="product">
            <?php if ( $per_page !== '12' ) : ?><input type="hidden" name="per_page" value="<?php echo esc_attr( $per_page ); ?>"><?php endif; ?>
            <?php if ( $orderby !== 'menu_order' ) : ?><input type="hidden" name="orderby" value="<?php echo esc_attr( $orderby ); ?>"><?php endif; ?>
            <button type="submit" aria-label="Search"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></button>
        </form>

        <div class="filter-dropdowns">
            <select class="filter-select" id="per-page-select" onchange="updateFilters('per_page', this.value)">
                <option value="12" <?php selected( $per_page, '12' ); ?>>Show 12</option>
                <option value="50" <?php selected( $per_page, '50' ); ?>>Show 50</option>
                <option value="100" <?php selected( $per_page, '100' ); ?>>Show 100</option>
                <option value="all" <?php selected( $per_page, 'all' ); ?>>Show All</option>
            </select>
            <select class="filter-select" id="sort-select" onchange="updateFilters('orderby', this.value)">
                <option value="menu_order" <?php selected($orderby, 'menu_order'); ?>>Default</option>
                <option value="title" <?php selected($orderby, 'title'); ?>>Name</option>
                <option value="price" <?php selected($orderby, 'price'); ?>>Price: Low</option>
                <option value="price-desc" <?php selected($orderby, 'price-desc'); ?>>Price: High</option>
                <option value="date" <?php selected($orderby, 'date'); ?>>Newest</option>
            </select>
        </div>
    </div>

    <script>
    function updateFilters(param, value) {
        var url = new URL(window.location.href);
        url.searchParams.set(param, value);
        url.searchParams.delete('paged');
        window.location.href = url.toString();
    }
    </script>

    <?php if (woocommerce_product_loop()) : ?>
    <div class="products-grid">
        <?php
        while (have_posts()) {
            the_post();
            global $product;
            if ($hide_products_without_image && !$product->get_image_id()) continue;
            $has_image = (bool) $product->get_image_id();
            $category_terms = wp_get_post_terms($product->get_id(), 'product_cat');
            $category_name = '';
            if (!empty($category_terms)) {
                $cat = $category_terms[0];
                $category_name = $vb_arms_cat_label( $cat->slug, $cat->name, $category_display_names );
            }
            $in_stock = $product->is_in_stock();
            $stock_qty = $product->get_stock_quantity();
        ?>
        <div class="product-card">
            <div class="product-image">
                <a href="<?php the_permalink(); ?>"><?php echo $has_image ? $product->get_image('large') : '<img src="' . esc_url(wc_placeholder_img_src('large')) . '" alt="" />'; ?></a>
            </div>
            <div class="product-meta">
                <div class="stock-row">
                    <span style="color:var(--text-secondary); font-size:0.8rem;"><?php echo $in_stock ? '● In Stock' : '● Out of Stock'; ?></span>
                    <?php if ($in_stock && $stock_qty !== null && $stock_qty < 5) : ?>
                        <div class="stock-pill-container">
                            <span class="left-num"><?php echo (int) $stock_qty; ?></span> LEFT
                        </div>
                    <?php elseif (!$in_stock) : ?>
                        <div class="stock-pill-container" style="border-color: rgba(255,255,255,0.3); color: var(--text-secondary);">Out of Stock</div>
                    <?php endif; ?>
                </div>
                <?php if ($category_name) : ?><div class="product-category"><?php echo esc_html($category_name); ?></div><?php endif; ?>
                <h3 class="product-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <div class="product-price"><?php echo $product->get_price_html(); ?></div>
                <?php
                $mfg_upc = function_exists('vb_arms_product_mfg_upc') ? vb_arms_product_mfg_upc($product) : array('mfg_mdl' => '', 'upc' => '');
                ?>
                <div class="product-mfg-upc" style="font-size:0.8rem; color:var(--text-secondary); margin-top:0.35rem;">
                    MFG MDL #: <?php echo $mfg_upc['mfg_mdl'] !== '' ? esc_html( $mfg_upc['mfg_mdl'] ) : '—'; ?><br>
                    UPC: <?php echo $mfg_upc['upc'] !== '' ? esc_html( $mfg_upc['upc'] ) : '—'; ?>
                </div>
                <a href="<?php the_permalink(); ?>" class="view-details">View Details</a>
            </div>
        </div>
        <?php } ?>
    </div>

    <?php woocommerce_pagination(); ?>

    <?php else : ?>
    <div class="no-products">
        <h3>No Products Found</h3>
        <p>We're currently updating our inventory. Check back soon or pick a category above.</p>
        <a href="<?php echo esc_url($shop_url); ?>">View All Categories</a>
    </div>
    <?php endif; ?>
</div>
</div><!-- .page-main -->

<div style="text-align: center; margin: 50px 0;">
    <a href="<?php echo esc_url( home_url( '/white-glove/' ) ); ?>" class="nav-contact-btn" style="padding: 0.5rem 2rem; font-size: 0.95rem;">Request a Custom Quote</a>
</div>

<div class="payment-strip">
    <div class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/btc.png' ); ?>" alt=""> BTC</div>
    <div class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/eth.png' ); ?>" alt=""> ETH</div>
    <div class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/usdc-logo.png' ); ?>" alt=""> USDC</div>
    <div class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/frnt.png' ); ?>" alt=""> FRNT</div>
    <div class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/metal-mtl-logo.png' ); ?>" alt=""> MTL</div>
    <div class="pay-item"><img src="<?php echo esc_url( function_exists( 'vb_arms_metamask_logo_url' ) ? vb_arms_metamask_logo_url() : ( $upload_dir['baseurl'] . '/logos/metamask.svg' ) ); ?>" alt=""> MetaMask</div>
    <div class="pay-item"><img src="<?php echo esc_url( function_exists( 'vb_arms_usdt_logo_url' ) ? vb_arms_usdt_logo_url() : ( $upload_dir['baseurl'] . '/logos/usdt-logo' ) ); ?>" alt=""> USDT</div>
    <div class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/us-dollar-512.png' ); ?>" alt=""> Traditional Payments</div>
</div>

<footer class="vb-footer">
    <div class="signature-tagline">Your target. Our acquisition.</div>
    <p>© <?php echo date('Y'); ?> VB ARMS • Bespoke Firearms Procurement</p>
    <div class="footer-links">
        <a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>" class="footer-link">Privacy Policy</a>
        <a href="<?php echo esc_url(home_url('/refund-returns/')); ?>" class="footer-link">Refund & Returns</a>
        <a href="<?php echo esc_url(home_url('/terms-of-service/')); ?>" class="footer-link">Terms of Service</a>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
