<?php
// session_security.php
// Put this at top of every page (before any HTML output)

// ----- CONFIG -----
define('SESSION_TIMEOUT', 900); // inactivity timeout (seconds) = 15 minutes
define('FINGERPRINT_SALT', 'CHANGE_THIS_TO_A_RANDOM_SECRET'); // <--- change this
define('COOKIE_SAMESITE', 'Lax'); // 'Strict' if you want more restriction
// Adjust domain if needed; default to host
$host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\d+$/','', $_SERVER['HTTP_HOST']) : 'localhost';

// Detect HTTPS (basic)
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
         (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

// ----- COOKIE & SESSION SETTINGS (must be before session_start) -----
$cookieParams = [
    'lifetime' => 0,
    'path' => '/',
    'domain' => $host,
    'secure' => $https,
    'httponly' => true,
    'samesite' => COOKIE_SAMESITE
];

if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params($cookieParams);
} else {
    // fallback for older PHP versions (approx)
    session_set_cookie_params(
        $cookieParams['lifetime'],
        $cookieParams['path'] . '; SameSite=' . $cookieParams['samesite'],
        $cookieParams['domain'],
        $cookieParams['secure'],
        $cookieParams['httponly']
    );
}

ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid', 0);
session_name('MYAPPSESSID'); // optional: customize
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----- CSP Header + HSTS (adjust as needed) -----
# Conservative CSP: only allow your origin for scripts/styles (adjust if you use CDNs)
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; object-src 'none'; base-uri 'self';");
# HTTP Strict Transport Security (only if you serve HTTPS)
if ($https) {
    header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
}

// ----- XSS-safe helpers -----
function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function esc_attr($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
function esc_url_param($s) {
    return rawurlencode((string)$s);
}

// ----- Fingerprint helpers -----
function client_ip() {
    // Basic: use REMOTE_ADDR; if behind trusted proxy change logic
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
function ip_partial($ip) {
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts = explode('.', $ip);
        if (count($parts) >= 2) return $parts[0] . '.' . $parts[1]; // /16
        return $ip;
    }
    // for IPv6 basic prefix
    $parts = explode(':', $ip);
    return $parts[0] ?? $ip;
}
function make_fingerprint() {
    $ip = ip_partial(client_ip());
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    return hash('sha256', $ip . '|' . $ua . '|' . $lang . '|' . FINGERPRINT_SALT);
}

// ----- Core session security logic -----
function session_security_check() {
    // inactivity timeout
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
        // expired
        session_unset();
        session_destroy();
        // new session to carry message if needed
        session_start();
        $_SESSION['error'] = 'timeout';
        header('Location: /login.php?error=timeout');
        exit();
    }
    $_SESSION['LAST_ACTIVITY'] = time();

    // fingerprint check (if fingerprint exists)
    $current_fp = make_fingerprint();
    if (isset($_SESSION['fingerprint']) && $_SESSION['fingerprint'] !== $current_fp) {
        // possible cookie reuse on different device/browser
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['error'] = 'session_hijack';
        header('Location: /login.php?error=session_hijack');
        exit();
    }
}

// call this each request to enforce
session_security_check();

// call on login success
function secure_login($user_id, $user_data = []) {
    session_regenerate_id(true);
    $_SESSION['id'] = $user_id;
    // store lightweight info; avoid storing sensitive info in session if unnecessary
    if (!empty($user_data['name'])) $_SESSION['name'] = $user_data['name'];
    $_SESSION['fingerprint'] = make_fingerprint();
    $_SESSION['LAST_ACTIVITY'] = time();
    // optionally add device metadata
    $_SESSION['device_info'] = [
        'ip_partial' => ip_partial(client_ip()),
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'login_at' => date('c')
    ];
}

// call on logout
function secure_logout() {
    // destroy session cookie as well
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_unset();
    session_destroy();
}

// helper: require login for protected page
function require_login() {
    if (empty($_SESSION['id'])) {
        header('Location: /login.php?error=need_login');
        exit();
    }
    // also re-check security
    session_security_check();
}
