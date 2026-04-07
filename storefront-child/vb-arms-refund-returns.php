<?php
/**
 * Template Name: VB Arms Refund & Returns
 * Description: Refund and Returns Policy page with VB Arms layout and styling.
 */

show_admin_bar(false);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund & Returns | <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-bg: #0a0a0a; --secondary-bg: #141414; --text-primary: #ffffff; --text-secondary: #b8b8b8;
            --accent-green: #00ff94; --border-subtle: rgba(255,255,255,0.08); --border-accent: rgba(0,255,148,0.3);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: var(--primary-bg); color: var(--text-primary); font-family: 'Space Grotesk', sans-serif; line-height: 1.6; overflow-x: hidden; }
        /* --- UNIVERSAL MOBILE HEADER (Android & iPhone) --- */
        .nav-header {
            position: fixed; top: 0; left: 0; right: 0; height: clamp(65px, 10vw, 75px);
            background: rgba(0,0,0,0.95); backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--border-subtle); z-index: 1000;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 clamp(1rem, 5vw, 4rem); transition: transform 0.4s ease;
        }
        .nav-logo { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 1; min-width: 0; }
        .nav-logo img { height: clamp(35px, 8vw, 50px); width: auto; }
        .nav-logo-text { font-size: clamp(0.9rem, 4vw, 1.3rem); font-weight: 700; color: #fff; letter-spacing: 0.1em; text-transform: uppercase; white-space: nowrap; }
        .nav-contact-btn {
            flex-shrink: 0; white-space: nowrap;
            background: rgba(0,255,148,0.1); border: 1px solid rgba(0, 255, 148, 0.3);
            padding: 0.5rem clamp(0.6rem, 3vw, 1.4rem); border-radius: 8px;
            color: var(--accent-green); text-decoration: none; font-weight: 600;
            font-size: clamp(0.7rem, 3vw, 0.85rem); transition: 0.3s;
        }
        .nav-contact-btn:hover { background: var(--accent-green); color: #000; transform: translateY(-2px); }
        .legal-wrap { max-width: 780px; margin: 0 auto; padding: 100px 2rem 4rem; }
        .legal-page h1 { font-size: 2rem; margin-bottom: 0.5rem; color: #fff; }
        .legal-page .subtitle { color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid var(--border-subtle); }
        .legal-page h2 { font-size: 1.2rem; color: var(--accent-green); margin-top: 2rem; margin-bottom: 0.75rem; }
        .legal-page p { color: var(--text-secondary); margin-bottom: 1rem; font-size: 0.95rem; }
        .legal-page ul { margin: 0 0 1rem 1.25rem; color: var(--text-secondary); font-size: 0.95rem; }
        .legal-page li { margin-bottom: 0.35rem; }
        .legal-page strong { color: #fff; }
        .footer-legal { padding: 24px 2rem 40px; text-align: center; border-top: 1px solid var(--border-subtle); }
        .footer-legal p { font-size: 0.85rem; margin-bottom: 0; color: var(--text-secondary); }
        .footer-links { display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem 1.5rem; margin-top: 12px; }
        .footer-link { color: rgba(0, 255, 148, 0.75); text-decoration: none; font-size: 0.8rem; transition: 0.2s; }
        .footer-link:hover { color: var(--accent-green); }
    </style>
</head>
<body>
    <?php
    $logo_paths = array( get_template_directory() . '/vbarms-black-logo_512.png', get_template_directory() . '/assets/vbarms-black-logo_512.png', ABSPATH . 'wp-content/uploads/vbarms-black-logo_512.png', ABSPATH . 'wp-content/uploads/logos/vbarms-black-logo_512.png' );
    $logo_url = '';
    foreach ($logo_paths as $path) {
        if (file_exists($path)) { $logo_url = str_replace(ABSPATH, home_url('/'), $path); $logo_url = str_replace('\\', '/', $logo_url); break; }
    }
    if (!$logo_url) { $ud = wp_upload_dir(); if (file_exists($ud['basedir'] . '/vbarms-black-logo_512.png')) { $logo_url = $ud['baseurl'] . '/vbarms-black-logo_512.png'; } }
    ?>
    <nav class="nav-header">
        <div class="nav-logo">
            <a href="<?php echo esc_url(home_url('/')); ?>" style="display:flex;align-items:center;gap:1rem;text-decoration:none;">
                <?php if ($logo_url) { echo '<img src="' . esc_url($logo_url) . '" alt="VB Arms">'; } ?>
                <span class="nav-logo-text">VB ARMS</span>
            </a>
        </div>
    </nav>

    <div class="legal-wrap">
        <article class="legal-page">
            <h1>Refund and Returns Policy</h1>
            <p class="subtitle">VB ARMS – Refund and Returns Policy</p>
            <p>At VB ARMS, we value your business and want you to be satisfied with your purchase. Because we deal in regulated goods, we adhere to a strict return policy to remain compliant with federal and state law.</p>

            <h2>1. Firearms Returns</h2>
            <ul>
                <li><strong>Mandatory Inspection:</strong> Please inspect your firearm thoroughly at the time of pickup BEFORE completing the FFL transfer (signing Form 4473).</li>
                <li><strong>Final Sale After Transfer:</strong> Once a firearm is transferred into your name, it is legally considered "used." We cannot accept returns on firearms once the transfer is complete.</li>
                <li><strong>Manufacturing Defects:</strong> If a firearm is found to be defective after the transfer, it must be returned directly to the manufacturer for warranty repair or replacement.</li>
            </ul>

            <h2>2. Non-Firearm Returns (Accessories, Optics, etc.)</h2>
            <ul>
                <li><strong>Return Window:</strong> You must request a Return Merchandise Authorization (RMA) within 10 business days of receiving your product.</li>
                <li><strong>Restocking Fees:</strong> Unopened items are subject to a 25% restocking fee. Opened or "Open Box" items are subject to a 40% restocking fee.</li>
                <li><strong>Non-Returnable Items:</strong> We do not accept returns on ammunition, primers, black powder, special orders, or items that have been mounted/installed.</li>
            </ul>

            <h2>3. Damaged Shipments</h2>
            <p>Damaged or missing items must be reported within 3 business days. Please keep all original packaging for carrier inspection.</p>

            <h2>Contact Us</h2>
            <p>Phone: 307-286-9128 | Email: <a href="mailto:vb@vb-arms.com" style="color:var(--accent-green);">vb@vb-arms.com</a><br>VB ARMS, 1607 Capitol Ave, Cheyenne, WY 82001</p>
        </article>
    </div>

    <footer class="footer-legal">
        <p>© <?php echo date('Y'); ?> VB ARMS • Professional Firearms Procurement</p>
        <?php
    $pg_privacy = get_page_by_path( 'privacy-policy', OBJECT, 'page' ) ?: get_page_by_path( 'privacy_policy', OBJECT, 'page' );
    $pg_terms   = get_page_by_path( 'terms-of-service', OBJECT, 'page' ) ?: get_page_by_path( 'terms_of_service', OBJECT, 'page' );
    $url_privacy = $pg_privacy ? get_permalink( $pg_privacy ) : home_url( '/privacy-policy/' );
    $url_terms   = $pg_terms   ? get_permalink( $pg_terms )   : home_url( '/terms-of-service/' );
    ?>
        <div class="footer-links">
            <a href="<?php echo esc_url( $url_privacy ); ?>" class="footer-link">Privacy Policy</a>
            <a href="<?php echo esc_url( get_permalink() ); ?>" class="footer-link">Refund & Returns</a>
            <a href="<?php echo esc_url( $url_terms ); ?>" class="footer-link">Terms of Service</a>
        </div>
    </footer>
    <?php wp_footer(); ?>
</body>
</html>
