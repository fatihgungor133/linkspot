<?php
require_once '../config/database.php';
session_start();

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Kullanıcı bilgilerini al
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Sosyal medya profillerini al
$social_query = "SELECT * FROM social_profiles WHERE user_id = ? ORDER BY order_number ASC";
$stmt = $db->prepare($social_query);
$stmt->execute([$user_id]);
$social_profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tema listesini al
$themes_query = "SELECT * FROM themes ORDER BY is_premium ASC, name ASC";
$stmt = $db->prepare($themes_query);
$stmt->execute();
$themes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $profile_title = trim($_POST['profile_title']);
    $profile_description = trim($_POST['profile_description']);
    $theme_color = trim($_POST['theme_color']);
    $theme_bg = trim($_POST['theme_bg']);
    $theme_text = trim($_POST['theme_text']);
    $theme_card_bg = trim($_POST['theme_card_bg']);
    $theme_style = trim($_POST['theme_style']);
    
    try {
        // Profil resmi yükleme işlemi
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_filename = uniqid() . '.' . $ext;
                $upload_path = '../uploads/profiles/' . $new_filename;
                
                // Uploads klasörü yoksa oluştur
                if (!file_exists('../uploads/profiles')) {
                    mkdir('../uploads/profiles', 0777, true);
                }
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    // Eski profil resmini sil
                    if ($user['profile_image'] && file_exists('../' . $user['profile_image'])) {
                        unlink('../' . $user['profile_image']);
                    }
                    $profile_image = 'uploads/profiles/' . $new_filename;
                }
            }
        }

        // Profil bilgilerini güncelle
        $update_query = "UPDATE users SET 
                        profile_title = ?, 
                        profile_description = ?, 
                        theme_color = ?,
                        theme_bg = ?,
                        theme_text = ?,
                        theme_card_bg = ?,
                        theme_style = ?";
        $params = [$profile_title, $profile_description, $theme_color, $theme_bg, $theme_text, $theme_card_bg, $theme_style];

        if (isset($profile_image)) {
            $update_query .= ", profile_image = ?";
            $params[] = $profile_image;
        }

        $update_query .= " WHERE id = ?";
        $params[] = $user_id;

        $stmt = $db->prepare($update_query);
        $stmt->execute($params);

        // Sosyal medya profillerini güncelle
        if (isset($_POST['social_profiles'])) {
            // Önce tüm profilleri pasife çek
            $update_query = "UPDATE social_profiles SET is_active = 0 WHERE user_id = ?";
            $stmt = $db->prepare($update_query);
            $stmt->execute([$user_id]);

            foreach ($_POST['social_profiles'] as $index => $profile) {
                if (empty($profile['platform']) || empty($profile['username']) || empty($profile['url'])) {
                    continue;
                }

                if (!empty($profile['id'])) {
                    // Mevcut profili güncelle
                    $update_query = "UPDATE social_profiles SET 
                                    platform = ?, 
                                    username = ?, 
                                    url = ?, 
                                    icon = ?,
                                    order_number = ?,
                                    is_active = 1
                                    WHERE id = ? AND user_id = ?";
                    $stmt = $db->prepare($update_query);
                    $stmt->execute([
                        $profile['platform'],
                        $profile['username'],
                        $profile['url'],
                        $profile['icon'],
                        $index,
                        $profile['id'],
                        $user_id
                    ]);
                } else {
                    // Yeni profil ekle
                    $insert_query = "INSERT INTO social_profiles 
                                    (user_id, platform, username, url, icon, order_number, is_active) 
                                    VALUES (?, ?, ?, ?, ?, ?, 1)";
                    $stmt = $db->prepare($insert_query);
                    $stmt->execute([
                        $user_id,
                        $profile['platform'],
                        $profile['username'],
                        $profile['url'],
                        $profile['icon'],
                        $index
                    ]);
                }
            }
        }

        $success_message = 'Profil bilgileriniz başarıyla güncellendi.';
        
        // Güncel kullanıcı bilgilerini al
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Güncel sosyal medya profillerini al
        $stmt = $db->prepare($social_query);
    } catch(PDOException $e) {
        $error_message = 'Profil güncellenirken bir hata oluştu.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Düzenle - LinkSpot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .social-profile-item {
            cursor: move;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: background-color 0.2s;
        }
        .social-profile-item:hover {
            background-color: #f8f9fa;
        }
        .social-profile-item.sortable-ghost {
            opacity: 0.5;
            background-color: #e9ecef;
        }
        .social-profile-item .drag-handle {
            cursor: move;
            color: #6c757d;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">LinkSpot Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">
                            <i class="bi bi-person"></i> Profil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="bi bi-gear"></i> Ayarlar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="bi bi-box-arrow-right"></i> Çıkış
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Profil Düzenle</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>

                        <?php if ($error_message): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="text-center mb-4">
                                <div class="position-relative d-inline-block">
                                    <img src="<?php echo $user['profile_image'] ? '../' . $user['profile_image'] : 'https://via.placeholder.com/150' ?>" 
                                         class="rounded-circle mb-3" 
                                         id="profileImage"
                                         style="width: 150px; height: 150px; object-fit: cover;">
                                    <label for="profile_image" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2" style="cursor: pointer;">
                                        <i class="bi bi-camera"></i>
                                    </label>
                                    <input type="file" 
                                           class="d-none" 
                                           id="profile_image" 
                                           name="profile_image" 
                                           accept="image/jpeg,image/png,image/gif"
                                           onchange="uploadProfileImage(this)">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="profile_title" class="form-label">Profil Başlığı</label>
                                <input type="text" class="form-control" id="profile_title" name="profile_title" 
                                       value="<?php echo htmlspecialchars($user['profile_title'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="profile_description" class="form-label">Profil Açıklaması</label>
                                <textarea class="form-control" id="profile_description" name="profile_description" 
                                          rows="4"><?php echo htmlspecialchars($user['profile_description'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="theme_color" class="form-label">Ana Renk</label>
                                <input type="color" class="form-control form-control-color" id="theme_color" name="theme_color" 
                                       value="<?php echo htmlspecialchars($user['theme_color'] ?? '#000000'); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="theme_bg" class="form-label">Arkaplan Rengi</label>
                                <input type="color" class="form-control form-control-color" id="theme_bg" name="theme_bg" 
                                       value="<?php echo htmlspecialchars($user['theme_bg'] ?? '#f8f9fa'); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="theme_text" class="form-label">Metin Rengi</label>
                                <input type="color" class="form-control form-control-color" id="theme_text" name="theme_text" 
                                       value="<?php echo htmlspecialchars($user['theme_text'] ?? '#212529'); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="theme_card_bg" class="form-label">Kart Arkaplan Rengi</label>
                                <input type="color" class="form-control form-control-color" id="theme_card_bg" name="theme_card_bg" 
                                       value="<?php echo htmlspecialchars($user['theme_card_bg'] ?? '#ffffff'); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="theme_style" class="form-label">Tema Stili</label>
                                <select class="form-select" id="theme_style" name="theme_style">
                                    <option value="auto" <?php echo ($user['theme_style'] ?? 'auto') == 'auto' ? 'selected' : ''; ?>>Otomatik (Sistem)</option>
                                    <option value="light" <?php echo ($user['theme_style'] ?? '') == 'light' ? 'selected' : ''; ?>>Açık Tema</option>
                                    <option value="dark" <?php echo ($user['theme_style'] ?? '') == 'dark' ? 'selected' : ''; ?>>Koyu Tema</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <h5 class="mb-3">Tema Seçimi</h5>
                                <div class="row g-3">
                                    <?php foreach ($themes as $theme): ?>
                                        <div class="col-md-4">
                                            <div class="card h-100 <?php echo $theme['id'] == $user['theme_id'] ? 'border-primary' : ''; ?>">
                                                <?php if ($theme['thumbnail']): ?>
                                                    <img src="<?php echo htmlspecialchars($theme['thumbnail']); ?>" 
                                                         class="card-img-top" alt="<?php echo htmlspecialchars($theme['name']); ?>"
                                                         style="height: 120px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="card-img-top bg-light" style="height: 120px;">
                                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center">
                                                            <i class="bi bi-palette fs-1"></i>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="card-body">
                                                    <h6 class="card-title d-flex align-items-center justify-content-between">
                                                        <?php echo htmlspecialchars($theme['name']); ?>
                                                        <?php if ($theme['is_premium']): ?>
                                                            <span class="badge bg-warning">Premium</span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <p class="card-text small text-muted"><?php echo htmlspecialchars($theme['description']); ?></p>
                                                    <div class="d-flex gap-1 mb-2">
                                                        <div class="rounded-circle" style="width: 20px; height: 20px; background-color: <?php echo $theme['theme_color']; ?>"></div>
                                                        <div class="rounded-circle" style="width: 20px; height: 20px; background-color: <?php echo $theme['theme_bg']; ?>"></div>
                                                        <div class="rounded-circle" style="width: 20px; height: 20px; background-color: <?php echo $theme['theme_card_bg']; ?>"></div>
                                                    </div>
                                                    <?php if ($theme['id'] == $user['theme_id']): ?>
                                                        <button type="button" class="btn btn-primary btn-sm w-100" disabled>
                                                            <i class="bi bi-check2"></i> Seçili
                                                        </button>
                                                    <?php elseif ($theme['is_premium'] && !$user['is_premium']): ?>
                                                        <button type="button" class="btn btn-warning btn-sm w-100" onclick="upgradeToPremium()">
                                                            <i class="bi bi-star"></i> Premium'a Yükselt
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-outline-primary btn-sm w-100" onclick="applyTheme(<?php echo $theme['id']; ?>)">
                                                            Temayı Uygula
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Sosyal Medya Profilleri</label>
                                <div id="socialProfiles">
                                    <?php foreach ($social_profiles as $index => $profile): ?>
                                    <div class="social-profile-item mb-3" data-id="<?php echo $profile['id']; ?>">
                                        <input type="hidden" name="social_profiles[<?php echo $index; ?>][id]" value="<?php echo $profile['id']; ?>">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-auto">
                                                <i class="bi bi-grip-vertical drag-handle"></i>
                                            </div>
                                            <div class="col-md-3">
                                                <select class="form-select platform-select" name="social_profiles[<?php echo $index; ?>][platform]" required>
                                                    <option value="">Platform Seçin</option>
                                                    <option value="Facebook" data-icon="facebook" <?php echo $profile['platform'] == 'Facebook' ? 'selected' : ''; ?>>Facebook</option>
                                                    <option value="Twitter" data-icon="twitter" <?php echo $profile['platform'] == 'Twitter' ? 'selected' : ''; ?>>Twitter</option>
                                                    <option value="Instagram" data-icon="instagram" <?php echo $profile['platform'] == 'Instagram' ? 'selected' : ''; ?>>Instagram</option>
                                                    <option value="LinkedIn" data-icon="linkedin" <?php echo $profile['platform'] == 'LinkedIn' ? 'selected' : ''; ?>>LinkedIn</option>
                                                    <option value="GitHub" data-icon="github" <?php echo $profile['platform'] == 'GitHub' ? 'selected' : ''; ?>>GitHub</option>
                                                    <option value="YouTube" data-icon="youtube" <?php echo $profile['platform'] == 'YouTube' ? 'selected' : ''; ?>>YouTube</option>
                                                    <option value="TikTok" data-icon="tiktok" <?php echo $profile['platform'] == 'TikTok' ? 'selected' : ''; ?>>TikTok</option>
                                                    <option value="Telegram" data-icon="telegram" <?php echo $profile['platform'] == 'Telegram' ? 'selected' : ''; ?>>Telegram</option>
                                                    <option value="Discord" data-icon="discord" <?php echo $profile['platform'] == 'Discord' ? 'selected' : ''; ?>>Discord</option>
                                                    <option value="Twitch" data-icon="twitch" <?php echo $profile['platform'] == 'Twitch' ? 'selected' : ''; ?>>Twitch</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="text" class="form-control" name="social_profiles[<?php echo $index; ?>][username]" 
                                                       placeholder="Kullanıcı Adı" value="<?php echo htmlspecialchars($profile['username']); ?>" required>
                                            </div>
                                            <div class="col-md-5">
                                                <input type="url" class="form-control" name="social_profiles[<?php echo $index; ?>][url]" 
                                                       placeholder="Profil URL" value="<?php echo htmlspecialchars($profile['url']); ?>" required>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-danger" onclick="removeSocialProfile(this)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            <input type="hidden" name="social_profiles[<?php echo $index; ?>][icon]" 
                                                   value="<?php echo htmlspecialchars($profile['icon'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-outline-primary" onclick="addSocialProfile()">
                                    <i class="bi bi-plus"></i> Sosyal Medya Ekle
                                </button>
                            </div>

                            <div class="text-end">
                                <a href="dashboard.php" class="btn btn-secondary">İptal</a>
                                <button type="submit" class="btn btn-primary">Kaydet</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
    let socialProfileCount = <?php echo count($social_profiles); ?>;

    function addSocialProfile() {
        const template = `
            <div class="social-profile-item mb-3">
                <div class="row g-2">
                    <div class="col-md-3">
                        <select class="form-select platform-select" name="social_profiles[${socialProfileCount}][platform]" required>
                            <option value="">Platform Seçin</option>
                            <option value="Facebook" data-icon="facebook">Facebook</option>
                            <option value="Twitter" data-icon="twitter">Twitter</option>
                            <option value="Instagram" data-icon="instagram">Instagram</option>
                            <option value="LinkedIn" data-icon="linkedin">LinkedIn</option>
                            <option value="GitHub" data-icon="github">GitHub</option>
                            <option value="YouTube" data-icon="youtube">YouTube</option>
                            <option value="TikTok" data-icon="tiktok">TikTok</option>
                            <option value="Telegram" data-icon="telegram">Telegram</option>
                            <option value="Discord" data-icon="discord">Discord</option>
                            <option value="Twitch" data-icon="twitch">Twitch</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="social_profiles[${socialProfileCount}][username]" 
                               placeholder="Kullanıcı Adı" required>
                    </div>
                    <div class="col-md-5">
                        <input type="url" class="form-control" name="social_profiles[${socialProfileCount}][url]" 
                               placeholder="Profil URL" required>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger" onclick="removeSocialProfile(this)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <input type="hidden" name="social_profiles[${socialProfileCount}][icon]">
                </div>
            </div>
        `;
        
        document.getElementById('socialProfiles').insertAdjacentHTML('beforeend', template);
        
        // Yeni eklenen profil için platform seçimi olayını ekle
        const newProfile = document.getElementById('socialProfiles').lastElementChild;
        const platformSelect = newProfile.querySelector('.platform-select');
        setupPlatformSelect(platformSelect);
        
        socialProfileCount++;
    }

    function removeSocialProfile(button) {
        button.closest('.social-profile-item').remove();
    }

    function setupPlatformSelect(select) {
        select.addEventListener('change', function() {
            const iconInput = this.closest('.social-profile-item').querySelector('input[name$="[icon]"]');
            const selectedOption = this.options[this.selectedIndex];
            iconInput.value = selectedOption.dataset.icon || selectedOption.value.toLowerCase();
        });
    }

    // Mevcut profiller için platform seçimi olaylarını ekle
    document.querySelectorAll('.platform-select').forEach(setupPlatformSelect);

    function uploadProfileImage(input) {
        if (input.files && input.files[0]) {
            const formData = new FormData();
            formData.append('profile_image', input.files[0]);

            fetch('upload_profile_image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showAlert('danger', data.message);
                }
            })
            .catch(error => {
                showAlert('danger', 'Bir hata oluştu. Lütfen tekrar deneyin.');
                console.error('Error:', error);
            });
        }
    }

    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.container');
        container.insertBefore(alertDiv, container.firstChild);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 3000);
    }

    // Sürükle-bırak sıralama için Sortable.js ayarları
    const socialProfilesContainer = document.getElementById('socialProfiles');
    const sortable = new Sortable(socialProfilesContainer, {
        animation: 150,
        handle: '.drag-handle',
        ghostClass: 'sortable-ghost',
        onEnd: function() {
            updateSocialProfilesOrder();
        }
    });

    function updateSocialProfilesOrder() {
        const items = socialProfilesContainer.querySelectorAll('.social-profile-item');
        const order = Array.from(items).map((item, index) => {
            const idInput = item.querySelector('input[name$="[id]"]');
            return idInput ? idInput.value : null;
        }).filter(id => id !== null);

        fetch('update_social_profile_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(order)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Sıralama güncellendi');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showAlert('danger', data.message || 'Sıralama güncellenirken bir hata oluştu');
            }
        })
        .catch(error => {
            showAlert('danger', 'Sıralama güncellenirken bir hata oluştu');
            console.error('Error:', error);
        });
    }

    function applyTheme(themeId) {
        fetch('apply_theme.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ theme_id: themeId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Tema başarıyla uygulandı');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showAlert('danger', data.message || 'Tema uygulanırken bir hata oluştu');
            }
        })
        .catch(error => {
            showAlert('danger', 'Tema uygulanırken bir hata oluştu');
            console.error('Error:', error);
        });
    }

    function upgradeToPremium() {
        // Premium yükseltme sayfasına yönlendir
        window.location.href = 'premium.php';
    }
    </script>
</body>
</html> 