<?php
/**
 * ==============================================
 * صفحة إعادة تعيين كلمة المرور - نظام تدبير بلس
 * Reset Password Page - Tadbeer Plus System
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
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard_redirect.php');
    exit;
}

// الحصول على الرمز من الرابط
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: forgot_password.php?error=invalid_token');
    exit;
}

// متغيرات الرسائل
$error_message = '';
$success_message = '';
$password_reset = false;
$user_data = null;

// التحقق من صحة الرمز
$tokenValidation = validateResetToken($token);
if (!$tokenValidation['valid']) {
    $error_message = $tokenValidation['message'];
} else {
    $user_data = $tokenValidation['user'];
}

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_data) {
    // التحقق من رمز CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_message = __('error_csrf_token');
    } else {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // التحقق من كلمات المرور
        $validation = validateNewPassword($password, $confirm_password);
        
        if ($validation['valid']) {
            $result = resetUserPassword($user_data['id'], $password, $token);
            
            if ($result['success']) {
                $success_message = $result['message'];
                $password_reset = true;
            } else {
                $error_message = $result['message'];
            }
        } else {
            $error_message = $validation['message'];
        }
    }
}

/**
 * التحقق من صحة رمز إعادة التعيين
 * Validate Reset Token
 */
function validateResetToken($token) {
    $db = getDB();
    
    try {
        $sql = "SELECT id, username, full_name, email, password_reset_expires 
                FROM users 
                WHERE password_reset_token = :token 
                AND is_active = 1";
        
        $user = $db->selectOne($sql, ['token' => hash('sha256', $token)]);
        
        if (!$user) {
            return [
                'valid' => false,
                'message' => __('invalid_reset_token'),
                'user' => null
            ];
        }
        
        // التحقق من انتهاء صلاحية الرمز
        if (strtotime($user['password_reset_expires']) < time()) {
            return [
                'valid' => false,
                'message' => __('expired_reset_token'),
                'user' => null
            ];
        }
        
        return [
            'valid' => true,
            'message' => '',
            'user' => $user
        ];
        
    } catch (Exception $e) {
        error_log("Token validation error: " . $e->getMessage());
        return [
            'valid' => false,
            'message' => __('error_database'),
            'user' => null
        ];
    }
}

/**
 * التحقق من صحة كلمة المرور الجديدة
 * Validate New Password
 */
function validateNewPassword($password, $confirmPassword) {
    if (empty($password) || empty($confirmPassword)) {
        return [
            'valid' => false,
            'message' => __('error_required_fields')
        ];
    }
    
    if ($password !== $confirmPassword) {
        return [
            'valid' => false,
            'message' => __('password_mismatch')
        ];
    }
    
    // التحقق من قوة كلمة المرور
    $strengthCheck = checkPasswordStrength($password);
    if (!$strengthCheck['is_strong']) {
        return [
            'valid' => false,
            'message' => implode('<br>', $strengthCheck['errors'])
        ];
    }
    
    return [
        'valid' => true,
        'message' => ''
    ];
}

/**
 * إعادة تعيين كلمة مرور المستخدم
 * Reset User Password
 */
function resetUserPassword($userId, $newPassword, $token) {
    $db = getDB();
    
    try {
        $db->beginTransaction();
        
        // تشفير كلمة المرور الجديدة
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // تحديث كلمة المرور وحذف رمز الإعادة
        $sql = "UPDATE users 
                SET password = :password,
                    password_reset_token = NULL,
                    password_reset_expires = NULL,
                    login_attempts = 0,
                    locked_until = NULL
                WHERE id = :user_id";
        
        $updated = $db->update($sql, [
            'password' => $hashedPassword,
            'user_id' => $userId
        ]);
        
        if ($updated) {
            // تسجيل النشاط
            logActivity('password_reset', 'Password was reset successfully', $userId);
            
            $db->commit();
            
            return [
                'success' => true,
                'message' => __('password_reset_success')
            ];
        } else {
            $db->rollback();
            return [
                'success' => false,
                'message' => __('error_database')
            ];
        }
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Password reset error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => __('error_server')
        ];
    }
}

