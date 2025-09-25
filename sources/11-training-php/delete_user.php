<?php
session_start();
require_once 'models/UserModel.php';
require_once 'csrf_helper.php';

$userModel = new UserModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    // ✅ Kiểm tra CSRF token
    if (empty($_POST['csrf_token']) || !CSRF_Protection::validateToken($_POST['csrf_token'])) {
        error_log("🚨 CSRF Blocked: " . print_r($_POST, true));
        die(" CSRF token validation failed!");
    }

    $id = $_POST['id'];
    $userModel->deleteUserById($id);
    header('location: list_users.php');
    exit;
}

// Nếu hacker paste GET link delete_user.php?id=1 → không có token
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['id'])) {
    error_log("🚨 CSRF attempt via GET: " . print_r($_GET, true));
    die(" CSRF token validation failed (GET request bị chặn)!");
}

// Trường hợp khác quay về danh sách
header('location: list_users.php');
exit;
