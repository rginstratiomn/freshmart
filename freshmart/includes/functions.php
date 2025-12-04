<?php
session_start();

// Include database configuration
require_once __DIR__ . '/../config/database.php';

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager');
}

function isKasir() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'kasir';
}

function isCustomer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

function generateSKU() {
    $db = new Database();
    $connection = $db->getConnection();
    
    do {
        $sku = 'PRD-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $check = $connection->prepare("SELECT id FROM products WHERE sku = ?");
        $check->execute([$sku]);
    } while ($check->rowCount() > 0);
    
    return $sku;
}

function uploadImage($file, $target_dir = "../uploads/") {
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Generate unique filename
    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $filename = time() . '_' . uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $filename;
    
    // Check if image file is actual image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        throw new Exception("File is not an image.");
    }
    
    // Check file size (max 2MB)
    if ($file["size"] > 2000000) {
        throw new Exception("Sorry, your file is too large. Maximum size is 2MB.");
    }
    
    // Allow certain file formats
    if(!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif', 'webp'])) {
        throw new Exception("Sorry, only JPG, JPEG, PNG, WEBP & GIF files are allowed.");
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $filename;
    } else {
        throw new Exception("Sorry, there was an error uploading your file.");
    }
}

// PASTIKAN FUNGSI INI ADA!
function getCartItemCount() {
    if (isset($_SESSION['cart'])) {
        return array_sum($_SESSION['cart']);
    }
    return 0;
}

function getSystemSetting($key, $default = '') {
    $db = new Database();
    $connection = $db->getConnection();
    
    $query = "SELECT setting_value FROM system_settings WHERE setting_key = ?";
    $stmt = $connection->prepare($query);
    $stmt->execute([$key]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['setting_value'] : $default;
}

function addNotification($user_id, $type, $title, $message, $data = null) {
    $db = new Database();
    $connection = $db->getConnection();
    
    $query = "INSERT INTO notifications (user_id, type, title, message, data) VALUES (?, ?, ?, ?, ?)";
    $stmt = $connection->prepare($query);
    
    $json_data = $data ? json_encode($data) : null;
    return $stmt->execute([$user_id, $type, $title, $message, $json_data]);
}

function logActivity($user_id, $action, $model_type = null, $model_id = null, $description = '') {
    $db = new Database();
    $connection = $db->getConnection();
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $query = "INSERT INTO activity_logs (user_id, action, model_type, model_id, description, ip_address, user_agent) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $connection->prepare($query);
    
    return $stmt->execute([$user_id, $action, $model_type, $model_id, $description, $ip_address, $user_agent]);
}

// Check if database connection is working
function checkDatabaseConnection() {
    $db = new Database();
    return $db->getConnection() !== false;
}
?>