<?php
require_once 'session_security.php';
// if not logged in, redirect to login
if (empty($_SESSION['id'])) {
    header('Location: /login.php?error=need_login');
    exit;
}
// Optionally fetch minimal info to render (no inline script)
$userId = intval($_SESSION['id']);
$username = $_SESSION['name'] ?? '';
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Logging in...</title>
    <?php include 'views/meta.php' ?>
</head>
<body>
    <p>Finalizing login — redirecting...</p>

    <!-- External JS sets localStorage and redirects to list_users.php -->
    <script src="/public/js/login_success.js"></script>
</body>
</html>
