<?php
/**
 * Template Name: VB Arms - Page
 * Description: Standard VB Arms layout for new page deployments. Use for new pages that need consistent header, footer, and content area.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

show_admin_bar( false );

$logo_paths = array(
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
$shop_url   = apply_filters( 'vb_arms_breadcrumb_shop_url', home_url( '/shop/' ) );
$cart_url   = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
$cart_count = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
$upload_dir = wp_upload_dir();
$theme_logos = get_stylesheet_directory_uri() . '/logos/';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title( '|', true, 'right' ); ?><?php bloginfo( 'name' ); ?></title>
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
            background: var(--primary-bg); color: var(--text-primary); font-family: 'Space Grotesk', sans-serif; line-height: 1.6;
        }
        .vb-page-body-wrap { flex: 1 0 auto; display: flex; flex-direction: column; }
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
        .nav-contact-btn:hover { background: var(--accent-green); color: #000; }
        .vb-page-content {
            padding-top: 100px; padding-bottom: 3rem; max-width: 720px; margin: 0 auto;
            padding-left: 2rem; padding-right: 2rem; flex: 1 0 auto;
        }
        .vb-page-content h1 { color: var(--accent-green); font-size: 1.75rem; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.08em; }
        .vb-page-content .subtitle { color: var(--text-secondary); font-size: 1rem; margin-bottom: 2rem; }
        .vb-page-content p { margin-bottom: 1rem; color: var(--text-secondary); }
        .vb-page-content a { color: var(--accent-green); text-decoration: none; font-weight: 600; }
        .vb-page-content a:hover { text-decoration: underline; }
        .vb-page-content h2 { color: var(--accent-green); font-size: 1.2rem; margin-top: 2rem; margin-bottom: 0.75rem; }
        .vb-page-content ul, .vb-page-content ol { margin: 0 0 1rem 1.25rem; color: var(--text-secondary); }
        .vb-footer { flex-shrink: 0; margin-top: auto; padding: 15px 2rem 40px; border-top: 1px solid var(--border-subtle); text-align: center; }
        .vb-footer p { font-size: 0.8rem; margin-bottom: 8px; color: var(--text-secondary); }
        .footer-link { color: rgba(0, 255, 148, 0.75); text-decoration: none; margin: 0 10px; font-size: 0.75rem; }
        .footer-link:hover { color: var(--accent-green); }
        .payment-strip { display: flex; flex-wrap: wrap; justify-content: center; gap: 1.5rem; padding: 1rem 0; }
        .pay-item { display: flex; align-items: center; gap: 0.35rem; color: var(--accent-green); font-size: 0.9rem; font-weight: 600; }
        .pay-item img { height: 22px; width: auto; }
    </style>
</head>
<body <?php body_class( 'vb-arms-page' ); ?>>

<div class="vb-page-body-wrap">
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

<main class="vb-page-content">
    <?php
    while ( have_posts() ) {
        the_post();
        the_content();
    }
    ?>
</main>
</div>

<footer class="vb-footer">
    <p>© <?php echo date( 'Y' ); ?> VB ARMS • Bespoke Firearms Procurement</p>
    <div class="payment-strip">
        <span class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/btc.png' ); ?>" alt=""> BTC</span>
        <span class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/eth.png' ); ?>" alt=""> ETH</span>
        <span class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/usdc-logo.png' ); ?>" alt=""> USDC</span>
        <span class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/frnt.png' ); ?>" alt=""> FRNT</span>
        <span class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/metal-mtl-logo.png' ); ?>" alt=""> MTL</span>
        <span class="pay-item"><img src="<?php echo esc_url( $theme_logos . 'xpr-white-logo.png' ); ?>" alt=""> XPR</span>
    </div>
    <div style="margin-top: 1rem;">
        <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" class="footer-link">Privacy Policy</a>
        <a href="<?php echo esc_url( home_url( '/refund-returns/' ) ); ?>" class="footer-link">Refund & Returns</a>
        <a href="<?php echo esc_url( home_url( '/terms-of-service/' ) ); ?>" class="footer-link">Terms of Service</a>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
