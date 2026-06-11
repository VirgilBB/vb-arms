<?php
/**
 * VB Arms - Cart Page
 * Refined: thin borders, centered X, reduced height, above-the-fold, transparent checkout button.
 * Fixes: Shipping description clipping, image spacing, and input centering.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'VB_ARMS_CART_TEMPLATE_LOADED', true );
show_admin_bar( false );

$upload_dir = wp_upload_dir();
$logo_paths = array(
    get_template_directory() . '/vbarms-black-logo_512.png',
    get_stylesheet_directory() . '/vbarms-black-logo_512.png',
    ABSPATH . 'wp-content/uploads/vbarms-black-logo_512.png'
);
$logo_url = '';
foreach ( $logo_paths as $path ) {
    if ( file_exists( $path ) ) {
        $logo_url = str_replace( ABSPATH, home_url( '/' ), $path );
        $logo_url = str_replace( '\\', '/', $logo_url );
        break;
    }
}
if ( ! $logo_url ) {
    $logo_url = $upload_dir['baseurl'] . '/vbarms-black-logo_512.png';
}

$shop_url = apply_filters( 'vb_arms_breadcrumb_shop_url', home_url( '/shop/' ) );
$shop_url = ( empty( $shop_url ) || trim( $shop_url ) === '' ) ? home_url( '/shop/' ) : $shop_url;
$cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
$cart_count = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title( 'Cart', true, 'right' ); ?> <?php bloginfo( 'name' ); ?></title>
    <?php wp_head(); ?>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-bg: #0a0a0a;
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.88);
            --accent-green: #00ff94;
            --border-subtle: rgba(255, 255, 255, 0.1);
            --border-pill: rgba(0, 255, 148, 0.5);
            --border-accent: rgba(0, 255, 148, 0.3);
            --accent-soft: rgba(0, 255, 148, 0.9);
            --glass: rgba(255, 255, 255, 0.03);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        * { -webkit-tap-highlight-color: transparent; tap-highlight-color: transparent; }
        body {
            background: var(--primary-bg) !important;
            color: var(--text-primary);
            font-family: 'JetBrains Mono', monospace;
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        /* Prevent any cart ancestor from clipping the right container (table + coupon) */
        body.woocommerce-cart .vb-cart-page,
        body.woocommerce-cart .vb-cart-inner,
        body.woocommerce-cart .vb-cart-layout,
        body.woocommerce-cart .vb-cart-layout .woocommerce,
        body.woocommerce-cart .vb-cart-layout .woocommerce-cart-form {
            overflow: visible !important;
        }

        /* Nav: match home page */
        .nav-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: clamp(65px, 10vw, 75px);
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--border-subtle);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 clamp(1rem, 5vw, 4rem);
            transition: transform 0.4s ease;
        }
        .nav-logo {
            display: flex;
            align-items: center;
            gap: clamp(0.5rem, 2vw, 1rem);
            flex-shrink: 1;
            min-width: 0;
        }
        .nav-logo a {
            display: flex;
            align-items: center;
            gap: clamp(0.5rem, 2vw, 1rem);
            text-decoration: none;
        }
        .nav-logo img {
            height: clamp(35px, 8vw, 50px);
            width: auto;
        }
        .nav-logo-text {
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            font-size: clamp(0.9rem, 4vw, 1.3rem);
            white-space: nowrap;
        }
        .nav-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            flex-shrink: 0;
        }
        .nav-contact-btn {
            flex-shrink: 0;
            white-space: nowrap;
            background: rgba(0, 255, 148, 0.1);
            border: 1px solid var(--border-accent) !important;
            padding: 0.35rem 0;
            border-radius: 50px;
            color: var(--accent-green);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.75rem;
            transition: 0.3s;
            text-transform: none;
            width: 110px;
            text-align: center;
        }
        .nav-contact-btn:hover {
            background: var(--accent-green);
            color: #000;
            transform: translateY(-2px);
        }
        .nav-cart-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
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
        .nav-cart-pill:hover {
            background: rgba(0, 255, 148, 0.25);
            box-shadow: 0 0 20px rgba(0, 255, 148, 0.2);
            transform: translateY(-2px);
        }
        .nav-cart-pill.current {
            background: rgba(0, 255, 148, 0.2);
        }
        .nav-cart-pill .nav-cart-emoji { font-size: 1.1rem; line-height: 1; }
        .nav-cart-pill .nav-cart-count { font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; }

        .vb-cart-page { flex: 1; padding-top: 80px; padding-bottom: 2rem; min-width: 0; overflow: visible !important; }
        .vb-cart-inner {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 0.75rem;
            width: 100%;
            box-sizing: border-box;
            overflow: visible !important;
        }
        .vb-cart-layout {
            overflow: visible !important;
        }

        /* Cart title: match Checkout page (green, uppercase, border) */
        .vb-cart-title { margin-bottom: 1rem; display: flex; align-items: center; gap: 12px; }
        .vb-cart-title h1 {
            font-family: 'JetBrains Mono', monospace;
            font-size: 2.25rem;
            font-weight: 700;
            margin: 0;
            color: var(--accent-green) !important;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            border-bottom: 1px solid var(--border-pill);
            padding-bottom: 10px;
        }
        .vb-cart-title p { color: var(--text-secondary); font-size: 0.85rem; margin-top: 2px; }

        /* Coupon block: Updated to match Checkout pill style */
        .vb-cart-coupon-block {
            background: var(--glass) !important;
            border: 1px solid rgba(0, 255, 148, 0.45) !important;
            border-radius: 50px !important;
            padding: 0.6rem 2rem !important;
            margin-bottom: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 0.5rem 1rem;
            color: var(--text-primary);
            min-height: 50px;
        }
        /* Pill: exact copy of checkout .woocommerce-info a (functions.php) – same display, size, border, hover */
        .vb-cart-coupon-block .coupon-pill {
            display: inline-block !important;
            margin: 0;
            padding: 0.4rem 0.9rem !important;
            background: transparent !important;
            border: 1px solid rgba(0, 255, 148, 0.5) !important;
            border-radius: 50px !important;
            color: #00ff94 !important;
            font-size: 0.8rem !important;
            font-weight: 600 !important;
            font-family: 'JetBrains Mono', monospace !important;
            text-decoration: none !important;
            cursor: pointer;
            transition: 0.3s;
            -webkit-appearance: none;
            appearance: none;
            line-height: 1.4;
            vertical-align: middle;
        }
        .vb-cart-coupon-block .coupon-pill:hover {
            background: rgba(0, 255, 148, 0.12) !important;
            border-color: #00ff94 !important;
            text-decoration: none !important;
        }
        .vb-cart-coupon-block .coupon-form-wrap {
            display: none;
            flex: 1 1 100%;
            align-items: center;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }
        .vb-cart-coupon-block .coupon-form-wrap.open { display: flex !important; flex-wrap: wrap; }
        .vb-cart-coupon-block .coupon-form-wrap input.input-text {
            flex: 1 1 180px;
            min-width: 140px;
            background: transparent !important;
            border: 1px solid var(--border-pill) !important;
            color: #fff !important;
            border-radius: 50px;
            padding: 0.5rem 1.2rem;
            font-size: 0.9rem;
            font-family: 'JetBrains Mono', monospace;
        }
        .vb-cart-coupon-block .coupon-form-wrap .button {
            background: transparent !important;
            border: 1px solid var(--border-pill) !important;
            color: var(--accent-green) !important;
            border-radius: 50px !important;
            padding: 0.5rem 1.2rem !important;
            font-weight: 600 !important;
            font-size: 0.85rem !important;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            cursor: pointer;
            transition: 0.3s;
        }
        .vb-cart-coupon-block .coupon-form-wrap .button:hover { background: rgba(0, 255, 148, 0.1) !important; }

        /* --- RESPONSIVE GRID STRATEGY --- */
        .vb-cart-layout .woocommerce {
            display: grid !important;
            grid-template-columns: minmax(280px, 380px) minmax(0, 1fr) !important;
            grid-auto-rows: minmax(min-content, auto) !important;
            column-gap: 1rem;
            row-gap: 1.5rem;
            align-items: flex-start !important;
            width: 100%;
            overflow: visible !important;
        }

        .vb-cart-layout .cart-collaterals {
            order: -1;
            display: flex;
            width: 100%;
            min-width: 0;
            max-width: 100%;
            margin-top: 0;
        }

        /* Right container: table + coupon; never clip. Content dictates height. */
        .vb-cart-layout .woocommerce-cart-form {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0;
            min-height: min-content !important;
            overflow: visible !important;
            border: 1px solid var(--border-pill) !important;
            border-radius: 12px;
            padding: 0 !important;
            box-sizing: border-box;
            display: flex !important;
            flex-direction: column !important;
        }
        .vb-cart-layout .woocommerce-cart-form table.cart {
            flex: 0 0 auto !important;
        }
        .vb-cart-layout .woocommerce-cart-form .actions {
            flex: 0 0 auto !important;
        }
        /* On narrow viewports: table scrolls horizontally only; coupon stays visible */
        @media (max-width: 900px) {
            .vb-cart-layout .woocommerce-cart-form {
                overflow-x: auto !important;
                overflow-y: visible !important;
                -webkit-overflow-scrolling: touch;
            }
            .vb-cart-layout .woocommerce-cart-form table.cart {
                overflow-x: auto !important;
            }
            .vb-cart-page table.cart {
                min-width: 520px;
            }
        }

        /* 1. Product name: wrap text, left-aligned */
        .vb-cart-page td.product-name {
            vertical-align: middle !important;
            overflow: visible !important;
            padding-left: 1rem !important;
            text-align: left !important;
        }
        .vb-cart-page td.product-name .product-name,
        .vb-cart-page td.product-name dl,
        .vb-cart-page td.product-name .product-name a {
            text-align: left !important;
        }

        .vb-cart-page td.product-name a,
        .vb-cart-page table.cart td.product-name .product-name a {
            display: block !important;
            white-space: normal !important;
            overflow: visible !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            font-size: 0.9rem !important;
            line-height: 1.35 !important;
            text-transform: uppercase;
            font-weight: 600;
            color: var(--text-primary) !important;
            text-decoration: none !important;
            text-align: left !important;
        }
        .vb-cart-page td.product-name a:hover {
            color: var(--accent-green) !important;
        }

        /* Fix cart table header alignment and borders */
        /* 2. HEADER DIVIDER & GAP FIX */
        .vb-cart-page table.cart {
            border-collapse: collapse !important;
            margin-top: -1px !important;
            width: 100% !important;
            table-layout: fixed !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            margin-bottom: 0 !important;
            background: var(--glass) !important;
            border: none !important;
            border-radius: 0 !important;
            border-spacing: 0 !important;
        }
        /* Upper pill: header row with Product, Price, Quantity, Subtotal */
        .vb-cart-page table.cart thead tr {
            background: rgba(0, 255, 148, 0.08) !important;
            border: 1px solid var(--border-pill) !important;
            border-bottom: 1px solid var(--border-pill) !important;
            border-radius: 12px 12px 0 0 !important;
        }
        .vb-cart-page table.cart thead th {
            background: transparent !important;
            border: none !important;
            color: var(--accent-green) !important;
            height: 50px;
            vertical-align: middle !important;
            padding: 1rem 0.5rem !important;
            text-align: center !important;
            text-transform: uppercase !important;
            letter-spacing: 0.08em !important;
            font-size: 0.8rem !important;
            font-weight: 600 !important;
            position: relative !important;
        }
        .vb-cart-page table.cart thead th:not(:first-child) {
            font-size: 0.75rem !important;
        }
        /* Pull table up to meet container border to close any visual gaps */
        .vb-cart-layout .woocommerce-cart-form table.cart {
            margin-top: -1px !important;
            border-top: none !important;
        }
        /* Column width fixes for proper alignment */
        .vb-cart-page table.cart thead th:nth-child(1),
        .vb-cart-page table.cart tbody td:nth-child(1) { padding-left: 0.75rem !important; }
        .vb-cart-page table.cart thead th:nth-child(6),
        .vb-cart-page table.cart tbody td:nth-child(6) { padding-right: 0.75rem !important; }
        .vb-cart-page table.cart thead th:nth-child(1) { width: 50px; min-width: 50px; }
        .vb-cart-page table.cart thead th:nth-child(2) { width: 90px; min-width: 90px; }
        .vb-cart-page table.cart thead th:nth-child(3) { width: 36%; }
        .vb-cart-page table.cart thead th:nth-child(4) { width: 14%; }
        .vb-cart-page table.cart thead th:nth-child(5) { width: 12%; }
        .vb-cart-page table.cart thead th:nth-child(6) { width: 23%; min-width: 80px; }
        /* Fix the × header alignment */
        .vb-cart-page table.cart thead th:first-child {
            font-size: 0 !important;
            text-indent: 0 !important;
            width: 50px !important;
            min-width: 50px !important;
        }
        .vb-cart-page table.cart thead th:first-child::before {
            content: "×" !important;
            font-size: 1.25rem !important;
            font-weight: 700 !important;
            color: var(--accent-green) !important;
            display: block !important;
            text-align: center !important;
        }
        .vb-cart-page table.cart td {
            padding: 1rem 0.5rem !important;
            vertical-align: middle !important;
            text-align: center !important;
            border-bottom: 1px solid var(--border-subtle) !important;
            background: transparent !important;
            color: #fff !important;
            overflow: hidden !important;
            word-wrap: break-word;
        }
        /* No bottom divider on thumbnail cell (so image area is clean and centered) */
        .vb-cart-page table.cart tbody td.product-thumbnail {
            border-bottom: none !important;
        }
        .vb-cart-page table.cart tbody tr:last-child td.product-thumbnail {
            border-bottom: none !important;
        }
        /* Divider line below the last product row (above coupon) */
        .vb-cart-page table.cart tbody tr:last-child td {
            border-bottom: 1px solid var(--border-pill) !important;
        }
        /* Gap above product images so they don't overlap divider lines */
        .vb-cart-page table.cart tbody td {
            padding-top: 1.5rem !important;
        }
        /* Remove the massive 3.25rem gap from the first row */
        .vb-cart-page table.cart tbody tr:first-child td {
            padding-top: 1.5rem !important;
        }
        /* Thumbnail cell: same top padding as row so image aligns with product name/price */
        .vb-cart-page table.cart tbody td.product-thumbnail {
            padding-top: 1.5rem !important;
        }

        /* 3. CLEANUP: REMOVE VERTICAL BORDERS & ALIGNMENT */
        .vb-cart-page .product-remove,
        .vb-cart-page .product-thumbnail,
        .vb-cart-page table.cart td,
        .vb-cart-page table.cart th {
            border-right: none !important;
        }

        .vb-cart-page .product-remove {
            width: 50px !important;
            min-width: 50px !important;
            text-align: center !important;
        }

        .vb-cart-page .product-remove a.remove {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 32px !important;
            height: 32px !important;
            border-radius: 50% !important;
            border: 1px solid var(--border-pill) !important;
            background: transparent !important;
            margin: 0 auto !important;
            font-size: 0 !important;
            color: transparent !important;
            text-decoration: none !important;
            position: relative !important;
            overflow: visible !important;
            transition: all 0.2s ease;
        }

        .vb-cart-page .product-remove a.remove::before {
            content: "\00d7" !important;
            font-family: 'JetBrains Mono', monospace !important;
            font-size: 22px !important;
            line-height: 1 !important;
            color: var(--accent-green) !important;
            font-weight: 400 !important;
            display: block !important;
            visibility: visible !important;
            text-transform: none !important;
            position: absolute !important;
            left: 50% !important;
            top: 50% !important;
            transform: translate(-50%, calc(-50% - 4px)) !important;
        }

        .vb-cart-page .product-remove a.remove:hover {
            border-color: #ffffff !important;
            background: rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 0 10px rgba(0, 255, 148, 0.2);
        }

        .vb-cart-page .product-remove a.remove:hover::before {
            color: #ffffff !important;
        }

        /* 2. FIX: IMAGE ZOOM + NO BORDERS + CENTERED */
        .vb-cart-page td.product-thumbnail {
            padding: 1.25rem 10px 10px 10px !important;
            vertical-align: middle !important;
            width: 90px !important;
        }

        .vb-cart-page .product-thumbnail {
            width: 75px !important;
            height: 55px !important;
            margin: 0.6rem auto 0 auto !important;
            overflow: hidden;
            border-radius: 6px;
            border: none !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: #fff;
        }

        .vb-cart-page .product-thumbnail a {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 100% !important;
            height: 100% !important;
        }

        .vb-cart-page .product-thumbnail img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            object-position: center center !important;
            display: block !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
        }

        /* 4. STOCK MESSAGE BRIGHTNESS (For Single Product consistency) */
        .vb-cart-page .stock {
            color: var(--accent-green) !important;
            font-weight: 700 !important;
            opacity: 1 !important;
        }
        .vb-cart-page .product-price .amount,
        .vb-cart-page .product-subtotal .amount {
            color: var(--accent-green) !important;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 0.95rem;
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .vb-cart-page .quantity input.qty {
            background: transparent !important;
            border: 1px solid var(--border-pill) !important;
            color: #fff !important;
            border-radius: 6px;
            padding: 4px;
            width: 45px;
            text-align: center;
            font-size: 0.9rem;
        }

        .vb-cart-page table.cart tfoot,
        .vb-cart-page table.cart .coupon + .button,
        .vb-cart-page .cart-collaterals::before,
        .vb-cart-page .cart-collaterals::after {
            display: none !important;
        }

        /* Hide default coupon row (moved to below title, same as checkout) */
        .vb-cart-page .actions {
            display: none !important;
        }

        .vb-cart-page ::-webkit-scrollbar {
            display: none;
        }
        .vb-cart-page .coupon input.input-text {
            background: transparent !important;
            border: 1px solid var(--border-pill) !important;
            color: #fff !important;
            border-radius: 50px;
            padding: 0.5rem 1.2rem;
            font-size: 0.85rem;
            font-family: 'JetBrains Mono', monospace;
        }
        .vb-cart-page .button[name="update_cart"],
        .vb-cart-page .coupon .button {
            background: transparent !important;
            border: 1px solid var(--border-pill) !important;
            color: var(--accent-green) !important;
            border-radius: 50px !important;
            padding: 0.5rem 1.2rem !important;
            font-family: 'JetBrains Mono', monospace !important;
            font-weight: 600 !important;
            font-size: 0.8rem !important;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: 0.3s;
        }
        .vb-cart-page .coupon .button:hover,
        .vb-cart-page .button[name="update_cart"]:hover {
            background: rgba(0, 255, 148, 0.1) !important;
            border-color: var(--accent-green) !important;
        }

        .vb-cart-page .cart_totals {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            overflow: visible !important;
            background: var(--glass) !important;
            border: 1px solid var(--border-pill) !important;
            border-radius: 12px;
            padding: 2rem 1.5rem !important;
            text-align: left;
            box-sizing: border-box;
        }

        .vb-cart-page .cart_totals table {
            width: 100% !important;
            border-spacing: 0;
            table-layout: fixed !important;
        }
        .vb-cart-page .cart_totals table tr:not(.cart-shipping) td {
            overflow: hidden !important;
        }
        .vb-cart-page .cart_totals table td {
            word-wrap: break-word;
            padding-right: 1.5rem !important;
            text-align: center !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
        }
        .vb-cart-page .cart_totals table td .amount {
            float: none !important;
            display: block !important;
            max-width: 100%;
        }
        .vb-cart-page .cart_totals .wc-proceed-to-checkout {
            text-align: left;
            margin-top: 1.25rem !important;
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }
        .vb-cart-page .cart_totals table th {
            text-align: left !important;
            padding: 15px 10px 15px 0 !important;
            vertical-align: top;
            width: 100px;
            white-space: nowrap;
        }
        .vb-cart-page .cart_totals tr.cart-shipping th::after {
            content: " ";
        }
        .vb-cart-page .cart_totals table td {
            padding: 15px 1.5rem 15px 0 !important;
            vertical-align: top;
        }

        /* Shipping row: hidden on cart (only shown on checkout); no display:block so hide wins */
        .vb-cart-page .cart_totals tr.cart-shipping,
        body.woocommerce-cart .cart_totals tr.cart-shipping {
            display: none !important;
        }
        .vb-cart-page .cart_totals tr.cart-shipping th,
        .vb-cart-page .cart_totals tr.cart-shipping td {
            display: none !important;
        }
        .vb-cart-page .cart_totals table td small,
        .vb-cart-page .cart_totals table td .woocommerce-shipping-destination,
        .vb-cart-page .cart_totals table td .shipping-calculator-button {
            display: block;
            text-align: left !important;
            margin: 0.2em 0 0 0 !important;
            padding: 0 !important;
        }
        .vb-cart-page .cart_totals tr.cart-shipping td .woocommerce-shipping-destination,
        .vb-cart-page .cart_totals tr.cart-shipping td .shipping-calculator-form {
            overflow: visible !important;
            padding-left: 0 !important;
            min-width: 0 !important;
        }
        /* "Optional..." text: shown when form collapsed; hidden when form open (via .shipping-form-open) */
        .vb-cart-page .cart_totals tr.cart-shipping td small {
            display: block !important;
            margin: 0.35em 0 0.5em 0 !important;
            color: var(--text-secondary);
            font-size: 0.85rem;
            line-height: 1.4;
        }
        .vb-cart-page .cart_totals tr.cart-shipping td.shipping-form-open small {
            display: none !important;
        }

        .vb-cart-page .cart_totals tr.cart-shipping td #shipping_method,
        .vb-cart-page .cart_totals tr.cart-shipping td ul.shipping-methods {
            list-style: none !important;
            padding-left: 0 !important;
            margin: 0 !important;
        }
        .vb-cart-page .cart_totals tr.cart-shipping td #shipping_method li,
        .vb-cart-page .cart_totals tr.cart-shipping td ul.shipping-methods li {
            list-style: none !important;
            padding-left: 0 !important;
            border: 1px solid var(--accent-green) !important;
            border-radius: 8px !important;
            padding: 1rem !important;
            margin-bottom: 0.5rem !important;
            text-align: center !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .vb-cart-page .cart_totals tr.cart-shipping td ul li input[type="radio"] {
            display: none !important;
        }
        .vb-cart-page .cart_totals .shipping-calculator-button {
            font-size: 0.9rem !important;
            color: var(--accent-green) !important;
            font-weight: 600;
        }
        .vb-cart-page .cart_totals .shipping-calculator-button::after {
            content: " \25BC";
            color: var(--accent-green);
            font-size: 0.75em;
            margin-left: 0.2em;
            vertical-align: 0.05em;
        }

        /* Shipping form: centered and symmetrical; tight dropdown boxes */
        .vb-cart-page .shipping-calculator-form .form-row {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            width: 100% !important;
            margin-bottom: 0.75rem !important;
        }
        .vb-cart-page .shipping-calculator-form .form-row label {
            text-align: center !important;
            margin-bottom: 4px;
        }
        /* Country: tight box (not full width) */
        .vb-cart-page .shipping-calculator-form .form-row:nth-child(1) .select2-container {
            width: 20ch !important;
            min-width: 20ch !important;
            max-width: 20ch !important;
            margin: 0 auto !important;
        }
        /* State: tight box */
        .vb-cart-page .shipping-calculator-form .form-row:nth-child(2) .select2-container {
            width: 12ch !important;
            min-width: 12ch !important;
            max-width: 12ch !important;
            margin: 0 auto !important;
        }
        /* Town/City and ZIP width: allow box to show (overridden by main input rule below) */
        /* Town/City and ZIP: green box, centered, same width as dropdowns for symmetry */
        .vb-cart-page .cart_totals .shipping-calculator-form input[type="text"],
        .vb-cart-page .cart_totals .shipping-calculator-form input.input-text,
        .vb-cart-page .cart_totals tr.cart-shipping td .shipping-calculator-form input[type="text"],
        .vb-cart-page .cart_totals tr.cart-shipping td .shipping-calculator-form input.input-text,
        body.woocommerce-cart .cart_totals .shipping-calculator-form input[type="text"],
        body.woocommerce-cart .cart_totals .shipping-calculator-form input.input-text {
            background: transparent !important;
            background-color: transparent !important;
            border: 1px solid var(--border-pill) !important;
            border-color: rgba(0, 255, 148, 0.5) !important;
            color: #fff !important;
            border-radius: 8px !important;
            padding: 6px 10px !important;
            text-align: center !important;
            font-family: 'JetBrains Mono', monospace !important;
            font-size: 0.9rem !important;
            margin: 0 auto 8px auto !important;
            display: block !important;
            width: 20ch !important;
            max-width: 20ch !important;
            box-sizing: border-box !important;
            -webkit-appearance: none !important;
            appearance: none !important;
        }

        body.woocommerce-cart .select2-container--default .select2-selection--single,
        body.woocommerce-cart .select2-container .select2-selection--single {
            background: transparent !important;
            border: 1px solid var(--border-pill) !important;
            border-radius: 8px !important;
            color: #fff !important;
            padding: 4px 6px !important;
            min-height: auto !important;
        }
        body.woocommerce-cart .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #fff !important;
        }
        body.woocommerce-cart .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: var(--accent-green) transparent transparent transparent !important;
        }
        body.woocommerce-cart .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
            border-color: transparent transparent var(--accent-green) transparent !important;
        }
        body.woocommerce-cart .select2-container--default .select2-dropdown {
            background: #0a0a0a !important;
            border: 1px solid var(--border-pill) !important;
            border-radius: 12px !important;
        }
        body.woocommerce-cart .select2-results__option {
            background: transparent !important;
            color: #fff !important;
        }
        body.woocommerce-cart .select2-results__option--highlighted[aria-selected],
        body.woocommerce-cart .select2-results__option[aria-selected=true] {
            background: rgba(0, 255, 148, 0.15) !important;
            color: var(--accent-green) !important;
        }

        .vb-cart-page .cart_totals *,
        .vb-cart-page .cart_totals table,
        .vb-cart-page .cart_totals tr,
        .vb-cart-page .cart_totals td,
        .vb-cart-page .cart_totals th,
        .vb-cart-page .shipping-calculator-button,
        .vb-cart-page .shipping-calculator-form {
            background: transparent !important;
            background-color: transparent !important;
            color: #fff !important;
            box-shadow: none !important;
        }
        /* Keep shipping text inputs as visible boxes – do not inherit border:none from any reset */
        .vb-cart-page .cart_totals .shipping-calculator-form input[type="text"],
        .vb-cart-page .cart_totals .shipping-calculator-form input.input-text {
            border: 1px solid rgba(0, 255, 148, 0.5) !important;
            border-radius: 8px !important;
            padding: 6px 10px !important;
            text-align: center !important;
            display: block !important;
            background: transparent !important;
            color: #fff !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }
        .vb-cart-page .cart_totals h2 {
            color: var(--accent-green) !important;
            border-bottom: 1px solid var(--accent-green) !important;
            padding-bottom: 8px;
            margin-bottom: 16px;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        /* One full-width divider per row (on tr so lines never misalign) */
        .vb-cart-page .cart_totals table tr {
            border-bottom: 1px solid var(--accent-green) !important;
        }
        .vb-cart-page .cart_totals table tr th,
        .vb-cart-page .cart_totals table tr td {
            border-bottom: none !important;
            border-top: none !important;
            font-size: 0.9rem;
            padding: 14px 1.5rem 14px 0 !important;
            vertical-align: middle !important;
        }
        .vb-cart-page .cart_totals table th { padding-left: 0 !important; }

        /* Ensure shipping row stays hidden on cart (highest specificity) */
        body.woocommerce-cart .vb-cart-page .cart_totals tr.cart-shipping {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
            border: none !important;
            line-height: 0 !important;
        }
        body.woocommerce-cart .vb-cart-page .cart_totals tr.cart-shipping th,
        body.woocommerce-cart .vb-cart-page .cart_totals tr.cart-shipping td {
            display: none !important;
        }

        .vb-cart-page .cart_totals tr.vb-arms-fee-card-row th,
        .vb-cart-page .cart_totals tr.vb-arms-fee-card-row td {
            padding: 14px 1.5rem 14px 0 !important;
        }

        .vb-cart-page .cart_totals tr.order-total {
            border-bottom: none !important;
        }
        .vb-cart-page .cart_totals .order-total th,
        .vb-cart-page .cart_totals .order-total td {
            border-top: 2px solid var(--accent-green) !important;
            border-bottom: none !important;
            padding-top: 14px !important;
            padding-bottom: 14px !important;
            color: var(--accent-green) !important;
            font-size: 1.25rem !important;
            font-weight: 800;
        }
        .vb-cart-page .checkout-button {
            display: flex !important;
            align-items: center;
            justify-content: center;
            width: 100% !important;
            background: transparent !important;
            border: 1px solid var(--accent-green) !important;
            color: var(--accent-green) !important;
            border-radius: 50px !important;
            padding: 1rem !important;
            font-weight: 700 !important;
            font-family: 'JetBrains Mono', monospace !important;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-size: 0.85rem !important;
            transition: 0.3s ease;
            white-space: nowrap !important;
            gap: 10px;
        }
        .vb-cart-page .checkout-button:hover {
            background: rgba(0, 255, 148, 0.15) !important;
            box-shadow: 0 5px 15px rgba(0, 255, 148, 0.2);
        }

        @media (max-width: 1250px) {
            .vb-cart-layout .woocommerce {
                display: flex !important;
                flex-direction: column !important;
                row-gap: 0.75rem !important;
            }

            .vb-cart-layout .woocommerce-cart-form {
                order: 1 !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            .vb-cart-layout .cart-collaterals {
                order: 2 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
        }

        @media (max-width: 768px) {
            .nav-logo-text { display: none; }
        }

        .vb-cart-page .cart-empty {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }
        .vb-cart-page .return-to-shop a {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: transparent !important;
            border: 1px solid var(--accent-green);
            color: var(--accent-green);
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        /* Notices overlay fix */
        .vb-cart-page .woocommerce-notices-wrapper {
            position: fixed !important;
            top: 90px;
            left: 0;
            right: 0;
            z-index: 1000;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            pointer-events: none;
        }
        .vb-cart-page .woocommerce-message,
        .vb-cart-page .woocommerce-error,
        .vb-cart-page .woocommerce-info {
            pointer-events: auto;
            background: rgba(10, 10, 10, 0.8) !important;
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-pill) !important;
            border-radius: 50px !important;
            padding: 0.5rem 1rem !important;
            margin-bottom: 0.5rem !important;
            color: var(--accent-green) !important;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.35) !important;
        }
        /* Remove checkmark/icon from cart notices (redundant, was overlapping) */
        .vb-cart-page .woocommerce-message::before,
        .vb-cart-page .woocommerce-message::after,
        .vb-cart-page .woocommerce-error::before,
        .vb-cart-page .woocommerce-error::after {
            display: none !important;
            content: none !important;
            visibility: hidden !important;
        }

        /* Footer — match homepage size (payment strip + tagline + generous padding) */
        .cart-footer-wrap .payment-strip {
            display: flex; align-items: center; justify-content: center; flex-wrap: wrap;
            gap: 2rem; padding: 0 2rem 0.5rem;
        }
        .cart-footer-wrap .pay-item {
            display: flex; align-items: center; gap: 0.3rem; color: #fff;
            font-size: clamp(0.85rem, 2vw, 0.95rem); font-family: 'JetBrains Mono', monospace;
        }
        .cart-footer-wrap .pay-item img { height: 20px; width: auto; }
        /* Footer font sizes = homepage: tagline clamp(1.1rem,3vw,1.4rem), copyright 0.8rem, links 0.75rem */
        .cart-footer-wrap .signature-tagline {
            text-align: center;
            font-size: clamp(1.1rem, 3vw, 1.4rem);
            font-style: italic;
            color: var(--accent-green);
            margin: 2rem 0;
            font-weight: 300;
        }
        .cart-footer-wrap .vb-footer {
            width: 100%;
            margin-top: auto;
            padding: 15px 2rem 40px;
            border-top: 1px solid var(--border-subtle);
            background: var(--primary-bg);
            text-align: center;
        }
        .cart-footer-wrap .vb-footer p {
            font-size: 0.8rem;
            margin-bottom: 8px;
            color: var(--text-secondary);
        }
        .cart-footer-wrap .footer-link {
            color: rgba(0, 255, 148, 0.75);
            text-decoration: none;
            font-size: 0.75rem;
            transition: 0.2s;
        }
        .cart-footer-wrap .footer-link:hover {
            color: var(--accent-green);
        }
    </style>
</head>
<body <?php body_class( 'woocommerce-cart vb-arms-cart' ); ?>>

<header class="nav-header">
    <div class="nav-logo">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
            <img src="<?php echo esc_url( $logo_url ); ?>" alt="VB Arms">
            <span class="nav-logo-text">VB ARMS</span>
        </a>
    </div>
    <div class="nav-actions">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="nav-contact-btn">Home</a>
        <a href="<?php echo esc_url( $shop_url ); ?>" class="nav-contact-btn">Shop</a>
        <a href="<?php echo esc_url( home_url( '/white-glove/' ) ); ?>" class="nav-contact-btn">White Glove</a>
        <a href="<?php echo esc_url( $cart_url ); ?>" class="nav-cart-pill current" aria-label="Cart"><span class="nav-cart-emoji" aria-hidden="true">🛒</span><?php if ( $cart_count > 0 ) { ?><span class="nav-cart-count"><?php echo (int) $cart_count; ?></span><?php } ?></a>
    </div>
</header>

<div class="vb-cart-page">
    <div class="vb-cart-inner">
        <div class="vb-cart-title">
            <div style="background: rgba(0, 255, 148, 0.1); padding: 8px; border-radius: 8px; border: 1px solid var(--border-pill); color: var(--accent-green);">✓</div>
            <div>
                <h1>Cart</h1>
                <?php $count = WC()->cart->get_cart_contents_count(); ?>
                <p><?php echo (int) $count; ?> selected items.</p>
            </div>
        </div>

        <div class="vb-cart-coupon-block" id="vb-cart-coupon-block">
            <span style="color: var(--text-secondary);">Have a coupon?</span>
            <button type="button" class="coupon-pill" id="vb-cart-coupon-trigger" aria-expanded="false" aria-controls="vb-cart-coupon-form">Click here to enter your code</button>
            <div class="coupon-form-wrap" id="vb-cart-coupon-form" role="region" aria-label="Coupon form" hidden>
                <form action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post" class="vb-cart-apply-coupon">
                    <?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
                    <input type="text" name="coupon_code" class="input-text" id="vb_cart_coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" />
                    <button type="submit" name="apply_coupon" class="button" value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>"><?php esc_html_e( 'Apply coupon', 'woocommerce' ); ?></button>
                </form>
            </div>
        </div>

        <div class="vb-cart-layout">
            <?php echo do_shortcode( '[woocommerce_cart]' ); ?>
        </div>
    </div>
</div>

<div class="cart-footer-wrap">
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
        <p>© <?php echo date( 'Y' ); ?> VB ARMS • Bespoke Firearms Procurement</p>
        <div class="footer-links" style="display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap;">
            <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" class="footer-link">Privacy Policy</a>
            <a href="<?php echo esc_url( home_url( '/refund-returns/' ) ); ?>" class="footer-link">Refund & Returns</a>
            <a href="<?php echo esc_url( home_url( '/terms-of-service/' ) ); ?>" class="footer-link">Terms of Service</a>
        </div>
    </footer>
</div>

<?php wp_footer(); ?>
<script>
(function() {
        /* Auto-update cart when quantity changes: submit form so WooCommerce applies new qty (must send update_cart) */
    var cartForm = document.querySelector('.vb-cart-page form.woocommerce-cart-form') || document.querySelector('.vb-cart-page form.cart');
    if (cartForm) {
        var qtyInputs = cartForm.querySelectorAll('input.qty');
        var submitTimeout = null;
        function scheduleSubmit() {
            if (submitTimeout) clearTimeout(submitTimeout);
            submitTimeout = setTimeout(function() {
                submitTimeout = null;
                /* WooCommerce only updates cart when update_cart is in POST; disabled button is not submitted, so add hidden */
                var hid = document.createElement('input');
                hid.type = 'hidden';
                hid.name = 'update_cart';
                hid.value = 'Update cart';
                cartForm.appendChild(hid);
                cartForm.submit();
            }, 400);
        }
        qtyInputs.forEach(function(input) {
            input.addEventListener('change', scheduleSubmit);
            input.addEventListener('blur', function() {
                var val = parseInt(input.value, 10);
                var min = parseInt(input.getAttribute('min'), 10) || 1;
                if (!isNaN(val) && val >= min) scheduleSubmit();
            });
        });
    }

    /* Coupon block: toggle form (same behavior as checkout) */
    var couponTrigger = document.getElementById('vb-cart-coupon-trigger');
    var couponForm = document.getElementById('vb-cart-coupon-form');
    if (couponTrigger && couponForm) {
        couponTrigger.addEventListener('click', function() {
            var open = couponForm.classList.toggle('open');
            couponForm.hidden = !open;
            couponTrigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }
})();
(function() {
    function markFeeRow() {
        var rows = document.querySelectorAll('.vb-cart-page .cart_totals table tr');
        rows.forEach(function(tr) {
            if (tr.textContent.indexOf('Card processing fee') !== -1) {
                tr.classList.add('vb-arms-fee-card-row');
            }
        });
    }
    markFeeRow();
    document.body.addEventListener('updated_cart_totals', markFeeRow);
})();
(function() {
    document.body.addEventListener('click', function(e) {
        if (!e.target || !e.target.closest) return;
        var btn = e.target.closest('.vb-cart-page .cart_totals .shipping-calculator-button');
        if (!btn) return;
        var td = btn.closest('tr.cart-shipping td');
        if (!td) return;
        setTimeout(function() { td.classList.toggle('shipping-form-open'); }, 50);
    });
})();
(function() {
    var wrapper = document.querySelector('.vb-cart-page .woocommerce-notices-wrapper');
    if (!wrapper || !wrapper.querySelector('.woocommerce-message, .woocommerce-error, .woocommerce-info')) return;
    setTimeout(function() {
        wrapper.style.opacity = '0';
        setTimeout(function() {
            wrapper.innerHTML = '';
            wrapper.style.display = 'none';
        }, 400);
    }, 3000);
})();
</script>
</body>
</html>