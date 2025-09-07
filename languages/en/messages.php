<?php
/**
 * ==============================================
 * English System Messages - Tadbeer Plus
 * رسائل النظام الإنجليزية - تدبير بلس
 * ==============================================
 */

return [
    // ==============================================
    // Success Messages
    // ==============================================
    'success_login' => 'Successfully logged in',
    'success_logout' => 'Successfully logged out',
    'success_save' => 'Successfully saved',
    'success_update' => 'Successfully updated',
    'success_delete' => 'Successfully deleted',
    'success_create' => 'Successfully created',
    'success_upload' => 'File uploaded successfully',
    'success_import' => 'Data imported successfully',
    'success_export' => 'Data exported successfully',
    'success_email_sent' => 'Email sent successfully',
    'success_password_reset' => 'Password reset successfully',
    'success_profile_update' => 'Profile updated successfully',
    'success_settings_update' => 'Settings updated successfully',
    'success_backup_created' => 'Backup created successfully',
    'success_contract_created' => 'Contract created successfully',
    'success_payment_processed' => 'Payment processed successfully',
    'success_worker_assigned' => 'Worker assigned successfully',
    'success_status_changed' => 'Status changed successfully',
    'success_approval' => 'Request approved successfully',
    'success_rejection' => 'Request rejected successfully',

    // ==============================================
    // Error Messages
    // ==============================================
    'error_login_failed' => 'Login failed. Please check your credentials',
    'error_invalid_credentials' => 'Invalid credentials provided',
    'error_account_locked' => 'Account locked due to multiple failed login attempts',
    'error_access_denied' => 'Access denied to this page',
    'error_permission_denied' => 'You do not have permission to perform this action',
    'error_invalid_data' => 'Invalid data provided',
    'error_required_fields' => 'Please fill all required fields',
    'error_database' => 'Database error. Please try again',
    'error_network' => 'Network connection error',
    'error_server' => 'Server error. Please try again later',
    'error_file_upload' => 'File upload failed',
    'error_file_size' => 'File size is too large',
    'error_file_type' => 'File type not supported',
    'error_duplicate_entry' => 'Duplicate entry. Please check your input',
    'error_email_exists' => 'Email already exists',
    'error_username_exists' => 'Username already exists',
    'error_record_not_found' => 'Record not found',
    'error_operation_failed' => 'Operation failed. Please try again',
    'error_session_expired' => 'Session expired. Please login again',
    'error_csrf_token' => 'Invalid security token',
    'error_maintenance_mode' => 'System is currently under maintenance',

    // ==============================================
    // Warning Messages
    // ==============================================
    'warning_unsaved_changes' => 'You have unsaved changes. Do you want to continue?',
    'warning_delete_confirm' => 'Are you sure you want to delete this item?',
    'warning_irreversible' => 'This action cannot be undone',
    'warning_contract_expiry' => 'Contract is about to expire',
    'warning_payment_overdue' => 'Payment is overdue',
    'warning_document_expiry' => 'Some documents are about to expire',
    'warning_low_balance' => 'Account balance is low',
    'warning_quota_exceeded' => 'Quota exceeded',
    'warning_duplicate_data' => 'Data may be duplicated',
    'warning_weak_password' => 'Password is weak',

    // ==============================================
    // Information Messages
    // ==============================================
    'info_welcome' => 'Welcome to Tadbeer Plus System',
    'info_first_login' => 'Please update your password on first login',
    'info_profile_incomplete' => 'Please complete your profile information',
    'info_email_verification' => 'Please verify your email address',
    'info_backup_recommended' => 'Regular backup is recommended',
    'info_update_available' => 'System update is available',
    'info_scheduled_maintenance' => 'Scheduled maintenance on {date}',
    'info_new_features' => 'New features have been added to the system',
    'info_data_export' => 'Export file will be sent to your email',
    'info_processing_request' => 'Processing your request...',

    // ==============================================
    // Confirmation Messages
    // ==============================================
    'confirm_delete' => 'Are you sure you want to delete "{item}"?',
    'confirm_logout' => 'Do you want to logout?',
    'confirm_cancel' => 'Do you want to cancel the current operation?',
    'confirm_submit' => 'Do you want to submit the form?',
    'confirm_approve' => 'Do you want to approve this request?',
    'confirm_reject' => 'Do you want to reject this request?',
    'confirm_reset' => 'Do you want to reset the form?',
    'confirm_backup' => 'Do you want to create a backup now?',
    'confirm_restore' => 'Do you want to restore from backup?',
    'confirm_archive' => 'Do you want to archive this item?',

    // ==============================================
    // Special Operation Messages
    // ==============================================
    'contract_created' => 'Contract #{contract_number} created successfully',
    'contract_updated' => 'Contract #{contract_number} updated',
    'contract_cancelled' => 'Contract #{contract_number} cancelled',
    'worker_assigned' => 'Worker {worker_name} assigned to client {client_name}',
    'payment_received' => 'Payment of {amount} {currency} received',
    'invoice_generated' => 'Invoice #{invoice_number} generated',
    'document_uploaded' => 'Document {document_name} uploaded',
    'status_changed' => 'Status changed to "{status}"',
    'notification_sent' => 'Notification sent to {recipient}',
    'backup_scheduled' => 'Backup scheduled for {date}',

    // ==============================================
    // Email Messages
    // ==============================================
    'email_welcome_subject' => 'Welcome to Tadbeer Plus',
    'email_password_reset_subject' => 'Password Reset Request',
    'email_contract_expiry_subject' => 'Alert: Contract Expiry',
    'email_payment_due_subject' => 'Reminder: Payment Due',
    'email_document_expiry_subject' => 'Alert: Document Expiry',
    'email_welcome_body' => 'Welcome {name}, your account has been created successfully in Tadbeer Plus system.',
    'email_password_reset_body' => 'To reset your password, please click the following link: {link}',
    'email_contract_expiry_body' => 'Contract #{contract_number} expires on {expiry_date}',
    'email_payment_due_body' => 'You have a payment due of {amount} {currency} on {due_date}',

    // ==============================================
    // Status and Progress Messages
    // ==============================================
    'status_pending' => 'Pending processing',
    'status_processing' => 'Processing',
    'status_completed' => 'Completed',
    'status_failed' => 'Failed',
    'status_cancelled' => 'Cancelled',
    'progress_uploading' => 'Uploading... {percentage}%',
    'progress_processing' => 'Processing... {percentage}%',
    'progress_completing' => 'Completing... {percentage}%',

    // ==============================================
    // Advanced System Messages
    // ==============================================
    'system_online' => 'System is online',
    'system_maintenance' => 'System under maintenance',
    'system_error' => 'System error',
    'database_connected' => 'Database connected',
    'database_error' => 'Database connection error',
    'cache_cleared' => 'Cache cleared',
    'logs_cleared' => 'Log files cleared',
    'backup_completed' => 'Backup completed successfully',
    'backup_failed' => 'Backup failed',
    'update_available' => 'Update available',
    'update_installed' => 'Update installed successfully',

    // ==============================================
    // User Interaction Messages
    // ==============================================
    'please_wait' => 'Please wait...',
    'loading_data' => 'Loading data...',
    'saving_changes' => 'Saving changes...',
    'processing_request' => 'Processing request...',
    'generating_report' => 'Generating report...',
    'sending_email' => 'Sending email...',
    'uploading_file' => 'Uploading file...',
    'connecting' => 'Connecting...',
    'redirecting' => 'Redirecting...',
    'refreshing' => 'Refreshing...',

    // ==============================================
    // Miscellaneous Messages
    // ==============================================
    'coming_soon' => 'Coming Soon...',
    'under_construction' => 'Under Construction',
    'feature_disabled' => 'This feature is currently disabled',
    'premium_feature' => 'This is a premium feature',
    'demo_mode' => 'Demo Mode',
    'read_only_mode' => 'Read-only Mode',
    'maintenance_scheduled' => 'Scheduled Maintenance',
    'service_unavailable' => 'Service currently unavailable',
    'try_again_later' => 'Please try again later',
    'contact_support' => 'Please contact technical support',
];
