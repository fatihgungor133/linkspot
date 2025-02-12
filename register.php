<?php
session_start();
require_once 'config/database.php';
require_once 'includes/language.php';

// Dil kontrolü
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'tr'; // Varsayılan dil
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $password_confirm = trim($_POST['password_confirm']);

    $errors = [];

    // Validasyon kontrolleri
    if (empty($username)) {
        $errors[] = "Kullanıcı adı gereklidir.";
    }
    if (empty($email)) {
        $errors[] = "E-posta adresi gereklidir.";
    }
    if (empty($password)) {
        $errors[] = "Şifre gereklidir.";
    }
    if ($password !== $password_confirm) {
        $errors[] = "Şifreler eşleşmiyor.";
    }

    if (empty($errors)) {
        $check_query = "SELECT id FROM users WHERE email = ? OR username = ?";
        $stmt = $db->prepare($check_query);
        $stmt->execute([$email, $username]);
        
        if ($stmt->rowCount() > 0) {
            $errors[] = "Bu e-posta adresi veya kullanıcı adı zaten kayıtlı.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $db->prepare($insert_query);
            
            try {
                $stmt->execute([$username, $email, $hashed_password]);
                $_SESSION['success'] = "Kayıt başarılı! Şimdi giriş yapabilirsiniz.";
                header("Location: login.php");
                exit;
            } catch(PDOException $e) {
                $errors[] = "Kayıt sırasında bir hata oluştu.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('register'); ?> - LinkSpot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .register-container {
            max-width: 400px;
            margin: 60px auto;
        }
        .card {
            border: none;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 2rem;
            border: none;
        }
        .btn-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        .btn-custom:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            color: white;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="register-container">
            <div class="card">
                <div class="card-header">
                    <h2 class="mb-0"><?php echo __('register'); ?></h2>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach($errors as $error): ?>
                                <p class="mb-0"><?php echo $error; ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label"><?php echo __('username'); ?></label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label"><?php echo __('email'); ?></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label"><?php echo __('password'); ?></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label"><?php echo __('confirm_password'); ?></label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-custom"><?php echo __('register'); ?></button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <p class="mb-0"><?php echo __('have_account'); ?> <a href="login.php"><?php echo __('login_now'); ?></a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 