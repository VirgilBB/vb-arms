<?php
/**
 * Template Name: VB Arms - Home
 * Description: High-contrast, 6-column grid, Best-Rate Assurance, and updated contact points.
 */

show_admin_bar(false);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php wp_title('|', true, 'right'); ?> <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-bg: #0a0a0a;
            --secondary-bg: #141414;
            --text-primary: #ffffff;
            --text-secondary: #b8b8b8;
            --accent-green: #00ff94;
            --border-subtle: rgba(255, 255, 255, 0.08);
            --border-accent: rgba(0, 255, 148, 0.3);
            --glass: rgba(255, 255, 255, 0.03);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--primary-bg);
            color: var(--text-primary);
            font-family: 'Space Grotesk', sans-serif;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Nav: match cart page exactly (logo + four pills) */
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
            font-family: 'JetBrains Mono', monospace;
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
        .nav-contact-btn.primary-cta {
            background: rgba(0, 255, 148, 0.25);
            color: var(--accent-green);
            font-weight: 700;
            border: 1px solid var(--border-accent) !important;
            box-shadow: 0 0 15px rgba(0, 255, 148, 0.2);
        }
        .nav-contact-btn.primary-cta:hover {
            background: rgba(0, 255, 148, 0.35);
            box-shadow: 0 0 25px rgba(0, 255, 148, 0.3);
            transform: translateY(-2px) scale(1.02);
        }
        .nav-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            flex-shrink: 0;
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
        .nav-cart-pill .nav-cart-emoji { font-size: 1.1rem; line-height: 1; }
        .nav-cart-pill .nav-cart-count { font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; }

        /* Mobile nav: same as cart/shop/checkout via functions.php (vb_arms_mobile_nav_shop_pages) when is_front_page() */

        /* CTA pills — same style as White Glove button; margin-top = space above the button */
        .cta-pill {
            display: inline-block;
            background: rgba(0, 255, 148, 0.1);
            border: 1px solid var(--accent-green);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            color: var(--accent-green);
            text-decoration: none;
            font-weight: 600;
            font-size: clamp(0.8rem, 2vw, 0.9rem);
            transition: 0.3s;
            margin-top: 3rem;
        }
        .cta-pill:hover {
            background: var(--accent-green);
            color: #000;
            transform: translateY(-2px);
        }

        /* Hero Section — forced to left margin (same as nav), no centering */
        .hero {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: flex-start;
            text-align: left !important;
            padding: clamp(75px, 15vw, 70px) clamp(1rem, 5vw, 4rem) 5px clamp(1rem, 5vw, 4rem);
            background: radial-gradient(circle at top right, rgba(0, 255, 148, 0.08), transparent);
        }
        .hero-content {
            max-width: 1200px;
            width: 100%;
            margin: 0 !important;
            margin-right: auto !important;
            padding: 0 !important;
            text-align: left !important;
            align-self: flex-start;
        }

        .ffl-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            background: var(--glass);
            border: 1px solid var(--border-accent);
            border-radius: 50px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            color: var(--accent-green);
            text-decoration: none;
            margin-bottom: 1.5rem;
            transition: 0.3s;
        }
        .ffl-badge .ffl-location { color: #fff; }

        .hero-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 0.5rem;
            color: #fff;
        }

        .hero-subtitle {
            color: var(--text-secondary);
            max-width: 800px;
            margin: 0 0 2rem 0;
            font-size: 1.1rem;
        }

        .curated-line {
            font-family: 'JetBrains Mono';
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 10px;
            opacity: 0.8;
        }

        /* Elite 6 — compact square grid to keep "Unmatched Quality" above the fold */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            max-width: 985px;
            width: 90%;
            margin: 0 auto 1rem;
        }

        .product-card {
            background: var(--secondary-bg);
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            overflow: hidden;
            transition: 0.3s;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
        }
        .product-card:hover {
            border-color: var(--accent-green);
            transform: translateY(-3px);
        }

        /* 1. Ensure all cards are square (1:1) */
        .product-grid .img-wrapper {
            aspect-ratio: 1 / 1;
            background-color: #050505;
            overflow: hidden;
            display: flex;
        }

        /* 2. LIFESTYLE CARDS (2 & 5): Added frame to match splits */
        .curated-2 .img-wrapper,
        .curated-5 .img-wrapper {
            padding: 5px; /* This creates the matching black border */
            background: #000;
        }
        .curated-2 .img-wrapper img,
        .curated-5 .img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px; /* Slight rounding inside the frame looks premium */
        }

        /* 3. PRODUCT CARDS (1 & 4): Keep as-is (Product in Box) */
        .curated-1 .img-wrapper img,
        .curated-4 .img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 5px; /* Heavy padding for product focus */
        }

        /* 4. SPLIT CARDS (3 & 6): Professional 2:1 Split */
        .product-grid .img-wrapper.split {
            display: flex;
            flex-direction: column;
            padding: 5px;
            gap: 5px;
            background: #000;
        }
        .product-grid .img-wrapper.split .split-half {
            flex: 1;
            width: 100%;
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .product-grid .img-wrapper.split .split-half img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }

        .p-info {
            padding: 0.35rem 0.75rem 0.25rem;
            border-top: 1px solid var(--border-subtle);
            text-align: center;
        }
        .p-info .build-name {
            color: #fff;
            font-weight: 600;
            font-size: clamp(0.8rem, 2.5vw, 0.9rem);
            display: block;
            margin-bottom: 0.1rem;
        }
        .p-info .build-caption {
            color: var(--text-secondary);
            font-size: clamp(0.65rem, 2vw, 0.75rem);
            line-height: 1.2;
        }

        /* Content Hubs — same width as stats grid and brand strip; left-aligned so titles line up */
        .content-hub {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto 1.25rem;
            background: var(--glass);
            border: 1px solid var(--border-accent);
            border-radius: 16px;
            padding: clamp(1rem, 3vw, 1.5rem);
            display: grid;
            grid-template-columns: 1fr 1fr;
            justify-items: start;
            align-items: center;
            gap: clamp(1rem, 3vw, 1.5rem);
        }

        .hub-title {
            color: var(--accent-green);
            font-size: clamp(1.8rem, 5vw, 2.2rem);
            margin-bottom: 0.5rem;
        }

        .hub-text {
            color: #fff;
            font-size: clamp(0.95rem, 2.3vw, 1.05rem);
            line-height: 1.5;
            margin-bottom: 0.5rem;
            text-align: justify;
            max-width: 480px;
        }

        .steps-box {
            background: rgba(0, 0, 0, 0.3);
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid var(--border-subtle);
        }

        .steps-box h3 {
            margin-bottom: 0.5rem;
        }
        .steps-box-title {
            color: var(--accent-green);
            font-size: clamp(1.25rem, 3.5vw, 1.5rem);
            font-weight: 700;
            margin-top: -0.2rem;
            margin-bottom: 0.5rem;
        }

        .steps-box p {
            margin-bottom: 0.5rem;
        }

        .step {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
            font-size: clamp(1rem, 2.5vw, 1.1rem);
            color: #fff;
        }

        .step:last-child {
            margin-bottom: 0;
        }

        .step-num {
            color: var(--accent-green);
            font-family: 'JetBrains Mono';
            font-weight: 700;
            font-size: clamp(1.2rem, 3vw, 1.4rem);
        }

        .content-hub .cta-pill {
            margin-top: 0.75rem;
        }

        .content-hub ul {
            margin: 0.75rem 0 0 0;
            padding: 0;
        }

        .content-hub ul li {
            margin-bottom: 0.55rem;
        }

        .content-hub ul li:last-child {
            margin-bottom: 0;
        }

        /* Assurance: title full-width above, left-aligned with White Glove section below */
        .content-hub--banner-title {
            row-gap: 1rem;
        }
        .content-hub--banner-title .hub-title {
            grid-column: 1 / -1;
            text-align: left;
            justify-self: start;
            margin-bottom: 0;
            margin-left: 0;
            white-space: nowrap;
        }
        .content-hub--banner-title .hub-text {
            margin-bottom: 1.3rem;
        }
        .content-hub--banner-title .hub-text:last-of-type {
            margin-bottom: 0;
        }
        .content-hub--banner-title .cta-pill {
            margin-top: 3rem;
        }
        @media (max-width: 640px) {
            .content-hub--banner-title .hub-title { white-space: normal; }
        }

        /* Stats — same width as content hubs and brand strip, stretches with viewport */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            max-width: 1200px;
            width: 100%;
            margin: 0 auto 3rem;
            padding: 0 2rem;
            box-sizing: border-box;
        }

        .stat-card {
            background: var(--glass);
            border: 1px solid var(--border-accent);
            padding: 1.2rem;
            border-radius: 10px;
            text-align: center;
        }

        .stat-number {
            font-family: 'JetBrains Mono';
            font-size: clamp(1.6rem, 4vw, 1.8rem);
            color: var(--accent-green);
            display: block;
            white-space: nowrap;
        }

        .stat-label {
            font-size: clamp(0.8rem, 2vw, 0.9rem);
            color: #fff;
            text-transform: uppercase;
            margin-top: 4px;
        }

        /* Brand strip — same width as content hubs and stats above */
        .brand-strip {
            max-width: 1200px;
            width: 100%;
            margin: 2rem auto;
            box-sizing: border-box;
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 1rem;
            padding: 0 2rem;
            align-items: center;
            justify-items: center;
        }
        .brand-strip .brand-strip-item {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 48px;
            min-height: 48px;
        }
        .brand-strip .brand-strip-item img {
            max-height: 32px;
            max-width: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            object-position: center;
        }
        
        /* Increase specific logos only */
        .brand-strip img[src*="glock" i],
        .brand-strip img[src*="savage" i],
        .brand-strip img[src*="taurus" i],
        .brand-strip img[src*="canik" i],
        .brand-strip img[src*="trijicon" i],
        .brand-strip img[src*="beretta" i],
        .brand-strip img[src*="berretta" i] {
            max-height: 50px;
        }

        /* Payment strip - no top padding so spacing above/below Request a Custom Quote is symmetrical */
        .payment-strip {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 2rem;
            padding: 0 2rem 0.5rem;
        }

        .pay-item {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            color: #fff;
            font-size: clamp(0.85rem, 2vw, 0.95rem);
            font-family: 'JetBrains Mono';
        }
        .pay-item img {
            height: 20px;
            width: auto;
        }

        .signature-tagline {
            text-align: center;
            font-size: clamp(1.1rem, 3vw, 1.4rem);
            font-style: italic;
            color: var(--accent-green);
            margin: 2rem 0;
            font-weight: 300;
        }

        .footer-link {
            color: rgba(0, 255, 148, 0.75);
            text-decoration: none;
            font-size: 0.75rem;
            transition: 0.2s;
        }
        .footer-link:hover {
            color: var(--accent-green);
        }

        /* Responsive — Curated Builds 2x3 → 2 cols on tablet, 1 on small mobile */
        @media (max-width: 900px) {
            .content-hub {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .brand-strip {
                grid-template-columns: repeat(5, 1fr);
            }
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 500px) {
            .product-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 768px) {
            .hero {
                text-align: center;
                padding: clamp(70px, 12vw, 85px) clamp(1rem, 4vw, 2rem) 0.5rem;
            }
            .hero-content {
                margin-left: auto;
            }
            .product-grid {
                margin-bottom: 1rem;
            }
            .section-assurance,
            .section-white-glove {
                margin-top: 0;
                padding-top: 0;
                padding-left: clamp(1rem, 4vw, 2rem) !important;
                padding-right: clamp(1rem, 4vw, 2rem) !important;
            }
            .curated-builds-title {
                margin: 0.5rem 0 0.75rem !important;
            }
        }
        @media (max-width: 640px) {
            .section-assurance,
            .section-white-glove {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
            .content-hub {
                padding: 1rem;
            }
        }

        /* Lightbox — ~35% of viewport, transparent overlay */
        .lightbox-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.3);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            cursor: pointer;
        }
        .lightbox-overlay.is-open {
            display: flex;
        }
        .lightbox-overlay .lightbox-inner {
            position: relative;
            cursor: default;
            max-width: min(90vw, 100%);
            max-height: 85vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .lightbox-overlay .lightbox-inner:focus {
            outline: none;
        }
        .lightbox-overlay .lightbox-img {
            max-width: 100%;
            max-height: 85vh;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
            border-radius: 8px;
        }
        .lightbox-overlay.lightbox-small .lightbox-img {
            max-width: min(70vw, 90vw);
            max-height: 70vh;
        }
        .lightbox-overlay.lightbox-xlarge .lightbox-inner {
            max-width: 96vw;
            max-height: 95vh;
        }
        .lightbox-overlay.lightbox-xlarge .lightbox-img {
            max-width: 96vw;
            max-height: 95vh;
        }
        .lightbox-close {
            position: absolute;
            top: -3rem;
            right: 0;
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid var(--accent-green);
            color: #fff;
            font-size: 2rem;
            line-height: 1;
            cursor: pointer;
            padding: 0.5rem 1rem;
            opacity: 1;
            border-radius: 8px;
            z-index: 10000;
        }
        .lightbox-close:hover {
            background: var(--accent-green);
            color: #000;
        }
        .product-card .img-wrapper img,
        .brand-strip .brand-strip-item img,
        .pay-item img {
            cursor: pointer;
        }
    </style>
</head>
<body>

    <?php
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
    $home_shop_url = apply_filters('vb_arms_breadcrumb_shop_url', home_url('/shop/'));
    $home_shop_url = (empty($home_shop_url) || trim($home_shop_url) === '') ? home_url('/shop/') : $home_shop_url;
    $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
    $cart_count = (function_exists('WC') && WC()->cart) ? WC()->cart->get_cart_contents_count() : 0;
    ?>

    <header class="nav-header" id="mainNav">
        <div class="nav-logo">
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <img src="<?php echo esc_url($logo_url); ?>" alt="VB Arms">
                <span class="nav-logo-text">VB ARMS</span>
            </a>
        </div>
        <div class="nav-actions">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="nav-contact-btn">Home</a>
            <a href="<?php echo esc_url($home_shop_url); ?>" class="nav-contact-btn">Shop</a>
            <a href="<?php echo esc_url(home_url('/white-glove/')); ?>" class="nav-contact-btn">White Glove</a>
            <a href="<?php echo esc_url($cart_url); ?>" class="nav-cart-pill" aria-label="Cart"><span class="nav-cart-emoji" aria-hidden="true">🛒</span><span class="nav-cart-count"><?php echo (int) $cart_count; ?></span></a>
        </div>
    </header>

    <section class="hero">
        <div class="hero-content">
            <a href="https://www.ffls.com/ffl/583021019a04518/benin-llc" target="_blank" rel="noopener noreferrer" class="ffl-badge">
                Licensed FFL Dealer • <span class="ffl-location">Cheyenne, Wyoming</span>
            </a>
            <h1 class="hero-title" style="margin-bottom: 1rem; font-size: clamp(2.3rem, 7vw, 3.3rem);">
                Pick your target. <span style="color: rgba(0, 255, 148, 0.85);">We procure.</span>
            </h1>
            <p class="hero-subtitle" style="font-size: clamp(1.1rem, 3.5vw, 1.4rem); margin-bottom: 1rem; color: #fff;">
                Sourced to spec. <span style="color: rgba(0, 255, 148, 0.85);">Beat-any-price guarantee.</span> In stock only.
            </p>
            <p class="hero-subtitle" style="font-size: clamp(1rem, 3vw, 1.2rem); color: var(--text-secondary); max-width: 800px;">
                Experience bespoke acquisition—your requirements met by our <span style="color:var(--accent-green); font-weight: 600;">500+ distributor network</span>. Name your caliber, we deliver.
            </p>
        </div>
    </section>

    <!-- Curated Builds — Elite 6 (2x3 grid, compact for above-the-fold) -->
    <h2 class="curated-builds-title" style="text-align: center; font-size: clamp(1.6rem, 5vw, 2.2rem); margin: 0.5rem 0 0.75rem; color: #fff; font-weight: 600;">Curated Builds</h2>
    <?php
    $stock_uri = get_stylesheet_directory_uri() . '/logos/stock-images/';
    $stock_dir = get_stylesheet_directory() . '/logos/stock-images/';
    // Elite 6: compact, contain. Card 6 bottom = golden light (crop to landscape strip for split box). Card 3 bottom = bag shot.
    $elite6 = array(
        array(
            'layout'  => 'single',
            'name'    => 'Backcountry Defense',
            'file'    => 'glock-hero-mags-belt.JPG',
            'file_lightbox' => 'glock-hero-mags-belt-lightbox.png',
            'file_lightbox_version' => '20260204230725',
            'caption' => 'Glock 20 Gen4 10mm. Bear and mountain lion defense for backcountry hunting.',
        ),
        array(
            'layout'  => 'single',
            'name'    => 'Precision Long Range',
            'file'    => 'tikka-hill-scope-original.JPG',
            'file_lightbox' => 'tikka-hill-scope-original.JPG',
            'file_lightbox_version' => '20260204224745',
            'lightbox_size' => 'xlarge',
            'caption' => 'Tikka T3x Precision. Expertly integrated with Vortex optics and bipod hardware.',
        ),
        array(
            'layout'  => 'split',
            'name'    => 'Field & Brush Shotgun',
            'lightbox_size' => 'xlarge',
            'top'     => 'stevens-shotgun.JPG',
            'top_lightbox' => 'stevens-shotgun-orignal.JPG',
            'bottom'  => 'stevens-savage+bag.jpeg',
            'bottom_version' => '20260204232215',
            'bottom_lightbox' => 'stevens-savage+bag-original.jpeg',
            'caption' => 'Stevens by Savage 301. Sourced with field ammunition and custom soft carry case.',
        ),
        array(
            'layout'  => 'single',
            'name'    => 'Rimfire Training Kit',
            'file'    => 'ruger pack.JPG',
            'file_lightbox' => 'ruger pack-original.JPG',
            'caption' => 'Ruger Mark IV Tactical. Complete legacy training kit sourced in factory hard case.',
        ),
        array(
            'layout'  => 'single',
            'name'    => 'Ammunition Supply',
            'file'    => 'ammo depth 1.JPG',
            'file_lightbox' => 'ammo top.JPG',
            'file_lightbox_version' => '20260205001015',
            'caption' => 'Bulk Ammunition Procurement. Multi-caliber sourcing across all cartridge types and gauges.',
        ),
        array(
            'layout'  => 'split',
            'name'    => 'Executive Procurement',
            'lightbox_size' => 'xlarge',
            'top'     => 'tikka-sniper-mountain.JPG',
            'top_version' => '20260204231840',
            'top_lightbox' => 'tikka-sniper-mountain original.JPG',
            'top_lightbox_version' => '20260204225545',
            'bottom'  => 'tikka-case.JPG',
            'bottom_lightbox' => 'tikka-case-original.JPG',
            'caption' => 'Bespoke long-range sourcing. Professional optics integration and custom-fit cases.',
        ),
    );
    ?>
    <section class="product-grid">
        <?php foreach ( $elite6 as $i => $build ) :
            $card_class = 'product-card curated-' . ( $i + 1 );
            $is_split = isset( $build['layout'] ) && $build['layout'] === 'split';
            $is_split_side = isset( $build['layout'] ) && $build['layout'] === 'split-side';
            $layout_class = '';
            if ( $is_split ) $layout_class = ' split';
            if ( $is_split_side ) $layout_class = ' split-side';
        ?>
        <div class="<?php echo esc_attr( $card_class ); ?>">
            <div class="img-wrapper<?php echo $layout_class; ?>">
                <?php if ( $is_split_side || $is_split ) : ?>
                <div class="split-half">
                    <?php 
                    $top_url = $stock_uri . rawurlencode( $build['top'] );
                    if ( isset( $build['top_version'] ) ) {
                        $top_url .= '?v=' . $build['top_version'];
                    }
                    $top_lightbox_url = '';
                    if ( isset( $build['top_lightbox'] ) ) {
                        $top_lightbox_url = $stock_uri . rawurlencode( $build['top_lightbox'] );
                        if ( isset( $build['top_lightbox_version'] ) ) {
                            $top_lightbox_url .= '?v=' . $build['top_lightbox_version'];
                        }
                    }
                    $lb_size = isset( $build['lightbox_size'] ) ? $build['lightbox_size'] : 'large';
                    ?>
                    <img src="<?php echo esc_url( $top_url ); ?>" 
                         <?php if ( $top_lightbox_url ) : ?>data-lightbox-src="<?php echo esc_url( $top_lightbox_url ); ?>"<?php endif; ?>
                         data-lightbox-size="<?php echo esc_attr( $lb_size ); ?>"
                         alt="<?php echo esc_attr( $build['name'] ); ?> — <?php echo $is_split_side ? 'left' : 'top'; ?>" loading="lazy">
                </div>
                <div class="split-half">
                    <?php 
                    $bottom_url = $stock_uri . rawurlencode( $build['bottom'] );
                    if ( isset( $build['bottom_version'] ) ) {
                        $bottom_url .= '?v=' . $build['bottom_version'];
                    }
                    $bottom_lightbox_url = '';
                    if ( isset( $build['bottom_lightbox'] ) ) {
                        $bottom_lightbox_url = $stock_uri . rawurlencode( $build['bottom_lightbox'] );
                        if ( isset( $build['bottom_lightbox_version'] ) ) {
                            $bottom_lightbox_url .= '?v=' . $build['bottom_lightbox_version'];
                        }
                    }
                    $lb_size_b = isset( $build['lightbox_size'] ) ? $build['lightbox_size'] : 'large';
                    ?>
                    <img src="<?php echo esc_url( $bottom_url ); ?>" 
                         <?php if ( $bottom_lightbox_url ) : ?>data-lightbox-src="<?php echo esc_url( $bottom_lightbox_url ); ?>"<?php endif; ?>
                         data-lightbox-size="<?php echo esc_attr( $lb_size_b ); ?>"
                         alt="<?php echo esc_attr( $build['name'] ); ?> — <?php echo $is_split_side ? 'right' : 'bottom'; ?>" loading="lazy">
                </div>
                <?php else : ?>
                <?php 
                $file_lightbox_url = '';
                if ( isset( $build['file_lightbox'] ) ) {
                    $file_lightbox_url = $stock_uri . rawurlencode( $build['file_lightbox'] );
                    if ( isset( $build['file_lightbox_version'] ) ) {
                        $file_lightbox_url .= '?v=' . $build['file_lightbox_version'];
                    }
                }
                $lb_size_s = isset( $build['lightbox_size'] ) ? $build['lightbox_size'] : 'large';
                ?>
                <img src="<?php echo esc_url( $stock_uri . rawurlencode( $build['file'] ) ); ?>" 
                     <?php if ( $file_lightbox_url ) : ?>data-lightbox-src="<?php echo esc_url( $file_lightbox_url ); ?>"<?php endif; ?>
                     data-lightbox-size="<?php echo esc_attr( $lb_size_s ); ?>"
                     alt="<?php echo esc_attr( $build['name'] ); ?>" loading="lazy">
                <?php endif; ?>
            </div>
            <div class="p-info">
                <span class="build-name"><?php echo esc_html( $build['name'] ); ?></span>
                <span class="build-caption"><?php echo esc_html( $build['caption'] ); ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </section>

    <!-- Assurance (Unmatched Quality) — above the fold on mobile -->
    <section class="section-assurance" style="padding: 0 2rem;">
        <div class="content-hub content-hub--banner-title">
            <h2 class="hub-title">Beat Any Price. In Stock Only.</h2>
            <div>
                <p class="hub-text">Find it cheaper? We'll beat it.</p>
                <a href="<?php echo esc_url(home_url('/white-glove/')); ?>" class="cta-pill">Submit Quote for Review →</a>
            </div>
            <div class="steps-box">
                <div class="step"><span class="step-num">01</span><span><strong>The Quote:</strong> Send us a competitor's current price.</span></div>
                <div class="step"><span class="step-num">02</span><span><strong>The Review:</strong> We verify product match and availability.</span></div>
                <div class="step"><span class="step-num">03</span><span><strong>The Beat:</strong> We beat their price. Guaranteed.</span></div>
            </div>
        </div>
    </section>

    <!-- White Glove Services — same horizontal padding as Assurance for alignment -->
    <section class="section-white-glove" style="padding: 0 2rem;">
        <div class="content-hub">
            <div>
                <h2 class="hub-title">White Glove Services</h2>
                <p class="hub-text">We source firearms through our 500+ distributor network.</p>
                <ul style="list-style:none; margin: 0.75rem 0 0 0; padding: 0; color:#fff; font-size:clamp(0.95rem, 2.5vw, 1.05rem);">
                    <li style="margin-bottom: 0.55rem;"><span style="color:var(--accent-green); margin-right: 0.5rem;">→</span>Firearms Sourcing</li>
                    <li style="margin-bottom: 0.55rem;"><span style="color:var(--accent-green); margin-right: 0.5rem;">→</span>FFL Transfers</li>
                    <li style="margin-bottom: 0.55rem;"><span style="color:var(--accent-green); margin-right: 0.5rem;">→</span>Hardware Integration</li>
                    <li style="margin-bottom: 0;"><span style="color:var(--accent-green); margin-right: 0.5rem;">→</span>Direct Distributor Access</li>
                </ul>
            </div>
            <div class="steps-box">
                <h3 class="steps-box-title">The Process</h3>
                <p style="color:#fff; font-size:clamp(0.95rem, 2.5vw, 1.05rem); margin-bottom:0.5rem;">Submit your specs. We source from our 500+ distributor network. You get the best price, fast.</p>
                <a href="<?php echo esc_url(home_url('/white-glove/')); ?>" class="cta-pill">Start Process →</a>
            </div>
        </div>
    </section>

    <!-- Sleek Stats -->
    <section class="stats-grid">
        <div class="stat-card">
            <span class="stat-number">500+</span>
            <span class="stat-label">Distributor Network</span>
        </div>
        <div class="stat-card">
            <span class="stat-number">24-36h</span>
            <span class="stat-label">Rapid Sourcing</span>
        </div>
        <div class="stat-card">
            <span class="stat-number">100%</span>
            <span class="stat-label">Federal Compliance</span>
        </div>
        <div class="stat-card">
            <span class="stat-number">Best Price</span>
            <span class="stat-label">Price Match Guarantee</span>
        </div>
    </section>

    <!-- Brand Strip — 30 logos from theme logos/brands/ or uploads -->
    <section class="brand-strip">
        <?php
        $brands_dir  = trailingslashit( get_stylesheet_directory() ) . 'logos/brands/';
        $brands_uri  = trailingslashit( get_stylesheet_directory_uri() ) . 'logos/brands/';
        $uploads_dir = trailingslashit( $upload_dir['basedir'] ) . 'logos/brands/';
        $uploads_uri = trailingslashit( $upload_dir['baseurl'] ) . 'logos/brands/';
        $extensions  = array( 'png', 'jpg', 'jpeg', 'webp', 'gif' );
        $files       = array();
        $base_uri    = $brands_uri;
        if ( is_dir( $brands_dir ) ) {
            $base_dir = $brands_dir;
            $base_uri = $brands_uri;
        } elseif ( is_dir( $uploads_dir ) ) {
            $base_dir = $uploads_dir;
            $base_uri = $uploads_uri;
        } else {
            $base_dir = '';
        }
        if ( $base_dir ) {
            foreach ( $extensions as $ext ) {
                $glob = glob( $base_dir . '*.' . $ext );
                if ( $glob ) {
                    $files = array_merge( $files, $glob );
                }
            }
            foreach ( $files as $path ) {
                $base = basename( $path );
                $url  = $base_uri . rawurlencode( $base );
                $alt  = preg_replace( '/\.[a-z]+$/i', '', $base );
                $alt  = str_replace( array( '-', '_', '.' ), ' ', $alt );
                echo '<div class="brand-strip-item"><img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy"></div>';
            }
        }
        ?>
    </section>

    <div style="text-align: center; margin: 50px 0;">
        <a href="<?php echo esc_url(home_url('/white-glove/')); ?>" class="nav-contact-btn" style="padding: 0.5rem 2rem; font-size: 0.95rem;">Request a Custom Quote</a>
    </div>

    <div class="payment-strip">
        <div class="pay-item"><img src="<?php echo esc_url($upload_dir['baseurl'] . '/logos/btc.png'); ?>" alt=""> BTC</div>
        <div class="pay-item"><img src="<?php echo esc_url($upload_dir['baseurl'] . '/logos/eth.png'); ?>" alt=""> ETH</div>
        <div class="pay-item"><img src="<?php echo esc_url($upload_dir['baseurl'] . '/logos/usdc-logo.png'); ?>" alt=""> USDC</div>
        <div class="pay-item"><img src="<?php echo esc_url($upload_dir['baseurl'] . '/logos/frnt.png'); ?>" alt=""> FRNT</div>
        <div class="pay-item"><img src="<?php echo esc_url($upload_dir['baseurl'] . '/logos/metal-mtl-logo.png'); ?>" alt=""> MTL</div>
        <div class="pay-item"><img src="<?php echo esc_url( function_exists( 'vb_arms_metamask_logo_url' ) ? vb_arms_metamask_logo_url() : ( $upload_dir['baseurl'] . '/logos/metamask.svg' ) ); ?>" alt=""> MetaMask</div>
        <div class="pay-item"><img src="<?php echo esc_url( function_exists( 'vb_arms_usdt_logo_url' ) ? vb_arms_usdt_logo_url() : ( $upload_dir['baseurl'] . '/logos/usdt-logo' ) ); ?>" alt=""> USDT</div>
        <div class="pay-item"><img src="<?php echo esc_url($upload_dir['baseurl'] . '/logos/us-dollar-512.png'); ?>" alt=""> Traditional Payments</div>
    </div>

    <div class="signature-tagline">Your target. Our acquisition.</div>

    <footer class="vb-footer" style="padding: 15px 2rem 40px; text-align: center; border-top: 1px solid var(--border-subtle);">
        <p style="font-size: 0.8rem; margin-bottom: 8px; color: var(--text-secondary);">© <?php echo date('Y'); ?> VB ARMS • Bespoke Firearms Procurement</p>
        <div class="footer-links" style="display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap;">
            <a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>" class="footer-link">Privacy Policy</a>
            <a href="<?php echo esc_url(home_url('/refund-returns/')); ?>" class="footer-link">Refund & Returns</a>
            <a href="<?php echo esc_url(home_url('/terms-of-service/')); ?>" class="footer-link">Terms of Service</a>
        </div>
    </footer>

    <!-- Lightbox: click overlay or × to close -->
    <div class="lightbox-overlay" id="lightbox" aria-hidden="true" role="dialog" aria-modal="true" aria-label="View image">
        <div class="lightbox-inner" onclick="event.stopPropagation()">
            <img class="lightbox-img" src="" alt="">
            <button type="button" class="lightbox-close" aria-label="Close">&times;</button>
        </div>
    </div>

    <?php wp_footer(); ?>

    <script>
        (function() {
            var lastScroll = 0;
            var nav = document.getElementById('mainNav');
            if (!nav) return;
            window.addEventListener('scroll', function() {
                var currentScroll = window.pageYOffset;
                if (currentScroll <= 0) {
                    nav.style.transform = "translateY(0)";
                    return;
                }
                if (currentScroll > lastScroll && currentScroll > 80) {
                    nav.style.transform = "translateY(-100%)";
                } else {
                    nav.style.transform = "translateY(0)";
                }
                lastScroll = currentScroll;
            });
        })();

        (function() {
            var lightbox = document.getElementById('lightbox');
            if (!lightbox) return;
            var img = lightbox.querySelector('.lightbox-img');
            var inner = lightbox.querySelector('.lightbox-inner');
            var closeBtn = lightbox.querySelector('.lightbox-close');

            function openLightbox(src, alt, size) {
                img.src = src;
                img.alt = alt || '';
                lightbox.classList.remove('lightbox-large', 'lightbox-small', 'lightbox-xlarge');
                lightbox.classList.add('lightbox-' + (size || 'large'));
                lightbox.classList.add('is-open');
                lightbox.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }
            function closeLightbox() {
                lightbox.classList.remove('is-open');
                lightbox.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            document.addEventListener('click', function(e) {
                var target = e.target;
                if (target.closest('.product-card .img-wrapper img')) {
                    e.preventDefault();
                    var lightboxSrc = target.getAttribute('data-lightbox-src') || target.src;
                    var size = target.getAttribute('data-lightbox-size') || 'large';
                    openLightbox(lightboxSrc, target.alt, size);
                } else if (target.closest('.brand-strip img')) {
                    e.preventDefault();
                    openLightbox(target.src, target.alt, 'small');
                } else if (target.closest('.pay-item img')) {
                    e.preventDefault();
                    openLightbox(target.src, target.alt, 'small');
                }
            });

            lightbox.addEventListener('click', function(e) {
                if (e.target === lightbox) closeLightbox();
            });
            if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && lightbox.classList.contains('is-open')) closeLightbox();
            });
        })();
    </script>
</body>
</html>