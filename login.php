<?php
/**
 * ==============================================
 * صفحة تسجيل الدخول - نظام تدبير بلس
 * Login Page - Tadbeer Plus System
 * ==============================================
 */

session_start();

// تضمين ملفات الإعدادات
require_once 'config/constants.php';
require_once 'config/db.php';
require_once 'config/i18n.php';
require_once 'config/permissions.php';
require_once 'config/functions.php';

// التحقق من تسجيل الدخول المسبق
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: dashboard_redirect.php');
    exit;
}

// متغيرات الرسائل
$error_message = '';
$success_message = '';

// معالجة نموذج تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من رمز CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_message = __('error_csrf_token');
    } else {
        $username = filterInput($_POST['username'] ?? '', 'string');
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);
        
        if (empty($username) || empty($password)) {
            $error_message = __('error_required_fields');
        } else {
            $result = authenticateUser($username, $password, $remember_me);
            
            if ($result['success']) {
                // تسجيل النشاط
                logActivity('login', 'User logged in successfully');
                
                // إعادة توجيه للصفحة المطلوبة أو لوحة التحكم
                $redirect_url = $_SESSION['redirect_after_login'] ?? 'dashboard_redirect.php';
                unset($_SESSION['redirect_after_login']);
                
                header('Location: ' . $redirect_url);
                exit;
            } else {
                $error_message = $result['message'];
            }
        }
    }
}

/**
 * دالة المصادقة
 * Authentication Function
 */
function authenticateUser($username, $password, $remember_me = false) {
    $db = getDB();
    
    try {
        // البحث عن المستخدم
        $sql = "SELECT u.*, r.role_name 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                WHERE (u.username = :username OR u.email = :username) 
                AND u.is_active = 1";
        
        $user = $db->selectOne($sql, ['username' => $username]);
        
        if (!$user) {
            return ['success' => false, 'message' => __('error_invalid_credentials')];
        }
        
        // التحقق من قفل الحساب
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return ['success' => false, 'message' => __('error_account_locked')];
        }
        
        // التحقق من كلمة المرور
        if (!password_verify($password, $user['password'])) {
            // زيادة عدد المحاولات الفاشلة
            incrementLoginAttempts($user['id']);
            return ['success' => false, 'message' => __('error_invalid_credentials')];
        }
        
        // إعادة تعيين محاولات تسجيل الدخول
        resetLoginAttempts($user['id']);
        
        // إنشاء الجلسة
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['role_name'] = $user['role_name'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['preferred_language'] = $user['preferred_language'];
        $_SESSION['last_activity'] = time();
        
        // تحديث آخر تسجيل دخول
        updateLastLogin($user['id']);
        
        // إنشاء كوكي "تذكرني"
        if ($remember_me) {
            createRememberToken($user['id']);
        }
        
        return ['success' => true, 'message' => __('success_login')];
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => __('error_database')];
    }
}

/**
 * زيادة محاولات تسجيل الدخول الفاشلة
 */
function incrementLoginAttempts($userId) {
    $db = getDB();
    $maxAttempts = getSetting('max_login_attempts', 5);
    
    $sql = "UPDATE users 
            SET login_attempts = login_attempts + 1,
                locked_until = CASE 
                    WHEN login_attempts + 1 >= :max_attempts 
                    THEN DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                    ELSE locked_until 
                END
            WHERE id = :user_id";
    
    $db->update($sql, [
        'max_attempts' => $maxAttempts,
        'user_id' => $userId
    ]);
}

/**
 * إعادة تعيين محاولات تسجيل الدخول
 */
function resetLoginAttempts($userId) {
    $db = getDB();
    
    $sql = "UPDATE users 
            SET login_attempts = 0, locked_until = NULL 
            WHERE id = :user_id";
    
    $db->update($sql, ['user_id' => $userId]);
}

/**
 * تحديث آخر تسجيل دخول
 */
function updateLastLogin($userId) {
    $db = getDB();
    
    $sql = "UPDATE users 
            SET last_login = NOW() 
            WHERE id = :user_id";
    
    $db->update($sql, ['user_id' => $userId]);
}

/**
 * إنشاء رمز "تذكرني"
 */
function createRememberToken($userId) {
    $token = bin2hex(random_bytes(32));
    $expiry = time() + (30 * 24 * 60 * 60); // 30 يوم
    
    setcookie('remember_token', $token, $expiry, '/', '', true, true);
    
    $db = getDB();
    $sql = "UPDATE users 
            SET remember_token = :token, remember_expires = FROM_UNIXTIME(:expiry)
            WHERE id = :user_id";
    
    $db->update($sql, [
        'token' => hash('sha256', $token),
        'expiry' => $expiry,
        'user_id' => $userId
    ]);
}

