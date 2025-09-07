<?php
/**
 * ==============================================
 * نظام تدبير بلس - الدوال العامة
 * Tadbeer Plus System - General Functions
 * ==============================================
 */

// منع الوصول المباشر
if (!defined('BASE_PATH')) {
    die('Direct access not permitted');
}

/**
 * ==============================================
 * دوال المساعدة العامة
 * General Helper Functions
 * ==============================================
 */

/**
 * تنظيف وتأمين النصوص
 * Clean and Secure Text
 */
function cleanText($text, $allowHtml = false) {
    if ($allowHtml) {
        return htmlspecialchars_decode(strip_tags($text, '<b><i><u><br><p><div><span>'));
    }
    return htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');
}

/**
 * تنظيف المدخلات
 * Sanitize Input
 */
function sanitizeInput($input, $type = 'string') {
    if (is_array($input)) {
        return array_map(function($item) use ($type) {
            return sanitizeInput($item, $type);
        }, $input);
    }
    
    switch ($type) {
        case 'int':
            return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return (float) filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);
        case 'string':
        default:
            return cleanText($input);
    }
}

/**
 * إنشاء معرف فريد
 * Generate Unique ID
 */
function generateUniqueId($prefix = '', $length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $prefix . date('Ymd') . $randomString;
}

/**
 * تحويل الحجم بالبايت إلى وحدة قابلة للقراءة
 * Convert Bytes to Readable Size
 */
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * إنشاء كلمة مرور عشوائية
 * Generate Random Password
 */
function generateRandomPassword($length = 12) {
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numbers = '0123456789';
    $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
    
    $password = '';
    $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
    $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
    $password .= $numbers[rand(0, strlen($numbers) - 1)];
    $password .= $symbols[rand(0, strlen($symbols) - 1)];
    
    $allChars = $lowercase . $uppercase . $numbers . $symbols;
    for ($i = 4; $i < $length; $i++) {
        $password .= $allChars[rand(0, strlen($allChars) - 1)];
    }
    
    return str_shuffle($password);
}

/**
 * ==============================================
 * دوال التاريخ والوقت
 * Date and Time Functions
 * ==============================================
 */

/**
 * تنسيق التاريخ حسب اللغة
 * Format Date by Language
 */
function formatDate($date, $format = null, $language = null) {
    if (!$date) return '';
    
    if (!$language) {
        $language = getCurrentLanguage();
    }
    
    if (!$format) {
        $format = $language === 'ar' ? 'Y/m/d' : 'd/m/Y';
    }
    
    if (is_string($date)) {
        $timestamp = strtotime($date);
    } else {
        $timestamp = $date;
    }
    
    $formattedDate = date($format, $timestamp);
    
    // تحويل للأرقام العربية إذا كانت اللغة عربية
    if ($language === 'ar') {
        $formattedDate = convertNumbersToArabic($formattedDate);
    }
    
    return $formattedDate;
}

/**
 * تنسيق التاريخ والوقت
 * Format DateTime
 */
function formatDateTime($datetime, $language = null) {
    if (!$datetime) return '';
    
    if (!$language) {
        $language = getCurrentLanguage();
    }
    
    $date = formatDate($datetime, null, $language);
    $time = date('H:i', strtotime($datetime));
    
    if ($language === 'ar') {
        $time = convertNumbersToArabic($time);
        return $date . ' ' . $time;
    }
    
    return $date . ' ' . $time;
}

/**
 * حساب العمر
 * Calculate Age
 */
function calculateAge($birthDate) {
    if (!$birthDate) return 0;
    
    $today = new DateTime();
    $birth = new DateTime($birthDate);
    
    return $today->diff($birth)->y;
}

/**
 * التحقق من انتهاء التاريخ
 * Check if Date is Expired
 */
function isExpired($date, $days = 0) {
    if (!$date) return true;
    
    $expiry = new DateTime($date);
    $now = new DateTime();
    
    if ($days > 0) {
        $expiry->sub(new DateInterval("P{$days}D"));
    }
    
    return $now > $expiry;
}

/**
 * حساب الأيام المتبقية
 * Calculate Remaining Days
 */
function daysRemaining($date) {
    if (!$date) return 0;
    
    $target = new DateTime($date);
    $now = new DateTime();
    
    $diff = $now->diff($target);
    
    if ($target < $now) {
        return -$diff->days;
    }
    
    return $diff->days;
}

/**
 * ==============================================
 * دوال التنسيق والعرض
 * Formatting and Display Functions
 * ==============================================
 */

/**
 * تنسيق الأرقام
 * Format Numbers
 */
function formatNumber($number, $decimals = 2, $language = null) {
    if (!$language) {
        $language = getCurrentLanguage();
    }
    
    $formatted = number_format($number, $decimals, '.', ',');
    
    if ($language === 'ar') {
        return convertNumbersToArabic($formatted);
    }
    
    return $formatted;
}

/**
 * تنسيق العملة
 * Format Currency
 */
function formatCurrency($amount, $currency = 'AED', $language = null) {
    if (!$language) {
        $language = getCurrentLanguage();
    }
    
    $formatted = formatNumber($amount, 2, $language);
    
    if ($language === 'ar') {
        return $formatted . ' ' . $currency;
    }
    
    return $currency . ' ' . $formatted;
}

