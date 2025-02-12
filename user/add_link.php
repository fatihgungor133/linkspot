<?php
require_once '../config/database.php';
session_start();

// JSON yanıt başlığı
header('Content-Type: application/json');

// Hata raporlamayı aktif et
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Oturum ve AJAX kontrolü
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $title = trim($_POST['title'] ?? '');
    $url = trim($_POST['url'] ?? '');
    
    // Validasyon
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Başlık alanı zorunludur.']);
        exit;
    }

    if (empty($url)) {
        echo json_encode(['success' => false, 'message' => 'URL alanı zorunludur.']);
        exit;
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        echo json_encode(['success' => false, 'message' => 'Geçerli bir URL giriniz.']);
        exit;
    }

    // Görsel yükleme işlemi
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Sadece JPG, PNG ve GIF dosyaları yüklenebilir.']);
            exit;
        }

        // Dosya boyutunu kontrol et (max 2MB)
        if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Dosya boyutu 2MB\'dan büyük olamaz.']);
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

    // Mevcut en yüksek sıra numarasını bul
    $order_query = "SELECT COALESCE(MAX(order_number), 0) as max_order FROM links WHERE user_id = ?";
    $stmt = $db->prepare($order_query);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $next_order = $result['max_order'] + 1;

    // Yeni linki ekle
    $insert_query = "INSERT INTO links (user_id, title, url, image, order_number, is_active) VALUES (?, ?, ?, ?, ?, 1)";
    $stmt = $db->prepare($insert_query);
    $stmt->execute([$user_id, $title, $url, $image_path, $next_order]);

    $link_id = $db->lastInsertId();

    // Eklenen linkin bilgilerini al
    $select_query = "SELECT * FROM links WHERE id = ?";
    $stmt = $db->prepare($select_query);
    $stmt->execute([$link_id]);
    $link = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Link başarıyla eklendi.',
        'link' => $link
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Link eklenirken bir hata oluştu.',
        'error' => $e->getMessage()
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Beklenmeyen bir hata oluştu.',
        'error' => $e->getMessage()
    ]);
} 