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
            --border-accent: rgba(0, 255, 148, 0.3);
            --glass: rgba(255, 255, 255, 0.03);
        }

        html, body {
            background: #0a0a0a !important;
            background-color: #0a0a0a !important;
            color: var(--text-primary);
            font-family: 'JetBrains Mono', monospace;
            margin: 0; min-height: 100vh;
            display: flex; flex-direction: column;
        }

        /* Nav: match cart style */
        .nav-header {
            position: fixed;
            top: 0; left: 0; right: 0;
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
        .nav-logo img { height: clamp(35px, 8vw, 50px); width: auto; }
        .nav-logo-text {
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            font-size: clamp(0.9rem, 4vw, 1.3rem);
            white-space: nowrap;
        }
        .nav-actions { display: flex; gap: 0.75rem; align-items: center; flex-shrink: 0; }
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
        .nav-cart-pill.current { background: rgba(0, 255, 148, 0.2); }
        .nav-cart-pill .nav-cart-emoji { font-size: 1.1rem; line-height: 1; }
        .nav-cart-pill .nav-cart-count { font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; }

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

        /* BILLING & SHIPPING SECTIONS — billing first, then shipping (no "Ship to different address?" checkbox) */
        .woocommerce-billing-fields, .woocommerce-shipping-fields, .woocommerce-additional-fields {
            background: var(--glass) !important;
            border: 1px solid var(--border-pill) !important;
            border-radius: 12px;
            padding: 1.25rem !important;
            margin-bottom: 1rem !important;
        }
        /* Shipping address block: visible only when "Ship to a different address?" is checked (WooCommerce JS toggles it) */
        .woocommerce-shipping-fields .shipping_address {
            /* Do not force display: block here — WooCommerce toggles visibility via JS */
        }
        /* Keep billing/shipping section wrappers visible (prevent flash-then-hide from scripts or broad selectors) */
        .vb-checkout-page .woocommerce-billing-fields,
        .vb-checkout-page .woocommerce-shipping-fields {
            display: block !important;
            visibility: visible !important;
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
            font-family: 'JetBrains Mono', monospace !important;
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

        /* All circle/radio inputs: same style – thin green border, transparent, checked = green dot */
        body.woocommerce-checkout input[type="radio"],
        body.vb-checkout-page input[type="radio"],
        .woocommerce-checkout input[type="radio"],
        .vb-checkout-page input[type="radio"],
        #payment input[type="radio"],
        #payment .payment_methods li input[type="radio"],
        body.woocommerce-checkout #payment .payment_methods li input[type="radio"],
        .woocommerce-checkout #payment .payment_methods li input[type="radio"] {
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            width: 18px !important;
            height: 18px !important;
            min-width: 18px !important;
            min-height: 18px !important;
            border: 1px solid #00ff94 !important;
            border-radius: 50% !important;
            background: transparent !important;
            background-color: transparent !important;
            box-shadow: none !important;
            margin-right: 0.75rem !important;
            position: relative !important;
            vertical-align: middle !important;
            flex-shrink: 0 !important;
        }
        body.woocommerce-checkout input[type="radio"]:checked,
        body.vb-checkout-page input[type="radio"]:checked,
        .woocommerce-checkout input[type="radio"]:checked,
        #payment input[type="radio"]:checked,
        #payment .payment_methods li input[type="radio"]:checked {
            border: 1px solid #00ff94 !important;
            background: transparent !important;
            background-color: transparent !important;
            box-shadow: none !important;
        }
        body.woocommerce-checkout input[type="radio"]:checked::after,
        body.vb-checkout-page input[type="radio"]:checked::after,
        .woocommerce-checkout input[type="radio"]:checked::after,
        #payment input[type="radio"]:checked::after {
            content: '' !important;
            display: block !important;
            width: 8px !important;
            height: 8px !important;
            border-radius: 50% !important;
            background: #00ff94 !important;
            background-color: #00ff94 !important;
            position: absolute !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
        }

        /* Checkboxes: thin green border, transparent */
        body.woocommerce-checkout input[type="checkbox"],
        body.vb-checkout-page input[type="checkbox"],
        .woocommerce-checkout input[type="checkbox"],
        .vb-checkout-page input[type="checkbox"],
        #payment input[type="checkbox"] {
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            width: 18px !important;
            height: 18px !important;
            min-width: 18px !important;
            min-height: 18px !important;
            border: 1px solid var(--accent-green) !important;
            border-radius: 4px !important;
            background: transparent !important;
            background-color: transparent !important;
            box-shadow: none !important;
            position: relative !important;
            flex-shrink: 0 !important;
        }
        body.woocommerce-checkout input[type="checkbox"]:checked,
        body.vb-checkout-page input[type="checkbox"]:checked,
        .vb-checkout-page input[type="checkbox"]:checked {
            border-color: var(--accent-green) !important;
            background: transparent !important;
            background-color: transparent !important;
        }
        body.woocommerce-checkout input[type="checkbox"]:checked::after,
        body.vb-checkout-page input[type="checkbox"]:checked::after,
        .vb-checkout-page input[type="checkbox"]:checked::after {
            content: '✓' !important;
            position: absolute !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            color: var(--accent-green) !important;
            font-size: 12px !important;
            font-weight: bold !important;
        }

        /* Pay with USDT row: left-aligned like other payment containers */
        body.woocommerce-checkout #payment .payment_methods li:nth-child(3),
        .woocommerce-checkout #payment .payment_methods li:nth-child(3) {
            justify-content: flex-start !important;
            align-items: flex-start !important;
        }
        body.woocommerce-checkout #payment .payment_methods li:nth-child(3) label,
        .woocommerce-checkout #payment .payment_methods li:nth-child(3) label {
            justify-content: flex-start !important;
        }

        /* Credit / Debit Card: "Credit / Debit Card" on first line, all card logos on second line (JS wraps logos in .vb-arms-card-logos-wrap) */
        body.woocommerce-checkout #payment .payment_methods li:nth-child(2) label,
        .woocommerce-checkout #payment .payment_methods li:nth-child(2) label {
            align-items: flex-start !important;
            flex-wrap: wrap !important;
        }
        body.woocommerce-checkout #payment .payment_methods li:nth-child(2) label .vb-arms-card-logos-wrap,
        .woocommerce-checkout #payment .payment_methods li:nth-child(2) label .payment_method_icons,
        body.woocommerce-checkout #payment .payment_methods li:nth-child(2) label > *:last-child {
            flex-basis: 100% !important;
            width: 100% !important;
            margin-top: 0.5rem !important;
            padding-left: calc(18px + 0.75rem) !important; /* align with "Credit / Debit Card" text above (radio width + gap) */
            box-sizing: border-box !important;
        }
        body.woocommerce-checkout #payment .payment_methods li:nth-child(2) label .vb-arms-card-logos-wrap {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            justify-content: flex-start !important;
            gap: 0.35rem !important;
        }
        body.woocommerce-checkout #payment .payment_methods li:nth-child(2) label img {
            display: inline-block !important;
            vertical-align: middle !important;
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
        /* Hide Pay with USDT expanded content (dropdown / extra UI) */
        body.woocommerce-checkout #payment .payment_methods li:nth-child(3) .payment_box,
        .woocommerce-checkout #payment .payment_methods li:nth-child(3) .payment_box {
            display: none !important;
        }
        #payment .payment_box,
        #payment .payment_box p,
        #payment .payment_box div { color: #e8e8e8 !important; font-size: 1rem !important; }
        #payment .payment_box a { color: rgba(0, 255, 148, 0.9) !important; font-size: 1rem !important; }
        #payment .payment_box .amount,
        #payment .payment_box [class*="amount"],
        #payment .payment_box input[readonly],
        #payment .payment_box input[type="text"] { color: #fff !important; font-size: 1.1rem !important; font-weight: 600 !important; }

        /* Crypto container – content left-aligned */
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
            align-items: flex-start !important;
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

        /* Link: column layout – head row, then strip (logos), then CTA below */
        .checkout-crypto-line-link {
            display: flex !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            justify-content: flex-start !important;
            width: 100% !important;
            min-width: 0 !important;
            background: transparent !important;
            border: none !important;
            color: #fff !important;
            text-decoration: none !important;
            padding: 0 !important;
            margin: 0 !important;
            gap: 0.75rem !important;
        }

        /* First row: radio + "Other crypto options" */
        .checkout-crypto-line-head {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
            gap: 0.75rem !important;
        }

        /* Fake radio – white filled circle, same size as payment method radios (16px) */
        .checkout-crypto-line-wrap .checkout-crypto-fake-radio,
        .checkout-crypto-line-link .checkout-crypto-fake-radio,
        .checkout-crypto-fake-radio {
            width: 16px !important;
            height: 16px !important;
            min-width: 16px !important;
            min-height: 16px !important;
            border: 1px solid #ffffff !important;
            border-radius: 50% !important;
            background: #ffffff !important;
            background-color: #ffffff !important;
            flex-shrink: 0 !important;
            box-shadow: none !important;
        }

        .checkout-crypto-head-text {
            color: #fff !important;
            font-weight: 600 !important;
            font-size: 1rem !important;
        }

        /* Strip: two rows of logos, then CTA below */
        .checkout-crypto-strip {
            display: flex !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            justify-content: flex-start !important;
            gap: 0.5rem !important;
            min-width: 0 !important;
        }

        .checkout-crypto-strip-row {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            justify-content: flex-start !important;
            gap: 0.5rem !important;
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
            color: var(--accent-green) !important;
            font-size: 0.85rem !important;
            font-weight: 600 !important;
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
        /* Exception: Other crypto options circle must be white filled (overrides * rule above) */
        .vb-checkout-page .checkout-crypto-fake-radio,
        .vb-checkout-page .woocommerce-checkout .checkout-crypto-fake-radio {
            background: #ffffff !important;
            background-color: #ffffff !important;
        }

        /* Footer block — match homepage spacing exactly */
        .checkout-payment-strip-wrap { margin-top: 0; padding: 0; }
        .checkout-payment-strip-wrap .payment-strip-label { text-align: center; color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0.5rem; }
        .checkout-payment-strip-wrap .payment-strip {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 2rem;
            padding: 0 2rem 0.5rem;
        }
        .checkout-payment-strip-wrap .pay-item {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            color: #fff;
            font-size: clamp(0.85rem, 2vw, 0.95rem);
            font-family: 'JetBrains Mono', monospace;
        }
        .checkout-payment-strip-wrap .pay-item img { height: 20px; width: auto; }

        .signature-tagline { text-align: center; font-size: clamp(1.1rem, 3vw, 1.4rem); font-style: italic; color: var(--accent-green); margin: 2rem 0; font-weight: 300; }
        .vb-footer { padding: 15px 2rem 40px; border-top: 1px solid var(--border-subtle); text-align: center; }
        .vb-footer p { font-size: 0.8rem; margin-bottom: 8px; color: var(--text-secondary); }
        .vb-footer .footer-links { display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap; }
        .footer-link { color: rgba(0, 255, 148, 0.75); text-decoration: none; font-size: 0.75rem; transition: 0.2s; }
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
        <a href="<?php echo esc_url( $cart_url ); ?>" class="nav-cart-pill"><span class="nav-cart-emoji">🛒</span><span class="nav-cart-count"><?php echo (int) $cart_count; ?></span></a>
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
        <div style="text-align: center; margin: 50px 0;">
            <a href="<?php echo esc_url( home_url( '/white-glove/' ) ); ?>" class="nav-contact-btn" style="padding: 0.5rem 2rem; font-size: 0.95rem;">Request a Custom Quote</a>
        </div>
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
                <div class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/us-dollar-512.png' ); ?>" alt=""> Traditional Payments</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="signature-tagline">Your target. Our acquisition.</div>

<footer class="vb-footer">
    <p>© <?php echo date( 'Y' ); ?> VB ARMS • Bespoke Firearms Procurement</p>
    <div class="footer-links">
        <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" class="footer-link">Privacy Policy</a>
        <a href="<?php echo esc_url( home_url( '/refund-returns/' ) ); ?>" class="footer-link">Refund & Returns</a>
        <a href="<?php echo esc_url( home_url( '/terms-of-service/' ) ); ?>" class="footer-link">Terms of Service</a>
    </div>
</footer>

<script>
(function() {
    function ensureBillingShippingVisible() {
        var billing = document.querySelector('.woocommerce-billing-fields');
        var shipping = document.querySelector('.woocommerce-shipping-fields');
        if (billing) { billing.style.setProperty('display', 'block', 'important'); billing.style.setProperty('visibility', 'visible', 'important'); }
        if (shipping) { shipping.style.setProperty('display', 'block', 'important'); shipping.style.setProperty('visibility', 'visible', 'important'); }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureBillingShippingVisible);
    } else {
        ensureBillingShippingVisible();
    }
    if (typeof jQuery !== 'undefined') {
        jQuery(document.body).on('updated_checkout', ensureBillingShippingVisible);
    }
})();
(function() {
    function unifyPaymentRadios() {
        var radios = document.querySelectorAll('#payment input[type="radio"]');
        radios.forEach(function(el) {
            el.style.removeProperty('border-color');
            el.style.removeProperty('background');
            el.style.removeProperty('background-color');
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', unifyPaymentRadios);
    } else {
        unifyPaymentRadios();
    }
    if (typeof jQuery !== 'undefined') {
        jQuery(document.body).on('updated_checkout', unifyPaymentRadios);
    }
})();

(function() {
    function removeMarketingOptIn() {
        var labels = document.querySelectorAll('.woocommerce-checkout label, form.checkout label, .vb-checkout-page label');
        labels.forEach(function(label) {
            var t = (label.textContent || '').trim();
            if (t.toLowerCase().indexOf('exclusive emails') !== -1 && t.toLowerCase().indexOf('discounts') !== -1) {
                var row = label.closest('.form-row') || label.closest('.form-group') || label.closest('p') || label.closest('div');
                if (row && !row.closest('.woocommerce-billing-fields') && !row.closest('.woocommerce-shipping-fields')) {
                    row.remove();
                }
            }
        });
        document.querySelectorAll('.woocommerce-checkout input[type="checkbox"]').forEach(function(cb) {
            var label = document.querySelector('label[for="' + (cb.id || '') + '"]') || cb.closest('label') || cb.parentElement;
            if (label) {
                var t = (label.textContent || '').trim();
                if (t.toLowerCase().indexOf('exclusive emails') !== -1 && t.toLowerCase().indexOf('discounts') !== -1) {
                    var row = cb.closest('.form-row') || cb.closest('.form-group') || cb.closest('p') || cb.closest('li') || cb.parentElement;
                    if (row && !row.closest('.woocommerce-billing-fields') && !row.closest('.woocommerce-shipping-fields')) {
                        row.remove();
                    }
                }
            }
        });
    }
    function runRemoval() {
        removeMarketingOptIn();
        setTimeout(removeMarketingOptIn, 400);
        setTimeout(removeMarketingOptIn, 1200);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runRemoval);
    } else {
        runRemoval();
    }
    if (typeof jQuery !== 'undefined') {
        jQuery(document.body).on('updated_checkout', runRemoval);
    }
})();
</script>
<?php wp_footer(); ?>

</body>
</html>
