<?php
/**
 * ==============================================
 * نظام تدبير بلس - مسار التنقل
 * Tadbeer Plus System - Breadcrumb Navigation
 * ==============================================
 * 
 * @file breadcrumb.php
 * @description مكون مسار التنقل المتقدم مع دعم متعدد اللغات والصلاحيات
 * @version 1.0
 * @author Tadbeer Plus Team
 * @created 2025-01-01
 */

// التأكد من تضمين الملفات المطلوبة
if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../config/constants.php';
}

require_once CONFIG_PATH . '/i18n.php';
require_once CONFIG_PATH . '/permissions.php';
require_once INCLUDES_PATH . '/functions.php';

/**
 * فئة مسار التنقل
 * Breadcrumb Navigation Class
 */
class TadbeerBreadcrumb {
    
    private $breadcrumbs = [];
    private $currentLanguage;
    private $userPermissions;
    private $currentPath;
    private $homeIcon = '<i class="fas fa-home"></i>';
    private $separator = '<i class="fas fa-chevron-right"></i>';
    
    /**
     * منشئ الفئة
     * Constructor
     */
    public function __construct() {
        $this->currentLanguage = getCurrentLanguage();
        $this->userPermissions = getPermissionsManager();
        $this->currentPath = $this->getCurrentPath();
        $this->initializeBreadcrumbs();
    }
    
