<?php
/**
 * ==============================================
 * نظام تدبير بلس - دوال العقود
 * Tadbeer Plus System - Contract Functions
 * ==============================================
 */

// منع الوصول المباشر
if (!defined('BASE_PATH')) {
    die('Direct access not permitted');
}

/**
 * ==============================================
 * دوال إنشاء وإدارة العقود
 * Contract Creation and Management Functions
 * ==============================================
 */

/**
 * إنشاء عقد جديد
 * Create New Contract
 */
function createContract($contractData) {
    $db = getDB();
    
    try {
        $db->beginTransaction();
        
        // التحقق من صحة البيانات
        $validation = validateContractData($contractData);
        if (!$validation['is_valid']) {
            throw new Exception(implode(', ', $validation['errors']));
        }
        
        // إنشاء رقم العقد
        $contractNumber = generateContractNumber();
        
        // حساب تاريخ انتهاء العقد
        $endDate = calculateContractEndDate($contractData['start_date'], $contractData['duration_months']);
        
        // حساب التكاليف
        $costs = calculateContractCosts($contractData);
        
        // إدراج العقد
        $sql = "INSERT INTO contracts (
            contract_number, client_id, worker_id, contract_type, 
            start_date, end_date, duration_months, probation_period_days,
            salary_amount, salary_currency, overtime_rate,
            accommodation_type, accommodation_allowance, food_allowance,
            transportation_allowance, communication_allowance,
            medical_insurance, medical_insurance_coverage,
            annual_ticket, annual_ticket_destination,
            work_hours_per_day, work_days_per_week, rest_day_per_week,
            annual_leave_days, sick_leave_days, emergency_leave_days,
            job_description, special_conditions, contract_terms,
            total_contract_value, monthly_client_fee, annual_contract_fee,
            insurance_deposit, government_fees, agency_fees,
            status, created_by, created_at
        ) VALUES (
            :contract_number, :client_id, :worker_id, :contract_type,
            :start_date, :end_date, :duration_months, :probation_period_days,
            :salary_amount, :salary_currency, :overtime_rate,
            :accommodation_type, :accommodation_allowance, :food_allowance,
            :transportation_allowance, :communication_allowance,
            :medical_insurance, :medical_insurance_coverage,
            :annual_ticket, :annual_ticket_destination,
            :work_hours_per_day, :work_days_per_week, :rest_day_per_week,
            :annual_leave_days, :sick_leave_days, :emergency_leave_days,
            :job_description, :special_conditions, :contract_terms,
            :total_contract_value, :monthly_client_fee, :annual_contract_fee,
            :insurance_deposit, :government_fees, :agency_fees,
            :status, :created_by, NOW()
        )";
        
        $params = array_merge($contractData, [
            'contract_number' => $contractNumber,
            'end_date' => $endDate,
            'salary_currency' => $contractData['salary_currency'] ?? 'AED',
            'status' => 'مسودة',
            'created_by' => $_SESSION['user_id'],
            'total_contract_value' => $costs['total_contract_value'],
            'monthly_client_fee' => $costs['monthly_client_fee'],
            'annual_contract_fee' => $costs['annual_contract_fee'],
            'insurance_deposit' => $costs['insurance_deposit'],
            'government_fees' => $costs['government_fees'],
            'agency_fees' => $costs['agency_fees']
        ]);
        
        $contractId = $db->insert($sql, $params);
        
        if (!$contractId) {
            throw new Exception('Failed to create contract');
        }
        
        // تحديث حالة العامل
        updateWorkerStatus($contractData['worker_id'], 'في العرض');
        
        // تسجيل النشاط
        logActivity('contract_created', "Contract {$contractNumber} created", 
                   $_SESSION['user_id'], $contractId, 'contract');
        
        $db->commit();
        
        return [
            'success' => true,
            'contract_id' => $contractId,
            'contract_number' => $contractNumber,
            'message' => __('success_contract_created')
        ];
        
    } catch (Exception $e) {
        $db->rollback();
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * إنشاء رقم عقد فريد
 * Generate Unique Contract Number
 */
function generateContractNumber($prefix = 'CON') {
    $db = getDB();
    
    do {
        $number = $prefix . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $exists = $db->selectOne("SELECT id FROM contracts WHERE contract_number = ?", [$number]);
    } while ($exists);
    
    return $number;
}

/**
 * حساب تاريخ انتهاء العقد
 * Calculate Contract End Date
 */
function calculateContractEndDate($startDate, $durationMonths) {
    $start = new DateTime($startDate);
    $start->add(new DateInterval("P{$durationMonths}M"));
    return $start->format('Y-m-d');
}

/**
 * حساب تكاليف العقد
 * Calculate Contract Costs
 */
function calculateContractCosts($contractData) {
    $salary = $contractData['salary_amount'];
    $duration = $contractData['duration_months'];
    
    // الرسوم الأساسية
    $basicContractFee = getSetting('basic_contract_fee', 3000);
    $insuranceDeposit = getSetting('insurance_deposit', 2000);
    $governmentFees = getSetting('government_fees', 500);
    $agencyFees = getSetting('agency_fees', 1000);
    
    // حساب الرسوم الشهرية
    $monthlyClientFee = $salary + ($contractData['accommodation_allowance'] ?? 0) + 
                        ($contractData['food_allowance'] ?? 0) + 
                        ($contractData['transportation_allowance'] ?? 0);
    
    // حساب الرسوم السنوية
    $annualContractFee = $basicContractFee + $insuranceDeposit + 
                        $governmentFees + $agencyFees;
    
    // إضافة رسوم التأمين الطبي إن وجد
    if (isset($contractData['medical_insurance']) && $contractData['medical_insurance']) {
        $medicalInsuranceCost = getSetting('medical_insurance_cost', 1000);
        $annualContractFee += $medicalInsuranceCost;
    }
    
    // إضافة رسوم التذكرة السنوية إن وجدت
    if (isset($contractData['annual_ticket']) && $contractData['annual_ticket']) {
        $ticketCost = getSetting('annual_ticket_cost', 1500);
        $annualContractFee += $ticketCost;
    }
    
    // حساب المجموع الكلي
    $totalContractValue = ($monthlyClientFee * $duration) + $annualContractFee;
    
    // إضافة ضريبة القيمة المضافة
    $vatRate = getSetting('vat_rate', 0.05);
    $totalContractValue += ($totalContractValue * $vatRate);
    
    return [
        'monthly_client_fee' => $monthlyClientFee,
        'annual_contract_fee' => $annualContractFee,
        'insurance_deposit' => $insuranceDeposit,
        'government_fees' => $governmentFees,
        'agency_fees' => $agencyFees,
        'total_contract_value' => $totalContractValue,
        'vat_amount' => ($totalContractValue * $vatRate),
        'total_with_vat' => $totalContractValue
    ];
}

/**
 * التحقق من صحة بيانات العقد
 * Validate Contract Data
 */
function validateContractData($data) {
    $errors = [];
    
    // التحقق من الحقول المطلوبة
    $requiredFields = [
        'client_id' => __('client_required'),
        'worker_id' => __('worker_required'),
        'contract_type' => __('contract_type_required'),
        'start_date' => __('start_date_required'),
        'duration_months' => __('duration_required'),
        'salary_amount' => __('salary_required')
    ];
    
    foreach ($requiredFields as $field => $message) {
        if (empty($data[$field])) {
            $errors[] = $message;
        }
    }
    
    // التحقق من وجود العميل
    if (!empty($data['client_id'])) {
        $client = getSingleRow('clients', ['id' => $data['client_id'], 'status' => 'نشط']);
        if (!$client) {
            $errors[] = __('client_not_found');
        }
    }
    
    // التحقق من وجود العامل وتوفره
    if (!empty($data['worker_id'])) {
        $worker = getSingleRow('workers', ['id' => $data['worker_id']]);
        if (!$worker) {
            $errors[] = __('worker_not_found');
        } elseif ($worker['status'] !== 'متاح') {
            $errors[] = __('worker_not_available');
        }
    }
    
    // التحقق من تاريخ البداية
    if (!empty($data['start_date'])) {
        $startDate = new DateTime($data['start_date']);
        $today = new DateTime();
        
        if ($startDate < $today) {
            $errors[] = __('start_date_past');
        }
    }
    
    // التحقق من مدة العقد
    if (!empty($data['duration_months'])) {
        $duration = (int)$data['duration_months'];
        if ($duration < 6 || $duration > 36) {
            $errors[] = __('invalid_contract_duration');
        }
    }
    
    // التحقق من الراتب
    if (!empty($data['salary_amount'])) {
        $salary = (float)$data['salary_amount'];
        $minSalary = getSetting('min_salary', 1000);
        $maxSalary = getSetting('max_salary', 10000);
        
        if ($salary < $minSalary || $salary > $maxSalary) {
            $errors[] = __('invalid_salary_range', [
                'min' => $minSalary,
                'max' => $maxSalary
            ]);
        }
    }
    
    return [
        'is_valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * تحديث حالة العامل
 * Update Worker Status
 */
function updateWorkerStatus($workerId, $status, $notes = null) {
    $db = getDB();
    
    $sql = "UPDATE workers SET status = :status, notes = :notes, updated_at = NOW() 
            WHERE id = :worker_id";
    
    $params = [
        'status' => $status,
        'notes' => $notes,
        'worker_id' => $workerId
    ];
    
    return $db->update($sql, $params);
}

/**
 * الموافقة على العقد
 * Approve Contract
 */
function approveContract($contractId, $approvedBy = null) {
    $db = getDB();
    
    try {
        $db->beginTransaction();
        
        // الحصول على بيانات العقد
        $contract = getContractById($contractId);
        if (!$contract) {
            throw new Exception('Contract not found');
        }
        
        if ($contract['status'] !== 'مسودة') {
            throw new Exception('Contract cannot be approved in current status');
        }
        
        // تحديث حالة العقد
        $sql = "UPDATE contracts SET 
                status = 'نشط', 
                approved_by = :approved_by, 
                approved_at = NOW(),
                updated_at = NOW()
                WHERE id = :contract_id";
        
        $params = [
            'approved_by' => $approvedBy ?: $_SESSION['user_id'],
            'contract_id' => $contractId
        ];
        
        $db->update($sql, $params);
        
        // تحديث حالة العامل
        updateWorkerStatus($contract['worker_id'], 'مكفول', 'Sponsored under contract ' . $contract['contract_number']);
        
        // إنشاء فاتورة العقد
        createContractInvoice($contractId);
        
        // إرسال إشعار للعميل
        sendContractApprovalNotification($contract);
        
        // تسجيل النشاط
        logActivity('contract_approved', "Contract {$contract['contract_number']} approved", 
                   $approvedBy ?: $_SESSION['user_id'], $contractId, 'contract');
        
        $db->commit();
        
        return [
            'success' => true,
            'message' => __('contract_approved_successfully')
        ];
        
    } catch (Exception $e) {
        $db->rollback();
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * إلغاء العقد
 * Cancel Contract
 */
function cancelContract($contractId, $reason, $cancelledBy = null) {
    $db = getDB();
    
    try {
        $db->beginTransaction();
        
        // الحصول على بيانات العقد
        $contract = getContractById($contractId);
        if (!$contract) {
            throw new Exception('Contract not found');
        }
        
        // تحديث حالة العقد
        $sql = "UPDATE contracts SET 
                status = 'ملغي', 
                cancellation_reason = :reason,
                cancelled_by = :cancelled_by, 
                cancelled_at = NOW(),
                updated_at = NOW()
                WHERE id = :contract_id";
        
        $params = [
            'reason' => $reason,
            'cancelled_by' => $cancelledBy ?: $_SESSION['user_id'],
            'contract_id' => $contractId
        ];
        
        $db->update($sql, $params);
        
        // تحرير العامل
        updateWorkerStatus($contract['worker_id'], 'متاح', 'Released from cancelled contract');
        
        // إلغاء الفواتير غير المدفوعة
        cancelUnpaidInvoices($contractId);
        
        // تسجيل النشاط
        logActivity('contract_cancelled', "Contract {$contract['contract_number']} cancelled: {$reason}", 
                   $cancelledBy ?: $_SESSION['user_id'], $contractId, 'contract');
        
        $db->commit();
        
        return [
            'success' => true,
            'message' => __('contract_cancelled_successfully')
        ];
        
    } catch (Exception $e) {
        $db->rollback();
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * تجديد العقد
 * Renew Contract
 */
function renewContract($contractId, $renewalData) {
    $db = getDB();
    
    try {
        $db->beginTransaction();
        
        // الحصول على العقد الأصلي
        $originalContract = getContractById($contractId);
        if (!$originalContract) {
            throw new Exception('Original contract not found');
        }
        
        // إنشاء عقد جديد مع البيانات المحدثة
        $newContractData = array_merge($originalContract, $renewalData);
        $newContractData['start_date'] = $renewalData['start_date'];
        unset($newContractData['id'], $newContractData['contract_number']);
        
        $result = createContract($newContractData);
        
        if (!$result['success']) {
            throw new Exception($result['message']);
        }
        
        // تحديث العقد الأصلي
        $sql = "UPDATE contracts SET 
                status = 'منتهي', 
                renewal_contract_id = :renewal_id,
                updated_at = NOW()
                WHERE id = :contract_id";
        
        $db->update($sql, [
            'renewal_id' => $result['contract_id'],
            'contract_id' => $contractId
        ]);
        
        // تسجيل النشاط
        logActivity('contract_renewed', 
                   "Contract {$originalContract['contract_number']} renewed as {$result['contract_number']}", 
                   $_SESSION['user_id'], $contractId, 'contract');
        
        $db->commit();
        
        return [
            'success' => true,
            'new_contract_id' => $result['contract_id'],
            'new_contract_number' => $result['contract_number'],
            'message' => __('contract_renewed_successfully')
        ];
        
    } catch (Exception $e) {
        $db->rollback();
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * ==============================================
 * دوال العقود المساعدة
 * Contract Helper Functions
 * ==============================================
 */

/**
 * الحصول على عقد بالمعرف
 * Get Contract by ID
 */
function getContractById($contractId) {
    $db = getDB();
    
    $sql = "SELECT c.*, cl.full_name_ar as client_name, w.full_name_ar as worker_name,
                   cl.emirates_id as client_emirates_id, w.passport_number as worker_passport
            FROM contracts c
            LEFT JOIN clients cl ON c.client_id = cl.id
            LEFT JOIN workers w ON c.worker_id = w.id
            WHERE c.id = :contract_id";
    
    return $db->selectOne($sql, ['contract_id' => $contractId]);
}

/**
 * البحث في العقود
 * Search Contracts
 */
function searchContracts($filters = [], $limit = 25, $offset = 0) {
    $db = getDB();
    
    $whereConditions = [];
    $params = [];
    
    // فلترة حسب حالة العقد
    if (!empty($filters['status'])) {
        $whereConditions[] = "c.status = :status";
        $params['status'] = $filters['status'];
    }
    
    // فلترة حسب العميل
    if (!empty($filters['client_id'])) {
        $whereConditions[] = "c.client_id = :client_id";
        $params['client_id'] = $filters['client_id'];
    }
    
    // فلترة حسب العامل
    if (!empty($filters['worker_id'])) {
        $whereConditions[] = "c.worker_id = :worker_id";
        $params['worker_id'] = $filters['worker_id'];
    }
    
    // فلترة حسب نوع العقد
    if (!empty($filters['contract_type'])) {
        $whereConditions[] = "c.contract_type = :contract_type";
        $params['contract_type'] = $filters['contract_type'];
    }
    
    // فلترة حسب التاريخ
    if (!empty($filters['date_from'])) {
        $whereConditions[] = "c.start_date >= :date_from";
        $params['date_from'] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $whereConditions[] = "c.start_date <= :date_to";
        $params['date_to'] = $filters['date_to'];
    }
    
    // البحث النصي
    if (!empty($filters['search'])) {
        $whereConditions[] = "(c.contract_number LIKE :search OR 
                              cl.full_name_ar LIKE :search OR 
                              w.full_name_ar LIKE :search)";
        $params['search'] = '%' . $filters['search'] . '%';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $sql = "SELECT c.*, 
                   cl.full_name_ar as client_name, 
                   w.full_name_ar as worker_name,
                   w.profession as worker_profession
            FROM contracts c
            LEFT JOIN clients cl ON c.client_id = cl.id
            LEFT JOIN workers w ON c.worker_id = w.id
            $whereClause
            ORDER BY c.created_at DESC
            LIMIT :limit OFFSET :offset";
    
    $params['limit'] = $limit;
    $params['offset'] = $offset;
    
    return $db->select($sql, $params);
}

/**
 * إحصائيات العقود
 * Contract Statistics
 */
function getContractStatistics($filters = []) {
    $db = getDB();
    
    $whereConditions = [];
    $params = [];
    
    // تطبيق المرشحات
    if (!empty($filters['date_from'])) {
        $whereConditions[] = "created_at >= :date_from";
        $params['date_from'] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $whereConditions[] = "created_at <= :date_to";
        $params['date_to'] = $filters['date_to'];
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $sql = "SELECT 
                COUNT(*) as total_contracts,
                COUNT(CASE WHEN status = 'نشط' THEN 1 END) as active_contracts,
                COUNT(CASE WHEN status = 'مسودة' THEN 1 END) as draft_contracts,
                COUNT(CASE WHEN status = 'منتهي' THEN 1 END) as expired_contracts,
                COUNT(CASE WHEN status = 'ملغي' THEN 1 END) as cancelled_contracts,
                SUM(total_contract_value) as total_value,
                AVG(salary_amount) as avg_salary,
                AVG(duration_months) as avg_duration
            FROM contracts 
            $whereClause";
    
    return $db->selectOne($sql, $params);
}

/**
 * العقود المنتهية قريباً
 * Contracts Expiring Soon
 */
function getExpiringContracts($days = 30) {
    $db = getDB();
    
    $sql = "SELECT c.*, cl.full_name_ar as client_name, w.full_name_ar as worker_name,
                   DATEDIFF(c.end_date, CURDATE()) as days_remaining
            FROM contracts c
            LEFT JOIN clients cl ON c.client_id = cl.id
            LEFT JOIN workers w ON c.worker_id = w.id
            WHERE c.status = 'نشط' 
            AND c.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
            ORDER BY c.end_date ASC";
    
    return $db->select($sql, ['days' => $days]);
}

/**
 * إنشاء فاتورة العقد
 * Create Contract Invoice
 */
function createContractInvoice($contractId) {
    $contract = getContractById($contractId);
    if (!$contract) {
        return false;
    }
    
    $db = getDB();
    
    // إنشاء رقم فاتورة فريد
    $invoiceNumber = 'INV' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    $sql = "INSERT INTO invoices (
        invoice_number, contract_id, client_id, 
        total_amount, due_date, status, created_by, created_at
    ) VALUES (
        :invoice_number, :contract_id, :client_id,
        :total_amount, :due_date, 'pending', :created_by, NOW()
    )";
    
    $dueDate = date('Y-m-d', strtotime('+30 days'));
    
    $params = [
        'invoice_number' => $invoiceNumber,
        'contract_id' => $contractId,
        'client_id' => $contract['client_id'],
        'total_amount' => $contract['total_contract_value'],
        'due_date' => $dueDate,
        'created_by' => $_SESSION['user_id']
    ];
    
    return $db->insert($sql, $params);
}

/**
 * إرسال إشعار الموافقة على العقد
 * Send Contract Approval Notification
 */
function sendContractApprovalNotification($contract) {
    // الحصول على بيانات العميل
    $client = getSingleRow('clients', ['id' => $contract['client_id']]);
    
    if ($client && $client['email']) {
        // إرسال بريد إلكتروني
        return sendContractConfirmationEmail(
            $client['email'], 
            $client['full_name_ar'], 
            $contract['contract_number'],
            $contract['worker_name'] ?? ''
        );
    }
    
    return false;
}

/**
 * إلغاء الفواتير غير المدفوعة
 * Cancel Unpaid Invoices
 */
function cancelUnpaidInvoices($contractId) {
    $db = getDB();
    
    $sql = "UPDATE invoices SET 
            status = 'cancelled', 
            cancelled_at = NOW(),
            cancelled_by = :cancelled_by
            WHERE contract_id = :contract_id 
            AND status IN ('pending', 'overdue')";
    
    return $db->update($sql, [
        'cancelled_by' => $_SESSION['user_id'],
        'contract_id' => $contractId
    ]);
}

/**
 * ==============================================
 * قوالب العقود
 * Contract Templates
 * ==============================================
 */

/**
 * الحصول على قالب العقد
 * Get Contract Template
 */
function getContractTemplate($contractType, $language = 'ar') {
    $templateFile = CONTRACTS_PATH . "/templates/{$contractType}_{$language}.php";
    
    if (file_exists($templateFile)) {
        return include $templateFile;
    }
    
    // قالب افتراضي
    return getDefaultContractTemplate($language);
}

/**
 * إنشاء PDF للعقد
 * Generate Contract PDF
 */
function generateContractPDF($contractId, $language = 'ar') {
    require_once VENDOR_PATH . '/tcpdf/tcpdf.php';
    
    $contract = getContractById($contractId);
    if (!$contract) {
        throw new Exception('Contract not found');
    }
    
    // إنشاء PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    
    // إعدادات PDF
    $pdf->SetCreator('Tadbeer Plus');
    $pdf->SetAuthor('Tadbeer Plus');
    $pdf->SetTitle('Contract ' . $contract['contract_number']);
    
    // إعدادات الخط للعربية
    if ($language === 'ar') {
        $pdf->setRTL(true);
        $pdf->SetFont('aealarabiya', '', 12);
    } else {
        $pdf->SetFont('helvetica', '', 12);
    }
    
    $pdf->AddPage();
    
    // الحصول على محتوى العقد
    $template = getContractTemplate($contract['contract_type'], $language);
    $content = renderContractTemplate($template, $contract);
    
    $pdf->writeHTML($content, true, false, true, false, '');
    
    // حفظ الملف
    $filename = "contract_{$contract['contract_number']}.pdf";
    $filepath = UPLOADS_PATH . "/contracts/{$filename}";
    
    createDirectory(dirname($filepath));
    $pdf->Output($filepath, 'F');
    
    return $filepath;
}

/**
 * عرض قالب العقد
 * Render Contract Template
 */
function renderContractTemplate($template, $contractData) {
    // استبدال المتغيرات في القالب
    $replacements = [
        '{contract_number}' => $contractData['contract_number'],
        '{client_name}' => $contractData['client_name'],
        '{worker_name}' => $contractData['worker_name'],
        '{start_date}' => formatDate($contractData['start_date']),
        '{end_date}' => formatDate($contractData['end_date']),
        '{salary}' => formatCurrency($contractData['salary_amount']),
        '{duration}' => $contractData['duration_months'] . ' شهر',
        '{job_description}' => $contractData['job_description'] ?? '',
        '{special_conditions}' => $contractData['special_conditions'] ?? '',
        '{company_name}' => getCompanySetting('company_name_ar'),
        '{company_license}' => getCompanySetting('license_number'),
        '{today_date}' => formatDate(date('Y-m-d'))
    ];
    
    return str_replace(array_keys($replacements), array_values($replacements), $template);
}

/**
 * الحصول على القالب الافتراضي
 * Get Default Contract Template
 */
function getDefaultContractTemplate($language = 'ar') {
    if ($language === 'ar') {
        return '
        <h2 style="text-align: center;">عقد عمل منزلي</h2>
        <p><strong>رقم العقد:</strong> {contract_number}</p>
        <p><strong>التاريخ:</strong> {today_date}</p>
        
        <h3>الطرف الأول (صاحب العمل):</h3>
        <p><strong>الاسم:</strong> {client_name}</p>
        
        <h3>الطرف الثاني (العامل):</h3>
        <p><strong>الاسم:</strong> {worker_name}</p>
        
        <h3>شروط العقد:</h3>
        <p><strong>تاريخ البداية:</strong> {start_date}</p>
        <p><strong>تاريخ الانتهاء:</strong> {end_date}</p>
        <p><strong>الراتب الشهري:</strong> {salary}</p>
        <p><strong>مدة العقد:</strong> {duration}</p>
        
        {job_description}
        {special_conditions}
        
        <div style="margin-top: 50px;">
            <table width="100%">
                <tr>
                    <td width="50%" style="text-align: center;">
                        <p>_________________</p>
                        <p>توقيع صاحب العمل</p>
                    </td>
                    <td width="50%" style="text-align: center;">
                        <p>_________________</p>
                        <p>توقيع العامل</p>
                    </td>
                </tr>
            </table>
        </div>
        ';
    }
    
    return '
    <h2 style="text-align: center;">Employment Contract</h2>
    <p><strong>Contract Number:</strong> {contract_number}</p>
    <p><strong>Date:</strong> {today_date}</p>
    
    <h3>First Party (Employer):</h3>
    <p><strong>Name:</strong> {client_name}</p>
    
    <h3>Second Party (Employee):</h3>
    <p><strong>Name:</strong> {worker_name}</p>
    
    <h3>Contract Terms:</h3>
    <p><strong>Start Date:</strong> {start_date}</p>
    <p><strong>End Date:</strong> {end_date}</p>
    <p><strong>Monthly Salary:</strong> {salary}</p>
    <p><strong>Duration:</strong> {duration}</p>
    
    {job_description}
    {special_conditions}
    
    <div style="margin-top: 50px;">
        <table width="100%">
            <tr>
                <td width="50%" style="text-align: center;">
                    <p>_________________</p>
                    <p>Employer Signature</p>
                </td>
                <td width="50%" style="text-align: center;">
                    <p>_________________</p>
                    <p>Employee Signature</p>
                </td>
            </tr>
        </table>
    </div>
    ';
}

?>
