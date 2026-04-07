<?php
/**
 * Template Name: White-Glove
 * Description: Combined white-glove service and partner catalog browsing
 */

show_admin_bar(false);

// Shop URL: always shop listing (never a product page)
$white_glove_shop_url = apply_filters('vb_arms_breadcrumb_shop_url', home_url('/shop/'));
$white_glove_shop_url = (empty($white_glove_shop_url) || trim($white_glove_shop_url) === '') ? home_url('/shop/') : $white_glove_shop_url;

// Handle form submission
$form_submitted = false;
$form_error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['procurement_request'])) {
    $full_name = sanitize_text_field($_POST['full_name'] ?? '');
    $contact = sanitize_text_field($_POST['contact_preference'] ?? '');
    $target = sanitize_text_field($_POST['acquisition_target'] ?? '');
    $request_type = sanitize_text_field($_POST['request_type'] ?? '');
    $timeline = sanitize_text_field($_POST['timeline'] ?? '');
    $specs = sanitize_textarea_field($_POST['specifications'] ?? '');
    
    $to = 'vb@vb-arms.com';
    $subject = 'New Procurement Request from ' . $full_name;
    $message = "New Procurement Request\n\n";
    $message .= "Full Name: $full_name\n";
    $message .= "Contact: $contact\n";
    $message .= "Acquisition Target: $target\n";
    $message .= "Request Type: $request_type\n";
    $message .= "Timeline: $timeline\n";
    $message .= "Specifications:\n$specs\n";
    
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: VB Arms Registry <noreply@vb-arms.com>',
        'Reply-To: ' . $contact
    );
    
    if (wp_mail($to, $subject, $message, $headers)) {
        $form_submitted = true;
    } else {
        $form_error = true;
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>White Glove | <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-bg: #0a0a0a;
            --secondary-bg: #111111;
            --accent-green: #00ff94;
            --text-primary: #ffffff;
            --text-secondary: #b8b8b8;
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
        .page-main { flex: 1; }

        /* --- NAVIGATION (match home page size) --- */
        .nav-header {
            position: fixed; top: 0; left: 0; right: 0; height: clamp(65px, 10vw, 75px);
            background: rgba(0, 0, 0, 0.95); backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--border-subtle); z-index: 1000;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 clamp(1rem, 5vw, 4rem); transition: transform 0.4s ease;
        }
        
        .nav-logo { 
            display: flex; 
            align-items: center; 
            gap: 0.5rem; 
            flex-shrink: 1; 
            min-width: 0; 
        }
        
        .nav-logo a { 
            display: flex; 
            align-items: center; 
            gap: 0.5rem; 
            text-decoration: none; 
        }
        
        .nav-logo img {
            height: clamp(35px, 8vw, 50px);
            width: auto;
            display: block;
            object-fit: contain;
            vertical-align: middle;
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

        /* --- WHITE GLOVE SECTION --- */
        .intake-container {
            max-width: 1100px;
            margin: 100px auto 60px;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: start;
        }

        .concierge-info {
            display: flex;
            flex-direction: column;
        }
        
        .service-label {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.3rem;
        }
        
        .service-tagline {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            line-height: 1.4;
        }
        
        .concierge-info h1 {
            font-size: clamp(2.5rem, 5vw, 3.3rem);
            line-height: 1.1;
            margin-bottom: 2rem;
            color: #fff;
        }
        
        .info-block {
            margin-bottom: 2rem;
        }
        
        .info-label {
            font-family: 'JetBrains Mono';
            font-size: 0.85rem;
            text-transform: uppercase;
            color: var(--accent-green);
            letter-spacing: 2px;
            margin-bottom: 0.4rem;
            display: block;
        }
        
        .info-value {
            font-size: 1.15rem;
            color: var(--text-secondary);
        }
        
        .info-value a {
            color: inherit;
            text-decoration: none;
            transition: 0.3s;
        }
        
        .info-value a:hover {
            color: #fff;
        }

        .intake-registry {
            background: var(--glass);
            border: 1px solid var(--border-subtle);
            padding: clamp(1.5rem, 5vw, 2.5rem);
            border-radius: 16px;
        }
        
        .registry-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.2rem;
        }
        
        .full-width {
            grid-column: span 2;
        }

        .form-group label {
            font-family: 'JetBrains Mono';
            font-size: 0.8rem;
            text-transform: uppercase;
            color: var(--text-secondary);
            margin-bottom: 0.4rem;
            display: block;
        }
        
        .form-input {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border-subtle);
            padding: clamp(0.6rem, 2vw, 0.9rem);
            border-radius: 8px;
            color: #fff;
            font-family: inherit;
            font-size: clamp(0.75rem, 1.5vw, 0.9rem);
            transition: 0.3s;
            width: 100%;
        }
        
        select.form-input {
            font-size: clamp(0.6rem, 1.8vw, 0.82rem) !important;
            padding: clamp(0.4rem, 1.2vw, 0.75rem) 0.5rem;
            min-height: 0;
            max-width: 100%;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--accent-green);
            background: rgba(255,255,255,0.08);
        }

        .submit-btn {
            grid-column: span 2;
            width: 100%;
            box-sizing: border-box;
            background: rgba(0, 255, 148, 0.1);
            border: 1px solid rgba(0, 255, 148, 0.3);
            color: var(--accent-green);
            padding: 0.65rem 2rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 1.5rem;
        }
        
        .submit-btn:hover {
            background: var(--accent-green);
            color: #000;
            transform: translateY(-2px);
        }

        .ffl-badge {
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
            margin-top: 1rem;
        }
        
        .ffl-badge:hover {
            background: rgba(0, 255, 148, 0.05);
            transform: translateY(-1px);
        }
        .ffl-badge .ffl-location { color: #fff; }

        /* --- BROWSE SECTION --- */
        .browse-section {
            max-width: 1100px;
            margin: 60px auto 60px;
            padding: 0 2rem;
            text-align: center;
        }

        /* Modern card container for Partner Catalogs */
        .partner-catalogs-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(0, 255, 148, 0.2);
            border-radius: 16px;
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.2);
            max-width: 560px;
            margin: 0 auto;
        }
        
        .partner-catalogs-box h2 {
            margin-top: 0;
            text-decoration: underline;
        }
        
        .partner-catalogs-box .browse-partners a {
            text-decoration: underline;
        }
        
        .partner-catalogs-box .browse-description {
            margin-bottom: 0;
        }
        
        .partner-tagline-pill {
            display: inline-block;
            margin-top: 0.5rem;
            padding: 0.5rem 1.25rem;
            background: rgba(0, 255, 148, 0.06);
            border: 1px solid rgba(0, 255, 148, 0.3);
            border-radius: 50px;
            font-size: 0.95rem;
            color: #fff;
        }
        
        .browse-section h2 {
            font-family: 'JetBrains Mono';
            font-size: 1rem;
            text-transform: uppercase;
            color: var(--accent-green);
            letter-spacing: 2px;
            margin-bottom: 1rem;
        }
        
        .browse-partners {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 1rem;
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
        
        .browse-description {
            font-size: 0.95rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        /* --- FOOTER (match homepage / shop: bottom of viewport, full-width, centered) --- */
        .signature-tagline {
            text-align: center;
            font-size: clamp(1.1rem, 3vw, 1.4rem);
            font-style: italic;
            color: var(--accent-green);
            margin: 2rem 0;
            font-weight: 300;
        }
        .vb-footer {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            margin: 0;
            margin-top: auto;
            padding: 15px 2rem 40px;
            border-top: 1px solid var(--border-subtle);
            background: var(--primary-bg);
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex-shrink: 0;
        }
        .vb-footer p { font-size: 0.8rem; margin-bottom: 8px; color: var(--text-secondary); }
        .vb-footer .footer-links { display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap; }
        .footer-link {
            color: rgba(0, 255, 148, 0.75);
            text-decoration: none;
            font-size: 0.75rem;
            transition: 0.2s;
        }
        .footer-link:hover {
            color: var(--accent-green);
        }

        /* Toast / success pop-up: transparent green, centered */
        .toast-notification {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 255, 148, 0.92);
            border: 1px solid rgba(0, 255, 148, 0.6);
            color: #0a0a0a;
            padding: 1.25rem 2rem;
            border-radius: 16px;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 12px 40px rgba(0, 255, 148, 0.35);
            z-index: 9999;
            animation: toastPopIn 0.35s ease;
        }
        
        .toast-notification.error {
            background: rgba(255, 77, 77, 0.92);
            border-color: rgba(255, 77, 77, 0.6);
            box-shadow: 0 12px 40px rgba(255, 77, 77, 0.35);
        }
        
        @keyframes toastPopIn {
            from { opacity: 0; transform: translate(-50%, -50%) scale(0.92); }
            to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }

        /* --- RESPONSIVE --- */
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
                width: 75px;
                font-size: 0.65rem;
                padding: 0.3rem 0;
            }
        }
        
        @media (max-width: 850px) {
            .intake-container {
                grid-template-columns: 1fr;
                gap: 3rem;
                text-align: center;
                padding: 0 1.5rem;
            }
            
            .registry-form {
                grid-template-columns: 1fr;
            }
            
            .full-width, .submit-btn {
                grid-column: span 1;
            }
            
            .concierge-info {
                align-items: center;
            }
            
            .ffl-badge {
                align-self: center;
            }
        }
        
        @media (max-width: 600px) {
            .browse-partners {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .browse-partners-sep {
                display: none;
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
            .nav-actions {
                gap: 0.3rem;
            }
            .nav-contact-btn {
                width: 65px;
                font-size: 0.58rem;
                padding: 0.28rem 0;
                border-radius: 40px;
            }
        }
        
        @media (max-width: 420px) {
            .nav-logo-text {
                display: none;
            }
            .nav-contact-btn {
                width: 60px;
                font-size: 0.55rem;
                padding: 0.25rem 0;
            }
            .nav-actions {
                gap: 0.25rem;
            }
        }
    </style>
</head>
<body>
    <?php
    $logo_paths = array(
        get_stylesheet_directory() . '/vbarms-black-logo_512.png',
        get_template_directory() . '/vbarms-black-logo_512.png',
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
        $ud = wp_upload_dir();
        $logo_url = $ud['baseurl'] . '/vbarms-black-logo_512.png';
    }
    ?>

    <nav class="nav-header">
        <div class="nav-logo">
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <img src="<?php echo esc_url($logo_url); ?>" alt="VB Arms">
                <span class="nav-logo-text">VB ARMS</span>
            </a>
        </div>
        <div class="nav-actions">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="nav-contact-btn">Home</a>
            <a href="<?php echo esc_url($white_glove_shop_url); ?>" class="nav-contact-btn">Shop</a>
            <a href="<?php echo esc_url(home_url('/white-glove/')); ?>" class="nav-contact-btn primary-cta">White Glove</a>
        </div>
    </nav>

    <div class="page-main">
    <!-- White Glove Form Section -->
    <main class="intake-container">
        <section class="concierge-info">
            <span class="service-label">White Glove Service</span>
            <p class="service-tagline">We Beat Any Comparable Quote.</p>
            <h1>Procurement <br><span style="color:var(--accent-green);">Registry</span></h1>

            <div class="info-block">
                <span class="info-label">Address</span>
                <p class="info-value">1607 Capitol Ave<br>Cheyenne, WY 82001</p>
            </div>

            <div class="info-block">
                <span class="info-label">Intelligence</span>
                <p class="info-value">
                    <a href="tel:307-286-9128">307-286-9128</a><br>
                    <a href="mailto:vb@vb-arms.com">vb@vb-arms.com</a>
                </p>
            </div>

            <a href="https://masterffl.com/ffl-dealers/vb-arms-cheyenne-wy-503109514/" target="_blank" rel="noopener noreferrer" class="ffl-badge">
                Licensed FFL Dealer • <span class="ffl-location">Cheyenne, Wyoming</span>
            </a>
        </section>

        <section class="intake-registry">
            <form class="registry-form" action="" method="POST">
                <input type="hidden" name="procurement_request" value="1">
                
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

    <!-- Browse Partner Catalogs Section -->
    <section class="browse-section">
        <div class="partner-catalogs-box">
            <h2>Partner Catalogs</h2>
            <div class="browse-partners">
                <a href="https://www.lipseys.com" target="_blank" rel="noopener noreferrer">Lipsey's</a>
                <span class="browse-partners-sep">•</span>
                <a href="https://shop2.gzanders.com/" target="_blank" rel="noopener noreferrer">Zanders</a>
                <span class="browse-partners-sep">•</span>
                <a href="https://www.davidsonsinc.com/" target="_blank" rel="noopener noreferrer">Davidson's</a>
            </div>
            <p class="browse-description"><span class="partner-tagline-pill">Find your ideal setup, then request a quote above.</span></p>
        </div>
    </section>

    </div><!-- .page-main -->

    <footer class="vb-footer">
        <div class="signature-tagline">Your target. Our acquisition.</div>
        <p>© <?php echo date('Y'); ?> VB ARMS • Bespoke Firearms Procurement</p>
        <div class="footer-links">
            <a href="<?php echo esc_url(home_url('/browse/')); ?>" class="footer-link">Browse</a>
            <a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>" class="footer-link">Privacy Policy</a>
            <a href="<?php echo esc_url(home_url('/refund-returns/')); ?>" class="footer-link">Refund & Returns</a>
            <a href="<?php echo esc_url(home_url('/terms-of-service/')); ?>" class="footer-link">Terms of Service</a>
        </div>
    </footer>

    <?php wp_footer(); ?>

    <?php if ($form_submitted): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.textContent = '✓ Request received! We\'ll contact you shortly.';
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                toast.style.opacity = '0';
                toast.style.transform = 'translate(-50%, -50%) scale(0.95)';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
            
            var form = document.querySelector('.registry-form');
            if (form) form.reset();
        });
    </script>
    <?php endif; ?>

    <?php if ($form_error): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toast = document.createElement('div');
            toast.className = 'toast-notification error';
            toast.textContent = '✗ Error sending request. Please email vb@vb-arms.com directly.';
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                toast.style.opacity = '0';
                toast.style.transform = 'translate(-50%, -50%) scale(0.95)';
                setTimeout(() => toast.remove(), 300);
            }, 7000);
        });
    </script>
    <?php endif; ?>
</body>
</html>
