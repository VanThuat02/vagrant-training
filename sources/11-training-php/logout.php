<?php
// secure_logout.php

// Must be called before any output
session_start();

// 1) Clear $_SESSION array
$_SESSION = [];

// 2) If you use a custom session cookie, fetch params to delete it properly
$params = session_get_cookie_params();

// 3) Delete the session cookie in the browser by setting it in the past
// Use same name/path/domain/secure/httponly values as the original cookie
setcookie(
    session_name(),    // typically PHPSESSID
    '',
    time() - 3600,     // expire in the past
    $params['path'],
    $params['domain'],
    $params['secure'],
    $params['httponly']
);

// If you used SameSite, set it explicitly (PHP < 7.3 needs manual header)
if (PHP_VERSION_ID >= 70300) {
    // session_get_cookie_params() on PHP 7.3+ includes 'samesite' in some envs,
    // but to be safe, you can set cookie again with samesite option:
    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => 'Lax' // or 'Strict' / '' depending on your app
        ]
    );
}

// 4) Destroy the session data on server
session_destroy();

// 5) If you use a custom session store (Redis, DB), optionally delete server-side key
// Example for Redis-based session stored at key "PHPREDIS_SESSION:{id}" — adjust to your handler.
// if (isset($_COOKIE[session_name()])) {
//     $sid = $_COOKIE[session_name()];
//     // $redis->del('session:' . $sid);  // only if you know exact key pattern
// }

// 6) Remove any other cookies you set (e.g., "remember_me", auth tokens)
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/', '', true, true);
}

// 7) Redirect and exit
header('Location: login.php');
exit;
