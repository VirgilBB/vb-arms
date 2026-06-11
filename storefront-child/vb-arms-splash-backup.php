<?php
/**
 * Template Name: Backup Homepage
 * Description: Full backup of the original VB Arms splash (Featured Firearms, services, stats). Use for reference or rollback.
 */

show_admin_bar(false);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title('|', true, 'right'); ?> <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-bg: #0f0f0f;
            --secondary-bg: #1a1a1a;
            --accent-bg: #252525;
            --text-primary: #ffffff;
            --text-secondary: #b8b8b8;
            --text-muted: #808080;
            --accent-green: #00ff94;
            --accent-blue: #0066ff;
            --accent-orange: #ff6b35;
            --border-subtle: rgba(255, 255, 255, 0.08);
            --border-accent: rgba(0, 255, 148, 0.3);
            --glass: rgba(255, 255, 255, 0.04);
            --glass-border: rgba(255, 255, 255, 0.12);
        }

        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { 
            margin: 0 !important; 
            padding: 0 !important; 
            overflow-x: hidden;
            font-family: 'Space Grotesk', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: var(--primary-bg);
            color: var(--text-primary);
            line-height: 1.6;
            font-weight: 400;
            scroll-behavior: smooth;
        }

        #page, .site-content { margin: 0; padding: 0; }
        .woocommerce-notices-wrapper { display: none; }

        /* Navigation Bar */
        .nav-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 80px;
            background: #000000;
            border-bottom: 1px solid var(--border-subtle);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            transition: all 0.3s ease;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            opacity: 0;
            animation: slideInLeft 0.8s ease-out 0.2s forwards;
        }

        .nav-logo img {
            height: 60px;
            width: auto;
        }

        .nav-logo-text {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .nav-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
            opacity: 0;
            animation: slideInRight 0.8s ease-out 0.4s forwards;
        }

        /* Modern Cart Button */
        .nav-cart {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.25rem;
            background: rgba(0, 255, 148, 0.15);
            border: 1px solid var(--accent-green);
            border-radius: 12px;
            text-decoration: none;
            color: var(--accent-green);
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            backdrop-filter: blur(10px);
            overflow: hidden;
        }

        .nav-cart::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 255, 148, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .nav-cart:hover::before {
            left: 100%;
        }

        .nav-cart:hover {
            background: rgba(0, 255, 148, 0.08);
            border-color: var(--accent-green);
            color: var(--accent-green);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 255, 148, 0.15);
        }

        .nav-cart-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-cart-count {
            display: none !important;
        }

        @keyframes cartBounce {
            0% { transform: scale(0); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); }
        }

        /* Hero Section - Fixed Padding */
        .hero {
            min-height: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            background: 
                radial-gradient(ellipse at 20% 30%, rgba(0, 255, 148, 0.15) 0%, transparent 40%),
                radial-gradient(ellipse at 80% 70%, rgba(0, 102, 255, 0.1) 0%, transparent 40%),
                linear-gradient(135deg, var(--primary-bg) 0%, var(--secondary-bg) 100%);
            position: relative;
            overflow: hidden;
            padding: 100px 2rem 25px;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                repeating-linear-gradient(
                    90deg,
                    transparent,
                    transparent 100px,
                    rgba(255, 255, 255, 0.01) 101px,
                    rgba(255, 255, 255, 0.01) 102px
                );
            pointer-events: none;
        }

        .hero-content {
            max-width: 1200px;
            width: 100%;
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 50px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            color: var(--accent-green);
            margin-bottom: 1.5rem;
            opacity: 0;
            animation: fadeInUp 1s ease-out 0.6s forwards;
        }

        .hero-badge::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent-green);
            box-shadow: 0 0 10px var(--accent-green);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.2); }
        }

        .hero-title {
            font-size: clamp(2.5rem, 7vw, 5rem);
            font-weight: 700;
            line-height: 0.95;
            margin-bottom: 1rem;
            letter-spacing: -0.03em;
        }

        .hero-title-line {
            display: block;
            opacity: 0;
        }

        .hero-title-line:nth-child(1) {
            animation: slideInLeft 1.2s ease-out 0.8s forwards;
        }

        .hero-title-line:nth-child(2) {
            color: var(--accent-green);
            animation: slideInLeft 1.2s ease-out 1s forwards;
        }

        .hero-title-line:nth-child(3) {
            animation: slideInLeft 1.2s ease-out 1.2s forwards;
        }

        .hero-subtitle {
            font-size: clamp(1rem, 2.5vw, 1.3rem);
            color: var(--text-secondary);
            margin-bottom: 2rem;
            max-width: 600px;
            opacity: 0;
            animation: fadeInUp 1s ease-out 1.4s forwards;
        }

        .hero-cta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0;
            animation: fadeInUp 1s ease-out 1.6s forwards;
        }

        .btn {
            position: relative;
            padding: 1rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary,
        .btn-secondary {
            background: rgba(0, 255, 148, 0.15);
            color: var(--accent-green);
            border: 1px solid var(--border-accent);
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        .btn-primary:hover,
        .btn-secondary:hover {
            background: rgba(0, 255, 148, 0.25);
            border-color: var(--border-accent);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 255, 148, 0.3);
        }

        /* Services Section */
        .services-section {
            padding: 3rem 2rem;
            background: var(--secondary-bg);
            position: relative;
        }

        .services-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 200px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent-green), transparent);
        }

        .services-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .services-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .services-title {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .services-subtitle {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .service-card {
            background: var(--glass);
            border: 1px solid var(--border-accent);
            border-radius: 16px;
            padding: 2rem;
            position: relative;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-green), var(--accent-blue));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .service-card:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: var(--border-accent);
            transform: translateY(-8px);
        }

        .service-card:hover::before {
            opacity: 1;
        }

        .service-icon {
            width: 60px;
            height: 60px;
            background: var(--accent-green);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            color: var(--primary-bg);
        }

        .service-card:nth-child(2) .service-icon {
            background: var(--accent-blue);
        }

        .service-card:nth-child(3) .service-icon {
            background: var(--accent-orange);
        }

        .service-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .service-description {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }

        .service-features {
            list-style: none;
            margin-bottom: 2rem;
        }

        .service-features li {
            padding: 0.5rem 0;
            padding-left: 1.5rem;
            position: relative;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .service-features li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--accent-green);
            font-weight: 600;
        }

        /* Stats Section */
        .stats-section {
            padding: 3rem 2rem;
            background: var(--primary-bg);
        }

        .stats-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
        }

        .stat-card {
            text-align: center;
            padding: 2rem;
            background: var(--glass);
            border: 1px solid var(--border-accent);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            background: rgba(0, 255, 148, 0.05);
            border-color: var(--border-accent);
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0, 255, 148, 0.15);
        }

        .stat-number {
            font-family: 'JetBrains Mono', monospace;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--accent-green);
            display: block;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 500;
        }

        /* Trust Section */
        .trust-section {
            padding: 3rem 2rem;
            background: var(--secondary-bg);
            text-align: center;
        }

        .trust-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .trust-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 2rem;
            color: var(--text-primary);
        }

        .trust-badges {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .trust-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 1.5rem;
            background: var(--glass);
            border: 1px solid var(--border-accent);
            border-radius: 50px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
            color: var(--text-secondary);
            transition: all 0.3s ease;
        }

        .trust-badge:hover {
            background: rgba(0, 255, 148, 0.05);
            border-color: var(--border-accent);
            color: var(--accent-green);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 255, 148, 0.15);
        }

        .trust-badge::before {
            content: '●';
            color: var(--accent-green);
        }

        .payment-methods {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
        }

        .payment-method {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: var(--accent-bg);
            border: 1px solid var(--border-accent);
            border-radius: 8px;
            font-size: 0.9rem;
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .payment-method img {
            height: 24px;
            width: auto;
            object-fit: contain;
        }

        .payment-method.crypto {
            color: var(--accent-green);
        }

        .payment-method:hover {
            background: var(--glass);
            transform: translateY(-2px);
        }

        /* Products Showcase Section */
        .products-section {
            padding: 4rem 2rem;
            background: var(--primary-bg);
            position: relative;
        }

        .products-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 200px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent-green), transparent);
        }

        .products-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .products-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .products-title {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .products-subtitle {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto 2rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-bottom: 3rem;
        }

        @media (max-width: 1024px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
        }

        .product-card {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-green), var(--accent-blue));
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1;
        }

        .product-card:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: var(--border-accent);
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .product-card:hover::before {
            opacity: 1;
        }

        .product-image-wrapper {
            position: relative;
            width: 100%;
            padding-top: 75%; /* 4:3 aspect ratio */
            background: var(--secondary-bg);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image-wrapper img {
            position: absolute !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) scaleX(1) rotate(0deg) !important;
            max-width: 90% !important;
            max-height: 90% !important;
            width: auto !important;
            height: auto !important;
            object-fit: contain !important;
            object-position: center !important;
            padding: 1rem !important;
            transition: transform 0.5s ease !important;
            image-orientation: none !important;
            transform-origin: center center !important;
        }

        .product-card:hover .product-image-wrapper img {
            transform: translate(-50%, -50%) scale(1.05) scaleX(1) rotate(0deg) !important;
        }

        .product-info {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--accent-green);
            margin-top: auto;
        }

        .product-price .woocommerce-Price-amount {
            color: var(--accent-green);
        }

        .products-cta {
            text-align: center;
            margin-top: 2rem;
        }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-header {
                height: 60px;
                padding: 0 1rem;
            }

            .nav-logo {
                gap: 0.75rem;
            }

            .nav-logo img {
                height: 45px;
            }

            .nav-logo-text {
                font-size: 1.2rem;
            }

            .nav-cart span:not(.nav-cart-count) {
                display: none;
            }

            .hero {
                padding: 70px 1rem 20px;
                min-height: 50vh;
            }

            .hero-cta {
                flex-direction: column;
                align-items: stretch;
            }

            .services-section {
                padding: 2rem 1rem;
            }

            .services-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .service-card {
                padding: 1.5rem;
            }

            .stats-section {
                padding: 2rem 1rem;
            }

            .stats-container {
                gap: 2rem;
            }

            .trust-section {
                padding: 2rem 1rem;
            }

            .trust-badges {
                flex-direction: column;
                align-items: center;
            }

            .payment-methods {
                gap: 0.75rem;
            }

            .products-section {
                padding: 2rem 1rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1.5rem;
            }

            .product-info {
                padding: 1rem;
            }

            .product-name {
                font-size: 1rem;
            }

            .product-price {
                font-size: 1rem;
            }
        }

        /* Scroll animations */
        .scroll-animate {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease;
        }

        .scroll-animate.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* --- OPTION 1: ABOVE THE FOLD DESKTOP OPTIMIZATION --- */
        @media (min-width: 1024px) {
            /* 1. Shrink Navigation height */
            .nav-header {
                height: 60px;
            }
            .nav-logo img {
                height: 40px;
            }

            /* 2. Tighten Hero Section */
            .hero {
                padding-top: 70px; /* Barely clears the 60px nav */
                padding-bottom: 0px;
                min-height: auto;
            }

            .hero-badge {
                margin-bottom: 0.5rem;
                padding: 0.25rem 0.75rem;
                font-size: 0.7rem;
            }

            .hero-title {
                font-size: 2.8rem; /* Scaled down from 5rem */
                line-height: 1.1;
                margin-bottom: 0.5rem;
            }

            .hero-subtitle {
                font-size: 1rem;
                margin-bottom: 1rem;
                max-width: 800px; /* Wider to take up less vertical lines */
            }

            .hero-cta {
                margin-bottom: 1.5rem;
                gap: 1rem;
            }

            .btn {
                padding: 0.6rem 1.2rem;
                font-size: 0.9rem;
            }

            /* 3. Tighten Products Section */
            .products-section {
                padding-top: 1.5rem; /* Reduced from 4rem */
                padding-bottom: 1.5rem;
            }

            .products-header {
                margin-bottom: 1.5rem; /* Reduced from 3rem */
                display: flex;
                align-items: center;
                justify-content: space-between;
                text-align: left;
            }

            .products-title {
                font-size: 1.5rem; /* Smaller header */
                margin-bottom: 0;
            }

            .products-subtitle {
                font-size: 0.9rem;
                margin: 0;
                text-align: right;
            }

            /* 4. Compact the Product Cards */
            .products-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 1rem; /* Tighter gap */
                margin-bottom: 1rem;
            }

            .product-card {
                border-radius: 8px;
            }

            .product-image-wrapper {
                padding-top: 60%; /* Shallower aspect ratio to save height */
            }

            .product-info {
                padding: 0.75rem;
            }

            .product-name {
                font-size: 0.9rem;
                margin-bottom: 0.25rem;
                -webkit-line-clamp: 1; /* Force single line name */
            }

            .product-price {
                font-size: 1rem;
            }

            /* Hide the huge bottom CTA on the main fold to focus on products */
            .products-cta {
                margin-top: 1rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="nav-header">
        <div class="nav-logo">
            <?php
            // Try multiple logo locations - check theme directory first, then uploads
            $logo_paths = array(
                get_template_directory() . '/vbarms-black-logo_512.png',
                get_template_directory() . '/assets/vbarms-black-logo_512.png',
                get_template_directory() . '/logo.png',
                ABSPATH . 'wp-content/uploads/vbarms-black-logo_512.png',
                ABSPATH . 'wp-content/themes/' . get_template() . '/vbarms-black-logo_512.png',
                ABSPATH . 'wp-content/themes/' . get_template() . '/assets/vbarms-black-logo_512.png'
            );
            
            // Also try with the logos directory from the project
            $logos_dir = ABSPATH . 'wp-content/uploads/logos/';
            if (is_dir($logos_dir)) {
                $logo_paths[] = $logos_dir . 'vbarms-black-logo_512.png';
            }
            
            $logo_url = '';
            foreach ($logo_paths as $path) {
                if (file_exists($path)) {
                    // Convert absolute path to URL
                    $logo_url = str_replace(ABSPATH, home_url('/'), $path);
                    // Fix Windows paths
                    $logo_url = str_replace('\\', '/', $logo_url);
                    break;
                }
            }
            
            // If still not found, try using WordPress uploads URL directly
            if (!$logo_url) {
                $upload_dir = wp_upload_dir();
                $test_url = $upload_dir['baseurl'] . '/vbarms-black-logo_512.png';
                // Check if file actually exists before using URL
                $test_path = $upload_dir['basedir'] . '/vbarms-black-logo_512.png';
                if (file_exists($test_path)) {
                    $logo_url = $test_url;
                }
            }
            
            // Always show logo with company name
            if ($logo_url && @getimagesize($logo_url)) {
                echo '<a href="' . esc_url(home_url('/')) . '" style="display: flex; align-items: center; gap: 1rem; text-decoration: none;">';
                echo '<img src="' . esc_url($logo_url) . '" alt="VB Arms" style="height: 60px; width: auto; display: block;">';
                echo '<span class="nav-logo-text">VB ARMS</span>';
                echo '</a>';
            } else {
                // Fallback text logo - always visible
                echo '<a href="' . esc_url(home_url('/')) . '" style="display: flex; align-items: center; text-decoration: none; color: #ffffff; font-size: 1.5rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;">VB ARMS</a>';
            }
            ?>
        </div>
        <div class="nav-actions">
            <?php
            // Shopping cart icon - always show
            $cart_url = '';
            $cart_count = 0;
            
            if (function_exists('wc_get_cart_url') && function_exists('WC')) {
                $cart_url = wc_get_cart_url();
                if (WC()->cart) {
                    $cart_count = WC()->cart->get_cart_contents_count();
                }
            } else {
                $cart_url = home_url('/shop');
            }
            
            // Coming soon page
            $coming_soon_url = home_url('/coming-soon');
            ?>
            <a href="<?php echo esc_url($coming_soon_url); ?>" class="nav-cart">
                <div class="nav-cart-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                </div>
                <span>Cart</span>
                <?php if ($cart_count > 0) : ?>
                    <span class="nav-cart-count"><?php echo esc_html($cart_count); ?></span>
                <?php endif; ?>
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <a href="https://www.ffls.com/ffl/583021019a04518/benin-llc" target="_blank" rel="noopener noreferrer" class="hero-badge" style="text-decoration: none; color: inherit;">
                Licensed FFL Dealer • Cheyenne, Wyoming
            </a>
            
            <h1 class="hero-title">
                <span class="hero-title-line">Custom</span>
                <span class="hero-title-line">Firearms</span>
                <span class="hero-title-line">Procurement</span>
            </h1>
            
            <p class="hero-subtitle">
                Professional firearms acquisition services through our extensive distributor network. 
                We specialize in finding and procuring your ideal firearm, whether it's a rare collectible 
                or the latest release.
            </p>

            <div class="hero-cta">
                <a href="#custom-orders" class="btn btn-primary">
                    <span>Start Custom Order</span>
                    <span>→</span>
                </a>
                <a href="#services" class="btn btn-secondary">
                    Our Services
                </a>
            </div>
        </div>
    </section>

    <!-- Products Showcase Section -->
    <section class="products-section scroll-animate" id="products">
        <div class="products-container">
            <div class="products-header">
                <h2 class="products-title">Featured Firearms</h2>
                <p class="products-subtitle">
                    Browse our available inventory of premium firearms from trusted manufacturers
                </p>
            </div>

            <div class="products-grid">
                <?php
                if (function_exists('wc_get_products')) {
                    // Firearm manufacturers to prioritize
                    $firearm_manufacturers = array(
                        'Sig Sauer', 'Smith & Wesson', 'Tikka', 'Desert Eagle', 'Ruger', 
                        'Beretta', 'Springfield Armory', 'Bergara', 'Glock', 'CZ', 'FN', 
                        'HK', 'Colt', 'Remington', 'Winchester', 'Browning', 'Savage', 
                        'Mossberg', 'Benelli', 'Stoeger', 'Walther', 'Kimber', 
                        'Daniel Defense', 'Geissele', 'Larue', 'Noveske', 'BCM', 'Aero Precision'
                    );

                    // Categories to exclude (accessories)
                    $exclude_categories = array('Accessories', 'Magazines', 'Optics', 'Holsters', 'Parts', 'Triggers');

                    // Get products - prioritize FFL required and firearm manufacturers
                    $args = array(
                        'limit' => 200,
                        'status' => 'publish',
                        'stock_status' => 'instock',
                        'orderby' => 'date',
                        'order' => 'DESC',
                        'meta_query' => array(
                            'relation' => 'OR',
                            array(
                                'key' => '_ffl_required',
                                'value' => 'yes',
                                'compare' => '='
                            ),
                            array(
                                'key' => '_ffl_required',
                                'compare' => 'NOT EXISTS'
                            )
                        )
                    );

                    $products = wc_get_products($args);
                    
                    // Separate products into categories
                    $pistols = array();
                    $tikka_rifles = array();
                    $other_rifles = array();
                    
                    foreach ($products as $product) {
                        // Skip if no image
                        if (!$product->get_image_id()) continue;
                        
                        $product_name = strtolower($product->get_name());
                        $product_desc = strtolower($product->get_short_description());
                        $product_price = floatval($product->get_price());
                        
                        // Check if Tikka or Mossberg rifle (can be up to $1500)
                        $is_tikka = stripos($product_name, 'tikka') !== false;
                        $is_mossberg = stripos($product_name, 'mossberg') !== false;
                        $is_bottom_row_rifle = $is_tikka || $is_mossberg;
                        
                        // Skip if price is too high (pistols under $700, Tikkas/Mossbergs under $1500)
                        if ($is_bottom_row_rifle) {
                            if ($product_price >= 1500) continue;
                        } else {
                            if ($product_price >= 700) continue;
                        }
                        
                        // Get product categories
                        $categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names'));
                        $category_names = array_map('strtolower', $categories);
                        
                        // Skip if in excluded categories
                        $is_excluded = false;
                        foreach ($exclude_categories as $exclude) {
                            if (in_array(strtolower($exclude), $category_names)) {
                                $is_excluded = true;
                                break;
                            }
                        }
                        if ($is_excluded) continue;
                        
                        // Skip Kel-Tec and any non-Tikka rifles for bottom row
                        if (stripos($product_name, 'kel-tec') !== false || stripos($product_name, 'sub-2000') !== false) {
                            continue;
                        }
                        $has_scope = stripos($product_name, 'scope') !== false || 
                                     stripos($product_name, 'scoped') !== false ||
                                     stripos($product_desc, 'scope') !== false ||
                                     stripos($product_name, 'optic') !== false;
                        
                        // Check if pistol
                        $is_pistol = false;
                        $pistol_keywords = array('pistol', 'handgun', 'glock', 'sig', 'beretta', 'smith & wesson', 'cz', 'hk', 'walther', 'kimber', 'desert eagle');
                        foreach ($pistol_keywords as $keyword) {
                            if (stripos($product_name, $keyword) !== false) {
                                $is_pistol = true;
                                break;
                            }
                        }
                        
                        // Check categories
                        foreach ($category_names as $cat) {
                            if (stripos($cat, 'handgun') !== false || stripos($cat, 'pistol') !== false) {
                                $is_pistol = true;
                            }
                            if (stripos($cat, 'rifle') !== false && !$is_pistol) {
                                $is_pistol = false;
                            }
                        }
                        
                        // Check if Mossberg
                        $is_mossberg = stripos($product_name, 'mossberg') !== false;
                        $is_bottom_row_rifle = $is_tikka || $is_mossberg;
                        
                        // Categorize - Tikka or Mossberg rifles go in tikka_rifles array
                        if ($is_bottom_row_rifle) {
                            $tikka_rifles[] = array(
                                'product' => $product,
                                'has_scope' => $has_scope ? 1 : 0,
                                'is_tikka' => $is_tikka ? 1 : 0 // Prioritize Tikka over Mossberg
                            );
                        } elseif ($is_pistol) {
                            $pistols[] = $product;
                        }
                        // Don't add other rifles - we ONLY want Tikka/Mossberg for bottom row
                    }
                    
                    // Sort rifles - Tikka first, then scoped ones first
                    usort($tikka_rifles, function($a, $b) {
                        // First prioritize Tikka over Mossberg
                        if ($a['is_tikka'] !== $b['is_tikka']) {
                            return $b['is_tikka'] - $a['is_tikka'];
                        }
                        // Then prioritize scoped ones
                        return $b['has_scope'] - $a['has_scope'];
                    });
                    
                    // Build final selection: 3 pistols (Glock, Ruger, Other), 3 Tikkas
                    $final_products = array();
                    
                    // Step 1: Find a Glock
                    $glock_found = false;
                    foreach ($pistols as $pistol) {
                        if ($glock_found) break;
                        $product_name_lower = strtolower($pistol->get_name());
                        if (stripos($product_name_lower, 'glock') !== false) {
                            $final_products[] = $pistol;
                            $glock_found = true;
                        }
                    }
                    
                    // Step 2: Find a Ruger
                    $ruger_found = false;
                    foreach ($pistols as $pistol) {
                        if ($ruger_found) break;
                        // Skip if already added (Glock)
                        $already_added = false;
                        foreach ($final_products as $fp) {
                            if ($fp->get_id() === $pistol->get_id()) {
                                $already_added = true;
                                break;
                            }
                        }
                        if ($already_added) continue;
                        
                        $product_name_lower = strtolower($pistol->get_name());
                        if (stripos($product_name_lower, 'ruger') !== false) {
                            $final_products[] = $pistol;
                            $ruger_found = true;
                        }
                    }
                    
                    // Step 3: Find another manufacturer (not Glock or Ruger)
                    $other_found = false;
                    $other_manufacturers = array('sig', 'beretta', 'smith', 'cz', 'hk', 'walther', 'kimber', 'desert eagle', 'springfield', 'fn');
                    foreach ($pistols as $pistol) {
                        if ($other_found) break;
                        // Skip if already added
                        $already_added = false;
                        foreach ($final_products as $fp) {
                            if ($fp->get_id() === $pistol->get_id()) {
                                $already_added = true;
                                break;
                            }
                        }
                        if ($already_added) continue;
                        
                        $product_name_lower = strtolower($pistol->get_name());
                        // Skip Glock and Ruger
                        if (stripos($product_name_lower, 'glock') !== false || stripos($product_name_lower, 'ruger') !== false) {
                            continue;
                        }
                        
                        // Check if it's one of the other manufacturers
                        foreach ($other_manufacturers as $mfg) {
                            if (stripos($product_name_lower, $mfg) !== false) {
                                $final_products[] = $pistol;
                                $other_found = true;
                                break;
                            }
                        }
                    }
                    
                    // If we still don't have 3, add any remaining pistol (not Glock or Ruger)
                    $pistol_count = count($final_products);
                    if ($pistol_count < 3) {
                        foreach ($pistols as $pistol) {
                            if ($pistol_count >= 3) break;
                            // Skip if already added
                            $already_added = false;
                            foreach ($final_products as $fp) {
                                if ($fp->get_id() === $pistol->get_id()) {
                                    $already_added = true;
                                    break;
                                }
                            }
                            if ($already_added) continue;
                            
                            $product_name_lower = strtolower($pistol->get_name());
                            // Skip Glock and Ruger if we already have them
                            if ($glock_found && stripos($product_name_lower, 'glock') !== false) continue;
                            if ($ruger_found && stripos($product_name_lower, 'ruger') !== false) continue;
                            
                            $final_products[] = $pistol;
                            $pistol_count++;
                        }
                    }
                    
                    // If we don't have enough pistols, add more from the original products list
                    if (count($final_products) < 3) {
                        foreach ($products as $product) {
                            if (count($final_products) >= 3) break;
                            if (!$product->get_image_id()) continue;
                            $product_name_lower = strtolower($product->get_name());
                            // Skip if already added or is Kel-Tec
                            $already_added = false;
                            foreach ($final_products as $fp) {
                                if ($fp->get_id() === $product->get_id()) {
                                    $already_added = true;
                                    break;
                                }
                            }
                            if ($already_added || stripos($product_name_lower, 'kel-tec') !== false) continue;
                            // Add if it looks like a pistol
                            $pistol_keywords = array('pistol', 'handgun', 'glock', 'sig', 'beretta', 'smith', 'cz', 'hk', 'walther', 'kimber');
                            foreach ($pistol_keywords as $keyword) {
                                if (stripos($product_name_lower, $keyword) !== false) {
                                    $final_products[] = $product;
                                    break;
                                }
                            }
                        }
                    }
                    
                    // If we don't have enough Tikkas, try to find more Tikka rifles from all products
                    if (count($tikka_rifles) < 3) {
                        // Get more products to search for Tikkas (under $500)
                        $more_args = array(
                            'limit' => 200,
                            'status' => 'publish',
                            'stock_status' => 'instock',
                            'orderby' => 'date',
                            'order' => 'DESC',
                            'meta_query' => array(
                                array(
                                    'key' => '_price',
                                    'value' => 500,
                                    'compare' => '<',
                                    'type' => 'NUMERIC'
                                )
                            )
                        );
                        $more_products = wc_get_products($more_args);
                        foreach ($more_products as $product) {
                            if (count($final_products) >= 6) break;
                            if (!$product->get_image_id()) continue;
                            
                            $product_name_lower = strtolower($product->get_name());
                            $product_price = floatval($product->get_price());
                            
                            // Only add Tikka or Mossberg rifles (can be up to $1500)
                            $is_tikka_search = stripos($product_name_lower, 'tikka') !== false;
                            $is_mossberg_search = stripos($product_name_lower, 'mossberg') !== false;
                            if ($is_tikka_search || $is_mossberg_search) {
                                // Skip if price is $1500 or more
                                if ($product_price >= 1500) continue;
                                $already_added = false;
                                foreach ($final_products as $fp) {
                                    if ($fp->get_id() === $product->get_id()) {
                                        $already_added = true;
                                        break;
                                    }
                                }
                                if (!$already_added) {
                                    $has_scope = stripos($product_name_lower, 'scope') !== false || 
                                                 stripos($product_name_lower, 'scoped') !== false ||
                                                 stripos($product_name_lower, 'optic') !== false;
                                    $is_tikka_check = stripos($product_name_lower, 'tikka') !== false;
                                    $tikka_rifles[] = array(
                                        'product' => $product,
                                        'has_scope' => $has_scope ? 1 : 0,
                                        'is_tikka' => $is_tikka_check ? 1 : 0 // Prioritize Tikka over Mossberg
                                    );
                                }
                            }
                        }
                        // Re-sort rifles - Tikka first, then scoped ones first
                        usort($tikka_rifles, function($a, $b) {
                            // First prioritize Tikka over Mossberg
                            if ($a['is_tikka'] !== $b['is_tikka']) {
                                return $b['is_tikka'] - $a['is_tikka'];
                            }
                            // Then prioritize scoped ones
                            return $b['has_scope'] - $a['has_scope'];
                        });
                    }
                    
                    // Now add Tikka rifles to bottom row (positions 4, 5, 6)
                    $tikka_added = 0;
                    foreach ($tikka_rifles as $tikka_data) {
                        if ($tikka_added >= 3) break;
                        $already_in = false;
                        foreach ($final_products as $fp) {
                            if ($fp->get_id() === $tikka_data['product']->get_id()) {
                                $already_in = true;
                                break;
                            }
                        }
                        if (!$already_in) {
                            $final_products[] = $tikka_data['product'];
                            $tikka_added++;
                        }
                    }
                    
                    // DO NOT add any non-Tikka products to bottom row - only show what we have
                    
                    // Display exactly 6 products (or as many as we have)
                    $display_count = 0;
                    foreach ($final_products as $product) {
                        if ($display_count >= 6) break;
                        
                        // Double-check price (pistols under $700, Tikkas/Mossbergs under $1500)
                        $product_price_val = floatval($product->get_price());
                        $product_name_check = strtolower($product->get_name());
                        $is_rifle_check = stripos($product_name_check, 'tikka') !== false || stripos($product_name_check, 'mossberg') !== false;
                        if ($is_rifle_check) {
                            if ($product_price_val >= 1500) continue;
                        } else {
                            if ($product_price_val >= 700) continue;
                        }
                        
                        $image_id = $product->get_image_id();
                        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'woocommerce_single') : '';
                        
                        if (!$image_url) continue;
                        
                        $product_url = $product->get_permalink();
                        $product_name = $product->get_name();
                        $product_price = $product->get_price_html();
                        
                        ?>
                        <a href="<?php echo esc_url($product_url); ?>" class="product-card">
                            <div class="product-image-wrapper">
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($product_name); ?>">
                            </div>
                            <div class="product-info">
                                <div class="product-name"><?php echo esc_html($product_name); ?></div>
                                <?php if ($product_price) : ?>
                                    <div class="product-price"><?php echo $product_price; ?></div>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php
                        $display_count++;
                    }
                    
                    // If no products found, show message
                    if ($display_count === 0) {
                        ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: var(--text-secondary);">
                            <p>Products are being added to our inventory. Check back soon!</p>
                        </div>
                        <?php
                    } elseif ($display_count < 6) {
                        // Show message if we have some but not 6
                        ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: var(--text-secondary); font-size: 0.9rem;">
                            <p>Showing <?php echo $display_count; ?> featured products. More inventory being added.</p>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: var(--text-secondary);">
                        <p>WooCommerce is required to display products.</p>
                    </div>
                    <?php
                }
                ?>
            </div>

            <div class="products-cta">
                <a href="<?php echo esc_url(home_url('/coming-soon')); ?>" class="btn btn-secondary">
                    View All Products
                </a>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services-section scroll-animate" id="services">
        <div class="services-container">
            <div class="services-header">
                <h2 class="services-title">Professional FFL Services</h2>
                <p class="services-subtitle">
                    Comprehensive firearms services backed by industry expertise and distributor relationships
                </p>
            </div>

            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">🎯</div>
                    <h3 class="service-title">Custom Procurement</h3>
                    <p class="service-description">
                        We work directly with you to locate and acquire your ideal firearm through our network of distributors.
                    </p>
                    <ul class="service-features">
                        <li>Access to multiple distributor networks</li>
                        <li>Rare and hard-to-find firearms</li>
                        <li>New releases and pre-orders</li>
                        <li>Competitive pricing through volume relationships</li>
                        <li>White-glove service from order to delivery</li>
                    </ul>
                </div>

                <div class="service-card">
                    <div class="service-icon">📋</div>
                    <h3 class="service-title">FFL Transfer Services</h3>
                    <p class="service-description">
                        Complete FFL transfer services for firearms purchased online or from out-of-state dealers.
                    </p>
                    <ul class="service-features">
                        <li>Fast background check processing</li>
                        <li>Secure firearm storage during processing</li>
                        <li>Flexible pickup scheduling</li>
                        <li>Competitive transfer fees</li>
                        <li>Experienced with all transfer types</li>
                    </ul>
                </div>

                <div class="service-card">
                    <div class="service-icon">🛡️</div>
                    <h3 class="service-title">NFA & SOT Services</h3>
                    <p class="service-description">
                        Specialized services for NFA items including suppressors, SBRs, and Class 3 transfers.
                    </p>
                    <ul class="service-features">
                        <li>ATF Form 4 and Form 1 assistance</li>
                        <li>Suppressor sales and transfers</li>
                        <li>Trust and individual transfers</li>
                        <li>Expedited processing when possible</li>
                        <li>Complete NFA compliance guidance</li>
                    </ul>
                </div>
            </div>

            <div style="text-align: center;">
                <a href="<?php echo esc_url(home_url('/coming-soon')); ?>" class="btn btn-primary">
                    Request Custom Quote
                </a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section scroll-animate">
        <div class="stats-container">
            <div class="stat-card">
                <span class="stat-number">500+</span>
                <span class="stat-label">Distributor Network</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">24-72</span>
                <span class="stat-label">Hour Sourcing</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">100%</span>
                <span class="stat-label">Licensed & Compliant</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">7</span>
                <span class="stat-label">Years Experience</span>
            </div>
        </div>
    </section>

    <!-- Trust Section -->
    <section class="trust-section scroll-animate">
        <div class="trust-container">
            <h2 class="trust-title">Licensed & Trusted</h2>
            
            <div class="trust-badges">
                <div class="trust-badge">Federal Firearms License</div>
                <div class="trust-badge">Wyoming Licensed Dealer</div>
                <div class="trust-badge">Crypto Payments Accepted</div>
            </div>

            <div class="payment-methods">
                <?php
                // Get uploads directory
                $upload_dir = wp_upload_dir();
                $logos_base = $upload_dir['baseurl'] . '/logos/';
                $logos_path = $upload_dir['basedir'] . '/logos/';
                
                // Payment method logos
                $payment_methods = array(
                    array(
                        'name' => 'BTC',
                        'logo' => 'btc.png',
                        'class' => 'crypto'
                    ),
                    array(
                        'name' => 'ETH',
                        'logo' => 'eth.png',
                        'class' => 'crypto'
                    ),
                    array(
                        'name' => 'USDC',
                        'logo' => 'usdc-logo.png',
                        'class' => 'crypto'
                    ),
                    array(
                        'name' => 'FRNT',
                        'logo' => 'frnt.png',
                        'class' => 'crypto'
                    ),
                    array(
                        'name' => 'MTL',
                        'logo' => 'metal-mtl-logo.png',
                        'class' => 'crypto'
                    ),
                    array(
                        'name' => 'Traditional Payments',
                        'logo' => 'us-dollar-512.png',
                        'class' => ''
                    )
                );
                
                $coming_soon_url = home_url('/coming-soon');
                foreach ($payment_methods as $method) {
                    $logo_url = $logos_base . $method['logo'];
                    $logo_path = $logos_path . $method['logo'];
                    $class = 'payment-method' . ($method['class'] ? ' ' . $method['class'] : '');
                    
                    // Check if logo exists
                    if (file_exists($logo_path)) {
                        echo '<a href="' . esc_url($coming_soon_url) . '" class="' . esc_attr($class) . '" style="text-decoration: none; color: inherit;">';
                        echo '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($method['name']) . '" loading="lazy">';
                        echo '<span>' . esc_html($method['name']) . '</span>';
                        echo '</a>';
                    } else {
                        // Fallback to text only if logo doesn't exist
                        echo '<a href="' . esc_url($coming_soon_url) . '" class="' . esc_attr($class) . '" style="text-decoration: none; color: inherit;">' . esc_html($method['name']) . '</a>';
                    }
                }
                ?>
            </div>

            <p style="margin-top: 2rem; color: var(--text-muted); font-size: 0.9rem;">
                Firearm purchases processed through Zen Payments • All sales subject to federal and state laws
            </p>
        </div>
    </section>

    <?php
    $pg_privacy = get_page_by_path( 'privacy-policy', OBJECT, 'page' ) ?: get_page_by_path( 'privacy_policy', OBJECT, 'page' );
    $pg_refund  = get_page_by_path( 'refund_returns', OBJECT, 'page' ) ?: get_page_by_path( 'refund-returns', OBJECT, 'page' );
    $pg_terms   = get_page_by_path( 'terms-of-service', OBJECT, 'page' ) ?: get_page_by_path( 'terms_of_service', OBJECT, 'page' );
    $url_privacy = $pg_privacy ? get_permalink( $pg_privacy ) : home_url( '/privacy-policy/' );
    $url_refund  = $pg_refund  ? get_permalink( $pg_refund )  : home_url( '/refund_returns/' );
    $url_terms   = $pg_terms   ? get_permalink( $pg_terms )   : home_url( '/terms-of-service/' );
    ?>
    <footer style="padding: 24px 2rem 40px; text-align: center; border-top: 1px solid var(--border-subtle); color: var(--text-muted); font-size: 0.85rem;">
        <p style="margin-bottom: 10px;">© <?php echo date('Y'); ?> VB ARMS • Professional Firearms Procurement</p>
        <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem 1.5rem;">
            <a href="<?php echo esc_url( $url_privacy ); ?>" style="color: var(--text-muted); text-decoration: none;">Privacy Policy</a>
            <a href="<?php echo esc_url( $url_refund ); ?>" style="color: var(--text-muted); text-decoration: none;">Refund & Returns</a>
            <a href="<?php echo esc_url( $url_terms ); ?>" style="color: var(--text-muted); text-decoration: none;">Terms of Service</a>
        </div>
    </footer>

    <?php wp_footer(); ?>

    <script>
        // Scroll animations
        function initScrollAnimations() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.scroll-animate').forEach(el => {
                observer.observe(el);
            });
        }

        // Smooth scrolling for anchor links
        function initSmoothScroll() {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        e.preventDefault();
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        }

        // Navbar scroll effect
        function initNavbarScroll() {
            const navbar = document.querySelector('.nav-header');
            let lastScroll = 0;

            window.addEventListener('scroll', () => {
                const currentScroll = window.pageYOffset;
                
                if (currentScroll <= 0) {
                    navbar.style.transform = 'translateY(0)';
                    return;
                }
                
                if (currentScroll > lastScroll && currentScroll > 100) {
                    navbar.style.transform = 'translateY(-100%)';
                } else if (currentScroll < lastScroll) {
                    navbar.style.transform = 'translateY(0)';
                }
                
                lastScroll = currentScroll;
            });
        }

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            initScrollAnimations();
            initSmoothScroll();
            initNavbarScroll();
        });

        // Analytics tracking for custom orders
        document.querySelectorAll('a[href="#custom-orders"], .btn-primary').forEach(btn => {
            btn.addEventListener('click', function() {
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'custom_order_interest', {
                        'event_category': 'engagement',
                        'event_label': 'custom_order_cta'
                    });
                }
            });
        });
    </script>
</body>
</html>