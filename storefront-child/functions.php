<?php
/**
 * Storefront Child Theme Functions - VB Arms Customizations
 * Removes Storefront styling on custom template pages and styles cart page
 */

/**
 * Get product MFG MDL # and UPC for display (used on archive and single product).
 * MFG MDL from _manufacturer_part_number or _model; UPC from _upc.
 *
 * @param int|WC_Product $product Product ID or product object.
 * @return array{ 'mfg_mdl': string, 'upc': string }
 */
function vb_arms_product_mfg_upc( $product ) {
    $p = is_numeric( $product ) ? wc_get_product( $product ) : $product;
    if ( ! $p || ! is_a( $p, 'WC_Product' ) ) {
        return array( 'mfg_mdl' => '', 'upc' => '' );
    }
    $id = $p->get_id();
    $mfg = get_post_meta( $id, '_manufacturer_part_number', true );
    if ( (string) $mfg === '' ) {
        $mfg = get_post_meta( $id, '_model', true );
    }
    $upc = get_post_meta( $id, '_upc', true );
    return array(
        'mfg_mdl' => is_string( $mfg ) ? trim( $mfg ) : '',
        'upc'     => is_string( $upc ) ? trim( $upc ) : '',
    );
}

/**
 * Get product meta for Lipsey's-style block on single product: manufacturer, MFG MDL #, UPC, MSRP.
 *
 * @param int|WC_Product $product Product ID or product object.
 * @return array{ 'manufacturer': string, 'mfg_mdl': string, 'upc': string, 'msrp_formatted': string }
 */
function vb_arms_product_meta_block( $product ) {
    $p = is_numeric( $product ) ? wc_get_product( $product ) : $product;
    if ( ! $p || ! is_a( $p, 'WC_Product' ) ) {
        return array( 'manufacturer' => '', 'mfg_mdl' => '', 'upc' => '', 'msrp_formatted' => '' );
    }
    $id = $p->get_id();
    $manufacturer = get_post_meta( $id, '_manufacturer', true );
    if ( (string) $manufacturer === '' ) {
        $attr = $p->get_attribute( 'pa_manufacturer' );
        if ( is_string( $attr ) && trim( $attr ) !== '' ) {
            $manufacturer = trim( $attr );
        }
    }
    $mfg_upc = function_exists( 'vb_arms_product_mfg_upc' ) ? vb_arms_product_mfg_upc( $p ) : array( 'mfg_mdl' => '', 'upc' => '' );
    $msrp_raw = get_post_meta( $id, '_msrp', true );
    $msrp_formatted = '';
    if ( $msrp_raw !== '' && is_numeric( $msrp_raw ) ) {
        $msrp_formatted = wc_price( (float) $msrp_raw );
    }
    return array(
        'manufacturer'    => is_string( $manufacturer ) ? trim( $manufacturer ) : '',
        'mfg_mdl'         => $mfg_upc['mfg_mdl'],
        'upc'             => $mfg_upc['upc'],
        'msrp_formatted'  => $msrp_formatted,
    );
}

// Remove WooCommerce "demo store for testing purposes" notice
add_filter( 'option_woocommerce_demo_store', function() { return 'no'; } );
add_action( 'init', function() {
    remove_action( 'wp_footer', 'woocommerce_demo_store' );
}, 20 );

// Remove exclamation point from coupon pill/link text (cart and checkout)
add_filter( 'gettext', 'vb_arms_coupon_text_no_exclamation', 20, 3 );
function vb_arms_coupon_text_no_exclamation( $translated, $text, $domain ) {
    if ( $domain !== 'woocommerce' ) {
        return $translated;
    }
    // Strip "!" from coupon-related strings
    if ( strpos( $text, 'coupon' ) !== false || strpos( $text, 'Coupon' ) !== false ) {
        return str_replace( '!', '', $translated );
    }
    return $translated;
}

// Fix payment method text: don't show "Thank you for your order" before the order is placed (e.g. Addify Invoice)
add_filter( 'woocommerce_gateway_description', 'vb_arms_checkout_payment_description_before_place_order', 10, 2 );
function vb_arms_checkout_payment_description_before_place_order( $description, $gateway_id ) {
    if ( ! is_checkout() || empty( $description ) ) {
        return $description;
    }
    if ( stripos( $description, 'Thank you for your order' ) !== false ) {
        return __( "We'll send invoice and payment details after you place your order.", 'storefront-child' );
    }
    return $description;
}

// Fallback: catch "Thank you for your order" if the gateway outputs it via gettext (e.g. some payment plugins)
add_filter( 'gettext', 'vb_arms_checkout_no_thank_you_before_order', 10, 3 );
function vb_arms_checkout_no_thank_you_before_order( $translated, $text, $domain ) {
    if ( ! is_checkout() ) {
        return $translated;
    }
    if ( stripos( $text, 'Thank you for your order' ) !== false || stripos( $translated, 'Thank you for your order' ) !== false ) {
        return __( "We'll send invoice and payment details after you place your order.", 'storefront-child' );
    }
    return $translated;
}

/**
 * MetaMask logo URL: theme MetaMask.png (preferred), then metamask-logo.png, metamask.svg, uploads fallback.
 */
function vb_arms_metamask_logo_url() {
    $theme_dir = get_stylesheet_directory();
    $theme_uri = get_stylesheet_directory_uri();
    if ( file_exists( $theme_dir . '/logos/MetaMask.png' ) ) {
        return $theme_uri . '/logos/MetaMask.png';
    }
    if ( file_exists( $theme_dir . '/logos/metamask-logo.png' ) ) {
        return $theme_uri . '/logos/metamask-logo.png';
    }
    if ( file_exists( $theme_dir . '/logos/metamask-logo' ) ) {
        return $theme_uri . '/logos/metamask-logo';
    }
    if ( file_exists( $theme_dir . '/logos/metamask.svg' ) ) {
        return $theme_uri . '/logos/metamask.svg';
    }
    $upload_dir = wp_upload_dir();
    if ( file_exists( $upload_dir['basedir'] . '/logos/MetaMask.png' ) ) {
        return $upload_dir['baseurl'] . '/logos/MetaMask.png';
    }
    if ( file_exists( $upload_dir['basedir'] . '/logos/metamask-logo.png' ) ) {
        return $upload_dir['baseurl'] . '/logos/metamask-logo.png';
    }
    return $upload_dir['baseurl'] . '/logos/metamask.svg';
}

/**
 * USDT logo URL: theme logos/USDT_Logo.png (preferred), then usdt-logo.png, uploads fallback.
 */
function vb_arms_usdt_logo_url() {
    $theme_dir = get_stylesheet_directory();
    $theme_uri = get_stylesheet_directory_uri();
    if ( file_exists( $theme_dir . '/logos/USDT_Logo.png' ) ) {
        return $theme_uri . '/logos/USDT_Logo.png';
    }
    if ( file_exists( $theme_dir . '/logos/usdt-logo.png' ) ) {
        return $theme_uri . '/logos/usdt-logo.png';
    }
    if ( file_exists( $theme_dir . '/logos/usdt-logo' ) ) {
        return $theme_uri . '/logos/usdt-logo';
    }
    $upload_dir = wp_upload_dir();
    if ( file_exists( $upload_dir['basedir'] . '/logos/USDT_Logo.png' ) ) {
        return $upload_dir['baseurl'] . '/logos/USDT_Logo.png';
    }
    if ( file_exists( $upload_dir['basedir'] . '/logos/usdt-logo.png' ) ) {
        return $upload_dir['baseurl'] . '/logos/usdt-logo.png';
    }
    return $theme_uri . '/logos/USDT_Logo.png';
}

// Checkout: force "Pay with USDT" as the payment method title (replaces "Pay With Cryptocurrency" etc.)
add_filter( 'woocommerce_gateway_title', 'vb_arms_gateway_title_pay_with_usdt', 100, 2 );
function vb_arms_gateway_title_pay_with_usdt( $title, $payment_id ) {
    if ( ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) {
        return $title;
    }
    $t = is_string( $title ) ? $title : '';
    if ( stripos( $t, 'Cryptocurrency' ) !== false || stripos( $t, 'Crypto' ) !== false ) {
        return 'Pay with USDT';
    }
    return $title;
}

// Checkout: ensure "Other crypto options" appears above privacy text and Place order; inject USDT logo next to "Pay with USDT".
add_action( 'wp_footer', 'vb_arms_checkout_order_and_usdt_logo', 20 );
function vb_arms_checkout_order_and_usdt_logo() {
    if ( ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) {
        return;
    }
    $usdt_logo = function_exists( 'vb_arms_usdt_logo_url' ) ? vb_arms_usdt_logo_url() : '';
    ?>
    <script>
    (function() {
        function vbArmsCheckoutOrder() {
            var wrap = document.querySelector('.checkout-crypto-line-wrap');
            var privacy = document.querySelector('.woocommerce-privacy-policy-text');
            if (wrap && privacy && wrap !== privacy.previousElementSibling) {
                privacy.parentNode.insertBefore(wrap, privacy);
            }
            <?php if ( $usdt_logo ) : ?>
            var usdtLogo = <?php echo json_encode( $usdt_logo ); ?>;
            document.querySelectorAll('#payment .payment_methods label').forEach(function(lab) {
                if (lab.textContent.indexOf('Pay with USDT') !== -1 && !lab.querySelector('.vb-arms-usdt-title-logo')) {
                    var img = document.createElement('img');
                    img.src = usdtLogo;
                    img.alt = '';
                    img.className = 'vb-arms-usdt-title-logo';
                    img.style.cssText = 'height:1.2em;width:auto;vertical-align:middle;margin-right:6px;';
                    lab.insertBefore(img, lab.firstChild);
                }
            });
            <?php endif; ?>
            // Wrap all card logos in 2nd payment method so they move to second line
            var list = document.querySelector('#payment .payment_methods');
            if (list && list.children[1]) {
                var secondLi = list.children[1];
                var label = secondLi.querySelector('label');
                if (label && !label.querySelector('.vb-arms-card-logos-wrap')) {
                    var imgs = label.querySelectorAll('img');
                    if (imgs.length > 0) {
                        var wrapper = document.createElement('span');
                        wrapper.className = 'vb-arms-card-logos-wrap';
                        imgs.forEach(function(img) { wrapper.appendChild(img); });
                        label.appendChild(wrapper);
                    }
                }
            }
        }
        function run() {
            vbArmsCheckoutOrder();
            setTimeout(vbArmsCheckoutOrder, 100);
            setTimeout(vbArmsCheckoutOrder, 500);
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', run);
        } else {
            run();
        }
        if (typeof jQuery !== 'undefined') {
            jQuery(document.body).on('updated_checkout', run);
        }
    })();
    </script>
    <?php
}

