<?php
require_once 'config/database.php';
require_once 'includes/language.php';
session_start();

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
<html lang="<?php echo $_SESSION['language']; ?>" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo __('register'); ?> - LinkSpot</title>
    
    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css">
    
    <!--Material Icon -->
    <link rel="stylesheet" type="text/css" href="css/materialdesignicons.min.css" />
    
    <!-- Custom  Css -->
    <link rel="stylesheet" type="text/css" href="css/style.css"/>
</head>
<body class="bg-account-pages">
    
    <!-- Register -->
    <section class="vh-100">
        <div class="container h-100">
            <div class="row justify-content-center h-100">
                <div class="col-12 align-self-center">
                    <div class="row">
                        <div class="col-lg-5 mx-auto">
                            <div class="card">
                                <div class="card-body p-0 auth-header-box">
                                    <div class="text-center p-3">
                                        <a href="index.php" class="logo logo-admin">
                                            <h2 class="text-white">LinkSpot</h2>
                                        </a>
                                        <p class="text-white mb-0"><?php echo __('register'); ?></p>   
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($errors)): ?>
                                        <div class="alert alert-danger">
                                            <?php foreach($errors as $error): ?>
                                                <p class="mb-0"><?php echo $error; ?></p>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <form class="form-horizontal auth-form my-4" method="POST">
                                        <div class="form-group mb-3">
                                            <label for="username"><?php echo __('username'); ?></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="username" name="username" placeholder="<?php echo __('username'); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group mb-3">
                                            <label for="email"><?php echo __('email'); ?></label>
                                            <div class="input-group">
                                                <input type="email" class="form-control" id="email" name="email" placeholder="<?php echo __('email'); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group mb-3">
                                            <label for="password"><?php echo __('password'); ?></label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="password" name="password" placeholder="<?php echo __('password'); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group mb-3">
                                            <label for="password_confirm"><?php echo __('confirm_password'); ?></label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="<?php echo __('confirm_password'); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group row my-4">
                                            <div class="col-sm-12 text-center">
                                                <button class="btn btn-custom w-100" type="submit"><?php echo __('register'); ?></button>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group mb-0 row">
                                            <div class="col-12 text-center">
                                                <a href="login.php" class="text-muted"><?php echo __('have_account'); ?> <span class="text-primary"><?php echo __('login_now'); ?></span></a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- End Register -->
    
    <!-- JavaScript -->
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/app.js"></script>
</body>
</html> 