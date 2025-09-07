<?php
/**
 * ==============================================
 * صفحة تأكيد البريد الإلكتروني - نظام تدبير بلس
 * Email Verification Page - Tadbeer Plus System
 * ==============================================
 */

session_start();

// تضمين ملفات الإعدادات
require_once 'config/constants.php';
require_once 'config/db.php';
require_once 'config/i18n.php';
require_once 'config/functions.php';

// الحصول على الرمز من الرابط
$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

$verification_result = null;
$error_message = '';
$success_message = '';

if (!empty($token)) {
    $verification_result = verifyEmailToken($token);
    
    if ($verification_result['success']) {
        $success_message = $verification_result['message'];
    } else {
        $error_message = $verification_result['message'];
    }
}

/**
 * التحقق من رمز تأكيد البريد الإلكتروني
 * Verify Email Token
 */
function verifyEmailToken($token) {
    $db = getDB();
    
    try {
        // البحث عن المستخدم بالرمز
        $sql = "SELECT id, username, full_name, email, email_verified 
                FROM users 
                WHERE email_verification_token = :token";
        
        $user = $db->selectOne($sql, ['token' => $token]);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => __('invalid_verification_token')
            ];
        }
        
        // التحقق من حالة التحقق
        if ($user['email_verified']) {
            return [
                'success' => true,
                'message' => __('email_already_verified'),
                'user' => $user
            ];
        }
        
        // تفعيل الحساب
        $db->beginTransaction();
        
        // تحديث حالة المستخدم
        $sql = "UPDATE users 
                SET email_verified = 1, 
                    is_active = 1,
                    email_verification_token = NULL 
                WHERE id = :user_id";
        
        $updated = $db->update($sql, ['user_id' => $user['id']]);
        
        if ($updated) {
            // تسجيل النشاط
            logActivity('email_verified', 'Email verified successfully', $user['id']);
            
            $db->commit();
            
            return [
                'success' => true,
                'message' => __('email_verified_successfully'),
                'user' => $user
            ];
        } else {
            $db->rollback();
            return [
                'success' => false,
                'message' => __('error_database')
            ];
        }
        
    } catch (Exception $e) {
        if (isset($db)) $db->rollback();
        error_log("Email verification error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => __('error_server')
        ];
    }
}

/**
 * إعادة إرسال رمز التحقق
 * Resend Verification Token
 */
function resendVerificationEmail($email) {
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'message' => __('error_invalid_email')
        ];
    }
    
    $db = getDB();
    
    try {
        // البحث عن المستخدم
        $sql = "SELECT id, full_name, email, email_verified 
                FROM users 
                WHERE email = :email AND is_active = 0";
        
        $user = $db->selectOne($sql, ['email' => $email]);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => __('email_not_found_or_verified')
            ];
        }
        
        if ($user['email_verified']) {
            return [
                'success' => false,
                'message' => __('email_already_verified')
            ];
        }
        
        // إنشاء رمز جديد
        $newToken = bin2hex(random_bytes(32));
        
        // تحديث الرمز في قاعدة البيانات
        $sql = "UPDATE users 
                SET email_verification_token = :token 
                WHERE id = :user_id";
        
        $updated = $db->update($sql, [
            'token' => $newToken,
            'user_id' => $user['id']
        ]);
        
        if ($updated) {
            // إرسال البريد الإلكتروني
            $emailSent = sendVerificationEmail($user['email'], $user['full_name'], $newToken);
            
            if ($emailSent) {
                logActivity('verification_email_resent', 'Verification email resent', $user['id']);
                
                return [
                    'success' => true,
                    'message' => __('verification_email_resent')
                ];
            } else {
                return [
                    'success' => false,
                    'message' => __('error_send_email')
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => __('error_database')
        ];
        
    } catch (Exception $e) {
        error_log("Resend verification error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => __('error_server')
        ];
    }
}

// معالجة طلب إعادة الإرسال
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_email'])) {
    $resend_email = filterInput($_POST['resend_email'], 'email');
    $resend_result = resendVerificationEmail($resend_email);
    
    if ($resend_result['success']) {
        $success_message = $resend_result['message'];
    } else {
        $error_message =