// Checkout: replace crypto gateway gettext strings with "Pay with USDT"
add_filter( 'gettext', 'vb_arms_checkout_pay_with_usdt_labels', 10, 3 );
function vb_arms_checkout_pay_with_usdt_labels( $translated, $text, $domain ) {
    if ( ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) {
        return $translated;
    }
    $t = is_string( $text ) ? $text : '';
    $tr = is_string( $translated ) ? $translated : '';
    $both = $t . ' ' . $tr;
    if ( stripos( $both, 'Pay With Cryptocurrency' ) !== false || stripos( $both, 'Pay with Cryptocurrency' ) !== false || stripos( $both, 'Pay with crypto' ) !== false ) {
        return __( 'Pay with USDT', 'storefront-child' );
    }
    if ( stripos( $t, 'Please Select a Currency' ) !== false || stripos( $tr, 'Please Select a Currency' ) !== false ) {
        return __( 'Pay with USDT', 'storefront-child' );
    }
    if ( stripos( $t, 'Select Cryptocurrency' ) !== false || stripos( $tr, 'Select Cryptocurrency' ) !== false ) {
        return __( 'Pay with USDT', 'storefront-child' );
    }
    return $translated;
}

/**
 * Checkout: output "Other crypto options" line below Pay with USDT, above privacy text and Place order.
 */
add_action( 'woocommerce_review_order_before_submit', 'vb_arms_checkout_crypto_line_under_usdt', 5 );
function vb_arms_checkout_crypto_line_under_usdt() {
    if ( is_wc_endpoint_url( 'order-received' ) ) {
        return;
    }
    $upload_dir   = wp_upload_dir();
    $theme_logos  = get_stylesheet_directory_uri() . '/logos/';
    $crypto_url   = home_url( '/crypto-payment/' );
    ?>
    <div class="checkout-crypto-line-wrap">
        <p class="checkout-crypto-line-label">Other crypto options</p>
        <a href="<?php echo esc_url( $crypto_url ); ?>" class="checkout-crypto-line-link">
            <span class="checkout-crypto-line-head">
                <span class="checkout-crypto-fake-radio" style="display:inline-block;width:18px;height:18px;min-width:18px;min-height:18px;border:1px solid #ffffff;border-radius:50%;background:#ffffff;flex-shrink:0;"></span>
                <span class="checkout-crypto-head-text">Other crypto options</span>
            </span>
            <span class="checkout-crypto-strip">
                <span class="checkout-crypto-strip-row">
                    <span class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/btc.png' ); ?>" alt=""> BTC</span>
                    <span class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/eth.png' ); ?>" alt=""> ETH</span>
                    <span class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/usdc-logo.png' ); ?>" alt=""> USDC</span>
                </span>
                <span class="checkout-crypto-strip-row">
                    <span class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/frnt.png' ); ?>" alt=""> FRNT</span>
                    <span class="pay-item"><img src="<?php echo esc_url( $upload_dir['baseurl'] . '/logos/metal-mtl-logo.png' ); ?>" alt=""> MTL</span>
                    <span class="pay-item"><img src="<?php echo esc_url( $theme_logos . 'xpr-white-logo.png' ); ?>" alt=""> XPR</span>
                </span>
            </span>
            <span class="checkout-crypto-line-cta">Addresses &amp; instructions →</span>
        </a>
    </div>
    <?php
}

// Remove marketing / newsletter opt-in checkbox from checkout (e.g. "I would like to receive exclusive emails with discounts and product information")
add_filter( 'woocommerce_checkout_fields', 'vb_arms_remove_checkout_marketing_opt_in', 9999 );
function vb_arms_remove_checkout_marketing_opt_in( $fields ) {
    $remove_keys = array( 'mailchimp_woocommerce_newsletter', 'mc4wp_subscribe', 'klaviyo_opt_in', 'woocommerce_mailchimp_opt_in', 'opt_in', 'marketing_opt_in', 'newsletter_opt_in', 'mailchimp_signup', 'yith_wcwl_subscribe', 'subscribe', 'email_subscription', 'mailchimp_signup_newsletter' );
    $needles = array( 'exclusive emails', 'discounts and product', 'product information', 'marketing', 'newsletter', 'opt-in', 'opt in' );
    foreach ( array( 'billing', 'shipping', 'order', 'additional' ) as $section ) {
        if ( empty( $fields[ $section ] ) || ! is_array( $fields[ $section ] ) ) {
            continue;
        }
        foreach ( $fields[ $section ] as $key => $field ) {
            if ( in_array( $key, $remove_keys, true ) ) {
                unset( $fields[ $section ][ $key ] );
                continue;
            }
            $label = isset( $field['label'] ) ? strtolower( $field['label'] ) : '';
            $type = isset( $field['type'] ) ? $field['type'] : '';
            if ( $type !== 'checkbox' ) {
                continue;
            }
            foreach ( $needles as $needle ) {
                if ( strpos( $label, $needle ) !== false ) {
                    unset( $fields[ $section ][ $key ] );
                    break;
                }
            }
        }
    }
    return $fields;
}

// Ensure no marketing/opt-in field can block checkout (plugins may add required checkboxes we hide)
add_filter( 'woocommerce_checkout_fields', 'vb_arms_checkout_force_optional_marketing_fields', 99999 );
function vb_arms_checkout_force_optional_marketing_fields( $fields ) {
    if ( ! is_checkout() ) {
        return $fields;
    }
    $optional_keys = array( 'mailchimp_woocommerce_newsletter', 'mc4wp_subscribe', 'klaviyo_opt_in', 'opt_in', 'marketing_opt_in', 'newsletter_opt_in', 'mailchimp_signup' );
    $optional_needles = array( 'exclusive emails', 'discounts', 'product information', 'marketing', 'newsletter', 'opt-in', 'opt in' );
    foreach ( array( 'billing', 'shipping', 'order', 'additional' ) as $section ) {
        if ( empty( $fields[ $section ] ) || ! is_array( $fields[ $section ] ) ) {
            continue;
        }
        foreach ( $fields[ $section ] as $key => $field ) {
            if ( in_array( $key, $optional_keys, true ) ) {
                $fields[ $section ][ $key ]['required'] = false;
                continue;
            }
            $label = isset( $field['label'] ) ? strtolower( $field['label'] ) : '';
            foreach ( $optional_needles as $needle ) {
                if ( strpos( $label, $needle ) !== false ) {
                    $fields[ $section ][ $key ]['required'] = false;
                    break;
                }
            }
        }
    }
    return $fields;
}

// "Ship to a different address?" unchecked by default so shipping fields only show when user checks it
add_filter( 'woocommerce_ship_to_different_address_checked', '__return_false' );

// Hide marketing checkbox via CSS (catches plugins that inject HTML outside checkout fields).
// If the checkbox still appears: disable it in the plugin that adds it (e.g. Klaviyo: Integrations → WooCommerce → uncheck "Subscribe contacts to email marketing"; Mailchimp: disable checkout signup).
add_action( 'wp_head', 'vb_arms_checkout_hide_marketing_opt_in_css', 5 );
function vb_arms_checkout_hide_marketing_opt_in_css() {
    if ( ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) {
        return;
    }
    ?>
    <style id="vb-arms-hide-marketing-opt-in">
    /* Hide marketing/newsletter opt-in only — never hide billing/shipping sections (target form rows only) */
    .woocommerce-checkout .form-row-mailchimp_woocommerce_newsletter,
    .woocommerce-checkout #mailchimp_woocommerce_newsletter_field,
    .woocommerce-checkout .form-row-opt_in,
    .woocommerce-checkout [data-id="mailchimp_woocommerce_newsletter"],
    .woocommerce form.checkout .form-row-mailchimp_woocommerce_newsletter,
    .woocommerce form.checkout #mailchimp_woocommerce_newsletter_field,
    .woocommerce-checkout .form-row-newsletter,
    .woocommerce-checkout .form-row-marketing,
    .woocommerce-checkout .form-row-subscribe,
    .woocommerce-checkout .form-row[class*="mailchimp"],
    .woocommerce-checkout .form-row[class*="newsletter"],
    .woocommerce-checkout .form-row[class*="klaviyo"],
    .woocommerce-checkout .form-row[class*="opt-in"],
    .woocommerce-checkout .form-row[class*="opt_in"],
    .vb-checkout-page .form-row-mailchimp_woocommerce_newsletter,
    .vb-checkout-page .form-row[class*="newsletter"],
    .vb-checkout-page .form-row[class*="opt_in"],
    .vb-checkout-page .form-row[class*="marketing"],
    .woocommerce-checkout [id*="klaviyo"][id*="newsletter"],
    .woocommerce-checkout [id*="mailchimp"][id*="newsletter"],
    .woocommerce-checkout [class*="klaviyo"] .form-row,
    .vb-checkout-page [id*="klaviyo"] .form-row
    { display: none !important; }
    /* Force billing and shipping sections to stay visible (prevent any script or broad selector from hiding them) */
    .woocommerce-checkout .woocommerce-billing-fields,
    .woocommerce-checkout .woocommerce-shipping-fields,
    .vb-checkout-page .woocommerce-billing-fields,
    .vb-checkout-page .woocommerce-shipping-fields
    { display: block !important; visibility: visible !important; }
    </style>
    <?php
}

// Blank out the marketing checkbox label so even if the row is visible, it shows no text (plugin may add via own hook)
add_filter( 'gettext', 'vb_arms_blank_marketing_opt_in_label_on_checkout', 10, 3 );
function vb_arms_blank_marketing_opt_in_label_on_checkout( $translated, $text, $domain ) {
    if ( ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) {
        return $translated;
    }
    $t = is_string( $text ) ? $text : '';
    if ( stripos( $t, 'exclusive emails' ) !== false && stripos( $t, 'discounts' ) !== false ) {
        return '';
    }
    return $translated;
}

// Don't output the marketing checkbox at all when WooCommerce or a plugin renders it via woocommerce_form_field()
add_filter( 'woocommerce_form_field_checkbox', 'vb_arms_remove_marketing_checkbox_output', 99999, 4 );
function vb_arms_remove_marketing_checkbox_output( $field_html, $key, $args, $value ) {
    if ( ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) {
        return $field_html;
    }
    $label = isset( $args['label'] ) ? ( is_string( $args['label'] ) ? $args['label'] : '' ) : '';
    if ( stripos( $label, 'exclusive emails' ) !== false && ( stripos( $label, 'discounts' ) !== false || stripos( $label, 'product information' ) !== false ) ) {
        return '';
    }
    if ( stripos( $label, 'product information' ) !== false && stripos( $label, 'discounts' ) !== false ) {
        return '';
    }
    return $field_html;
}

// Fix duplicated/sloppy invoice message everywhere: page, email, any plugin output
add_filter( 'gettext', 'vb_arms_fix_invoice_message_order_received', 10, 3 );
function vb_arms_fix_invoice_message_order_received( $translated, $text, $domain ) {
    if ( ! is_string( $translated ) || $translated === '' ) {
        return $translated;
    }
    $needle = 'you will be invoiced soon with regards to payment';
    if ( stripos( $translated, $needle ) === false ) {
        return $translated;
    }
    // Always replace with a single clean sentence (fixes duplicate "payment.you will...")
    return 'You will be invoiced soon with regards to payment.';
}

