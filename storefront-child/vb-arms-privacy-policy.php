<?php
/**
 * Template Name: VB Arms Privacy Policy
 * Description: Privacy Policy page with VB Arms layout and styling.
 */

show_admin_bar(false);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy | <?php bloginfo('name'); ?></title>
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
            <h1>Privacy Policy</h1>
            <p class="subtitle">VB ARMS – Privacy Statement</p>
            <p>This Privacy Statement is provided by Benin, LLC dba VB ARMS ("VB ARMS," "we," or "us"). This Statement governs the manner in which VB ARMS collects and processes Personal Data about individuals who use our products, services, or website (vbarms.com).</p>

            <h2>1. What Personal Information We Collect</h2>
            <p>We collect personal information (PII) that can be used to identify you. This includes:</p>
            <ul>
                <li><strong>Identifying Information:</strong> Name, email address, phone number, and physical shipping/billing address.</li>
                <li><strong>Compliance Documentation:</strong> Government-issued identification and FFL information required for firearm transfers.</li>
                <li><strong>Commercial Information:</strong> Order history, products purchased, and payment method details (processed via secure, encrypted third-party gateways).</li>
                <li><strong>Technical Data:</strong> IP address, browser type, and website activity via cookies or analytics tools.</li>
            </ul>

            <h2>2. How We Use Your Information</h2>
            <p>VB ARMS uses your data to:</p>
            <ul>
                <li>Process and fulfill sales orders and support requests.</li>
                <li>Maintain accurate "Bound Book" records as required by the ATF.</li>
                <li>Enforce our policies and Terms and Conditions.</li>
                <li>Communicate marketing and sales promotions (where you have opted in).</li>
                <li>Comply with the Wyoming Second Amendment Financial Privacy Act by ensuring your financial data is not shared for the purpose of surveilling lawful firearms purchases.</li>
            </ul>

            <h2>3. Sharing of Information with Third Parties</h2>
            <p>We do not sell, rent, or distribute your personal information for resale. We may share information with:</p>
            <ul>
                <li><strong>Service Providers:</strong> Consultants, shipping carriers (UPS/FedEx), and payment processors necessary to fulfill your order.</li>
                <li><strong>Legal Compliance:</strong> We may release information to comply with any applicable law, respond to a court order, subpoena, or ATF inspection.</li>
                <li><strong>Analytics:</strong> We may use tools like Microsoft Clarity or Google Analytics to capture how you interact with our website through behavioral metrics and heatmaps to improve our services.</li>
            </ul>

            <h2>4. SMS and 10DLC Compliance</h2>
            <p>By providing your phone number and signing up for text communications, you consent to receive transactional and promotional messages from VB ARMS.</p>
            <ul>
                <li><strong>Opt-Out:</strong> You may reply "STOP" or "UNSUBSCRIBE" to any message to be removed from our list.</li>
                <li><strong>Rates:</strong> Message and data rates may apply.</li>
                <li><strong>Security:</strong> We do not share your SMS consent or phone numbers with third parties for their marketing purposes.</li>
            </ul>

            <h2>5. Children's Privacy</h2>
            <p>Our services are not intended for children under the age of 13. We do not knowingly collect personal information from children.</p>

            <h2>6. Protection of Your Data</h2>
            <p>VB ARMS adopts appropriate technical and organizational security measures to protect against unauthorized access or disclosure. However, transmission over the internet is never 100% secure; use of our site is at your own risk.</p>

            <h2>7. Contact Us</h2>
            <p>If you have questions regarding this policy, please contact:</p>
            <p><strong>VB ARMS</strong><br>1607 Capitol Ave, Cheyenne, WY 82001<br>Phone: 307-286-9128<br>Email: <a href="mailto:vb@vb-arms.com" style="color:var(--accent-green);">vb@vb-arms.com</a></p>
        </article>
    </div>

    <footer class="footer-legal">
        <p>© <?php echo date('Y'); ?> VB ARMS • Professional Firearms Procurement</p>
        <?php
    $pg_refund = get_page_by_path( 'refund_returns', OBJECT, 'page' ) ?: get_page_by_path( 'refund-returns', OBJECT, 'page' );
    $pg_terms  = get_page_by_path( 'terms-of-service', OBJECT, 'page' ) ?: get_page_by_path( 'terms_of_service', OBJECT, 'page' );
    $url_refund = $pg_refund ? get_permalink( $pg_refund ) : home_url( '/refund_returns/' );
    $url_terms  = $pg_terms  ? get_permalink( $pg_terms )  : home_url( '/terms-of-service/' );
    ?>
        <div class="footer-links">
            <a href="<?php echo esc_url( get_permalink() ); ?>" class="footer-link">Privacy Policy</a>
            <a href="<?php echo esc_url( $url_refund ); ?>" class="footer-link">Refund & Returns</a>
            <a href="<?php echo esc_url( $url_terms ); ?>" class="footer-link">Terms of Service</a>
        </div>
    </footer>
    <?php wp_footer(); ?>
</body>
</html>
