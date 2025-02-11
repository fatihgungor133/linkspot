<?php
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
    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Dosya yüklenirken bir hata oluştu.');
    }

    $file = $_FILES['profile_image'];
    
    // Dosya türünü kontrol et
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        throw new Exception('Sadece JPG, PNG ve GIF dosyaları yüklenebilir.');
    }

    // Dosya boyutunu kontrol et (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Dosya boyutu 5MB\'dan büyük olamaz.');
    }

    // Uploads klasörünü kontrol et ve oluştur
    $uploads_dir = '../uploads';
    $profiles_dir = $uploads_dir . '/profiles';
    
    if (!file_exists($uploads_dir)) {
        if (!mkdir($uploads_dir, 0777, true)) {
            throw new Exception('Uploads klasörü oluşturulamadı.');
        }
        chmod($uploads_dir, 0777);
    }
    
    if (!file_exists($profiles_dir)) {
        if (!mkdir($profiles_dir, 0777, true)) {
            throw new Exception('Profiles klasörü oluşturulamadı.');
        }
        chmod($profiles_dir, 0777);
    }

    // Yeni dosya adı oluştur
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = uniqid('profile_') . '.' . $extension;
    $upload_path = $profiles_dir . '/' . $new_filename;
    
    // Dosyayı yükle
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('Dosya yüklenirken bir hata oluştu: ' . error_get_last()['message']);
    }

    // Eski profil resmini bul
    $query = "SELECT profile_image FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Eski profil resmini sil
    if ($user['profile_image']) {
        $old_file = '../' . $user['profile_image'];
        if (file_exists($old_file)) {
            unlink($old_file);
        }
    }

    // Veritabanını güncelle
    $profile_image = 'uploads/profiles/' . $new_filename;
    $update_query = "UPDATE users SET profile_image = ? WHERE id = ?";
    $stmt = $db->prepare($update_query);
    $stmt->execute([$profile_image, $user_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Profil resmi başarıyla güncellendi.',
        'profile_image' => $profile_image
    ]);

} catch (Exception $e) {
    error_log('Profil resmi yükleme hatası: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Profil resmi yüklenirken bir hata oluştu: ' . $e->getMessage()
    ]);
} 