// On order-received page, scrub duplicate from final HTML (catches gateway output that bypasses gettext)
add_action( 'template_redirect', 'vb_arms_order_received_buffer_start', 0 );
function vb_arms_order_received_buffer_start() {
    if ( ! is_checkout() || ! is_wc_endpoint_url( 'order-received' ) ) {
        return;
    }
    ob_start( 'vb_arms_order_received_buffer_callback' );
}
add_action( 'shutdown', 'vb_arms_order_received_buffer_end', 0 );
function vb_arms_order_received_buffer_end() {
    if ( ! is_checkout() || ! is_wc_endpoint_url( 'order-received' ) ) {
        return;
    }
    if ( function_exists( 'ob_get_level' ) && ob_get_level() > 0 ) {
        ob_end_flush();
    }
}
function vb_arms_order_received_buffer_callback( $html ) {
    if ( ! is_string( $html ) ) {
        return $html;
    }
    $dupe = 'you will be invoiced soon with regards to payment.you will be invoiced soon with regards to payment.';
    $single = 'You will be invoiced soon with regards to payment.';
    $html = str_ireplace( $dupe, $single, $html );
    // Also fix if there's a space or extra punctuation between duplicates
    $html = preg_replace( '/\byou will be invoiced soon with regards to payment\.?\s*\.?\s*you will be invoiced soon with regards to payment\.?/iu', $single, $html );
    return $html;
}

// Customize order-received (confirmation) email: subject, heading, and intro text
add_filter( 'woocommerce_email_subject_customer_on_hold_order', 'vb_arms_order_received_email_subject', 10, 2 );
add_filter( 'woocommerce_email_subject_customer_processing_order', 'vb_arms_order_received_email_subject', 10, 2 );
function vb_arms_order_received_email_subject( $subject, $order ) {
    return sprintf( __( 'Your %s order has been received!', 'storefront-child' ), get_bloginfo( 'name' ) );
}

add_filter( 'woocommerce_email_heading_customer_on_hold_order', 'vb_arms_order_received_email_heading', 10, 2 );
add_filter( 'woocommerce_email_heading_customer_processing_order', 'vb_arms_order_received_email_heading', 10, 2 );
function vb_arms_order_received_email_heading( $heading, $order ) {
    return get_bloginfo( 'name' );
}

// Replace "on hold until we confirm payment" intro and fix duplicated invoice line in emails
add_filter( 'woocommerce_mail_content', 'vb_arms_order_received_email_content', 20, 2 );
function vb_arms_order_received_email_content( $content, $email ) {
    if ( ! $content || ! is_string( $content ) ) {
        return $content;
    }
    // Fix duplicated invoice sentence in any email (e.g. "...payment.you will be invoiced soon...")
    $dupe = 'you will be invoiced soon with regards to payment.you will be invoiced soon with regards to payment.';
    $single = 'You will be invoiced soon with regards to payment.';
    $content = str_ireplace( $dupe, $single, $content );
    $content = preg_replace( '/\byou will be invoiced soon with regards to payment\.?\s*\.?\s*you will be invoiced soon with regards to payment\.?/iu', $single, $content );
    // Replace on-hold intros only for order-received emails
    $ids = array( 'customer_on_hold_order', 'customer_processing_order' );
    if ( is_object( $email ) && in_array( $email->id, $ids, true ) ) {
        $old_intros = array(
            "We've received your order and it's currently on hold until we can confirm your payment has been processed.",
            "We've received your order and it's currently on hold. We'll let you know if we need any other information from you.",
            "Your order is on-hold until we confirm payment has been received.",
        );
        $new_intro = "We've received your order. You will be invoiced soon with payment details.";
        foreach ( $old_intros as $old ) {
            $content = str_replace( $old, $new_intro, $content );
        }
    }
    return $content;
}

// Notify store admin when a customer opts in to marketing emails at checkout (only runs if a plugin still saves opt-in)
add_action( 'woocommerce_thankyou', 'vb_arms_notify_admin_marketing_opt_in', 10, 1 );
function vb_arms_notify_admin_marketing_opt_in( $order_id ) {
    if ( ! $order_id ) {
        return;
    }
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }
    $opt_in = $order->get_meta( '_wc_customer_opt_in' );
    if ( empty( $opt_in ) ) {
        $opt_in = $order->get_meta( 'marketing_opt_in' );
    }
    if ( empty( $opt_in ) ) {
        $opt_in = $order->get_meta( 'opt_in_newsletter' );
    }
    if ( $opt_in !== 'yes' && $opt_in !== '1' ) {
        return;
    }
    if ( $order->get_meta( '_vb_arms_opt_in_notification_sent' ) ) {
        return;
    }
    $order->update_meta_data( '_vb_arms_opt_in_notification_sent', '1' );
    $order->save();
    $admin = get_option( 'admin_email' );
    $subject = sprintf( '[%s] New marketing opt-in', get_bloginfo( 'name' ) );
    $edit_url = $order->get_edit_order_url();
    $message = sprintf(
        "A customer opted in to marketing emails on order #%s.\n\nView order: %s",
        $order_id,
        $edit_url ? $edit_url : admin_url( 'post.php?post=' . $order_id . '&action=edit' )
    );
    wp_mail( $admin, $subject, $message );
}

// Redirect ?page_id=1303 to clean URL /contact/ (so the URL bar shows vb-arms.com/contact/)
function vb_arms_redirect_page_id_to_contact() {
    if ( isset( $_GET['page_id'] ) && (int) $_GET['page_id'] === 1303 ) {
        wp_safe_redirect( home_url( '/contact/' ), 301 );
        exit;
    }
}
add_action( 'template_redirect', 'vb_arms_redirect_page_id_to_contact', 0 );

// Redirect ?page_id=6 and ?page_id=7 to clean cart/checkout URLs (so old links open correctly)
function vb_arms_redirect_cart_checkout_permalinks() {
    if ( ! isset( $_GET['page_id'] ) ) {
        return;
    }
    $page_id = (int) $_GET['page_id'];
    if ( $page_id === 6 ) {
        wp_safe_redirect( home_url( '/cart/' ), 301 );
        exit;
    }
    if ( $page_id === 7 ) {
        wp_safe_redirect( home_url( '/checkout/' ), 301 );
        exit;
    }
}
add_action( 'template_redirect', 'vb_arms_redirect_cart_checkout_permalinks', 0 );

// Force cart and checkout links to use pretty URLs (so nav/buttons never point to ?page_id=6 or 7)
add_filter( 'woocommerce_get_cart_url', function( $url ) {
    return home_url( '/cart/' );
}, 10, 1 );
add_filter( 'woocommerce_get_checkout_url', function( $url ) {
    return home_url( '/checkout/' );
}, 10, 1 );

/**
 * VB Arms: Custom flat-rate shipping — Standard Shipping $15 only.
 *
 * Where to set the $15: WooCommerce → Settings → Shipping → open a zone (e.g. "United States"
 * or "Locations not covered by your other zones") → Add shipping method → "VB Arms Standard Shipping"
 * → set Cost to 15 → Save. If the zone has no methods (or only Local Pickup which we hide),
 * the cart still shows $15 via fallback below.
 */
function vb_arms_shipping_method_init() {
    if ( ! class_exists( 'WC_VB_Arms_Flat_Shipping' ) ) {
        class WC_VB_Arms_Flat_Shipping extends WC_Shipping_Method {
            public function __construct( $instance_id = 0 ) {
                $this->id                 = 'vb_arms_flat';
                $this->instance_id        = absint( $instance_id );
                $this->method_title       = __( 'VB Arms Standard Shipping', 'woocommerce' );
                $this->method_description = __( 'Flat rate shipping (e.g. when Lipseys or other supplier charges apply).', 'woocommerce' );
                $this->supports            = array( 'shipping-zones', 'instance-settings', 'instance-settings-modal' );
                $this->init();
            }

            public function init() {
                $this->init_form_fields();
                $this->init_settings();
                $this->title  = $this->get_option( 'title', __( 'Standard Shipping', 'woocommerce' ) );
                $this->cost   = $this->get_option( 'cost', 15 );
                $this->tax_status = $this->get_option( 'tax_status', 'taxable' );
                add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
            }

            public function init_form_fields() {
                $this->instance_form_fields = array(
                    'title' => array(
                        'title'       => __( 'Title', 'woocommerce' ),
                        'type'        => 'text',
                        'description' => __( 'Label shown to the customer.', 'woocommerce' ),
                        'default'     => __( 'Standard Shipping', 'woocommerce' ),
                        'desc_tip'    => true,
                    ),
                    'cost' => array(
                        'title'       => __( 'Cost', 'woocommerce' ),
                        'type'        => 'number',
                        'description' => __( 'Flat cost (e.g. 15 for $15).', 'woocommerce' ),
                        'default'     => 15,
                        'min'         => 0,
                        'step'        => 0.01,
                        'desc_tip'    => true,
                    ),
                );
            }

            public function calculate_shipping( $package = array() ) {
                $cost = is_numeric( $this->cost ) ? (float) $this->cost : 15;
                $this->add_rate( array(
                    'id'    => $this->get_rate_id(),
                    'label' => $this->title,
                    'cost'  => $cost,
                ) );
            }
        }
    }
}

add_action( 'woocommerce_shipping_init', 'vb_arms_shipping_method_init' );

function vb_arms_register_shipping_method( $methods ) {
    $methods['vb_arms_flat'] = 'WC_VB_Arms_Flat_Shipping';
    return $methods;
}

add_filter( 'woocommerce_shipping_methods', 'vb_arms_register_shipping_method' );

/**
 * VB Arms: Only Standard Shipping ($15) — remove Free shipping and Local Pickup so cart/checkout show $15.
 */
add_filter( 'woocommerce_package_rates', 'vb_arms_remove_free_and_local_pickup', 25, 2 );
function vb_arms_remove_free_and_local_pickup( $rates, $package ) {
    foreach ( $rates as $rate_id => $rate ) {
        if ( isset( $rate->method_id ) && ( $rate->method_id === 'free_shipping' || $rate->method_id === 'local_pickup' ) ) {
            unset( $rates[ $rate_id ] );
        }
    }
    return $rates;
}

/**
 * VB Arms: When no rates remain (no zone methods or we removed them), show $15 Standard Shipping.
 * Runs after removing Local Pickup so removing it doesn't leave "no shipping options".
 */
add_filter( 'woocommerce_package_rates', 'vb_arms_fallback_shipping_rate', 30, 2 );
function vb_arms_fallback_shipping_rate( $rates, $package ) {
    if ( ! empty( $rates ) ) {
        return $rates;
    }
    $cost = 15;
    $label = __( 'Standard Shipping', 'woocommerce' );
    $rates['vb_arms_flat:0'] = new WC_Shipping_Rate(
        'vb_arms_flat:0',
        $label,
        $cost,
        array(),
        'vb_arms_flat',
        0
    );
    return $rates;
}

/**
 * VB Arms: Always choose $15 Standard Shipping when it's available so the cart total includes it.
 */
add_filter( 'woocommerce_shipping_chosen_method', 'vb_arms_default_shipping_to_flat', 10, 3 );
function vb_arms_default_shipping_to_flat( $default, $rates, $chosen_method ) {
    if ( ! is_array( $rates ) ) {
        return $default;
    }
    foreach ( $rates as $rate_id => $rate ) {
        if ( isset( $rate->method_id ) && $rate->method_id === 'vb_arms_flat' ) {
            return $rate_id;
        }
    }
    return $default;
}

/**
 * VB Arms: 6% state tax only — remove county/other tax rates so only State Sales Tax (6%) shows.
 */
