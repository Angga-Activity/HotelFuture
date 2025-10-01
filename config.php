<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'asep');

define('SITE_NAME', 'HotelFuture');
define('SITE_URL', 'http://localhost');
define('UPLOAD_PATH', 'uploads/');

define('API_KEY_LENGTH', 32);

session_start();

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function generateBookingCode() {
    return 'HFT' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
}
?>