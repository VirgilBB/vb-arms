<?php
/**
 * VB Arms - Optimized Checkout Page
 * Refined vertical spacing, unified thin borders, transparent inputs, and sidebar anchoring.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'VB_ARMS_CHECKOUT_TEMPLATE_LOADED', true );
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
$is_order_received = is_wc_endpoint_url( 'order-received' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title( 'Checkout', true, 'right' ); ?> <?php bloginfo( 'name' ); ?></title>
    <?php wp_head(); ?>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-bg: #0a0a0a;
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --accent-green: #00ff94;
            --border-subtle: rgba(255, 255, 255, 0.1);
            --border-pill: rgba(0, 255, 148, 0.4);
            --glass: rgba(255, 255, 255, 0.03);
        }

        html, body {
            background: #0a0a0a !important;
            background-color: #0a0a0a !important;
            color: var(--text-primary);
            font-family: 'Space Grotesk', sans-serif;
            margin: 0; min-height: 100vh;
            display: flex; flex-direction: column;
        }

        /* Nav Header - Matched to Cart */
        .nav-header {
            position: fixed; top: 0; left: 0; right: 0; height: 60px;
            background: rgba(0,0,0,0.95); backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--border-subtle); z-index: 1000;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 3rem;
        }
        .nav-logo img { height: 35px; width: auto; }
        .nav-logo-text { font-weight: 700; color: #fff; letter-spacing: 0.1em; text-transform: uppercase; font-size: 1rem; }
        .nav-actions { display: flex; gap: 0.5rem; }
        .nav-contact-btn {
            background: transparent; border: 1px solid var(--border-pill);
            padding: 0.4rem 1rem; border-radius: 50px; color: var(--accent-green);
            text-decoration: none; font-weight: 600; font-size: 0.75rem; transition: 0.3s;
        }

        /* Page Layout - dark bg so theme cannot override */
        .vb-checkout-page { flex: 1; padding-top: 80px; padding-bottom: 2rem; background: #0a0a0a !important; }
        .vb-checkout-inner { max-width: 1200px; margin: 0 auto; padding: 0 2rem; width: 100%; box-sizing: border-box; }

        .vb-checkout-title { margin-bottom: 1rem; display: flex; align-items: center; gap: 12px; }
        .vb-checkout-title h1 {
            font-size: 2.25rem;
            font-weight: 700;
            margin: 0;
            color: var(--accent-green) !important;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            border-bottom: 1px solid var(--border-pill);
            padding-bottom: 10px;
        }
        .vb-checkout-title p { color: var(--text-secondary); font-size: 0.85rem; margin-top: 2px; }

        /* Single column: coupon, billing, shipping, order review stacked (reverted from two-column) */
        form.woocommerce-checkout {
            display: block !important;
        }

        /* BILLING & SHIPPING SECTIONS */
        .woocommerce-billing-fields, .woocommerce-shipping-fields, .woocommerce-additional-fields {
            background: var(--glass) !important;
            border: 1px solid var(--border-pill) !important;
            border-radius: 12px;
            padding: 1.25rem !important;
            margin-bottom: 1rem !important;
        }

        .woocommerce-checkout h3 {
            color: var(--accent-green) !important;
            font-size: 1rem; text-transform: uppercase; letter-spacing: 0.1em;
            border-bottom: 1px solid var(--border-pill);
            padding-bottom: 8px; margin-bottom: 15px;
        }

        /* FORM FIELDS: Transparent Green Outlined */
        .woocommerce-checkout label { font-size: 0.85rem; margin-bottom: 5px !important; color: var(--text-secondary) !important; }
        .woocommerce-checkout input[type="text"],
        .woocommerce-checkout input[type="email"],
        .woocommerce-checkout input[type="tel"],
        .woocommerce-checkout input[type="password"],
        .woocommerce-checkout select,
        .woocommerce-checkout textarea {
            background: transparent !important;
            border: 1px solid var(--border-pill) !important;
            color: #fff !important;
            border-radius: 50px !important;
            padding: 0.5rem 1.2rem !important;
            font-size: 0.9rem !important;
            font-family: 'Space Grotesk', sans-serif !important;
        }
        .woocommerce-checkout select { border-radius: 12px !important; }
        .woocommerce-checkout textarea { border-radius: 15px !important; min-height: 80px; }

        /* Dropdown options: dark theme, thin green accent (native option + Select2/selectWoo) */
        .woocommerce-checkout select option {
            background: #0a0a0a !important;
            color: #fff !important;
        }
        /* Select2 / selectWoo (WooCommerce enhanced dropdown) — Country/Region & State use this; match our style */
        .vb-checkout-page .select2-container--default .select2-selection--single,
        .vb-checkout-page .select2-container .select2-selection--single,
        body.woocommerce-checkout .select2-container--default .select2-selection--single,
        body.woocommerce-checkout .select2-container .select2-selection--single {
            background: transparent !important;
            border: 1px solid var(--border-pill) !important;
            border-radius: 12px !important;
            color: #fff !important;
            padding: 0.5rem 1.2rem !important;
            height: auto !important;
            min-height: 42px !important;
        }
        .vb-checkout-page .select2-container--default .select2-selection--single .select2-selection__rendered,
        body.woocommerce-checkout .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #fff !important;
            line-height: 1.5 !important;
            padding-left: 0 !important;
        }
        .vb-checkout-page .select2-container--default .select2-selection--single .select2-selection__arrow,
        body.woocommerce-checkout .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100% !important;
        }
        .vb-checkout-page .select2-container--default .select2-selection--single .select2-selection__arrow b,
        body.woocommerce-checkout .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: var(--accent-green) transparent transparent transparent !important;
        }
        .vb-checkout-page .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b,
        body.woocommerce-checkout .select2-container--open .select2-selection--single .select2-selection__arrow b {
            border-color: transparent transparent var(--accent-green) transparent !important;
        }
        /* Dropdown list (open state) — Select2 appends to body, so target by body class */
        .vb-checkout-page .select2-container--default .select2-dropdown,
        body.vb-arms-checkout .select2-container--default .select2-dropdown,
        body.woocommerce-checkout .select2-dropdown,
        body.vb-arms-checkout .select2-dropdown {
            background: #0a0a0a !important;
            border: 1px solid var(--border-pill) !important;
            border-radius: 12px !important;
        }
        body.vb-arms-checkout .select2-results__options,
        body.woocommerce-checkout .select2-results__options {
            background: #0a0a0a !important;
        }
        .vb-checkout-page .select2-container--default .select2-results__option,
        body.vb-arms-checkout .select2-results__option,
        body.woocommerce-checkout .select2-results__option {
            background: transparent !important;
            color: #fff !important;
            padding: 0.5rem 1rem !important;
        }
        .vb-checkout-page .select2-container--default .select2-results__option--highlighted[aria-selected],
        body.vb-arms-checkout .select2-results__option--highlighted[aria-selected],
        body.woocommerce-checkout .select2-results__option--highlighted[aria-selected] {
            background: rgba(0, 255, 148, 0.15) !important;
            color: var(--accent-green) !important;
        }
        .vb-checkout-page .select2-container--default .select2-results__option[aria-selected=true],
        body.vb-arms-checkout .select2-results__option[aria-selected=true],
        body.woocommerce-checkout .select2-results__option[aria-selected=true] {
            background: rgba(0, 255, 148, 0.1) !important;
            color: var(--accent-green) !important;
        }
        .vb-checkout-page .select2-results__message,
        body.vb-arms-checkout .select2-results__message { color: var(--text-secondary) !important; }

        /* ORDER REVIEW */
        #order_review_heading { display: none; }
        #order_review {
            background: var(--glass) !important;
            border: 1px solid var(--border-pill) !important;
            border-radius: 12px;
            padding: 1.25rem !important;
            margin-top: 1rem;
        }

        /* TABLES IN REVIEW — symmetric padding so dividers sit centered between rows */
        .shop_table { border: none !important; width: 100%; }
        .shop_table thead th {
            color: var(--accent-green); font-size: 0.75rem; text-transform: uppercase;
            border-bottom: 1px solid var(--border-pill) !important;
            padding-top: 1rem !important;
            padding-bottom: 1rem !important;
        }
        .shop_table td {
            padding-top: 1rem !important;
            padding-bottom: 1rem !important;
            font-size: 0.9rem;
            border-bottom: 1px solid var(--border-subtle) !important;
            color: #fff !important;
        }
        .product-total .amount { color: var(--accent-green) !important; font-family: 'JetBrains Mono'; font-weight: 700; }

        .order-total th, .order-total td {
            border-top: 1px solid var(--accent-green) !important;
            padding-top: 1rem !important;
            padding-bottom: 1rem !important;
            color: var(--accent-green) !important;
            font-size: 1.4rem !important; font-weight: 800;
        }

        /* PAYMENT METHODS - All payment options in one unified container */
        body.woocommerce-checkout #payment,
        .woocommerce-checkout #payment {
            background: transparent !important;
            margin-top: 0 !important;
            border: none !important;
            padding: 0 !important;
        }

        /* Force all payment children to have consistent width */
        body.woocommerce-checkout #payment > *,
        .woocommerce-checkout #payment > *,
        body.woocommerce-checkout #payment .payment_methods,
        .woocommerce-checkout #payment .payment_methods {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }


        body.woocommerce-checkout #payment ul.payment_methods,
        .woocommerce-checkout #payment ul.payment_methods {
            list-style: none !important;
            padding: 0 !important;
            margin: 0 !important;
            border: none !important;
            background: transparent !important;
        }

        /* Payment method list items - min height, content can wrap */
        /* Each li stacks label + description so payment_box stays inside the same box */
        body.woocommerce-checkout #payment .payment_methods li,
        .woocommerce-checkout #payment .payment_methods li {
            background: rgba(255,255,255,0.03) !important;
            border: 1px solid var(--border-pill) !important;
            border-radius: 10px !important;
            padding: 1rem 1.25rem !important;
            margin-bottom: 0.75rem !important;
            color: #fff !important;
            transition: border-color 0.3s ease !important;
            list-style: none !important;
            min-height: 100px !important;
            box-sizing: border-box !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            justify-content: flex-start !important;
            overflow: visible !important;
        }

        /* Payment description stays inside its own li (no absolute positioning) */
        body.woocommerce-checkout #payment .payment_methods li .payment_box,
        .woocommerce-checkout #payment .payment_methods li .payment_box {
            margin-top: 0.5rem !important;
            margin-bottom: 0 !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }

        body.woocommerce-checkout #payment .payment_methods li:hover,
        .woocommerce-checkout #payment .payment_methods li:hover {
            border-color: var(--accent-green) !important;
        }

        /* Payment method labels - content can wrap */
        body.woocommerce-checkout #payment .payment_methods li label,
        .woocommerce-checkout #payment .payment_methods li label {
            color: #fff !important;
            font-weight: 600 !important;
            font-size: 1rem !important;
            margin: 0 !important;
            padding: 0 !important;
            display: flex !important;
            align-items: center !important;
            flex-wrap: wrap !important;
            cursor: pointer !important;
            width: 100% !important;
            min-width: 0 !important;
            flex: 1 1 auto !important;
        }

        /* Radio buttons styling */
        body.woocommerce-checkout #payment .payment_methods li input[type="radio"],
        .woocommerce-checkout #payment .payment_methods li input[type="radio"] {
            appearance: none !important;
            -webkit-appearance: none !important;
            width: 18px !important;
            height: 18px !important;
            border: 1px solid var(--border-pill) !important;
            border-radius: 50% !important;
            background: transparent !important;
            margin-right: 0.75rem !important;
            position: relative !important;
            vertical-align: middle !important;
            flex-shrink: 0 !important;
        }

        body.woocommerce-checkout #payment .payment_methods li input[type="radio"]:checked,
        .woocommerce-checkout #payment .payment_methods li input[type="radio"]:checked {
            border-color: var(--accent-green) !important;
        }

        body.woocommerce-checkout #payment .payment_methods li input[type="radio"]:checked::after,
        .woocommerce-checkout #payment .payment_methods li input[type="radio"]:checked::after {
            content: '' !important;
            width: 8px !important;
            height: 8px !important;
            border-radius: 50% !important;
            background: var(--accent-green) !important;
            position: absolute !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
        }

        /* Payment descriptions + Crypto (USDT) box */
        body.woocommerce-checkout .payment_box,
        .woocommerce-checkout .payment_box {
            background: rgba(0,0,0,0.3) !important;
            color: #e8e8e8 !important;
            font-size: 1rem !important;
            border-radius: 8px !important;
            line-height: 1.5 !important;
            margin-top: 0.75rem !important;
            padding: 0.75rem 1rem !important;
            border: none !important;
        }
        #payment .payment_box select { display: none !important; }
        #payment .payment_box [placeholder*="Select"] { display: none !important; }
        #payment .payment_box,
        #payment .payment_box p,
        #payment .payment_box div { color: #e8e8e8 !important; font-size: 1rem !important; }
        #payment .payment_box a { color: rgba(0, 255, 148, 0.9) !important; font-size: 1rem !important; }
        #payment .payment_box .amount,
        #payment .payment_box [class*="amount"],
        #payment .payment_box input[readonly],
        #payment .payment_box input[type="text"] { color: #fff !important; font-size: 1.1rem !important; font-weight: 600 !important; }

        /* Crypto container - min height, content can wrap (matches payment rows) */
        .checkout-crypto-line-wrap {
            background: rgba(255,255,255,0.03) !important;
            border: 1px solid var(--border-pill) !important;
            border-radius: 10px !important;
            padding: 1rem 1.25rem !important;
            margin-bottom: 0.75rem !important;
            margin-top: 0 !important;
            color: #fff !important;
            min-height: 100px !important;
            box-sizing: border-box !important;
            display: flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
            transition: border-color 0.3s ease !important;
            cursor: pointer !important;
        }

        .checkout-crypto-line-wrap:hover {
            border-color: var(--accent-green) !important;
        }

        .checkout-crypto-line-label {
            display: none !important;
        }

        /* Link: single row, no wrap - radio, label text, strip, CTA */
        .checkout-crypto-line-link {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
            flex-wrap: wrap !important;
            width: 100% !important;
            min-width: 0 !important;
            background: transparent !important;
            border: none !important;
            color: #fff !important;
            text-decoration: none !important;
            padding: 0 !important;
            margin: 0 !important;
            gap: 0.25rem 0.75rem !important;
        }

        /* Fake radio - same as payment method radios */
        .checkout-crypto-line-link::before {
            content: '' !important;
            width: 18px !important;
            height: 18px !important;
            border: 1px solid var(--border-pill) !important;
            border-radius: 50% !important;
            background: transparent !important;
            margin-right: 0.75rem !important;
            flex-shrink: 0 !important;
        }

        /* "Other crypto options" label - right after radio (flex order so it appears second) */
        .checkout-crypto-line-link::after {
            content: "Other crypto options" !important;
            color: #fff !important;
            font-weight: 600 !important;
            font-size: 1rem !important;
            margin-right: 0.5rem !important;
            order: 1 !important;
        }

        /* Strip: can wrap; pushed right */
        .checkout-crypto-strip {
            order: 2 !important;
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            gap: 0.35rem !important;
            margin-left: auto !important;
            min-width: 0 !important;
            flex-shrink: 1 !important;
        }

        .checkout-crypto-strip .pay-item {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.2rem !important;
            color: #fff !important;
            font-size: 0.85rem !important;
            flex-shrink: 0 !important;
        }

        .checkout-crypto-strip .pay-item img {
            height: 16px !important;
            width: auto !important;
        }

        .checkout-crypto-line-cta {
            order: 3 !important;
            color: var(--accent-green) !important;
            font-size: 0.85rem !important;
            font-weight: 600 !important;
            margin-left: 0 !important;
            flex-shrink: 0 !important;
        }

        /* Privacy policy and place order - positioned after payment methods */
        .woocommerce-checkout #payment .woocommerce-privacy-policy-text,
        .vb-checkout-page #payment .woocommerce-privacy-policy-text {
            margin: 1.5rem 0 0.75rem 0 !important;
            padding: 0 !important;
        }

        .woocommerce-checkout #payment .form-row.place-order,
        .vb-checkout-page #payment .form-row.place-order {
            margin: 0 !important;
            padding: 0 !important;
        }

        /* PLACE ORDER BUTTON: same width as other containers */
        .vb-checkout-page #place_order,
        #place_order {
            display: block !important;
            width: 100% !important;
            margin: 0 !important;
            background: transparent !important;
            border: 1px solid var(--accent-green) !important;
            color: var(--accent-green) !important;
            border-radius: 50px !important;
            padding: 0.85rem 1rem !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.15em !important;
            font-size: 0.85rem !important;
            transition: 0.3s !important;
            cursor: pointer !important;
        }
        .vb-checkout-page #place_order:hover,
        #place_order:hover {
            background: rgba(0, 255, 148, 0.1) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 5px 15px rgba(0, 255, 148, 0.2) !important;
        }

        /* Keep form/shop_table transparent so dark page shows; do not force white */
        .vb-checkout-page .woocommerce-checkout,
        .vb-checkout-page .woocommerce-checkout *,
        .vb-checkout-page .shop_table,
        .vb-checkout-page .shop_table * {
            background-color: transparent !important;
        }

        /* Alternative payment options strip – uniform spacing */
        .checkout-payment-strip-wrap { margin-top: 0.75rem; padding: 0.75rem 0; }
        .checkout-payment-strip-wrap .payment-strip-label { text-align: center; color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0.75rem; }
        .checkout-payment-strip-wrap .payment-strip {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1.5rem;
            padding: 0;
        }
        .checkout-payment-strip-wrap .pay-item {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            color: #fff;
            font-size: 0.9rem;
            font-family: 'JetBrains Mono', monospace;
        }
        .checkout-payment-strip-wrap .pay-item img { height: 22px; width: auto; }

        /* Footer & Tagline — match homepage size */
        .signature-tagline { text-align: center; font-size: clamp(1.1rem, 3vw, 1.4rem); font-style: italic; color: var(--accent-green); margin: 2rem 0; font-weight: 300; }
        .vb-footer { padding: 15px 2rem 40px; border-top: 1px solid var(--border-subtle); text-align: center; }
        .vb-footer p { font-size: 0.8rem; margin-bottom: 8px; color: var(--text-secondary); }
        .footer-link { color: rgba(0, 255, 148, 0.75); text-decoration: none; margin: 0 10px; font-size: 0.75rem; transition: 0.2s; }
        .footer-link:hover { color: var(--accent-green); }

        /* Order received (thank you) page: hero-style block, green text, larger fonts */
        .vb-checkout-page .woocommerce-thankyou-order-received-text,
        .vb-checkout-page .woocommerce-order p,
        .vb-checkout-page .woocommerce-order .woocommerce-info,
        .vb-checkout-page .woocommerce-table--order-details th,
        .vb-checkout-page .woocommerce-table--order-details td,
        .vb-checkout-page .woocommerce-order-details .woocommerce-order-details__title,
        .vb-checkout-page .woocommerce-customer-details h2,
        .vb-checkout-page .woocommerce-column--billing-address address,
        .vb-checkout-page .woocommerce-column--shipping-address address,
        .vb-checkout-page .woocommerce-customer-details address,
        .vb-checkout-page .woocommerce-order .order-again,
        .vb-checkout-page .woocommerce-order .woocommerce-MyAccount-content p,
        .vb-checkout-page .woocommerce-order .payment_method,
        .vb-checkout-page .woocommerce-order .woocommerce-order-details__address {
            color: var(--accent-green) !important;
            font-size: 1rem !important;
        }
        .vb-checkout-page .woocommerce-thankyou-order-received-text { font-size: 1.15rem !important; }
        .vb-checkout-page .woocommerce-order h2 { color: var(--accent-green) !important; font-size: 1.25rem !important; }
        .vb-checkout-page .woocommerce-table--order-details { font-size: 1rem !important; }
        .vb-checkout-page .woocommerce-table--order-details .product-name { color: var(--accent-green) !important; }
        .vb-checkout-page .woocommerce-order .order_details tfoot th,
        .vb-checkout-page .woocommerce-order .order_details tfoot td { font-size: 1rem !important; color: var(--accent-green) !important; }
        .vb-checkout-page .woocommerce-order .addresses .title { color: var(--accent-green) !important; font-size: 1.1rem !important; }
        .vb-checkout-page .woocommerce-order a.edit { color: var(--accent-green) !important; font-size: 1rem !important; }
        .vb-checkout-page .woocommerce-order .woocommerce-notice { color: var(--accent-green) !important; font-size: 1rem !important; }
        .vb-checkout-page .woocommerce-order .woocommerce-notice--success,
        .vb-checkout-page .woocommerce-order .order_details tfoot th,
        .vb-checkout-page .woocommerce-order .order_details .order-total th { color: var(--accent-green) !important; font-size: 1rem !important; }
        .vb-checkout-page .woocommerce-order address { font-size: 1rem !important; line-height: 1.6 !important; }
        .vb-checkout-page .woocommerce-order,
        .vb-checkout-page .woocommerce-order *,
        .vb-checkout-page .woocommerce-thankyou-order-received { background: transparent !important; }

        @media (max-width: 768px) {
            .nav-header { padding: 0 1.5rem; }
        }
    </style>
