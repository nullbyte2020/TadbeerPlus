<?php
/**
 * ==============================================
 * نظام تدبير بلس - الشريط الجانبي
 * Tadbeer Plus System - Sidebar
 * ==============================================
 */

// منع الوصول المباشر
if (!defined('BASE_PATH')) {
    die('Direct access not permitted');
}

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    return;
}

$currentUser = $_SESSION;
$userRole = $currentUser['role_name'] ?? '';
$userType = $currentUser['user_type'] ?? '';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentPath = $_SERVER['REQUEST_URI'];

// الحصول على معلومات المستخدم الكاملة
$userData = getSingleRow('users', ['id' => $_SESSION['user_id']]);
$userPermissions = getPermissionsManager();

?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <img src="<?= ASSETS_URL ?>images/logo.png" alt="<?= __('app_name') ?>" class="logo">
            <h4 class="logo-text"><?= __('app_name') ?></h4>
        </div>
        <button type="button" class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar">
            <?php if (!empty($userData['profile_image'])): ?>
                <img src="<?= UPLOADS_URL ?>profiles/<?= htmlspecialchars($userData['profile_image']) ?>" alt="Profile">
            <?php else: ?>
                <div class="avatar-placeholder">
                    <?= mb_substr($userData['full_name'] ?? 'U', 0, 1) ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($userData['full_name'] ?? '') ?></div>
            <div class="user-role"><?= __($userRole) ?></div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-menu">

            <!-- لوحة التحكم الرئيسية -->
            <?php if ($userPermissions->canView('dashboard')): ?>
            <li class="nav-item <?= ($currentPage === 'dashboard' || $currentPage === 'index') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span><?= __('dashboard') ?></span>
                </a>
            </li>
            <?php endif; ?>

            <!-- إدارة النظام - للمدير العام ومدير النظام فقط -->
            <?php if (isSuperAdmin() || isAdmin()): ?>
            <li class="nav-item has-submenu <?= strpos($currentPath, '/admin/') !== false ? 'active' : '' ?>">
                <a href="#" class="nav-link">
                    <i class="fas fa-cogs"></i>
                    <span><?= __('system_management') ?></span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <?php if ($userPermissions->canView('users_management')): ?>
                    <li class="<?= strpos($currentPath, '/users/') !== false ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/users/user_manage.php">
                            <i class="fas fa-users"></i>
                            <?= __('users') ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($userPermissions->canView('roles_permissions')): ?>
                    <li class="<?= strpos($currentPath, '/permissions/') !== false ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/permissions/roles_manage.php">
                            <i class="fas fa-shield-alt"></i>
                            <?= __('roles_permissions') ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($userPermissions->canView('system_settings')): ?>
                    <li class="<?= strpos($currentPath, '/settings/') !== false ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/settings/system_settings.php">
                            <i class="fas fa-sliders-h"></i>
                            <?= __('system_settings') ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($userPermissions->canView('activity_logs')): ?>
                    <li class="<?= strpos($currentPath, '/logs/') !== false ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/logs/activity_logs.php">
                            <i class="fas fa-history"></i>
                            <?= __('activity_logs') ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <!-- إدارة العملاء -->
            <?php if ($userPermissions->canView('clients_management')): ?>
            <li class="nav-item has-submenu <?= strpos($currentPath, '/clients/') !== false || strpos($currentPath, '/employers/') !== false ? 'active' : '' ?>">
                <a href="#" class="nav-link">
                    <i class="fas fa-user-tie"></i>
                    <span><?= __('clients') ?></span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <?php if ($userPermissions->canView('clients_list')): ?>
                    <li class="<?= ($currentPage === 'clients_list' || $currentPage === 'employers_list') ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/employers/employers_list.php">
                            <i class="fas fa-list"></i>
                            <?= __('clients_list') ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($userPermissions->canAdd('clients_management')): ?>
                    <li class="<?= ($currentPage === 'employer_add' || $currentPage === 'client_add') ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/employers/employer_add.php">
                            <i class="fas fa-plus"></i>
                            <?= __('add_client') ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($userPermissions->canView('clients_reports')): ?>
                    <li class="<?= $currentPage === 'employers_reports' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>reports/clients_report.php">
                            <i class="fas fa-chart-bar"></i>
                            <?= __('clients_reports') ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <!-- إدارة العمالة -->
            <?php if ($userPermissions->canView('workers_management')): ?>
            <li class="nav-item has-submenu <?= strpos($currentPath, '/workers/') !== false ? 'active' : '' ?>">
                <a href="#" class="nav-link">
                    <i class="fas fa-hard-hat"></i>
                    <span><?= __('workers') ?></span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <?php if ($userPermissions->canView('workers_list')): ?>
                    <li class="<?= $currentPage === 'workers_list' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/workers/workers_list.php">
                            <i class="fas fa-list"></i>
                            <?= __('workers_list') ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($userPermissions->canAdd('workers_management')): ?>
                    <li class="<?= $currentPage === 'worker_add' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/workers/worker_add.php">
                            <i class="fas fa-plus"></i>
                            <?= __('add_worker') ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($userPermissions->canView('workers_import')): ?>
                    <li class="<?= $currentPage === 'workers_import' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/workers/workers_import.php">
                            <i class="fas fa-file-import"></i>
                            <?= __('import_workers') ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($userPermissions->canView('workers_reports')): ?>
                    <li class="<?= $currentPage === 'workers_reports' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/workers/workers_reports.php">
                            <i class="fas fa-chart-line"></i>
                            <?= __('workers_reports') ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <!-- إدارة العقود -->
            <?php if ($userPermissions->canView('contracts_management')): ?>
            <li class="nav-item has-submenu <?= strpos($currentPath, '/contracts/') !== false ? 'active' : '' ?>">
                <a href="#" class="nav-link">
                    <i class="fas fa-file-contract"></i>
                    <span><?= __('contracts') ?></span>
                    <?php
                    // عرض عدد العقود المنتهية قريباً
                    $expiringContracts = count(getExpiringContracts(30));
                    if ($expiringContracts > 0):
                    ?>
                    <span class="badge badge-warning"><?= $expiringContracts ?></span>
                    <?php endif; ?>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <?php if ($userPermissions->canView('contracts_active')): ?>
                    <li class="<?= $currentPage === 'contracts_active' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/contracts/contracts_active.php">
                            <i class="fas fa-check-circle"></i>
                            <?= __('active_contracts') ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($userPermissions->canAdd('contracts_management')): ?>
                    <li class="<?= $currentPage === 'contract_new' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/contracts/contract_new.php">
                            <i class="fas fa-plus"></i>
                            <?= __('new_contract') ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($userPermissions->canView('contracts_expired')): ?>
                    <li class="<?= $currentPage === 'contracts_expired' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/contracts/contracts_expired.php">
                            <i class="fas fa-clock"></i>
                            <?= __('expiring_contracts') ?>
                            <?php if ($expiringContracts > 0): ?>
                            <span class="badge badge-warning"><?= $expiringContracts ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($userPermissions->canView('contract_templates')): ?>
                    <li class="<?= $currentPage === 'contract_templates' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/contracts/contract_templates.php">
                            <i class="fas fa-file-alt"></i>
                            <?= __('contract_templates') ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <!-- الشؤون المالية -->
            <?php if ($userPermissions->canView('financial_management') || $userRole === 'financial_manager'): ?>
            <li class="nav-item has-submenu <?= strpos($currentPath, '/financial/') !== false ? 'active' : '' ?>">
                <a href="#" class="nav-link">
                    <i class="fas fa-dollar-sign"></i>
                    <span><?= __('finance') ?></span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <?php if ($userPermissions->canView('accounts_management')): ?>
                    <li class="<?= $currentPage === 'accounts' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/financial/accounts.php">
                            <i class="fas fa-piggy-bank"></i>
                            <?= __('accounts') ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($userPermissions->canView('invoices_management')): ?>
                    <li class="<?= $currentPage === 'invoices_payments' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/financial/invoices_payments.php">
                            <i class="fas fa-file-invoice"></i>
                            <?= __('invoices') ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($userPermissions->canView('payments_management')): ?>
                    <li class="<?= $currentPage === 'payment_process' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/financial/payment_process.php">
                            <i class="fas fa-credit-card"></i>
                            <?= __('payments') ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($userPermissions->canView('financial_reports')): ?>
                    <li class="<?= $currentPage === 'financial_reports' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/financial/financial_reports.php">
                            <i class="fas fa-chart-pie"></i>
                            <?= __('financial_reports') ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <!-- المبيعات -->
            <?php if ($userPermissions->canView('sales_management') || $userRole === 'sales_manager' || $userRole === 'sales_rep'): ?>
            <li class="nav-item has-submenu <?= strpos($currentPath, '/sales/') !== false ? 'active' : '' ?>">
                <a href="#" class="nav-link">
                    <i class="fas fa-handshake"></i>
                    <span><?= __('sales') ?></span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <?php if ($userPermissions->canView('sales_customers')): ?>
                    <li class="<?= $currentPage === 'customers_list' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>sales/customers/customers_list.php">
                            <i class="fas fa-users"></i>
                            <?= __('customers') ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($userPermissions->canView('sales_orders')): ?>
                    <li class="<?= strpos($currentPath, '/orders/') !== false ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>sales/orders/orders_new.php">
                            <i class="fas fa-shopping-cart"></i>
                            <?= __('orders') ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($userPermissions->canView('sales_matching')): ?>
                    <li class="<?= $currentPage === 'worker_matching' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>sales/matching/worker_matching.php">
                            <i class="fas fa-search-plus"></i>
                            <?= __('worker_matching') ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($userPermissions->canView('sales_reports')): ?>
                    <li class="<?= $currentPage === 'sales_report' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>sales/reports/sales_report.php">
                            <i class="fas fa-chart-area"></i>
                            <?= __('sales_reports') ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <!-- خدمة العملاء -->
            <?php if ($userPermissions->canView('customer_service') || $userRole === 'customer_service'): ?>
            <li class="nav-item has-submenu <?= strpos($currentPath, '/customer_service/') !== false ? 'active' : '' ?>">
                <a href="#" class="nav-link">
                    <i class="fas fa-headset"></i>
                    <span><?= __('customer_service') ?></span>
                    <?php
                    // عدد التذاكر المفتوحة
                    $openTickets = getSingleRow('support_tickets', [], 'COUNT(*) as count WHERE status IN ("جديد", "مفتوح")')['count'] ?? 0;
                    if ($openTickets > 0):
                    ?>
                    <span class="badge badge-danger"><?= $openTickets ?></span>
                    <?php endif; ?>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <?php if ($userPermissions->canView('support_tickets')): ?>
                    <li class="<?= strpos($currentPath, '/tickets/') !== false ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>customer_service/support/tickets_list.php">
                            <i class="fas fa-ticket-alt"></i>
                            <?= __('support_tickets') ?>
                            <?php if ($openTickets > 0): ?>
                            <span class="badge badge-danger"><?= $openTickets ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($userPermissions->canView('complaints')): ?>
                    <li class="<?= strpos($currentPath, '/complaints/') !== false ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>customer_service/complaints/complaints_new.php">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?= __('complaints') ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($userPermissions->canView('feedback')): ?>
                    <li class="<?= strpos($currentPath, '/feedback/') !== false ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>customer_service/feedback/service_rating.php">
                            <i class="fas fa-star"></i>
                            <?= __('feedback') ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <!-- مدخل البيانات -->
            <?php if ($userRole === 'data_entry'): ?>
            <li class="nav-item has-submenu <?= strpos($currentPath, '/data_entry/') !== false ? 'active' : '' ?>">
                <a href="#" class="nav-link">
                    <i class="fas fa-keyboard"></i>
                    <span><?= __('data_entry') ?></span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <li class="<?= $currentPage === 'worker_add' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>data_entry/workers/worker_add.php">
                            <i class="fas fa-user-plus"></i>
                            <?= __('add_worker') ?>
                        </a>
                    </li>
                    <li class="<?= $currentPage === 'employer_add' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>data_entry/employers/employer_add.php">
                            <i class="fas fa-user-tie"></i>
                            <?= __('add_employer') ?>
                        </a>
                    </li>
                    <li class="<?= $currentPage === 'document_upload' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>data_entry/documents/document_upload.php">
                            <i class="fas fa-file-upload"></i>
                            <?= __('upload_documents') ?>
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>

            <!-- المشرفين -->
            <?php if ($userRole === 'supervisor'): ?>
            <li class="nav-item has-submenu <?= strpos($currentPath, '/supervisor/') !== false ? 'active' : '' ?>">
                <a href="#" class="nav-link">
                    <i class="fas fa-eye"></i>
                    <span><?= __('supervision') ?></span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <li class="<?= $currentPage === 'orders_pending' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>supervisor/orders/orders_pending.php">
                            <i class="fas fa-clock"></i>
                            <?= __('pending_orders') ?>
                        </a>
                    </li>
                    <li class="<?= $currentPage === 'contracts_pending' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>supervisor/contracts/contracts_pending.php">
                            <i class="fas fa-file-signature"></i>
                            <?= __('pending_contracts') ?>
                        </a>
                    </li>
                    <li class="<?= $currentPage === 'quality_checks' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>supervisor/quality/quality_checks.php">
                            <i class="fas fa-clipboard-check"></i>
                            <?= __('quality_checks') ?>
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>

            <!-- التقارير -->
            <?php if ($userPermissions->canView('reports')): ?>
            <li class="nav-item has-submenu <?= strpos($currentPath, '/reports/') !== false ? 'active' : '' ?>">
                <a href="#" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span><?= __('reports') ?></span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <li class="<?= $currentPage === 'workers_report' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>reports/workers_report.php">
                            <i class="fas fa-users"></i>
                            <?= __('workers_reports') ?>
                        </a>
                    </li>
                    <li class="<?= $currentPage === 'clients_report' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>reports/clients_report.php">
                            <i class="fas fa-user-tie"></i>
                            <?= __('clients_reports') ?>
                        </a>
                    </li>
                    <li class="<?= $currentPage === 'contracts_report' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>reports/contracts_report.php">
                            <i class="fas fa-file-contract"></i>
                            <?= __('contracts_reports') ?>
                        </a>
                    </li>
                    <li class="<?= $currentPage === 'financial_report' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>reports/financial_report.php">
                            <i class="fas fa-dollar-sign"></i>
                            <?= __('financial_reports') ?>
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>

            <!-- فاصل -->
            <li class="nav-divider"></li>

            <!-- المساعدة والدعم -->
            <li class="nav-item <?= $currentPage === 'help' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>help.php" class="nav-link">
                    <i class="fas fa-question-circle"></i>
                    <span><?= __('help') ?></span>
                </a>
            </li>

            <!-- الإعدادات الشخصية -->
            <li class="nav-item <?= $currentPage === 'profile' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>profile.php" class="nav-link">
                    <i class="fas fa-user-cog"></i>
                    <span><?= __('my_profile') ?></span>
                </a>
            </li>

        </ul>
    </nav>

    <div class="sidebar-footer">
        <!-- معلومات النظام -->
        <div class="system-info">
            <small class="text-muted">
                <?= __('version') ?>: <?= getSetting('system_version', '1.0.0') ?><br>
                <?= __('last_backup') ?>: <?= formatDate(getSetting('last_backup_date', date('Y-m-d'))) ?>
            </small>
        </div>

        <!-- تبديل اللغة -->
        <div class="language-switcher">
            <?php
            $availableLanguages = getAvailableLanguages();
            foreach ($availableLanguages as $langCode => $langInfo):
                if ($langCode !== getCurrentLanguage()):
            ?>
            <a href="<?= getLanguageUrl($langCode) ?>" class="lang-switch" title="<?= $langInfo['native_name'] ?>">
                <img src="<?= ASSETS_URL ?>images/flags/<?= $langCode ?>.png" alt="<?= $langInfo['name'] ?>">
                <?= $langInfo['native_name'] ?>
            </a>
            <?php 
                endif;
            endforeach; 
            ?>
        </div>

        <!-- تسجيل الخروج -->
        <div class="logout-section">
            <a href="<?= BASE_URL ?>logout.php" class="nav-link logout-link" onclick="return confirm('<?= __('confirm_logout') ?>')">
                <i class="fas fa-sign-out-alt"></i>
                <span><?= __('logout') ?></span>
            </a>
        </div>
    </div>

