<?php
/**
 * Admin UI for Lipsey's API: credentials, test connection, and option to submit paid orders.
 *
 * @package Lipseys_Import
 */

if (!defined('ABSPATH')) {
    exit;
}

class Lipseys_API_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'), 20);
        add_action('admin_init', array($this, 'save_settings'));
        add_action('wp_ajax_lipseys_api_test_connection', array($this, 'ajax_test_connection'));
    }

    public function add_menu() {
        add_submenu_page(
            'woocommerce',
            __("Lipsey's API", 'lipseys-import'),
            __("Lipsey's API", 'lipseys-import'),
            'manage_woocommerce',
            'lipseys-api',
            array($this, 'render_page')
        );
    }

    public function save_settings() {
        if (!isset($_POST['lipseys_api_save']) || !current_user_can('manage_woocommerce')) {
            return;
        }
        check_admin_referer('lipseys_api_settings');

        if (isset($_POST['lipseys_api_email'])) {
            update_option('lipseys_api_email', sanitize_email(wp_unslash($_POST['lipseys_api_email'])));
        }
        if (isset($_POST['lipseys_api_password']) && $_POST['lipseys_api_password'] !== '') {
            update_option('lipseys_api_password', wp_unslash($_POST['lipseys_api_password'])); // store as-is; don't log
        }
        if (isset($_POST['lipseys_api_submit_orders'])) {
            update_option('lipseys_api_submit_orders', 'yes');
        } else {
            update_option('lipseys_api_submit_orders', 'no');
        }
        if (isset($_POST['lipseys_api_proxy_url'])) {
            update_option('lipseys_api_proxy_url', esc_url_raw(trim(wp_unslash($_POST['lipseys_api_proxy_url']))));
        }
        if (isset($_POST['lipseys_api_proxy_secret'])) {
            update_option('lipseys_api_proxy_secret', wp_unslash($_POST['lipseys_api_proxy_secret']));
        }

        Lipseys_API_Client::clear_token();
        add_settings_error(
            'lipseys_api',
            'saved',
            __("Settings saved. Token cache cleared.", 'lipseys-import'),
            'success'
        );
    }

    public function ajax_test_connection() {
        check_ajax_referer('lipseys_api_test', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        $token = Lipseys_API_Client::get_token();
        if ($token) {
            wp_send_json_success(array('message' => 'Connection successful. Token obtained.'));
        }
        $creds = Lipseys_API_Client::get_credentials();
        if (!$creds) {
            wp_send_json_error(array('message' => 'No credentials saved. Enter email and password, then save.'));
        }
        $login = Lipseys_API_Client::login($creds['email'], $creds['password']);
        if (!empty($login['token'])) {
            set_transient(Lipseys_API_Client::TOKEN_TRANSIENT, $login['token'], Lipseys_API_Client::TOKEN_TTL);
            wp_send_json_success(array('message' => 'Connection successful.'));
        }
        $errors = isset($login['errors']) && is_array($login['errors']) ? $login['errors'] : array('Login failed.');
        wp_send_json_error(array('message' => implode(' ', $errors)));
    }

    public function render_page() {
        $email   = get_option('lipseys_api_email', '');
        $submit  = get_option('lipseys_api_submit_orders', 'yes') === 'yes';
        $proxy_url = get_option('lipseys_api_proxy_url', '');
        $proxy_secret = get_option('lipseys_api_proxy_secret', '');
        $nonce   = wp_create_nonce('lipseys_api_test');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e("Lipsey's API", 'lipseys-import'); ?></h1>
            <?php settings_errors('lipseys_api'); ?>

            <p class="description">
                <?php esc_html_e("API is used for live catalog/ordering. If your WordPress host's IP is not whitelisted, use a proxy (your server with whitelisted IP, e.g. 35.134.230.192). Payment: Authorize.net for checkout; orders submitted here to Lipsey's.", 'lipseys-import'); ?>
            </p>
            <?php if ( $proxy_url && ( strpos( $proxy_url, 'YOUR_PROXY_SERVER_IP' ) !== false ) ) : ?>
            <p class="description" style="margin: 8px 0; padding: 10px 12px; background: #e7f5e9; border-left: 4px solid #00a32a;">
                <?php esc_html_e( 'Proxy runs on server 24/7. Lipsey\'s has whitelisted YOUR_PROXY_SERVER_IP—no Mac tunnel needed.', 'lipseys-import' ); ?>
            </p>
            <?php endif; ?>

            <form method="post" action="" id="lipseys-api-form">
                <?php wp_nonce_field('lipseys_api_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="lipseys_api_proxy_url"><?php esc_html_e('Proxy URL', 'lipseys-import'); ?></label></th>
                        <td>
                            <input type="url" id="lipseys_api_proxy_url" name="lipseys_api_proxy_url" class="large-text"
                                   value="<?php echo esc_attr($proxy_url); ?>" placeholder="https://your-server.com/path/to/lipseys-proxy.php">
                            <p class="description"><?php esc_html_e('Optional. If set, all API requests go through this URL (your server with whitelisted IP). Leave blank to call Lipsey\'s directly from this server.', 'lipseys-import'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="lipseys_api_proxy_secret"><?php esc_html_e('Proxy secret', 'lipseys-import'); ?></label></th>
                        <td>
                            <input type="password" id="lipseys_api_proxy_secret" name="lipseys_api_proxy_secret" class="regular-text"
                                   value="<?php echo esc_attr($proxy_secret); ?>" autocomplete="off" placeholder="••••••••">
                            <p class="description"><?php esc_html_e('Optional. Must match the secret set in lipseys-proxy.php on your proxy server.', 'lipseys-import'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="lipseys_api_email"><?php esc_html_e('Email', 'lipseys-import'); ?></label></th>
                        <td>
                            <input type="email" id="lipseys_api_email" name="lipseys_api_email" class="regular-text"
                                   value="<?php echo esc_attr($email); ?>" autocomplete="email">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="lipseys_api_password"><?php esc_html_e('Password', 'lipseys-import'); ?></label></th>
                        <td>
                            <input type="password" id="lipseys_api_password" name="lipseys_api_password" class="regular-text"
                                   value="" autocomplete="current-password" placeholder="••••••••">
                            <p class="description"><?php esc_html_e('Leave blank to keep current password.', 'lipseys-import'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Submit orders', 'lipseys-import'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="lipseys_api_submit_orders" value="1" <?php checked($submit); ?>>
                                <?php esc_html_e('When a WooCommerce order is paid, submit line items to Lipsey\'s API (APIOrder). Products must have Lipsey\'s item number in SKU or custom field lipseys_item_no.', 'lipseys-import'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <p>
                    <button type="submit" name="lipseys_api_save" class="button button-primary">
                        <?php esc_html_e('Save settings', 'lipseys-import'); ?>
                    </button>
                    <button type="button" id="lipseys-api-test" class="button">
                        <?php esc_html_e('Test connection', 'lipseys-import'); ?>
                    </button>
                    <span id="lipseys-api-test-result"></span>
                </p>
            </form>
        </div>
        <script>
        jQuery(function($) {
            $('#lipseys-api-test').on('click', function() {
                var $btn = $(this), $result = $('#lipseys-api-test-result');
                $btn.prop('disabled', true);
                $result.text('').removeClass('success error');
                $.post(ajaxurl, {
                    action: 'lipseys_api_test_connection',
                    nonce: '<?php echo esc_js($nonce); ?>'
                }).done(function(r) {
                    $result.text(r.data && r.data.message ? r.data.message : (r.success ? 'OK' : 'Failed')).addClass(r.success ? 'success' : 'error');
                }).fail(function() {
                    $result.text('Request failed').addClass('error');
                }).always(function() {
                    $btn.prop('disabled', false);
                });
            });
        });
        </script>
        <style>
        #lipseys-api-test-result { margin-left: 8px; }
        #lipseys-api-test-result.success { color: #00a32a; }
        #lipseys-api-test-result.error { color: #d63638; }
        </style>
        <?php
    }
}