/**
 * تحويل الأرقام الإنجليزية للعربية
 * Convert English Numbers to Arabic
 */
function convertNumbersToArabic($string) {
    $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    
    return str_replace($englishNumbers, $arabicNumbers, $string);
}

/**
 * اختصار النص
 * Truncate Text
 */
function truncateText($text, $length = 100, $append = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length) . $append;
}

/**
 * ==============================================
 * دوال الملفات والمجلدات
 * File and Directory Functions
 * ==============================================
 */

/**
 * إنشاء مجلد إذا لم يكن موجوداً
 * Create Directory if Not Exists
 */
function createDirectory($path, $permissions = 0755) {
    if (!is_dir($path)) {
        return mkdir($path, $permissions, true);
    }
    return true;
}

/**
 * رفع ملف بأمان
 * Safe File Upload
 */
function uploadFile($file, $uploadDir, $allowedTypes = [], $maxSize = null) {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new Exception('Invalid file parameters');
    }
    
    // التحقق من أخطاء الرفع
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new Exception('No file sent');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new Exception('File size exceeded');
        default:
            throw new Exception('Unknown upload error');
    }
    
    // التحقق من حجم الملف
    if ($maxSize && $file['size'] > $maxSize) {
        throw new Exception('File size too large');
    }
    
    // التحقق من نوع الملف
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if ($allowedTypes && !in_array($mimeType, $allowedTypes)) {
        throw new Exception('File type not allowed');
    }
    
    // إنشاء المجلد إذا لم يكن موجوداً
    createDirectory($uploadDir);
    
    // إنشاء اسم ملف فريد
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = generateUniqueId('file_') . '.' . $extension;
    $filepath = $uploadDir . '/' . $filename;
    
    // نقل الملف
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    return [
        'filename' => $filename,
        'filepath' => $filepath,
        'size' => $file['size'],
        'mime_type' => $mimeType
    ];
}

/**
 * حذف ملف بأمان
 * Safe File Delete
 */
