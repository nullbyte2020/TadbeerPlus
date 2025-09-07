/**
 * ==============================================
 * نظام تدبير بلس - الملف الرئيسي للجافا سكريبت
 * Tadbeer Plus System - Main JavaScript File
 * ==============================================
 */

'use strict';

/**
 * إعدادات النظام العامة
 * Global System Configuration
 */
const TadbeerPlus = {
    // إعدادات الأساسية
    config: {
        baseUrl: window.location.protocol + '//' + window.location.hostname + (window.location.port ? ':' + window.location.port : ''),
        apiUrl: '/api/v1/',
        language: document.documentElement.lang || 'ar',
        direction: document.documentElement.dir || 'rtl',
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        userId: null,
        userRole: null,
        theme: localStorage.getItem('theme') || 'light'
    },

    // حالة التطبيق
    state: {
        isLoading: false,
        notifications: [],
        modalsOpen: 0,
        unsavedChanges: false
    },

    // الترجمات
    translations: {
        ar: {
            loading: 'جاري التحميل...',
            saving: 'جاري الحفظ...',
            deleting: 'جاري الحذف...',
            confirm_delete: 'هل أنت متأكد من الحذف؟',
            success: 'تم بنجاح',
            error: 'حدث خطأ',
            warning: 'تحذير',
            info: 'معلومات',
            cancel: 'إلغاء',
            ok: 'موافق',
            yes: 'نعم',
            no: 'لا',
            close: 'إغلاق',
            required_fields: 'يرجى ملء جميع الحقول المطلوبة',
            network_error: 'خطأ في الاتصال بالشبكة',
            session_expired: 'انتهت صلاحية الجلسة',
            permission_denied: 'ليس لديك صلاحية',
            file_size_error: 'حجم الملف كبير جداً',
            invalid_file_type: 'نوع الملف غير مدعوم'
        },
        en: {
            loading: 'Loading...',
            saving: 'Saving...',
            deleting: 'Deleting...',
            confirm_delete: 'Are you sure you want to delete?',
            success: 'Success',
            error: 'Error',
            warning: 'Warning',
            info: 'Information',
            cancel: 'Cancel',
            ok: 'OK',
            yes: 'Yes',
            no: 'No',
            close: 'Close',
            required_fields: 'Please fill all required fields',
            network_error: 'Network connection error',
            session_expired: 'Session has expired',
            permission_denied: 'Permission denied',
            file_size_error: 'File size is too large',
            invalid_file_type: 'File type not supported'
        }
    },

    /**
     * تهيئة النظام
     * Initialize System
     */
    init() {
        this.bindEvents();
        this.initializeComponents();
        this.loadUserData();
        this.checkSession();
        this.setupAjaxDefaults();
        this.initializeTheme();
        this.initializeLanguage();
        
        // إظهار رسالة ترحيب
        console.log('🎉 Tadbeer Plus System Initialized Successfully');
    },

    /**
     * ربط الأحداث العامة
     * Bind Global Events
     */
    bindEvents() {
        // منع إرسال النماذج الفارغة
        document.addEventListener('submit', this.handleFormSubmission.bind(this));
        
        // تتبع التغييرات غير المحفوظة
        document.addEventListener('change', this.trackUnsavedChanges.bind(this));
        
        // تحذير عند الخروج مع وجود تغييرات
        window.addEventListener('beforeunload', this.handleBeforeUnload.bind(this));
        
        // معالج الضغط على المفاتيح
        document.addEventListener('keydown', this.handleKeyboardShortcuts.bind(this));
        
        // معالج النقر العام
        document.addEventListener('click', this.handleGlobalClick.bind(this));
        
        // معالج تغيير حجم النافذة
        window.addEventListener('resize', this.handleWindowResize.bind(this));
        
        // معالج فقدان الاتصال
        window.addEventListener('online', () => this.showNotification(this.t('connection_restored'), 'success'));
        window.addEventListener('offline', () => this.showNotification(this.t('connection_lost'), 'warning'));
    },

    /**
     * تهيئة المكونات
     * Initialize Components
     */
    initializeComponents() {
        // تهيئة القوائم المنسدلة
        this.initializeDropdowns();
        
        // تهيئة النوافذ المنبثقة
        this.initializeModals();
        
        // تهيئة التبويبات
        this.initializeTabs();
        
        // تهيئة الأكورديون
        this.initializeAccordions();
        
        // تهيئة التلميحات
        this.initializeTooltips();
        
        // تهيئة جدول البيانات
        this.initializeDataTables();
        
        // تهيئة التقويم
        this.initializeDatePickers();
        
        // تهيئة رفع الملفات
        this.initializeFileUploads();
        
        // تهيئة الإشعارات
        this.initializeNotifications();
    },

    /**
     * الحصول على الترجمة
     * Get Translation
     */
    t(key, params = {}) {
        const lang = this.config.language;
        let translation = this.translations[lang]?.[key] || key;
        
        // استبدال المعاملات
        Object.keys(params).forEach(param => {
            translation = translation.replace(new RegExp(`{${param}}`, 'g'), params[param]);
        });
        
        return translation;
    },

    /**
     * إظهار الإشعارات
     * Show Notification
     */
    showNotification(message, type = 'info', duration = 5000) {
        const notification = {
            id: Date.now(),
            message,
            type,
            duration
        };

        this.state.notifications.push(notification);
        this.renderNotification(notification);

        // إزالة الإشعار تلقائياً
        if (duration > 0) {
            setTimeout(() => {
                this.removeNotification(notification.id);
            }, duration);
        }

        return notification.id;
    },

    /**
     * عرض الإشعار
     * Render Notification
     */
    renderNotification(notification) {
        const container = this.getNotificationContainer();
        const notificationEl = document.createElement('div');
        
        notificationEl.className = `alert alert-${notification.type} alert-dismissible fade show notification-item`;
        notificationEl.setAttribute('data-notification-id', notification.id);
        notificationEl.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-${this.getNotificationIcon(notification.type)} me-2"></i>
                <span>${notification.message}</span>
                <button type="button" class="btn-close ms-auto" onclick="TadbeerPlus.removeNotification(${notification.id})"></button>
            </div>
        `;

        container.appendChild(notificationEl);
        
        // تحريك دخول الإشعار
        setTimeout(() => notificationEl.classList.add('show'), 10);
    },

    /**
     * الحصول على أيقونة الإشعار
     * Get Notification Icon
     */
    getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'bell';
    },

    /**
     * إزالة الإشعار
     * Remove Notification
     */
    removeNotification(id) {
        const notificationEl = document.querySelector(`[data-notification-id="${id}"]`);
        if (notificationEl) {
            notificationEl.classList.remove('show');
            setTimeout(() => {
                notificationEl.remove();
                this.state.notifications = this.state.notifications.filter(n => n.id !== id);
            }, 300);
        }
    },

    /**
     * الحصول على حاوي الإشعارات
     * Get Notification Container
     */
    getNotificationContainer() {
        let container = document.getElementById('notifications-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notifications-container';
            container.className = 'position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        return container;
    },

    /**
     * طلب AJAX
     * AJAX Request
     */
    async ajax(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': this.config.csrfToken
            },
            credentials: 'same-origin'
        };

        const config = { ...defaultOptions, ...options };
        
        // إضافة البيانات للـ GET requests
        if (config.method === 'GET' && config.data) {
            const params = new URLSearchParams(config.data);
            url += (url.includes('?') ? '&' : '?') + params.toString();
            delete config.data;
        }

        // تحويل البيانات للـ POST requests
        if (config.data && config.method !== 'GET') {
            if (config.data instanceof FormData) {
                delete config.headers['Content-Type'];
                config.body = config.data;
            } else {
                config.body = JSON.stringify(config.data);
            }
            delete config.data;
        }

        try {
            this.showLoading();
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();
            
            // معالجة انتهاء الجلسة
            if (result.session_expired) {
                this.handleSessionExpired();
                return;
            }

            return result;
        } catch (error) {
            console.error('AJAX Error:', error);
            this.showNotification(this.t('network_error'), 'error');
            throw error;
        } finally {
            this.hideLoading();
        }
    },

    /**
     * إظهار شاشة التحميل
     * Show Loading
     */
    showLoading(message = null) {
        this.state.isLoading = true;
        
        let loader = document.getElementById('global-loader');
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'global-loader';
            loader.className = 'global-loader';
            loader.innerHTML = `
                <div class="loader-content">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">${this.t('loading')}</span>
                    </div>
                    <div class="loader-text">${message || this.t('loading')}</div>
                </div>
            `;
            document.body.appendChild(loader);
        }
        
        loader.style.display = 'flex';
        document.body.classList.add('loading');
    },

    /**
     * إخفاء شاشة التحميل
     * Hide Loading
     */
    hideLoading() {
        this.state.isLoading = false;
        
        const loader = document.getElementById('global-loader');
        if (loader) {
            loader.style.display = 'none';
        }
        
        document.body.classList.remove('loading');
    },

    /**
     * نافذة تأكيد
     * Confirmation Dialog
     */
    async confirm(message, title = null) {
        return new Promise((resolve) => {
            const modal = this.createConfirmModal(message, title, resolve);
            document.body.appendChild(modal);
            
            // إظهار النافذة
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            // إزالة النافذة عند الإغلاق
            modal.addEventListener('hidden.bs.modal', () => {
                modal.remove();
            });
        });
    },

    /**
     * إنشاء نافذة التأكيد
     * Create Confirmation Modal
     */
    createConfirmModal(message, title, callback) {
        const modalId = 'confirm-modal-' + Date.now();
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = modalId;
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${title || this.t('confirm')}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="document.getElementById('${modalId}').confirmCallback(false)">
                            ${this.t('cancel')}
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="document.getElementById('${modalId}').confirmCallback(true)">
                            ${this.t('ok')}
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        modal.confirmCallback = callback;
        return modal;
    },

    /**
     * معالج إرسال النماذج
     * Handle Form Submission
     */
    handleFormSubmission(event) {
        const form = event.target;
        
        if (!form.matches('form')) return;
        
        // التحقق من صحة النموذج
        if (!this.validateForm(form)) {
            event.preventDefault();
            return false;
        }
        
        // إضافة رمز CSRF
        this.addCSRFToken(form);
        
        // تعطيل زر الإرسال
        const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            const originalText = submitBtn.textContent;
            submitBtn.textContent = this.t('saving');
            
            // إعادة تفعيل الزر بعد ثانيتين
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }, 2000);
        }
    },

    /**
     * إضافة رمز CSRF
     * Add CSRF Token
     */
    addCSRFToken(form) {
        if (!this.config.csrfToken) return;
        
        let csrfInput = form.querySelector('input[name="csrf_token"]');
        if (!csrfInput) {
            csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            form.appendChild(csrfInput);
        }
        csrfInput.value = this.config.csrfToken;
    },

    /**
     * تتبع التغييرات غير المحفوظة
     * Track Unsaved Changes
     */
    trackUnsavedChanges(event) {
        const target = event.target;
        
        if (target.matches('input, select, textarea') && !target.matches('[data-ignore-changes]')) {
            this.state.unsavedChanges = true;
        }
    },

    /**
     * معالج ما قبل الخروج
     * Handle Before Unload
     */
    handleBeforeUnload(event) {
        if (this.state.unsavedChanges) {
            const message = this.t('unsaved_changes_warning');
            event.returnValue = message;
            return message;
        }
    },

    /**
     * اختصارات لوحة المفاتيح
     * Keyboard Shortcuts
     */
    handleKeyboardShortcuts(event) {
        // Ctrl/Cmd + S للحفظ
        if ((event.ctrlKey || event.metaKey) && event.key === 's') {
            event.preventDefault();
            const form = document.querySelector('form');
            if (form) form.submit();
        }
        
        // ESC لإغلاق النوافذ المنبثقة
        if (event.key === 'Escape') {
            const modal = document.querySelector('.modal.show');
            if (modal) {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) bsModal.hide();
            }
        }
    },

    /**
     * معالج النقر العام
     * Handle Global Click
     */
    handleGlobalClick(event) {
        const target = event.target;
        
        // معالج أزرار الحذف
        if (target.matches('[data-action="delete"]')) {
            event.preventDefault();
            this.handleDeleteAction(target);
        }
        
        // معالج روابط AJAX
        if (target.matches('[data-ajax="true"]')) {
            event.preventDefault();
            this.handleAjaxLink(target);
        }
        
        // معالج النقر خارج القوائم المنسدلة
        if (!target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    },

    /**
     * معالج تغيير حجم النافذة
     * Handle Window Resize
     */
    handleWindowResize() {
        // إعادة حساب أبعاد الجداول
        const tables = document.querySelectorAll('.table-responsive');
        tables.forEach(table => {
            // يمكن إضافة منطق خاص بإعادة حساب الأبعاد
        });
    },

    /**
     * معالج إجراء الحذف
     * Handle Delete Action
     */
    async handleDeleteAction(button) {
        const message = button.dataset.message || this.t('confirm_delete');
        const url = button.dataset.url || button.href;
        
        const confirmed = await this.confirm(message);
        if (confirmed && url) {
            try {
                const result = await this.ajax(url, { method: 'DELETE' });
                
                if (result.success) {
                    this.showNotification(result.message || this.t('deleted_successfully'), 'success');
                    
                    // إزالة العنصر من الصفحة
                    const row = button.closest('tr, .card, .list-item');
                    if (row) {
                        row.remove();
                    } else {
                        location.reload();
                    }
                } else {
                    this.showNotification(result.message || this.t('delete_failed'), 'error');
                }
            } catch (error) {
                this.showNotification(this.t('operation_failed'), 'error');
            }
        }
    },

    /**
     * معالج روابط AJAX
     * Handle AJAX Links
     */
    async handleAjaxLink(link) {
        const url = link.href;
        const method = link.dataset.method || 'GET';
        const target = link.dataset.target;
        
        try {
            const result = await this.ajax(url, { method });
            
            if (target) {
                const targetElement = document.querySelector(target);
                if (targetElement && result.html) {
                    targetElement.innerHTML = result.html;
                }
            }
            
            if (result.message) {
                this.showNotification(result.message, result.success ? 'success' : 'error');
            }
        } catch (error) {
            this.showNotification(this.t('operation_failed'), 'error');
        }
    },

    /**
     * تهيئة القوائم المنسدلة
     * Initialize Dropdowns
     */
    initializeDropdowns() {
        document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const menu = this.nextElementSibling;
                if (menu && menu.classList.contains('dropdown-menu')) {
                    menu.classList.toggle('show');
                }
            });
        });
    },

    /**
     * تهيئة النوافذ المنبثقة
     * Initialize Modals
     */
    initializeModals() {
        // معالج فتح النوافذ عبر البيانات
        document.addEventListener('click', function(e) {
            const trigger = e.target.closest('[data-bs-toggle="modal"]');
            if (trigger) {
                const modalId = trigger.dataset.bsTarget;
                const modal = document.querySelector(modalId);
                if (modal) {
                    const bsModal = new bootstrap.Modal(modal);
                    bsModal.show();
                }
            }
        });
    },

    /**
     * تهيئة التبويبات
     * Initialize Tabs
     */
    initializeTabs() {
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                
                // إزالة الحالة النشطة من جميع التبويبات
                const tabList = this.closest('.nav-tabs, .nav-pills');
                if (tabList) {
                    tabList.querySelectorAll('.nav-link').forEach(link => {
                        link.classList.remove('active');
                    });
                }
                
                // إضافة الحالة النشطة للتبويب الحالي
                this.classList.add('active');
                
                // إظهار المحتوى المطلوب
                const targetId = this.dataset.bsTarget || this.getAttribute('href');
                const targetPane = document.querySelector(targetId);
                if (targetPane) {
                    // إخفاء جميع المحتويات
                    const tabContent = targetPane.closest('.tab-content');
                    if (tabContent) {
                        tabContent.querySelectorAll('.tab-pane').forEach(pane => {
                            pane.classList.remove('show', 'active');
                        });
                    }
                    
                    // إظهار المحتوى المطلوب
                    targetPane.classList.add('show', 'active');
                }
            });
        });
    },

    /**
     * تهيئة التلميحات
     * Initialize Tooltips
     */
    initializeTooltips() {
        // تهيئة تلميحات Bootstrap
        const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipElements.forEach(element => {
            new bootstrap.Tooltip(element);
        });
    },

    /**
     * تهيئة جداول البيانات
     * Initialize Data Tables
     */
    initializeDataTables() {
        document.querySelectorAll('.data-table').forEach(table => {
            // يمكن إضافة مكتبة جداول البيانات هنا
            this.makeTableSortable(table);
            this.addTableSearch(table);
        });
    },

    /**
     * جعل الجدول قابل للترتيب
     * Make Table Sortable
     */
    makeTableSortable(table) {
        const headers = table.querySelectorAll('th[data-sortable="true"]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                this.sortTable(table, header);
            });
        });
    },

    /**
     * ترتيب الجدول
     * Sort Table
     */
    sortTable(table, header) {
        const columnIndex = Array.from(header.parentNode.children).indexOf(header);
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        const isNumeric = header.dataset.type === 'numeric';
        const currentDirection = header.dataset.direction || 'asc';
        const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
        
        rows.sort((a, b) => {
            const cellA = a.children[columnIndex]?.textContent.trim() || '';
            const cellB = b.children[columnIndex]?.textContent.trim() || '';
            
            let comparison = 0;
            if (isNumeric) {
                comparison = parseFloat(cellA) - parseFloat(cellB);
            } else {
                comparison = cellA.localeCompare(cellB);
            }
            
            return newDirection === 'asc' ? comparison : -comparison;
        });
        
        // تحديث ترتيب الصفوف
        rows.forEach(row => tbody.appendChild(row));
        
        // تحديث مؤشر الاتجاه
        header.dataset.direction = newDirection;
        
        // تحديث الأيقونات
        table.querySelectorAll('th .sort-icon').forEach(icon => icon.remove());
        const icon = document.createElement('i');
        icon.className = `fas fa-sort-${newDirection === 'asc' ? 'up' : 'down'} sort-icon ms-1`;
        header.appendChild(icon);
    },

    /**
     * إضافة بحث للجدول
     * Add Table Search
     */
    addTableSearch(table) {
        const searchInput = table.previousElementSibling?.querySelector('.table-search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.filterTable(table, e.target.value);
            });
        }
    },

    /**
     * تصفية الجدول
     * Filter Table
     */
    filterTable(table, searchTerm) {
        const tbody = table.querySelector('tbody');
        const rows = tbody.querySelectorAll('tr');
        const term = searchTerm.toLowerCase();
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    },

    /**
     * تهيئة منتقي التواريخ
     * Initialize Date Pickers
     */
    initializeDatePickers() {
        document.querySelectorAll('.date-picker').forEach(input => {
            // يمكن إضافة مكتبة منتقي التواريخ هنا
            input.type = 'date';
        });
    },

    /**
     * تهيئة رفع الملفات
     * Initialize File Uploads
     */
    initializeFileUploads() {
        document.querySelectorAll('.file-upload').forEach(input => {
            input.addEventListener('change', (e) => {
                this.handleFileSelection(e.target);
            });
        });
        
        // إضافة دعم السحب والإفلات
        document.querySelectorAll('.dropzone').forEach(zone => {
            this.initializeDropzone(zone);
        });
    },

    /**
     * معالج اختيار الملف
     * Handle File Selection
     */
    handleFileSelection(input) {
        const files = input.files;
        const preview = input.nextElementSibling?.querySelector('.file-preview');
        
        if (preview) {
            preview.innerHTML = '';
            
            Array.from(files).forEach(file => {
                const fileItem = this.createFilePreview(file);
                preview.appendChild(fileItem);
            });
        }
        
        // التحقق من حجم الملفات
        Array.from(files).forEach(file => {
            if (file.size > 10 * 1024 * 1024) { // 10MB
                this.showNotification(this.t('file_size_error'), 'error');
                input.value = '';
            }
        });
    },

    /**
     * إنشاء معاينة الملف
     * Create File Preview
     */
    createFilePreview(file) {
        const div = document.createElement('div');
        div.className = 'file-preview-item d-flex align-items-center mb-2';
        
        const icon = this.getFileIcon(file.type);
        const size = this.formatFileSize(file.size);
        
        div.innerHTML = `
            <i class="fas fa-${icon} me-2"></i>
            <span class="file-name me-2">${file.name}</span>
            <span class="file-size text-muted">(${size})</span>
            <button type="button" class="btn btn-sm btn-link text-danger ms-auto" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        return div;
    },

    /**
     * الحصول على أيقونة الملف
     * Get File Icon
     */
    getFileIcon(mimeType) {
        if (mimeType.startsWith('image/')) return 'image';
        if (mimeType.startsWith('video/')) return 'video';
        if (mimeType.startsWith('audio/')) return 'music';
        if (mimeType === 'application/pdf') return 'file-pdf';
        if (mimeType.includes('word')) return 'file-word';
        if (mimeType.includes('excel')) return 'file-excel';
        return 'file';
    },

    /**
     * تنسيق حجم الملف
     * Format File Size
     */
    formatFileSize(bytes) {
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        if (bytes === 0) return '0 Bytes';
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
    },

    /**
     * تهيئة منطقة الإفلات
     * Initialize Dropzone
     */
    initializeDropzone(zone) {
        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.classList.add('drag-over');
        });
        
        zone.addEventListener('dragleave', () => {
            zone.classList.remove('drag-over');
        });
        
        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('drag-over');
            
            const files = e.dataTransfer.files;
            const input = zone.querySelector('input[type="file"]');
            if (input && files.length > 0) {
                input.files = files;
                this.handleFileSelection(input);
            }
        });
    },

    /**
     * تحميل بيانات المستخدم
     * Load User Data
     */
    loadUserData() {
        const userDataEl = document.querySelector('#user-data');
        if (userDataEl) {
            try {
                const userData = JSON.parse(userDataEl.textContent);
                this.config.userId = userData.id;
                this.config.userRole = userData.role;
            } catch (error) {
                console.error('Failed to parse user data:', error);
            }
        }
    },

    /**
     * فحص الجلسة
     * Check Session
     */
    checkSession() {
        // فحص الجلسة كل 5 دقائق
        setInterval(() => {
            this.ajax('/api/session/check')
                .then(result => {
                    if (!result.valid) {
                        this.handleSessionExpired();
                    }
                })
                .catch(() => {
                    // تجاهل أخطاء فحص الجلسة
                });
        }, 5 * 60 * 1000);
    },

    /**
     * معالج انتهاء الجلسة
     * Handle Session Expired
     */
    handleSessionExpired() {
        this.showNotification(this.t('session_expired'), 'warning');
        setTimeout(() => {
            window.location.href = '/login.php';
        }, 3000);
    },

    /**
     * إعداد AJAX الافتراضي
     * Setup AJAX Defaults
     */
    setupAjaxDefaults() {
        // إعداد fetch الافتراضي غير مطلوب في المتصفحات الحديثة
    },

    /**
     * تهيئة السمة
     * Initialize Theme
     */
    initializeTheme() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        this.setTheme(savedTheme);
        
        // معالج تبديل السمة
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="toggle-theme"]')) {
                this.toggleTheme();
            }
        });
    },

    /**
     * تعيين السمة
     * Set Theme
     */
    setTheme(theme) {
        this.config.theme = theme;
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        
        // تحديث أيقونة السمة
        const themeIcon = document.querySelector('.theme-toggle i');
        if (themeIcon) {
            themeIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    },

    /**
     * تبديل السمة
     * Toggle Theme
     */
    toggleTheme() {
        const newTheme = this.config.theme === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
    },

    /**
     * تهيئة اللغة
     * Initialize Language
     */
    initializeLanguage() {
        // معالج تبديل اللغة
        document.addEventListener('click', (e) => {
            const langLink = e.target.closest('[data-lang]');
            if (langLink) {
                e.preventDefault();
                this.switchLanguage(langLink.dataset.lang);
            }
        });
    },

    /**
     * تبديل اللغة
     * Switch Language
     */
    switchLanguage(lang) {
        const url = new URL(window.location);
        url.searchParams.set('lang', lang);
        window.location.href = url.toString();
    },

    /**
     * تهيئة الإشعارات
     * Initialize Notifications
     */
    initializeNotifications() {
        // تحميل الإشعارات المؤجلة
        const flashMessages = document.querySelectorAll('.flash-message');
        flashMessages.forEach(message => {
            const type = message.dataset.type || 'info';
            const text = message.textContent.trim();
            if (text) {
                this.showNotification(text, type);
            }
            message.remove();
        });
    },

    /**
     * تحديد التحقق من النموذج
     * Mark Form as Validated
     */
    markFormValidated(form) {
        this.state.unsavedChanges = false;
        form.classList.add('was-validated');
    },

    /**
     * تنظيف البيانات
     * Clean Up
     */
    cleanup() {
        // تنظيف المؤقتات والأحداث
        this.state.notifications = [];
        document.body.classList.remove('loading');
    }
};

/**
 * تشغيل النظام عند تحميل الصفحة
 * Initialize System on Page Load
 */
document.addEventListener('DOMContentLoaded', () => {
    TadbeerPlus.init();
});

/**
 * تنظيف عند إغلاق الصفحة
 * Cleanup on Page Unload
 */
window.addEventListener('beforeunload', () => {
    TadbeerPlus.cleanup();
});

// تصدير النظام للاستخدام العام
window.TadbeerPlus = TadbeerPlus;
