<?php
/**
 * ==============================================
 * تدبير بلس - نظام اللغات والترجمة
 * Tadbeer Plus - Internationalization System
 * ==============================================
 */

// منع الوصول المباشر
if (!defined('TADBEER_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * فئة إدارة نظام اللغات والترجمة
 * Internationalization Management Class
 */
class i18n {
    
    private static $instance = null;
    private $currentLanguage;
    private $defaultLanguage;
    private $translations = [];
    private $availableLanguages = [];
    private $rtlLanguages = ['ar', 'ur', 'he', 'fa'];
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->defaultLanguage = DEFAULT_LANGUAGE;
        $this->loadAvailableLanguages();
        $this->setLanguage($this->detectLanguage());
        $this->loadTranslations();
    }
    
    /**
     * الحصول على مثيل نظام اللغات
     * Get i18n Instance (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * تحميل اللغات المتاحة من قاعدة البيانات
     * Load Available Languages from Database
     */
    private function loadAvailableLanguages() {
        try {
            $db = getDB();
            $sql = "SELECT code, name, native_name, direction, is_active FROM languages WHERE is_active = 1 ORDER BY sort_order";
            $languages = $db->select($sql);
            
            foreach ($languages as $lang) {
                $this->availableLanguages[$lang['code']] = [
                    'name' => $lang['name'],
                    'native_name' => $lang['native_name'],
                    'direction' => $lang['direction'],
                    'is_active' => $lang['is_active']
                ];
            }
        } catch (Exception $e) {
            // في حالة فشل تحميل اللغات من قاعدة البيانات، استخدم اللغات الافتراضية
            $this->availableLanguages = [
                'ar' => ['name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl', 'is_active' => 1],
                'en' => ['name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'is_active' => 1]
            ];
        }
    }
    
    /**
     * كشف اللغة المطلوبة
     * Detect Required Language
     */
    private function detectLanguage() {
        // 1. التحقق من معامل URL
        if (isset($_GET['lang']) && $this->isLanguageAvailable($_GET['lang'])) {
            $_SESSION['language'] = $_GET['lang'];
            return $_GET['lang'];
        }
        
        // 2. التحقق من الجلسة
        if (isset($_SESSION['language']) && $this->isLanguageAvailable($_SESSION['language'])) {
            return $_SESSION['language'];
        }
        
        // 3. التحقق من إعدادات المستخدم
        if (isset($_SESSION['user_id'])) {
            $userLang = $this->getUserLanguagePreference($_SESSION['user_id']);
            if ($userLang && $this->isLanguageAvailable($userLang)) {
                return $userLang;
            }
        }
        
        // 4. التحقق من إعدادات المتصفح
        $browserLang = $this->detectBrowserLanguage();
        if ($browserLang && $this->isLanguageAvailable($browserLang)) {
            return $browserLang;
        }
        
        // 5. استخدام اللغة الافتراضية
        return $this->defaultLanguage;
    }
    
    /**
     * كشف لغة المتصفح
     * Detect Browser Language
     */
    private function detectBrowserLanguage() {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }
        
        $languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        
        foreach ($languages as $lang) {
            $lang = trim(substr($lang, 0, 2));
            if ($this->isLanguageAvailable($lang)) {
                return $lang;
            }
        }
        
        return null;
    }
    
    /**
     * الحصول على تفضيل لغة المستخدم
     * Get User Language Preference
     */
    private function getUserLanguagePreference($userId) {
        try {
            $db = getDB();
            $sql = "SELECT preferred_language FROM users WHERE id = :user_id";
            $result = $db->selectOne($sql, ['user_id' => $userId]);
            
            return $result ? $result['preferred_language'] : null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * التحقق من توفر اللغة
     * Check if Language is Available
     */
    private function isLanguageAvailable($langCode) {
        return isset($this->availableLanguages[$langCode]) && $this->availableLanguages[$langCode]['is_active'];
    }
    
    /**
     * تعيين اللغة الحالية
     * Set Current Language
     */
    public function setLanguage($langCode) {
        if ($this->isLanguageAvailable($langCode)) {
            $this->currentLanguage = $langCode;
            $_SESSION['language'] = $langCode;
            
            // تعيين اتجاه النص
            define('TEXT_DIRECTION', $this->isRtlLanguage($langCode) ? 'rtl' : 'ltr');
            define('CURRENT_LANGUAGE', $langCode);
            
            $this->loadTranslations();
            return true;
        }
        return false;
    }
    
    /**
     * تحميل ترجمات اللغة الحالية
     * Load Current Language Translations
     */
    private function loadTranslations() {
        $this->translations = [];
        
        // ملفات الترجمة المطلوبة
        $translationFiles = [
            'translations.php',
            'messages.php',
            'validation.php'
        ];
        
        foreach ($translationFiles as $file) {
            $filePath = LANGUAGES_PATH . '/' . $this->currentLanguage . '/' . $file;
            
            if (file_exists($filePath)) {
                $translations = include $filePath;
                if (is_array($translations)) {
                    $this->translations = array_merge($this->translations, $translations);
                }
            }
        }
    }
    
    /**
     * الحصول على ترجمة نص
     * Get Text Translation
     */
    public function translate($key, $params = []) {
        // البحث عن الترجمة
        $translation = $this->translations[$key] ?? $key;
        
        // استبدال المعاملات
        if (!empty($params)) {
            foreach ($params as $param => $value) {
                $translation = str_replace('{' . $param . '}', $value, $translation);
            }
        }
        
        return $translation;
    }
    
    /**
     * الحصول على اللغة الحالية
     * Get Current Language
     */
    public function getCurrentLanguage() {
        return $this->currentLanguage;
    }
    
    /**
     * الحصول على اللغات المتاحة
     * Get Available Languages
     */
    public function getAvailableLanguages() {
        return $this->availableLanguages;
    }
    
    /**
     * التحقق من كون اللغة من اليمين لليسار
     * Check if Language is RTL
     */
    public function isRtlLanguage($langCode = null) {
        $langCode = $langCode ?: $this->currentLanguage;
        return in_array($langCode, $this->rtlLanguages);
    }
    
    /**
     * الحصول على اتجاه النص
     * Get Text Direction
     */
    public function getTextDirection($langCode = null) {
        return $this->isRtlLanguage($langCode) ? 'rtl' : 'ltr';
    }
    
    /**
     * تنسيق التاريخ حسب اللغة
     * Format Date by Language
     */
    public function formatDate($date, $format = null) {
        if (!$format) {
            $format = $this->currentLanguage === 'ar' ? 'Y/m/d' : 'd/m/Y';
        }
        
        if (is_string($date)) {
            $date = strtotime($date);
        }
        
        $formattedDate = date($format, $date);
        
        // تحويل الأرقام للعربية إذا كانت اللغة عربية
        if ($this->currentLanguage === 'ar') {
            $formattedDate = $this->convertNumbersToArabic($formattedDate);
        }
        
        return $formattedDate;
    }
    
    /**
     * تنسيق الأرقام حسب اللغة
     * Format Numbers by Language
     */
    public function formatNumber($number, $decimals = 2) {
        $formattedNumber = number_format($number, $decimals, '.', ',');
        
        // تحويل للأرقام العربية إذا كانت اللغة عربية
        if ($this->currentLanguage === 'ar') {
            $formattedNumber = $this->convertNumbersToArabic($formattedNumber);
        }
        
        return $formattedNumber;
    }
    
    /**
     * تحويل الأرقام الإنجليزية للعربية
     * Convert English Numbers to Arabic
     */
    private function convertNumbersToArabic($string) {
        $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        
        return str_replace($englishNumbers, $arabicNumbers, $string);
    }
    
    /**
     * الحصول على النص المترجم مع التحقق من الجنس
     * Get Translated Text with Gender Check
     */
    public function translateGender($key, $gender = 'male', $params = []) {
        $genderKey = $key . '_' . $gender;
        
        // التحقق من وجود ترجمة مخصصة للجنس
        if (isset($this->translations[$genderKey])) {
            return $this->translate($genderKey, $params);
        }
        
        // استخدام الترجمة العامة
        return $this->translate($key, $params);
    }
    
    /**
     * تحديث إعدادات لغة المستخدم
     * Update User Language Settings
     */
    public function updateUserLanguagePreference($userId, $langCode) {
        if (!$this->isLanguageAvailable($langCode)) {
            return false;
        }
        
        try {
            $db = getDB();
            $sql = "UPDATE users SET preferred_language = :lang WHERE id = :user_id";
            $result = $db->update($sql, [
                'lang' => $langCode,
                'user_id' => $userId
            ]);
            
            return $result > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * إنشاء رابط مع تغيير اللغة
     * Generate Link with Language Change
     */
    public function getLanguageUrl($langCode, $currentUrl = null) {
        if (!$currentUrl) {
            $currentUrl = $_SERVER['REQUEST_URI'];
        }
        
        // إزالة معامل اللغة الحالي إن وجد
        $currentUrl = preg_replace('/[?&]lang=[^&]*/', '', $currentUrl);
        
        // إضافة معامل اللغة الجديد
        $separator = strpos($currentUrl, '?') !== false ? '&' : '?';
        
        return $currentUrl . $separator . 'lang=' . $langCode;
    }
    
    /**
     * الحصول على معلومات اللغة
     * Get Language Information
     */
    public function getLanguageInfo($langCode = null) {
        $langCode = $langCode ?: $this->currentLanguage;
        return $this->availableLanguages[$langCode] ?? null;
    }
}

/**
 * ==============================================
 * الدوال المساعدة للترجمة
 * Translation Helper Functions
 * ==============================================
 */

/**
 * الحصول على مثيل نظام اللغات
 * Get i18n Instance
 */
function getI18n() {
    return i18n::getInstance();
}

/**
 * ترجمة نص
 * Translate Text
 */
function __($key, $params = []) {
    return getI18n()->translate($key, $params);
}

/**
 * ترجمة نص مع الطباعة
 * Translate and Echo Text
 */
function _e($key, $params = []) {
    echo __($key, $params);
}

/**
 * ترجمة مع تحديد الجنس
 * Translate with Gender
 */
function __gender($key, $gender = 'male', $params = []) {
    return getI18n()->translateGender($key, $gender, $params);
}

/**
 * الحصول على اللغة الحالية
 * Get Current Language
 */
function getCurrentLanguage() {
    return getI18n()->getCurrentLanguage();
}

/**
 * الحصول على اللغات المتاحة
 * Get Available Languages
 */
function getAvailableLanguages() {
    return getI18n()->getAvailableLanguages();
}

/**
 * التحقق من كون اللغة RTL
 * Check if Current Language is RTL
 */
function isRtlLanguage() {
    return getI18n()->isRtlLanguage();
}

/**
 * الحصول على اتجاه النص
 * Get Text Direction
 */
function getTextDirection() {
    return getI18n()->getTextDirection();
}

/**
 * تنسيق التاريخ حسب اللغة
 * Format Date by Language
 */
function formatLocalDate($date, $format = null) {
    return getI18n()->formatDate($date, $format);
}

/**
 * تنسيق الأرقام حسب اللغة
 * Format Numbers by Language
 */
function formatLocalNumber($number, $decimals = 2) {
    return getI18n()->formatNumber($number, $decimals);
}

/**
 * إنشاء رابط تغيير اللغة
 * Generate Language Switch URL
 */
function getLanguageUrl($langCode) {
    return getI18n()->getLanguageUrl($langCode);
}

/**
 * تعيين اللغة
 * Set Language
 */
function setLanguage($langCode) {
    return getI18n()->setLanguage($langCode);
}

// تهيئة نظام اللغات
$i18n = getI18n();

?>