add_filter( 'woocommerce_matched_tax_rates', 'vb_arms_tax_rates_state_only', 10, 2 );
function vb_arms_tax_rates_state_only( $matched_tax_rates, $args ) {
    if ( empty( $args['country'] ) ) {
        return $matched_tax_rates;
    }
    // Only our 6% state tax — no county or other rates
    $matched_tax_rates = array(
        'vb_arms_state_6' => array(
            'rate'     => '6.0000',
            'label'    => __( 'State Sales Tax (6%)', 'woocommerce' ),
            'shipping' => 'yes',
            'compound' => 'no',
        ),
    );
    return $matched_tax_rates;
}

/**
 * VB Arms: Show tax as one line "Sales Tax" (combined state + county) on cart and checkout.
 * Force non-itemized display so WooCommerce shows a single tax row instead of County + State separately.
 */
add_filter( 'option_woocommerce_tax_total_display', 'vb_arms_single_tax_line_option', 10, 1 );
function vb_arms_single_tax_line_option( $value ) {
    if ( is_cart() || is_checkout() ) {
        return '';
    }
    return $value;
}

/**
 * VB Arms: Use label "Sales Tax" for the single tax line (only replace exact "Tax"/"VAT" on cart/checkout).
 */
add_filter( 'gettext', 'vb_arms_tax_label_sales_tax', 10, 3 );
function vb_arms_tax_label_sales_tax( $translated, $text, $domain ) {
    if ( $domain !== 'woocommerce' || ! ( is_cart() || is_checkout() ) ) {
        return $translated;
    }
    if ( $text === 'Tax' || $text === 'VAT' ) {
        return __( 'Sales Tax', 'storefront-child' );
    }
    return $translated;
}

/**
 * VB Arms: Flat $15 shipping line on cart (no calculator; shipping details at checkout).
 */
add_action( 'woocommerce_cart_calculate_fees', 'vb_arms_add_flat_shipping_cart_line', 10, 1 );
function vb_arms_add_flat_shipping_cart_line( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }
    if ( ! is_cart() ) {
        return;
    }
    $cart->add_fee( __( 'Shipping', 'woocommerce' ), 15, false );
}

/**
 * VB Arms: 1.5% credit/debit card processing fee — applied to subtotal + shipping + 6% tax.
 */
add_action( 'woocommerce_cart_calculate_fees', 'vb_arms_add_card_processing_fee', 20, 1 );
function vb_arms_add_card_processing_fee( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }
    $subtotal  = (float) $cart->get_subtotal();
    $shipping  = (float) $cart->get_shipping_total();
    if ( is_cart() && $shipping === 0.0 ) {
        $shipping = 15.0; // flat shipping for fee base
    }
    $tax_6     = 0.06 * $subtotal;
    $base      = $subtotal + $shipping + $tax_6;
    $fee       = round( 0.015 * $base, 2 );
    if ( $fee > 0 ) {
        $cart->add_fee( __( 'Card processing fee (1.5%)', 'woocommerce' ), $fee, false );
    }
}

/**
 * VB Arms: Show card processing fee percentage on its own line (less congested).
 */
add_filter( 'esc_html', 'vb_arms_fee_label_percentage_newline', 10, 2 );
function vb_arms_fee_label_percentage_newline( $safe_text, $text ) {
    if ( ( is_cart() || is_checkout() ) && $text === 'Card processing fee (1.5%)' ) {
        return 'Card processing fee<br><span class="fee-percentage-line">(1.5%)</span>';
    }
    return $safe_text;
}

/**
 * Cart page: do not show shipping row at all (shipping only on checkout).
 * Prevents the shipping block from being rendered in cart totals.
 */
add_filter( 'woocommerce_cart_ready_to_calc_shipping', 'vb_arms_hide_shipping_on_cart', 99 );
function vb_arms_hide_shipping_on_cart( $show_shipping ) {
    if ( is_cart() ) {
        return false;
    }
    return $show_shipping;
}

/** Cart page: treat cart as not needing shipping for display — shipping row is not output. */
add_filter( 'woocommerce_cart_needs_shipping', 'vb_arms_cart_hide_shipping_row', 99 );
function vb_arms_cart_hide_shipping_row( $needs_shipping ) {
    if ( is_cart() ) {
        return false;
    }
    return $needs_shipping;
}

/**
 * Cart shipping: show "Optional: add your address..." when dropdown is collapsed.
 * (When expanded, JS in cart template hides this text.)
 */
add_filter( 'woocommerce_cart_shipping_method_full_label', 'vb_arms_cart_shipping_label_calculated_in_box', 10, 2 );
function vb_arms_cart_shipping_label_calculated_in_box( $label, $method ) {
    if ( ! is_cart() ) {
        return $label;
    }
    return __( 'Optional: add your address here to pre-populate checkout.', 'woocommerce' );
}

add_filter( 'gettext', 'vb_arms_cart_shipping_to_text', 20, 3 );
function vb_arms_cart_shipping_to_text( $translated, $text, $domain ) {
    if ( $domain !== 'woocommerce' ) {
        return $translated;
    }
    // Cart only: remove "Shipping to [state]." line — they enter address in the box below
    if ( is_cart() && ( $text === 'Shipping to %s.' || $text === 'Shipping to %s' ) ) {
        return '';
    }
    // Cart + Checkout: "Change address" → "Add shipping address" (makes clear it's the dropdown)
    if ( ( is_cart() || is_checkout() ) && $text === 'Change address' ) {
        return __( 'Add shipping address', 'woocommerce' );
    }
    return $translated;
}

/**
 * VB Arms: Use non-cropped images in the cart to prevent long items from being cut off.
 */
add_filter( 'woocommerce_cart_item_thumbnail', 'vb_arms_use_full_aspect_ratio_thumbnails', 10, 3 );
function vb_arms_use_full_aspect_ratio_thumbnails( $thumbnail, $cart_item, $cart_item_key ) {
    $product = $cart_item['data'];
    return $product->get_image( 'medium' );
}

// Shop redirect DISABLED - now showing product catalog with custom template
// Uncomment below if you want to hide the shop page and redirect to Browse
/*
function vb_arms_redirect_shop_to_browse() {
    if ( function_exists( 'is_shop' ) && is_shop() ) {
        wp_safe_redirect( home_url( '/browse/' ), 301 );
        exit;
    }
}
add_action( 'template_redirect', 'vb_arms_redirect_shop_to_browse', 1 );
*/

/**
 * Above-the-fold products: firearms and scopes/optics only (NO triggers, parts, grips, holsters).
 * Returns product IDs to show at top of All Products and exclude from main grid.
 */
function vb_arms_get_featured_shop_product_ids() {
    $featured_ids = array();
    // Only pull from Firearms parent and Optics child (not triggers, parts, holsters, grips)
    $category_names = array(
        'Handguns' => 3,
        'Rifles' => 3,
        'Shotguns' => 3,
        'Optics' => 6,
    );
    
    foreach ( $category_names as $name => $limit ) {
        $term = get_term_by( 'name', $name, 'product_cat' );
        if ( ! $term ) {
            $term = get_term_by( 'slug', sanitize_title( $name ), 'product_cat' );
        }
        if ( ! $term ) {
            continue;
        }
        
        // Exclude terms: Triggers, Parts, Grips, Holsters (get their term_ids to exclude)
        $exclude_terms = array();
        foreach (array('Triggers', 'Parts', 'Grips', 'Holsters', 'HOLSTERS AND RELATED ITEMS') as $exclude_name) {
            $exc_term = get_term_by('name', $exclude_name, 'product_cat');
            if (!$exc_term) {
                $exc_term = get_term_by('slug', sanitize_title($exclude_name), 'product_cat');
            }
            if ($exc_term) {
                $exclude_terms[] = $exc_term->term_id;
            }
        }
        
        $tax_query = array(
            array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $term->term_id ),
        );
        if (!empty($exclude_terms)) {
            $tax_query[] = array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $exclude_terms,
                'operator' => 'NOT IN',
            );
        }
        
        $q = new WP_Query( array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'fields'         => 'ids',
            'tax_query'      => $tax_query,
            'meta_query'     => array(
                array( 'key' => '_stock_status', 'value' => 'instock', 'compare' => '=' ),
            ),
            'orderby'        => 'rand',
        ) );
        if ( ! empty( $q->posts ) ) {
            $featured_ids = array_merge( $featured_ids, $q->posts );
        }
    }
    $featured_ids = array_unique( array_filter( array_map( 'intval', $featured_ids ) ) );
    return array_slice( $featured_ids, 0, 18 );
}

/**
 * Expand shop search when the search term matches an accessory category (grips, triggers, holsters):
 * include all products in that category so "grips" shows grip products even if title doesn't contain "grips".
 *
 * @param string   $where Current WHERE clause.
 * @param WP_Query $query The query.
 * @return string
 */
function vb_arms_search_include_category_products_where( $where, $query ) {
    global $vb_arms_search_cat_slugs, $wpdb;
    if ( ! $query->is_main_query() || empty( $vb_arms_search_cat_slugs ) || ! is_array( $vb_arms_search_cat_slugs ) ) {
        return $where;
    }
    $slugs = array_map( 'sanitize_title', $vb_arms_search_cat_slugs );
    $ids   = get_posts( array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'posts_per_page' => -1,
        'no_found_rows'  => true,
        'tax_query'      => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $slugs,
                'operator' => 'IN',
            ),
        ),
    ) );
    remove_filter( 'posts_where', 'vb_arms_search_include_category_products_where', 10 );
    $vb_arms_search_cat_slugs = null;
    if ( empty( $ids ) ) {
        return $where;
    }
    $ids_str = implode( ',', array_map( 'intval', $ids ) );
    $where  .= " OR ({$wpdb->posts}.ID IN ({$ids_str}))";
    return $where;
}

/**
 * Unified shop query: per_page, orderby, hide no-image, main shop whitelist, Firearms subcats.
 * Priority 999 so it overrides WooCommerce default 12 per page.
 */
