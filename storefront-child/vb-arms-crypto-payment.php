<?php
/**
 * Template Name: VB Arms - Crypto Payment
 * Description: Crypto payment instructions — addresses to send to and contact vb@vb-arms.com
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

show_admin_bar( false );

$upload_dir   = wp_upload_dir();
$logo_paths   = array(
    get_stylesheet_directory() . '/vbarms-black-logo_512.png',
    get_stylesheet_directory() . '/logos/vbarms-black-logo_512.png',
    get_template_directory() . '/vbarms-black-logo_512.png',
    ABSPATH . 'wp-content/uploads/vbarms-black-logo_512.png',
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
    $logo_url = get_stylesheet_directory_uri() . '/vbarms-black-logo_512.png';
}
$shop_url  = apply_filters( 'vb_arms_breadcrumb_shop_url', home_url( '/shop/' ) );
$cart_url  = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
$cart_count = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
$theme_logos = get_stylesheet_directory_uri() . '/logos/';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crypto Payment | <?php bloginfo( 'name' ); ?></title>
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
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            display: flex; flex-direction: column; min-height: 100%;
            background: var(--primary-bg); color: var(--text-primary); font-family: 'JetBrains Mono', monospace; line-height: 1.6;
        }
        .vb-crypto-body-wrap { flex: 1 0 auto; display: flex; flex-direction: column; }
        .nav-header {
            position: fixed; top: 0; left: 0; right: 0; height: 60px;
            background: rgba(0,0,0,0.95); backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--border-subtle); z-index: 1000;
            display: flex; align-items: center; justify-content: space-between; padding: 0 3rem;
        }
        .nav-logo a { display: flex; align-items: center; gap: 0.5rem; text-decoration: none; color: #fff; }
        .nav-logo img { height: 35px; width: auto; }
        .nav-logo-text { font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; font-size: 1rem; }
        .nav-actions { display: flex; gap: 0.5rem; }
        .nav-contact-btn {
            background: transparent; border: 1px solid var(--border-pill);
            padding: 0.4rem 1rem; border-radius: 50px; color: var(--accent-green);
            text-decoration: none; font-weight: 600; font-size: 0.75rem; transition: 0.3s;
        }
        .vb-crypto-page { padding-top: 100px; padding-bottom: 3rem; max-width: 720px; margin: 0 auto; padding-left: 2rem; padding-right: 2rem; }
        .vb-crypto-page h1 { color: var(--accent-green); font-size: 1.75rem; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.08em; }
        .vb-crypto-page .subtitle { color: var(--text-secondary); font-size: 1rem; margin-bottom: 2rem; }
        .crypto-instruction-box {
            background: rgba(255,255,255,0.03); border: 1px solid var(--border-pill); border-radius: 12px;
            padding: 1.5rem; margin-bottom: 2rem;
        }
        .crypto-instruction-box p { margin-bottom: 0.75rem; font-size: 1.05rem; }
        .crypto-instruction-box a { color: var(--accent-green); text-decoration: none; font-weight: 600; }
        .crypto-instruction-box a:hover { text-decoration: underline; }
        .crypto-addresses { margin-top: 1.5rem; }
        .crypto-addresses h3 { color: var(--accent-green); font-size: 1rem; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .crypto-addresses .accepted-tickers { display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; color: var(--accent-green); font-weight: 600; }
        .crypto-addresses .accepted-tickers .pay-item { display: flex; align-items: center; gap: 0.35rem; font-size: 0.9rem; }
        .crypto-addresses .accepted-tickers .pay-item img { height: 22px; width: auto; }
        .crypto-addresses p, .crypto-addresses .address-line { font-family: 'JetBrains Mono', monospace; font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.5rem; word-break: break-all; }
        .payment-strip { display: flex; flex-wrap: wrap; justify-content: center; gap: 2rem; padding: 0 2rem 0.5rem; margin: 0; }
        .payment-strip .pay-item { display: flex; align-items: center; gap: 0.3rem; color: #fff; font-size: clamp(0.85rem, 2vw, 0.95rem); font-family: 'JetBrains Mono'; }
        .payment-strip .pay-item img { height: 20px; width: auto; }
        .signature-tagline { text-align: center; font-size: clamp(1.1rem, 3vw, 1.4rem); font-style: italic; color: var(--accent-green); margin: 2rem 0; font-weight: 300; }
        .vb-footer { flex-shrink: 0; margin-top: auto; padding: 15px 2rem 40px; border-top: 1px solid var(--border-subtle); text-align: center; }
        .vb-footer .footer-links { display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap; }
        .vb-footer p { font-size: 0.8rem; margin-bottom: 8px; color: var(--text-secondary); }
        .footer-link { color: rgba(0, 255, 148, 0.75); text-decoration: none; font-size: 0.75rem; transition: 0.2s; }
        .footer-link:hover { color: var(--accent-green); }
    </style>
</head>
<body <?php body_class( 'vb-arms-crypto-payment' ); ?>>

<div class="vb-crypto-body-wrap">
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
        <a href="<?php echo esc_url( $cart_url ); ?>" class="nav-contact-btn">🛒 <?php echo (int) $cart_count; ?></a>
    </div>
</header>

<div class="vb-crypto-page">
    <h1>Crypto payment</h1>
    <p class="subtitle">Addresses and instructions for paying with cryptocurrency.</p>

    <div class="crypto-instruction-box">
        <p><strong>Contact us for payment details.</strong> To receive wallet addresses and confirm price quote to send (BTC, ETH, USDC, FRNT, MTL, XPR), call <a href="tel:307-286-9128" style="color: var(--accent-green); text-decoration: none; font-weight: 600;">307-286-9128</a> or email <a href="mailto:vb@vb-arms.com">vb@vb-arms.com</a> with your order number or inquiry.</p>
        <p>We will reply with the address to send your crypto to and any network-specific instructions.</p>
    </div>

    <div class="crypto-addresses">
        <h3>Accepted</h3>
        <div class="accepted-tickers">
            <span class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/btc.png' ); ?>" alt=""> BTC</span>
            <span class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/eth.png' ); ?>" alt=""> ETH</span>
            <span class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/usdc-logo.png' ); ?>" alt=""> USDC</span>
            <span class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/frnt.png' ); ?>" alt=""> FRNT</span>
            <span class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/metal-mtl-logo.png' ); ?>" alt=""> MTL</span>
            <span class="pay-item"><img src="<?php echo esc_url( $theme_logos . 'xpr-white-logo.png' ); ?>" alt=""> XPR</span>
        </div>
        <p style="margin-top: 1rem;">After you send payment, email <a href="mailto:vb@vb-arms.com" style="color: var(--accent-green);">vb@vb-arms.com</a> with the transaction ID so we can confirm your payment.</p>
    </div>
</div>
</div>

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
    <div class="footer-links">
        <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" class="footer-link">Privacy Policy</a>
        <a href="<?php echo esc_url( home_url( '/refund-returns/' ) ); ?>" class="footer-link">Refund & Returns</a>
        <a href="<?php echo esc_url( home_url( '/terms-of-service/' ) ); ?>" class="footer-link">Terms of Service</a>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
