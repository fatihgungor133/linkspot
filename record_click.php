<?php
require_once 'config/database.php';

header('Content-Type: application/json');

$link_id = filter_input(INPUT_POST, 'link_id', FILTER_VALIDATE_INT);

if (!$link_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz link ID.']);
    exit;
}

try {
    // Linkin var olduğunu ve aktif olduğunu kontrol et
    $check_query = "SELECT user_id FROM links WHERE id = ? AND is_active = 1";
    $stmt = $db->prepare($check_query);
    $stmt->execute([$link_id]);
    $link = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$link) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Link bulunamadı.']);
        exit;
    }

    // Ziyaret kaydı ekle
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $visit_query = "INSERT INTO visits (user_id, link_id, ip_address, user_agent) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($visit_query);
    $stmt->execute([$link['user_id'], $link_id, $ip, $user_agent]);

    echo json_encode(['success' => true]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ziyaret kaydedilirken bir hata oluştu.']);
} 