    /**
     * الحصول على المسار الحالي
     * Get Current Path
     */
    private function getCurrentPath() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = trim($path, '/');
        return $path;
    }
    
    /**
     * تهيئة مسار التنقل
     * Initialize Breadcrumbs
     */
    private function initializeBreadcrumbs() {
        // إضافة الصفحة الرئيسية دائماً
        $this->addHome();
        
        // تحليل المسار الحالي وإضافة العناصر
        $this->parsePath();
    }
    
    /**
     * إضافة الصفحة الرئيسية
     * Add Home Page
     */
    private function addHome() {
        $homeUrl = $this->getHomeUrl();
        $homeText = $this->currentLanguage === 'ar' ? 'الرئيسية' : 'Home';
        
        $this->breadcrumbs[] = [
            'text' => $this->homeIcon . ' ' . $homeText,
            'url' => $homeUrl,
            'active' => false,
            'icon' => 'fas fa-home'
        ];
    }
    
    /**
     * الحصول على رابط الصفحة الرئيسية حسب نوع المستخدم
     * Get Home URL Based on User Type
     */
    private function getHomeUrl() {
        if (!isset($_SESSION['user_type'])) {
            return BASE_URL;
        }
        
        switch ($_SESSION['user_type']) {
            case 'staff':
                if ($this->userPermissions->isSuperAdmin()) {
                    return BASE_URL . 'admin/dashboard.php';
                } elseif ($this->userPermissions->hasPermission('financial', 'can_view')) {
                    return BASE_URL . 'financial/dashboard.php';
                } elseif ($this->userPermissions->hasPermission('sales', 'can_view')) {
                    return BASE_URL . 'sales/dashboard.php';
                } elseif ($this->userPermissions->hasPermission('customer_service', 'can_view')) {
                    return BASE_URL . 'customer_service/dashboard.php';
                } else {
                    return BASE_URL . 'dashboard_redirect.php';
                }
            case 'client':
                return BASE_URL . 'client/dashboard.php';
            default:
                return BASE_URL;
        }
    }
    
    /**
     * تحليل المسار وإضافة العناصر
     * Parse Path and Add Elements
     */
    private function parsePath() {
        if (empty($this->currentPath)) {
            return;
        }
        
        $pathParts = explode('/', $this->currentPath);
        $breadcrumbMap = $this->getBreadcrumbMap();
        
        $currentUrl = BASE_URL;
        
        foreach ($pathParts as $index => $part) {
            // تجاهل الملفات مع الامتدادات
            if (strpos($part, '.php') !== false || strpos($part, '.html') !== false) {
                $part = pathinfo($part, PATHINFO_FILENAME);
            }
            
            $currentUrl .= $part . '/';
            
            // البحث عن المعلومات في خريطة مسار التنقل
            $breadcrumbInfo = $this->findBreadcrumbInfo($part, $pathParts, $index, $breadcrumbMap);
            
            if ($breadcrumbInfo) {
                // التحقق من الصلاحيات
                if ($this->hasAccessPermission($breadcrumbInfo)) {
                    $isLast = ($index === count($pathParts) - 1);
                    
                    $this->breadcrumbs[] = [
                        'text' => $this->getLocalizedText($breadcrumbInfo),
                        'url' => $isLast ? null : $currentUrl,
                        'active' => $isLast,
                        'icon' => $breadcrumbInfo['icon'] ?? null
                    ];
                }
            }
        }
    }
    
    /**
     * البحث عن معلومات مسار التنقل
     * Find Breadcrumb Information
     */
    private function findBreadcrumbInfo($part, $pathParts, $index, $breadcrumbMap) {
        // البحث المباشر
        if (isset($breadcrumbMap[$part])) {
            return $breadcrumbMap[$part];
        }
        
        // البحث المتقدم بناءً على السياق
        $context = implode('/', array_slice($pathParts, 0, $index + 1));
        if (isset($breadcrumbMap[$context])) {
            return $breadcrumbMap[$context];
        }
        
        // البحث بناءً على النمط
        return $this->findByPattern($part, $pathParts, $index, $breadcrumbMap);
    }
    
    /**
     * البحث بناءً على النمط
     * Find by Pattern
     */
    private function findByPattern($part, $pathParts, $index, $breadcrumbMap) {
        $patterns = [
            // أنماط الإدارة
            '/^admin$/' => $breadcrumbMap['admin'] ?? null,
            '/^dashboard$/' => $breadcrumbMap['dashboard'] ?? null,
            '/^users$/' => $breadcrumbMap['users'] ?? null,
            '/^clients$/' => $breadcrumbMap['clients'] ?? null,
            '/^workers$/' => $breadcrumbMap['workers'] ?? null,
            '/^contracts$/' => $breadcrumbMap['contracts'] ?? null,
            '/^financial$/' => $breadcrumbMap['financial'] ?? null,
            '/^reports$/' => $breadcrumbMap['reports'] ?? null,
            
            // أنماط العمليات
            '/.*_add$/' => ['ar' => 'إضافة جديد', 'en' => 'Add New', 'icon' => 'fas fa-plus'],
            '/.*_edit$/' => ['ar' => 'تعديل', 'en' => 'Edit', 'icon' => 'fas fa-edit'],
            '/.*_view$/' => ['ar' => 'عرض', 'en' => 'View', 'icon' => 'fas fa-eye'],
            '/.*_list$/' => ['ar' => 'قائمة', 'en' => 'List', 'icon' => 'fas fa-list'],
            '/.*_manage$/' => ['ar' => 'إدارة', 'en' => 'Manage', 'icon' => 'fas fa-cogs'],
        ];
        
        foreach ($patterns as $pattern => $info) {
            if (preg_match($pattern, $part)) {
                return $info;
            }
        }
        
        return null;
    }
    
    /**
     * خريطة مسار التنقل
     * Breadcrumb Map
     */
    private function getBreadcrumbMap() {
        return [
            // الإدارة الرئيسية
            'admin' => [
                'ar' => 'لوحة الإدارة',
                'en' => 'Administration',
                'icon' => 'fas fa-cogs',
                'permission' => 'admin'
            ],
            'dashboard' => [
                'ar' => 'لوحة التحكم',
                'en' => 'Dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'permission' => 'dashboard'
            ],
            
            // إدارة المستخدمين
            'users' => [
                'ar' => 'المستخدمون',
                'en' => 'Users',
                'icon' => 'fas fa-users',
                'permission' => 'users'
            ],
            'user_manage' => [
                'ar' => 'إدارة المستخدمين',
                'en' => 'User Management',
                'icon' => 'fas fa-user-cog',
                'permission' => 'users'
            ],
            'user_add' => [
                'ar' => 'إضافة مستخدم جديد',
                'en' => 'Add New User',
                'icon' => 'fas fa-user-plus',
                'permission' => 'users'
            ],
            'user_edit' => [
                'ar' => 'تعديل المستخدم',
                'en' => 'Edit User',
                'icon' => 'fas fa-user-edit',
                'permission' => 'users'
            ],
            'user_profile' => [
                'ar' => 'الملف الشخصي',
                'en' => 'User Profile',
                'icon' => 'fas fa-user-circle',
                'permission' => 'users'
            ],
            
            // الصلاحيات
            'permissions' => [
                'ar' => 'الصلاحيات',
                'en' => 'Permissions',
                'icon' => 'fas fa-key',
                'permission' => 'permissions'
            ],
            'roles_manage' => [
                'ar' => 'إدارة الأدوار',
                'en' => 'Role Management',
                'icon' => 'fas fa-user-tag',
                'permission' => 'permissions'
            ],
            'permissions_manage' => [
                'ar' => 'إدارة الصلاحيات',
                'en' => 'Permission Management',
                'icon' => 'fas fa-shield-alt',
                'permission' => 'permissions'
            ],
            
            // الموظفون
            'employees' => [
                'ar' => 'الموظفون',
                'en' => 'Employees',
                'icon' => 'fas fa-user-tie',
                'permission' => 'employees'
            ],
            'departments' => [
                'ar' => 'الأقسام',
                'en' => 'Departments',
                'icon' => 'fas fa-building',
                'permission' => 'employees'
            ],
            'designations' => [
                'ar' => 'المناصب الوظيفية',
                'en' => 'Designations',
                'icon' => 'fas fa-briefcase',
                'permission' => 'employees'
            ],
            
            // العملاء
            'clients' => [
                'ar' => 'العملاء',
                'en' => 'Clients',
                'icon' => 'fas fa-user-friends',
                'permission' => 'clients'
            ],
            'employers' => [
                'ar' => 'أصحاب العمل',
                'en' => 'Employers',
                'icon' => 'fas fa-user-tie',
                'permission' => 'clients'
            ],
            'client_add' => [
                'ar' => 'إضافة عميل جديد',
                'en' => 'Add New Client',
                'icon' => 'fas fa-user-plus',
                'permission' => 'clients'
            ],
            
            // العمالة
            'workers' => [
                'ar' => 'العمالة',
                'en' => 'Workers',
                'icon' => 'fas fa-hard-hat',
                'permission' => 'workers'
            ],
            'worker_add' => [
                'ar' => 'إضافة عامل جديد',
                'en' => 'Add New Worker',
                'icon' => 'fas fa-user-plus',
                'permission' => 'workers'
            ],
            'workers_list' => [
                'ar' => 'قائمة العمالة',
                'en' => 'Workers List',
                'icon' => 'fas fa-list',
                'permission' => 'workers'
            ],
            'workers_import' => [
                'ar' => 'استيراد العمالة',
                'en' => 'Import Workers',
                'icon' => 'fas fa-file-import',
                'permission' => 'workers'
            ],
            
            // العقود
            'contracts' => [
                'ar' => 'العقود',
                'en' => 'Contracts',
                'icon' => 'fas fa-file-contract',
                'permission' => 'contracts'
            ],
            'contract_new' => [
                'ar' => 'عقد جديد',
                'en' => 'New Contract',
                'icon' => 'fas fa-file-plus',
                'permission' => 'contracts'
            ],
            'contracts_active' => [
                'ar' => 'العقود النشطة',
                'en' => 'Active Contracts',
                'icon' => 'fas fa-file-check',
                'permission' => 'contracts'
            ],
            'contracts_expired' => [
                'ar' => 'العقود المنتهية',
                'en' => 'Expired Contracts',
                'icon' => 'fas fa-file-times',
                'permission' => 'contracts'
            ],
            'contract_templates' => [
                'ar' => 'قوالب العقود',
                'en' => 'Contract Templates',
                'icon' => 'fas fa-file-alt',
                'permission' => 'contracts'
            ],
            
            // الشؤون المالية
            'financial' => [
                'ar' => 'الشؤون المالية',
                'en' => 'Financial',
                'icon' => 'fas fa-dollar-sign',
                'permission' => 'financial'
            ],
            'accounts' => [
                'ar' => 'الحسابات',
                'en' => 'Accounts',
                'icon' => 'fas fa-money-check-alt',
                'permission' => 'financial'
            ],
            'invoices' => [
                'ar' => 'الفواتير',
                'en' => 'Invoices',
                'icon' => 'fas fa-file-invoice-dollar',
                'permission' => 'financial'
            ],
            'payments' => [
                'ar' => 'المدفوعات',
                'en' => 'Payments',
                'icon' => 'fas fa-credit-card',
                'permission' => 'financial'
            ],
            'financial_reports' => [
                'ar' => 'التقارير المالية',
                'en' => 'Financial Reports',
                'icon' => 'fas fa-chart-line',
                'permission' => 'financial'
            ],
            
            // التقارير
            'reports' => [
                'ar' => 'التقارير',
                'en' => 'Reports',
                'icon' => 'fas fa-chart-bar',
                'permission' => 'reports'
            ],
            
            // الإعدادات
            'settings' => [
                'ar' => 'الإعدادات',
                'en' => 'Settings',
                'icon' => 'fas fa-cogs',
                'permission' => 'settings'
            ],
            'company_settings' => [
                'ar' => 'إعدادات الشركة',
                'en' => 'Company Settings',
                'icon' => 'fas fa-building',
                'permission' => 'settings'
            ],
            'system_settings' => [
                'ar' => 'إعدادات النظام',
                'en' => 'System Settings',
                'icon' => 'fas fa-server',
                'permission' => 'settings'
            ],
            'email_settings' => [
                'ar' => 'إعدادات البريد الإلكتروني',
                'en' => 'Email Settings',
                'icon' => 'fas fa-envelope-open',
                'permission' => 'settings'
            ],
            'backup_restore' => [
                'ar' => 'النسخ الاحتياطي والاستعادة',
                'en' => 'Backup & Restore',
                'icon' => 'fas fa-database',
                'permission' => 'settings'
            ],
            
            // السجلات
            'logs' => [
                'ar' => 'السجلات',
                'en' => 'Logs',
                'icon' => 'fas fa-list-alt',
                'permission' => 'logs'
            ],
            'activity_logs' => [
                'ar' => 'سجل النشاطات',
                'en' => 'Activity Logs',
                'icon' => 'fas fa-history',
                'permission' => 'logs'
            ],
            'error_logs' => [
                'ar' => 'سجل الأخطاء',
                'en' => 'Error Logs',
                'icon' => 'fas fa-exclamation-triangle',
                'permission' => 'logs'
            ],
            
            // منطقة العملاء
            'client' => [
                'ar' => 'منطقة العملاء',
                'en' => 'Client Area',
                'icon' => 'fas fa-user-circle',
                'permission' => 'client_portal'
            ],
            'my_contracts' => [
                'ar' => 'عقودي',
                'en' => 'My Contracts',
                'icon' => 'fas fa-file-contract',
                'permission' => 'client_portal'
            ],
            'payment_history' => [
                'ar' => 'تاريخ المدفوعات',
                'en' => 'Payment History',
                'icon' => 'fas fa-history',
                'permission' => 'client_portal'
            ],
            'my_profile' => [
                'ar' => 'ملفي الشخصي',
                'en' => 'My Profile',
                'icon' => 'fas fa-user',
                'permission' => 'client_portal'
            ],
            'support_tickets' => [
                'ar' => 'تذاكر الدعم',
                'en' => 'Support Tickets',
                'icon' => 'fas fa-ticket-alt',
                'permission' => 'client_portal'
            ],
            
            // المبيعات
            'sales' => [
                'ar' => 'المبيعات',
                'en' => 'Sales',
                'icon' => 'fas fa-chart-line',
                'permission' => 'sales'
            ],
            'customers' => [
                'ar' => 'الزبائن',
                'en' => 'Customers',
                'icon' => 'fas fa-users',
                'permission' => 'sales'
            ],
            'orders' => [
                'ar' => 'الطلبات',
                'en' => 'Orders',
                'icon' => 'fas fa-shopping-cart',
                'permission' => 'sales'
            ],
            'matching' => [
                'ar' => 'المطابقة',
                'en' => 'Matching',
                'icon' => 'fas fa-handshake',
                'permission' => 'sales'
            ],
            
            // خدمة العملاء
            'customer_service' => [
                'ar' => 'خدمة العملاء',
                'en' => 'Customer Service',
                'icon' => 'fas fa-headset',
                'permission' => 'customer_service'
            ],
            'complaints' => [
                'ar' => 'الشكاوى',
                'en' => 'Complaints',
                'icon' => 'fas fa-exclamation-circle',
                'permission' => 'customer_service'
            ],
            'inquiries' => [
                'ar' => 'الاستفسارات',
                'en' => 'Inquiries',
                'icon' => 'fas fa-question-circle',
                'permission' => 'customer_service'
            ],
            'support' => [
                'ar' => 'الدعم الفني',
                'en' => 'Support',
                'icon' => 'fas fa-life-ring',
                'permission' => 'customer_service'
            ],
            'feedback' => [
                'ar' => 'التقييمات',
                'en' => 'Feedback',
                'icon' => 'fas fa-star',
                'permission' => 'customer_service'
            ],
            
            // مدخل البيانات
            'data_entry' => [
                'ar' => 'إدخال البيانات',
                'en' => 'Data Entry',
                'icon' => 'fas fa-keyboard',
                'permission' => 'data_entry'
            ],
            'documents' => [
                'ar' => 'المستندات',
                'en' => 'Documents',
                'icon' => 'fas fa-folder',
                'permission' => 'data_entry'
            ],
            
            // المشرف
            'supervisor' => [
                'ar' => 'الإشراف',
                'en' => 'Supervision',
                'icon' => 'fas fa-user-check',
                'permission' => 'supervisor'
            ],
            'quality' => [
                'ar' => 'الجودة',
                'en' => 'Quality',
                'icon' => 'fas fa-award',
                'permission' => 'supervisor'
            ],
        ];
    }
    
    /**
     * التحقق من صلاحية الوصول
     * Check Access Permission
     */
    private function hasAccessPermission($breadcrumbInfo) {
        if (!isset($breadcrumbInfo['permission'])) {
            return true;
        }
        
        return $this->userPermissions->canView($breadcrumbInfo['permission']);
    }
    
    /**
     * الحصول على النص المحلي
     * Get Localized Text
     */
    private function getLocalizedText($breadcrumbInfo) {
        $text = $breadcrumbInfo[$this->currentLanguage] ?? $breadcrumbInfo['en'] ?? 'Unknown';
        
        if (isset($breadcrumbInfo['icon'])) {
            return '<i class="' . $breadcrumbInfo['icon'] . '"></i> ' . $text;
        }
        
        return $text;
    }
    
    /**
     * إضافة عنصر مخصص إلى مسار التنقل
     * Add Custom Breadcrumb Item
     */
    public function addItem($text, $url = null, $icon = null, $active = false) {
        $this->breadcrumbs[] = [
            'text' => $icon ? '<i class="' . $icon . '"></i> ' . $text : $text,
            'url' => $active ? null : $url,
            'active' => $active,
            'icon' => $icon
        ];
        
        return $this;
    }
    
    /**
     * إضافة عنصر نشط (الصفحة الحالية)
     * Add Active Item (Current Page)
     */
    public function addActiveItem($text, $icon = null) {
        return $this->addItem($text, null, $icon, true);
    }
    
    /**
     * تعيين الفاصل المخصص
     * Set Custom Separator
     */
    public function setSeparator($separator) {
        $this->separator = $separator;
        return $this;
    }
    
    /**
     * تعيين رمز الصفحة الرئيسية
     * Set Home Icon
     */
    public function setHomeIcon($icon) {
        $this->homeIcon = $icon;
        return $this;
    }
    
    /**
     * عرض مسار التنقل
     * Render Breadcrumb
     */
    public function render($class = 'breadcrumb', $containerClass = 'breadcrumb-container') {
        if (empty($this->breadcrumbs)) {
            return '';
        }
        
        $direction = $this->currentLanguage === 'ar' ? 'rtl' : 'ltr';
        $html = '<div class="' . $containerClass . '" dir="' . $direction . '">';
        $html .= '<nav aria-label="' . ($this->currentLanguage === 'ar' ? 'مسار التنقل' : 'Breadcrumb') . '">';
        $html .= '<ol class="' . $class . '">';
        
        $totalItems = count($this->breadcrumbs);
        
        foreach ($this->breadcrumbs as $index => $item) {
            $isLast = ($index === $totalItems - 1);
            $itemClass = 'breadcrumb-item';
            
            if ($item['active'] || $isLast) {
                $itemClass .= ' active';
            }
            
            $html .= '<li class="' . $itemClass . '"';
            
            if ($item['active'] || $isLast) {
                $html .= ' aria-current="page"';
            }
            
            $html .= '>';
            
            if (!empty($item['url']) && !($item['active'] || $isLast)) {
                $html .= '<a href="' . htmlspecialchars($item['url']) . '" class="breadcrumb-link">';
                $html .= $item['text'];
                $html .= '</a>';
            } else {
                $html .= '<span class="breadcrumb-text">' . $item['text'] . '</span>';
            }
            
            $html .= '</li>';
            
            // إضافة فاصل إذا لم يكن العنصر الأخير
            if (!$isLast) {
                $html .= '<li class="breadcrumb-separator" aria-hidden="true">' . $this->separator . '</li>';
            }
        }
        
        $html .= '</ol>';
        $html .= '</nav>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * عرض مسار التنقل المبسط
     * Render Simple Breadcrumb
     */
    public function renderSimple($separator = ' / ') {
        if (empty($this->breadcrumbs)) {
            return '';
        }
        
        $items = [];
        foreach ($this->breadcrumbs as $item) {
            $text = strip_tags($item['text']);
            if (!empty($item['url']) && !$item['active']) {
                $items[] = '<a href="' . htmlspecialchars($item['url']) . '">' . $text . '</a>';
            } else {
                $items[] = $text;
            }
        }
        
        return implode($separator, $items);
    }
    
    /**
     * الحصول على مسار التنقل كمصفوفة
     * Get Breadcrumbs as Array
     */
    public function toArray() {
        return $this->breadcrumbs;
    }
    
    /**
     * الحصول على مسار التنقل كـ JSON
     * Get Breadcrumbs as JSON
     */
    public function toJson() {
        return json_encode($this->breadcrumbs, JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * مسح مسار التنقل
     * Clear Breadcrumbs
     */
    public function clear() {
        $this->breadcrumbs = [];
        return $this;
    }
    
    /**
     * إعادة تهيئة مسار التنقل
     * Reset Breadcrumbs
     */
    public function reset() {
        $this->clear();
        $this->initializeBreadcrumbs();
        return $this;
    }
}

/**
 * ==============================================
 * الدوال المساعدة لمسار التنقل
 * Breadcrumb Helper Functions
 * ==============================================
 */

/**
 * إنشاء مثيل جديد من مسار التنقل
 * Create New Breadcrumb Instance
 */
function createBreadcrumb() {
    return new TadbeerBreadcrumb();
}

/**
 * عرض مسار التنقل التلقائي
 * Render Auto Breadcrumb
 */
function renderBreadcrumb($class = 'breadcrumb', $containerClass = 'breadcrumb-container') {
    $breadcrumb = new TadbeerBreadcrumb();
    return $breadcrumb->render($class, $containerClass);
}

/**
 * عرض مسار التنقل المبسط
 * Render Simple Auto Breadcrumb
 */
function renderSimpleBreadcrumb($separator = ' / ') {
    $breadcrumb = new TadbeerBreadcrumb();
    return $breadcrumb->renderSimple($separator);
}

/**
 * الحصول على مسار التنقل المخصص
 * Get Custom Breadcrumb
 */
function getCustomBreadcrumb($items = []) {
    $breadcrumb = new TadbeerBreadcrumb();
    $breadcrumb->clear();
    
    // إضافة الصفحة الرئيسية
    $breadcrumb->addHome();
    
    // إضافة العناصر المخصصة
    foreach ($items as $item) {
        $text = $item['text'] ?? '';
        $url = $item['url'] ?? null;
        $icon = $item['icon'] ?? null;
        $active = $item['active'] ?? false;
        
        $breadcrumb->addItem($text, $url, $icon, $active);
    }
    
    return $breadcrumb;
}

/**
 * تحديد العنوان الديناميكي للصفحة
 * Set Dynamic Page Title
 */
function setDynamicPageTitle($breadcrumb = null) {
    if ($breadcrumb === null) {
        $breadcrumb = new TadbeerBreadcrumb();
    }
    
    $breadcrumbs = $breadcrumb->toArray();
    if (empty($breadcrumbs)) {
        return;
    }
    
    $titles = [];
    foreach (array_reverse($breadcrumbs) as $item) {
        $titles[] = strip_tags($item['text']);
    }
    
    $pageTitle = implode(' - ', $titles);
    
    // تعيين العنوان
    if (!headers_sent()) {
        echo '<script>document.title = "' . htmlspecialchars($pageTitle) . '";</script>';
    }
}

/**
 * إنشاء مسار تنقل خاص بالعملاء
 * Create Client-Specific Breadcrumb
 */
function createClientBreadcrumb($additionalItems = []) {
    $breadcrumb = new TadbeerBreadcrumb();
    $breadcrumb->clear();
    
    // الصفحة الرئيسية للعميل
    $lang = getCurrentLanguage();
    $homeText = $lang === 'ar' ? 'منطقة العملاء' : 'Client Area';
    $breadcrumb->addItem($homeText, BASE_URL . 'client/dashboard.php', 'fas fa-user-circle');
    
    // إضافة العناصر الإضافية
    foreach ($additionalItems as $item) {
        $breadcrumb->addItem(
            $item['text'] ?? '',
            $item['url'] ?? null,
            $item['icon'] ?? null,
            $item['active'] ?? false
        );
    }
    
    return $breadcrumb;
}

/**
 * CSS مسار التنقل المخصص
 * Custom Breadcrumb CSS
 */
function getBreadcrumbCSS() {
    return '
    <style>
    .breadcrumb-container {
        background: #f8f9fa;
        padding: 0.75rem 1rem;
        border-radius: 0.375rem;
        margin-bottom: 1.5rem;
        border: 1px solid #dee2e6;
    }
    
    .breadcrumb {
        margin: 0;
        padding: 0;
        list-style: none;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        font-size: 0.875rem;
    }
    
    .breadcrumb-item {
        display: flex;
        align-items: center;
    }
    
    .breadcrumb-item:not(:last-child) {
        margin-inline-end: 0.5rem;
    }
    
    .breadcrumb-link {
        color: #0d6efd;
        text-decoration: none;
        transition: color 0.15s ease-in-out;
    }
    
    .breadcrumb-link:hover {
        color: #0a58ca;
        text-decoration: underline;
    }
    
    .breadcrumb-item.active .breadcrumb-text {
        color: #6c757d;
        font-weight: 500;
    }
    
    .breadcrumb-separator {
        color: #6c757d;
        margin: 0 0.5rem;
        font-size: 0.75rem;
    }
    
    .breadcrumb i {
        margin-inline-end: 0.25rem;
        font-size: 0.875rem;
    }
    
    /* الاتجاه من اليمين لليسار */
    [dir="rtl"] .breadcrumb {
        direction: rtl;
    }
    
    [dir="rtl"] .breadcrumb-item:not(:last-child) {
        margin-inline-end: 0;
        margin-inline-start: 0.5rem;
    }
    
    [dir="rtl"] .breadcrumb-separator {
        transform: scaleX(-1);
    }
    
    /* التجاوبية */
    @media (max-width: 768px) {
        .breadcrumb-container {
            padding: 0.5rem;
            font-size: 0.8rem;
        }
        
        .breadcrumb-item .breadcrumb-text,
        .breadcrumb-item .breadcrumb-link {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
        }
    }
    </style>';
}

/**
 * JavaScript مسار التنقل
 * Breadcrumb JavaScript
 */
function getBreadcrumbJS() {
    return '
    <script>
    // تحديث مسار التنقل ديناميكياً
    function updateBreadcrumb(items) {
        const container = document.querySelector(".breadcrumb-container");
        if (!container) return;
        
        const breadcrumb = container.querySelector(".breadcrumb");
        if (!breadcrumb) return;
        
        breadcrumb.innerHTML = "";
        
        items.forEach((item, index) => {
            const li = document.createElement("li");
            li.className = "breadcrumb-item" + (item.active ? " active" : "");
            
            if (item.active) {
                li.setAttribute("aria-current", "page");
            }
            
            if (item.url && !item.active) {
                const link = document.createElement("a");
                link.href = item.url;
                link.className = "breadcrumb-link";
                link.innerHTML = item.text;
                li.appendChild(link);
            } else {
                const span = document.createElement("span");
                span.className = "breadcrumb-text";
                span.innerHTML = item.text;
                li.appendChild(span);
            }
            
            breadcrumb.appendChild(li);
            
            // إضافة فاصل
            if (index < items.length - 1) {
                const separator = document.createElement("li");
                separator.className = "breadcrumb-separator";
                separator.setAttribute("aria-hidden", "true");
                separator.innerHTML = \'<i class="fas fa-chevron-right"></i>\';
                breadcrumb.appendChild(separator);
            }
        });
    }
    
    // تحديث العنوان تلقائياً
    function updatePageTitle() {
        const breadcrumbs = document.querySelectorAll(".breadcrumb-item");
        const titles = [];
        
        breadcrumbs.forEach(item => {
            const text = item.textContent.trim();
            if (text) titles.unshift(text);
        });
        
        if (titles.length > 0) {
            document.title = titles.join(" - ");
        }
    }
    
    // تهيئة مسار التنقل عند تحميل الصفحة
    document.addEventListener("DOMContentLoaded", function() {
        updatePageTitle();
    });
    </script>';
}

// تصدير الفئة الرئيسية للاستخدام العام
$GLOBALS['TadbeerBreadcrumb'] = TadbeerBreadcrumb::class;

?>
