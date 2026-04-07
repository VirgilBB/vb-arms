<?php
/**
 * VB Arms - Single Product Page
 * 2x Lightbox Zoom, 3x3 Related Products Grid, Image Contain
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

global $product;
// Always use shop listing URL (never a product URL). Filter can override.
$single_shop_url = apply_filters('vb_arms_breadcrumb_shop_url', home_url('/shop/'));
$single_shop_url = (empty($single_shop_url) || trim($single_shop_url) === '') ? home_url('/shop/') : $single_shop_url;
$ffl_url = 'https://www.ffls.com/ffl/583021019a04518/benin-llc';
$cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
$cart_count = (function_exists('WC') && WC()->cart) ? WC()->cart->get_cart_contents_count() : 0;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php the_title(); ?> | <?php bloginfo('name'); ?></title>
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
            --border-pill: rgba(0, 255, 148, 0.5);
            --border-accent: rgba(0, 255, 148, 0.3);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        * { -webkit-tap-highlight-color: transparent; tap-highlight-color: transparent; }
        body { background: var(--primary-bg); color: var(--text-primary); font-family: 'JetBrains Mono', monospace; line-height: 1.6; }
        .site-header, .site-footer, .storefront-breadcrumb { display: none !important; }
        .p-image-box, .p-image-box a, .p-image-box img, .product-image, .product-image a, .product-card, .related-card, .related-img a { outline: none !important; }
        .p-image-box:focus, .p-image-box:active, .product-card:focus, .product-card:active { outline: none !important; box-shadow: none !important; }

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
        .nav-contact-btn.primary-cta {
            background: rgba(0, 255, 148, 0.25); color: var(--accent-green); font-weight: 700;
            border: 1px solid var(--border-accent) !important; box-shadow: 0 0 15px rgba(0, 255, 148, 0.2);
        }
        .nav-contact-btn.primary-cta:hover {
            background: rgba(0, 255, 148, 0.35); box-shadow: 0 0 25px rgba(0, 255, 148, 0.3);
            transform: translateY(-2px) scale(1.02);
        }
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
        .ffl-badge {
            display: inline-block; margin-bottom: 1rem; padding: 0.35rem 1rem;
            background: rgba(0, 255, 148, 0.06); border: 1px solid rgba(0, 255, 148, 0.3);
            border-radius: 50px; color: rgba(0, 255, 148, 0.9); font-size: 0.8rem; font-weight: 600;
            text-decoration: none; cursor: pointer; transition: 0.3s;
        }
        .ffl-badge:hover { opacity: 0.9; border-color: var(--accent-green); }
        .ffl-badge .ffl-location { color: #fff; }

        .p-container { padding: 120px 5% 5rem; max-width: 1400px; margin: 0 auto; }
        .product-header-area { margin-bottom: 1rem; }
        .product-header-area .ffl-badge { margin-bottom: 0.45rem; }
        .product-header-area .custom-breadcrumb { font-size: 0.85rem; color: #b8b8b8; border-bottom: 1px solid #00ff94 !important; padding-bottom: 0.45rem; margin-bottom: 0; }
        .custom-breadcrumb a {
            color: var(--accent-green); text-decoration: none;
            -webkit-tap-highlight-color: transparent; tap-highlight-color: transparent;
            outline: none;
        }
        .custom-breadcrumb a:hover { text-decoration: underline; }
        .custom-breadcrumb a:focus,
        .custom-breadcrumb a:active {
            outline: none !important; box-shadow: none !important;
            -webkit-tap-highlight-color: transparent; tap-highlight-color: transparent;
        }

        .p-layout { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 3rem; margin-top: 2rem; margin-bottom: 5rem; align-items: start; }
        .p-info-col { min-height: 600px; display: flex; flex-direction: column; }
        .p-image-col { position: relative; }
        .title-pill-below-breadcrumb {
            display: inline-block; text-align: left; margin-top: 0.45rem; margin-bottom: 0.5rem;
            max-width: 100%;
            background: transparent; color: var(--accent-green); padding: 5px 16px;
            border: 1px solid var(--border-pill); border-radius: 50px; font-weight: 700; font-size: 0.8rem;
            text-transform: uppercase; letter-spacing: 0.05em;
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        /* White background – catalog look; padding reduced ~25px per side to zoom product in */
        /* Main Product Gallery Box */
        .p-image-box {
            background: #ffffff !important;
            border: 1px solid var(--border-subtle);
            border-radius: 15px;
            height: 600px; /* Large height for the main feature */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3.5rem !important; /* Tighter than 5rem to show more product, less white space */
            cursor: zoom-in;
            overflow: hidden;
            box-sizing: border-box;
        }

        .p-image-box img {
            max-width: 100%;
            max-height: 100%;
            width: auto !important;
            height: auto !important;
            object-fit: contain;
        }
        .zoom-hint { text-align: center; color: var(--text-secondary); margin-top: 8px; font-size: 0.7rem; }

        .vb-lightbox {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.35);
            z-index: 99999;
            overflow: auto;
            cursor: zoom-out;
        }
        .vb-lightbox.active { display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .vb-lightbox img {
            display: block;
            max-width: min(96vw, 100%);
            max-height: 94vh;
            width: auto;
            height: auto;
            object-fit: contain;
        }
        .vb-lightbox-close {
            position: fixed; top: 1.5rem; right: 1.5rem;
            width: 48px; height: 48px;
            background: rgba(0,0,0,0.8);
            border: 2px solid var(--accent-green);
            color: var(--accent-green);
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            z-index: 100000;
            display: flex; align-items: center; justify-content: center;
        }

        /* Top product container: thin green border, reduced padding */
        .p-info-col .product-top-container {
            border: 1px solid var(--border-pill);
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            container-type: inline-size;
            container-name: product-top;
        }
        .p-info-col .product-top-container .product-manufacturer { font-size: 2rem; font-weight: 700; color: var(--accent-green); margin-bottom: 0.35rem; letter-spacing: 0.02em; line-height: 1; }
        .p-info-col .product-top-container .product-full-title {
            font-size: clamp(0.65rem, 4.5cqw, 1.6rem); font-weight: 500; color: #fff; margin-bottom: 0.5rem; line-height: 1.3;
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: break-word;
            min-width: 0;
        }
        .p-info-col .product-top-container .product-caliber { font-size: 1.1rem; color: var(--text-secondary); margin-bottom: 1rem; line-height: 1.4; }
        .p-info-col .product-top-container .product-price { font-size: 2.5rem; color: var(--accent-green); font-family: 'JetBrains Mono'; margin-bottom: 0.2rem; }
        .p-info-col .product-top-container .product-msrp { font-size: 1rem; color: var(--text-secondary); margin-bottom: 0; }
        .product-meta-box {
            border: 1px solid var(--border-pill); border-radius: 12px; padding: 1rem 1.5rem;
            margin-bottom: 1.5rem; font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6;
        }
        .product-meta-box .meta-row { display: flex; margin-bottom: 0.25rem; }
        .product-meta-box .meta-row:last-child { margin-bottom: 0; }
        .product-meta-box .meta-label { width: 120px; flex-shrink: 0; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; color: var(--text-secondary); }
        .product-meta-box .meta-val { color: #e0e0e0; }
        /* Specs container: thin green border, aligned with product meta/title above */
        .p-info-col .product-bottom-container {
            border: 1px solid #00ff94 !important;
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #e0e0e0;
            line-height: 1.5;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            background: rgba(0, 255, 148, 0.02);
        }
        .product-bottom-container .specs-heading { font-family: 'JetBrains Mono'; color: var(--accent-green); font-size: 1.2rem; margin-bottom: 1.5rem; text-transform: uppercase; }
        .product-bottom-container .p-specs-compact { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem 2rem; }
        .product-bottom-container .p-specs-compact .spec-row { display: flex; flex-direction: column; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 8px 0; }
        .product-bottom-container .p-specs-compact .spec-label { font-size: 0.7rem; text-transform: uppercase; color: var(--text-secondary); letter-spacing: 0.05em; margin-bottom: 2px; }
        .product-bottom-container .p-specs-compact .spec-value { font-size: 0.9rem; color: #fff; font-weight: 500; }
        @media (max-width: 900px) { .product-bottom-container .p-specs-compact { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 600px) { .product-bottom-container .p-specs-compact { grid-template-columns: 1fr; } }
        /* Hide redundant features line if output by short description or other source */
        .p-info-col .product-features-line,
        .product-bottom-container .product-features-line { display: none !important; }
        /* Stock message: bright neon green */
        .p-info-col .cart .stock,
        .p-info-col .woocommerce-variation-availability .stock,
        .p-info-col p.stock,
        .p-info-col .single_variation_wrap .stock,
        .stock { color: #00ff94 !important; opacity: 1 !important; font-weight: 700 !important; font-size: 1.1rem !important; }
        .p-info-col .product-description { color: #e0e0e0; line-height: 1.8; margin-bottom: 1rem; font-size: 0.95rem; }
        .p-info-col .add-to-cart-section { margin-top: 1rem; }
        /* Spacing so quantity box doesn't touch Add to cart button */
        .p-info-col .cart .quantity { margin-bottom: 1rem; }
        /* Quantity input: modern style like checkout State dropdown (transparent, green border); keep up/down arrows */
        .p-info-col .quantity input.qty {
            background: transparent !important;
            border: 1px solid var(--border-pill) !important;
            color: #fff !important;
            border-radius: 12px !important;
            padding: 0.5rem 1rem !important;
            font-size: 0.95rem !important;
            font-family: 'JetBrains Mono', monospace !important;
            width: 4.5em !important;
            min-height: 42px !important;
        }
        .p-info-col .quantity input.qty::-webkit-inner-spin-button,
        .p-info-col .quantity input.qty::-webkit-outer-spin-button {
            opacity: 1;
            color: var(--accent-green);
        }
        .p-info-col .quantity input.qty:focus {
            outline: none !important;
            border-color: var(--accent-green) !important;
            box-shadow: 0 0 0 1px var(--accent-green);
        }
        .p-info-col .cart button.single_add_to_cart_button,
        .p-info-col .cart button[type="submit"] {
            background: transparent !important;
            border: 1px solid var(--accent-green);
            color: var(--accent-green);
            border-radius: 50px;
            padding: 0.75rem 1.75rem;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
            outline: none !important;
        }
        .p-info-col .cart button.single_add_to_cart_button:hover,
        .p-info-col .cart button[type="submit"]:hover {
            background: rgba(0, 255, 148, 0.1) !important;
        }
        .p-info-col .cart button:focus { outline: none !important; box-shadow: none !important; }

        .p-specs-section { margin-top: 3rem; padding-top: 2.5rem; border-top: 1px solid #222; margin-bottom: 2rem; }
        .p-specs-section h3 { font-family: 'JetBrains Mono'; color: var(--accent-green); font-size: 1.35rem; margin-bottom: 1.25rem; }
        .p-specs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem 2rem; font-size: 1.05rem; color: #e0e0e0; line-height: 1.6; }
        .p-specs-grid dt { color: var(--text-secondary); font-weight: 600; margin-bottom: 0.15rem; }
        .p-specs-grid dd { margin: 0 0 0.5rem 0; }

        .related-section { margin-top: 5rem; padding-top: 4rem; border-top: 1px solid #222; }
        .related-section h2 { font-family: 'JetBrains Mono'; color: var(--accent-green); text-align: center; margin-bottom: 3rem; font-size: 2rem; }
        .related-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }
        .related-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid #222;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        /* Related Product Thumbnails at bottom of page; padding reduced ~25px per side to zoom in */
        .related-img {
            height: 260px;
            background: #ffffff !important;
            padding: 1rem !important; /* Tighter than 2.5rem to show more product */
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-bottom: 1px solid var(--border-subtle);
        }
        .related-img a { display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; }
        .related-img img {
            max-width: 100%;
            max-height: 100%;
            width: auto !important;
            height: auto !important;
            object-fit: contain;
        }
        .related-info { padding: 1.5rem; text-align: left; flex-grow: 1; }
        .related-info .related-cat { color: var(--accent-green); font-size: 0.7rem; text-transform: uppercase; }
        .related-info h4 { margin: 10px 0; font-size: 1rem; height: 2.8em; overflow: hidden; line-height: 1.35; }
        .related-info h4 a { color: #fff; text-decoration: none; }
        .related-info h4 a:hover { color: var(--accent-green); }
        .related-info .price { color: var(--accent-green); font-family: 'JetBrains Mono'; font-weight: 700; margin-bottom: 1rem; }
        .related-info .view-details { display: inline-block; padding: 0.5rem 1rem; font-size: 0.85rem; border: 1px solid var(--accent-green); color: var(--accent-green); text-decoration: none; border-radius: 8px; }
        .related-info .view-details:hover { background: rgba(0, 255, 148, 0.1); }

        .payment-strip { display: flex; flex-wrap: wrap; justify-content: center; gap: 2rem; padding: 0 2rem 0.5rem; }
        .payment-strip .pay-item { display: flex; align-items: center; gap: 0.3rem; color: #fff; font-size: clamp(0.85rem, 2vw, 0.95rem); font-family: 'JetBrains Mono'; }
        .payment-strip .pay-item img { height: 20px; width: auto; }
        .signature-tagline { text-align: center; font-size: clamp(1.1rem, 3vw, 1.4rem); font-style: italic; color: var(--accent-green); margin: 2rem 0; font-weight: 300; }
        .vb-footer { padding: 15px 2rem 40px; text-align: center; border-top: 1px solid var(--border-subtle); }
        .vb-footer p { font-size: 0.8rem; margin-bottom: 8px; color: var(--text-secondary); }
        .vb-footer .footer-links { display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap; }
        .footer-link { color: rgba(0, 255, 148, 0.75); text-decoration: none; font-size: 0.75rem; transition: 0.2s; }
        .footer-link:hover { color: var(--accent-green); }

        @media (max-width: 768px) {
            .p-layout { grid-template-columns: 1fr; gap: 2rem; }
            .p-info-col { min-height: 0; }
            .title-pill-below-breadcrumb { max-width: 100%; font-size: 0.7rem; white-space: normal; padding: 6px 12px; }
            .related-grid { grid-template-columns: 1fr; gap: 1.5rem; }
        }
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
        <a href="<?php echo esc_url($single_shop_url); ?>" class="nav-contact-btn">Shop</a>
        <a href="<?php echo esc_url(home_url('/white-glove/')); ?>" class="nav-contact-btn">White Glove</a>
        <a href="<?php echo esc_url($cart_url); ?>" class="nav-cart-pill" aria-label="Cart"><span class="nav-cart-emoji" aria-hidden="true">🛒</span><span class="nav-cart-count"><?php echo (int) $cart_count; ?></span></a>
    </div>
</header>

<div class="p-container">
<?php while (have_posts()) : the_post(); global $product; ?>
    <?php $shop_url = $single_shop_url; ?>
    <?php
    $breadcrumb_category_display = array('accessory-parts' => 'PARTS', 'parts' => 'PARTS');
    ?>
    <div class="product-header-area">
        <a href="<?php echo esc_url($ffl_url); ?>" target="_blank" rel="noopener noreferrer" class="ffl-badge">Licensed FFL Dealer • <span class="ffl-location">Cheyenne, Wyoming</span></a>
        <div class="custom-breadcrumb">
            <a href="<?php echo esc_url($shop_url); ?>">Shop</a>
            <?php
            $categories = wp_get_post_terms($product->get_id(), 'product_cat');
            if (!empty($categories)) {
                $cat = $categories[0];
                $cat_label = isset($breadcrumb_category_display[$cat->slug]) ? $breadcrumb_category_display[$cat->slug] : esc_html($cat->name);
                echo ' / <a href="' . esc_url(get_term_link($cat)) . '">' . $cat_label . '</a>';
            }
            ?>
            / <span style="color: #fff;"><?php the_title(); ?></span>
        </div>
    </div>
    <?php
    $lipseys_title_for_pill = get_post_meta( $product->get_id(), '_lipseys_display_title', true );
    $lipseys_title_for_pill = is_string( $lipseys_title_for_pill ) ? trim( $lipseys_title_for_pill ) : '';
    $title_pill_text = ( $lipseys_title_for_pill !== '' ) ? $lipseys_title_for_pill : $product->get_name();
    ?>
    <div class="title-pill-below-breadcrumb"><?php echo esc_html( $title_pill_text ); ?></div>

    <div class="p-layout">
        <div class="p-image-col">
            <div class="p-image-box" id="zoom-trigger">
                <?php echo $product->get_image('full'); ?>
            </div>
            <p class="zoom-hint">Click image to zoom 2X</p>
        </div>

        <div class="p-info-col">
            <?php
            $meta_block = function_exists('vb_arms_product_meta_block') ? vb_arms_product_meta_block($product) : array('manufacturer' => '', 'mfg_mdl' => '', 'upc' => '', 'msrp_formatted' => '');
            $lipseys_title = get_post_meta($product->get_id(), '_lipseys_display_title', true);
            $lipseys_title = is_string($lipseys_title) ? trim($lipseys_title) : '';
            $main_title = '';
            $features_line = '';
            if ( $lipseys_title !== '' ) {
                if ( strpos( $lipseys_title, ' – ' ) !== false ) {
                    $parts = explode( ' – ', $lipseys_title, 2 );
                    $main_title = trim( $parts[0] );
                    $features_line = isset( $parts[1] ) ? trim( $parts[1] ) : '';
                } elseif ( preg_match( '/\s+-\s+/', $lipseys_title ) ) {
                    $parts = preg_split( '/\s+-\s+/', $lipseys_title, 2 );
                    $main_title = trim( $parts[0] );
                    $features_line = isset( $parts[1] ) ? trim( $parts[1] ) : '';
                } elseif ( strpos( $lipseys_title, ' | ' ) !== false ) {
                    $parts = explode( ' | ', $lipseys_title, 2 );
                    $main_title = trim( $parts[0] );
                    $features_line = isset( $parts[1] ) ? trim( $parts[1] ) : '';
                } else {
                    $main_title = $lipseys_title;
                }
            }
            $caliber = $product->get_attribute( 'pa_caliber' );
            $caliber = is_string( $caliber ) ? trim( $caliber ) : '';
            ?>
            <div class="product-top-container">
                <?php if ( isset( $meta_block['manufacturer'] ) && $meta_block['manufacturer'] !== '' ) : ?>
                    <p class="product-manufacturer"><?php echo esc_html( $meta_block['manufacturer'] ); ?></p>
                <?php endif; ?>
                <p class="product-full-title"><?php echo esc_html( $lipseys_title !== '' ? ( $main_title !== '' ? $main_title : $lipseys_title ) : $product->get_name() ); ?></p>
                <?php if ( $caliber !== '' ) : ?>
                    <p class="product-caliber"><?php echo esc_html( $caliber ); ?></p>
                <?php endif; ?>
                <div class="product-price"><?php echo $product->get_price_html(); ?></div>
                <?php if ( ! empty( $meta_block['msrp_formatted'] ) ) : ?>
                    <div class="product-msrp">MSRP: <?php echo wp_kses_post( $meta_block['msrp_formatted'] ); ?></div>
                <?php endif; ?>
            </div>
            <!-- 1. META BOX (Manufacturer, UPC, MSRP) -->
            <div class="product-meta-box">
                <div class="meta-row"><span class="meta-label">Manufacturer</span><span class="meta-val"><?php echo $meta_block['manufacturer'] !== '' ? esc_html( $meta_block['manufacturer'] ) : '—'; ?></span></div>
                <div class="meta-row"><span class="meta-label">UPC</span><span class="meta-val"><?php echo $meta_block['upc'] !== '' ? esc_html( $meta_block['upc'] ) : '—'; ?></span></div>
                <?php if ( $meta_block['msrp_formatted'] !== '' ) : ?>
                    <div class="meta-row"><span class="meta-label">MSRP</span><span class="meta-val"><?php echo wp_kses_post( $meta_block['msrp_formatted'] ); ?></span></div>
                <?php endif; ?>
            </div>

            <!-- 2. THE GREEN SPECIFICATIONS BOX -->
            <div class="product-bottom-container" style="border: 1px solid #00ff94; border-radius: 12px; padding: 1.5rem; background: rgba(0, 255, 148, 0.02); margin-bottom: 1.5rem;">
                <div class="p-specs-compact" style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php
                    $highlight_specs = array(
                        'Manufacturer'  => $meta_block['manufacturer'],
                        'Model'         => get_post_meta( $product->get_id(), '_model', true ) ?: $meta_block['mfg_mdl'],
                        'Caliber/Gauge' => $product->get_attribute( 'pa_caliber' ),
                        'Barrel Length' => $product->get_attribute( 'pa_barrel_length' ),
                        'Capacity'      => $product->get_attribute( 'pa_capacity' ),
                        'Finish'        => $product->get_attribute( 'pa_finish' )
                    );
                    foreach ( $highlight_specs as $label => $val ) :
                        if ( ! empty( $val ) ) :
                    ?>
                        <div class="spec-row" style="display: flex; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 4px;">
                            <span class="spec-label" style="font-weight: 700; color: #fff; min-width: 140px;"><?php echo esc_html( $label ); ?>:</span>
                            <span class="spec-value" style="color: #e0e0e0;"><?php echo esc_html( $val ); ?></span>
                        </div>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </div>
            </div>

            <!-- 3. FFL WARNING & STOCK -->
            <div class="ffl-shipping-notice" style="margin-bottom: 1rem; color: #fff; font-size: 0.9rem;">
                <span style="color: var(--accent-green);">⚠️ FFL Required:</span> This item must be shipped to a licensed FFL dealer.
            </div>

            <?php
            // Short description removed here to avoid Lipsey's redundant "WALNUT STOCK | ADJ SIGHTS" line
            woocommerce_template_single_add_to_cart();
            ?>

    <?php
    $pid = $product->get_id();
    // Build specs by attribute slug (pa_*) so we get every attribute the importer set
    $specs_by_slug = array();
    $attrs = $product->get_attributes();
    if ( ! empty( $attrs ) ) {
        foreach ( $attrs as $slug => $attr ) {
            if ( ! is_object( $attr ) ) {
                continue;
            }
            $name = is_string( $slug ) ? $slug : $attr->get_name();
            $options = $attr->get_options();
            $value = is_array( $options ) ? implode( ', ', array_map( 'esc_html', $options ) ) : '';
            if ( (string) $value !== '' ) {
                $specs_by_slug[ $name ] = $value;
            }
        }
    }
    // Also get any attribute via get_attribute() in case storage differs
    $pa_slugs = array( 'pa_manufacturer', 'pa_family', 'pa_caliber', 'pa_action', 'pa_finish', 'pa_finish_type', 'pa_stock', 'pa_barrel_length', 'pa_overall_length', 'pa_rate_of_twist', 'pa_capacity', 'pa_number_of_magazines', 'pa_mag_description', 'pa_sights', 'pa_sight_type', 'pa_weight', 'pa_shipping_weight', 'pa_safety', 'pa_additional_info', 'pa_receiver', 'pa_magazine', 'pa_chamber' );
    foreach ( $pa_slugs as $slug ) {
        if ( isset( $specs_by_slug[ $slug ] ) ) {
            continue;
        }
        $val = $product->get_attribute( $slug );
        $val = is_string( $val ) ? trim( $val ) : '';
        if ( $val !== '' ) {
            $specs_by_slug[ $slug ] = $val;
        }
    }

    // Meta fallbacks (Lipsey's / vb_arms meta)
    $mfg_mdl_no = get_post_meta( $pid, '_manufacturer_part_number', true );
    $mfg_mdl_no = is_string( $mfg_mdl_no ) ? trim( $mfg_mdl_no ) : '';
    if ( $mfg_mdl_no === '' ) {
        $mfg_mdl_no = get_post_meta( $pid, '_model', true );
        $mfg_mdl_no = is_string( $mfg_mdl_no ) ? trim( $mfg_mdl_no ) : '';
    }
    if ( $mfg_mdl_no === '' && ! empty( $meta_block['mfg_mdl'] ) ) {
        $mfg_mdl_no = $meta_block['mfg_mdl'];
    }
    $upc_code = get_post_meta( $pid, '_upc', true );
    $upc_code = is_string( $upc_code ) ? trim( $upc_code ) : '';
    if ( $upc_code === '' && ! empty( $meta_block['upc'] ) ) {
        $upc_code = $meta_block['upc'];
    }
    $lipseys_itemgroup = get_post_meta( $pid, '_lipseys_itemgroup', true );
    $lipseys_itemgroup = is_string( $lipseys_itemgroup ) ? trim( $lipseys_itemgroup ) : '';
    $lipseys_type = get_post_meta( $pid, '_lipseys_type', true );
    $lipseys_type = is_string( $lipseys_type ) ? trim( $lipseys_type ) : '';

    if ( ! isset( $specs_by_slug['pa_manufacturer'] ) && ! empty( $meta_block['manufacturer'] ) ) {
        $specs_by_slug['pa_manufacturer'] = $meta_block['manufacturer'];
    }
    if ( $lipseys_type !== '' ) {
        $specs_by_slug['pa_type'] = $lipseys_type;
    }
    if ( $lipseys_itemgroup !== '' ) {
        $specs_by_slug['pa_item_group'] = $lipseys_itemgroup;
    }
    if ( $upc_code !== '' ) {
        $specs_by_slug['pa_upc'] = $upc_code;
    }

    // Lipsey's-style display labels and order (match their SPECIFICATIONS page)
    $slug_to_label = array(
        'pa_manufacturer' => 'Manufacturer',
        'pa_family' => 'Family',
        'pa_model' => 'Model',
        'pa_type' => 'Type',
        'pa_item_group' => 'Item Group',
        'pa_action' => 'Action',
        'pa_caliber' => 'Caliber/Gauge',
        'pa_finish' => 'Finish',
        'pa_finish_type' => 'Finish Type',
        'pa_stock' => 'Stock',
        'pa_barrel_length' => 'Barrel',
        'pa_overall_length' => 'Overall Length',
        'pa_rate_of_twist' => 'Rate-of-Twist',
        'pa_capacity' => 'Capacity',
        'pa_number_of_magazines' => '# of Magazines',
        'pa_mag_description' => 'Mag Description',
        'pa_sights' => 'Sights',
        'pa_sight_type' => 'Sight Type',
        'pa_weight' => 'Weight',
        'pa_shipping_weight' => 'Shipping Weight',
        'pa_safety' => 'Safety Features',
        'pa_additional_info' => 'Addl Info',
        'pa_upc' => 'UPC',
        'pa_receiver' => 'Receiver',
        'pa_magazine' => 'Magazine',
        'pa_chamber' => 'Chamber',
    );
    $order_slugs = array( 'pa_manufacturer', 'pa_family', 'pa_model', 'pa_type', 'pa_item_group', 'pa_action', 'pa_caliber', 'pa_finish', 'pa_finish_type', 'pa_stock', 'pa_barrel_length', 'pa_overall_length', 'pa_rate_of_twist', 'pa_capacity', 'pa_number_of_magazines', 'pa_mag_description', 'pa_sights', 'pa_sight_type', 'pa_weight', 'pa_shipping_weight', 'pa_safety', 'pa_additional_info', 'pa_upc', 'pa_receiver', 'pa_magazine', 'pa_chamber' );
    // Model: short name from _model (e.g. "M1A Tanker"); MFG Model No from part number (e.g. AA9622)
    $model_short = get_post_meta( $pid, '_model', true );
    $model_short = is_string( $model_short ) ? trim( $model_short ) : '';
    if ( $model_short !== '' ) {
        $specs_by_slug['pa_model'] = $model_short;
    } elseif ( $mfg_mdl_no !== '' ) {
        $specs_by_slug['pa_model'] = $mfg_mdl_no;
    }
    if ( $mfg_mdl_no !== '' ) {
        $specs_by_slug['pa_mfg_model_no'] = $mfg_mdl_no;
    }
    $slug_to_label['pa_mfg_model_no'] = 'MFG Model No';
    $order_slugs[] = 'pa_mfg_model_no';
    ?>
    <div class="product-bottom-container">
        <h4 class="specs-heading"><?php echo esc_html( $mfg_mdl_no !== '' ? $mfg_mdl_no . ' SPECIFICATIONS' : 'PRODUCT SPECIFICATIONS' ); ?></h4>
        <div class="p-specs-compact">
            <?php
            $shown = array();
            foreach ( $order_slugs as $slug ) {
                if ( ! isset( $specs_by_slug[ $slug ] ) ) {
                    continue;
                }
                $val = $specs_by_slug[ $slug ];
                if ( (string) $val === '' ) {
                    continue;
                }
                $label = isset( $slug_to_label[ $slug ] ) ? $slug_to_label[ $slug ] : $slug;
                $shown[ $slug ] = true;
                ?>
                <div class="spec-row">
                    <span class="spec-label"><?php echo esc_html( $label ); ?></span>
                    <span class="spec-value"><?php echo esc_html( $val ); ?></span>
                </div>
            <?php
            }
            foreach ( $specs_by_slug as $slug => $val ) {
                if ( isset( $shown[ $slug ] ) || (string) $val === '' ) {
                    continue;
                }
                $label = isset( $slug_to_label[ $slug ] ) ? $slug_to_label[ $slug ] : ( function_exists( 'wc_attribute_label' ) ? wc_attribute_label( $slug ) : $slug );
                ?>
                <div class="spec-row">
                    <span class="spec-label"><?php echo esc_html( $label ); ?></span>
                    <span class="spec-value"><?php echo esc_html( $val ); ?></span>
                </div>
            <?php
            }
            ?>
        </div>
    </div>

        </div>
    </div>

    <?php
    $related_ids = wc_get_related_products($product->get_id(), 9);
    $related_with_images = array();
    foreach ($related_ids as $rid) {
        $rp = wc_get_product($rid);
        if ($rp && $rp->get_image_id()) $related_with_images[] = $rid;
    }
    if (!empty($related_with_images)) :
    ?>
    <div class="related-section">
        <h2>Popular Add-On Items</h2>
        <div class="related-grid">
            <?php foreach ($related_with_images as $r_id) :
                $rp = wc_get_product($r_id);
                if (!$rp) continue;
                $related_cats = wp_get_post_terms($rp->get_id(), 'product_cat', array('fields' => 'names'));
            ?>
            <div class="related-card">
                <div class="related-img">
                    <a href="<?php echo get_permalink($r_id); ?>"><?php echo $rp->get_image('medium'); ?></a>
                </div>
                <div class="related-info">
                    <?php if (!empty($related_cats)) : ?><span class="related-cat"><?php echo esc_html($related_cats[0]); ?></span><?php endif; ?>
                    <h4><a href="<?php echo get_permalink($r_id); ?>"><?php echo $rp->get_name(); ?></a></h4>
                    <div class="price"><?php echo $rp->get_price_html(); ?></div>
                    <a href="<?php echo get_permalink($r_id); ?>" class="view-details">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
<?php endwhile; ?>
</div>

<div class="vb-lightbox" id="vb-zoom">
    <button type="button" class="vb-lightbox-close" id="vb-lightbox-close" aria-label="Close">&times;</button>
    <?php
    $img_id = $product->get_image_id();
    $full_url = $img_id ? wp_get_attachment_image_url($img_id, 'full') : '';
    if ($full_url) :
    ?>
    <img src="<?php echo esc_url($full_url); ?>" alt="<?php echo esc_attr($product->get_name()); ?>">
    <?php endif; ?>
</div>

<script>
(function() {
    var box = document.getElementById('zoom-trigger');
    var lb = document.getElementById('vb-zoom');
    var closeBtn = document.getElementById('vb-lightbox-close');
    if (box && lb) {
        box.onclick = function() { lb.classList.add('active'); };
        lb.onclick = function(e) { if (e.target === lb) lb.classList.remove('active'); };
        if (closeBtn) closeBtn.onclick = function() { lb.classList.remove('active'); };
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') lb.classList.remove('active');
        });
    }
})();
</script>

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

<div class="signature-tagline">Your target. Our acquisition.</div>
<footer class="vb-footer">
    <p>© <?php echo date('Y'); ?> VB ARMS • Bespoke Firearms Procurement</p>
    <div style="display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap;">
        <a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>" class="footer-link">Privacy Policy</a>
        <a href="<?php echo esc_url(home_url('/refund-returns/')); ?>" class="footer-link">Refund & Returns</a>
        <a href="<?php echo esc_url(home_url('/terms-of-service/')); ?>" class="footer-link">Terms of Service</a>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
