<?php
/**
 * ==============================================
 * تدبير بلس - نظام الصلاحيات والأمان
 * Tadbeer Plus - Permissions and Security System
 * ==============================================
 */

// منع الوصول المباشر
if (!defined('TADBEER_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * فئة إدارة نظام الصلاحيات
 * Permissions Management Class
 */
class PermissionsManager {
    
    private static $instance = null;
    private $db;
    private $userPermissions = [];
    private $rolePermissions = [];
    private $screensList = [];
    private $currentUser = null;
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->db = getDB();
        $this->loadCurrentUser();
        $this->loadScreensList();
        $this->loadUserPermissions();
    }
    
    /**
     * الحصول على مثيل مدير الصلاحيات
     * Get Permissions Manager Instance (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * تحميل بيانات المستخدم الحالي
     * Load Current User Data
     */
    private function loadCurrentUser() {
        if (!isset($_SESSION['user_id'])) {
            return;
        }
        
        try {
            $sql = "SELECT u.*, r.role_name, r.role_level 
                    FROM users u 
                    LEFT JOIN roles r ON u.role_id = r.id 
                    WHERE u.id = :user_id AND u.is_active = 1";
            
            $this->currentUser = $this->db->selectOne($sql, ['user_id' => $_SESSION['user_id']]);
        } catch (Exception $e) {
            error_log("Failed to load current user: " . $e->getMessage());
        }
    }
    
    /**
     * تحميل قائمة الشاشات
     * Load Screens List
     */
    private function loadScreensList() {
        try {
            $sql = "SELECT id, screen_name, screen_title_ar, screen_url, parent_screen_id 
                    FROM screens WHERE is_active = 1 ORDER BY sort_order";
            
            $screens = $this->db->select($sql);
            
            foreach ($screens as $screen) {
                $this->screensList[$screen['screen_name']] = $screen;
            }
        } catch (Exception $e) {
            error_log("Failed to load screens list: " . $e->getMessage());
        }
    }
    
    /**
     * تحميل صلاحيات المستخدم
     * Load User Permissions
     */
    private function loadUserPermissions() {
        if (!$this->currentUser) {
            return;
        }
        
        try {
            // تحميل صلاحيات الدور
            $sql = "SELECT s.screen_name, rp.can_view, rp.can_add, rp.can_edit, 
                           rp.can_delete, rp.can_export, rp.can_approve
                    FROM role_permissions rp
                    JOIN screens s ON rp.screen_id = s.id
                    WHERE rp.role_id = :role_id AND s.is_active = 1";
            
            $rolePerms = $this->db->select($sql, ['role_id' => $this->currentUser['role_id']]);
            
            foreach ($rolePerms as $perm) {
                $this->rolePermissions[$perm['screen_name']] = [
                    'can_view' => (bool)$perm['can_view'],
                    'can_add' => (bool)$perm['can_add'],
                    'can_edit' => (bool)$perm['can_edit'],
                    'can_delete' => (bool)$perm['can_delete'],
                    'can_export' => (bool)$perm['can_export'],
                    'can_approve' => (bool)$perm['can_approve']
                ];
            }
            
            // تحميل الصلاحيات الخاصة للمستخدم
            $sql = "SELECT s.screen_name, up.can_view, up.can_add, up.can_edit, 
                           up.can_delete, up.can_export, up.can_approve
                    FROM user_permissions up
                    JOIN screens s ON up.screen_id = s.id
                    WHERE up.user_id = :user_id AND s.is_active = 1 
                    AND (up.expires_at IS NULL OR up.expires_at > NOW())";
            
            $userPerms = $this->db->select($sql, ['user_id' => $this->currentUser['id']]);
            
            foreach ($userPerms as $perm) {
                $this->userPermissions[$perm['screen_name']] = [
                    'can_view' => $perm['can_view'] !== null ? (bool)$perm['can_view'] : null,
                    'can_add' => $perm['can_add'] !== null ? (bool)$perm['can_add'] : null,
                    'can_edit' => $perm['can_edit'] !== null ? (bool)$perm['can_edit'] : null,
                    'can_delete' => $perm['can_delete'] !== null ? (bool)$perm['can_delete'] : null,
                    'can_export' => $perm['can_export'] !== null ? (bool)$perm['can_export'] : null,
                    'can_approve' => $perm['can_approve'] !== null ? (bool)$perm['can_approve'] : null
                ];
            }
        } catch (Exception $e) {
            error_log("Failed to load user permissions: " . $e->getMessage());
        }
    }
    
    /**
     * التحقق من صلاحية معينة
     * Check Specific Permission
     */
    public function hasPermission($screenName, $permission = 'can_view') {
        if (!$this->currentUser) {
            return false;
        }
        
        // المدير العام له كامل الصلاحيات
        if ($this->currentUser['role_name'] === 'super_admin') {
            return true;
        }
        
        // التحقق من الصلاحية الخاصة للمستخدم أولاً
        if (isset($this->userPermissions[$screenName][$permission])) {
            $userPerm = $this->userPermissions[$screenName][$permission];
            if ($userPerm !== null) {
                return $userPerm;
            }
        }
        
        // التحقق من صلاحية الدور
        if (isset($this->rolePermissions[$screenName][$permission])) {
            return $this->rolePermissions[$screenName][$permission];
        }
        
        return false;
    }
    
    /**
     * التحقق من صلاحية العرض
     * Check View Permission
     */
    public function canView($screenName) {
        return $this->hasPermission($screenName, 'can_view');
    }
    
    /**
     * التحقق من صلاحية الإضافة
     * Check Add Permission
     */
    public function canAdd($screenName) {
        return $this->hasPermission($screenName, 'can_add');
    }
    
    /**
     * التحقق من صلاحية التعديل
     * Check Edit Permission
     */
    public function canEdit($screenName) {
        return $this->hasPermission($screenName, 'can_edit');
    }
    
    /**
     * التحقق من صلاحية الحذف
     * Check Delete Permission
     */
    public function canDelete($screenName) {
        return $this->hasPermission($screenName, 'can_delete');
    }
    
    /**
     * التحقق من صلاحية التصدير
     * Check Export Permission
     */
    public function canExport($screenName) {
        return $this->hasPermission($screenName, 'can_export');
    }
    
    /**
     * التحقق من صلاحية الموافقة
     * Check Approve Permission
     */
    public function canApprove($screenName) {
        return $this->hasPermission($screenName, 'can_approve');
    }
    
    /**
     * التحقق من مستوى الدور
     * Check Role Level
     */
    public function hasRoleLevel($minimumLevel) {
        if (!$this->currentUser) {
            return false;
        }
        
        return (int)$this->currentUser['role_level'] <= (int)$minimumLevel;
    }
    
    /**
     * التحقق من كون المستخدم مدير
     * Check if User is Admin
     */
    public function isAdmin() {
        if (!$this->currentUser) {
            return false;
        }
        
        return in_array($this->currentUser['role_name'], ['super_admin', 'admin']);
    }
    
    /**
     * التحقق من كون المستخدم مدير عام
     * Check if User is Super Admin
     */
    public function isSuperAdmin() {
        if (!$this->currentUser) {
            return false;
        }
        
        return $this->currentUser['role_name'] === 'super_admin';
    }
    
    /**
     * الحصول على قائمة الشاشات المسموحة للمستخدم
     * Get User's Allowed Screens
     */
    public function getAllowedScreens() {
        $allowedScreens = [];
        
        foreach ($this->screensList as $screenName => $screen) {
            if ($this->canView($screenName)) {
                $allowedScreens[] = $screen;
            }
        }
        
        return $allowedScreens;
    }
    
    /**
     * منح صلاحية خاصة لمستخدم
     * Grant Special Permission to User
     */
    public function grantUserPermission($userId, $screenName, $permissions, $expiryDate = null, $grantedBy = null) {
        if (!$this->isSuperAdmin() && !$this->isAdmin()) {
            return false;
        }
        
        try {
            $screenId = $this->getScreenId($screenName);
            if (!$screenId) {
                return false;
            }
            
            $sql = "INSERT INTO user_permissions 
                    (user_id, screen_id, can_view, can_add, can_edit, can_delete, can_export, can_approve, granted_by, expires_at) 
                    VALUES (:user_id, :screen_id, :can_view, :can_add, :can_edit, :can_delete, :can_export, :can_approve, :granted_by, :expires_at)
                    ON DUPLICATE KEY UPDATE
                    can_view = VALUES(can_view),
                    can_add = VALUES(can_add),
                    can_edit = VALUES(can_edit),
                    can_delete = VALUES(can_delete),
                    can_export = VALUES(can_export),
                    can_approve = VALUES(can_approve),
                    granted_by = VALUES(granted_by),
                    expires_at = VALUES(expires_at)";
            
            $params = [
                'user_id' => $userId,
                'screen_id' => $screenId,
                'can_view' => $permissions['can_view'] ?? null,
                'can_add' => $permissions['can_add'] ?? null,
                'can_edit' => $permissions['can_edit'] ?? null,
                'can_delete' => $permissions['can_delete'] ?? null,
                'can_export' => $permissions['can_export'] ?? null,
                'can_approve' => $permissions['can_approve'] ?? null,
                'granted_by' => $grantedBy ?: $this->currentUser['id'],
                'expires_at' => $expiryDate
            ];
            
            return $this->db->query($sql, $params);
            
        } catch (Exception $e) {
            error_log("Failed to grant user permission: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * إلغاء صلاحية خاصة للمستخدم
     * Revoke User Permission
     */
    public function revokeUserPermission($userId, $screenName) {
        if (!$this->isSuperAdmin() && !$this->isAdmin()) {
            return false;
        }
        
        try {
            $screenId = $this->getScreenId($screenName);
            if (!$screenId) {
                return false;
            }
            
            $sql = "DELETE FROM user_permissions WHERE user_id = :user_id AND screen_id = :screen_id";
            
            return $this->db->delete($sql, [
                'user_id' => $userId,
                'screen_id' => $screenId
            ]);
            
        } catch (Exception $e) {
            error_log("Failed to revoke user permission: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * الحصول على معرف الشاشة
     * Get Screen ID
     */
    private function getScreenId($screenName) {
        return isset($this->screensList[$screenName]) ? $this->screensList[$screenName]['id'] : null;
    }
    
    /**
     * تسجيل نشاط الصلاحية
     * Log Permission Activity
     */
    private function logPermissionActivity($action, $details) {
        try {
            $sql = "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent, created_at) 
                    VALUES (:user_id, :action, :description, :ip_address, :user_agent, NOW())";
            
            $params = [
                'user_id' => $this->currentUser['id'] ?? null,
                'action' => 'permission_' . $action,
                'description' => $details,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ];
            
            $this->db->insert($sql, $params);
            
        } catch (Exception $e) {
            error_log("Failed to log permission activity: " . $e->getMessage());
        }
    }
    
    /**
     * إنشاء رمز CSRF
     * Generate CSRF Token
     */
    public function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * التحقق من رمز CSRF
     * Verify CSRF Token
     */
    public function verifyCsrfToken($token) {
        if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * فلترة البيانات المدخلة
     * Filter Input Data
     */
    public function filterInput($data, $type = 'string') {
        switch ($type) {
            case 'int':
                return filter_var($data, FILTER_VALIDATE_INT);
                
            case 'float':
                return filter_var($data, FILTER_VALIDATE_FLOAT);
                
            case 'email':
                return filter_var($data, FILTER_VALIDATE_EMAIL);
                
            case 'url':
                return filter_var($data, FILTER_VALIDATE_URL);
                
            case 'string':
            default:
                return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * التحقق من قوة كلمة المرور
     * Check Password Strength
     */
    public function checkPasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = __('password_too_short', ['min' => PASSWORD_MIN_LENGTH]);
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = __('password_missing_uppercase');
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = __('password_missing_lowercase');
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = __('password_missing_number');
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = __('password_missing_special');
        }
        
        return [
            'is_strong' => empty($errors),
            'errors' => $errors,
            'score' => $this->calculatePasswordScore($password)
        ];
    }
    
    /**
     * حساب نقاط قوة كلمة المرور
     * Calculate Password Score
     */
    private function calculatePasswordScore($password) {
        $score = 0;
        
        // طول كلمة المرور
        if (strlen($password) >= 8) $score += 25;
        if (strlen($password) >= 12) $score += 25;
        
        // وجود أحرف كبيرة
        if (preg_match('/[A-Z]/', $password)) $score += 12.5;
        
        // وجود أحرف صغيرة
        if (preg_match('/[a-z]/', $password)) $score += 12.5;
        
        // وجود أرقام
        if (preg_match('/[0-9]/', $password)) $score += 12.5;
        
        // وجود رموز خاصة
        if (preg_match('/[^A-Za-z0-9]/', $password)) $score += 12.5;
        
        return min(100, $score);
    }
}

/**
 * ==============================================
 * الدوال المساعدة للصلاحيات
 * Permissions Helper Functions
 * ==============================================
 */

/**
 * الحصول على مدير الصلاحيات
 * Get Permissions Manager
 */
function getPermissionsManager() {
    return PermissionsManager::getInstance();
}

/**
 * التحقق من صلاحية معينة
 * Check Permission
 */
function hasPermission($screenName, $permission = 'can_view') {
    return getPermissionsManager()->hasPermission($screenName, $permission);
}

/**
 * التحقق من صلاحية العرض
 * Check View Permission
 */
function canView($screenName) {
    return getPermissionsManager()->canView($screenName);
}

/**
 * التحقق من صلاحية الإضافة
 * Check Add Permission
 */
function canAdd($screenName) {
    return getPermissionsManager()->canAdd($screenName);
}

/**
 * التحقق من صلاحية التعديل
 * Check Edit Permission
 */
function canEdit($screenName) {
    return getPermissionsManager()->canEdit($screenName);
}

/**
 * التحقق من صلاحية الحذف
 * Check Delete Permission
 */
function canDelete($screenName) {
    return getPermissionsManager()->canDelete($screenName);
}

/**
 * التحقق من صلاحية التصدير
 * Check Export Permission
 */
function canExport($screenName) {
    return getPermissionsManager()->canExport($screenName);
}

/**
 * التحقق من صلاحية الموافقة
 * Check Approve Permission
 */
function canApprove($screenName) {
    return getPermissionsManager()->canApprove($screenName);
}

/**
 * التحقق من كون المستخدم مدير
 * Check if User is Admin
 */
function isAdmin() {
    return getPermissionsManager()->isAdmin();
}

/**
 * التحقق من كون المستخدم مدير عام
 * Check if User is Super Admin
 */
function isSuperAdmin() {
    return getPermissionsManager()->isSuperAdmin();
}

/**
 * إنشاء رمز CSRF
 * Generate CSRF Token
 */
function generateCsrfToken() {
    return getPermissionsManager()->generateCsrfToken();
}

/**
 * التحقق من رمز CSRF
 * Verify CSRF Token
 */
function verifyCsrfToken($token) {
    return getPermissionsManager()->verifyCsrfToken($token);
}

/**
 * إنشاء حقل CSRF مخفي
 * Generate CSRF Hidden Field
 */
function csrfTokenField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . $token . '">';
}

/**
 * فلترة البيانات المدخلة
 * Filter Input Data
 */
function filterInput($data, $type = 'string') {
    return getPermissionsManager()->filterInput($data, $type);
}

/**
 * التحقق من قوة كلمة المرور
 * Check Password Strength
 */
function checkPasswordStrength($password) {
    return getPermissionsManager()->checkPasswordStrength($password);
}

/**
 * إعادة توجيه في حالة عدم وجود صلاحية
 * Redirect if No Permission
 */
function requirePermission($screenName, $permission = 'can_view', $redirectUrl = null) {
    if (!hasPermission($screenName, $permission)) {
        if ($redirectUrl) {
            header("Location: $redirectUrl");
        } else {
            header("Location: " . BASE_URL . "error/403.php");
        }
        exit;
    }
}

/**
 * التحقق من تسجيل الدخول
 * Check if User is Logged In
 */
function requireLogin($redirectUrl = null) {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        if ($redirectUrl) {
            $_SESSION['redirect_after_login'] = $redirectUrl;
        }
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
}

/**
 * التحقق من نوع المستخدم
 * Check User Type
 */
function requireUserType($userType) {
    requireLogin();
    
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== $userType) {
        header("Location: " . BASE_URL . "error/403.php");
        exit;
    }
}

// تهيئة مدير الصلاحيات
$permissionsManager = getPermissionsManager();

?>
