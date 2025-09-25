<?php
// File: csrf_helper.php
class CSRF_Protection {
    
    public static function generateToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateToken($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        
        // Kiểm tra token timeout (30 phút)
        if (time() - $_SESSION['csrf_token_time'] > 1800) {
            unset($_SESSION['csrf_token']);
            unset($_SESSION['csrf_token_time']);
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function getTokenField() {
        return '<input type="hidden" name="csrf_token" value="' . self::generateToken() . '">';
    }
}
?>