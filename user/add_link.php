<?php
require_once '../config/database.php';
require_once '../includes/language.php';
session_start();

// JSON yanıt başlığı
header('Content-Type: application/json');

// Hata raporlamayı aktif et
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Oturum ve AJAX kontrolü
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => __('session_required')]);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $title = trim($_POST['title'] ?? '');
    $url = trim($_POST['url'] ?? '');
    
    // Validasyon
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => __('title_required')]);
        exit;
    }

    if (empty($url)) {
        echo json_encode(['success' => false, 'message' => __('url_required')]);
        exit;
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        echo json_encode(['success' => false, 'message' => __('invalid_url')]);
        exit;
    }

    // Görsel yükleme işlemi
    $image_path = null;
    
    // URL ile görsel ekleme
    if (!empty($_POST['image_url'])) {
        $image_url = trim($_POST['image_url']);
        
        // URL'nin geçerli olup olmadığını kontrol et
        if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => __('invalid_image_url')]);
            exit;
        }
        
        // Görsel uzantısını kontrol et
        $ext = strtolower(pathinfo($image_url, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => __('image_type_error')]);
            exit;
        }
        
        // Uploads klasörünü kontrol et ve oluştur
        $uploads_dir = '../uploads/links';
        if (!file_exists($uploads_dir)) {
            mkdir($uploads_dir, 0777, true);
        }
        
        // Benzersiz dosya adı oluştur
        $new_filename = uniqid('link_') . '.' . $ext;
        $upload_path = $uploads_dir . '/' . $new_filename;
        
        // URL'den görseli indir
        $image_content = @file_get_contents($image_url);
        if ($image_content === false) {
            echo json_encode(['success' => false, 'message' => __('image_download_error')]);
            exit;
        }
        
        // Görseli kaydet
        if (file_put_contents($upload_path, $image_content)) {
            $image_path = 'uploads/links/' . $new_filename;
        }
    }
    // Dosya yükleme ile görsel ekleme
    else if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => __('image_type_error')]);
            exit;
        }

        // Dosya boyutunu kontrol et (max 2MB)
        if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => __('image_size_error')]);
            exit;
        }

        // Uploads klasörünü kontrol et ve oluştur
        $uploads_dir = '../uploads/links';
        if (!file_exists($uploads_dir)) {
            mkdir($uploads_dir, 0777, true);
        }

        // Benzersiz dosya adı oluştur
        $new_filename = uniqid('link_') . '.' . $ext;
        $upload_path = $uploads_dir . '/' . $new_filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $image_path = 'uploads/links/' . $new_filename;
        }
    }

    // Önce mevcut linklerin sırasını bir artır
    $update_order_query = "UPDATE links SET order_number = order_number + 1 WHERE user_id = ?";
    $stmt = $db->prepare($update_order_query);
    $stmt->execute([$user_id]);

    // Yeni linki en başa ekle (order_number = 0)
    $insert_query = "INSERT INTO links (user_id, title, url, image, order_number, is_active) VALUES (?, ?, ?, ?, 0, 1)";
    $stmt = $db->prepare($insert_query);
    $stmt->execute([$user_id, $title, $url, $image_path]);

    $link_id = $db->lastInsertId();

    // Eklenen linkin bilgilerini al
    $select_query = "SELECT * FROM links WHERE id = ?";
    $stmt = $db->prepare($select_query);
    $stmt->execute([$link_id]);
    $link = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => __('link_added'),
        'link' => $link
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => __('link_add_error'),
        'error' => $e->getMessage()
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => __('unexpected_error'),
        'error' => $e->getMessage()
    ]);
} 