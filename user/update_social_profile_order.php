<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$profiles = json_decode(file_get_contents('php://input'), true);

if (!is_array($profiles)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz veri formatı.']);
    exit;
}

try {
    $db->beginTransaction();

    $update_query = "UPDATE social_profiles SET order_number = ? WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($update_query);

    foreach ($profiles as $order => $profile_id) {
        $stmt->execute([$order, $profile_id, $user_id]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Profil sıralaması güncellendi.']);

} catch(PDOException $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sıralama güncellenirken bir hata oluştu.']);
} 