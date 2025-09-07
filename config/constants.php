<?php
/**
 * ==============================================
 * تدبير بلس - الثوابت والإعدادات العامة
 * Tadbeer Plus - Constants and General Settings
 * ==============================================
 */

// منع الوصول المباشر
defined('TADBEER_ACCESS') || define('TADBEER_ACCESS', true);

// ==============================================
// معلومات النظام الأساسية
// ==============================================
define('APP_NAME', 'تدبير بلس');
define('APP_NAME_EN', 'Tadbeer Plus');
define('APP_VERSION', '1.0.0');
define('APP_AUTHOR', 'Tadbeer Plus Development Team');
define('APP_URL', 'https://tadbeerplus.ae');
define('APP_EMAIL', 'info@tadbeerplus.ae');
define('APP_PHONE', '+971-4-1234567');

// ==============================================
// إعدادات الخادم والمسارات
// ==============================================
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('LOGS_PATH', ROOT_PATH . '/logs');
define('BACKUP_PATH', ROOT_PATH . '/backup');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('LANGUAGES_PATH', ROOT_PATH . '/languages');

// URLs الأساسية
define('BASE_URL', 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . '/');
define('ADMIN_URL', BASE_URL . 'admin/');
define('CLIENT_URL', BASE_URL . 'client/');
define('API_URL', BASE_URL . 'api/');
define('ASSETS_URL', BASE_URL . 'assets/');
define('UPLOADS_URL', BASE_URL . 'uploads/');

// ==============================================
// إعدادات قاعدة البيانات
// ==============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'tadbeer_plus');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('DB_PREFIX', '');

// ==============================================
// إعدادات الجلسات والأمان
// ==============================================
define('SESSION_NAME', 'tadbeer_plus_session');
define('SESSION_LIFETIME', 3600); // ساعة واحدة
define('REMEMBER_ME_LIFETIME', 2592000); // 30 يوم
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 دقيقة
define('CSRF_TOKEN_NAME', '_token');

// مفاتيح التشفير
define('ENCRYPTION_KEY', 'your-secret-encryption-key-here');
define('JWT_SECRET', 'your-jwt-secret-key-here');
define('HASH_ALGORITHM', 'sha256');

// ==============================================
// إعدادات اللغة والمنطقة الزمنية
// ==============================================
define('DEFAULT_LANGUAGE', 'ar');
define('SUPPORTED_LANGUAGES', ['ar', 'en', 'ur', 'hi']);
define('DEFAULT_TIMEZONE', 'Asia/Dubai');
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'd/m/Y');
define('DISPLAY_DATETIME_FORMAT', 'd/m/Y H:i');

// ==============================================
// إعدادات الملفات والرفع
// ==============================================
define('MAX_FILE_SIZE', 32 * 1024 * 1024); // 32 ميجابايت
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt']);
define('ALLOWED_UPLOAD_TYPES', array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_DOCUMENT_TYPES));

// مجلدات الرفع
define('WORKERS_PHOTOS_DIR', 'uploads/photos/workers/');
define('CLIENTS_PHOTOS_DIR', 'uploads/photos/clients/');
define('WORKERS_DOCUMENTS_DIR', 'uploads/documents/workers/');
define('CLIENTS_DOCUMENTS_DIR', 'uploads/documents/clients/');
define('CONTRACTS_DIR', 'uploads/contracts/');
define('MEDICAL_CERTIFICATES_DIR', 'uploads/certificates/medical/');

// ==============================================
// إعدادات البريد الإلكتروني
// ==============================================
define('MAIL_FROM_NAME', 'تدبير بلس');
define('MAIL_FROM_EMAIL', 'noreply@tadbeerplus.ae');
define('MAIL_REPLY_TO', 'support@tadbeerplus.ae');
define('MAIL_SMTP_HOST', 'smtp.gmail.com');
define('MAIL_SMTP_PORT', 587);
define('MAIL_SMTP_USERNAME', '');
define('MAIL_SMTP_PASSWORD', '');
define('MAIL_SMTP_ENCRYPTION', 'tls');

// ==============================================
// إعدادات العقود والأعمال
// ==============================================

// أنواع العقود
define('CONTRACT_TYPES', [
    'عقد عمل منزلي' => 'General Domestic Work',
    'عقد رعاية مسنين' => 'Elderly Care',
    'عقد طبخ' => 'Cooking',
    'عقد تنظيف' => 'Cleaning',
    'عقد قيادة' => 'Driving',
    'عقد حراسة' => 'Security'
]);

// حالات العمالة
define('WORKER_STATUSES', [
    'متاح' => 'Available',
    'مكفول' => 'Sponsored',
    'مرفوض' => 'Rejected',
    'معلق' => 'Suspended',
    'في العرض' => 'In Offer',
    'تحت المراجعة' => 'Under Review'
]);

// حالات العملاء
define('CLIENT_STATUSES', [
    'نشط' => 'Active',
    'موقوف' => 'Suspended',
    'محظور' => 'Blocked'
]);

// حالات العقود
define('CONTRACT_STATUSES', [
    'مسودة' => 'Draft',
    'نشط' => 'Active',
    'منتهي' => 'Expired',
    'ملغي' => 'Cancelled',
    'متوقف' => 'Suspended',
    'مجمد' => 'Frozen'
]);

