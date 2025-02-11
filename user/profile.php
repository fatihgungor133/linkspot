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

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $profile_title = trim($_POST['profile_title']);
    $profile_description = trim($_POST['profile_description']);
    $theme_color = trim($_POST['theme_color']);
    
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
                        theme_color = ?";
        $params = [$profile_title, $profile_description, $theme_color];

        if (isset($profile_image)) {
            $update_query .= ", profile_image = ?";
            $params[] = $profile_image;
        }

        $update_query .= " WHERE id = ?";
        $params[] = $user_id;

        $stmt = $db->prepare($update_query);
        $stmt->execute($params);

        $success_message = 'Profil bilgileriniz başarıyla güncellendi.';
        
        // Güncel kullanıcı bilgilerini al
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
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
                                <label for="theme_color" class="form-label">Tema Rengi</label>
                                <input type="color" class="form-control form-control-color" id="theme_color" name="theme_color" 
                                       value="<?php echo htmlspecialchars($user['theme_color'] ?? '#000000'); ?>">
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
    <script>
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
                    // Profil resmini güncelle
                    document.getElementById('profileImage').src = '../' + data.profile_image;
                    showAlert('success', data.message);
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
    </script>
</body>
</html> 