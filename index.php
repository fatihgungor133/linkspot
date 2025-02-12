<?php
require_once 'includes/language.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LinkSpot - <?php echo __('welcome'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">LinkSpot</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"><?php echo __('login'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php"><?php echo __('register'); ?></a>
                    </li>
                    <li class="nav-item">
                        <?php echo language_selector(); ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2 text-center">
                <h1><?php echo __('welcome'); ?></h1>
                <p class="lead"><?php echo __('welcome_text'); ?></p>
                <div class="mt-4">
                    <a href="register.php" class="btn btn-primary btn-lg me-2"><?php echo __('register_now'); ?></a>
                    <a href="login.php" class="btn btn-outline-primary btn-lg"><?php echo __('login'); ?></a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 