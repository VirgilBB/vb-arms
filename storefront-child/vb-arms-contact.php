<?php
/**
 * Template Name: Contact
 * Description: The high-end intake form for VB Arms procurement requests. Used for Contact page.
 */

show_admin_bar(false);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Registry | <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-bg: #0a0a0a; --secondary-bg: #111111; --accent-green: #00ff94;
            --text-primary: #ffffff; --text-secondary: #b8b8b8; --border-subtle: rgba(255, 255, 255, 0.08);
            --border-accent: rgba(0, 255, 148, 0.3); --glass: rgba(255, 255, 255, 0.03);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: var(--primary-bg); color: var(--text-primary); font-family: 'Space Grotesk', sans-serif; line-height: 1.6; overflow-x: hidden; }

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
        .nav-logo-text { font-size: clamp(0.9rem, 4vw, 1.3rem); font-weight: 700; color: #fff; letter-spacing: 0.1em; text-transform: uppercase; white-space: nowrap; line-height: 1; padding-top: 0.1em; }

        .back-home { color: var(--text-secondary); text-decoration: none; font-size: 0.8rem; font-family: 'JetBrains Mono'; transition: 0.3s; flex-shrink: 0; }
        .back-home:hover { color: var(--accent-green); }

        /* --- LAYOUT --- */
        .intake-container {
            max-width: 1100px; margin: 100px auto 40px; padding: 0 2rem;
            display: grid; grid-template-columns: 0.8fr 1.2fr; gap: 4rem; align-items: start;
        }

        /* Left Side: Contact Info */
        .concierge-info h1 { font-size: clamp(2.2rem, 5vw, 3rem); line-height: 1.1; margin-bottom: 2rem; color: #fff; }
        .info-block { margin-bottom: 2rem; }
        .info-label { font-family: 'JetBrains Mono'; font-size: 0.7rem; text-transform: uppercase; color: var(--accent-green); letter-spacing: 2px; margin-bottom: 0.4rem; display: block; }
        .info-value { font-size: 1rem; color: var(--text-secondary); }
        .info-value a { color: inherit; text-decoration: none; transition: 0.3s; }
        .info-value a:hover { color: #fff; }

        /* Right Side: Form */
        .intake-registry { background: var(--glass); border: 1px solid var(--border-subtle); padding: clamp(1.5rem, 5vw, 2.5rem); border-radius: 16px; }
        .registry-form { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; }
        .full-width { grid-column: span 2; }

        .form-group label { font-family: 'JetBrains Mono'; font-size: 0.65rem; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 0.4rem; display: block; }
        .form-input {
            background: rgba(255,255,255,0.05); border: 1px solid var(--border-subtle);
            padding: 0.9rem; border-radius: 8px; color: #fff; font-family: inherit; font-size: 0.9rem; transition: 0.3s; width: 100%;
        }
        .form-input:focus { outline: none; border-color: var(--accent-green); background: rgba(255,255,255,0.08); }

        .submit-btn {
            grid-column: span 2; background: var(--accent-green); color: #000; border: none; padding: 1.1rem;
            border-radius: 8px; font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: 0.3s;
            text-transform: uppercase; letter-spacing: 1px; margin-top: 0.5rem;
        }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0, 255, 148, 0.2); }

        /* Signature */
        .signature { text-align: center; font-size: 1.3rem; font-style: italic; color: var(--accent-green); margin: 3rem 0; font-weight: 300; opacity: 0.9; }

        .footer-legal { padding: 20px 2rem 40px; text-align: center; border-top: 1px solid var(--border-subtle); }
        .footer-legal p { font-size: 0.85rem; margin-bottom: 12px; color: var(--text-secondary); }
        .footer-links { display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem 1.5rem; }
        .footer-link { color: rgba(0, 255, 148, 0.75); text-decoration: none; font-size: 0.75rem; transition: 0.2s; }
        .footer-link:hover { color: var(--accent-green); }

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
            .back-home {
                font-size: 0.65rem;
            }
        }
        
        @media (max-width: 500px) {
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
            .back-home {
                font-size: 0.58rem;
            }
        }
        
        @media (max-width: 420px) {
            .nav-logo-text {
                display: none; /* Hide "VB ARMS" text on very small screens */
            }
            .back-home {
                font-size: 0.55rem;
            }
        }
        
        @media (max-width: 850px) {
            .intake-container { grid-template-columns: 1fr; gap: 3rem; text-align: center; }
            .registry-form { grid-template-columns: 1fr; }
            .full-width, .submit-btn { grid-column: span 1; }
        }
    </style>