</head>
<body <?php body_class( 'woocommerce-checkout vb-arms-checkout' ); ?>>

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
        <a href="<?php echo esc_url( $cart_url ); ?>" class="nav-contact-btn" style="border-color:var(--accent-green);">🛒 <?php echo (int) $cart_count; ?></a>
    </div>
</header>

<div class="vb-checkout-page">
    <div class="vb-checkout-inner">
        <?php if ( $is_order_received ) : ?>
        <div class="vb-checkout-title vb-thankyou-hero" style="margin-bottom: 2rem;">
            <div style="background: rgba(0, 255, 148, 0.1); padding: 10px; border-radius: 8px; border: 1px solid var(--border-pill); color: var(--accent-green); font-size: 1.5rem;">✓</div>
            <div>
                <h1 style="font-size: clamp(1.75rem, 4vw, 2.25rem);">Order received</h1>
                <p style="font-size: 1.1rem; color: var(--accent-green); margin-top: 0.5rem;">Thank you. Your order has been received.</p>
            </div>
        </div>
        <?php else : ?>
        <div class="vb-checkout-title">
            <div style="background: rgba(0, 255, 148, 0.1); padding: 8px; border-radius: 8px; border: 1px solid var(--border-pill); color: var(--accent-green);">✓</div>
            <div>
                <h1>Checkout</h1>
                <p>Verify acquisition details.</p>
            </div>
        </div>
        <?php endif; ?>

        <?php echo do_shortcode( '[woocommerce_checkout]' ); ?>

        <?php if ( ! $is_order_received ) : ?>
        <div class="checkout-payment-strip-wrap">
            <p class="payment-strip-label">We also accept</p>
            <div class="payment-strip">
                <div class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/btc.png' ); ?>" alt=""> BTC</div>
                <div class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/eth.png' ); ?>" alt=""> ETH</div>
                <div class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/usdc-logo.png' ); ?>" alt=""> USDC</div>
                <div class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/frnt.png' ); ?>" alt=""> FRNT</div>
                <div class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/metal-mtl-logo.png' ); ?>" alt=""> MTL</div>
                <div class="pay-item"><img src="<?php echo esc_url( function_exists( 'vb_arms_metamask_logo_url' ) ? vb_arms_metamask_logo_url() : ( $upload_dir['baseurl'] . '/logos/metamask.svg' ) ); ?>" alt=""> MetaMask</div>
                <div class="pay-item"><img src="<?php echo esc_url( function_exists( 'vb_arms_usdt_logo_url' ) ? vb_arms_usdt_logo_url() : ( $upload_dir['baseurl'] . '/logos/usdt-logo' ) ); ?>" alt=""> USDT</div>
                <div class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/us-dollar-512.png' ); ?>" alt=""> Traditional</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="signature-tagline">Your target. Our acquisition.</div>

<footer class="vb-footer">
    <p>© <?php echo date( 'Y' ); ?> VB ARMS • Bespoke Firearms Procurement</p>
    <div class="footer-links">
        <a href="<?php echo esc_url( home_url( '/browse/' ) ); ?>" class="footer-link">Browse</a>
        <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" class="footer-link">Privacy Policy</a>
        <a href="<?php echo esc_url( home_url( '/refund-returns/' ) ); ?>" class="footer-link">Refund & Returns</a>
        <a href="<?php echo esc_url( home_url( '/terms-of-service/' ) ); ?>" class="footer-link">Terms of Service</a>
    </div>
</footer>

<?php wp_footer(); ?>

</body>
</html>