function vb_arms_shop_unified_pre_get_posts( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }
    if ( ! function_exists( 'is_shop' ) || ( ! is_shop() && ! is_product_category() ) ) {
        return;
    }

    // 1. Pagination: listen for $_GET['per_page'] (12, 50, 100, all)
    if ( isset( $_GET['per_page'] ) ) {
        $per_page = sanitize_text_field( wp_unslash( $_GET['per_page'] ) );
        $query->set( 'posts_per_page', ( $per_page === 'all' ) ? -1 : ( in_array( $per_page, array( '12', '50', '100' ), true ) ? (int) $per_page : 12 ) );
    } else {
        $query->set( 'posts_per_page', 12 );
    }

    // 2. Sorting: listen for $_GET['orderby']
    if ( isset( $_GET['orderby'] ) ) {
        $orderby = sanitize_text_field( wp_unslash( $_GET['orderby'] ) );
        switch ( $orderby ) {
            case 'price':
                $query->set( 'meta_key', '_price' );
                $query->set( 'orderby', 'meta_value_num' );
                $query->set( 'order', 'ASC' );
                break;
            case 'price-desc':
                $query->set( 'meta_key', '_price' );
                $query->set( 'orderby', 'meta_value_num' );
                $query->set( 'order', 'DESC' );
                break;
            case 'title':
                $query->set( 'orderby', 'title' );
                $query->set( 'order', 'ASC' );
                break;
            case 'date':
                $query->set( 'orderby', 'date' );
                $query->set( 'order', 'DESC' );
                break;
            default:
                $query->set( 'orderby', 'menu_order title' );
                $query->set( 'order', 'ASC' );
        }
    } else {
        $query->set( 'orderby', 'menu_order title' );
        $query->set( 'order', 'ASC' );
    }

    // 3. Strictly hide products without featured image
    $mq = $query->get( 'meta_query' ) ?: array();
    $mq[] = array( 'key' => '_thumbnail_id', 'value' => '0', 'compare' => '>', 'type' => 'NUMERIC' );
    $query->set( 'meta_query', $mq );

    // 4a. Search: when search term matches an accessory category slug, also include all products in that category (so "grips" shows grip products even if title doesn't say "grips")
    if ( is_shop() && is_search() && $query->get( 's' ) ) {
        $search_term = trim( strtolower( (string) $query->get( 's' ) ) );
        $term_to_slugs = array(
            'grips'    => array( 'grips', 'accessory-grips' ),
            'triggers' => array( 'triggers', 'accessory-triggers' ),
            'holsters' => array( 'holsters', 'accessory-holsters', 'holsters-and-related-items' ),
        );
        if ( isset( $term_to_slugs[ $search_term ] ) ) {
            global $vb_arms_search_cat_slugs;
            $vb_arms_search_cat_slugs = $term_to_slugs[ $search_term ];
            add_filter( 'posts_where', 'vb_arms_search_include_category_products_where', 10, 2 );
        }
    }

    // 4. Main shop only: only firearms / optics — IN allowed AND NOT IN disallowed (so Cleaning/Parts never show)
    if ( is_shop() && ! is_product_category() && ! is_search() ) {
        $allowed_slugs   = array( 'firearms', 'handguns', 'rifles', 'shotguns', 'optics', 'sights-lasers' );
        $disallowed_slugs = array(
            'cleaning', 'accessory-cleaning', 'cleaning-accessories',
            'parts', 'accessory-parts', 'accessory-barrels',
            'holsters', 'holsters-and-related-items', 'magazines', 'lights', 'bipods', 'grips', 'triggers',
            'mounts-rings', 'mounts-and-rings', 'sights-lasers',
            'accessories', 'suppressors', 'suppressor-accessories', 'other-accessories', 'other',
        );
        // Remove sights-lasers from disallowed (it's in allowed) — optics subcat is allowed
        $disallowed_slugs = array_diff( $disallowed_slugs, array( 'sights-lasers' ) );
        $query->set( 'tax_query', array(
            'relation' => 'AND',
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $allowed_slugs,
                'operator' => 'IN',
            ),
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => array_values( $disallowed_slugs ),
                'operator' => 'NOT IN',
            ),
        ) );
    }

    // 5. Firearms category: only Handguns, Rifles, Shotguns
    $cat_slug = $query->get( 'product_cat' );
    $is_firearms = ( is_string( $cat_slug ) && trim( $cat_slug ) === 'firearms' ) || ( is_array( $cat_slug ) && in_array( 'firearms', array_map( 'trim', $cat_slug ), true ) );
    if ( ! $is_firearms && is_product_category() ) {
        $tax_query = $query->get( 'tax_query' );
        if ( is_array( $tax_query ) ) {
            $firearms_term = get_term_by( 'slug', 'firearms', 'product_cat' );
            $firearms_id   = $firearms_term ? (int) $firearms_term->term_id : 0;
            foreach ( $tax_query as $clause ) {
                if ( empty( $clause['taxonomy'] ) || $clause['taxonomy'] !== 'product_cat' || empty( $clause['terms'] ) ) continue;
                $terms = is_array( $clause['terms'] ) ? $clause['terms'] : array( $clause['terms'] );
                if ( in_array( 'firearms', $terms, true ) || ( $firearms_id && in_array( $firearms_id, $terms, true ) ) ) {
                    $is_firearms = true; break;
                }
            }
        }
    }
    if ( $is_firearms ) {
        $query->set( 'tax_query', array(
            array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => array( 'handguns', 'rifles', 'shotguns' ), 'operator' => 'IN' ),
        ) );
    }
}
add_action( 'pre_get_posts', 'vb_arms_shop_unified_pre_get_posts', 999 );

/**
 * On main shop page, exclude featured product IDs from the main query so they only appear in the featured strip.
 */
function vb_arms_exclude_featured_from_shop_main_query( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }
    if ( ! function_exists( 'is_shop' ) || ! is_shop() || is_product_category() ) {
        return;
    }
    $featured = vb_arms_get_featured_shop_product_ids();
    if ( ! empty( $featured ) ) {
        $query->set( 'post__not_in', $featured );
    }
}
add_action( 'pre_get_posts', 'vb_arms_exclude_featured_from_shop_main_query', 20 );

/**
 * Display all prices 10% above MAP (stored price is MAP).
 * Applied to product get_price, get_regular_price, get_sale_price.
 */
function vb_arms_price_10_above_map( $price, $product ) {
    if ( $price === '' || $price === null ) {
        return $price;
    }
    $p = floatval( $price );
    if ( $p <= 0 ) {
        return $price;
    }
    return (string) round( $p * 1.10, 2 );
}
add_filter( 'woocommerce_product_get_price', 'vb_arms_price_10_above_map', 10, 2 );
add_filter( 'woocommerce_product_get_regular_price', 'vb_arms_price_10_above_map', 10, 2 );
add_filter( 'woocommerce_product_get_sale_price', 'vb_arms_price_10_above_map', 10, 2 );
// Variable products: apply to variation prices
add_filter( 'woocommerce_product_variation_get_price', 'vb_arms_price_10_above_map', 10, 2 );
add_filter( 'woocommerce_product_variation_get_regular_price', 'vb_arms_price_10_above_map', 10, 2 );
add_filter( 'woocommerce_product_variation_get_sale_price', 'vb_arms_price_10_above_map', 10, 2 );

/**
 * Shop: hide products that have no featured image (only show products with images).
 * Set to false to show ALL products (Lights, Magazines, etc.) with placeholder where no image.
 * Set to true once images are attached for all product types.
 */
add_filter( 'vb_arms_shop_hide_no_image', '__return_false' );

// Force Contact page to use Contact template (by page ID 1303, slug, or saved template)
function vb_arms_force_contact_template( $template ) {
    if ( ! is_page() ) {
        return $template;
    }
    $page_id    = get_queried_object_id();
    $saved_tpl = get_post_meta( $page_id, '_wp_page_template', true );
    $contact_slugs = array( 'contact', 'contact-us', 'contact-us-page' );
    $slug_ok   = in_array( get_post_field( 'post_name', $page_id ), $contact_slugs, true );
    $template_ok = ( $saved_tpl === 'vb-arms-contact.php' );
    $is_contact_page = ( (int) $page_id === 1303 );
    if ( $is_contact_page || $slug_ok || $template_ok ) {
        $contact_template = get_stylesheet_directory() . '/vb-arms-contact.php';
        if ( file_exists( $contact_template ) ) {
            return $contact_template;
        }
    }
    return $template;
}
add_filter( 'template_include', 'vb_arms_force_contact_template', 99 );

// Cart page: use VB Arms full cart template (nav + modern cart layout + footer)
function vb_arms_force_cart_template( $template ) {
    if ( is_cart() ) {
        $cart_template = get_stylesheet_directory() . '/woocommerce/cart.php';
        if ( file_exists( $cart_template ) ) {
            return $cart_template;
        }
    }
    return $template;
}
add_filter( 'template_include', 'vb_arms_force_cart_template', 100 );

function vb_arms_force_checkout_template( $template ) {
    if ( is_checkout() ) {
        $checkout_template = get_stylesheet_directory() . '/woocommerce/checkout-page.php';
        if ( file_exists( $checkout_template ) ) {
            return $checkout_template;
        }
    }
    return $template;
}
add_filter( 'template_include', 'vb_arms_force_checkout_template', 100 );

// Redirect My Account → Orders endpoint to our styled Orders page (/orders/)
add_action( 'template_redirect', 'vb_arms_redirect_account_orders_to_orders_page', 5 );
function vb_arms_redirect_account_orders_to_orders_page() {
    if ( ! function_exists( 'is_account_page' ) || ! function_exists( 'is_wc_endpoint_url' ) ) {
        return;
    }
    if ( is_account_page() && is_wc_endpoint_url( 'orders' ) ) {
        $orders_url = home_url( '/orders/' );
        if ( $orders_url && trim( $orders_url ) !== '' ) {
            wp_safe_redirect( $orders_url, 302 );
            exit;
        }
    }
}

// Order history page: use VB Arms orders template for /orders/ or page_id=8
function vb_arms_force_orders_template( $template ) {
    $orders_page_id = 8;
    $is_orders_page = is_page( $orders_page_id ) || is_page( 'orders' );
    if ( ! $is_orders_page && isset( $_GET['page_id'] ) && (int) $_GET['page_id'] === $orders_page_id ) {
        $is_orders_page = true;
    }
    if ( $is_orders_page ) {
        $orders_template = get_stylesheet_directory() . '/vb-arms-orders.php';
        if ( file_exists( $orders_template ) ) {
            return $orders_template;
        }
    }
    return $template;
}
add_filter( 'template_include', 'vb_arms_force_orders_template', 99 );

// Remove all Storefront styles on custom template pages
function vb_arms_remove_storefront_styles() {
    if (is_page_template('vb-arms-splash-redesigned.php')) {
        wp_dequeue_style('storefront-style');
        wp_dequeue_style('storefront-woocommerce-style');
        wp_dequeue_style('storefront-icons');
        wp_dequeue_style('storefront-fonts');
    }
}
add_action('wp_enqueue_scripts', 'vb_arms_remove_storefront_styles', 999);

// Remove Storefront header/footer on custom template and on shop (custom archive has its own header/footer)
function vb_arms_remove_storefront_elements() {
    $remove_on = is_page_template('vb-arms-splash-redesigned.php') || is_shop() || is_product_category() || is_product() || is_cart() || is_checkout();
    if ($remove_on) {
        // Remove header
        remove_action('storefront_header', 'storefront_header_container', 0);
        remove_action('storefront_header', 'storefront_skip_links', 5);
        remove_action('storefront_header', 'storefront_site_branding', 20);
        remove_action('storefront_header', 'storefront_primary_navigation', 50);
        remove_action('storefront_header', 'storefront_header_container_close', 41);
        
        // Remove footer (shop page uses archive-product.php custom footer)
        remove_action('storefront_footer', 'storefront_footer_widgets', 10);
        remove_action('storefront_footer', 'storefront_credit', 20);
        
        // Remove breadcrumbs
        remove_action('storefront_before_content', 'woocommerce_breadcrumb', 10);
    }
}
add_action('wp', 'vb_arms_remove_storefront_elements', 1);