function deleteFile($filepath) {
    if (file_exists($filepath) && is_file($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * ==============================================
 * دوال الأمان
 * Security Functions
 * ==============================================
 */

/**
 * تشفير كلمة المرور
 * Hash Password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * التحقق من كلمة المرور
 * Verify Password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * إنشاء رمز مميز آمن
 * Generate Secure Token
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * تنظيف اسم الملف
 * Sanitize Filename
 */
function sanitizeFilename($filename) {
    // إزالة الأحرف الخطرة
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    
    // منع أسماء الملفات الخطرة
    $dangerous = ['.htaccess', '.htpasswd', '.php', '.phtml', '.php3', '.php4', '.php5'];
    foreach ($dangerous as $danger) {
        $filename = str_ireplace($danger, '_' . substr($danger, 1), $filename);
    }
    
    return $filename;
}

/**
 * ==============================================
 * دوال التنبيهات والرسائل
 * Notifications and Messages Functions
 * ==============================================
 */

/**
 * عرض رسالة تنبيه
 * Show Alert Message
 */
function showAlert($message, $type = 'info', $dismissible = true) {
    $alertClass = 'alert alert-' . $type;
    if ($dismissible) {
        $alertClass .= ' alert-dismissible fade show';
    }
    
    $html = '<div class="' . $alertClass . '" role="alert">';
    $html .= htmlspecialchars($message);
    
    if ($dismissible) {
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * تعيين رسالة فلاش
 * Set Flash Message
 */
function setFlashMessage($message, $type = 'info') {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    
    $_SESSION['flash_messages'][] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * الحصول على رسائل الفلاش
 * Get Flash Messages
 */
function getFlashMessages($clear = true) {
    $messages = $_SESSION['flash_messages'] ?? [];
    
    if ($clear) {
        unset($_SESSION['flash_messages']);
    }
    
    return $messages;
}

/**
 * عرض رسائل الفلاش
 * Display Flash Messages
 */
function displayFlashMessages() {
    $messages = getFlashMessages();
    $html = '';
    
    foreach ($messages as $flash) {
        $html .= showAlert($flash['message'], $flash['type']);
    }
    
    return $html;
}

/**
 * ==============================================
 * دوال قاعدة البيانات المساعدة
 * Database Helper Functions
 * ==============================================
 */

/**
 * الحصول على صف واحد
 * Get Single Row
 */
function getSingleRow($table, $conditions = [], $columns = '*') {
    $db = getDB();
    
    $whereClause = '';
    $params = [];
    
    if (!empty($conditions)) {
        $whereConditions = [];
        foreach ($conditions as $key => $value) {
            $whereConditions[] = "$key = :$key";
            $params[$key] = $value;
        }
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }
    
    $sql = "SELECT $columns FROM $table $whereClause LIMIT 1";
    return $db->selectOne($sql, $params);
}

/**
 * إدراج أو تحديث
 * Insert or Update
 */
function insertOrUpdate($table, $data, $conditions = []) {
    $db = getDB();
    
    if (empty($conditions)) {
        // إدراج جديد
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        return $db->insert($sql, $data);
    } else {
        // تحقق من وجود السجل
        $exists = getSingleRow($table, $conditions, 'COUNT(*) as count');
        
        if ($exists && $exists['count'] > 0) {
            // تحديث
            $setParts = [];
            foreach ($data as $key => $value) {
                $setParts[] = "$key = :$key";
            }
            
            $whereConditions = [];
            foreach ($conditions as $key => $value) {
                $whereConditions[] = "$key = :where_$key";
                $data["where_$key"] = $value;
            }
            
            $sql = "UPDATE $table SET " . implode(', ', $setParts) . 
                   " WHERE " . implode(' AND ', $whereConditions);
            
            return $db->update($sql, $data);
        } else {
            // إدراج جديد
            $allData = array_merge($data, $conditions);
            $columns = implode(', ', array_keys($allData));
            $placeholders = ':' . implode(', :', array_keys($allData));
            $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
            return $db->insert($sql, $allData);
        }
    }
}

/**
 * ==============================================
 * دوال التحقق والتصديق
 * Validation Functions
 * ==============================================
 */

/**
 * التحقق من البريد الإلكتروني
 * Validate Email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * التحقق من رقم الهاتف الإماراتي
 * Validate UAE Phone Number
 */
function validateUAEPhone($phone) {
    $pattern = '/^(\+971|00971|971|0)?[0-9]{8,9}$/';
    return preg_match($pattern, $phone);
}

/**
 * التحقق من رقم الهوية الإماراتية
 * Validate UAE Emirates ID
 */
function validateEmiratesID($emiratesId) {
    // إزالة الفراغات والشرطات
    $emiratesId = preg_replace('/[\s-]/', '', $emiratesId);
    
    // يجب أن يكون 15 رقماً
    if (!preg_match('/^\d{15}$/', $emiratesId)) {
        return false;
    }
    
    // خوارزمية التحقق من صحة رقم الهوية الإماراتية
    $weights = [1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2];
    $sum = 0;
    
    for ($i = 0; $i < 14; $i++) {
        $digit = (int)$emiratesId[$i];
        $product = $digit * $weights[$i];
        
        if ($product > 9) {
            $product = floor($product / 10) + ($product % 10);
        }
        
        $sum += $product;
    }
    
    $checkDigit = (10 - ($sum % 10)) % 10;
    
    return $checkDigit == (int)$emiratesId[14];
}

/**
 * ==============================================
 * دوال مساعدة أخرى
 * Other Helper Functions
 * ==============================================
 */

/**
 * الحصول على عنوان IP للمستخدم
 * Get User IP Address
 */
function getUserIP() {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = explode(',', $ip)[0];
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * تسجيل النشاط
 * Log Activity
 */
function logActivity($action, $description, $userId = null, $relatedId = null, $relatedType = null) {
    try {
        $db = getDB();
        
        $sql = "INSERT INTO activity_logs (user_id, action, description, related_id, related_type, 
                ip_address, user_agent, created_at) 
                VALUES (:user_id, :action, :description, :related_id, :related_type, 
                :ip_address, :user_agent, NOW())";
        
        $params = [
            'user_id' => $userId ?: ($_SESSION['user_id'] ?? null),
            'action' => $action,
            'description' => $description,
            'related_id' => $relatedId,
            'related_type' => $relatedType,
            'ip_address' => getUserIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ];
        
        return $db->insert($sql, $params);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}

/**
 * إعادة توجيه آمن
 * Safe Redirect
 */
function safeRedirect($url, $statusCode = 302) {
    // التحقق من أن الرابط آمن
    if (!filter_var($url, FILTER_VALIDATE_URL) && !preg_match('/^\/[^\/]/', $url)) {
        $url = BASE_URL;
    }
    
    header("Location: $url", true, $statusCode);
    exit;
}

/**
 * تحديد نوع المتصفح
 * Detect Browser
 */
function getBrowserInfo() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $browsers = [
        'Chrome' => '/Chrome/i',
        'Firefox' => '/Firefox/i',
        'Safari' => '/Safari/i',
        'Edge' => '/Edge/i',
        'Internet Explorer' => '/MSIE/i'
    ];
    
    foreach ($browsers as $browser => $pattern) {
        if (preg_match($pattern, $userAgent)) {
            return $browser;
        }
    }
    
    return 'Unknown';
}

/**
 * تحويل المصفوفة إلى CSV
 * Array to CSV
 */
function arrayToCSV($array, $filename = 'export.csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // إضافة BOM للتعامل مع UTF-8
    fwrite($output, "\xEF\xBB\xBF");
    
    $firstRow = true;
    foreach ($array as $row) {
        if ($firstRow) {
            fputcsv($output, array_keys($row));
            $firstRow = false;
        }
        fputcsv($output, $row);
    }
    
    fclose($output);
}

/**
 * طباعة متغير للاختبار
 * Debug Print
 */
function debug($var, $exit = false) {
    if (DEBUG_MODE) {
        echo '<pre>';
        print_r($var);
        echo '</pre>';
        
        if ($exit) {
            exit;
        }
    }
}

?>
