<?php
/**
 * ==============================================
 * تدبير بلس - إعدادات البريد الإلكتروني
 * Tadbeer Plus - Email Configuration
 * ==============================================
 */

// منع الوصول المباشر
if (!defined('TADBEER_ACCESS')) {
    die('Direct access not permitted');
}

require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * فئة إدارة البريد الإلكتروني
 * Email Management Class
 */
class EmailManager {
    
    private static $instance = null;
    private $mailer;
    private $config;
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->initializeConfig();
        $this->setupMailer();
    }
    
    /**
     * الحصول على مثيل البريد الإلكتروني
     * Get Email Manager Instance (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * تهيئة إعدادات البريد الإلكتروني
     * Initialize Email Configuration
     */
    private function initializeConfig() {
        $this->config = [
            'host' => getSetting('smtp_host', MAIL_SMTP_HOST),
            'port' => getSetting('smtp_port', MAIL_SMTP_PORT),
            'username' => getSetting('smtp_username', MAIL_SMTP_USERNAME),
            'password' => getSetting('smtp_password', MAIL_SMTP_PASSWORD),
            'encryption' => getSetting('smtp_encryption', MAIL_SMTP_ENCRYPTION),
            'from_name' => getSetting('mail_from_name', MAIL_FROM_NAME),
            'from_email' => getSetting('mail_from_email', MAIL_FROM_EMAIL),
            'reply_to' => getSetting('mail_reply_to', MAIL_REPLY_TO),
            'charset' => 'UTF-8',
            'debug_level' => DEBUG_MODE ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF
        ];
    }
    
    /**
     * إعداد PHPMailer
     * Setup PHPMailer
     */
    private function setupMailer() {
        $this->mailer = new PHPMailer(true);
        
        try {
            // إعدادات الخادم
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['username'];
            $this->mailer->Password = $this->config['password'];
            $this->mailer->SMTPSecure = $this->config['encryption'];
            $this->mailer->Port = $this->config['port'];
            $this->mailer->CharSet = $this->config['charset'];
            $this->mailer->SMTPDebug = $this->config['debug_level'];
            
            // إعدادات المرسل
            $this->mailer->setFrom($this->config['from_email'], $this->config['from_name']);
            $this->mailer->addReplyTo($this->config['reply_to'], $this->config['from_name']);
            
            // إعدادات إضافية
            $this->mailer->isHTML(true);
            $this->mailer->WordWrap = 70;
            $this->mailer->Timeout = 30;
            
        } catch (Exception $e) {
            $this->logError("Failed to setup PHPMailer: " . $e->getMessage());
        }
    }
    
    /**
     * إرسال بريد إلكتروني
     * Send Email
     */
    public function send($to, $subject, $body, $options = []) {
        try {
            // إعادة تعيين المستقبلين
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearCustomHeaders();
            
            // تعيين المستقبل
            if (is_array($to)) {
                foreach ($to as $email => $name) {
                    if (is_numeric($email)) {
                        $this->mailer->addAddress($name);
                    } else {
                        $this->mailer->addAddress($email, $name);
                    }
                }
            } else {
                $this->mailer->addAddress($to);
            }
            
            // تعيين العنوان والمحتوى
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $this->wrapInTemplate($body, $subject, $options);
            
            // تعيين النص البديل
            if (isset($options['alt_body'])) {
                $this->mailer->AltBody = $options['alt_body'];
            } else {
                $this->mailer->AltBody = strip_tags($body);
            }
            
            // إضافة نسخة كربونية
            if (isset($options['cc'])) {
                if (is_array($options['cc'])) {
                    foreach ($options['cc'] as $email => $name) {
                        if (is_numeric($email)) {
                            $this->mailer->addCC($name);
                        } else {
                            $this->mailer->addCC($email, $name);
                        }
                    }
                } else {
                    $this->mailer->addCC($options['cc']);
                }
            }
            
            // إضافة نسخة كربونية مخفية
            if (isset($options['bcc'])) {
                if (is_array($options['bcc'])) {
                    foreach ($options['bcc'] as $email => $name) {
                        if (is_numeric($email)) {
                            $this->mailer->addBCC($name);
                        } else {
                            $this->mailer->addBCC($email, $name);
                        }
                    }
                } else {
                    $this->mailer->addBCC($options['bcc']);
                }
            }
            
            // إضافة المرفقات
            if (isset($options['attachments'])) {
                foreach ($options['attachments'] as $attachment) {
                    if (is_array($attachment)) {
                        $this->mailer->addAttachment(
                            $attachment['path'],
                            $attachment['name'] ?? '',
                            $attachment['encoding'] ?? 'base64',
                            $attachment['type'] ?? ''
                        );
                    } else {
                        $this->mailer->addAttachment($attachment);
                    }
                }
            }
            
            // إضافة أولوية
            if (isset($options['priority'])) {
                $this->mailer->Priority = $options['priority'];
            }
            
            // إرسال البريد
            $result = $this->mailer->send();
            
            if ($result) {
                $this->logEmailActivity($to, $subject, 'sent');
                return true;
            } else {
                $this->logError("Failed to send email to: " . (is_array($to) ? implode(', ', array_keys($to)) : $to));
                return false;
            }
            
        } catch (Exception $e) {
            $this->logError("Email send error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * تطبيق قالب البريد الإلكتروني
     * Wrap Content in Email Template
     */
    private function wrapInTemplate($content, $subject, $options = []) {
        $companyName = getCompanySetting('company_name_ar', 'تدبير بلس');
        $companyLogo = getCompanySetting('logo', ASSETS_URL . 'images/logo.png');
        $companyWebsite = getCompanySetting('website', 'https://tadbeerplus.ae');
        $companyEmail = getCompanySetting('email', 'info@tadbeerplus.ae');
        $companyPhone = getCompanySetting('phone', '+971-4-1234567');
        
        $template = '
        <!DOCTYPE html>
        <html dir="rtl" lang="ar">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . htmlspecialchars($subject) . '</title>
            <style>
                body {
                    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                    margin: 0;
                    padding: 0;
                    background-color: #f5f5f5;
                    color: #333;
                    direction: rtl;
                }
                .email-container {
                    max-width: 600px;
                    margin: 20px auto;
                    background-color: #ffffff;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }
                .email-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                .email-header img {
                    max-width: 150px;
                    margin-bottom: 15px;
                }
                .email-header h1 {
                    margin: 0;
                    font-size: 24px;
                    font-weight: 300;
                }
                .email-body {
                    padding: 30px;
                    line-height: 1.6;
                }
                .email-body h2 {
                    color: #667eea;
                    border-bottom: 2px solid #eee;
                    padding-bottom: 10px;
                }
                .button {
                    display: inline-block;
                    padding: 12px 30px;
                    background-color: #667eea;
                    color: white !important;
                    text-decoration: none;
                    border-radius: 5px;
                    margin: 20px 0;
                    font-weight: bold;
                }
                .button:hover {
                    background-color: #5a6fd8;
                }
                .email-footer {
                    background-color: #f8f9fa;
                    padding: 20px;
                    text-align: center;
                    font-size: 14px;
                    color: #666;
                    border-top: 1px solid #eee;
                }
                .contact-info {
                    margin-top: 15px;
                }
                .contact-info a {
                    color: #667eea;
                    text-decoration: none;
                }
                @media only screen and (max-width: 600px) {
                    .email-container {
                        margin: 0;
                        border-radius: 0;
                    }
                    .email-header, .email-body, .email-footer {
                        padding: 20px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <img src="' . $companyLogo . '" alt="' . htmlspecialchars($companyName) . '">
                    <h1>' . htmlspecialchars($companyName) . '</h1>
                </div>
                <div class="email-body">
                    ' . $content . '
                </div>
                <div class="email-footer">
                    <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($companyName) . '. جميع الحقوق محفوظة.</p>
                    <div class="contact-info">
                        <p>
                            <strong>الموقع الإلكتروني:</strong> <a href="' . $companyWebsite . '">' . $companyWebsite . '</a><br>
                            <strong>البريد الإلكتروني:</strong> <a href="mailto:' . $companyEmail . '">' . $companyEmail . '</a><br>
                            <strong>الهاتف:</strong> ' . htmlspecialchars($companyPhone) . '
                        </p>
                    </div>
                    <p style="font-size: 12px; margin-top: 15px; color: #999;">
                        هذا البريد الإلكتروني تم إرساله تلقائياً من نظام تدبير بلس. يرجى عدم الرد على هذا البريد.
                    </p>
                </div>
            </div>
        </body>
        </html>';
        
        return $template;
    }
    
    /**
     * إرسال بريد ترحيبي
     * Send Welcome Email
     */
    public function sendWelcomeEmail($userEmail, $userName, $userRole) {
        $subject = 'مرحباً بك في تدبير بلس';
        
        $body = '
        <h2>أهلاً وسهلاً ' . htmlspecialchars($userName) . '</h2>
        <p>نرحب بك في نظام تدبير بلس لإدارة العمالة المنزلية.</p>
        <p><strong>معلومات حسابك:</strong></p>
        <ul>
            <li>البريد الإلكتروني: ' . htmlspecialchars($userEmail) . '</li>
            <li>نوع الحساب: ' . htmlspecialchars($userRole) . '</li>
        </ul>
        <p>يمكنك الآن تسجيل الدخول والاستفادة من جميع خدماتنا.</p>
        <a href="' . BASE_URL . 'login.php" class="button">تسجيل الدخول</a>
        <p>إذا كان لديك أي استفسارات، لا تتردد في التواصل معنا.</p>';
        
        return $this->send($userEmail, $subject, $body);
    }
    
    /**
     * إرسال بريد إعادة تعيين كلمة المرور
     * Send Password Reset Email
     */
    public function sendPasswordResetEmail($userEmail, $userName, $resetToken) {
        $subject = 'إعادة تعيين كلمة المرور - تدبير بلس';
        $resetLink = BASE_URL . 'reset_password.php?token=' . $resetToken;
        
        $body = '
        <h2>إعادة تعيين كلمة المرور</h2>
        <p>عزيزي ' . htmlspecialchars($userName) . ',</p>
        <p>تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك في تدبير بلس.</p>
        <p>للمتابعة، يرجى النقر على الرابط أدناه:</p>
        <a href="' . $resetLink . '" class="button">إعادة تعيين كلمة المرور</a>
        <p><strong>ملاحظة مهمة:</strong></p>
        <ul>
            <li>هذا الرابط صالح لمدة ساعة واحدة فقط</li>
            <li>إذا لم تطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذا البريد</li>
            <li>لا تشارك هذا الرابط مع أي شخص آخر</li>
        </ul>
        <p>إذا واجهت أي مشاكل، يرجى التواصل مع فريق الدعم.</p>';
        
        return $this->send($userEmail, $subject, $body, [
            'priority' => 1 // أولوية عالية
        ]);
    }
    
    /**
     * إرسال تنبيه انتهاء العقد
     * Send Contract Expiry Alert
     */
    public function sendContractExpiryAlert($clientEmail, $clientName, $contractNumber, $expiryDate, $workerName) {
        $subject = 'تنبيه: انتهاء صلاحية العقد رقم ' . $contractNumber;
        
        $body = '
        <h2>تنبيه انتهاء صلاحية العقد</h2>
        <p>عزيزي ' . htmlspecialchars($clientName) . ',</p>
        <p>نود تنبيهك بأن العقد التالي على وشك الانتهاء:</p>
        <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p><strong>رقم العقد:</strong> ' . htmlspecialchars($contractNumber) . '</p>
            <p><strong>اسم العامل:</strong> ' . htmlspecialchars($workerName) . '</p>
            <p><strong>تاريخ الانتهاء:</strong> ' . date('d/m/Y', strtotime($expiryDate)) . '</p>
        </div>
        <p>يرجى التواصل معنا لتجديد العقد أو اتخاذ الإجراءات المناسبة.</p>
        <a href="' . CLIENT_URL . 'contracts/" class="button">عرض عقودي</a>
        <p>شكراً لك على ثقتك في خدماتنا.</p>';
        
        return $this->send($clientEmail, $subject, $body);
    }
    
    /**
     * إرسال تنبيه دفع مستحق
     * Send Payment Due Alert
     */
    public function sendPaymentDueAlert($clientEmail, $clientName, $invoiceNumber, $amount, $dueDate) {
        $subject = 'تذكير: دفعة مستحقة - فاتورة رقم ' . $invoiceNumber;
        
        $body = '
        <h2>تذكير بدفعة مستحقة</h2>
        <p>عزيزي ' . htmlspecialchars($clientName) . ',</p>
        <p>نود تذكيرك بوجود دفعة مستحقة في حسابك:</p>
        <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p><strong>رقم الفاتورة:</strong> ' . htmlspecialchars($invoiceNumber) . '</p>
            <p><strong>المبلغ المستحق:</strong> ' . number_format($amount, 2) . ' درهم</p>
            <p><strong>تاريخ الاستحقاق:</strong> ' . date('d/m/Y', strtotime($dueDate)) . '</p>
        </div>
        <p>يرجى سداد المبلغ في أقرب وقت ممكن لتجنب أي رسوم إضافية.</p>
        <a href="' . CLIENT_URL . 'payments/" class="button">عرض الفواتير</a>
        <p>نشكرك على تعاونك.</p>';
        
        return $this->send($clientEmail, $subject, $body);
    }
    
    /**
     * تسجيل نشاط البريد الإلكتروني
     * Log Email Activity
     */
    private function logEmailActivity($recipient, $subject, $status) {
        try {
            $db = getDB();
            $sql = "INSERT INTO activity_logs (user_id, action, description, created_at) 
                    VALUES (:user_id, :action, :description, NOW())";
            
            $description = "Email {$status} to: " . (is_array($recipient) ? implode(', ', array_keys($recipient)) : $recipient) . " | Subject: {$subject}";
            
            $params = [
                'user_id' => $_SESSION['user_id'] ?? null,
                'action' => 'email_' . $status,
                'description' => $description
            ];
            
            $db->insert($sql, $params);
            
        } catch (Exception $e) {
            error_log("Failed to log email activity: " . $e->getMessage());
        }
    }
    
    /**
     * تسجيل أخطاء البريد الإلكتروني
     * Log Email Errors
     */
    private function logError($message) {
        $logFile = LOGS_PATH . '/email_errors.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
        
        if (is_writable(LOGS_PATH)) {
            file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        }
        
        error_log($message);
    }
    
    /**
     * اختبار إعدادات البريد الإلكتروني
     * Test Email Settings
     */
    public function testConnection() {
        try {
            return $this->mailer->smtpConnect();
        } catch (Exception $e) {
            $this->logError("SMTP connection test failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * إرسال بريد اختباري
     * Send Test Email
     */
    public function sendTestEmail($recipient) {
        $subject = 'رسالة اختبار من تدبير بلس';
        $body = '
        <h2>رسالة اختبار</h2>
        <p>هذه رسالة اختبار للتأكد من عمل نظام البريد الإلكتروني بشكل صحيح.</p>
        <p>إذا وصلتك هذه الرسالة، فهذا يعني أن النظام يعمل بشكل سليم.</p>
        <p><strong>تاريخ ووقت الإرسال:</strong> ' . date('d/m/Y H:i:s') . '</p>';
        
        return $this->send($recipient, $subject, $body);
    }
}

/**
 * ==============================================
 * الدوال المساعدة للبريد الإلكتروني
 * Email Helper Functions
 * ==============================================
 */

/**
 * الحصول على مدير البريد الإلكتروني
 * Get Email Manager Instance
 */
function getEmailManager() {
    return EmailManager::getInstance();
}

/**
 * إرسال بريد إلكتروني سريع
 * Send Quick Email
 */
function sendEmail($to, $subject, $body, $options = []) {
    return getEmailManager()->send($to, $subject, $body, $options);
}

/**
 * إرسال بريد ترحيبي
 * Send Welcome Email
 */
function sendWelcomeEmail($userEmail, $userName, $userRole) {
    return getEmailManager()->sendWelcomeEmail($userEmail, $userName, $userRole);
}

/**
 * إرسال بريد إعادة تعيين كلمة المرور
 * Send Password Reset Email
 */
function sendPasswordResetEmail($userEmail, $userName, $resetToken) {
    return getEmailManager()->sendPasswordResetEmail($userEmail, $userName, $resetToken);
}

/**
 * إرسال تنبيه انتهاء العقد
 * Send Contract Expiry Alert
 */
function sendContractExpiryAlert($clientEmail, $clientName, $contractNumber, $expiryDate, $workerName) {
    return getEmailManager()->sendContractExpiryAlert($clientEmail, $clientName, $contractNumber, $expiryDate, $workerName);
}

/**
 * إرسال تنبيه دفع مستحق
 * Send Payment Due Alert
 */
function sendPaymentDueAlert($clientEmail, $clientName, $invoiceNumber, $amount, $dueDate) {
    return getEmailManager()->sendPaymentDueAlert($clientEmail, $clientName, $invoiceNumber, $amount, $dueDate);
}

/**
 * اختبار إعدادات البريد الإلكتروني
 * Test Email Settings
 */
function testEmailConnection() {
    return getEmailManager()->testConnection();
}

/**
 * إرسال بريد اختباري
 * Send Test Email
 */
function sendTestEmail($recipient) {
    return getEmailManager()->sendTestEmail($recipient);
}

/**
 * إرسال إشعار جديد
 * Send Notification Email
 */
function sendNotificationEmail($userEmail, $title, $message, $type = 'info') {
    $subject = 'إشعار من تدبير بلس: ' . $title;
    
    $typeColors = [
        'info' => '#17a2b8',
        'success' => '#28a745',
        'warning' => '#ffc107',
        'error' => '#dc3545'
    ];
    
    $typeIcons = [
        'info' => '💬',
        'success' => '✅',
        'warning' => '⚠️',
        'error' => '❌'
    ];
    
    $color = $typeColors[$type] ?? $typeColors['info'];
    $icon = $typeIcons[$type] ?? $typeIcons['info'];
    
    $body = '
    <div style="border-left: 4px solid ' . $color . '; padding-left: 15px; margin: 20px 0;">
        <h3 style="color: ' . $color . '; margin-bottom: 10px;">' . $icon . ' ' . htmlspecialchars($title) . '</h3>
        <p style="line-height: 1.6;">' . nl2br(htmlspecialchars($message)) . '</p>
    </div>
    <p style="margin-top: 30px;">هذا إشعار تلقائي من نظام تدبير بلس.</p>';
    
    return sendEmail($userEmail, $subject, $body);
}

/**
 * إرسال بريد تأكيد العقد
 * Send Contract Confirmation Email
 */
function sendContractConfirmationEmail($clientEmail, $clientName, $contractNumber, $workerName) {
    $subject = 'تأكيد العقد رقم ' . $contractNumber . ' - تدبير بلس';
    
    $body = '
    <h2>تأكيد إنشاء العقد</h2>
    <p>عزيزي ' . htmlspecialchars($clientName) . ',</p>
    <p>يسعدنا إبلاغك بأنه تم إنشاء عقدكم بنجاح:</p>
    <div style="background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <p><strong>رقم العقد:</strong> ' . htmlspecialchars($contractNumber) . '</p>
        <p><strong>اسم العامل:</strong> ' . htmlspecialchars($workerName) . '</p>
        <p><strong>تاريخ الإنشاء:</strong> ' . date('d/m/Y H:i') . '</p>
    </div>
    <p>يمكنكم الآن الاطلاع على تفاصيل العقد وطباعته من منطقة العملاء.</p>
    <a href="' . CLIENT_URL . 'contracts/contract_view.php?id=' . urlencode($contractNumber) . '" class="button">عرض العقد</a>
    <p>نشكركم لثقتكم في خدماتنا.</p>';
    
    return sendEmail($clientEmail, $subject, $body);
}

/**
 * إرسال بريد تأكيد الدفع
 * Send Payment Confirmation Email
 */
function sendPaymentConfirmationEmail($clientEmail, $clientName, $invoiceNumber, $amount, $paymentMethod) {
    $subject = 'تأكيد استلام الدفعة - فاتورة رقم ' . $invoiceNumber;
    
    $body = '
    <h2>تأكيد استلام الدفعة</h2>
    <p>عزيزي ' . htmlspecialchars($clientName) . ',</p>
    <p>نؤكد استلامنا لدفعتكم بنجاح:</p>
    <div style="background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <p><strong>رقم الفاتورة:</strong> ' . htmlspecialchars($invoiceNumber) . '</p>
        <p><strong>المبلغ المدفوع:</strong> ' . number_format($amount, 2) . ' درهم</p>
        <p><strong>طريقة الدفع:</strong> ' . htmlspecialchars($paymentMethod) . '</p>
        <p><strong>تاريخ الدفع:</strong> ' . date('d/m/Y H:i') . '</p>
    </div>
    <p>شكراً لكم على سداد المبلغ في الوقت المحدد.</p>
    <a href="' . CLIENT_URL . 'payments/payment_receipt.php?invoice=' . urlencode($invoiceNumber) . '" class="button">تحميل الإيصال</a>';
    
    return sendEmail($clientEmail, $subject, $body);
}

/**
 * إرسال بريد تذكير الموعد
 * Send Appointment Reminder Email
 */
function sendAppointmentReminderEmail($userEmail, $userName, $appointmentDate, $appointmentType, $notes = '') {
    $subject = 'تذكير بموعد - ' . $appointmentType;
    
    $body = '
    <h2>تذكير بموعد قادم</h2>
    <p>عزيزي ' . htmlspecialchars($userName) . ',</p>
    <p>نذكركم بموعدكم القادم معنا:</p>
    <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <p><strong>نوع الموعد:</strong> ' . htmlspecialchars($appointmentType) . '</p>
        <p><strong>التاريخ والوقت:</strong> ' . date('d/m/Y H:i', strtotime($appointmentDate)) . '</p>
        ' . (!empty($notes) ? '<p><strong>ملاحظات:</strong> ' . nl2br(htmlspecialchars($notes)) . '</p>' : '') . '
    </div>
    <p>يرجى التأكد من حضوركم في الموعد المحدد.</p>
    <p>في حالة الحاجة لإعادة جدولة الموعد، يرجى التواصل معنا.</p>';
    
    return sendEmail($userEmail, $subject, $body);
}

/**
 * إرسال بريد تنبيه النظام
 * Send System Alert Email
 */
function sendSystemAlertEmail($adminEmails, $alertType, $message, $severity = 'warning') {
    $severityColors = [
        'info' => '#17a2b8',
        'warning' => '#ffc107',
        'error' => '#dc3545',
        'critical' => '#6f42c1'
    ];
    
    $severityIcons = [
        'info' => 'ℹ️',
        'warning' => '⚠️',
        'error' => '🚨',
        'critical' => '🔥'
    ];
    
    $color = $severityColors[$severity] ?? $severityColors['warning'];
    $icon = $severityIcons[$severity] ?? $severityIcons['warning'];
    
    $subject = $icon . ' تنبيه النظام: ' . $alertType;
    
    $body = '
    <h2 style="color: ' . $color . ';">' . $icon . ' تنبيه نظام تدبير بلس</h2>
    <div style="background-color: #f8f9fa; border-left: 4px solid ' . $color . '; padding: 15px; margin: 20px 0;">
        <p><strong>نوع التنبيه:</strong> ' . htmlspecialchars($alertType) . '</p>
        <p><strong>مستوى الخطورة:</strong> ' . strtoupper($severity) . '</p>
        <p><strong>الوقت:</strong> ' . date('d/m/Y H:i:s') . '</p>
        <hr style="margin: 15px 0;">
        <p><strong>التفاصيل:</strong></p>
        <p style="font-family: monospace; background: #f1f3f4; padding: 10px; border-radius: 3px;">' . nl2br(htmlspecialchars($message)) . '</p>
    </div>
    <p>يرجى اتخاذ الإجراء المناسب في أقرب وقت ممكن.</p>';
    
    return sendEmail($adminEmails, $subject, $body, ['priority' => 1]);
}

?>