// Add minimal reset to hide any remaining Storefront elements
function vb_arms_minimal_reset() {
    if (is_page_template('vb-arms-splash-redesigned.php')) {
        ?>
        <style>
            /* Hide Storefront elements completely */
            body.page-template-vb-arms-splash-redesigned-php .site-header,
            body.page-template-vb-arms-splash-redesigned-php .site-footer,
            body.page-template-vb-arms-splash-redesigned-php .storefront-primary-navigation,
            body.page-template-vb-arms-splash-redesigned-php .site-branding,
            body.page-template-vb-arms-splash-redesigned-php .storefront-breadcrumb {
                display: none !important;
                visibility: hidden !important;
            }
            
            /* Ensure body has no margins */
            body.page-template-vb-arms-splash-redesigned-php {
                margin: 0 !important;
                padding: 0 !important;
            }
        </style>
        <?php
    }
    // Shop, product category, single product, cart, checkout: hide theme header/footer so custom templates are the only ones
    if (is_shop() || is_product_category() || is_product() || is_cart() || is_checkout()) {
        ?>
        <style>
            body.woocommerce-shop .site-header,
            body.woocommerce-shop .site-footer,
            body.post-type-archive-product .site-header,
            body.post-type-archive-product .site-footer,
            body.tax-product_cat .site-header,
            body.tax-product_cat .site-footer,
            body.single-product .site-header,
            body.single-product .site-footer,
            body.woocommerce-cart .site-header,
            body.woocommerce-cart .site-footer,
            body.woocommerce-checkout .site-header,
            body.woocommerce-checkout .site-footer {
                display: none !important;
            }
        </style>
        <?php
    }
}
add_action('wp_head', 'vb_arms_minimal_reset', 999);

// Remove focus outline/ring on all buttons and interactive elements site-wide (no purple/blue ring on click)
function vb_arms_remove_focus_outline() {
    ?>
    <style id="vb-arms-no-focus-outline">
        *:focus,
        *:focus-visible {
            outline: none !important;
            box-shadow: none !important;
        }
        * {
            -webkit-tap-highlight-color: transparent !important;
            tap-highlight-color: transparent !important;
        }
    </style>
    <?php
}
add_action('wp_head', 'vb_arms_remove_focus_outline', 1000);

// WooCommerce notices site-wide: sleek, transparent, pill-shaped, thin border (product added, errors, etc.)
function vb_arms_woocommerce_notices_style() {
    if ( ! function_exists( 'WC' ) ) {
        return;
    }
    ?>
    <style id="vb-arms-woo-notices">
        .woocommerce-notices-wrapper {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            text-align: center !important;
        }
        .woocommerce .woocommerce-message,
        .woocommerce .woocommerce-error,
        .woocommerce .woocommerce-info,
        .woocommerce-notices-wrapper .woocommerce-message,
        .woocommerce-notices-wrapper .woocommerce-error,
        .woocommerce-notices-wrapper .woocommerce-info {
            background: rgba(10, 10, 10, 0.85) !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(0, 255, 148, 0.5) !important;
            border-radius: 50px !important;
            padding: 0.5rem 1rem !important;
            margin: 0 0 0.5rem 0 !important;
            color: rgba(255, 255, 255, 0.95) !important;
            font-size: 1.05rem !important;
            font-weight: 500 !important;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.4) !important;
            list-style: none !important;
            text-align: center !important;
        }
        .woocommerce .woocommerce-message,
        .woocommerce-notices-wrapper .woocommerce-message {
            border-color: rgba(0, 255, 148, 0.5) !important;
        }
        .woocommerce .woocommerce-error,
        .woocommerce-notices-wrapper .woocommerce-error {
            border-color: rgba(255, 120, 120, 0.6) !important;
            color: rgba(255, 220, 220, 0.98) !important;
        }
        .woocommerce .woocommerce-info,
        .woocommerce-notices-wrapper .woocommerce-info {
            border-color: rgba(0, 255, 148, 0.45) !important;
        }
        .woocommerce .woocommerce-message a,
        .woocommerce .woocommerce-error a,
        .woocommerce .woocommerce-info a,
        .woocommerce .woocommerce-message .button,
        .woocommerce .woocommerce-error .button,
        .woocommerce .woocommerce-info .button,
        .woocommerce-notices-wrapper .woocommerce-message a,
        .woocommerce-notices-wrapper .woocommerce-error a,
        .woocommerce-notices-wrapper .woocommerce-info a,
        .woocommerce-notices-wrapper .woocommerce-message .button,
        .woocommerce-notices-wrapper .woocommerce-error .button,
        .woocommerce-notices-wrapper .woocommerce-info .button {
            display: inline-block !important;
            font-size: 0.9rem !important;
            background: transparent !important;
            border: 1px solid rgba(0, 255, 148, 0.5) !important;
            color: #00ff94 !important;
            border-radius: 50px !important;
            padding: 0.4rem 0.9rem !important;
            font-size: 0.8rem !important;
            font-weight: 600 !important;
            text-decoration: none !important;
            transition: 0.3s;
        }
        .woocommerce .woocommerce-message a:hover,
        .woocommerce .woocommerce-error a:hover,
        .woocommerce .woocommerce-info a:hover,
        .woocommerce .woocommerce-message .button:hover,
        .woocommerce .woocommerce-error .button:hover,
        .woocommerce .woocommerce-info .button:hover,
        .woocommerce-notices-wrapper .woocommerce-message a:hover,
        .woocommerce-notices-wrapper .woocommerce-error a:hover,
        .woocommerce-notices-wrapper .woocommerce-info a:hover,
        .woocommerce-notices-wrapper .woocommerce-message .button:hover,
        .woocommerce-notices-wrapper .woocommerce-error .button:hover,
        .woocommerce-notices-wrapper .woocommerce-info .button:hover {
            background: rgba(0, 255, 148, 0.12) !important;
            border-color: #00ff94 !important;
            text-decoration: none !important;
        }
        .woocommerce .woocommerce-message li,
        .woocommerce .woocommerce-error li,
        .woocommerce .woocommerce-info li,
        .woocommerce-notices-wrapper .woocommerce-message li,
        .woocommerce-notices-wrapper .woocommerce-error li,
        .woocommerce-notices-wrapper .woocommerce-info li {
            list-style: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        /* On cart page: hide "View cart" link/button inside notices (user is already on cart) */
        body.woocommerce-cart .woocommerce-message a,
        body.woocommerce-cart .woocommerce-message .button,
        body.woocommerce-cart .woocommerce-error a,
        body.woocommerce-cart .woocommerce-error .button,
        body.woocommerce-cart .woocommerce-info a,
        body.woocommerce-cart .woocommerce-info .button {
            display: none !important;
        }
        /* On cart page: remove white circle + exclamation icon from "Have a coupon?" notice */
        body.woocommerce-cart .woocommerce-info::before,
        body.woocommerce-cart .woocommerce-info::after {
            display: none !important;
            content: none !important;
            visibility: hidden !important;
        }
    </style>
    <?php
}
add_action( 'wp_head', 'vb_arms_woocommerce_notices_style', 1001 );

