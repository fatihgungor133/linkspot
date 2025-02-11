<?php
require_once '../config/database.php';
session_start();

// Oturum ve AJAX kontrolü
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit('Yetkisiz erişim');
}

$user_id = $_SESSION['user_id'];
$title = trim($_POST['title']);
$url = trim($_POST['url']);
$icon = trim($_POST['icon'] ?? '');

$response = ['success' => false, 'message' => ''];

// Validasyon
if (empty($title) || empty($url)) {
    $response['message'] = 'Başlık ve URL alanları zorunludur.';
    echo json_encode($response);
    exit;
}

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    $response['message'] = 'Geçerli bir URL giriniz.';
    echo json_encode($response);
    exit;
}

try {
    // Mevcut en yüksek sıra numarasını bul
    $order_query = "SELECT MAX(order_number) as max_order FROM links WHERE user_id = ?";
    $stmt = $db->prepare($order_query);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $next_order = ($result['max_order'] ?? 0) + 1;

    // Yeni linki ekle
    $insert_query = "INSERT INTO links (user_id, title, url, icon, order_number) VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($insert_query);
    $stmt->execute([$user_id, $title, $url, $icon, $next_order]);

    $response = [
        'success' => true,
        'message' => 'Link başarıyla eklendi.',
        'link' => [
            'id' => $db->lastInsertId(),
            'title' => $title,
            'url' => $url,
            'icon' => $icon,
            'order_number' => $next_order
        ]
    ];
} catch(PDOException $e) {
    $response['message'] = 'Link eklenirken bir hata oluştu.';
}

echo json_encode($response); 