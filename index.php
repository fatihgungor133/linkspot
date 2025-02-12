<?php
require_once 'includes/language.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="LinkSpot - Tüm linkleriniz tek bir yerde" />
    <meta name="keywords" content="link, sosyal medya, profil" />
    <title>LinkSpot - <?php echo __('welcome'); ?></title>
    
    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css">
    
    <!--Material Icon -->
    <link rel="stylesheet" type="text/css" href="css/materialdesignicons.min.css" />
    
    <!-- owl.carousel -->
    <link rel="stylesheet" type="text/css" href="css/owl.carousel.css"/>
    <link rel="stylesheet" type="text/css" href="css/owl.theme.css"/>
    <link rel="stylesheet" type="text/css" href="css/owl.transitions.css"/>
    
    <!-- Pe7 Icon -->
    <link rel="stylesheet" type="text/css" href="css/pe-icon-7.css">
    
    <!-- Magnific Popup -->
    <link rel="stylesheet" type="text/css" href="css/magnific-popup.css">
    
    <!-- Custom  Css -->
    <link rel="stylesheet" type="text/css" href="css/style.css"/>
</head>
<body>
    <!-- Pre-loader -->
    <div id="preloader">
        <div id="status">
            <div class="spinner">Loading...</div>
        </div>
    </div>
    
    <!--Navbar Start-->
    <nav class="navbar navbar-expand-lg fixed-top navbar-custom sticky sticky-dark">
        <div class="container">
            <!-- LOGO -->
            <a class="logo" href="index.php">LinkSpot</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <i class="mdi mdi-menu"></i>
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
    
    <!-- Start Home -->
    <section class="home-height-half" id="home">
        <div class="bg-overlay-gredient"></div>
        <div class="home-center">
            <div class="home-desc-center">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="home-title text-white">
                                <h1 class="text-white"><?php echo __('welcome'); ?></h1>
                                <p class="mt-4"><?php echo __('welcome_text'); ?></p>
                                <div class="watch-video mt-5">
                                    <a href="register.php" class="btn btn-custom mr-4 btn-round"><?php echo __('register_now'); ?></a>
                                    <a href="login.php" class="btn btn-outline-light btn-round"><?php echo __('login'); ?></a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="software-home-img">
                                <img src="HTML/images/svg/crypto.svg" alt="img-responsive" class="img-fluid mx-auto d-block">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Start Features -->
    <section class="section bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="title text-center mb-5">
                        <h3 class="font-weight-600">Özellikler</h3>
                        <p class="text-muted">Tüm sosyal medya hesaplarınızı ve önemli linklerinizi tek bir sayfada toplayın.</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4">
                    <div class="features-box text-center p-4">
                        <div class="features-icon">
                            <i class="pe-7s-link text-custom"></i>
                        </div>
                        <div class="features-desc">
                            <h5 class="font-weight-600 pt-4">Sınırsız Link</h5>
                            <p class="text-muted">İstediğiniz kadar link ekleyebilir ve düzenleyebilirsiniz.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="features-box text-center p-4">
                        <div class="features-icon">
                            <i class="pe-7s-paint-bucket text-custom"></i>
                        </div>
                        <div class="features-desc">
                            <h5 class="font-weight-600 pt-4">Özelleştirilebilir Tema</h5>
                            <p class="text-muted">Profilinizi istediğiniz gibi özelleştirebilirsiniz.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="features-box text-center p-4">
                        <div class="features-icon">
                            <i class="pe-7s-graph1 text-custom"></i>
                        </div>
                        <div class="features-desc">
                            <h5 class="font-weight-600 pt-4">Detaylı İstatistikler</h5>
                            <p class="text-muted">Ziyaretçi ve tıklama istatistiklerini takip edebilirsiniz.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- JavaScript -->
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/jquery.easing.min.js"></script>
    <script src="js/scrollspy.min.js"></script>
    <!-- owl-carousel -->
    <script src="js/owl.carousel.min.js"></script>
    <!-- Magnific Popup -->
    <script src="js/jquery.magnific-popup.min.js"></script>
    <!-- custom -->
    <script>
        // Preloader
        $(window).on('load', function() {
            $('#status').fadeOut();
            $('#preloader').delay(350).fadeOut('slow');
        });

        // Sticky Navbar
        $(window).scroll(function() {
            var scroll = $(window).scrollTop();
            if (scroll >= 50) {
                $(".sticky").addClass("nav-sticky");
            } else {
                $(".sticky").removeClass("nav-sticky");
            }
        });
    </script>
</body>
</html> 