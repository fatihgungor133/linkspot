<?php
// Hata raporlamayı aktif et
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';
require_once '../includes/language.php';
session_start();

header('Content-Type: application/json');

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => __('session_required')]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // POST verilerini kontrol et
    error_log('POST verileri: ' . print_r($_POST, true));
    error_log('FILES verileri: ' . print_r($_FILES, true));

    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        $error_message = isset($_FILES['profile_image']) ? 
            __('upload_error_code') . ': ' . $_FILES['profile_image']['error'] : 
            __('no_file_uploaded');
        throw new Exception(__('file_upload_error') . ': ' . $error_message);
    }

    $file = $_FILES['profile_image'];
    
    // Dosya uzantısını kontrol et
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['profile_image']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    error_log('Dosya uzantısı: ' . $ext);
    
    if (!in_array($ext, $allowed_extensions)) {
        throw new Exception(__('invalid_file_type'));
    }

    // Dosya boyutunu kontrol et (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception(__('file_size_error'));
    }

    // Uploads klasörünü kontrol et ve oluştur
    $uploads_dir = '../uploads';
    $profiles_dir = $uploads_dir . '/profiles';
    
    error_log('Uploads dizini yolu: ' . $uploads_dir);
    error_log('Profiles dizini yolu: ' . $profiles_dir);
    
    if (!file_exists($uploads_dir)) {
        error_log('Uploads dizini oluşturuluyor...');
        if (!mkdir($uploads_dir, 0777, true)) {
            throw new Exception(__('create_uploads_dir_error') . ': ' . error_get_last()['message']);
        }
        chmod($uploads_dir, 0777);
    }
    
    if (!file_exists($profiles_dir)) {
        error_log('Profiles dizini oluşturuluyor...');
        if (!mkdir($profiles_dir, 0777, true)) {
            throw new Exception(__('create_profiles_dir_error') . ': ' . error_get_last()['message']);
        }
        chmod($profiles_dir, 0777);
    }

    // Dizin yazma izinlerini kontrol et
    if (!is_writable($profiles_dir)) {
        throw new Exception(__('profiles_dir_not_writable') . ': ' . $profiles_dir);
    }

    // Yeni dosya adı oluştur
    $new_filename = uniqid('profile_') . '.' . $ext;
    $upload_path = $profiles_dir . '/' . $new_filename;
    
    error_log('Yükleme yolu: ' . $upload_path);
    
    // Dosyayı yükle
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        $error = error_get_last();
        throw new Exception(__('file_upload_error') . ': ' . ($error ? $error['message'] : __('unknown_error')));
    }

    // Dosya izinlerini ayarla
    chmod($upload_path, 0644);

    // Eski profil resmini bul
    $query = "SELECT profile_image FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Eski profil resmini sil
    if ($user['profile_image']) {
        $old_file = '../' . $user['profile_image'];
        error_log('Eski dosya siliniyor: ' . $old_file);
        if (file_exists($old_file)) {
            unlink($old_file);
        }
    }

    // Veritabanını güncelle
    $profile_image = 'uploads/profiles/' . $new_filename;
    $update_query = "UPDATE users SET profile_image = ? WHERE id = ?";
    $stmt = $db->prepare($update_query);
    $stmt->execute([$profile_image, $user_id]);

    error_log('Profil resmi başarıyla güncellendi: ' . $profile_image);

    echo json_encode([
        'success' => true,
        'message' => __('profile_image_updated'),
        'profile_image' => $profile_image
    ]);

} catch (Exception $e) {
    error_log('Profil resmi yükleme hatası: ' . $e->getMessage());
    error_log('Hata detayı: ' . print_r($e->getTraceAsString(), true));
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => __('profile_image_update_error') . ': ' . $e->getMessage(),
        'debug_info' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
} 