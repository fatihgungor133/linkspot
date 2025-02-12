<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$theme_id = $data['theme_id'] ?? null;

if (!$theme_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tema ID gereklidir.']);
    exit;
}

try {
    // Önce temayı kontrol et
    $theme_query = "SELECT * FROM themes WHERE id = ?";
    $stmt = $db->prepare($theme_query);
    $stmt->execute([$theme_id]);
    $theme = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$theme) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tema bulunamadı.']);
        exit;
    }

    // Premium tema kontrolü
    if ($theme['is_premium']) {
        $user_query = "SELECT is_premium FROM users WHERE id = ?";
        $stmt = $db->prepare($user_query);
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user['is_premium']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bu tema sadece premium üyeler için kullanılabilir.']);
            exit;
        }
    }

    // Temayı uygula
    $update_query = "UPDATE users SET 
                    theme_id = ?,
                    theme_color = ?,
                    theme_bg = ?,
                    theme_text = ?,
                    theme_card_bg = ?,
                    theme_style = ?
                    WHERE id = ?";
    
    $stmt = $db->prepare($update_query);
    $stmt->execute([
        $theme_id,
        $theme['theme_color'],
        $theme['theme_bg'],
        $theme['theme_text'],
        $theme['theme_card_bg'],
        $theme['theme_style'],
        $user_id
    ]);

    echo json_encode([
        'success' => true, 
        'message' => 'Tema başarıyla uygulandı.',
        'theme' => $theme
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Tema uygulanırken bir hata oluştu.']);
} 