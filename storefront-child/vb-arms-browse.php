<?php
/**
 * Template Name: VB Arms - Browse (Improved)
 * Description: Improved browse page with better hierarchy, modern typography, and enhanced UX
 */

show_admin_bar(false);

// Lipsey's catalog / dealer portal URL — update when you have your dealer login URL
$lipseys_catalog_url = 'https://www.lipseys.com';

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
            font-family: 'JetBrains Mono', monospace;
            line-height: 1.6;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* --- ANTI-WRAP HEADER (Universal Mobile) --- */
        .nav-header {
            position: fixed; top: 0; left: 0; right: 0; height: clamp(65px, 10vw, 75px);
            background: rgba(0, 0, 0, 0.95); backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--border-subtle); z-index: 1000;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 clamp(1rem, 5vw, 4rem); transition: transform 0.4s ease;
        }
        .nav-logo { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 1; min-width: 0; }
        .nav-logo a { display: flex; align-items: center; gap: 0.5rem; text-decoration: none; }
        .nav-logo img {
            height: clamp(35px, 8vw, 50px); width: auto; display: block; object-fit: contain; vertical-align: middle;
        }
        .nav-logo-text {
            font-size: clamp(0.9rem, 4vw, 1.3rem);
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            white-space: nowrap;
            line-height: 1;
            padding-top: 0.1em;
        }

        .nav-contact-btn {
            flex-shrink: 0;
            white-space: nowrap;
            background: rgba(0, 255, 148, 0.1);
            border: 1px solid rgba(0, 255, 148, 0.3);
            padding: 0.35rem 0;
            border-radius: 50px;
            color: var(--accent-green);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.75rem;
            transition: 0.3s;
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
            border: 1px solid rgba(0, 255, 148, 0.3);
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
        }
        
        /* Mobile Navigation Optimization */
        @media (max-width: 768px) {
            .nav-header {
                padding: 0 1rem;
                height: 60px;
            }
            .nav-logo {
                gap: 0.4rem;
            }
            .nav-logo img {
                height: 32px;
            }
            .nav-logo-text {
                font-size: 0.85rem;
                letter-spacing: 0.05em;
            }
            .nav-actions {
                gap: 0.4rem;
            }
            .nav-contact-btn {
                width: 85px;
                font-size: 0.65rem;
                padding: 0.3rem 0;
            }
        }
        
        @media (max-width: 480px) {
            .nav-header {
                padding: 0 0.75rem;
                height: 55px;
            }
            .nav-logo {
                gap: 0.3rem;
            }
            .nav-logo img {
                height: 28px;
            }
            .nav-logo-text {
                font-size: 0.75rem;
                letter-spacing: 0.03em;
            }
            .nav-actions {
                gap: 0.3rem;
            }
            .nav-contact-btn {
                width: 70px;
                font-size: 0.6rem;
                padding: 0.25rem 0;
                border-radius: 40px;
            }
        }
        
        @media (max-width: 380px) {
            .nav-logo-text {
                display: none; /* Hide "VB ARMS" text on very small screens */
            }
            .nav-contact-btn {
                width: 65px;
                font-size: 0.55rem;
            }
        }

        /* --- IMPROVED LAYOUT --- */
        .browse-container {
            max-width: 1200px;
            margin: 100px auto 60px;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1.3fr 0.8fr;
            gap: 6rem;
            align-items: start;
            flex: 1;
        }

        /* LEFT COLUMN - MAIN CONTENT */
        .browse-main {
            display: flex;
            flex-direction: column;
        }
        
        .browse-main h1 {
            font-size: clamp(2.8rem, 6vw, 4rem);
            line-height: 1.1;
            margin-bottom: 1.5rem;
            color: #fff;
            font-weight: 700;
        }
        
        .browse-description {
            font-size: 1.3rem;
            line-height: 1.5;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            text-align: justify;
        }
        
        .browse-tagline {
            color: var(--accent-green);
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 3rem;
            font-style: italic;
        }

        .browse-partners-section {
            margin-bottom: 3rem;
        }
        
        .browse-partners-label {
            font-family: 'JetBrains Mono';
            font-size: 0.9rem;
            text-transform: uppercase;
            color: var(--accent-green);
            letter-spacing: 2px;
            margin-bottom: 1rem;
            display: block;
        }
        
        .browse-partners {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }
        
        .browse-partners a {
            color: #fff;
            text-decoration: none;
            font-size: 1.2rem;
            font-weight: 500;
            transition: all 0.3s;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: 1px solid transparent;
        }
        
        .browse-partners a:first-child {
            padding-left: 0;
        }
        
        .browse-partners a:hover {
            color: var(--accent-green);
            background: rgba(0, 255, 148, 0.05);
            border-color: var(--border-accent);
            transform: translateY(-1px);
        }
        
        .browse-partners-sep {
            color: var(--text-secondary);
            font-weight: 300;
            opacity: 0.6;
        }

        /* CUSTOM QUOTE BUTTON - Pill style like nav buttons */
        .browse-cta {
            background: rgba(0, 255, 148, 0.1);
            border: 1px solid rgba(0, 255, 148, 0.3);
            padding: 0.5rem 2rem;
            border-radius: 50px;
            color: var(--accent-green);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: 0.3s;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 1px;
            align-self: flex-start;
            width: auto;
            margin-top: -50px;
        }
        
        .browse-cta:hover {
            background: var(--accent-green);
            color: #000;
            transform: translateY(-2px);
        }

        /* RIGHT COLUMN - CONTACT & REGISTRY */
        .browse-contact {
            background: var(--glass);
            border: 1px solid var(--border-subtle);
            padding: 2.5rem;
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            gap: 2.5rem;
        }

        .contact-section h2 {
            font-family: 'JetBrains Mono';
            font-size: 1rem;
            text-transform: uppercase;
            color: var(--accent-green);
            letter-spacing: 2px;
            margin-bottom: 1rem;
        }
        
        .contact-info {
            font-size: 1.4rem;
            line-height: 1.4;
            color: #fff;
        }
        
        .contact-info a {
            color: inherit;
            text-decoration: none;
            transition: 0.3s;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .contact-info a:hover {
            color: var(--accent-green);
        }

        .registry-section h2 {
            font-family: 'JetBrains Mono', monospace;
            font-size: 2rem;
            color: #fff;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        
        .registry-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        /* FFL Badge - matching homepage simple pill style */
        /* FFL Badge - Optimized to fit content (no stretch from flex parent) */
        .browse-ffl-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1.2rem;
            background: var(--glass);
            border: 1px solid var(--border-accent);
            border-radius: 50px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            color: var(--accent-green);
            text-decoration: none;
            transition: 0.3s;
            white-space: nowrap;
            width: fit-content;
            align-self: flex-start;
        }
        
        .browse-ffl-badge:hover {
            background: rgba(0, 255, 148, 0.05);
            transform: translateY(-1px);
        }
        .browse-ffl-badge .ffl-location { color: #fff; }

        /* Signature + footer */
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

        @media (max-width: 950px) {
            body {
                overflow-x: hidden; /* Prevent horizontal scroll */
            }
            
            .browse-container {
                grid-template-columns: 1fr;
                gap: 4rem;
                text-align: center; /* Center on mobile */
                margin: 90px auto 40px;
                padding: 0 1rem; /* Tighter padding on mobile */
                max-width: 100vw; /* Never exceed viewport */
                overflow-x: hidden;
            }
            
            .nav-header { 
                position: fixed; /* Keep nav fixed on mobile */
                padding: 0 1rem; 
            }
            
            .nav-contact-btn {
                width: 75px; /* Smaller buttons on tablet */
                font-size: 0.65rem;
                padding: 0.3rem 0;
            }
            
            .browse-main {
                align-items: center; /* Center content */
                max-width: 100%; /* Constrain width */
            }
            
            .browse-cta {
                align-self: center; /* Center button */
                margin-bottom: 1rem; /* Tight gap above contact */
            }
            
            .browse-contact {
                margin: 0 auto; /* Center the contact box */
                width: 100%; /* Full width */
                max-width: calc(100vw - 2rem); /* Never exceed viewport minus padding */
                padding: 1.5rem; /* Reduced padding on mobile */
                padding-top: 1rem; /* Less padding above contact on mobile */
                box-sizing: border-box; /* Include padding in width */
            }
            
            .contact-info {
                font-size: 1.1rem; /* Smaller font on mobile */
                word-break: break-word; /* Prevent long text overflow */
            }
            
            .browse-ffl-badge {
                align-self: center;
            }
            
            .registry-section h2 {
                font-size: 1.5rem; /* Smaller heading on mobile */
            }
        }

        @media (max-width: 600px) {
            .browse-container {
                padding: 0 0.75rem; /* Even tighter on small screens */
            }
            
            .browse-contact {
                max-width: calc(100vw - 1.5rem); /* Adjust for smaller padding */
                padding: 1.25rem; /* Even more compact */
                padding-top: 0.75rem; /* Tight gap below Custom Quote */
            }
            
            .browse-partners {
                flex-direction: column;
                align-items: center; /* Center partner links */
                gap: 1rem;
                justify-content: center;
            }
            
            .browse-partners a {
                font-size: 1.1rem;
                padding: 0.3rem 1rem;
                text-align: center;
            }
            
            .browse-partners-sep {
                display: none;
            }
            
            .browse-description {
                text-align: center; /* Center description on small screens */
            }
            
            .contact-info {
                font-size: 1rem; /* Even smaller on small screens */
            }
            
            .browse-ffl-badge {
                align-self: center;
            }
        }
        
        @media (max-width: 500px) {
            .nav-contact-btn {
                width: 65px; /* Even smaller buttons on small phones */
                font-size: 0.58rem;
                padding: 0.28rem 0;
            }
        }
        
        @media (max-width: 420px) {
            .nav-contact-btn {
                width: 60px; /* Ultra-compact for very small screens */
                font-size: 0.55rem;
                padding: 0.25rem 0;
            }
            .nav-actions {
                gap: 0.25rem; /* Minimal gap */
            }
        }
    </style>
</head>
<body>

    <nav class="nav-header" id="mainNav">
        <div class="nav-logo">
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <img src="<?php echo esc_url($logo_url); ?>" alt="VB Arms">
                <span class="nav-logo-text">VB ARMS</span>
            </a>
        </div>
        <div class="nav-actions">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="nav-contact-btn">Home</a>
            <a href="<?php echo esc_url(home_url('/browse/')); ?>" class="nav-contact-btn">Browse</a>
            <a href="<?php echo esc_url(home_url('/white-glove/')); ?>" class="nav-contact-btn primary-cta">White Glove</a>
        </div>
    </nav>

    <main class="browse-container">
        <!-- Left Column: Main Browse Content -->
        <section class="browse-main">
            <h1>Elite Network <br><span style="color:var(--accent-green);">Browse</span></h1>

            <p class="browse-description">
                We acquire firearms and accessories through Lipsey's, Zanders, and Davidson's. Browse our partner catalogs below to find your ideal setup, then return here to request a custom quote.
            </p>

            <p class="browse-tagline">We don't just acquire. We beat any comparable price.</p>

            <div class="browse-partners-section">
                <span class="browse-partners-label">Partner Catalogs</span>
                <div class="browse-partners">
                    <a href="<?php echo esc_url($lipseys_catalog_url); ?>" target="_blank" rel="noopener noreferrer">Lipsey's</a>
                    <span class="browse-partners-sep">·</span>
                    <a href="https://shop2.gzanders.com/" target="_blank" rel="noopener noreferrer">Zanders</a>
                    <span class="browse-partners-sep">·</span>
                    <a href="https://www.davidsonsinc.com/" target="_blank" rel="noopener noreferrer">Davidson's</a>
                </div>
            </div>

            <a href="<?php echo esc_url(home_url('/white-glove/')); ?>" class="browse-cta">Custom Quote</a>
        </section>

        <!-- Right Column: Contact & Registry -->
        <section class="browse-contact">
            <div class="contact-section">
                <h2>Contact</h2>
                <div class="contact-info">
                    <a href="tel:307-286-9128">307-286-9128</a>
                    <a href="mailto:vb@vb-arms.com">vb@vb-arms.com</a>
                </div>
            </div>

            <div class="registry-section">
                <h2>Procurement</h2>
                <h2><span style="color:var(--accent-green);">Registry</span></h2>
                <p class="registry-subtitle">Professional firearms acquisition services</p>
            </div>

            <a href="https://www.ffls.com/ffl/583021019a04518/benin-llc" target="_blank" rel="noopener noreferrer" class="browse-ffl-badge">
                Licensed FFL Dealer • <span class="ffl-location">Cheyenne, Wyoming</span>
            </a>
        </section>
    </main>

    <div class="signature-tagline">Your target. Our acquisition.</div>

    <footer style="padding: 15px 2rem 40px; text-align: center; border-top: 1px solid var(--border-subtle);">
        <p style="font-size: 0.8rem; margin-bottom: 8px; color: var(--text-secondary);">© <?php echo date('Y'); ?> VB ARMS • Bespoke Firearms Procurement</p>
        <div style="display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap;">
            <a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>" class="footer-link">Privacy Policy</a>
            <a href="<?php echo esc_url(home_url('/refund-returns/')); ?>" class="footer-link">Refund & Returns</a>
            <a href="<?php echo esc_url(home_url('/terms-of-service/')); ?>" class="footer-link">Terms of Service</a>
        </div>
    </footer>

    <?php wp_footer(); ?>
</body>
</html>