</head>
<body>
    <?php
    $logo_paths = array(
        get_stylesheet_directory() . '/vbarms-black-logo_512.png',
        get_template_directory() . '/vbarms-black-logo_512.png',
        ABSPATH . 'wp-content/uploads/vbarms-black-logo_512.png',
        ABSPATH . 'wp-content/uploads/logos/vbarms-black-logo_512.png'
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
        $ud = wp_upload_dir();
        if (file_exists($ud['basedir'] . '/vbarms-black-logo_512.png')) {
            $logo_url = $ud['baseurl'] . '/vbarms-black-logo_512.png';
        }
    }
    ?>
    <nav class="nav-header">
        <div class="nav-logo">
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <?php if ($logo_url) { echo '<img src="' . esc_url($logo_url) . '" alt="VB Arms">'; } ?>
                <span class="nav-logo-text">VB ARMS</span>
            </a>
        </div>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="back-home">← HOME</a>
    </nav>

    <main class="intake-container">
        <section class="concierge-info">
            <span class="info-label">White Glove Service</span>
            <h1>Procurement <br><span style="color:var(--accent-green);">Registry</span></h1>

            <div class="info-block">
                <span class="info-label">Address</span>
                <p class="info-value">1607 Capitol Ave<br>Cheyenne, WY 82001</p>
            </div>

            <div class="info-block">
                <span class="info-label">Intelligence</span>
                <p class="info-value"><a href="tel:307-286-9128">307-286-9128</a></p>
                <p class="info-value"><a href="mailto:vb@vb-arms.com">vb@vb-arms.com</a></p>
            </div>

            <a href="https://www.ffls.com/ffl/583021019a04518/benin-llc" target="_blank" rel="noopener noreferrer" style="text-decoration:none;">
                <div class="info-block" style="border: 1px solid var(--border-accent); padding: 10px; border-radius: 8px; background: rgba(0,255,148,0.02);">
                    <span class="info-label" style="margin-bottom:0;">Licensed FFL Dealer</span>
                    <p class="info-value" style="font-size:0.75rem;">Verified Status • Cheyenne, WY</p>
                </div>
            </a>
        </section>

        <section class="intake-registry">
            <form class="registry-form" action="#" method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-input" placeholder="Client Identity" required>
                </div>
                <div class="form-group">
                    <label>Preferred Contact</label>
                    <input type="text" name="contact_preference" class="form-input" placeholder="Phone or Email" required>
                </div>
                <div class="form-group full-width">
                    <label>Acquisition Target</label>
                    <input type="text" name="acquisition_target" class="form-input" placeholder="Make, Model, or Caliber">
                </div>
                <div class="form-group">
                    <label>Request Type</label>
                    <select name="request_type" class="form-input" style="background: #1a1a1a; color: #fff;">
                        <option>Integrated Custom Package</option>
                        <option>Single Firearm Acquisition</option>
                        <option>Best-Rate Quote Match</option>
                        <option>FFL Transfer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Timeline</label>
                    <select name="timeline" class="form-input" style="background: #1a1a1a; color: #fff;">
                        <option>Immediate Procurement</option>
                        <option>30-Day Window</option>
                        <option>Future Build Consultation</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label>Build Specifications / Competitive Quotes</label>
                    <textarea name="specifications" class="form-input" rows="4" placeholder="List optics, accessory requirements, or paste details of a competitor's quote to be outperformed..."></textarea>
                </div>
                <button type="submit" class="submit-btn">File Procurement Request</button>
            </form>
        </section>
    </main>

    <div class="signature">Your target. Our acquisition.</div>

    <footer class="footer-legal">
        <p>© 2026 VB ARMS • Professional Firearms Procurement</p>
        <div class="footer-links">
            <?php
            $pg_privacy = get_page_by_path( 'privacy-policy', OBJECT, 'page' ) ?: get_page_by_path( 'privacy_policy', OBJECT, 'page' );
            $pg_refund  = get_page_by_path( 'refund_returns', OBJECT, 'page' ) ?: get_page_by_path( 'refund-returns', OBJECT, 'page' );
            $pg_terms   = get_page_by_path( 'terms-of-service', OBJECT, 'page' ) ?: get_page_by_path( 'terms_of_service', OBJECT, 'page' );
            $url_privacy = $pg_privacy ? get_permalink( $pg_privacy ) : home_url( '/privacy-policy/' );
            $url_refund  = $pg_refund  ? get_permalink( $pg_refund )  : home_url( '/refund-returns/' );
            $url_terms   = $pg_terms   ? get_permalink( $pg_terms )   : home_url( '/terms-of-service/' );
            ?>
            <a href="<?php echo esc_url( $url_privacy ); ?>" class="footer-link">Privacy Policy</a>
            <a href="<?php echo esc_url( $url_refund ); ?>" class="footer-link">Refund & Returns</a>
            <a href="<?php echo esc_url( $url_terms ); ?>" class="footer-link">Terms of Service</a>
        </div>
    </footer>

    <?php wp_footer(); ?>
</body>
</html>
