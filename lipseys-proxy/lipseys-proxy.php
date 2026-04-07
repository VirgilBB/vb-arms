<?php
/**
 * Lipsey's API proxy – run this on your server whose IP is whitelisted by Lipsey's (e.g. 35.134.230.192).
 *
 * WordPress (EasyWP) sends requests here; this script forwards them to api.lipseys.com.
 * Lipsey's sees your server's IP, so whitelist that IP with Lipsey's.
 *
 * Setup:
 * 1. Upload this file to your server (the one with the whitelisted IP).
 * 2. Set LIPSEYS_PROXY_SECRET to a random string; set the same value in WooCommerce → Lipsey's API → Proxy secret.
 * 3. In WooCommerce → Lipsey's API, set Proxy URL to https://your-server.com/path/to/lipseys-proxy.php
 *
 * Optional: Restrict by IP (add your EasyWP outbound IP) or leave empty to allow any caller.
 */

// --- CONFIG (edit these) ---
define('LIPSEYS_PROXY_SECRET', 'YOUR_PROXY_SECRET_HERE'); // Must match WooCommerce → Lipsey's API → Proxy secret.
define('LIPSEYS_API_BASE', 'https://api.lipseys.com');
// Optional: comma-separated IPs allowed to call this proxy (e.g. EasyWP). Leave empty to allow any.
define('LIPSEYS_PROXY_ALLOWED_IPS', '');

// --- No edits below ---
header('Content-Type: application/json; charset=utf-8');

// Allow POST and GET methods
if (!in_array($_SERVER['REQUEST_METHOD'], array('POST', 'GET'), true)) {
    http_response_code(405);
    echo json_encode(array('error' => 'Method not allowed (POST or GET only)'));
    exit;
}

if (LIPSEYS_PROXY_ALLOWED_IPS !== '') {
    $allowed = array_map('trim', explode(',', LIPSEYS_PROXY_ALLOWED_IPS));
    $client_ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]) : ($_SERVER['REMOTE_ADDR'] ?? '');
    if (!in_array($client_ip, $allowed, true)) {
        http_response_code(403);
        echo json_encode(array('error' => 'IP not allowed', 'your_ip' => $client_ip));
        exit;
    }
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input) || empty($input['path'])) {
    http_response_code(400);
    echo json_encode(array('error' => 'Invalid request: need JSON with path'));
    exit;
}

if (LIPSEYS_PROXY_SECRET !== '' && (!isset($input['secret']) || $input['secret'] !== LIPSEYS_PROXY_SECRET)) {
    http_response_code(403);
    echo json_encode(array('error' => 'Invalid or missing secret'));
    exit;
}

$path = ltrim($input['path'], '/');
$body = isset($input['body']) ? $input['body'] : '';
$headers = isset($input['headers']) && is_array($input['headers']) ? $input['headers'] : array();
$method = isset($input['method']) ? strtoupper($input['method']) : 'POST';

$url = rtrim(LIPSEYS_API_BASE, '/') . '/' . $path;

$ch = curl_init($url);
$curl_opts = array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 180, // 3 min for full catalog feed (Lipsey's can be slow)
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, // Use IPv4 so Lipsey's sees your whitelisted IPv4
    CURLOPT_HTTPHEADER => array_merge(
        array('Content-Type: application/json'),
        array_map(function ($k, $v) { return $k . ': ' . $v; }, array_keys($headers), $headers)
    ),
);

// Set method-specific options
if ($method === 'GET') {
    $curl_opts[CURLOPT_HTTPGET] = true;
} else {
    $curl_opts[CURLOPT_POST] = true;
    $curl_opts[CURLOPT_POSTFIELDS] = $body;
}

curl_setopt_array($ch, $curl_opts);
$response_body = curl_exec($ch);
$http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err = curl_error($ch);
// curl_close() is a no-op in PHP 8+ and deprecated in 8.5; omit to avoid deprecation notice in output

if ($curl_err !== '') {
    http_response_code(502);
    echo json_encode(array('error' => 'Proxy request failed', 'detail' => $curl_err));
    exit;
}

// Debug: so WordPress can show whether response came from Mac or server
header('X-Proxy-Host: ' . gethostname());
http_response_code($http_code);
echo $response_body;
