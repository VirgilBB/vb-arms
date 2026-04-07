<?php
/**
 * Template Name: VB Arms Order History
 * Description: Order history page with VB Arms nav, hero, and footer. Use for /orders/ or page_id=8.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
$is_logged_in = is_user_logged_in();
$orders = array();
if ( $is_logged_in ) {
    $orders = wc_get_orders( array(
        'customer' => get_current_user_id(),
        'status'   => array( 'wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending' ),
        'limit'    => 50,
        'orderby'  => 'date',
        'order'    => 'DESC',
    ) );
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History | <?php bloginfo( 'name' ); ?></title>
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
            --glass: rgba(255, 255, 255, 0.03);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body, body.vb-arms-orders,
        body.vb-arms-orders .site, body.vb-arms-orders .site-main,
        body.vb-arms-orders #content, body.vb-arms-orders main {
            background: #0a0a0a !important;
            background-color: #0a0a0a !important;
        }
        body, body.vb-arms-orders {
            color: var(--text-primary);
            font-family: 'JetBrains Mono', monospace;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .vb-orders-page {
            flex: 1;
            padding: 100px 1.5rem 3rem;
            max-width: 1100px;
            margin: 0 auto;
            width: 100%;
            background: #0a0a0a !important;
            background-color: #0a0a0a !important;
        }
        .nav-header {
            position: fixed; top: 0; left: 0; right: 0; height: 60px;
            background: rgba(0,0,0,0.95); backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--border-subtle); z-index: 1000;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 1.5rem;
        }
        .nav-logo a { display: flex; align-items: center; gap: 0.5rem; text-decoration: none; }
        .nav-logo img { height: 35px; width: auto; }
        .nav-logo-text { font-weight: 700; color: #fff; letter-spacing: 0.1em; text-transform: uppercase; font-size: 1rem; }
        .nav-actions { display: flex; gap: 0.5rem; align-items: center; }
        .nav-contact-btn {
            background: transparent; border: 1px solid var(--border-pill);
            padding: 0.4rem 1rem; border-radius: 50px; color: var(--accent-green);
            text-decoration: none; font-weight: 600; font-size: 0.75rem; transition: 0.3s;
        }
        .nav-contact-btn:hover { background: rgba(0, 255, 148, 0.1); }
        .vb-orders-hero { margin-bottom: 2rem; }
        .vb-orders-hero h1 {
            font-size: clamp(1.75rem, 4vw, 2.25rem);
            font-weight: 700;
            color: var(--accent-green);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            border-bottom: 1px solid var(--border-pill);
            padding-bottom: 10px;
            display: inline-block;
        }
        .vb-orders-hero p { color: var(--text-secondary); font-size: 1rem; margin-top: 0.5rem; }
        /* Box container: match cart_totals / checkout sections (glass + border-pill + 12px radius) */
        .vb-orders-table-wrap {
            background: rgba(255,255,255,0.03) !important;
            background-color: rgba(255,255,255,0.03) !important;
            border: 1px solid var(--border-pill) !important;
            border-radius: 12px;
            padding: 1.25rem;
            overflow: hidden;
        }
        .vb-orders-table,
        .vb-orders-table th,
        .vb-orders-table td,
        .vb-orders-table thead,
        .vb-orders-table tbody {
            background: transparent !important;
            background-color: transparent !important;
        }
        .vb-orders-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }
        /* Header row: transparent like cart, green text, single divider under row */
        .vb-orders-table thead tr {
            border-bottom: 1px solid var(--border-pill);
        }
        .vb-orders-table th {
            text-align: left;
            padding: 12px 0 14px 0;
            background: transparent !important;
            color: var(--accent-green);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: none;
        }
        .vb-orders-table th:first-child { padding-left: 0; }
        .vb-orders-table th:last-child { padding-right: 0; }
        /* Data rows: one full-width divider per row (match cart totals) */
        .vb-orders-table tbody tr {
            border-bottom: 1px solid var(--border-pill);
        }
        .vb-orders-table tbody tr:last-child { border-bottom: none; }
        .vb-orders-table td {
            padding: 14px 0;
            border-bottom: none;
            color: var(--text-primary);
        }
        .vb-orders-table td:first-child { padding-left: 0; }
        .vb-orders-table td:last-child { padding-right: 0; }
        .vb-orders-table tbody tr:hover td { background: transparent; }
        .vb-orders-table tbody tr:hover { background: rgba(255,255,255,0.02); }
        .vb-orders-table .order-total { color: var(--accent-green); font-weight: 600; font-family: 'JetBrains Mono', monospace; }
        .vb-orders-table .order-view a {
            color: var(--accent-green);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .vb-orders-table .order-view a:hover { text-decoration: underline; }
        .vb-orders-login {
            padding: 2rem;
            text-align: center;
            color: var(--text-secondary);
            font-size: 1.05rem;
        }
        .vb-orders-login a {
            color: var(--accent-green);
            text-decoration: none;
            font-weight: 600;
        }
        .vb-orders-login a:hover { text-decoration: underline; }
        .vb-orders-empty { padding: 2rem; text-align: center; color: var(--text-secondary); font-size: 1.05rem; }
        .signature-tagline { text-align: center; font-size: clamp(1.1rem, 3vw, 1.4rem); font-style: italic; color: var(--accent-green); margin: 2rem 0; font-weight: 300; }
        .vb-footer { padding: 15px 2rem 40px; border-top: 1px solid var(--border-subtle); text-align: center; }
        .vb-footer p { font-size: 0.8rem; margin-bottom: 8px; color: var(--text-secondary); }
        .footer-link { color: rgba(0, 255, 148, 0.75); text-decoration: none; margin: 0 10px; font-size: 0.75rem; }
        .footer-link:hover { color: var(--accent-green); }
        @media (max-width: 768px) {
            .vb-orders-table th:nth-child(3), .vb-orders-table td:nth-child(3) { display: none; }
        }
    </style>
</head>
<body class="vb-arms-orders">

<header class="nav-header">
    <div class="nav-logo">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
            <img src="<?php echo esc_url( $logo_url ); ?>" alt="VB ARMS">
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

<div class="vb-orders-page">
    <div class="vb-orders-hero">
        <h1>Order history</h1>
        <p>View and manage your orders.</p>
    </div>

    <?php if ( ! $is_logged_in ) : ?>
        <div class="vb-orders-login">
            <p>Please <a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">log in</a> to view your order history.</p>
        </div>
    <?php elseif ( empty( $orders ) ) : ?>
        <div class="vb-orders-empty">
            <p>You have no orders yet.</p>
            <p style="margin-top: 0.75rem;"><a href="<?php echo esc_url( $shop_url ); ?>" style="color: var(--accent-green); text-decoration: none; font-weight: 600;">Browse shop</a></p>
        </div>
    <?php else : ?>
        <div class="vb-orders-table-wrap">
            <table class="vb-orders-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $orders as $order ) :
                        if ( ! is_a( $order, 'WC_Order' ) ) { continue; }
                        $view_url = $order->get_view_order_url();
                        $status   = wc_get_order_status_name( $order->get_status() );
                    ?>
                    <tr>
                        <td>#<?php echo esc_html( $order->get_order_number() ); ?></td>
                        <td><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></td>
                        <td><?php echo esc_html( $status ); ?></td>
                        <td class="order-total"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
                        <td class="order-view"><a href="<?php echo esc_url( $view_url ); ?>">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="signature-tagline">Your target. Our acquisition.</div>

<footer class="vb-footer">
    <p>© <?php echo date( 'Y' ); ?> VB ARMS • Bespoke Firearms Procurement</p>
    <div>
        <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" class="footer-link">Privacy Policy</a>
        <a href="<?php echo esc_url( home_url( '/refund-returns/' ) ); ?>" class="footer-link">Refund & Returns</a>
        <a href="<?php echo esc_url( home_url( '/terms-of-service/' ) ); ?>" class="footer-link">Terms of Service</a>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