</aside>

<!-- تراكب الشاشة للهواتف -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<style>
.sidebar {
    width: 280px;
    height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    position: fixed;
    left: 0;
    top: 0;
    z-index: 1000;
    transition: all 0.3s ease;
    overflow-y: auto;
    overflow-x: hidden;
}

.sidebar.collapsed {
    width: 70px;
}

.sidebar-header {
    padding: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.logo-container {
    display: flex;
    align-items: center;
    gap: 12px;
}

.logo {
    width: 40px;
    height: 40px;
}

.logo-text {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
    white-space: nowrap;
}

.sidebar.collapsed .logo-text {
    display: none;
}

.sidebar-toggle {
    background: none;
    border: none;
    color: white;
    font-size: 18px;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    transition: background-color 0.3s;
}

.sidebar-toggle:hover {
    background-color: rgba(255,255,255,0.1);
}

.sidebar-user {
    padding: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 600;
    text-transform: uppercase;
}

.user-info {
    flex: 1;
    min-width: 0;
}

.user-name {
    font-weight: 600;
    font-size: 14px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-role {
    font-size: 12px;
    opacity: 0.8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar.collapsed .user-info {
    display: none;
}

.sidebar-nav {
    flex: 1;
    padding: 10px 0;
}

.nav-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-item {
    margin: 2px 0;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: rgba(255,255,255,0.9);
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
}

.nav-link:hover {
    background-color: rgba(255,255,255,0.1);
    color: white;
    text-decoration: none;
}

.nav-item.active > .nav-link {
    background-color: rgba(255,255,255,0.15);
    color: white;
}

.nav-link i {
    width: 20px;
    text-align: center;
    margin-left: 12px;
    font-size: 16px;
}

.nav-link span {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar.collapsed .nav-link span,
.sidebar.collapsed .submenu-arrow,
.sidebar.collapsed .badge {
    display: none;
}

.submenu-arrow {
    margin-right: 0;
    transition: transform 0.3s ease;
}

.nav-item.active .submenu-arrow {
    transform: rotate(180deg);
}

.submenu {
    list-style: none;
    padding: 0;
    margin: 0;
    background-color: rgba(0,0,0,0.1);
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.nav-item.active .submenu {
    max-height: 500px;
}

.submenu li {
    margin: 0;
}

.submenu a {
    display: flex;
    align-items: center;
    padding: 10px 20px 10px 55px;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 14px;
}

.submenu a:hover {
    background-color: rgba(255,255,255,0.1);
    color: white;
    text-decoration: none;
}

.submenu li.active a {
    background-color: rgba(255,255,255,0.15);
    color: white;
}

.submenu i {
    width: 16px;
    text-align: center;
    margin-left: 8px;
    font-size: 14px;
}

.badge {
    background-color: #dc3545;
    color: white;
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 10px;
    margin-right: 5px;
}

.badge-warning {
    background-color: #ffc107;
    color: #212529;
}

.nav-divider {
    height: 1px;
    background-color: rgba(255,255,255,0.1);
    margin: 10px 20px;
}

.sidebar-footer {
    padding: 20px;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.system-info {
    margin-bottom: 15px;
    font-size: 11px;
    text-align: center;
}

.language-switcher {
    margin-bottom: 15px;
    text-align: center;
}

.lang-switch {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    font-size: 12px;
    padding: 5px 10px;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.lang-switch:hover {
    background-color: rgba(255,255,255,0.1);
    color: white;
    text-decoration: none;
}

.lang-switch img {
    width: 16px;
    height: 12px;
}

.logout-link {
    border-top: 1px solid rgba(255,255,255,0.1);
    padding-top: 15px !important;
    margin-top: 15px;
}

.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    z-index: 999;
}

/* الاستجابة للشاشات الصغيرة */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .sidebar-overlay.active {
        display: block;
    }
}

/* RTL Support */
.sidebar[dir="rtl"] {
    left: auto;
    right: 0;
}

.sidebar[dir="rtl"] .nav-link i {
    margin-left: 0;
    margin-right: 12px;
}

.sidebar[dir="rtl"] .submenu a {
    padding: 10px 55px 10px 20px;
}

.sidebar[dir="rtl"] .submenu i {
    margin-left: 0;
    margin-right: 8px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    // تبديل الشريط الجانبي
    sidebarToggle?.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        } else {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
        }
    });

    // إغلاق عند النقر على التراكب
    sidebarOverlay?.addEventListener('click', function() {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
    });

    // استعادة حالة الشريط الجانبي
    if (window.innerWidth > 768) {
        const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
        }
    }

    // التعامل مع القوائم الفرعية
    document.querySelectorAll('.has-submenu > .nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const parentItem = this.parentElement;
            const isActive = parentItem.classList.contains('active');
            
            // إغلاق جميع القوائم الفرعية الأخرى
            document.querySelectorAll('.has-submenu.active').forEach(item => {
                if (item !== parentItem) {
                    item.classList.remove('active');
                }
            });
            
            // تبديل القائمة الحالية
            parentItem.classList.toggle('active', !isActive);
        });
    });

    // تفعيل القائمة الفرعية للصفحة الحالية
    const activeSubmenuItem = document.querySelector('.submenu li.active');
    if (activeSubmenuItem) {
        const parentMenuItem = activeSubmenuItem.closest('.has-submenu');
        if (parentMenuItem) {
            parentMenuItem.classList.add('active');
        }
    }
});
</script>
