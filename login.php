<?php
require_once 'config/database.php';
require_once 'includes/language.php';
session_start();

// Eğer kullanıcı zaten giriş yapmışsa, panele yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: user/dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $errors = [];

    if (empty($email)) {
        $errors[] = "E-posta adresi gereklidir.";
    }
    if (empty($password)) {
        $errors[] = "Şifre gereklidir.";
    }

    if (empty($errors)) {
        $query = "SELECT id, username, email, password, is_admin FROM users WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];

            if ($user['is_admin']) {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: user/dashboard.php");
            }
            exit;
        } else {
            $errors[] = "E-posta adresi veya şifre hatalı.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo __('login'); ?> - LinkSpot</title>
    
    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css">
    
    <!--Material Icon -->
    <link rel="stylesheet" type="text/css" href="css/materialdesignicons.min.css" />
    
    <!-- Custom  Css -->
    <link rel="stylesheet" type="text/css" href="css/style.css"/>
</head>
<body class="bg-account-pages">
    
    <!-- Login -->
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
                                        <p class="text-white mb-0"><?php echo __('login'); ?></p>   
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

                                    <?php if (isset($_SESSION['success'])): ?>
                                        <div class="alert alert-success">
                                            <?php 
                                            echo $_SESSION['success'];
                                            unset($_SESSION['success']);
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <form class="form-horizontal auth-form my-4" method="POST">
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
                                        
                                        <div class="form-group row my-4">
                                            <div class="col-sm-12 text-center">
                                                <button class="btn btn-custom w-100" type="submit"><?php echo __('login'); ?></button>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group mb-0 row">
                                            <div class="col-12 text-center">
                                                <a href="register.php" class="text-muted"><?php echo __('no_account'); ?> <span class="text-primary"><?php echo __('register_now'); ?></span></a>
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
    <!-- End Login -->
    
    <!-- JavaScript -->
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        // Preloader
        $(window).on('load', function() {
            $('#status').fadeOut();
            $('#preloader').delay(350).fadeOut('slow');
        });
    </script>
</body>
</html> 