$pageTitle = __('reset_password') . ' - ' . __('app_name');
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
                                
                                <?php if ($password_reset): ?>
                                    <i class="bi bi-check2-circle text-success" style="font-size: 3rem;"></i>
                                    <h1 class="h4 text-gray-900 mt-3 mb-2"><?php _e('password_reset_success_title'); ?></h1>
                                    <p class="mb-4"><?php _e('password_reset_success_subtitle'); ?></p>
                                <?php elseif ($user_data): ?>
                                    <h1 class="h4 text-gray-900 mb-2"><?php _e('reset_password_title'); ?></h1>
                                    <p class="mb-4"><?php _e('reset_password_subtitle'); ?></p>
                                <?php else: ?>
                                    <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                                    <h1 class="h4 text-gray-900 mt-3 mb-2"><?php _e('invalid_token_title'); ?></h1>
                                    <p class="mb-4"><?php _e('invalid_token_subtitle'); ?></p>
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
                            
                            <?php if ($user_data && !$password_reset): ?>
                            <!-- معلومات المستخدم -->
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <?php _e('reset_password_for'); ?>: <strong><?php echo htmlspecialchars($user_data['full_name']); ?></strong>
                                <br>
                                <small><?php echo htmlspecialchars($user_data['email']); ?></small>
                            </div>
                            
                            <!-- نموذج إعادة تعيين كلمة المرور -->
                            <form method="POST" action="" class="user" id="resetPasswordForm">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                
                                <div class="form-group mb-3">
                                    <label for="password" class="form-label">
                                        <?php _e('new_password'); ?> *
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-lock"></i>
                                        </span>
                                        <input type="password" 
                                               class="form-control form-control-user" 
                                               id="password" 
                                               name="password" 
                                               placeholder="<?php _e('enter_new_password'); ?>"
                                               required 
                                               minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                                               autofocus>
                                        <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                        </button>
                                    </div>
                                    <small class="form-text text-muted">
                                        <?php _e('password_requirements'); ?>
                                    </small>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="confirm_password" class="form-label">
                                        <?php _e('confirm_new_password'); ?> *
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-lock-fill"></i>
                                        </span>
                                        <input type="password" 
                                               class="form-control form-control-user" 
                                               id="confirm_password" 
                                               name="confirm_password" 
                                               placeholder="<?php _e('confirm_new_password'); ?>"
                                               required>
                                        <button type="button" class="btn btn-outline-secondary" id="toggleConfirmPassword">
                                            <i class="bi bi-eye" id="toggleConfirmPasswordIcon"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- مؤشر قوة كلمة المرور -->
                                <div class="password-strength mb-3" id="passwordStrength" style="display: none;">
                                    <label class="form-label small"><?php _e('password_strength'); ?>:</label>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar" id="strengthBar" style="width: 0%"></div>
                                    </div>
                                    <small id="strengthText" class="form-text"></small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-user btn-block w-100 mb-4">
                                    <i class="bi bi-shield-check me-2"></i>
                                    <?php _e('reset_password'); ?>
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <hr>
                            
                            <div class="text-center">
                                <?php if ($password_reset): ?>
                                    <a class="btn btn-primary" href="login.php">
                                        <i class="bi bi-box-arrow-in-right me-1"></i>
                                        <?php _e('login_now'); ?>
                                    </a>
                                <?php else: ?>
                                    <a class="small" href="login.php">
                                        <i class="bi bi-arrow-left me-1"></i>
                                        <?php _e('back_to_login'); ?>
                                    </a>
                                <?php endif; ?>
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
        // تبديل عرض كلمة المرور
        document.getElementById('togglePassword')?.addEventListener('click', function() {
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
        
        document.getElementById('toggleConfirmPassword')?.addEventListener('click', function() {
            const passwordField = document.getElementById('confirm_password');
            const toggleIcon = document.getElementById('toggleConfirmPasswordIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.className = 'bi bi-eye-slash';
            } else {
                passwordField.type = 'password';
                toggleIcon.className = 'bi bi-eye';
            }
        });
        
        // فحص قوة كلمة المرور
        document.getElementById('password')?.addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            if (password.length === 0) {
                strengthDiv.style.display = 'none';
                return;
            }
            
            strengthDiv.style.display = 'block';
            
            let score = 0;
            let feedback = [];
            
            // فحص الطول
            if (password.length >= 8) score += 25;
            else feedback.push('<?php _e("password_min_8_chars"); ?>');
            
            // فحص الأحرف الكبيرة
            if (/[A-Z]/.test(password)) score += 25;
            else feedback.push('<?php _e("password_need_uppercase"); ?>');
            
            // فحص الأحرف الصغيرة
            if (/[a-z]/.test(password)) score += 25;
            else feedback.push('<?php _e("password_need_lowercase"); ?>');
            
            // فحص الأرقام والرموز
            if (/[0-9]/.test(password) && /[^A-Za-z0-9]/.test(password)) score += 25;
            else feedback.push('<?php _e("password_need_number_symbol"); ?>');
            
            // تحديث شريط التقدم
            strengthBar.style.width = score + '%';
            
            if (score < 50) {
                strengthBar.className = 'progress-bar bg-danger';
                strengthText.textContent = '<?php _e("weak"); ?>';
                strengthText.className = 'form-text text-danger';
            } else if (score < 75) {
                strengthBar.className = 'progress-bar bg-warning';
                strengthText.textContent = '<?php _e("medium"); ?>';
                strengthText.className = 'form-text text-warning';
            } else {
                strengthBar.className = 'progress-bar bg-success';
                strengthText.textContent = '<?php _e("strong"); ?>';
                strengthText.className = 'form-text text-success';
            }
        });
        
        // التحقق من تطابق كلمات المرور
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('<?php _e("passwords_dont_match"); ?>');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
                if (confirmPassword) this.classList.add('is-valid');
            }
        });
        
        // التحقق من صحة النموذج
        document.getElementById('resetPasswordForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (!password || !confirmPassword) {
                e.preventDefault();
                alert('<?php _e("please_fill_all_fields"); ?>');
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('<?php _e("passwords_dont_match"); ?>');
                return false;
            }
            
            if (password.length < <?php echo PASSWORD_MIN_LENGTH; ?>) {
                e.preventDefault();
                alert('<?php _e("password_too_short"); ?>');
                return false;
            }
            
            // إظهار مؤشر التحميل
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-spinner-border spinner-border-sm me-2"></i><?php _e("processing"); ?>';
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
        
        .form-control.is-invalid {
            border-color: #dc3545;
        }
        
        .form-control.is-valid {
            border-color: #28a745;
        }
    </style>
</body>
</html>
