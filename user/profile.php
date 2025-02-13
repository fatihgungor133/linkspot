<?php
require_once '../config/database.php';
require_once '../includes/language.php';
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
        // URL ile görsel yükleme işlemi
        if (!empty($_POST['image_url'])) {
            $image_url = trim($_POST['image_url']);
            
            // URL'nin geçerli olup olmadığını kontrol et
            if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
                throw new Exception(__('invalid_image_url'));
            }
            
            // Görsel uzantısını kontrol et
            $ext = strtolower(pathinfo($image_url, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($ext, $allowed)) {
                throw new Exception(__('image_type_error'));
            }
            
            // Uploads klasörünü kontrol et ve oluştur
            if (!file_exists('../uploads/profiles')) {
                mkdir('../uploads/profiles', 0777, true);
            }
            
            // Benzersiz dosya adı oluştur
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = '../uploads/profiles/' . $new_filename;
            
            // URL'den görseli indir
            $image_content = @file_get_contents($image_url);
            if ($image_content === false) {
                throw new Exception(__('image_download_error'));
            }
            
            // Görseli kaydet
            if (file_put_contents($upload_path, $image_content)) {
                // Eski profil resmini sil
                if ($user['profile_image'] && file_exists('../' . $user['profile_image'])) {
                    unlink('../' . $user['profile_image']);
                }
                $profile_image = 'uploads/profiles/' . $new_filename;
            }
        }
        // Dosya yükleme ile görsel ekleme
        else if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
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
            $deactivate_query = "UPDATE social_profiles SET is_active = 0 WHERE user_id = ?";
            $stmt = $db->prepare($deactivate_query);
            $stmt->execute([$user_id]);

            foreach ($_POST['social_profiles'] as $index => $profile) {
                if (empty($profile['platform']) || empty($profile['url'])) {
                    continue;
                }

                // Icon değerini platform adından al
                $icon = strtolower($profile['platform']);

                if (!empty($profile['id'])) {
                    // Mevcut profili güncelle
                    $update_query = "UPDATE social_profiles 
                                   SET platform = ?, 
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
                        $icon,
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
                        $icon,
                        $index
                    ]);
                }
            }
        }

        $success_message = __('profile_update_success');
        
        // Güncel kullanıcı bilgilerini al
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Güncel sosyal medya profillerini al
        $stmt = $db->prepare($social_query);
    } catch(PDOException $e) {
        $error_message = __('profile_update_error');
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('edit_profile'); ?> - LinkSpot</title>
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
        .drag-handle {
            cursor: move;
            color: #6c757d;
        }
        .form-control-color {
            width: 100px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/user/dashboard.php">LinkSpot Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="/user/profile.php">
                            <i class="bi bi-person"></i> <?php echo __('profile'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/user/settings.php">
                            <i class="bi bi-gear"></i> <?php echo __('settings'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <?php echo language_selector(); ?>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/logout.php">
                            <i class="bi bi-box-arrow-right"></i> <?php echo __('logout'); ?>
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
                        <h4 class="mb-0"><?php echo __('edit_profile'); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>

                        <?php if ($error_message): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-4">
                                <div class="text-center mb-3">
                                    <img src="<?php echo $user['profile_image'] ? '../' . htmlspecialchars($user['profile_image']) : 'https://via.placeholder.com/150' ?>" 
                                         class="rounded-circle" 
                                         style="width: 150px; height: 150px; object-fit: cover;"
                                         id="profileImagePreview">
                                </div>
                                <div class="text-center">
                                    <input type="file" class="form-control d-none" id="profile_image" name="profile_image" 
                                           accept="image/jpeg,image/png,image/gif"
                                           onchange="uploadProfileImage(this)">
                                    <div class="mb-3">
                                        <input type="text" class="form-control mb-2" id="image_url" name="image_url" 
                                               placeholder="<?php echo __('image_url'); ?>">
                                    </div>
                                    <button type="button" class="btn btn-primary" onclick="document.getElementById('profile_image').click()">
                                        <i class="bi bi-upload"></i> <?php echo __('change_profile_image'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="profile_title" class="form-label"><?php echo __('profile_title'); ?></label>
                                <input type="text" class="form-control" id="profile_title" name="profile_title" 
                                       value="<?php echo htmlspecialchars($user['profile_title'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="profile_description" class="form-label"><?php echo __('profile_description'); ?></label>
                                <textarea class="form-control" id="profile_description" name="profile_description" 
                                          rows="4"><?php echo htmlspecialchars($user['profile_description'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="theme_color" class="form-label"><?php echo __('main_color'); ?></label>
                                <input type="color" class="form-control form-control-color" id="theme_color" name="theme_color" 
                                       value="<?php echo htmlspecialchars($user['theme_color'] ?? '#000000'); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="theme_bg" class="form-label"><?php echo __('background_color'); ?></label>
                                <input type="color" class="form-control form-control-color" id="theme_bg" name="theme_bg" 
                                       value="<?php echo htmlspecialchars($user['theme_bg'] ?? '#f8f9fa'); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="theme_text" class="form-label"><?php echo __('text_color'); ?></label>
                                <input type="color" class="form-control form-control-color" id="theme_text" name="theme_text" 
                                       value="<?php echo htmlspecialchars($user['theme_text'] ?? '#212529'); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="theme_card_bg" class="form-label"><?php echo __('card_background'); ?></label>
                                <input type="color" class="form-control form-control-color" id="theme_card_bg" name="theme_card_bg" 
                                       value="<?php echo htmlspecialchars($user['theme_card_bg'] ?? '#ffffff'); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="theme_style" class="form-label"><?php echo __('theme_style'); ?></label>
                                <select class="form-select" id="theme_style" name="theme_style">
                                    <option value="auto" <?php echo ($user['theme_style'] ?? 'auto') == 'auto' ? 'selected' : ''; ?>><?php echo __('auto_system'); ?></option>
                                    <option value="light" <?php echo ($user['theme_style'] ?? '') == 'light' ? 'selected' : ''; ?>><?php echo __('light_theme'); ?></option>
                                    <option value="dark" <?php echo ($user['theme_style'] ?? '') == 'dark' ? 'selected' : ''; ?>><?php echo __('dark_theme'); ?></option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <h5 class="mb-3"><?php echo __('theme_settings'); ?></h5>
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
                                                        <div class="d-flex align-items-center justify-content-center h-100">
                                                            <i class="bi bi-palette fs-1 text-muted"></i>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="card-body">
                                                    <h6 class="card-title"><?php echo htmlspecialchars($theme['name']); ?></h6>
                                                    <p class="card-text small text-muted"><?php echo htmlspecialchars($theme['description']); ?></p>
                                                    <div class="d-flex gap-1 mb-2">
                                                        <div class="rounded-circle" style="width: 20px; height: 20px; background-color: <?php echo $theme['theme_color']; ?>"></div>
                                                        <div class="rounded-circle" style="width: 20px; height: 20px; background-color: <?php echo $theme['theme_bg']; ?>"></div>
                                                        <div class="rounded-circle" style="width: 20px; height: 20px; background-color: <?php echo $theme['theme_card_bg']; ?>"></div>
                                                    </div>
                                                    <?php if ($theme['id'] == $user['theme_id']): ?>
                                                        <button type="button" class="btn btn-primary btn-sm w-100" disabled>
                                                            <i class="bi bi-check2"></i> <?php echo __('selected'); ?>
                                                        </button>
                                                    <?php elseif ($theme['is_premium'] && !$user['is_premium']): ?>
                                                        <button type="button" class="btn btn-warning btn-sm w-100" onclick="upgradeToPremium()">
                                                            <i class="bi bi-star"></i> <?php echo __('upgrade_to_premium'); ?>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-outline-primary btn-sm w-100" onclick="applyTheme(<?php echo $theme['id']; ?>)">
                                                            <?php echo __('apply_theme'); ?>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mb-4">
                                <div class="btn-group">
                                    <a href="../<?php echo $user['username']; ?>" target="_blank" class="btn btn-outline-primary">
                                        <i class="bi bi-eye"></i> <?php echo __('preview_profile'); ?>
                                    </a>
                                    <button type="button" class="btn btn-outline-primary" onclick="copyProfileUrl()">
                                        <i class="bi bi-clipboard"></i> <?php echo __('copy_profile_url'); ?>
                                    </button>
                                </div>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="confirmDiscard()">
                                        <i class="bi bi-x"></i> <?php echo __('discard_changes'); ?>
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check2"></i> <?php echo __('save_changes'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h5 class="mb-3"><?php echo __('social_profiles'); ?></h5>
                                <div id="socialProfiles">
                                    <?php foreach ($social_profiles as $index => $profile): ?>
                                    <div class="social-profile-item mb-3" data-id="<?php echo $profile['id']; ?>">
                                        <input type="hidden" name="social_profiles[<?php echo $index; ?>][id]" value="<?php echo $profile['id']; ?>">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-auto">
                                                <i class="bi bi-grip-vertical drag-handle" title="<?php echo __('drag_to_reorder'); ?>"></i>
                                            </div>
                                            <div class="col-md-3">
                                                <select class="form-select platform-select" name="social_profiles[<?php echo $index; ?>][platform]" required>
                                                    <option value=""><?php echo __('select_platform'); ?></option>
                                                    <option value="Facebook" data-icon="facebook" <?php echo $profile['platform'] == 'Facebook' ? 'selected' : ''; ?>>Facebook</option>
                                                    <option value="Twitter" data-icon="twitter" <?php echo $profile['platform'] == 'Twitter' ? 'selected' : ''; ?>>Twitter</option>
                                                    <option value="Instagram" data-icon="instagram" <?php echo $profile['platform'] == 'Instagram' ? 'selected' : ''; ?>>Instagram</option>
                                                    <option value="LinkedIn" data-icon="linkedin" <?php echo $profile['platform'] == 'LinkedIn' ? 'selected' : ''; ?>>LinkedIn</option>
                                                    <option value="GitHub" data-icon="github" <?php echo $profile['platform'] == 'GitHub' ? 'selected' : ''; ?>>GitHub</option>
                                                    <option value="YouTube" data-icon="youtube" <?php echo $profile['platform'] == 'YouTube' ? 'selected' : ''; ?>>YouTube</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="text" class="form-control" 
                                                       name="social_profiles[<?php echo $index; ?>][username]" 
                                                       placeholder="<?php echo __('username'); ?>"
                                                       value="<?php echo htmlspecialchars($profile['username']); ?>">
                                            </div>
                                            <div class="col">
                                                <input type="url" class="form-control" 
                                                       name="social_profiles[<?php echo $index; ?>][url]" 
                                                       placeholder="<?php echo __('profile_url'); ?>"
                                                       value="<?php echo htmlspecialchars($profile['url']); ?>" required>
                                            </div>
                                            <div class="col-auto">
                                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeSocialProfile(this)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-outline-primary" onclick="addSocialProfile()">
                                    <i class="bi bi-plus"></i> <?php echo __('add_social'); ?>
                                </button>
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
                <div class="row g-2 align-items-center">
                    <div class="col-auto">
                        <i class="bi bi-grip-vertical drag-handle" title="<?php echo __('drag_to_reorder'); ?>"></i>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select platform-select" name="social_profiles[${socialProfileCount}][platform]" required>
                            <option value=""><?php echo __('select_platform'); ?></option>
                            <option value="Facebook" data-icon="facebook">Facebook</option>
                            <option value="Twitter" data-icon="twitter">Twitter</option>
                            <option value="Instagram" data-icon="instagram">Instagram</option>
                            <option value="LinkedIn" data-icon="linkedin">LinkedIn</option>
                            <option value="GitHub" data-icon="github">GitHub</option>
                            <option value="YouTube" data-icon="youtube">YouTube</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" 
                               name="social_profiles[${socialProfileCount}][username]" 
                               placeholder="<?php echo __('username'); ?>">
                    </div>
                    <div class="col">
                        <input type="url" class="form-control" 
                               name="social_profiles[${socialProfileCount}][url]" 
                               placeholder="<?php echo __('profile_url'); ?>" required>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeSocialProfile(this)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('socialProfiles').insertAdjacentHTML('beforeend', template);
        socialProfileCount++;
    }

    function removeSocialProfile(button) {
        button.closest('.social-profile-item').remove();
    }

    // Sürükle-bırak sıralama için Sortable.js ayarları
    new Sortable(document.getElementById('socialProfiles'), {
        handle: '.drag-handle',
        animation: 150
    });

    function applyTheme(themeId) {
        fetch('apply_theme.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                theme_id: themeId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', '<?php echo __('theme_update_success'); ?>');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showAlert('danger', '<?php echo __('theme_update_error'); ?>');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', '<?php echo __('theme_update_error'); ?>');
        });
    }

    function upgradeToPremium() {
        alert('<?php echo __('premium_feature_alert'); ?>');
    }

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
                    showAlert('success', '<?php echo __('profile_image_updated'); ?>');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showAlert('danger', data.message);
                }
            })
            .catch(error => {
                showAlert('danger', '<?php echo __('profile_image_update_error'); ?>');
                console.error('Error:', error);
            });
        }
    }

    // Tooltip'leri etkinleştir
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Profil URL'ini kopyalama
    function copyProfileUrl() {
        const url = window.location.origin + '/' + '<?php echo $user['username']; ?>';
        navigator.clipboard.writeText(url).then(function() {
            showAlert('success', '<?php echo __('profile_url_copied'); ?>');
        }).catch(function() {
            showAlert('danger', '<?php echo __('profile_url_copy_error'); ?>');
        });
    }

    // Sıralama güncellemesi
    function updateOrder(items) {
        fetch('update_social_profile_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(items)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', '<?php echo __('order_updated'); ?>');
            } else {
                showAlert('danger', '<?php echo __('order_update_error'); ?>');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', '<?php echo __('order_update_error'); ?>');
        });
    }

    // Değişiklikleri iptal etme onayı
    function confirmDiscard() {
        if (confirm('<?php echo __('confirm_discard'); ?>')) {
            window.location.reload();
        }
    }

    // Uyarı gösterme
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
    </script>
</body>
</html> 