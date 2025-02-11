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

if (!$link_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz link ID.']);
    exit;
}

try {
    // Önce linkin bu kullanıcıya ait olduğunu kontrol et
    $check_query = "SELECT id FROM links WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($check_query);
    $stmt->execute([$link_id, $user_id]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bu linki silme yetkiniz yok.']);
        exit;
    }

    // Linki sil
    $delete_query = "DELETE FROM links WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($delete_query);
    $stmt->execute([$link_id, $user_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Link başarıyla silindi.',
        'link_id' => $link_id
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Link silinirken bir hata oluştu.']);
} 