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
    $icon = trim($_POST['icon'] ?? '');

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

    // Mevcut en yüksek sıra numarasını bul
    $order_query = "SELECT COALESCE(MAX(order_number), 0) as max_order FROM links WHERE user_id = ?";
    $stmt = $db->prepare($order_query);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $next_order = $result['max_order'] + 1;

    // Yeni linki ekle
    $insert_query = "INSERT INTO links (user_id, title, url, icon, order_number, is_active) VALUES (?, ?, ?, ?, ?, 1)";
    $stmt = $db->prepare($insert_query);
    $stmt->execute([$user_id, $title, $url, $icon, $next_order]);

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