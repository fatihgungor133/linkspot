<?php
session_start();

// Varsayılan dil kodunu ayarla
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'en';
}

// Dil değiştirme isteği varsa
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'tr'])) {
    $_SESSION['language'] = $_GET['lang'];
    
    // Kullanıcı giriş yapmışsa dil tercihini güncelle
    if (isset($_SESSION['user_id'])) {
        require_once '../config/database.php';
        $update_query = "UPDATE users SET language_code = ? WHERE id = ?";
        $stmt = $db->prepare($update_query);
        $stmt->execute([$_SESSION['language'], $_SESSION['user_id']]);
    }
    
    // Önceki sayfaya yönlendir
    $redirect_url = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header("Location: " . $redirect_url);
    exit;
}

// Dil metinlerini getir
function get_language_strings($lang_code) {
    global $db;
    
    if (!isset($db)) {
        require dirname(__DIR__) . '/config/database.php';
    }
    
    $query = "SELECT ls.string_key, ls.string_value 
              FROM language_strings ls 
              JOIN languages l ON l.id = ls.language_id 
              WHERE l.code = 'en'";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $strings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $strings[$row['string_key']] = $row['string_value'];
    }
    
    return $strings;
}

// Dil metni getirme fonksiyonu
function __($key) {
    static $strings = null;
    
    if ($strings === null) {
        $strings = get_language_strings('en');
    }
    
    return $strings[$key] ?? $key;
}

// Dil seçim menüsü HTML'i - artık kullanılmıyor
function language_selector() {
    return '';
}

// Dil değiştirme işlemi
if (isset($_GET['lang'])) {
    $lang_code = $_GET['lang'];
    $query = "SELECT code FROM languages WHERE code = ? AND is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$lang_code]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['language'] = $lang_code;
    }
    
    // Mevcut URL'den lang parametresini kaldır
    $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: " . $redirect_url);
    exit;
}
?> 