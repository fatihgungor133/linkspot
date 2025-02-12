<?php
require_once '../config/database.php';
session_start();

// JSON yanıt başlığı
header('Content-Type: application/json');

// Oturum ve yetki kontrolü
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

// POST metodu kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$link_id = filter_input(INPUT_POST, 'link_id', FILTER_VALIDATE_INT);
$title = trim($_POST['title'] ?? '');
$url = trim($_POST['url'] ?? '');
$is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

// Validasyon
if (!$link_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz link ID.']);
    exit;
}

if (empty($title) || empty($url)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Başlık ve URL alanları zorunludur.']);
    exit;
}

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçerli bir URL giriniz.']);
    exit;
}

try {
    // Önce linkin bu kullanıcıya ait olduğunu kontrol et
    $check_query = "SELECT * FROM links WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($check_query);
    $stmt->execute([$link_id, $user_id]);
    $current_link = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_link) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bu linki düzenleme yetkiniz yok.']);
        exit;
    }

    // Görsel yükleme işlemi
    $image_path = $current_link['image']; // Mevcut görsel yolunu koru
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
            // Eski görseli sil
            if ($current_link['image'] && file_exists('../' . $current_link['image'])) {
                unlink('../' . $current_link['image']);
            }
            $image_path = 'uploads/links/' . $new_filename;
        }
    }

    // Linki güncelle
    $update_query = "UPDATE links SET 
                    title = ?, 
                    url = ?, 
                    image = ?,
                    is_active = ?
                    WHERE id = ? AND user_id = ?";
    
    $stmt = $db->prepare($update_query);
    $stmt->execute([$title, $url, $image_path, $is_active, $link_id, $user_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Link başarıyla güncellendi.',
        'link' => [
            'id' => $link_id,
            'title' => $title,
            'url' => $url,
            'image' => $image_path,
            'is_active' => $is_active
        ]
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Link güncellenirken bir hata oluştu.']);
} 