$pageTitle = __('login') . ' - ' . __('app_name');
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
<body class="bg-gradient-primary">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <!-- قسم الصورة -->
                            <div class="col-lg-6 d-none d-lg-block bg-login-image">
                                <div class="text-center p-5 h-100 d-flex flex-column justify-content-center">
                                    <img src="assets/images/login-illustration.png" alt="Login" class="img-fluid mb-4">
                                    <h4 class="text-white"><?php _e('welcome_back'); ?></h4>
                                    <p class="text-white-50"><?php _e('login_subtitle'); ?></p>
                                </div>
                            </div>
                            
                            <!-- قسم النموذج -->
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center">
                                        <img src="assets/images/logo.png" alt="Logo" height="60" class="mb-4">
                                        <h1 class="h4 text-gray-900 mb-4"><?php _e('login_title'); ?></h1>
                                    </div>
                                    
                                    <!-- رسائل التنبيه -->
                                    <?php if (!empty($error_message)): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                            <?php echo $error_message; ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($success_message)): ?>
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <i class="bi bi-check-circle-fill me-2"></i>
                                            <?php echo $success_message; ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- نموذج تسجيل الدخول -->
                                    <form method="POST" action="" class="user" id="loginForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        
                                        <div class="form-group mb-3">
                                            <label for="username" class="form-label">
                                                <?php _e('username_or_email'); ?> *
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-person"></i>
                                                </span>
                                                <input type="text" 
                                                       class="form-control form-control-user" 
                                                       id="username" 
                                                       name="username" 
                                                       placeholder="<?php _e('enter_username_or_email'); ?>"
                                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                                       required 
                                                       autofocus>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group mb-3">
                                            <label for="password" class="form-label">
                                                <?php _e('password'); ?> *
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-lock"></i>
                                                </span>
                                                <input type="password" 
                                                       class="form-control form-control-user" 
                                                       id="password" 
                                                       name="password" 
                                                       placeholder="<?php _e('enter_password'); ?>"
                                                       required>
                                                <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                                    <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group mb-3">
                                            <div class="custom-control custom-checkbox small">
                                                <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                                                <label class="form-check-label" for="remember_me">
                                                    <?php _e('remember_me'); ?>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary btn-user btn-block w-100 mb-3">
                                            <i class="bi bi-box-arrow-in-right me-2"></i>
                                            <?php _e('login'); ?>
                                        </button>
                                        
                                        <hr>
                                        
                                        <div class="text-center">
                                            <a class="small" href="forgot_password.php">
                                                <?php _e('forgot_password'); ?>؟
                                            </a>
                                        </div>
                                        
                                        <?php if (getSetting('allow_registration', false)): ?>
                                        <div class="text-center mt-2">
                                            <a class="small" href="register.php">
                                                <?php _e('create_account'); ?>
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="text-center mt-3">
                                            <a href="index.php" class="small text-muted">
                                                <i class="bi bi-arrow-left me-1"></i>
                                                <?php _e('back_to_home'); ?>
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript مخصص -->
    <script>
        // تبديل عرض كلمة المرور
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePasswordIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.className = 'bi bi-eye-slash';
            } else {
                passwordField.type = 'password';
                toggleIcon.className = 'bi bi-eye';
            }
        });
        
        // التحقق من صحة النموذج
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('<?php _e("please_fill_all_fields"); ?>');
                return false;
            }
            
            // إظهار مؤشر التحميل
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-spinner-border spinner-border-sm me-2"></i><?php _e("processing"); ?>';
        });
        
        // التركيز على أول حقل فارغ
        window.addEventListener('load', function() {
            const usernameField = document.getElementById('username');
            if (!usernameField.value) {
                usernameField.focus();
            } else {
                document.getElementById('password').focus();
            }
        });
    </script>
    
    <!-- الأنماط المخصصة -->
    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
        }
        
        .bg-login-image {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%),
                        url('assets/images/login-bg.jpg');
            background-size: cover;
            background-position: center;
        }
        
        .card {
            border-radius: 1rem;
        }
        
        .form-control-user {
            border-radius: 10rem;
            padding: 1.5rem 1rem;
        }
        
        .btn-user {
            border-radius: 10rem;
            padding: 0.75rem 1rem;
        }
        
        .input-group .form-control {
            border-right: 0;
        }
        
        .input-group .input-group-text {
            border-left: 0;
            background-color: #f8f9fc;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</body>
</html>
