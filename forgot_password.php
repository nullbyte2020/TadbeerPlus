<?php
/**
 * ==============================================
 * صفحة نسيان كلمة المرور - نظام تدبير بلس
 * Forgot Password Page - Tadbeer Plus System
 * ==============================================
 */

session_start();

// تضمين ملفات الإعدادات
require_once 'config/constants.php';
require_once 'config/db.php';
require_once 'config/i18n.php';
require_once 'config/email.php';
require_once 'config/functions.php';

// التحقق من تسجيل الدخول المسبق
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard_redirect.php');
    exit;
}

// متغيرات الرسائل
$error_message = '';
$success_message = '';
$email_sent = false;

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من رمز CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_message = __('error_csrf_token');
    } else {
        $email = filterInput($_POST['email'] ?? '', 'email');
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = __('error_invalid_email');
        } else {
            $result = processForgotPassword($email);
            
            if ($result['success']) {
                $success_message = $result['message'];
                $email_sent = true;
            } else {
                $error_message = $result['message'];
            }
        }
    }
}

/**
 * معالجة طلب نسيان كلمة المرور
 * Process Forgot Password Request
 */
function processForgotPassword($email) {
    $db = getDB();
    
    try {
        // البحث عن المستخدم
        $sql = "SELECT id, username, full_name, email FROM users WHERE email = :email AND is_active = 1";
        $user = $db->selectOne($sql, ['email' => $email]);
        
        if (!$user) {
            // لأغراض الأمان، نعرض رسالة النجاح حتى لو لم يكن البريد موجود
            return [
                'success' => true, 
                'message' => __('password_reset_email_sent')
            ];
        }
        
        // التحقق من عدد الطلبات السابقة (تحديد معدل)
        $rateLimitResult = checkPasswordResetRateLimit($user['id']);
        if (!$rateLimitResult['allowed']) {
            return [
                'success' => false,
                'message' => __('password_reset_rate_limit')
            ];
        }
        
        // إنشاء رمز إعادة التعيين
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', time() + 3600); // ساعة واحدة
        
        // حفظ الرمز في قاعدة البيانات
        $sql = "UPDATE users 
                SET password_reset_token = :token, 
                    password_reset_expires = :expiry 
                WHERE id = :user_id";
        
        $updated = $db->update($sql, [
            'token' => hash('sha256', $token),
            'expiry' => $expiry,
            'user_id' => $user['id']
        ]);
        
        if ($updated) {
            // إرسال البريد الإلكتروني
            $emailSent = sendPasswordResetEmail($user['email'], $user['full_name'], $token);
            
            if ($emailSent) {
                // تسجيل النشاط
                logActivity('password_reset_request', 'Password reset requested for: ' . $email, $user['id']);
                
                return [
                    'success' => true,
                    'message' => __('password_reset_email_sent')
                ];
            } else {
                return [
                    'success' => false,
                    'message' => __('error_email_send_failed')
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => __('error_database')
        ];
        
    } catch (Exception $e) {
        error_log("Forgot password error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => __('error_server')
        ];
    }
}

/**
 * التحقق من معدل طلبات إعادة تعيين كلمة المرور
 * Check Password Reset Rate Limit
 */
function checkPasswordResetRateLimit($userId) {
    $db = getDB();
    
    // السماح بطلب واحد كل 15 دقيقة
    $sql = "SELECT COUNT(*) as reset_count 
            FROM activity_logs 
            WHERE user_id = :user_id 
            AND action = 'password_reset_request' 
            AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
    
    $result = $db->selectOne($sql, ['user_id' => $userId]);
    
    return [
        'allowed' => ($result['reset_count'] ?? 0) < 1,
        'count' => $result['reset_count'] ?? 0
    ];
}

$pageTitle = __('forgot_password') . ' - ' . __('app_name');
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
            <div class="col-xl-6 col-lg-8 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="p-5">
                            <div class="text-center">
                                <img src="assets/images/logo.png" alt="Logo" height="60" class="mb-4">
                                
                                <?php if (!$email_sent): ?>
                                    <h1 class="h4 text-gray-900 mb-2"><?php _e('forgot_password_title'); ?></h1>
                                    <p class="mb-4"><?php _e('forgot_password_subtitle'); ?></p>
                                <?php else: ?>
                                    <i class="bi bi-check2-circle text-success" style="font-size: 3rem;"></i>
                                    <h1 class="h4 text-gray-900 mt-3 mb-2"><?php _e('email_sent_title'); ?></h1>
                                    <p class="mb-4"><?php _e('email_sent_subtitle'); ?></p>
                                <?php endif; ?>
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
                            
                            <?php if (!$email_sent): ?>
                            <!-- نموذج طلب إعادة تعيين كلمة المرور -->
                            <form method="POST" action="" class="user" id="forgotPasswordForm">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                
                                <div class="form-group mb-4">
                                    <label for="email" class="form-label">
                                        <?php _e('email_address'); ?> *
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-envelope"></i>
                                        </span>
                                        <input type="email" 
                                               class="form-control form-control-user" 
                                               id="email" 
                                               name="email" 
                                               placeholder="<?php _e('enter_email_address'); ?>"
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                               required 
                                               autofocus>
                                    </div>
                                    <small class="form-text text-muted">
                                        <?php _e('forgot_password_email_help'); ?>
                                    </small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-user btn-block w-100 mb-4">
                                    <i class="bi bi-envelope me-2"></i>
                                    <?php _e('send_reset_link'); ?>
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <hr>
                            
                            <div class="text-center">
                                <a class="small" href="login.php">
                                    <i class="bi bi-arrow-left me-1"></i>
                                    <?php _e('back_to_login'); ?>
                                </a>
                            </div>
                            
                            <div class="text-center mt-2">
                                <a href="index.php" class="small text-muted">
                                    <i class="bi bi-house me-1"></i>
                                    <?php _e('back_to_home'); ?>
                                </a>
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
        // التحقق من صحة النموذج
        document.getElementById('forgotPasswordForm')?.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            
            if (!email) {
                e.preventDefault();
                alert('<?php _e("please_enter_email"); ?>');
                return false;
            }
            
            // التحقق من صحة البريد الإلكتروني
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('<?php _e("please_enter_valid_email"); ?>');
                return false;
            }
            
            // إظهار مؤشر التحميل
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-spinner-border spinner-border-sm me-2"></i><?php _e("sending"); ?>';
        });
    </script>
    
    <!-- الأنماط المخصصة -->
    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
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
