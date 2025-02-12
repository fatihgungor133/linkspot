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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .hero-section {
            padding: 100px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #667eea;
        }
    </style>
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
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php"><?php echo __('login'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php"><?php echo __('register'); ?></a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <?php echo language_selector(); ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-4 mb-4"><?php echo __('welcome'); ?></h1>
                    <p class="lead mb-4"><?php echo __('welcome_text'); ?></p>
                    <div class="d-grid gap-2 d-md-flex">
                        <a href="register.php" class="btn btn-light btn-lg me-md-2"><?php echo __('register_now'); ?></a>
                        <a href="login.php" class="btn btn-outline-light btn-lg"><?php echo __('login'); ?></a>
                    </div>
                </div>
                <div class="col-md-6">
                    <img src="assets/images/hero-image.svg" alt="Hero Image" class="img-fluid">
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row text-center">
                <div class="col-lg-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <i class="bi bi-link feature-icon"></i>
                            <h3><?php echo __('unlimited_links'); ?></h3>
                            <p class="text-muted"><?php echo __('unlimited_links_desc'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <i class="bi bi-palette feature-icon"></i>
                            <h3><?php echo __('custom_theme'); ?></h3>
                            <p class="text-muted"><?php echo __('custom_theme_desc'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <i class="bi bi-graph-up feature-icon"></i>
                            <h3><?php echo __('detailed_stats'); ?></h3>
                            <p class="text-muted"><?php echo __('detailed_stats_desc'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 