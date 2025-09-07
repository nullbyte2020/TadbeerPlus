<?php
/**
 * ==============================================
 * صفحة تسجيل الخروج - نظام تدبير بلس
 * Logout Page - Tadbeer Plus System
 * ==============================================
 */

session_start();

// تضمين ملفات الإعدادات
require_once 'config/constants.php';
require_once 'config/db.php';
require_once 'config/functions.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// تسجيل النشاط قبل تسجيل الخروج
if (isset($_SESSION['user_id'])) {
    logActivity('logout', 'User logged out');
}

// حذف رمز "تذكرني" من قاعدة البيانات
if (isset($_COOKIE['remember_token']) && isset($_SESSION['user_id'])) {
    $db = getDB();
    $sql = "UPDATE users SET remember_token = NULL, remember_expires = NULL WHERE id = :user_id";
    $db->update($sql, ['user_id' => $_SESSION['user_id']]);
    
    // حذف الكوكي
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

// تدمير الجلسة
session_unset();
session_destroy();

// إعادة بدء الجلسة لإضافة رسالة النجاح
session_start();
$_SESSION['logout_success'] = true;

// إعادة توجيه لصفحة تسجيل الدخول
header('Location: login.php?logged_out=1');
exit;
?>
