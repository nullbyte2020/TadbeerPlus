<?php
/**
 * ==============================================
 * تدبير بلس - إعدادات النظام
 * Tadbeer Plus - System Settings
 * ==============================================
 */

// منع الوصول المباشر
if (!defined('TADBEER_ACCESS')) {
    die('Direct access not permitted');
}

require_once 'constants.php';
require_once 'db.php';

/**
 * فئة إدارة إعدادات النظام
 * System Settings Management Class
 */
class SystemSettings {
    
    private static $instance = null;
    private $db;
    private $settings = [];
    private $isLoaded = false;
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->db = getDB();
        $this->loadSettings();
    }
    
    /**
     * الحصول على مثيل الإعدادات
     * Get Settings Instance (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * تحميل جميع الإعدادات من قاعدة البيانات
     * Load All Settings from Database
     */
    private function loadSettings() {
        if ($this->isLoaded) {
            return;
        }
        
        try {
            $sql = "SELECT setting_key, setting_value, setting_type FROM system_settings WHERE 1=1";
            $result = $this->db->select($sql);
            
            if ($result) {
                foreach ($result as $setting) {
                    $this->settings[$setting['setting_key']] = $this->convertValue(
                        $setting['setting_value'], 
                        $setting['setting_type']
                    );
                }
            }
            
            $this->isLoaded = true;
            
        } catch (Exception $e) {
            error_log("Failed to load system settings: " . $e->getMessage());
        }
    }
    
    /**
     * تحويل القيمة حسب النوع
     * Convert Value by Type
     */
    private function convertValue($value, $type) {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'number':
                return is_numeric($value) ? (int)$value : 0;
            case 'json':
                return json_decode($value, true) ?: [];
            default:
                return $value;
        }
    }
    
    /**
     * الحصول على قيمة إعداد
     * Get Setting Value
     */
    public function get($key, $default = null) {
        $this->loadSettings();
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }
    
    /**
     * تعيين قيمة إعداد
     * Set Setting Value
     */
    public function set($key, $value, $type = 'text') {
        try {
            // تحويل القيمة للتخزين
            $storeValue = $this->prepareValueForStorage($value, $type);
            
            $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_type, updated_by) 
                    VALUES (:key, :value, :type, :user_id) 
                    ON DUPLICATE KEY UPDATE 
                    setting_value = :value, 
                    setting_type = :type, 
                    updated_by = :user_id, 
                    updated_at = NOW()";
            
            $params = [
                'key' => $key,
                'value' => $storeValue,
                'type' => $type,
                'user_id' => $_SESSION['user_id'] ?? 1
            ];
            
            $result = $this->db->query($sql, $params);
            
            if ($result) {
                $this->settings[$key] = $value;
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Failed to set system setting: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * تحضير القيمة للتخزين
     * Prepare Value for Storage
     */
    private function prepareValueForStorage($value, $type) {
        switch ($type) {
            case 'boolean':
                return $value ? '1' : '0';
            case 'json':
                return json_encode($value);
            default:
                return (string)$value;
        }
    }
    
    /**
     * حذف إعداد
     * Delete Setting
     */
    public function delete($key) {
        try {
            $sql = "DELETE FROM system_settings WHERE setting_key = :key";
            $result = $this->db->delete($sql, ['key' => $key]);
            
            if ($result) {
                unset($this->settings[$key]);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Failed to delete system setting: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * الحصول على جميع الإعدادات
     * Get All Settings
     */
    public function getAll() {
        $this->loadSettings();
        return $this->settings;
    }
    
    /**
     * الحصول على الإعدادات العامة
     * Get Public Settings
     */
    public function getPublicSettings() {
        try {
            $sql = "SELECT setting_key, setting_value, setting_type 
                    FROM system_settings 
                    WHERE is_public = 1";
            
            $result = $this->db->select($sql);
            $publicSettings = [];
            
            if ($result) {
                foreach ($result as $setting) {
                    $publicSettings[$setting['setting_key']] = $this->convertValue(
                        $setting['setting_value'], 
                        $setting['setting_type']
                    );
                }
            }
            
            return $publicSettings;
            
        } catch (Exception $e) {
            error_log("Failed to get public settings: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * تحديث إعدادات متعددة
     * Update Multiple Settings
     */
    public function updateMultiple($settings) {
        try {
            $this->db->beginTransaction();
            
            foreach ($settings as $key => $data) {
                $value = $data['value'] ?? '';
                $type = $data['type'] ?? 'text';
                
                if (!$this->set($key, $value, $type)) {
                    $this->db->rollback();
                    return false;
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Failed to update multiple settings: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * إعادة تحميل الإعدادات
     * Reload Settings
     */
    public function reload() {
        $this->isLoaded = false;
        $this->settings = [];
        $this->loadSettings();
    }
}

/**
 * ==============================================
 * إعدادات الشركة
 * Company Settings Class
 * ==============================================
 */
class CompanySettings {
    
    private static $instance = null;
    private $db;
    private $settings = [];
    
    private function __construct() {
        $this->db = getDB();
        $this->loadCompanySettings();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * تحميل إعدادات الشركة
     * Load Company Settings
     */
    private function loadCompanySettings() {
        try {
            $sql = "SELECT * FROM company_settings ORDER BY id DESC LIMIT 1";
            $result = $this->db->selectOne($sql);
            
            if ($result) {
                $this->settings = $result;
            } else {
                // إنشاء إعدادات افتراضية
                $this->createDefaultSettings();
            }
            
        } catch (Exception $e) {
            error_log("Failed to load company settings: " . $e->getMessage());
        }
    }
    
    /**
     * إنشاء إعدادات افتراضية
     * Create Default Settings
     */
    private function createDefaultSettings() {
        $defaultSettings = [
            'company_name_ar' => 'شركة تدبير بلس لإدارة العمالة المنزلية',
            'company_name_en' => 'Tadbeer Plus Domestic Workers Management',
            'license_number' => 'DED-123456',
            'address_ar' => 'دبي، الإمارات العربية المتحدة',
            'address_en' => 'Dubai, United Arab Emirates',
            'city' => 'دبي',
            'country' => 'الإمارات العربية المتحدة',
            'phone' => '+971-4-1234567',
            'email' => 'info@tadbeerplus.ae',
            'website' => 'https://tadbeerplus.ae',
            'default_currency' => 'AED',
            'timezone' => 'Asia/Dubai',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i'
        ];
        
        $this->updateCompanySettings($defaultSettings);
    }
    
    /**
     * الحصول على إعداد شركة
     * Get Company Setting
     */
    public function get($key, $default = null) {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }
    
    /**
     * تحديث إعدادات الشركة
     * Update Company Settings
     */
    public function updateCompanySettings($data) {
        try {
            if (empty($this->settings)) {
                // إنشاء سجل جديد
                $columns = implode(', ', array_keys($data));
                $placeholders = ':' . implode(', :', array_keys($data));
                
                $sql = "INSERT INTO company_settings ({$columns}, updated_by, created_at) 
                        VALUES ({$placeholders}, :updated_by, NOW())";
                
                $data['updated_by'] = $_SESSION['user_id'] ?? 1;
                $result = $this->db->insert($sql, $data);
                
            } else {
                // تحديث السجل الموجود
                $setParts = [];
                foreach (array_keys($data) as $key) {
                    $setParts[] = "{$key} = :{$key}";
                }
                
                $sql = "UPDATE company_settings 
                        SET " . implode(', ', $setParts) . ", 
                            updated_by = :updated_by, 
                            updated_at = NOW() 
                        WHERE id = :id";
                
                $data['updated_by'] = $_SESSION['user_id'] ?? 1;
                $data['id'] = $this->settings['id'];
                
                $result = $this->db->update($sql, $data);
            }
            
            if ($result) {
                $this->loadCompanySettings();
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Failed to update company settings: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * الحصول على جميع إعدادات الشركة
     * Get All Company Settings
     */
    public function getAll() {
        return $this->settings;
    }
}

/**
 * ==============================================
 * الدوال المساعدة للإعدادات
 * Settings Helper Functions
 * ==============================================
 */

/**
 * الحصول على إعداد نظام
 * Get System Setting
 */
function getSetting($key, $default = null) {
    return SystemSettings::getInstance()->get($key, $default);
}

/**
 * تعيين إعداد نظام
 * Set System Setting
 */
function setSetting($key, $value, $type = 'text') {
    return SystemSettings::getInstance()->set($key, $value, $type);
}

/**
 * الحصول على إعداد شركة
 * Get Company Setting
 */
function getCompanySetting($key, $default = null) {
    return CompanySettings::getInstance()->get($key, $default);
}

/**
 * الحصول على جميع الإعدادات العامة
 * Get All Public Settings
 */
function getPublicSettings() {
    return SystemSettings::getInstance()->getPublicSettings();
}

/**
 * إعدادات النظام الافتراضية
 * Default System Settings
 */
$defaultSystemSettings = [
    'site_name' => 'تدبير بلس',
    'site_url' => 'https://tadbeerplus.ae',
    'default_language' => 'ar',
    'timezone' => 'Asia/Dubai',
    'currency' => 'AED',
    'date_format' => 'd/m/Y',
    'pagination_limit' => '25',
    'session_timeout' => '3600',
    'max_login_attempts' => '5',
    'backup_retention_days' => '30',
    'maintenance_mode' => false,
    'debug_mode' => false,
    'email_notifications' => true,
    'sms_notifications' => false
];

// تطبيق الإعدادات الافتراضية إذا لم تكن موجودة
foreach ($defaultSystemSettings as $key => $value) {
    if (getSetting($key) === null) {
        $type = is_bool($value) ? 'boolean' : (is_numeric($value) ? 'number' : 'text');
        setSetting($key, $value, $type);
    }
}

?>
