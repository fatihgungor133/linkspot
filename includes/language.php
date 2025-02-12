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

// Metinleri getir
function get_language_strings($lang_code) {
    require_once 'config/database.php';
    
    $query = "SELECT ls.string_key, ls.string_value 
              FROM language_strings ls 
              JOIN languages l ON l.id = ls.language_id 
              WHERE l.code = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$lang_code]);
    
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
        $strings = get_language_strings($_SESSION['language']);
    }
    
    return $strings[$key] ?? $key;
}

// Dil seçim menüsü HTML'i
function language_selector() {
    $current_lang = $_SESSION['language'];
    $languages = [
        'en' => 'English',
        'tr' => 'Türkçe'
    ];
    
    $html = '<div class="dropdown">';
    $html .= '<button class="btn btn-link nav-link dropdown-toggle" type="button" data-bs-toggle="dropdown">';
    $html .= '<i class="bi bi-globe"></i> ' . $languages[$current_lang];
    $html .= '</button>';
    $html .= '<ul class="dropdown-menu">';
    
    foreach ($languages as $code => $name) {
        $active = $code === $current_lang ? ' active' : '';
        $html .= '<li><a class="dropdown-item' . $active . '" href="?lang=' . $code . '">' . $name . '</a></li>';
    }
    
    $html .= '</ul></div>';
    
    return $html;
}
?> 