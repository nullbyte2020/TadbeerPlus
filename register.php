<?php
/**
 * ==============================================
 * صفحة التسجيل - نظام تدبير بلس
 * Registration Page - Tadbeer Plus System
 * ==============================================
 */

session_start();

// تضمين ملفات الإعدادات
require_once 'config/constants.php';
require_once 'config/db.php';
require_once 'config/i18n.php';
require_once 'config/email.php';
require_once 'config/permissions.php';
require_once 'config/functions.php';

// التحقق من السماح بالتسجيل
if (!getSetting('allow_registration', false)) {
    header('Location: login.php?error=registration_disabled');
    exit;
}

// التحقق من تسجيل الدخول المسبق
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard_redirect.php');
    exit;
}

// متغيرات الرسائل
$error_message = '';
$success_message = '';
$registration_success = false;

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من رمز CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_message = __('error_csrf_token');
    } else {
        $result = processRegistration($_POST);
        
        if ($result['success']) {
            $success_message = $result['message'];
            $registration_success = true;
        } else {
            $error_message = $result['message'];
        }
    }
}

/**
 * معالجة طلب التسجيل
 * Process Registration Request
 */
function processRegistration($data) {
    // تنظيف البيانات
    $fullName = filterInput($data['full_name'] ?? '', 'string');
    $email = filterInput($data['email'] ?? '', 'email');
    $phone = filterInput($data['phone'] ?? '', 'string');
    $emiratesId = filterInput($data['emirates_id'] ?? '', 'string');
    $password = $data['password'] ?? '';
    $confirmPassword = $data['confirm_password'] ?? '';
    $agreeTerms = isset($data['agree_terms']);
    
    // التحقق من البيانات الأساسية
    $validation = validateRegistrationData($fullName, $email, $phone, $emiratesId, $password, $confirmPassword, $agreeTerms);
    
    if (!$validation['valid']) {
        return ['success' => false, 'message' => $validation['message']];
    }
    
    // التحقق من عدم وجود المستخدم مسبقاً
    $existingUser = checkExistingUser($email, $phone, $emiratesId);
    if (!$existingUser['unique']) {
        return ['success' => false, 'message' => $existingUser['message']];
    }
    
    // إنشاء الحساب
    return createUserAccount($fullName, $email, $phone, $emiratesId, $password);
}

/**
 * التحقق من صحة بيانات التسجيل
 * Validate Registration Data
 */
function validateRegistrationData($fullName, $email, $phone, $emiratesId, $password, $confirmPassword, $agreeTerms) {
    // التحقق من الحقول المطلوبة
    if (empty($fullName) || empty($email) || empty($phone) || empty($emiratesId) || empty($password)) {
        return ['valid' => false, 'message' => __('error_required_fields')];
    }
    
    // التحقق من البريد الإلكتروني
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'message' => __('error_invalid_email')];
    }
    
    // التحقق من رقم الهوية الإماراتية
    if (!validateEmiratesId($emiratesId)) {
        return ['valid' => false, 'message' => __('error_invalid_emirates_id')];
    }
    
    // التحقق من رقم الهاتف
    if (!validateUaePhone($phone)) {
        return ['valid' => false, 'message' => __('error_invalid_phone')];
    }
    
    // التحقق من كلمة المرور
    if ($password !== $confirmPassword) {
        return ['valid' => false, 'message' => __('password_mismatch')];
    }
    
    $passwordCheck = checkPasswordStrength($password);
    if (!$passwordCheck['is_strong']) {
        return ['valid' => false, 'message' => implode('<br>', $passwordCheck['errors'])];
    }
    
    // التحقق من الموافقة على الشروط
    if (!$agreeTerms) {
        return ['valid' => false, 'message' => __('error_agree_terms')];
    }
    
    return ['valid' => true, 'message' => ''];
}

/**
 * التحقق من عدم وجود المستخدم مسبقاً
 * Check Existing User
 */
function checkExistingUser($email, $phone, $emiratesId) {
    $db = getDB();
    
    try {
        // التحقق من البريد الإلكتروني
        $sql = "SELECT id FROM users WHERE email = :email";
        $existing = $db->selectOne($sql, ['email' => $email]);
        if ($existing) {
            return ['unique' => false, 'message' => __('error_email_exists')];
        }
        
        // التحقق من الهاتف
        $sql = "SELECT id FROM users WHERE phone = :phone";
        $existing = $db->selectOne($sql, ['phone' => $phone]);
        if ($existing) {
            return ['unique' => false, 'message' => __('error_phone_exists')];
        }
        
        // التحقق من رقم الهوية في جدول العملاء
        $sql = "SELECT id FROM clients WHERE emirates_id = :emirates_id";
        $existing = $db->selectOne($sql, ['emirates_id' => $emiratesId]);
        if ($existing) {
            return ['unique' => false, 'message' => __('error_emirates_id_exists')];
        }
        
        return ['unique' => true, 'message' => ''];
        
    } catch (Exception $e) {
        error_log("Check existing user error: " . $e->getMessage());
        return ['unique' => false, 'message' => __('error_database')];
    }
}

