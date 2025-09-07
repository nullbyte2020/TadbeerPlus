<?php
/**
 * ==============================================
 * رسائل النظام العربية - تدبير بلس
 * Arabic System Messages - Tadbeer Plus
 * ==============================================
 */

return [
    // ==============================================
    // رسائل النجاح
    // ==============================================
    'success_login' => 'تم تسجيل الدخول بنجاح',
    'success_logout' => 'تم تسجيل الخروج بنجاح',
    'success_save' => 'تم الحفظ بنجاح',
    'success_update' => 'تم التحديث بنجاح',
    'success_delete' => 'تم الحذف بنجاح',
    'success_create' => 'تم الإنشاء بنجاح',
    'success_upload' => 'تم رفع الملف بنجاح',
    'success_import' => 'تم الاستيراد بنجاح',
    'success_export' => 'تم التصدير بنجاح',
    'success_email_sent' => 'تم إرسال البريد الإلكتروني بنجاح',
    'success_password_reset' => 'تم إعادة تعيين كلمة المرور بنجاح',
    'success_profile_update' => 'تم تحديث الملف الشخصي بنجاح',
    'success_settings_update' => 'تم تحديث الإعدادات بنجاح',
    'success_backup_created' => 'تم إنشاء النسخة الاحتياطية بنجاح',
    'success_contract_created' => 'تم إنشاء العقد بنجاح',
    'success_payment_processed' => 'تم معالجة الدفع بنجاح',
    'success_worker_assigned' => 'تم تعيين العامل بنجاح',
    'success_status_changed' => 'تم تغيير الحالة بنجاح',
    'success_approval' => 'تم الموافقة على الطلب بنجاح',
    'success_rejection' => 'تم رفض الطلب بنجاح',

    // ==============================================
    // رسائل الخطأ
    // ==============================================
    'error_login_failed' => 'فشل في تسجيل الدخول. يرجى التحقق من البيانات',
    'error_invalid_credentials' => 'بيانات الاعتماد غير صحيحة',
    'error_account_locked' => 'تم قفل الحساب بسبب محاولات تسجيل دخول متعددة',
    'error_access_denied' => 'غير مصرح لك بالوصول إلى هذه الصفحة',
    'error_permission_denied' => 'ليس لديك صلاحية لتنفيذ هذا الإجراء',
    'error_invalid_data' => 'البيانات المدخلة غير صحيحة',
    'error_required_fields' => 'يرجى ملء جميع الحقول المطلوبة',
    'error_database' => 'خطأ في قاعدة البيانات. يرجى المحاولة مرة أخرى',
    'error_network' => 'خطأ في الاتصال بالشبكة',
    'error_server' => 'خطأ في الخادم. يرجى المحاولة لاحقاً',
    'error_file_upload' => 'فشل في رفع الملف',
    'error_file_size' => 'حجم الملف كبير جداً',
    'error_file_type' => 'نوع الملف غير مدعوم',
    'error_duplicate_entry' => 'البيانات مكررة. يرجى التحقق من الإدخال',
    'error_email_exists' => 'البريد الإلكتروني مسجل مسبقاً',
    'error_username_exists' => 'اسم المستخدم موجود بالفعل',
    'error_record_not_found' => 'السجل غير موجود',
    'error_operation_failed' => 'فشلت العملية. يرجى المحاولة مرة أخرى',
    'error_session_expired' => 'انتهت صلاحية الجلسة. يرجى تسجيل الدخول مرة أخرى',
    'error_csrf_token' => 'رمز الأمان غير صحيح',
    'error_maintenance_mode' => 'النظام تحت الصيانة حالياً',

    // ==============================================
    // رسائل التحذير
    // ==============================================
    'warning_unsaved_changes' => 'لديك تغييرات غير محفوظة. هل تريد المتابعة؟',
    'warning_delete_confirm' => 'هل أنت متأكد من حذف هذا العنصر؟',
    'warning_irreversible' => 'هذا الإجراء لا يمكن التراجع عنه',
    'warning_contract_expiry' => 'العقد على وشك الانتهاء',
    'warning_payment_overdue' => 'هناك مدفوعات متأخرة',
    'warning_document_expiry' => 'بعض الوثائق على وشك الانتهاء',
    'warning_low_balance' => 'رصيد الحساب منخفض',
    'warning_quota_exceeded' => 'تم تجاوز الحد المسموح',
    'warning_duplicate_data' => 'قد تكون البيانات مكررة',
    'warning_weak_password' => 'كلمة المرور ضعيفة',

    // ==============================================
    // رسائل المعلومات
    // ==============================================
    'info_welcome' => 'مرحباً بك في نظام تدبير بلس',
    'info_first_login' => 'يرجى تحديث كلمة المرور عند أول تسجيل دخول',
    'info_profile_incomplete' => 'يرجى إكمال بيانات الملف الشخصي',
    'info_email_verification' => 'يرجى تأكيد البريد الإلكتروني',
    'info_backup_recommended' => 'يُنصح بإنشاء نسخة احتياطية',
    'info_update_available' => 'يتوفر تحديث جديد للنظام',
    'info_scheduled_maintenance' => 'صيانة مجدولة للنظام يوم {date}',
    'info_new_features' => 'تم إضافة ميزات جديدة للنظام',
    'info_data_export' => 'سيتم إرسال ملف التصدير إلى بريدك الإلكتروني',
    'info_processing_request' => 'جاري معالجة طلبك...',

    // ==============================================
    // رسائل التأكيد
    // ==============================================
    'confirm_delete' => 'هل أنت متأكد من حذف "{item}"؟',
    'confirm_logout' => 'هل تريد تسجيل الخروج؟',
    'confirm_cancel' => 'هل تريد إلغاء العملية الحالية؟',
    'confirm_submit' => 'هل تريد إرسال النموذج؟',
    'confirm_approve' => 'هل تريد الموافقة على هذا الطلب؟',
    'confirm_reject' => 'هل تريد رفض هذا الطلب؟',
    'confirm_reset' => 'هل تريد إعادة تعيين النموذج؟',
    'confirm_backup' => 'هل تريد إنشاء نسخة احتياطية الآن؟',
    'confirm_restore' => 'هل تريد استعادة النسخة الاحتياطية؟',
    'confirm_archive' => 'هل تريد أرشفة هذا العنصر؟',

    // ==============================================
    // رسائل العمليات الخاصة
    // ==============================================
    'contract_created' => 'تم إنشاء العقد رقم {contract_number} بنجاح',
    'contract_updated' => 'تم تحديث العقد رقم {contract_number}',
    'contract_cancelled' => 'تم إلغاء العقد رقم {contract_number}',
    'worker_assigned' => 'تم تعيين العامل {worker_name} للعميل {client_name}',
    'payment_received' => 'تم استلام دفعة بمبلغ {amount} {currency}',
    'invoice_generated' => 'تم إنشاء فاتورة رقم {invoice_number}',
    'document_uploaded' => 'تم رفع الوثيقة {document_name}',
    'status_changed' => 'تم تغيير الحالة إلى "{status}"',
    'notification_sent' => 'تم إرسال إشعار إلى {recipient}',
    'backup_scheduled' => 'تم جدولة النسخة الاحتياطية ليوم {date}',

    // ==============================================
    // رسائل البريد الإلكتروني
    // ==============================================
    'email_welcome_subject' => 'مرحباً بك في تدبير بلس',
    'email_password_reset_subject' => 'إعادة تعيين كلمة المرور',
    'email_contract_expiry_subject' => 'تنبيه: انتهاء صلاحية العقد',
    'email_payment_due_subject' => 'تذكير: دفعة مستحقة',
    'email_document_expiry_subject' => 'تنبيه: انتهاء صلاحية الوثائق',
    'email_welcome_body' => 'مرحباً {name}، تم إنشاء حسابك بنجاح في نظام تدبير بلس.',
    'email_password_reset_body' => 'لإعادة تعيين كلمة المرور، يرجى النقر على الرابط التالي: {link}',
    'email_contract_expiry_body' => 'ينتهي العقد رقم {contract_number} في تاريخ {expiry_date}',
    'email_payment_due_body' => 'لديك دفعة مستحقة بمبلغ {amount} {currency} في تاريخ {due_date}',

    // ==============================================
    // رسائل الحالة والتقدم
    // ==============================================
    'status_pending' => 'في انتظار المعالجة',
    'status_processing' => 'قيد المعالجة',
    'status_completed' => 'تم الإنجاز',
    'status_failed' => 'فشل',
    'status_cancelled' => 'تم الإلغاء',
    'progress_uploading' => 'جاري الرفع... {percentage}%',
    'progress_processing' => 'جاري المعالجة... {percentage}%',
    'progress_completing' => 'جاري الإنهاء... {percentage}%',

    // ==============================================
    // رسائل النظام المتقدمة
    // ==============================================
    'system_online' => 'النظام يعمل بشكل طبيعي',
    'system_maintenance' => 'النظام تحت الصيانة',
    'system_error' => 'خطأ في النظام',
    'database_connected' => 'متصل بقاعدة البيانات',
    'database_error' => 'خطأ في الاتصال بقاعدة البيانات',
    'cache_cleared' => 'تم مسح ذاكرة التخزين المؤقت',
    'logs_cleared' => 'تم مسح ملفات السجل',
    'backup_completed' => 'تمت النسخة الاحتياطية بنجاح',
    'backup_failed' => 'فشلت النسخة الاحتياطية',
    'update_available' => 'يتوفر تحديث جديد',
    'update_installed' => 'تم تثبيت التحديث بنجاح',

    // ==============================================
    // رسائل التفاعل مع المستخدم
    // ==============================================
    'please_wait' => 'يرجى الانتظار...',
    'loading_data' => 'جاري تحميل البيانات...',
    'saving_changes' => 'جاري حفظ التغييرات...',
    'processing_request' => 'جاري معالجة الطلب...',
    'generating_report' => 'جاري إنشاء التقرير...',
    'sending_email' => 'جاري إرسال البريد الإلكتروني...',
    'uploading_file' => 'جاري رفع الملف...',
    'connecting' => 'جاري الاتصال...',
    'redirecting' => 'جاري التوجيه...',
    'refreshing' => 'جاري التحديث...',

    // ==============================================
    // رسائل متنوعة
    // ==============================================
    'coming_soon' => 'قريباً...',
    'under_construction' => 'تحت الإنشاء',
    'feature_disabled' => 'هذه الميزة غير مفعلة حالياً',
    'premium_feature' => 'هذه ميزة مدفوعة',
    'demo_mode' => 'وضع التجربة',
    'read_only_mode' => 'وضع القراءة فقط',
    'maintenance_scheduled' => 'صيانة مجدولة',
    'service_unavailable' => 'الخدمة غير متاحة حالياً',
    'try_again_later' => 'يرجى المحاولة لاحقاً',
    'contact_support' => 'يرجى التواصل مع الدعم الفني',
];