// VB ARMS logo text in green at top of every page (nav bar)
function vb_arms_nav_logo_text_green() {
    ?>
    <style id="vb-arms-nav-logo-green">
    .nav-header .nav-logo-text { color: #00ff94 !important; }
    </style>
    <?php
}
add_action( 'wp_head', 'vb_arms_nav_logo_text_green', 5 );

// Mobile nav: same as shop page on all pages (global .nav-header)
function vb_arms_mobile_nav_shop_pages() {
    ?>
    <style id="vb-arms-mobile-nav-shop">
    @media (max-width: 768px) {
        .nav-header {
            padding: 0 1rem !important;
            height: 56px !important;
            min-height: 56px !important;
        }
        .nav-header .nav-logo img {
            height: 32px !important;
        }
        .nav-header .nav-logo-text {
            font-size: 0.85rem !important;
        }
        .nav-header .nav-actions {
            gap: 0.35rem !important;
        }
        .nav-header .nav-contact-btn {
            padding: 0.4rem 0.5rem !important;
            font-size: 0.68rem !important;
            width: auto !important;
            min-width: 0 !important;
        }
        .nav-header .nav-cart-pill {
            padding: 0.4rem 0.6rem !important;
            font-size: 0.75rem !important;
        }
    }
    @media (max-width: 480px) {
        .nav-header {
            padding: 0 0.6rem !important;
        }
        .nav-header .nav-logo-text {
            display: none !important;
        }
        .nav-header .nav-logo img {
            height: 28px !important;
        }
        .nav-header .nav-contact-btn {
            padding: 0.35rem 0.4rem !important;
            font-size: 0.62rem !important;
        }
        .nav-header .nav-actions {
            gap: 0.25rem !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'vb_arms_mobile_nav_shop_pages', 999 );

// Mobile footer: keep Privacy Policy, Refund & Returns, Terms of Service on one line
function vb_arms_mobile_footer_links() {
    ?>
    <style id="vb-arms-mobile-footer-links">
    @media (max-width: 768px) {
        .vb-footer,
        .cart-footer-wrap,
        footer[style*="padding"] {
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
        }
        .footer-links,
        .vb-footer .footer-links,
        .cart-footer-wrap .footer-links,
        div[class="footer-links"] {
            flex-wrap: nowrap !important;
            gap: 0.75rem !important;
            justify-content: center !important;
            max-width: 100% !important;
        }
        .footer-link,
        .vb-footer .footer-link,
        .cart-footer-wrap .footer-link {
            font-size: 0.68rem !important;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
        }
    }
    @media (max-width: 400px) {
        .footer-link,
        .vb-footer .footer-link,
        .cart-footer-wrap .footer-link {
            font-size: 0.62rem !important;
        }
        .footer-links,
        .vb-footer .footer-links,
        .cart-footer-wrap .footer-links {
            gap: 0.5rem !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'vb_arms_mobile_footer_links', 1000 );

// Style WooCommerce Cart Page - Fallback (skipped when VB Arms cart.php template is used)
function vb_arms_cart_page_styles() {
    if ( defined( 'VB_ARMS_CART_TEMPLATE_LOADED' ) && VB_ARMS_CART_TEMPLATE_LOADED ) {
        return;
    }
    if ( is_cart() || is_checkout() ) {
        ?>
        <style>
            :root {
                --primary-bg: #0a0a0a;
                --secondary-bg: #141414;
                --card-bg: rgba(20, 20, 20, 0.6);
                --text-primary: #ffffff;
                --text-secondary: rgba(255, 255, 255, 0.75);
                --text-muted: rgba(255, 255, 255, 0.5);
                --accent-green: #00ff94;
                --accent-green-hover: #00e085;
                --accent-green-soft: rgba(0, 255, 148, 0.15);
                --border-subtle: rgba(255, 255, 255, 0.1);
                --border-medium: rgba(255, 255, 255, 0.15);
                --border-green: rgba(0, 255, 148, 0.4);
                --glass: rgba(255, 255, 255, 0.04);
                --glass-hover: rgba(255, 255, 255, 0.08);
                --error-bg: rgba(255, 100, 100, 0.1);
                --error-border: rgba(255, 100, 100, 0.4);
                --error-text: #ff9999;
                --accent-bg: #1a1a1a;
            }

            /* Add to Cart buttons */
            .woocommerce .single_add_to_cart_button,
            .woocommerce button.single_add_to_cart_button,
            .woocommerce .add_to_cart_button,
            .woocommerce a.add_to_cart_button {
                background: transparent !important;
                border: 1px solid var(--accent-green) !important;
                color: var(--accent-green) !important;
                border-radius: 8px !important;
                padding: 0.75rem 1.75rem !important;
                font-size: 0.875rem !important;
                font-weight: 600 !important;
                text-align: center !important;
                outline: none !important;
                -webkit-tap-highlight-color: transparent;
            }
            .woocommerce .single_add_to_cart_button:hover,
            .woocommerce button.single_add_to_cart_button:hover,
            .woocommerce .add_to_cart_button:hover,
            .woocommerce a.add_to_cart_button:hover {
                background: var(--accent-green-soft) !important;
                border-color: var(--accent-green) !important;
                color: var(--accent-green) !important;
                transform: translateY(-1px);
            }
            .woocommerce .single_add_to_cart_button:focus,
            .woocommerce button.single_add_to_cart_button:focus,
            .woocommerce .add_to_cart_button:focus,
            .woocommerce a.add_to_cart_button:focus {
                outline: none !important;
                box-shadow: none !important;
            }

            body.woocommerce-cart,
            body.woocommerce-checkout,
            body.woocommerce-cart .site-content,
            body.woocommerce-checkout .site-content {
                background: var(--primary-bg) !important;
                color: var(--text-primary) !important;
                font-family: 'Space Grotesk', -apple-system, BlinkMacSystemFont, sans-serif !important;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
            }
            body.woocommerce-cart .site-main,
            body.woocommerce-checkout .site-main {
                background: var(--primary-bg) !important;
            }

            .woocommerce-cart table.cart,
            .woocommerce table.cart {
                background: var(--card-bg) !important;
                border: 1px solid var(--border-medium) !important;
                border-radius: 16px !important;
                overflow: hidden;
                backdrop-filter: blur(20px);
                width: 100% !important;
            }
            .woocommerce-cart table.cart thead th,
            .woocommerce table.cart thead th {
                background: rgba(0, 255, 148, 0.08) !important;
                color: var(--accent-green) !important;
                border-bottom: 1px solid var(--border-medium) !important;
                padding: 1.25rem 1rem !important;
                font-weight: 600 !important;
                font-size: 0.8125rem !important;
                text-transform: uppercase;
                letter-spacing: 0.1em;
            }
            .woocommerce-cart table.cart tbody td,
            .woocommerce table.cart tbody td {
                background: transparent !important;
                border-bottom: 1px solid var(--border-subtle) !important;
                color: var(--text-primary) !important;
                padding: 1.5rem 1rem !important;
            }
            .woocommerce-cart table.cart tbody tr:hover,
            .woocommerce table.cart tbody tr:hover {
                background: var(--glass) !important;
            }
            .woocommerce-cart .product-thumbnail img {
                border-radius: 12px !important;
                border: 1px solid var(--border-subtle) !important;
                width: 100px !important;
                height: 100px !important;
                object-fit: cover !important;
                transition: transform 0.3s ease;
            }
            .woocommerce-cart .product-thumbnail a:hover img {
                transform: scale(1.05);
            }
            .woocommerce-cart .product-name a {
                color: var(--text-primary) !important;
                text-decoration: none !important;
                font-weight: 600 !important;
                transition: color 0.3s ease;
            }
            .woocommerce-cart .product-name a:hover {
                color: var(--accent-green) !important;
            }
            .woocommerce-cart .product-price .amount,
            .woocommerce-cart .product-subtotal .amount {
                font-family: 'JetBrains Mono', monospace !important;
                font-weight: 700 !important;
                font-size: 1.125rem !important;
            }
            .woocommerce-cart .product-price .amount { color: var(--accent-green) !important; }
            .woocommerce-cart .product-subtotal .amount { color: var(--text-primary) !important; }
            .woocommerce-cart .quantity input.qty {
                background: var(--secondary-bg) !important;
                border: 1px solid var(--border-medium) !important;
                color: var(--text-primary) !important;
                border-radius: 8px !important;
                padding: 0.625rem 0.75rem !important;
                width: 80px !important;
                font-family: 'JetBrains Mono', monospace !important;
                font-weight: 500 !important;
                text-align: center !important;
                transition: all 0.3s ease;
            }
            .woocommerce-cart .quantity input.qty:focus {
                outline: none !important;
                border-color: var(--border-green) !important;
                background: rgba(0, 255, 148, 0.05) !important;
            }
            .woocommerce-cart .product-remove a {
                color: var(--error-text) !important;
                background: var(--error-bg) !important;
                border: 1px solid var(--error-border) !important;
                border-radius: 8px !important;
                width: 36px !important;
                height: 36px !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                transition: all 0.3s ease !important;
                font-size: 1.25rem !important;
            }
            .woocommerce-cart .product-remove a:hover {
                background: rgba(255, 100, 100, 0.2) !important;
                border-color: rgba(255, 100, 100, 0.6) !important;
                transform: scale(1.05) !important;
            }
            .woocommerce-cart .actions {
                padding: 1.5rem !important;
                background: rgba(0, 0, 0, 0.3) !important;
                border-top: 1px solid var(--border-subtle) !important;
            }
            .woocommerce-cart .coupon input.input-text {
                background: var(--secondary-bg) !important;
                border: 1px solid var(--border-medium) !important;
                color: var(--text-primary) !important;
                border-radius: 8px !important;
                padding: 0.625rem 1rem !important;
                transition: all 0.3s ease;
            }
            .woocommerce-cart .coupon input.input-text:focus {
                outline: none !important;
                border-color: var(--border-green) !important;
                background: rgba(0, 255, 148, 0.05) !important;
            }
            .woocommerce-cart .coupon .button {
                background: transparent !important;
                border: 1px solid var(--border-green) !important;
                color: var(--accent-green) !important;
                border-radius: 8px !important;
                padding: 0.625rem 1.25rem !important;
                font-size: 0.875rem !important;
                font-weight: 600 !important;
                transition: all 0.3s ease;
            }
            .woocommerce-cart .coupon .button:hover {
                background: var(--accent-green-soft) !important;
                transform: translateY(-1px);
            }
            .woocommerce-cart .button[name="update_cart"] {
                background: transparent !important;
                border: 1px solid var(--border-medium) !important;
                color: var(--text-secondary) !important;
                border-radius: 8px !important;
                padding: 0.625rem 1.5rem !important;
                font-size: 0.875rem !important;
                font-weight: 600 !important;
                transition: all 0.3s ease;
            }
            .woocommerce-cart .button[name="update_cart"]:hover {
                background: var(--glass-hover) !important;
                color: var(--text-primary) !important;
                border-color: var(--border-green) !important;
            }
            .woocommerce-cart .cart_totals {
                background: var(--card-bg) !important;
                border: 1px solid var(--border-medium) !important;
                border-radius: 16px !important;
                padding: 2rem !important;
                backdrop-filter: blur(20px);
            }
            .woocommerce-cart .cart_totals h2 {
                color: var(--text-primary) !important;
                font-size: 1.25rem !important;
                font-weight: 700 !important;
                margin-bottom: 1.5rem !important;
                padding-bottom: 1rem !important;
                border-bottom: 1px solid var(--border-medium) !important;
            }
            .woocommerce-cart .cart_totals table th,
            .woocommerce-cart .cart_totals table td {
                color: var(--text-secondary) !important;
                border-bottom: 1px solid var(--border-subtle) !important;
                padding: 0.875rem 0 !important;
                font-size: 0.9375rem !important;
            }
            .woocommerce-cart .cart_totals table td {
                text-align: right;
                font-family: 'JetBrains Mono', monospace;
                font-weight: 600;
            }
            .woocommerce-cart .cart_totals .order-total th,
            .woocommerce-cart .cart_totals .order-total td {
                color: var(--accent-green) !important;
                font-size: 1.5rem !important;
                font-weight: 700 !important;
                border-top: 2px solid var(--border-green) !important;
                border-bottom: none !important;
                padding-top: 1.25rem !important;
            }
            .woocommerce-cart .checkout-button {
                background: var(--accent-green) !important;
                color: #0a0a0a !important;
                border: none !important;
                border-radius: 12px !important;
                padding: 1rem 1.5rem !important;
                font-weight: 700 !important;
                font-size: 1rem !important;
                width: 100% !important;
                margin-top: 1.5rem !important;
                transition: all 0.3s ease !important;
                text-decoration: none !important;
            }
            .woocommerce-cart .checkout-button:hover {
                background: var(--accent-green-hover) !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 8px 25px rgba(0, 255, 148, 0.3) !important;
            }
            .woocommerce-cart .cart-empty,
            .woocommerce .cart-empty {
                text-align: center !important;
                padding: 4rem 2rem !important;
                color: var(--text-secondary) !important;
                background: var(--card-bg) !important;
                border: 1px solid var(--border-medium) !important;
                border-radius: 16px !important;
                margin: 2rem 0 !important;
                backdrop-filter: blur(20px);
            }
            .woocommerce-cart .cart-empty::before,
            .woocommerce .cart-empty::before {
                content: '🛒';
                display: block;
                font-size: 4rem;
                margin-bottom: 1rem;
                opacity: 0.5;
            }
            /* Match "Your cart is currently empty" pill: transparent background, green border */
            .woocommerce-cart .return-to-shop a {
                display: inline-block !important;
                padding: 0.875rem 2rem !important;
                background: transparent !important;
                border: 1px solid var(--accent-green) !important;
                color: var(--accent-green) !important;
                border-radius: 50px !important;
                text-decoration: none !important;
                font-weight: 600 !important;
                transition: all 0.3s ease !important;
            }
            .woocommerce-cart .return-to-shop a:hover {
                background: rgba(0, 255, 148, 0.1) !important;
                color: var(--accent-green) !important;
                transform: translateY(-2px);
            }
            .woocommerce-cart .entry-title,
            .woocommerce .entry-title,
            .woocommerce-cart h1,
            .woocommerce h1.page-title {
                color: var(--text-primary) !important;
                font-weight: 700 !important;
            }
            body.woocommerce-cart .site-header,
            body.woocommerce-checkout .site-header {
                background: rgba(15, 15, 15, 0.95) !important;
            }
            body.woocommerce-cart .site-content,
            body.woocommerce-checkout .site-content {
                padding-top: 2rem !important;
            }
            @media (max-width: 768px) {
                .woocommerce-cart table.cart { font-size: 0.9rem !important; }
                .woocommerce-cart table.cart thead th,
                .woocommerce-cart table.cart tbody td { padding: 1rem 0.5rem !important; }
                .woocommerce-cart .product-thumbnail img { width: 80px !important; height: 80px !important; }
            }

            /* ============================================
               CHECKOUT PAGE SPECIFIC STYLING
               ============================================ */
            
            /* Checkout Form Container */
            .woocommerce-checkout .woocommerce-checkout,
            .woocommerce-checkout form.checkout {
                background: var(--primary-bg) !important;
                color: var(--text-primary) !important;
            }

            /* Checkout Page Title */
            .woocommerce-checkout .entry-title,
            .woocommerce-checkout h1.page-title {
                color: var(--text-primary) !important;
                text-align: center;
                font-size: 2.5rem;
                margin-bottom: 2rem;
                padding-top: 100px;
            }

            /* Checkout Form Sections */
            .woocommerce-checkout .woocommerce-billing-fields,
            .woocommerce-checkout .woocommerce-shipping-fields,
            .woocommerce-checkout .woocommerce-additional-fields {
                background: var(--glass) !important;
                border: 1px solid var(--glass-border) !important;
                border-radius: 16px !important;
                padding: 2rem !important;
                margin-bottom: 2rem !important;
                backdrop-filter: blur(20px);
            }

            /* Section Headings */
            .woocommerce-checkout h3 {
                color: var(--accent-green) !important;
                font-size: 1.5rem !important;
                margin-bottom: 1.5rem !important;
                border-bottom: 2px solid var(--accent-green) !important;
                padding-bottom: 0.75rem !important;
            }

            /* Form Labels */
            .woocommerce-checkout label {
                color: var(--text-primary) !important;
                font-weight: 500 !important;
                margin-bottom: 0.5rem !important;
                display: block !important;
            }

            .woocommerce-checkout label.required::after {
                color: var(--accent-green) !important;
            }

            /* Form Inputs */
            .woocommerce-checkout input[type="text"],
            .woocommerce-checkout input[type="email"],
            .woocommerce-checkout input[type="tel"],
            .woocommerce-checkout input[type="password"],
            .woocommerce-checkout select,
            .woocommerce-checkout textarea {
                background: var(--accent-bg) !important;
                border: 1px solid var(--glass-border) !important;
                color: var(--text-primary) !important;
                border-radius: 8px !important;
                padding: 0.75rem 1rem !important;
                width: 100% !important;
                transition: all 0.3s ease !important;
            }

            .woocommerce-checkout input[type="text"]:focus,
            .woocommerce-checkout input[type="email"]:focus,
            .woocommerce-checkout input[type="tel"]:focus,
            .woocommerce-checkout input[type="password"]:focus,
            .woocommerce-checkout select:focus,
            .woocommerce-checkout textarea:focus {
                border-color: var(--accent-green) !important;
                outline: none !important;
                box-shadow: 0 0 0 3px rgba(0, 255, 148, 0.1) !important;
            }

            /* Checkboxes and Radio Buttons */
            .woocommerce-checkout input[type="checkbox"],
            .woocommerce-checkout input[type="radio"] {
                accent-color: var(--accent-green) !important;
                width: 20px !important;
                height: 20px !important;
                margin-right: 0.5rem !important;
            }

            .woocommerce-checkout .form-row label.checkbox {
                color: var(--text-secondary) !important;
                font-weight: 400 !important;
            }

            /* Shipping Options */
            .woocommerce-checkout .woocommerce-shipping-methods {
                list-style: none !important;
                padding: 0 !important;
            }

            .woocommerce-checkout .woocommerce-shipping-methods li {
                background: var(--accent-bg) !important;
                border: 1px solid var(--accent-green) !important;
                border-radius: 8px !important;
                padding: 1rem !important;
                margin-bottom: 0.5rem !important;
                color: var(--text-primary) !important;
                text-align: center !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }

            .woocommerce-checkout .woocommerce-shipping-methods li label {
                text-align: center !important;
                width: 100%;
                margin: 0 !important;
                cursor: pointer;
            }

            .woocommerce-checkout .woocommerce-shipping-methods li:hover {
                border-color: var(--accent-green) !important;
                background: rgba(0, 255, 148, 0.05) !important;
            }

            /* Payment Options Section */
            .woocommerce-checkout #payment {
                background: var(--glass) !important;
                border: 1px solid var(--glass-border) !important;
                border-radius: 16px !important;
                padding: 2rem !important;
                margin-top: 2rem !important;
                backdrop-filter: blur(20px);
            }

            .woocommerce-checkout #payment .payment_methods {
                list-style: none !important;
                padding: 0 !important;
                margin-bottom: 1.5rem !important;
            }

            .woocommerce-checkout #payment .payment_methods li {
                background: var(--accent-bg) !important;
                border: 1px solid var(--glass-border) !important;
                border-radius: 8px !important;
                padding: 1rem !important;
                margin-bottom: 0.5rem !important;
            }

            .woocommerce-checkout #payment .payment_methods li:hover {
                border-color: var(--accent-green) !important;
            }

            .woocommerce-checkout #payment .payment_methods li label {
                color: var(--text-primary) !important;
                font-weight: 600 !important;
            }

            /* Payment Method Description */
            .woocommerce-checkout .payment_box {
                background: var(--secondary-bg) !important;
                border: 1px solid var(--glass-border) !important;
                border-radius: 8px !important;
                padding: 1rem !important;
                margin-top: 0.5rem !important;
                color: var(--text-secondary) !important;
            }

            /* Place Order Button: transparent, thin green border (match cart / normal style) */
            .woocommerce-checkout #place_order {
                background: transparent !important;
                color: var(--accent-green) !important;
                border: 1px solid var(--accent-green) !important;
                border-radius: 50px !important;
                padding: 0.85rem 2rem !important;
                font-size: 0.85rem !important;
                font-weight: 700 !important;
                width: 100% !important;
                text-transform: uppercase !important;
                letter-spacing: 0.15em !important;
                transition: all 0.3s ease !important;
                cursor: pointer !important;
            }

            .woocommerce-checkout #place_order:hover {
                background: rgba(0, 255, 148, 0.1) !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 5px 15px rgba(0, 255, 148, 0.2) !important;
            }

            /* Order Summary (Right Sidebar) */
            .woocommerce-checkout .woocommerce-checkout-review-order,
            .woocommerce-checkout #order_review {
                background: var(--glass) !important;
                border: 1px solid var(--glass-border) !important;
                border-radius: 16px !important;
                padding: 2rem !important;
                backdrop-filter: blur(20px);
            }

            .woocommerce-checkout #order_review_heading {
                color: var(--accent-green) !important;
                font-size: 1.5rem !important;
                margin-bottom: 1.5rem !important;
                border-bottom: 2px solid var(--accent-green) !important;
                padding-bottom: 0.75rem !important;
            }

            /* Order Summary Table */
            .woocommerce-checkout .shop_table {
                background: transparent !important;
                border: none !important;
                width: 100% !important;
            }

            .woocommerce-checkout .shop_table thead th {
                background: rgba(0, 255, 148, 0.1) !important;
                color: var(--accent-green) !important;
                border-bottom: 2px solid var(--accent-green) !important;
                padding: 1rem !important;
                font-weight: 600 !important;
            }
            .woocommerce-checkout .shop_table thead th:last-child {
                padding-right: 1.5rem !important;
                text-align: center !important;
            }

            .woocommerce-checkout .shop_table tbody td {
                background: transparent !important;
                border-bottom: none !important;
                color: var(--text-primary) !important;
                padding: 1rem !important;
            }

            .woocommerce-checkout .shop_table tbody .product-name {
                color: var(--text-primary) !important;
                font-weight: 500 !important;
            }

            .woocommerce-checkout .shop_table tbody .product-total {
                color: var(--accent-green) !important;
                font-weight: 600 !important;
                padding-right: 1.5rem !important;
                text-align: center !important;
            }

            /* Order Summary Totals: one continuous divider per row (on tr so it never splits) */
            .woocommerce-checkout .shop_table {
                border-collapse: collapse !important;
            }
            .woocommerce-checkout .shop_table tfoot th,
            .woocommerce-checkout .shop_table tfoot td {
                color: var(--text-secondary) !important;
                border: none !important;
                padding-top: 0.75rem !important;
                padding-bottom: 0.75rem !important;
                padding-left: 1rem !important;
                padding-right: 1.5rem !important;
            }
            .woocommerce-checkout .shop_table tfoot tr {
                border-bottom: 1px solid var(--border-subtle) !important;
            }
            .woocommerce-checkout .shop_table tfoot tr:first-child {
                border-top: 1px solid var(--border-subtle) !important;
            }
            .woocommerce-checkout .shop_table tfoot td {
                text-align: center !important;
                display: flex !important;
                justify-content: center !important;
                align-items: center !important;
            }
            .woocommerce-checkout .shop_table tfoot td .amount {
                float: none !important;
                display: block !important;
            }

            /* Shipping row: thinner so it doesn't push the divider above */
            .woocommerce-checkout .shop_table tfoot tr.woocommerce-shipping-totals td,
            .woocommerce-checkout .shop_table tfoot tr.shipping td {
                padding-top: 0.5rem !important;
                padding-bottom: 0.5rem !important;
            }
            .woocommerce-checkout .shop_table tfoot tr.woocommerce-shipping-totals td .woocommerce-shipping-methods,
            .woocommerce-checkout .shop_table tfoot tr.shipping td .woocommerce-shipping-methods {
                margin: 0 !important;
            }
            .woocommerce-checkout .shop_table tfoot tr.woocommerce-shipping-totals .woocommerce-shipping-methods li,
            .woocommerce-checkout .shop_table tfoot tr.shipping .woocommerce-shipping-methods li {
                padding: 0.4rem 0.75rem !important;
                margin: 0 !important;
            }

            .woocommerce-checkout .shop_table tfoot .order-total th,
            .woocommerce-checkout .shop_table tfoot .order-total td {
                color: var(--accent-green) !important;
                font-size: 1.3rem !important;
                font-weight: 700 !important;
                border: none !important;
                padding-top: 1rem !important;
                padding-bottom: 1rem !important;
            }
            .woocommerce-checkout .shop_table tfoot tr.order-total {
                border-top: 2px solid var(--accent-green) !important;
                border-bottom: none !important;
            }

            /* Order Summary Product Images */
            .woocommerce-checkout .product-thumbnail img {
                border-radius: 8px !important;
                border: 1px solid var(--glass-border) !important;
            }

            /* Terms and Conditions */
            .woocommerce-checkout .terms {
                color: var(--text-secondary) !important;
                font-size: 0.9rem !important;
            }

            .woocommerce-checkout .terms a {
                color: var(--accent-green) !important;
            }

            /* Error Messages */
            .woocommerce-checkout .woocommerce-error,
            .woocommerce-checkout .woocommerce-info,
            .woocommerce-checkout .woocommerce-message {
                background: var(--glass) !important;
                border: 1px solid var(--glass-border) !important;
                border-radius: 8px !important;
                color: var(--text-primary) !important;
                padding: 1rem !important;
            }
            /* On checkout: remove white circle + exclamation icon from "Have a coupon?" notice */
            .woocommerce-checkout .woocommerce-info::before,
            .woocommerce-checkout .woocommerce-info::after {
                display: none !important;
                content: none !important;
                visibility: hidden !important;
            }

            /* Responsive */
            @media (max-width: 768px) {
                .woocommerce-cart table.cart {
                    font-size: 0.9rem !important;
                }

                .woocommerce-cart table.cart thead th,
                .woocommerce-cart table.cart tbody td {
                    padding: 1rem !important;
                }

                .woocommerce-cart .cart_totals {
                    padding: 1.5rem !important;
                }

                .woocommerce-checkout .woocommerce-checkout {
                    flex-direction: column !important;
                }

                .woocommerce-checkout #order_review {
                    margin-top: 2rem !important;
                }
            }
        </style>
        <?php
    }
}
add_action('wp_head', 'vb_arms_cart_page_styles', 999);
