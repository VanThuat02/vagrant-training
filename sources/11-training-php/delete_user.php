<?php
require_once 'models/UserModel.php';
require_once 'csrf_helper.php';

$userModel = new UserModel();

$user = NULL; //Add new user
$id = NULL;

// Xử lý post request với CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    // Kiểm tra CSRF token cho POST
    if (empty($_POST['csrf_token']) || !CSRF_Protection::validateToken($_POST['csrf_token'])) {
        die("CSRF token validation failed!");
    }

    $id = $_POST['id'];
    $userModel->deleteUserById($id);
    header('location: list_users.php');
    exit;
}
    
if (!empty($_GET['id'])) {
    $id = $_GET['id'];
    $userModel->deleteUserById($id);//Delete existing user
}
header('location: list_users.php');
?>