/**
 * إنشاء حساب المستخدم
 * Create User Account
 */
function createUserAccount($fullName, $email, $phone, $emiratesId, $password) {
    $db = getDB();
    
    try {
        $db->beginTransaction();
        
        // إنشاء رمز التحقق من البريد الإلكتروني
        $emailVerificationToken = bin2hex(random_bytes(32));
        
        // الحصول على دور العميل
        $clientRole = $db->selectOne("SELECT id FROM roles WHERE role_name = 'client'");
        if (!$clientRole) {
            $db->rollback();
            return ['success' => false, 'message' => __('error_client_role_not_found')];
        }
        
        // إنشاء حساب المستخدم
        $userData = [
            'username' => generateUniqueUsername($email),
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'full_name' => $fullName,
            'phone' => $phone,
            'role_id' => $clientRole['id'],
            'user_type' => 'client',
            'preferred_language' => getCurrentLanguage(),
            'email_verification_token' => $emailVerificationToken,
            'is_active' => 0 // غير نشط حتى تأكيد البريد الإلكتروني
        ];
        
        $userId = createUser($userData);
        if (!$userId) {
            $db->rollback();
            return ['success' => false, 'message' => __('error_create_user')];
        }
        
        // إنشاء ملف العميل
        $clientCode = generateClientCode();
        $clientData = [
            'user_id' => $userId,
            'client_code' => $clientCode,
            'emirates_id' => $emiratesId,
            'full_name_ar' => $fullName,
            'full_name_en' => $fullName,
            'phone_primary' => $phone,
            'email' => $email,
            'status' => 'نشط'
        ];
        
        $clientId = createClient($clientData);
        if (!$clientId) {
            $db->rollback();
            return ['success' => false, 'message' => __('error_create_client')];
        }
        
        // إرسال بريد التحقق
        $emailSent = sendVerificationEmail($email, $fullName, $emailVerificationToken);
        
        if ($emailSent) {
            $db->commit();
            
            // تسجيل النشاط
            logActivity('user_registration', 'New user registered: ' . $email, $userId);
            
            return [
                'success' => true,
                'message' => __('registration_success_verify_email')
            ];
        } else {
            $db->rollback();
            return ['success' => false, 'message' => __('error_send_verification_email')];
        }
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => __('error_registration_failed')];
    }
}

/**
 * دوال مساعدة للتسجيل
 * Registration Helper Functions
 */
function generateUniqueUsername($email) {
    $base = strtolower(explode('@', $email)[0]);
    $username = $base;
    $counter = 1;
    
    $db = getDB();
    while ($db->exists('users', ['username' => $username])) {
        $username = $base . $counter;
        $counter++;
    }
    
    return $username;
}

function generateClientCode() {
    $db = getDB();
    do {
        $code = 'CL' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    } while ($db->exists('clients', ['client_code' => $code]));
    
    return $code;
}

function createUser($userData) {
    $db = getDB();
    
    $sql = "INSERT INTO users (username, email, password, full_name, phone, role_id, user_type, preferred_language, email_verification_token, is_active) 
            VALUES (:username, :email, :password, :full_name, :phone, :role_id, :user_type, :preferred_language, :email_verification_token, :is_active)";
    
    return $db->insert($sql, $userData);
}

function createClient($clientData) {
    $db = getDB();
    
    $sql = "INSERT INTO clients (user_id, client_code, emirates_id, full_name_ar, full_name_en, phone_primary, email, status) 
            VALUES (:user_id, :client_code, :emirates_id, :full_name_ar, :full_name_en, :phone_primary, :email, :status)";
    
    return $db->insert($sql, $clientData);
}

