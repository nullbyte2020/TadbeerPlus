<?php
/**
 * ==============================================
 * English Validation Messages - Tadbeer Plus
 * رسائل التحقق الإنجليزية - تدبير بلس
 * ==============================================
 */

return [
    // ==============================================
    // Basic Validation Messages
    // ==============================================
    'required' => 'The {field} field is required',
    'email' => 'The {field} field must be a valid email address',
    'min_length' => 'The {field} field must be at least {min} characters',
    'max_length' => 'The {field} field must not exceed {max} characters',
    'numeric' => 'The {field} field must contain only numbers',
    'alpha' => 'The {field} field must contain only letters',
    'alpha_numeric' => 'The {field} field must contain only letters and numbers',
    'url' => 'The {field} field must be a valid URL',
    'date' => 'The {field} field must be a valid date',
    'phone' => 'The {field} field must be a valid phone number',
    'unique' => 'The {field} field already exists',
    'confirmed' => 'The {field} field confirmation does not match',
    'min_value' => 'The {field} field must be at least {min}',
    'max_value' => 'The {field} field must not exceed {max}',
    'between' => 'The {field} field must be between {min} and {max}',
    'in' => 'The {field} field must be one of: {values}',
    'not_in' => 'The {field} field must not be one of: {values}',
    'regex' => 'The {field} field format is invalid',

    // ==============================================
    // File Validation Messages
    // ==============================================
    'file_required' => 'Please select a file',
    'file_size' => 'File size must not exceed {max} MB',
    'file_type' => 'File type not supported. Supported types: {types}',
    'image_required' => 'The file must be an image',
    'image_dimensions' => 'Image dimensions are not suitable',
    'image_min_width' => 'Image width must be at least {min} pixels',
    'image_max_width' => 'Image width must not exceed {max} pixels',
    'image_min_height' => 'Image height must be at least {min} pixels',
    'image_max_height' => 'Image height must not exceed {max} pixels',

    // ==============================================
    // Password Validation Messages
    // ==============================================
    'password_min_length' => 'Password must be at least {min} characters',
    'password_complexity' => 'Password must contain uppercase, lowercase, numbers and symbols',
    'password_uppercase' => 'Password must contain at least one uppercase letter',
    'password_lowercase' => 'Password must contain at least one lowercase letter',
    'password_number' => 'Password must contain at least one number',
    'password_special' => 'Password must contain at least one special character',
    'password_common' => 'Password is too common, please choose another',
    'password_match_old' => 'New password must be different from the old one',

    // ==============================================
    // System-Specific Validation
    // ==============================================
    'emirates_id' => 'Emirates ID number is invalid',
    'passport_number' => 'Passport number is invalid',
    'visa_number' => 'Visa number is invalid',
    'contract_date_range' => 'Contract end date must be after start date',
    'birth_date' => 'Birth date is invalid',
    'future_date' => 'The {field} field cannot be in the future',
    'past_date' => 'The {field} field cannot be in the past',
    'working_age' => 'Age must be between 21 and 60 years',
    'salary_range' => 'Salary must be between {min} and {max}',
    'experience_years' => 'Years of experience cannot exceed age',
    'phone_uae' => 'Phone number must be a valid UAE number',
    'iban' => 'IBAN number is invalid',

    // ==============================================
    // Financial Data Validation
    // ==============================================
    'amount_positive' => 'Amount must be greater than zero',
    'currency_code' => 'Currency code is invalid',
    'vat_rate' => 'VAT rate is invalid',
    'payment_method' => 'Payment method is invalid',
    'account_number' => 'Account number is invalid',
    'transaction_amount' => 'Transaction amount is invalid',

    // ==============================================
    // Date Validation Messages
    // ==============================================
    'date_format' => 'Date format is invalid. Use {format}',
    'start_date' => 'Start date is required',
    'end_date' => 'End date is required',
    'date_before' => 'The {field} field must be before {date}',
    'date_after' => 'The {field} field must be after {date}',
    'date_range_invalid' => 'Date range is invalid',
    'contract_duration' => 'Contract duration must be between {min} and {max} months',
    'probation_period' => 'Probation period cannot exceed 6 months',

    // ==============================================
    // Status Validation Messages
    // ==============================================
    'invalid_status' => 'Status is invalid',
    'status_transition' => 'Cannot change status from {from} to {to}',
    'worker_available' => 'Worker is not currently available',
    'client_active' => 'Client is not active',
    'contract_active' => 'Contract is not active',

    // ==============================================
    // Permission Validation Messages
    // ==============================================
    'permission_denied' => 'You do not have permission to perform this action',
    'role_required' => 'Role is required',
    'invalid_role' => 'Role is invalid',
    'access_level' => 'Access level is insufficient',

    // ==============================================
    // Relationship Validation Messages
    // ==============================================
    'client_exists' => 'Client does not exist',
    'worker_exists' => 'Worker does not exist',
    'contract_exists' => 'Contract does not exist',
    'user_exists' => 'User does not exist',
    'department_exists' => 'Department does not exist',
    'role_exists' => 'Role does not exist',
    'related_records' => 'Cannot delete record due to related records',

    // ==============================================
    // Custom Form Validation Messages
    // ==============================================
    'contract_form' => [
        'client_required' => 'Client must be selected',
        'worker_required' => 'Worker must be selected',
        'salary_required' => 'Salary is required',
        'duration_required' => 'Contract duration is required',
        'start_date_required' => 'Contract start date is required',
    ],
    
    'user_form' => [
        'username_unique' => 'Username already exists',
        'email_unique' => 'Email already registered',
        'phone_unique' => 'Phone number already registered',
        'role_valid' => 'Role is invalid',
    ],
    
    'payment_form' => [
        'amount_required' => 'Payment amount is required',
        'method_required' => 'Payment method is required',
        'reference_required' => 'Reference number is required',
        'date_required' => 'Payment date is required',
    ],

    // ==============================================
    // Advanced Validation Messages
    // ==============================================
    'business_rules' => [
        'max_contracts_per_client' => 'Client has exceeded maximum allowed contracts',
        'worker_already_assigned' => 'Worker is already assigned to another contract',
        'contract_overlapping' => 'Contract conflicts with another contract',
        'payment_exceeds_balance' => 'Payment amount exceeds due balance',
        'document_expired' => 'Document has expired',
        'visa_expired' => 'Visa has expired',
    ],

    // ==============================================
    // Security and Protection Messages
    // ==============================================
    'security' => [
        'csrf_token_invalid' => 'Security token is invalid',
        'session_expired' => 'Session has expired',
        'too_many_attempts' => 'Too many attempts, please try again later',
        'ip_blocked' => 'Your IP address has been blocked',
        'suspicious_activity' => 'Suspicious activity detected',
    ],

    // ==============================================
    // UAE-Specific Validation
    // ==============================================
    'uae_specific' => [
        'emirates_id_format' => 'Emirates ID must be 15 digits',
        'emirates_id_checksum' => 'Emirates ID is mathematically invalid',
        'trade_license' => 'Trade license number is invalid',
        'establishment_card' => 'Establishment card number is invalid',
        'labor_card' => 'Labor card number is invalid',
    ],

    // ==============================================
    // Field Names for Validation
    // ==============================================
    'field_names' => [
        'username' => 'Username',
        'email' => 'Email',
        'password' => 'Password',
        'full_name' => 'Full Name',
        'phone' => 'Phone Number',
        'emirates_id' => 'Emirates ID',
        'passport_number' => 'Passport Number',
        'birth_date' => 'Birth Date',
        'nationality' => 'Nationality',
        'gender' => 'Gender',
        'address' => 'Address',
        'salary' => 'Salary',
        'start_date' => 'Start Date',
        'end_date' => 'End Date',
        'amount' => 'Amount',
        'description' => 'Description',
        'notes' => 'Notes',
        'status' => 'Status',
        'type' => 'Type',
        'category' => 'Category',
        'priority' => 'Priority',
    ],
];
