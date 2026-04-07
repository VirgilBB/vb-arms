<?php
/**
 * Template Name: VB Arms - Browse
 * Description: Browse page with VB Arms styling; CTA links to Lipsey's catalog.
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
            font-family: 'Space Grotesk', sans-serif;
            line-height: 1.6;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

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
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: clamp(0.5rem, 2vw, 1rem);
            flex-shrink: 1;
            min-width: 0;
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
            border: 1px solid var(--accent-green);
            padding: 0.35rem 3rem;
            border-radius: 50px;
            color: var(--accent-green);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.75rem;
            transition: 0.3s;
        }
        .nav-contact-btn:hover {
            background: var(--accent-green);
            color: #000;
            transform: translateY(-2px);
        }
        .nav-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        /* Container — push content down, centered like White Glove */
        .browse-container {
            max-width: 1100px;
            width: 100%;
            margin: 0 auto 40px;
            padding: 140px 2rem 3rem;
            flex: 1;
            box-sizing: border-box;
        }
        /* Content box — visible panel so it clearly looks like a container */
        .browse-content-box {
            background: var(--secondary-bg);
            border: 1px solid var(--border-accent);
            border-radius: 16px;
            padding: clamp(2rem, 5vw, 3rem);
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.4);
        }
        .browse-title {
            color: #00ff94 !important;
            font-size: clamp(2rem, 5vw, 2.75rem);
            margin-bottom: 1.5rem;
            font-weight: 700;
        }
        .browse-text {
            color: #ffffff;
            font-size: clamp(1.1rem, 2.5vw, 1.25rem);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        .browse-cta-desc {
            color: #ffffff;
            font-size: clamp(1rem, 2.2vw, 1.1rem);
            line-height: 1.5;
            margin-bottom: 2rem;
        }
        /* Request White Glove Quote — same thin pill as nav (see-through, green border) */
        .browse-cta {
            display: inline-block;
            background: rgba(0, 255, 148, 0.1);
            border: 1px solid #00ff94;
            padding: 0.35rem 2rem;
            border-radius: 50px;
            color: #00ff94;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.75rem;
            transition: 0.3s;
            margin-top: 1.5rem;
        }
        .browse-cta:hover {
            background: #00ff94;
            color: #000;
            transform: translateY(-2px);
        }
        .browse-catalogs {
            margin-top: 2rem;
        }
        .browse-partners-label {
            color: var(--text-secondary);
            font-size: clamp(1rem, 2.2vw, 1.1rem);
            margin: 0 0 0.5rem;
        }
        .browse-partners {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        /* Partner catalog links — between green and white; hover = lime green */
        .browse-partners a {
            color: rgba(184, 255, 220, 0.95);
            text-decoration: none;
            font-size: clamp(1rem, 2.2vw, 1.1rem);
            font-weight: 500;
            transition: color 0.2s;
        }
        .browse-partners a:hover {
            color: #00ff94;
            text-decoration: underline;
        }
        .browse-partners-sep {
            color: var(--text-secondary);
            font-weight: 300;
            opacity: 0.6;
        }
        .browse-tagline {
            color: var(--accent-green);
            font-size: clamp(1.1rem, 2.5vw, 1.2rem);
            font-weight: 600;
            margin: 1.5rem 0 0;
        }

        /* Signature + footer — exact copy from homepage */
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

        @media (max-width: 850px) {
            .browse-container {
                padding: 120px 1rem 2rem;
            }
        }
    </style>
</head>
<body>

    <nav class="nav-header" id="mainNav">
        <div class="nav-logo">
            <a href="<?php echo esc_url(home_url('/')); ?>" style="display: flex; align-items: center; gap: 0.5rem; text-decoration: none;">
                <img src="<?php echo esc_url($logo_url); ?>" alt="VB Arms">
                <span class="nav-logo-text">VB ARMS</span>
            </a>
        </div>
        <div class="nav-actions">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="nav-contact-btn">HOME</a>
            <a href="<?php echo esc_url(home_url('/white-glove/')); ?>" class="nav-contact-btn">White Glove</a>
        </div>
    </nav>

    <main class="browse-container">
        <div class="browse-content-box">
            <h1 class="browse-title" style="color: #00ff94;">Direct access to the industry's elite networks.</h1>
            <p class="browse-text">We acquire firearms and accessories through Lipsey's, Zanders, and Davidson's. Browse our partner catalogs below to find your ideal setup, then return here to request a White Glove quote.</p>
            <p class="browse-tagline">We don't just acquire. We beat any comparable price.</p>
            <div class="browse-catalogs">
                <p class="browse-partners-label">Partner catalogs:</p>
                <div class="browse-partners">
                    <a href="<?php echo esc_url($lipseys_catalog_url); ?>" target="_blank" rel="noopener noreferrer">Lipsey's</a>
                    <span class="browse-partners-sep">·</span>
                    <a href="https://shop2.gzanders.com/" target="_blank" rel="noopener noreferrer">Zanders</a>
                    <span class="browse-partners-sep">·</span>
                    <a href="https://www.davidsonsinc.com/" target="_blank" rel="noopener noreferrer">Davidson's</a>
                </div>
                <a href="<?php echo esc_url(home_url('/white-glove/')); ?>" class="browse-cta">Request White Glove Quote →</a>
            </div>
        </div>
    </main>

    <div class="signature-tagline">Your target. Our acquisition.</div>

    <footer style="padding: 15px 2rem 40px; text-align: center; border-top: 1px solid var(--border-subtle);">
        <p style="font-size: 0.8rem; margin-bottom: 8px; color: var(--text-secondary);">© <?php echo date('Y'); ?> VB ARMS • Bespoke Firearms Procurement</p>
        <div style="display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap;">
            <a href="<?php echo esc_url(home_url('/browse/')); ?>" class="footer-link">Browse</a>
            <a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>" class="footer-link">Privacy Policy</a>
            <a href="<?php echo esc_url(home_url('/refund-returns/')); ?>" class="footer-link">Refund & Returns</a>
            <a href="<?php echo esc_url(home_url('/terms-of-service/')); ?>" class="footer-link">Terms of Service</a>
        </div>
    </footer>

    <?php wp_footer(); ?>
</body>
</html>
