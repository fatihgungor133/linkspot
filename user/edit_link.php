<?php
require_once '../config/database.php';
require_once '../includes/language.php';
session_start();

// JSON yanıt başlığı
header('Content-Type: application/json');

// Oturum ve yetki kontrolü
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => __('session_required')]);
    exit;
}

// POST metodu kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => __('invalid_request')]);
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
    echo json_encode(['success' => false, 'message' => __('invalid_link_id')]);
    exit;
}

if (empty($title) || empty($url)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => __('title_required')]);
    exit;
}

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => __('invalid_url')]);
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
        echo json_encode(['success' => false, 'message' => __('no_permission')]);
        exit;
    }

    // URL ile görsel ekleme
    $image_path = $current_link['image']; // Mevcut görsel yolunu koru

    if (!empty($_POST['image_url'])) {
        $image_url = trim($_POST['image_url']);
        
        // URL'nin geçerli olup olmadığını kontrol et
        if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => __('invalid_image_url')]);
            exit;
        }
        
        // URL'den görseli indir ve içerik türünü kontrol et
        $headers = get_headers($image_url, 1);
        $content_type = $headers['Content-Type'];
        
        if (is_array($content_type)) {
            $content_type = end($content_type);
        }
        
        $allowed_types = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif'
        ];
        
        if (!in_array(strtolower($content_type), $allowed_types)) {
            echo json_encode(['success' => false, 'message' => __('image_type_error')]);
            exit;
        }
        
        // Uzantıyı content type'a göre belirle
        $ext = 'jpg';
        switch($content_type) {
            case 'image/png':
                $ext = 'png';
                break;
            case 'image/gif':
                $ext = 'gif';
                break;
            default:
                $ext = 'jpg';
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
            // Eski görseli sil
            if ($current_link['image'] && file_exists('../' . $current_link['image'])) {
                unlink('../' . $current_link['image']);
            }
            $image_path = str_replace('../', '', $upload_path);
        } else {
            echo json_encode(['success' => false, 'message' => __('upload_error')]);
            exit;
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
            // Eski görseli sil
            if ($current_link['image'] && file_exists('../' . $current_link['image'])) {
                unlink('../' . $current_link['image']);
            }
            $image_path = str_replace('../', '', $upload_path);
        } else {
            echo json_encode(['success' => false, 'message' => __('upload_error')]);
            exit;
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
        'message' => __('link_updated'),
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
    echo json_encode(['success' => false, 'message' => __('link_update_error')]);
} 