<?php
require_once 'config/database.php';

// URL'den kullanıcı adını al
$username = isset($_GET['username']) ? trim($_GET['username']) : '';

// Eğer URL'den alınamadıysa, path'den almayı dene
if (empty($username)) {
    $request_uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($request_uri, PHP_URL_PATH);
    $username = trim(ltrim($path, '/'));
}

if (empty($username)) {
    header("Location: /");
    exit;
}

// Admin, user, assets gibi sistem klasörlerini kontrol et
$system_folders = ['admin', 'user', 'assets', 'uploads'];
if (in_array(strtolower($username), $system_folders)) {
    header("Location: /");
    exit;
}

// Kullanıcı bilgilerini al
$query = "SELECT * FROM users WHERE username = ?";
$stmt = $db->prepare($query);
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: /");
    exit;
}

// Kullanıcının aktif linklerini al
$links_query = "SELECT * FROM links WHERE user_id = ? AND is_active = 1 ORDER BY order_number ASC";
$stmt = $db->prepare($links_query);
$stmt->execute([$user['id']]);
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ziyaret kaydı ekle
$ip = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];
$visit_query = "INSERT INTO visits (user_id, ip_address, user_agent) VALUES (?, ?, ?)";
$stmt = $db->prepare($visit_query);
$stmt->execute([$user['id'], $ip, $user_agent]);

// Meta etiketleri için açıklama
$meta_description = $user['profile_description'] ?? $user['username'] . ' - LinkSpot profili';
$meta_description = substr(strip_tags($meta_description), 0, 160);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <title><?php echo htmlspecialchars($user['profile_title'] ?? $user['username']); ?> - LinkSpot</title>
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="profile">
    <meta property="og:url" content="<?php echo "https://$_SERVER[HTTP_HOST]/$username"; ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($user['profile_title'] ?? $user['username']); ?> - LinkSpot">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <?php if ($user['profile_image']): ?>
    <meta property="og:image" content="<?php echo "https://$_SERVER[HTTP_HOST]/" . htmlspecialchars($user['profile_image']); ?>">
    <?php endif; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .profile-container {
            max-width: 680px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            border: 3px solid <?php echo $user['theme_color'] ?? '#000000'; ?>;
        }
        .link-card {
            background: white;
            border: 1px solid rgba(0,0,0,.125);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .link-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,.1);
        }
        .footer {
            margin-top: auto;
            text-align: center;
            padding: 1rem;
            color: #6c757d;
        }
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #212529;
                color: #f8f9fa;
            }
            .link-card {
                background: #343a40;
                border-color: #495057;
                color: #f8f9fa;
            }
            .text-muted {
                color: #adb5bd !important;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <img src="<?php echo $user['profile_image'] ? htmlspecialchars($user['profile_image']) : 'https://via.placeholder.com/150' ?>" 
                 alt="<?php echo htmlspecialchars($user['username']); ?>" 
                 class="profile-image">
            
            <h1 class="h3 mb-2"><?php echo htmlspecialchars($user['profile_title'] ?? $user['username']); ?></h1>
            
            <?php if (!empty($user['profile_description'])): ?>
                <p class="text-muted mb-4"><?php echo nl2br(htmlspecialchars($user['profile_description'])); ?></p>
            <?php endif; ?>
        </div>

        <div class="links-container">
            <?php foreach ($links as $link): ?>
                <a href="<?php echo htmlspecialchars($link['url']); ?>" 
                   target="_blank" 
                   class="link-card" 
                   onclick="recordClick(<?php echo $link['id']; ?>)">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-<?php echo htmlspecialchars($link['icon'] ?? 'link'); ?> me-3" 
                           style="font-size: 1.5rem; color: <?php echo $user['theme_color'] ?? '#000000'; ?>;"></i>
                        <span><?php echo htmlspecialchars($link['title']); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p class="mb-0">
                <a href="/" class="text-decoration-none text-muted">
                    Powered by LinkSpot
                </a>
            </p>
        </div>
    </footer>

    <script>
        function recordClick(linkId) {
            fetch('/record_click.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `link_id=${linkId}`
            }).catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html> 