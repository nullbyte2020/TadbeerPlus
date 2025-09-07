<?php
/**
 * ==============================================
 * توجيه لوحات التحكم - نظام تدبير بلس
 * Dashboard Redirect - Tadbeer Plus System
 * ==============================================
 */

session_start();

// تضمين ملفات الإعدادات
require_once 'config/constants.php';
require_once 'config/db.php';
require_once 'config/permissions.php';
require_once 'config/functions.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// الحصول على معلومات المستخدم
$userType = $_SESSION['user_type'] ?? 'staff';
$roleName = $_SESSION['role_name'] ?? '';

// تحديد الوجهة بناءً على نوع المستخدم والدور
$dashboardUrl = getDashboardUrl($userType, $roleName);

/**
 * تحديد رابط لوحة التحكم المناسبة
 * Determine Appropriate Dashboard URL
 */
function getDashboardUrl($userType, $roleName) {
    
    // العملاء
    if ($userType === 'client') {
        return 'client/dashboard.php';
    }
    
    // الموظفين حسب الأدوار
    switch ($roleName) {
        case 'super_admin':
        case 'admin':
            return 'admin/dashboard.php';
            
        case 'financial_manager':
            return 'financial/dashboard.php';
            
        case 'sales_manager':
        case 'sales_rep':
            return 'sales/dashboard.php';
            
        case 'customer_service':
            return 'customer_service/dashboard.php';
            
        case 'data_entry':
            return 'data_entry/dashboard.php';
            
        case 'supervisor':
            return 'supervisor/dashboard.php';
            
        default:
            // الدور الافتراضي للموظفين
            return 'admin/dashboard.php';
    }
}

// التحقق من وجود ملف لوحة التحكم
if (!file_exists($dashboardUrl)) {
    // إذا لم يكن الملف موجود، توجيه للوحة التحكم الافتراضية
    $dashboardUrl = 'admin/dashboard.php';
    
    // إذا كان عميل ولا توجد لوحة تحكم العملاء، عرض رسالة خطأ
    if ($userType === 'client' && !file_exists($dashboardUrl)) {
        die('خطأ: لوحة تحكم العملاء غير متاحة حالياً. يرجى التواصل مع الدعم الفني.');
    }
}

// تسجيل الوصول للوحة التحكم
logActivity('dashboard_access', "Accessed dashboard: {$dashboardUrl}");

// إعادة التوجيه
header('Location: ' . $dashboardUrl);
exit;
?>
