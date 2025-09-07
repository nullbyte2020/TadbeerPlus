<?php
/**
 * ==============================================
 * الصفحة الرئيسية - نظام تدبير بلس
 * Main Page - Tadbeer Plus System
 * ==============================================
 */

// بدء الجلسة
session_start();

// تضمين ملفات الإعدادات
require_once 'config/constants.php';
require_once 'config/db.php';
require_once 'config/i18n.php';
require_once 'includes/functions.php';

// التحقق من حالة تسجيل الدخول
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // إعادة توجيه للوحة التحكم المناسبة
    header('Location: dashboard_redirect.php');
    exit;
}

// الحصول على إعدادات الشركة
$companySettings = getCompanySettings();
$pageTitle = __('app_name') . ' - ' . __('welcome');
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>" dir="<?php echo getTextDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- خطوط عربية -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- الأنماط المخصصة -->
    <link href="assets/css/style-<?php echo getCurrentLanguage(); ?>.css" rel="stylesheet">
    
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
</head>
<body class="bg-light">

    <!-- شريط التنقل العلوي -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="assets/images/logo.png" alt="Logo" height="40" class="me-2">
                <span class="fw-bold"><?php _e('app_name'); ?></span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-house"></i> <?php _e('home'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#services">
                            <i class="bi bi-briefcase"></i> <?php _e('services'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">
                            <i class="bi bi-info-circle"></i> <?php _e('about_us'); ?>
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <!-- تبديل اللغة -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-globe"></i> <?php _e('language'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?lang=ar">العربية</a></li>
                            <li><a class="dropdown-item" href="?lang=en">English</a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light btn-sm ms-2" href="login.php">
                            <i class="bi bi-box-arrow-in-right"></i> <?php _e('login'); ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- القسم الرئيسي -->
    <section class="hero-section py-5">
        <div class="container">
            <div class="row align-items-center min-vh-75">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <h1 class="display-4 fw-bold text-primary mb-4">
                            <?php _e('welcome_to_tadbeer'); ?>
                        </h1>
                        <p class="lead text-muted mb-4">
                            <?php _e('hero_description'); ?>
                        </p>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="login.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right"></i> <?php _e('login'); ?>
                            </a>
                            <a href="#services" class="btn btn-outline-primary btn-lg">
                                <i class="bi bi-info-circle"></i> <?php _e('learn_more'); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-image text-center">
                        <img src="assets/images/hero-image.png" alt="Hero Image" class="img-fluid rounded-3 shadow">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- قسم الخدمات -->
    <section id="services" class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold"><?php _e('our_services'); ?></h2>
                <p class="text-muted"><?php _e('services_description'); ?></p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="service-card text-center p-4 h-100 border rounded-3 shadow-sm">
                        <i class="bi bi-people-fill text-primary fs-1 mb-3"></i>
                        <h5 class="fw-bold"><?php _e('worker_management'); ?></h5>
                        <p class="text-muted"><?php _e('worker_management_desc'); ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="service-card text-center p-4 h-100 border rounded-3 shadow-sm">
                        <i class="bi bi-file-earmark-text text-success fs-1 mb-3"></i>
                        <h5 class="fw-bold"><?php _e('contract_management'); ?></h5>
                        <p class="text-muted"><?php _e('contract_management_desc'); ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="service-card text-center p-4 h-100 border rounded-3 shadow-sm">
                        <i class="bi bi-calculator text-warning fs-1 mb-3"></i>
                        <h5 class="fw-bold"><?php _e('financial_management'); ?></h5>
                        <p class="text-muted"><?php _e('financial_management_desc'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- قسم حول النظام -->
    <section id="about" class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="fw-bold mb-4"><?php _e('about_system'); ?></h2>
                    <p class="mb-4"><?php _e('about_description'); ?></p>
                    
                    <div class="features-list">
                        <div class="feature-item d-flex align-items-center mb-3">
                            <i class="bi bi-check-circle-fill text-success fs-5 me-3"></i>
                            <span><?php _e('feature_1'); ?></span>
                        </div>
                        <div class="feature-item d-flex align-items-center mb-3">
                            <i class="bi bi-check-circle-fill text-success fs-5 me-3"></i>
                            <span><?php _e('feature_2'); ?></span>
                        </div>
                        <div class="feature-item d-flex align-items-center mb-3">
                            <i class="bi bi-check-circle-fill text-success fs-5 me-3"></i>
                            <span><?php _e('feature_3'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="assets/images/about-image.png" alt="About" class="img-fluid rounded-3 shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- التذييل -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?php echo $companySettings['company_name_' . getCurrentLanguage()] ?? __('app_name'); ?></h5>
                    <p class="text-muted small">
                        <?php _e('footer_description'); ?>
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="contact-info">
                        <?php if (!empty($companySettings['phone'])): ?>
                        <p class="mb-1">
                            <i class="bi bi-telephone"></i> 
                            <?php echo $companySettings['phone']; ?>
                        </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($companySettings['email'])): ?>
                        <p class="mb-1">
                            <i class="bi bi-envelope"></i> 
                            <?php echo $companySettings['email']; ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <hr class="my-3">
            <div class="text-center">
                <small class="text-muted">
                    © <?php echo date('Y'); ?> <?php _e('app_name'); ?>. <?php _e('all_rights_reserved'); ?>
                </small>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- الأنماط المخصصة -->
    <style>
        .min-vh-75 {
            min-height: 75vh;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .service-card {
            transition: transform 0.3s ease;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-item {
            transition: all 0.3s ease;
        }
        
        .feature-item:hover {
            background-color: rgba(0, 123, 255, 0.1);
            border-radius: 5px;
            padding: 10px;
        }
    </style>
</body>
</html>
