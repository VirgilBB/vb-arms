<?php
/**
 * Template Name: VB Arms Terms of Service
 * Description: Terms of Use page with VB Arms layout and styling.
 */

show_admin_bar(false);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service | <?php bloginfo('name'); ?></title>
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
            <h1>Terms of Use</h1>
            <p class="subtitle">VB ARMS – Terms of Use</p>
            <p>By accessing or using the VB ARMS website, you agree to be bound by these Terms of Use ("TOU"). If you do not agree, please leave the website immediately.</p>

            <h2>1. Personal and Non-Commercial Use</h2>
            <p>The content on this website is for your personal use only. You may not copy, distribute, transmit, or sell any information, software, or products obtained from this website without express written permission from Benin, LLC.</p>

            <h2>2. No Unlawful or Prohibited Use</h2>
            <p>As a condition of your use, you warrant that you will not use this website for any purpose that is unlawful or prohibited by these terms. You may not:</p>
            <ul>
                <li>Attempt to gain unauthorized access to our data or computer systems.</li>
                <li>Use the site in any manner that could damage or disable our servers.</li>
                <li>Use the site to transmit unsolicited email (spam) or spoof your identity.</li>
            </ul>
            <p><strong>Monitoring:</strong> By using this site, you consent to monitoring. Evidence of criminal activity or violations of the TOU may be provided to law enforcement.</p>

            <h2>3. Firearm Sales and Compliance</h2>
            <p>All firearms sales must be conducted in accordance with Federal and State law. Firearms must be shipped to a licensed FFL dealer. It is the customer's responsibility to ensure that the firearm they are purchasing is legal in their jurisdiction.</p>

            <h2>4. Disclaimer of Warranties and Liability</h2>
            <p>The content on this website is provided "as is" without guarantees of any kind. VB ARMS makes no warranties that the website will be error-free or uninterrupted.</p>
            <p><strong>Inaccuracies:</strong> While we strive for accuracy, inaccuracies or omissions regarding pricing or descriptions may occur. VB ARMS reserves the right to correct errors and cancel orders if necessary.</p>
            <p><strong>Liability:</strong> In no event shall VB ARMS or Benin, LLC be liable for any special, indirect, or consequential damages arising out of the use or performance of this website.</p>

            <h2>5. Indemnification</h2>
            <p>You agree to defend and indemnify VB ARMS and its officers, employees, and agents against all claims and losses resulting from your violation of these Terms of Use.</p>

            <h2>6. Governing Law</h2>
            <p>These terms are governed by the laws of the State of Wyoming. Any legal action regarding these terms shall be filed in the courts located in Laramie County, Wyoming.</p>

            <h2>7. Changes to Terms</h2>
            <p>VB ARMS reserves the right to update these terms at any time without notice. The version posted on this page is the effective version.</p>

            <h2>Contact Information</h2>
            <p>VB ARMS<br>1607 Capitol Ave, Cheyenne, WY 82001<br>307-286-9128 | <a href="mailto:vb@vb-arms.com" style="color:var(--accent-green);">vb@vb-arms.com</a></p>
        </article>
    </div>

    <footer class="footer-legal">
        <p>© <?php echo date('Y'); ?> VB ARMS • Professional Firearms Procurement</p>
        <?php
    $pg_privacy = get_page_by_path( 'privacy-policy', OBJECT, 'page' ) ?: get_page_by_path( 'privacy_policy', OBJECT, 'page' );
    $pg_refund  = get_page_by_path( 'refund_returns', OBJECT, 'page' ) ?: get_page_by_path( 'refund-returns', OBJECT, 'page' );
    $url_privacy = $pg_privacy ? get_permalink( $pg_privacy ) : home_url( '/privacy-policy/' );
    $url_refund  = $pg_refund  ? get_permalink( $pg_refund )  : home_url( '/refund_returns/' );
    ?>
        <div class="footer-links">
            <a href="<?php echo esc_url( $url_privacy ); ?>" class="footer-link">Privacy Policy</a>
            <a href="<?php echo esc_url( $url_refund ); ?>" class="footer-link">Refund & Returns</a>
            <a href="<?php echo esc_url( get_permalink() ); ?>" class="footer-link">Terms of Service</a>
        </div>
    </footer>
    <?php wp_footer(); ?>
</body>
</html>
