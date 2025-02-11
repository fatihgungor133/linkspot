<?php
// Hata raporlamayı aktif et
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // POST verilerini kontrol et
    error_log('POST verileri: ' . print_r($_POST, true));
    error_log('FILES verileri: ' . print_r($_FILES, true));

    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        $error_message = isset($_FILES['profile_image']) ? 
            'Yükleme hatası kodu: ' . $_FILES['profile_image']['error'] : 
            'Dosya gönderilmedi';
        throw new Exception('Dosya yükleme hatası: ' . $error_message);
    }

    $file = $_FILES['profile_image'];
    
    // Dosya türünü kontrol et
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    error_log('Dosya MIME türü: ' . $mime_type);
    
    if (!in_array($mime_type, $allowed_types)) {
        throw new Exception('Sadece JPG, PNG ve GIF dosyaları yüklenebilir. Gönderilen dosya türü: ' . $mime_type);
    }

    // Dosya boyutunu kontrol et (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Dosya boyutu 5MB\'dan büyük olamaz. Gönderilen dosya boyutu: ' . ($file['size'] / 1024 / 1024) . 'MB');
    }

    // Uploads klasörünü kontrol et ve oluştur
    $uploads_dir = '../uploads';
    $profiles_dir = $uploads_dir . '/profiles';
    
    error_log('Uploads dizini yolu: ' . $uploads_dir);
    error_log('Profiles dizini yolu: ' . $profiles_dir);
    
    if (!file_exists($uploads_dir)) {
        error_log('Uploads dizini oluşturuluyor...');
        if (!mkdir($uploads_dir, 0777, true)) {
            throw new Exception('Uploads klasörü oluşturulamadı. Hata: ' . error_get_last()['message']);
        }
        chmod($uploads_dir, 0777);
    }
    
    if (!file_exists($profiles_dir)) {
        error_log('Profiles dizini oluşturuluyor...');
        if (!mkdir($profiles_dir, 0777, true)) {
            throw new Exception('Profiles klasörü oluşturulamadı. Hata: ' . error_get_last()['message']);
        }
        chmod($profiles_dir, 0777);
    }

    // Dizin yazma izinlerini kontrol et
    if (!is_writable($profiles_dir)) {
        throw new Exception('Profiles dizinine yazma izni yok. Dizin: ' . $profiles_dir);
    }

    // Yeni dosya adı oluştur
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = uniqid('profile_') . '.' . $extension;
    $upload_path = $profiles_dir . '/' . $new_filename;
    
    error_log('Yükleme yolu: ' . $upload_path);
    
    // Dosyayı yükle
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        $error = error_get_last();
        throw new Exception('Dosya yüklenirken bir hata oluştu: ' . ($error ? $error['message'] : 'Bilinmeyen hata'));
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
        'message' => 'Profil resmi başarıyla güncellendi.',
        'profile_image' => $profile_image
    ]);

} catch (Exception $e) {
    error_log('Profil resmi yükleme hatası: ' . $e->getMessage());
    error_log('Hata detayı: ' . print_r($e->getTraceAsString(), true));
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Profil resmi yüklenirken bir hata oluştu: ' . $e->getMessage(),
        'debug_info' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
} 