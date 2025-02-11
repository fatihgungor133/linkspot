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
$icon = trim($_POST['icon'] ?? '');
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
    $check_query = "SELECT id FROM links WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($check_query);
    $stmt->execute([$link_id, $user_id]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bu linki düzenleme yetkiniz yok.']);
        exit;
    }

    // Linki güncelle
    $update_query = "UPDATE links SET 
                    title = ?, 
                    url = ?, 
                    icon = ?,
                    is_active = ?
                    WHERE id = ? AND user_id = ?";
    
    $stmt = $db->prepare($update_query);
    $stmt->execute([$title, $url, $icon, $is_active, $link_id, $user_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Link başarıyla güncellendi.',
        'link' => [
            'id' => $link_id,
            'title' => $title,
            'url' => $url,
            'icon' => $icon,
            'is_active' => $is_active
        ]
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Link güncellenirken bir hata oluştu.']);
} 