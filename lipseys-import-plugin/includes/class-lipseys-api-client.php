<?php
/**
 * Lipsey's API client – authentication, catalog, and order submission.
 * Requires server IP to be whitelisted by Lipsey's. Credentials via options (lipseys_api_email, lipseys_api_password).
 *
 * @package Lipseys_Import
 */

if (!defined('ABSPATH')) {
    exit;
}

class Lipseys_API_Client {

    const BASE_URL = 'https://api.lipseys.com';
    const TOKEN_TRANSIENT = 'lipseys_api_token';
    const TOKEN_TTL = 3300; // 55 minutes; login token used for all requests

    /**
     * Get stored API credentials from options (never commit these).
     *
     * @return array{email: string, password: string}|null
     */
    public static function get_credentials() {
        $email = get_option('lipseys_api_email', '');
        $password = get_option('lipseys_api_password', '');
        if (empty($email) || empty($password)) {
            return null;
        }
        return array(
            'email'    => $email,
            'password' => $password,
        );
    }

    /**
     * Get a valid token (from transient or by logging in). Returns null if credentials missing or login fails.
     *
     * @return string|null
     */
    public static function get_token() {
        $token = get_transient(self::TOKEN_TRANSIENT);
        if (!empty($token)) {
            return $token;
        }
        $creds = self::get_credentials();
        if (!$creds) {
            return null;
        }
        $response = self::login($creds['email'], $creds['password']);
        if (empty($response['token'])) {
            return null;
        }
        set_transient(self::TOKEN_TRANSIENT, $response['token'], self::TOKEN_TTL);
        return $response['token'];
    }

    /**
     * Clear cached token (e.g. after credential change).
     */
    public static function clear_token() {
        delete_transient(self::TOKEN_TRANSIENT);
    }

    /**
     * Send request to Lipsey's API (direct or via proxy). Proxy URL = server with whitelisted IP (e.g. 35.134.230.192).
     *
     * @param string $path   Path relative to api.lipseys.com (e.g. api/Integration/Authentication/Login).
     * @param string $body   Raw request body.
     * @param array  $headers Associative array of headers (e.g. Token, Content-Type).
     * @param int    $timeout Optional timeout in seconds (default 30). Use 15 for CatalogFeed/Item to fail fast on batch.
     * @return array|\WP_Error Response suitable for parse_response.
     */
    private static function request($path, $body, $headers = array(), $timeout = 30) {
        $proxy_url = get_option('lipseys_api_proxy_url', '');
        $timeout = max(5, min(120, (int) $timeout));

        if ($proxy_url !== '') {
            $path = ltrim($path, '/');
            $payload = array(
                'path'    => $path,
                'body'    => $body,
                'headers' => $headers,
            );
            $secret = get_option('lipseys_api_proxy_secret', '');
            if ($secret !== '') {
                $payload['secret'] = $secret;
            }
            return wp_remote_post(
                $proxy_url,
                array(
                    'headers' => array('Content-Type' => 'application/json'),
                    'body'    => wp_json_encode($payload),
                    'timeout' => $timeout,
                )
            );
        }

        $url = self::BASE_URL . '/' . ltrim($path, '/');
        $request_headers = array('Content-Type' => 'application/json');
        foreach ($headers as $k => $v) {
            $request_headers[ $k ] = $v;
        }
        return wp_remote_post($url, array(
            'headers' => $request_headers,
            'body'    => $body,
            'timeout' => $timeout,
        ));
    }

    /**
     * Login and return token + dealer info.
     *
     * @param string $email
     * @param string $password
     * @return array{token?: string, econtact?: array, success?: bool, errors?: array}
     */
    public static function login($email, $password) {
        $body = wp_json_encode(array(
            'Email'    => $email,
            'Password' => $password,
        ));
        $res = self::request('api/Integration/Authentication/Login', $body, array());
        return self::parse_response($res);
    }

    /**
     * Validate item(s) for ordering (quantity, price, blocked, etc.).
     * Body: array of item numbers (Lipsey's item #, UPC, or MFG model #).
     *
     * @param array $item_numbers
     * @return array{success: bool, authorized: bool, errors: array, data: array}
     */
    public static function validate_item($item_numbers) {
        $token = self::get_token();
        if (!$token) {
            return array('success' => false, 'authorized' => false, 'errors' => array('No API token'), 'data' => array());
        }
        $body = wp_json_encode($item_numbers);
        $res = self::request('api/Integration/Items/ValidateItem', $body, array('Token' => $token));
        return self::parse_response($res);
    }

    /**
     * Get catalog details for one item (by Lipsey's item #, UPC, or MFG model #).
     *
     * @param string $item_number
     * @return array{success: bool, authorized: bool, errors: array, data: object|array}
     */
    public static function catalog_feed_item($item_number) {
        $token = self::get_token();
        if (!$token) {
            return array('success' => false, 'authorized' => false, 'errors' => array('No API token'), 'data' => array());
        }
        $body = wp_json_encode($item_number);
        // 30s per item when using proxy (Lipsey's can be slow); gateway may still kill the PHP request
        $res = self::request('api/Integration/Items/CatalogFeed/Item', $body, array('Token' => $token), 30);
        return self::parse_response($res);
    }

