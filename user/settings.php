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

// Şifre değiştirme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $db->prepare($update_query);
                
                if ($stmt->execute([$hashed_password, $user_id])) {
                    $success_message = 'Şifreniz başarıyla güncellendi.';
                } else {
                    $error_message = 'Şifre güncellenirken bir hata oluştu.';
                }
            } else {
                $error_message = 'Yeni şifre en az 6 karakter olmalıdır.';
            }
        } else {
            $error_message = 'Yeni şifreler eşleşmiyor.';
        }
    } else {
        $error_message = 'Mevcut şifre yanlış.';
    }
}

// E-posta bildirimleri ayarları güncelleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_notifications'])) {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    
    $update_query = "UPDATE users SET email_notifications = ? WHERE id = ?";
    $stmt = $db->prepare($update_query);
    
    if ($stmt->execute([$email_notifications, $user_id])) {
        $success_message = 'Bildirim ayarlarınız güncellendi.';
    } else {
        $error_message = 'Ayarlar güncellenirken bir hata oluştu.';
    }
}

// Hesap silme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_account'])) {
    $password = trim($_POST['delete_account_password']);
    
    if (password_verify($password, $user['password'])) {
        try {
            $db->beginTransaction();
            
            // Profil resmini sil
            if ($user['profile_image'] && file_exists('../' . $user['profile_image'])) {
                unlink('../' . $user['profile_image']);
            }
            
            // Link resimlerini sil
            $links_query = "SELECT image FROM links WHERE user_id = ? AND image IS NOT NULL";
            $stmt = $db->prepare($links_query);
            $stmt->execute([$user_id]);
            $link_images = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($link_images as $image) {
                if (file_exists('../' . $image)) {
                    unlink('../' . $image);
                }
            }
            
            // Kullanıcıyı ve ilişkili verileri sil
            $delete_queries = [
                "DELETE FROM visits WHERE user_id = ?",
                "DELETE FROM links WHERE user_id = ?",
                "DELETE FROM social_profiles WHERE user_id = ?",
                "DELETE FROM users WHERE id = ?"
            ];
            
            foreach ($delete_queries as $query) {
                $stmt = $db->prepare($query);
                $stmt->execute([$user_id]);
            }
            
            $db->commit();
            
            // Oturumu sonlandır ve ana sayfaya yönlendir
            session_destroy();
            header("Location: ../?deleted=1");
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            $error_message = 'Hesap silinirken bir hata oluştu.';
        }
    } else {
        $error_message = 'Şifre yanlış. Hesabınızı silmek için doğru şifreyi girin.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayarlar - LinkSpot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .settings-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }
        .card-header {
            background: none;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            padding: 1.25rem;
        }
        .card-header h5 {
            margin: 0;
            font-weight: 600;
        }
        .card-body {
            padding: 1.5rem;
        }
        .btn-danger-soft {
            color: #dc3545;
            background-color: #fdf1f2;
            border-color: #fdf1f2;
        }
        .btn-danger-soft:hover {
            color: #fff;
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .form-label {
            font-weight: 500;
        }
        .alert {
            border-radius: 10px;
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
                        <a class="nav-link" href="profile.php">
                            <i class="bi bi-person"></i> Profil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="settings.php">
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

    <div class="settings-container">
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Şifre Değiştirme -->
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-key me-2"></i>Şifre Değiştir</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Mevcut Şifre</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Yeni Şifre</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="form-text">En az 6 karakter olmalıdır.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Yeni Şifre Tekrar</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="bi bi-check2 me-2"></i>Şifreyi Güncelle
                    </button>
                </form>
            </div>
        </div>

        <!-- E-posta Bildirimleri -->
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-bell me-2"></i>Bildirim Ayarları</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="email_notifications" 
                               name="email_notifications" <?php echo $user['email_notifications'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="email_notifications">
                            E-posta bildirimleri
                        </label>
                    </div>
                    <div class="form-text mb-3">
                        Ziyaret istatistikleri ve önemli güncellemeler hakkında e-posta bildirimleri alın.
                    </div>
                    <button type="submit" name="update_notifications" class="btn btn-primary">
                        <i class="bi bi-check2 me-2"></i>Ayarları Kaydet
                    </button>
                </form>
            </div>
        </div>

        <!-- Hesap Silme -->
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-exclamation-triangle me-2"></i>Hesabı Sil</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">
                    Hesabınızı sildiğinizde, tüm verileriniz kalıcı olarak silinecektir. Bu işlem geri alınamaz.
                </p>
                <button type="button" class="btn btn-danger-soft" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                    <i class="bi bi-trash me-2"></i>Hesabı Sil
                </button>
            </div>
        </div>
    </div>

    <!-- Hesap Silme Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Hesabı Sil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Bu işlem geri alınamaz! Tüm verileriniz kalıcı olarak silinecektir.
                    </div>
                    <form method="POST" id="deleteAccountForm">
                        <div class="mb-3">
                            <label for="delete_account_password" class="form-label">Şifrenizi Girin</label>
                            <input type="password" class="form-control" id="delete_account_password" 
                                   name="delete_account_password" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" form="deleteAccountForm" name="delete_account" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>Hesabı Sil
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form doğrulama
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html> 