// ==============================================
// إعدادات المالية والدفع
// ==============================================
define('DEFAULT_CURRENCY', 'AED');
define('SUPPORTED_CURRENCIES', ['AED', 'USD', 'EUR', 'SAR']);
define('VAT_RATE', 0.05); // 5% ضريبة القيمة المضافة
define('DEFAULT_CONTRACT_FEE', 3000); // رسوم العقد الافتراضية
define('DEFAULT_INSURANCE_AMOUNT', 2000); // التأمين الافتراضي

// طرق الدفع
define('PAYMENT_METHODS', [
    'نقدي' => 'Cash',
    'بنكي' => 'Bank Transfer',
    'شيك' => 'Cheque',
    'بطاقة ائتمان' => 'Credit Card',
    'تحويل' => 'Wire Transfer',
    'محفظة إلكترونية' => 'E-Wallet'
]);

// ==============================================
// إعدادات النظام والأداء
// ==============================================
define('PAGINATION_LIMIT', 25);
define('SEARCH_MIN_LENGTH', 3);
define('CACHE_LIFETIME', 3600); // ساعة واحدة
define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10 ميجابايت
define('BACKUP_RETENTION_DAYS', 30);

// ==============================================
// رسائل النظام
// ==============================================
define('SUCCESS_MESSAGES', [
    'LOGIN_SUCCESS' => 'تم تسجيل الدخول بنجاح',
    'LOGOUT_SUCCESS' => 'تم تسجيل الخروج بنجاح',
    'SAVE_SUCCESS' => 'تم الحفظ بنجاح',
    'UPDATE_SUCCESS' => 'تم التحديث بنجاح',
    'DELETE_SUCCESS' => 'تم الحذف بنجاح',
    'UPLOAD_SUCCESS' => 'تم رفع الملف بنجاح'
]);

define('ERROR_MESSAGES', [
    'LOGIN_FAILED' => 'فشل في تسجيل الدخول',
    'ACCESS_DENIED' => 'غير مصرح لك بالوصول',
    'INVALID_DATA' => 'البيانات المدخلة غير صحيحة',
    'FILE_TOO_LARGE' => 'حجم الملف كبير جداً',
    'INVALID_FILE_TYPE' => 'نوع الملف غير مدعوم',
    'DATABASE_ERROR' => 'خطأ في قاعدة البيانات',
    'NETWORK_ERROR' => 'خطأ في الاتصال'
]);

// ==============================================
// الجنسيات المدعومة
// ==============================================
define('SUPPORTED_NATIONALITIES', [
    'إماراتي' => 'Emirati',
    'فلبيني' => 'Filipino',
    'إندونيسي' => 'Indonesian',
    'هندي' => 'Indian',
    'سريلانكي' => 'Sri Lankan',
    'بنغلاديشي' => 'Bangladeshi',
    'نيبالي' => 'Nepalese',
    'إثيوبي' => 'Ethiopian',
    'كيني' => 'Kenyan',
    'أوغندي' => 'Ugandan',
    'مصري' => 'Egyptian',
    'سوداني' => 'Sudanese',
    'أردني' => 'Jordanian',
    'فلسطيني' => 'Palestinian',
    'لبناني' => 'Lebanese',
    'سوري' => 'Syrian'
]);

// ==============================================
// إمارات دولة الإمارات
// ==============================================
define('UAE_EMIRATES', [
    'أبوظبي' => 'Abu Dhabi',
    'دبي' => 'Dubai',
    'الشارقة' => 'Sharjah',
    'عجمان' => 'Ajman',
    'أم القيوين' => 'Umm Al Quwain',
    'رأس الخيمة' => 'Ras Al Khaimah',
    'الفجيرة' => 'Fujairah'
]);

// ==============================================
// مستويات السجلات
// ==============================================
define('LOG_LEVELS', [
    'DEBUG' => 0,
    'INFO' => 1,
    'WARNING' => 2,
    'ERROR' => 3,
    'FATAL' => 4
]);

// ==============================================
// إعدادات التطوير والإنتاج
// ==============================================
define('APP_ENV', 'development'); // development, staging, production
define('DEBUG_MODE', APP_ENV === 'development');
define('DISPLAY_ERRORS', DEBUG_MODE);
define('LOG_ERRORS', true);

// ==============================================
// API إعدادات
// ==============================================
define('API_VERSION', 'v1');
define('API_RATE_LIMIT', 1000); // طلب في الساعة
define('API_TOKEN_EXPIRY', 3600); // ساعة واحدة
define('API_REFRESH_TOKEN_EXPIRY', 2592000); // 30 يوم

// ==============================================
// إعدادات متقدمة
// ==============================================

// وضع الصيانة
define('MAINTENANCE_MODE', false);
define('MAINTENANCE_MESSAGE', 'النظام تحت الصيانة، يرجى المحاولة لاحقاً');

// إعدادات التخزين المؤقت
define('CACHE_ENABLED', true);
define('CACHE_DRIVER', 'file'); // file, redis, memcached

// إعدادات الأمان المتقدمة
define('ENABLE_2FA', false);
define('FORCE_HTTPS', false);
define('SECURE_COOKIES', false);
define('CSRF_PROTECTION', true);

// ==============================================
// تعيين المنطقة الزمنية
// ==============================================
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set(DEFAULT_TIMEZONE);
}

// ==============================================
// تعيين الترميز
// ==============================================
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

// ==============================================
// تعيين اللغة المحلية
// ==============================================
if (function_exists('setlocale')) {
    setlocale(LC_ALL, 'ar_AE.UTF-8', 'en_US.UTF-8');
}

// ==============================================
// نهاية الملف
// ==============================================
?>
