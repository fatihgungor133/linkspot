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

// Kullanıcının sosyal medya profillerini al
$social_query = "SELECT * FROM social_profiles WHERE user_id = ? AND is_active = 1 ORDER BY order_number ASC";
$stmt = $db->prepare($social_query);
$stmt->execute([$user['id']]);
$social_profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    
    <?php if ($user['theme_id']): ?>
        <?php
        // Tema bilgilerini al
        $theme_query = "SELECT * FROM themes WHERE id = ?";
        $stmt = $db->prepare($theme_query);
        $stmt->execute([$user['theme_id']]);
        $theme = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($theme && $theme['css_code']): ?>
            <style>
                <?php echo $theme['css_code']; ?>
            </style>
        <?php endif; ?>
    <?php endif; ?>
    
    <style>
        :root {
            --theme-color: <?php echo $user['theme_color'] ?? '#000000'; ?>;
            --theme-bg: <?php echo $user['theme_bg'] ?? '#f8f9fa'; ?>;
            --theme-text: <?php echo $user['theme_text'] ?? '#212529'; ?>;
            --theme-card-bg: <?php echo $user['theme_card_bg'] ?? '#ffffff'; ?>;
        }
        body {
            background-color: var(--theme-bg);
            color: var(--theme-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .profile-header {
            text-align: center;
            margin-bottom: 2.5rem;
            padding: 2rem;
            background: var(--theme-card-bg);
            border-radius: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .profile-image {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1.5rem;
            border: 4px solid var(--theme-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .profile-image:hover {
            transform: scale(1.05);
        }
        .link-card {
            background: var(--theme-card-bg);
            border: none;
            border-radius: 15px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            color: var(--theme-text);
            display: block;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .link-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
        .footer {
            margin-top: auto;
            text-align: center;
            padding: 2rem;
            color: #6c757d;
            background: var(--theme-card-bg);
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        }
        .share-buttons {
            margin: 1.5rem 0;
            display: flex;
            justify-content: center;
            gap: 0.75rem;
        }
        .share-button {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }
        .share-button:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            color: #fff;
        }
        .social-buttons {
            margin: 1.5rem 0;
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .social-button {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.2rem;
            background-color: var(--theme-color);
        }
        .social-button:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            color: #fff;
        }
        .profile-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--theme-text);
        }
        .profile-description {
            font-size: 1.1rem;
            color: #6c757d;
            max-width: 600px;
            margin: 0 auto 1.5rem;
            line-height: 1.6;
        }
        .links-container {
            padding: 0.5rem;
        }
        .link-card .link-title {
            font-size: 1.1rem;
            font-weight: 500;
        }
        .link-card .link-image {
            width: 32px;
            height: 32px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 1rem;
        }
        .link-card .bi {
            font-size: 1.4rem;
        }
        @media (prefers-color-scheme: dark) {
            body.theme-auto {
                --theme-bg: #1a1a1a;
                --theme-text: #ffffff;
                --theme-card-bg: #2d2d2d;
            }
        }
        body.theme-dark {
            --theme-bg: #1a1a1a;
            --theme-text: #ffffff;
            --theme-card-bg: #2d2d2d;
        }
        .link-card-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .link-card-left {
            display: flex;
            align-items: center;
            flex: 1;
        }
        .link-card-right {
            opacity: 0;
            transition: opacity 0.2s;
        }
        .link-card:hover .link-card-right {
            opacity: 1;
        }
        .link-card-right .bi {
            font-size: 1.2rem;
            color: var(--theme-color);
        }
    </style>
</head>
<body class="theme-<?php echo $user['theme_style'] ?? 'auto'; ?>">
    <div class="profile-container">
        <div class="profile-header">
            <img src="<?php echo $user['profile_image'] ? htmlspecialchars($user['profile_image']) : 'https://via.placeholder.com/150' ?>" 
                 alt="<?php echo htmlspecialchars($user['username']); ?>" 
                 class="profile-image">
            
            <h1 class="profile-title"><?php echo htmlspecialchars($user['profile_title'] ?? $user['username']); ?></h1>
            
            <?php if (!empty($user['profile_description'])): ?>
                <p class="profile-description"><?php echo nl2br(htmlspecialchars($user['profile_description'])); ?></p>
            <?php endif; ?>

            <?php if (!empty($social_profiles)): ?>
            <div class="social-buttons">
                <?php foreach ($social_profiles as $profile): ?>
                    <a href="<?php echo htmlspecialchars($profile['url']); ?>" 
                       target="_blank" 
                       class="social-button" 
                       title="<?php echo htmlspecialchars($profile['platform']); ?>"
                       data-bs-toggle="tooltip">
                        <i class="bi bi-<?php echo htmlspecialchars($profile['icon'] ?? strtolower($profile['platform'])); ?>"></i>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="links-container">
            <?php foreach ($links as $link): ?>
                <a href="<?php echo htmlspecialchars($link['url']); ?>" 
                   target="_blank" 
                   class="link-card" 
                   onclick="recordClick(<?php echo $link['id']; ?>)">
                    <div class="link-card-content">
                        <div class="link-card-left">
                            <?php if (!empty($link['image'])): ?>
                                <img src="<?php echo htmlspecialchars($link['image']); ?>" 
                                     alt="" 
                                     class="link-image">
                            <?php else: ?>
                                <i class="bi bi-link me-3" style="color: var(--theme-color);"></i>
                            <?php endif; ?>
                            <span class="link-title"><?php echo htmlspecialchars($link['title']); ?></span>
                        </div>
                        <div class="link-card-right">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tooltip'leri etkinleştir
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })

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