$pageTitle = __('register') . ' - ' . __('app_name');
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
            <div class="col-xl-8 col-lg-10 col-md-12">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="p-5">
                            <div class="text-center">
                                <img src="assets/images/logo.png" alt="Logo" height="60" class="mb-4">
                                
                                <?php if ($registration_success): ?>
                                    <i class="bi bi-check2-circle text-success" style="font-size: 3rem;"></i>
                                    <h1 class="h4 text-gray-900 mt-3 mb-2"><?php _e('registration_success_title'); ?></h1>
                                    <p class="mb-4"><?php _e('registration_success_subtitle'); ?></p>
                                <?php else: ?>
                                    <h1 class="h4 text-gray-900 mb-2"><?php _e('register_title'); ?></h1>
                                    <p class="mb-4"><?php _e('register_subtitle'); ?></p>
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
                            
                            <?php if (!$registration_success): ?>
                            <!-- نموذج التسجيل -->
                            <form method="POST" action="" class="user" id="registrationForm">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="full_name" class="form-label">
                                                <?php _e('full_name'); ?> *
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-person"></i>
                                                </span>
                                                <input type="text" 
                                                       class="form-control form-control-user" 
                                                       id="full_name" 
                                                       name="full_name" 
                                                       placeholder="<?php _e('enter_full_name'); ?>"
                                                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                                                       required 
                                                       autofocus>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="emirates_id" class="form-label">
                                                <?php _e('emirates_id'); ?> *
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-card-text"></i>
                                                </span>
                                                <input type="text" 
                                                       class="form-control form-control-user" 
                                                       id="emirates_id" 
                                                       name="emirates_id" 
                                                       placeholder="784-XXXX-XXXXXXX-X"
                                                       value="<?php echo htmlspecialchars($_POST['emirates_id'] ?? ''); ?>"
                                                       maxlength="18"
                                                       required>
                                            </div>
                                            <small class="form-text text-muted">
                                                <?php _e('emirates_id_format_help'); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
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
                                                       required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="phone" class="form-label">
                                                <?php _e('phone_number'); ?> *
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-phone"></i>
                                                </span>
                                                <input type="tel" 
                                                       class="form-control form-control-user" 
                                                       id="phone" 
                                                       name="phone" 
                                                       placeholder="05XXXXXXXX"
                                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                                       required>
                                            </div>
                                            <small class="form-text text-muted">
                                                <?php _e('uae_phone_format_help'); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
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
                                                       required 
                                                       minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                                                <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                                    <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="confirm_password" class="form-label">
                                                <?php _e('confirm_password'); ?> *
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-lock-fill"></i>
                                                </span>
                                                <input type="password" 
                                                       class="form-control form-control-user" 
                                                       id="confirm_password" 
                                                       name="confirm_password" 
                                                       placeholder="<?php _e('confirm_password'); ?>"
                                                       required>
                                                <button type="button" class="btn btn-outline-secondary" id="toggleConfirmPassword">
                                                    <i class="bi bi-eye" id="toggleConfirmPasswordIcon"></i>
                                                </button>
                                            </div>
                                        </div>
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
                                
                                <div class="form-group mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="agree_terms" name="agree_terms" required>
                                        <label class="form-check-label" for="agree_terms">
                                            <?php _e('agree_to'); ?> 
                                            <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">
                                                <?php _e('terms_and_conditions'); ?>
                                            </a>
                                        </label>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-user btn-block w-100 mb-4">
                                    <i class="bi bi-person-plus me-2"></i>
                                    <?php _e('create_account'); ?>
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <hr>
                            
                            <div class="text-center">
                                <?php if ($registration_success): ?>
                                    <a class="btn btn-primary" href="login.php">
                                        <i class="bi bi-box-arrow-in-right me-1"></i>
                                        <?php _e('login_now'); ?>
                                    </a>
                                <?php else: ?>
                                    <a class="small" href="login.php">
                                        <?php _e('already_have_account'); ?>
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

    <!-- نافذة الشروط والأحكام -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php _e('terms_and_conditions'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="terms-content">
                        <?php include 'includes/terms_and_conditions.php'; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?php _e('close'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript مخصص -->
    <script>
        // تنسيق رقم الهوية الإماراتية
        document.getElementById('emirates_id').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = value.substring(0, 15);
                value = value.replace(/(\d{3})(\d{4})(\d{7})(\d{1})/, '$1-$2-$3-$4');
            }
            this.value = value;
        });
        
        // تنسيق رقم الهاتف
        document.getElementById('phone').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            this.value = value;
        });
        
        // تبديل عرض كلمات المرور
        ['togglePassword', 'toggleConfirmPassword'].forEach(id => {
            document.getElementById(id)?.addEventListener('click', function() {
                const targetId = id === 'togglePassword' ? 'password' : 'confirm_password';
                const iconId = id + 'Icon';
                const passwordField = document.getElementById(targetId);
                const toggleIcon = document.getElementById(iconId);
                
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    toggleIcon.className = 'bi bi-eye-slash';
                } else {
                    passwordField.type = 'password';
                    toggleIcon.className = 'bi bi-eye';
                }
            });
        });
        
        // فحص قوة كلمة المرور
        document.getElementById('password').addEventListener('input', function() {
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
            
            if (password.length >= 8) score += 25;
            if (/[A-Z]/.test(password)) score += 25;
            if (/[a-z]/.test(password)) score += 25;
            if (/[0-9]/.test(password) && /[^A-Za-z0-9]/.test(password)) score += 25;
            
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
        document.getElementById('confirm_password').addEventListener('input', function() {
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
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const requiredFields = ['full_name', 'emirates_id', 'email', 'phone', 'password', 'confirm_password'];
            let valid = true;
            
            requiredFields.forEach(field => {
                const element = document.getElementById(field);
                if (!element.value.trim()) {
                    valid = false;
                    element.classList.add('is-invalid');
                } else {
                    element.classList.remove('is-invalid');
                }
            });
            
            if (!valid) {
                e.preventDefault();
                alert('<?php _e("please_fill_all_fields"); ?>');
                return false;
            }
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('<?php _e("passwords_dont_match"); ?>');
                return false;
            }
            
            if (!document.getElementById('agree_terms').checked) {
                e.preventDefault();
                alert('<?php _e("must_agree_terms"); ?>');
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
        
        .terms-content {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</body>
</html>