    /**
     * Get full catalog feed (all products with details).
     * Catalog feed updates every 4 hours on Lipsey's side (cached).
     * Images available at: https://www.lipseyscloud.com/images/[imageName]
     *
     * @return array{success: bool, authorized: bool, errors: array, data: array}
     */
    public static function catalog_feed() {
        $token = self::get_token();
        if (!$token) {
            return array('success' => false, 'authorized' => false, 'errors' => array('No API token'), 'data' => array());
        }
        
        // GET request (no body)
        $res = self::request_get('api/Integration/Items/CatalogFeed', array('Token' => $token));
        return self::parse_response($res);
    }

    /**
     * Get pricing and quantity feed (faster than catalog feed).
     * Returns only: itemNumber, upc, mfgModelNumber, quantity, price, currentPrice, retailMap, etc.
     * Updates every 1 hour on Lipsey's side (cached).
     *
     * @return array{success: bool, authorized: bool, errors: array, data: array}
     */
    public static function pricing_quantity_feed() {
        $token = self::get_token();
        if (!$token) {
            return array('success' => false, 'authorized' => false, 'errors' => array('No API token'), 'data' => array());
        }
        
        // GET request (no body)
        $res = self::request_get('api/Integration/Items/PricingQuantityFeed', array('Token' => $token));
        return self::parse_response($res);
    }

    /**
     * Send GET request to Lipsey's API (direct or via proxy).
     *
     * @param string $path   Path relative to api.lipseys.com
     * @param array  $headers Associative array of headers (e.g. Token).
     * @return array|\WP_Error Response suitable for parse_response.
     */
    private static function request_get($path, $headers = array()) {
        $proxy_url = get_option('lipseys_api_proxy_url', '');
        $timeout = 120; // Longer timeout for catalog feed (match frontend AJAX timeout)

        if ($proxy_url !== '') {
            $path = ltrim($path, '/');
            $payload = array(
                'path'    => $path,
                'method'  => 'GET',
                'headers' => $headers,
            );
            $secret = get_option('lipseys_api_proxy_secret', '');
            if ($secret !== '') {
                $payload['secret'] = $secret;
            }
            return wp_remote_post(
                $proxy_url,
                array(
                    'headers' => array('Content-Type' => 'application/json'),
                    'body'    => wp_json_encode($payload),
                    'timeout' => $timeout,
                )
            );
        }

        $url = self::BASE_URL . '/' . ltrim($path, '/');
        $request_headers = array();
        foreach ($headers as $k => $v) {
            $request_headers[ $k ] = $v;
        }
        return wp_remote_get($url, array(
            'headers' => $request_headers,
            'timeout' => $timeout,
        ));
    }

    /**
     * Create an order (adds to open order of same type or creates new). Ship-to from token account.
     *
     * @param string $po_number Your PO/reference number.
     * @param array  $items Array of { ItemNo: string, Quantity: int, Note?: string }.
     * @param bool   $disable_email
     * @return array{success: bool, authorized: bool, errors: array, data: array}
     */
    public static function api_order($po_number, $items, $disable_email = false) {
        $token = self::get_token();
        if (!$token) {
            return array('success' => false, 'authorized' => false, 'errors' => array('No API token'), 'data' => array());
        }
        $body = wp_json_encode(array(
            'PONumber'     => $po_number,
            'DisableEmail' => $disable_email,
            'Items'        => $items,
        ));
        $res = self::request('api/Integration/Order/APIOrder', $body, array('Token' => $token));
        return self::parse_response($res);
    }

    /**
     * Parse Lipsey's API response (success, authorized, errors, data).
     *
     * @param array|\WP_Error $response wp_remote_post response.
     * @return array
     */
    private static function parse_response($response) {
        if (is_wp_error($response)) {
            return array(
                'success'     => false,
                'authorized'  => false,
                'errors'      => array($response->get_error_message()),
                'data'        => array(),
            );
        }
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $headers = wp_remote_retrieve_headers($response);
        $proxy_host = is_object($headers) && isset($headers['x-proxy-host']) ? $headers['x-proxy-host'] : '';
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            $hint = '';
            if ($code === 401 || $code === 403) {
                $hint = ' Server IP may need to be whitelisted by Lipsey\'s.';
                if ($proxy_host !== '') {
                    $hint .= ' [Response from proxy host: ' . $proxy_host . ' — if this is your Mac, check Lipsey\'s email/password.]';
                } else {
                    $hint .= ' [No X-Proxy-Host header — request may not have reached your Mac proxy.]';
                }
            }
            $preview = strlen($body) > 120 ? substr($body, 0, 120) . '…' : $body;
            $preview = preg_replace('/\s+/', ' ', trim($preview));
            if ($preview !== '') {
                $hint .= ' Response (HTTP ' . $code . '): ' . $preview;
            } else {
                $hint .= ' Empty response (HTTP ' . $code . ').';
            }
            return array(
                'success'     => false,
                'authorized'  => false,
                'errors'      => array('Invalid JSON response.' . $hint),
                'data'        => array(),
            );
        }
        if ($code >= 400) {
            $decoded['success'] = false;
            if (empty($decoded['errors']) || !is_array($decoded['errors'])) {
                $decoded['errors'] = array('HTTP ' . $code);
            }
        }
        return array_merge(
            array(
                'success'    => isset($decoded['success']) ? (bool) $decoded['success'] : false,
                'authorized' => isset($decoded['authorized']) ? (bool) $decoded['authorized'] : false,
                'errors'     => isset($decoded['errors']) && is_array($decoded['errors']) ? $decoded['errors'] : array(),
                'data'       => isset($decoded['data']) ? $decoded['data'] : array(),
            ),
            $decoded
        );
    }
}
