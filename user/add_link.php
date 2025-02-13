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
        
        // URL'den görseli indir ve içerik türünü kontrol et
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'follow_location' => 1,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ]
        ]);
        
        $headers = @get_headers($image_url, 1, $context);
        if ($headers === false) {
            echo json_encode(['success' => false, 'message' => __('image_download_error')]);
            exit;
        }
        
        // HTTP durum kodunu kontrol et
        $status_line = $headers[0];
        if (!preg_match("/200/", $status_line)) {
            echo json_encode(['success' => false, 'message' => __('image_download_error')]);
            exit;
        }
        
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
            $image_path = str_replace('../', '', $upload_path);
        } else {
            echo json_encode(['success' => false, 'message' => __('upload_error')]);
            exit;
        }
    } else {
        $image_path = null;
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