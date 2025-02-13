<?php
require_once '../config/database.php';
require_once '../includes/language.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
        
        // URL'den görseli indir
        $image_content = @file_get_contents($image_url);
        if ($image_content === false) {
            echo json_encode(['success' => false, 'message' => __('image_download_error')]);
            exit;
        }

        // Geçici dosya oluştur
        $temp_file = tempnam(sys_get_temp_dir(), 'img_');
        file_put_contents($temp_file, $image_content);
        
        // Görsel türünü dosya içeriğine göre kontrol et
        $image_info = getimagesize($temp_file);
        if ($image_info === false) {
            unlink($temp_file);
            echo json_encode(['success' => false, 'message' => __('image_type_error')]);
            exit;
        }
        
        $mime_type = $image_info['mime'];
        $allowed_types = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif'
        ];
        
        if (!in_array(strtolower($mime_type), $allowed_types)) {
            unlink($temp_file);
            echo json_encode(['success' => false, 'message' => __('image_type_error')]);
            exit;
        }
        
        // Uzantıyı mime type'a göre belirle
        $ext = 'jpg';
        switch($mime_type) {
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
            if (!mkdir($uploads_dir, 0777, true)) {
                unlink($temp_file);
                echo json_encode(['success' => false, 'message' => __('upload_error')]);
                exit;
            }
            chmod($uploads_dir, 0777);
        }
        
        // Benzersiz dosya adı oluştur
        $new_filename = uniqid('link_') . '.' . $ext;
        $upload_path = $uploads_dir . '/' . $new_filename;
        
        // Görseli kaydet
        if (copy($temp_file, $upload_path)) {
            chmod($upload_path, 0644);
            unlink($temp_file);
            $image_path = str_replace('../', '', $upload_path);
        } else {
            unlink($temp_file);
            echo json_encode(['success' => false, 'message' => __('upload_error')]);
            exit;
        }
    }
    // Dosya yükleme ile görsel ekleme
    else if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Görsel türünü kontrol et
        $image_info = getimagesize($_FILES['image']['tmp_name']);
        if ($image_info === false) {
            echo json_encode(['success' => false, 'message' => __('image_type_error')]);
            exit;
        }
        
        $mime_type = $image_info['mime'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        
        if (!in_array($mime_type, $allowed_types)) {
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
            if (!mkdir($uploads_dir, 0777, true)) {
                echo json_encode(['success' => false, 'message' => __('upload_error')]);
                exit;
            }
            chmod($uploads_dir, 0777);
        }

        // Benzersiz dosya adı oluştur
        $new_filename = uniqid('link_') . '.' . $ext;
        $upload_path = $uploads_dir . '/' . $new_filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            chmod($upload_path, 0644);
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