/**
 * ==============================================
 * نظام تدبير بلس - إدارة العقود الكاملة
 * Tadbeer Plus System - Complete Contract Management
 * ==============================================
 */

'use strict';

/**
 * نظام إدارة العقود المتكامل
 * Complete Contract Management System
 */
const TadbeerContracts = {
    // إعدادات العقود
    config: {
        minDurationMonths: 6,
        maxDurationMonths: 24,
        defaultDurationMonths: 24,
        probationMaxDays: 180,
        minSalary: 1000,
        maxSalary: 5000,
        vatRate: 0.05,
        baseUrl: '/admin/contracts/',
        apiUrl: '/api/contracts/',
        maxFileSize: 10 * 1024 * 1024, // 10MB
        allowedFileTypes: ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']
    },

    // أنواع العقود
    contractTypes: {
        'عقد عمل منزلي': 'domestic_work',
        'عقد رعاية مسنين': 'elderly_care',
        'عقد طبخ': 'cooking',
        'عقد تنظيف': 'cleaning',
        'عقد قيادة': 'driving',
        'عقد حراسة': 'security'
    },

    // قوالب شروط العقود
    contractTermsTemplates: {
        standard: {
            ar: `
1. يلتزم العامل بأداء الأعمال المطلوبة بكفاءة وأمانة
2. ساعات العمل 8 ساعات يومياً مع يوم راحة أسبوعي
3. يحق للعامل الحصول على إجازة سنوية مدفوعة الأجر
4. يلتزم صاحب العمل بدفع الراتب في موعده المحدد
5. توفير السكن والطعام والرعاية الطبية
6. احترام كرامة العامل وحقوقه الأساسية
7. عدم احتجاز جواز السفر أو الوثائق الشخصية
8. توفير بيئة عمل آمنة وصحية
            `,
            en: `
1. The worker shall perform the required work efficiently and honestly
2. Working hours 8 hours per day with one day off per week
3. The worker is entitled to paid annual leave
4. The employer undertakes to pay the salary on time
5. Providing accommodation, food and medical care
6. Respecting the dignity and basic rights of the worker
7. Not withholding passport or personal documents
8. Providing a safe and healthy work environment
            `
        },
        cooking: {
            ar: `إضافة إلى الشروط العامة:
1. إعداد الوجبات وفقاً لمتطلبات الأسرة
2. المحافظة على نظافة المطبخ وأدوات الطهي
3. التسوق للمواد الغذائية عند الحاجة`,
            en: `In addition to general terms:
1. Prepare meals according to family requirements
2. Maintain kitchen and cooking utensils cleanliness
3. Shop for groceries when needed`
        }
    },

    // حالة النظام
    state: {
        currentContract: null,
        selectedClient: null,
        selectedWorker: null,
        calculatedAmounts: {},
        formStep: 1,
        maxSteps: 4,
        isLoading: false,
        validationErrors: {}
    },

    /**
     * تهيئة نظام العقود
     * Initialize Contract System
     */
    init() {
        console.log('Initializing Tadbeer Contracts System...');
        this.bindEvents();
        this.initializeCalculator();
        this.initializeStepForm();
        this.loadContractData();
        this.initializeDataTables();
        this.setupValidation();
        console.log('Contract system initialized successfully');
    },

    /**
     * ربط الأحداث
     * Bind Events
     */
    bindEvents() {
        // أحداث إنشاء العقد
        $(document).on('click', '[data-action]', this.handleContractActions.bind(this));
        
        // أحداث تغيير البيانات
        $(document).on('change', 'input, select, textarea', this.handleFormChanges.bind(this));
        
        // أحداث الحاسبة
        $(document).on('input', '.calculator-input', this.handleCalculatorInput.bind(this));
        
        // أحداث البحث
        $(document).on('input', '.search-input', this.debounce(this.handleSearchInput.bind(this), 300));
        
        // أحداث التنقل بين خطوات النموذج
        $(document).on('click', '#nextStep', this.nextStep.bind(this));
        $(document).on('click', '#prevStep', this.prevStep.bind(this));
        $(document).on('click', '#submitContract', this.submitContract.bind(this));
        
        // أحداث الطباعة
        $(document).on('click', '[data-print]', this.handlePrintActions.bind(this));
        
        // أحداث رفع الملفات
        $(document).on('change', '.file-input', this.handleFileUpload.bind(this));
        
        // أحداث اختيار العميل/العامل
        $(document).on('click', '.select-client', this.selectClient.bind(this));
        $(document).on('click', '.select-worker', this.selectWorker.bind(this));
    },

    /**
     * معالج إجراءات العقود
     * Handle Contract Actions
     */
    handleContractActions(event) {
        const button = $(event.target).closest('[data-action]');
        if (!button.length) return;
        
        const action = button.data('action');
        const contractId = button.data('contract-id');
        
        event.preventDefault();
        
        switch (action) {
            case 'create-contract':
                this.showContractWizard();
                break;
            case 'edit-contract':
                this.editContract(contractId);
                break;
            case 'view-contract':
                this.viewContract(contractId);
                break;
            case 'renew-contract':
                this.renewContract(contractId);
                break;
            case 'cancel-contract':
                this.cancelContract(contractId);
                break;
            case 'approve-contract':
                this.approveContract(contractId);
                break;
            case 'reject-contract':
                this.rejectContract(contractId);
                break;
            case 'generate-pdf':
                this.generateContractPDF(contractId);
                break;
            case 'send-email':
                this.sendContractEmail(contractId);
                break;
            case 'duplicate-contract':
                this.duplicateContract(contractId);
                break;
            case 'export-contract':
                this.exportContract(contractId);
                break;
        }
    },

    /**
     * إظهار معالج إنشاء العقد
     * Show Contract Wizard
     */
    showContractWizard() {
        const modal = this.createContractWizardModal();
        $('body').append(modal);
        
        const bsModal = new bootstrap.Modal(modal[0], {
            backdrop: 'static',
            keyboard: false
        });
        
        bsModal.show();
        
        // تهيئة الخطوة الأولى
        this.initializeStep1();
        
        // إزالة النافذة عند الإغلاق
        modal.on('hidden.bs.modal', () => {
            modal.remove();
            this.resetWizardState();
        });
    },

    /**
     * إنشاء نافذة معالج العقد
     * Create Contract Wizard Modal
     */
    createContractWizardModal() {
        const lang = this.getCurrentLanguage();
        
        return $(`
            <div class="modal fade contract-wizard-modal" id="contractWizardModal" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-file-contract me-2"></i>
                                ${lang === 'ar' ? 'إنشاء عقد جديد' : 'Create New Contract'}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <!-- مؤشر التقدم -->
                            <div class="progress mb-4" style="height: 8px;">
                                <div class="progress-bar bg-success" id="wizardProgress" role="progressbar" style="width: 25%"></div>
                            </div>
                            
                            <!-- خطوات النموذج -->
                            <div class="wizard-steps">
                                <div class="step-indicator d-flex justify-content-between mb-4">
                                    <div class="step active" data-step="1">
                                        <div class="step-number">1</div>
                                        <div class="step-title">${lang === 'ar' ? 'اختيار العميل' : 'Select Client'}</div>
                                    </div>
                                    <div class="step" data-step="2">
                                        <div class="step-number">2</div>
                                        <div class="step-title">${lang === 'ar' ? 'اختيار العامل' : 'Select Worker'}</div>
                                    </div>
                                    <div class="step" data-step="3">
                                        <div class="step-number">3</div>
                                        <div class="step-title">${lang === 'ar' ? 'تفاصيل العقد' : 'Contract Details'}</div>
                                    </div>
                                    <div class="step" data-step="4">
                                        <div class="step-number">4</div>
                                        <div class="step-title">${lang === 'ar' ? 'المراجعة والتأكيد' : 'Review & Confirm'}</div>
                                    </div>
                                </div>
                                
                                <!-- محتوى الخطوات -->
                                <div class="step-content">
                                    ${this.getStep1HTML()}
                                    ${this.getStep2HTML()}
                                    ${this.getStep3HTML()}
                                    ${this.getStep4HTML()}
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" id="prevStep" style="display: none;">
                                <i class="fas fa-arrow-left me-2"></i>
                                ${lang === 'ar' ? 'السابق' : 'Previous'}
                            </button>
                            <div class="ms-auto">
                                <button type="button" class="btn btn-primary" id="nextStep">
                                    ${lang === 'ar' ? 'التالي' : 'Next'}
                                    <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                                <button type="button" class="btn btn-success" id="submitContract" style="display: none;">
                                    <i class="fas fa-save me-2"></i>
                                    ${lang === 'ar' ? 'إنشاء العقد' : 'Create Contract'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
    },

    /**
     * الحصول على HTML الخطوة الأولى
     * Get Step 1 HTML
     */
    getStep1HTML() {
        const lang = this.getCurrentLanguage();
        
        return `
            <div class="wizard-step" data-step="1">
                <h6 class="mb-3">
                    <i class="fas fa-user-tie me-2"></i>
                    ${lang === 'ar' ? 'اختيار العميل' : 'Select Client'}
                </h6>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">${lang === 'ar' ? 'البحث عن العميل' : 'Search Client'}</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control search-input" id="clientSearch" 
                                       placeholder="${lang === 'ar' ? 'اسم العميل أو رقم الهوية' : 'Client name or Emirates ID'}"
                                       autocomplete="off">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">${lang === 'ar' ? 'تصفية حسب الإمارة' : 'Filter by Emirate'}</label>
                            <select class="form-select" id="clientEmirate">
                                <option value="">${lang === 'ar' ? 'جميع الإمارات' : 'All Emirates'}</option>
                                <option value="أبوظبي">Abu Dhabi</option>
                                <option value="دبي">Dubai</option>
                                <option value="الشارقة">Sharjah</option>
                                <option value="عجمان">Ajman</option>
                            </select>
                        </div>
                        
                        <div class="clients-list" id="clientsList">
                            <div class="loading-spinner text-center">
                                <i class="fas fa-spinner fa-spin"></i>
                                <p class="mt-2">${lang === 'ar' ? 'جاري تحميل العملاء...' : 'Loading clients...'}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="client-details" id="clientDetails" style="display: none;">
                            <h6>
                                <i class="fas fa-info-circle me-2"></i>
                                ${lang === 'ar' ? 'تفاصيل العميل' : 'Client Details'}
                            </h6>
                            <div class="card">
                                <div class="card-body">
                                    <div class="client-info">
                                        <!-- سيتم عرض تفاصيل العميل هنا -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * الحصول على HTML الخطوة الثانية
     * Get Step 2 HTML
     */
    getStep2HTML() {
        const lang = this.getCurrentLanguage();
        
        return `
            <div class="wizard-step" data-step="2" style="display: none;">
                <h6 class="mb-3">
                    <i class="fas fa-users me-2"></i>
                    ${lang === 'ar' ? 'اختيار العامل' : 'Select Worker'}
                </h6>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">${lang === 'ar' ? 'البحث عن العامل' : 'Search Worker'}</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control search-input" id="workerSearch" 
                                       placeholder="${lang === 'ar' ? 'اسم العامل أو رقم الجواز' : 'Worker name or passport number'}"
                                       autocomplete="off">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">${lang === 'ar' ? 'المهنة' : 'Profession'}</label>
                                    <select class="form-select" id="workerProfession">
                                        <option value="">${lang === 'ar' ? 'جميع المهن' : 'All Professions'}</option>
                                        <option value="مربية">${lang === 'ar' ? 'مربية' : 'Nanny'}</option>
                                        <option value="خادمة منزل">${lang === 'ar' ? 'خادمة منزل' : 'Housemaid'}</option>
                                        <option value="طباخة">${lang === 'ar' ? 'طباخة' : 'Cook'}</option>
                                        <option value="سائق">${lang === 'ar' ? 'سائق' : 'Driver'}</option>
                                        <option value="حارس">${lang === 'ar' ? 'حارس' : 'Guard'}</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">${lang === 'ar' ? 'الجنسية' : 'Nationality'}</label>
                                    <select class="form-select" id="workerNationality">
                                        <option value="">${lang === 'ar' ? 'جميع الجنسيات' : 'All Nationalities'}</option>
                                        <option value="فلبيني">Filipino</option>
                                        <option value="إندونيسي">Indonesian</option>
                                        <option value="هندي">Indian</option>
                                        <option value="سريلانكي">Sri Lankan</option>
                                        <option value="بنغلاديشي">Bangladeshi</option>
                                        <option value="نيبالي">Nepalese</option>
                                        <option value="إثيوبي">Ethiopian</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">${lang === 'ar' ? 'الحالة' : 'Status'}</label>
                            <select class="form-select" id="workerStatus">
                                <option value="">${lang === 'ar' ? 'جميع الحالات' : 'All Statuses'}</option>
                                <option value="متاح" selected>Available</option>
                                <option value="في العرض">In Offer</option>
                            </select>
                        </div>
                        
                        <div class="workers-list" id="workersList">
                            <div class="loading-spinner text-center">
                                <i class="fas fa-spinner fa-spin"></i>
                                <p class="mt-2">${lang === 'ar' ? 'جاري تحميل العمال...' : 'Loading workers...'}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="worker-details" id="workerDetails" style="display: none;">
                            <h6>
                                <i class="fas fa-info-circle me-2"></i>
                                ${lang === 'ar' ? 'تفاصيل العامل' : 'Worker Details'}
                            </h6>
                            <div class="card">
                                <div class="card-body">
                                    <div class="worker-info">
                                        <!-- سيتم عرض تفاصيل العامل هنا -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * الحصول على HTML الخطوة الثالثة
     * Get Step 3 HTML
     */
    getStep3HTML() {
        const lang = this.getCurrentLanguage();
        
        return `
            <div class="wizard-step" data-step="3" style="display: none;">
                <h6 class="mb-3">
                    <i class="fas fa-file-contract me-2"></i>
                    ${lang === 'ar' ? 'تفاصيل العقد' : 'Contract Details'}
                </h6>
                
                <form id="contractDetailsForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">${lang === 'ar' ? 'نوع العقد' : 'Contract Type'} *</label>
                                <select class="form-select calculator-input" id="contractType" required>
                                    <option value="">${lang === 'ar' ? 'اختر نوع العقد' : 'Select Contract Type'}</option>
                                    <option value="عقد عمل منزلي">${lang === 'ar' ? 'عقد عمل منزلي' : 'Domestic Work Contract'}</option>
                                    <option value="عقد رعاية مسنين">${lang === 'ar' ? 'عقد رعاية مسنين' : 'Elderly Care Contract'}</option>
                                    <option value="عقد طبخ">${lang === 'ar' ? 'عقد طبخ' : 'Cooking Contract'}</option>
                                    <option value="عقد تنظيف">${lang === 'ar' ? 'عقد تنظيف' : 'Cleaning Contract'}</option>
                                    <option value="عقد قيادة">${lang === 'ar' ? 'عقد قيادة' : 'Driving Contract'}</option>
                                    <option value="عقد حراسة">${lang === 'ar' ? 'عقد حراسة' : 'Security Contract'}</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">${lang === 'ar' ? 'تاريخ بداية العقد' : 'Contract Start Date'} *</label>
                                <input type="date" class="form-control calculator-input" id="startDate" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">${lang === 'ar' ? 'مدة العقد (بالأشهر)' : 'Contract Duration (Months)'} *</label>
                                <select class="form-select calculator-input" id="contractDuration" required>
                                    <option value="6">6 ${lang === 'ar' ? 'أشهر' : 'months'}</option>
                                    <option value="12">12 ${lang === 'ar' ? 'شهر' : 'months'}</option>
                                    <option value="24" selected>24 ${lang === 'ar' ? 'شهر' : 'months'}</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">${lang === 'ar' ? 'فترة التجربة (بالأيام)' : 'Probation Period (Days)'}</label>
                                <input type="number" class="form-control calculator-input" id="probationPeriod" value="90" min="0" max="180">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">${lang === 'ar' ? 'فترة الإخطار (بالأيام)' : 'Notice Period (Days)'}</label>
                                <input type="number" class="form-control calculator-input" id="noticePeriod" value="30" min="15" max="60">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">${lang === 'ar' ? 'الراتب الشهري (درهم)' : 'Monthly Salary (AED)'} *</label>
                                <input type="number" class="form-control calculator-input" id="monthlySalary" required 
                                       min="${this.config.minSalary}" max="${this.config.maxSalary}" step="50">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">${lang === 'ar' ? 'بدل السكن' : 'Accommodation Allowance'}</label>
                                <input type="number" class="form-control calculator-input" id="accommodationAllowance" value="0" min="0" step="100">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">${lang === 'ar' ? 'بدل الطعام' : 'Food Allowance'}</label>
                                <input type="number" class="form-control calculator-input" id="foodAllowance" value="0" min="0" step="50">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">${lang === 'ar' ? 'بدل المواصلات' : 'Transportation Allowance'}</label>
                                <input type="number" class="form-control calculator-input" id="transportationAllowance" value="0" min="0" step="50">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">${lang === 'ar' ? 'بدل الاتصالات' : 'Communication Allowance'}</label>
                                <input type="number" class="form-control calculator-input" id="communicationAllowance" value="0" min="0" step="25">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">${lang === 'ar' ? 'ساعات العمل يومياً' : 'Working Hours per Day'}</label>
                                <input type="number" class="form-control" id="workHoursPerDay" value="8" min="6" max="12">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">${lang === 'ar' ? 'أيام العمل أسبوعياً' : 'Working Days per Week'}</label>
                                <input type="number" class="form-control" id="workDaysPerWeek" value="6" min="5" max="6">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">${lang === 'ar' ? 'أيام الإجازة السنوية' : 'Annual Leave Days'}</label>
                                <input type="number" class="form-control" id="annualLeaveDays" value="30" min="21" max="45">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">${lang === 'ar' ? 'الامتيازات' : 'Benefits'}</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="medicalInsurance" checked>
                                    <label class="form-check-label" for="medicalInsurance">
                                        ${lang === 'ar' ? 'التأمين الطبي' : 'Medical Insurance'}
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="annualTicket" checked>
                                    <label class="form-check-label" for="annualTicket">
                                        ${lang === 'ar' ? 'التذكرة السنوية' : 'Annual Ticket'}
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="endOfServiceBenefit">
                                    <label class="form-check-label" for="endOfServiceBenefit">
                                        ${lang === 'ar' ? 'مكافأة نهاية الخدمة' : 'End of Service Benefit'}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">${lang === 'ar' ? 'وصف الوظيفة' : 'Job Description'}</label>
                                <textarea class="form-control" id="jobDescription" rows="4" 
                                          placeholder="${lang === 'ar' ? 'اكتب وصف مفصل للمهام المطلوبة...' : 'Write detailed description of required tasks...'}"
                                ></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">${lang === 'ar' ? 'شروط خاصة' : 'Special Conditions'}</label>
                                <textarea class="form-control" id="specialConditions" rows="3" 
                                          placeholder="${lang === 'ar' ? 'أي شروط خاصة للعقد...' : 'Any special conditions for the contract...'}"
                                ></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- حاسبة التكاليف -->
                    <div class="card bg-light">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-calculator me-2"></i>
                                ${lang === 'ar' ? 'حاسبة تكاليف العقد' : 'Contract Cost Calculator'}
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row" id="costCalculator">
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h5 class="text-primary mb-1" id="totalSalaryDisplay">0 AED</h5>
                                        <small class="text-muted">${lang === 'ar' ? 'إجمالي الراتب' : 'Total Salary'}</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h5 class="text-success mb-1" id="serviceFeeDisplay">0 AED</h5>
                                        <small class="text-muted">${lang === 'ar' ? 'رسوم الخدمة' : 'Service Fee'}</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h5 class="text-info mb-1" id="totalCostDisplay">0 AED</h5>
                                        <small class="text-muted">${lang === 'ar' ? 'إجمالي التكلفة' : 'Total Cost'}</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>${lang === 'ar' ? 'البند' : 'Item'}</th>
                                                    <th class="text-end">${lang === 'ar' ? 'المبلغ' : 'Amount'}</th>
                                                </tr>
                                            </thead>
                                            <tbody id="costBreakdown">
                                                <!-- سيتم تحديثها تلقائياً -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        `;
    },

    /**
     * الحصول على HTML الخطوة الرابعة
     * Get Step 4 HTML
     */
    getStep4HTML() {
        const lang = this.getCurrentLanguage();
        
        return `
            <div class="wizard-step" data-step="4" style="display: none;">
                <h6 class="mb-3">
                    <i class="fas fa-check-circle me-2"></i>
                    ${lang === 'ar' ? 'مراجعة وتأكيد العقد' : 'Review and Confirm Contract'}
                </h6>
                
                <div class="contract-preview">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user-tie me-2"></i>
                                        ${lang === 'ar' ? 'معلومات العميل' : 'Client Information'}
                                    </h6>
                                </div>
                                <div class="card-body" id="clientPreview">
                                    <!-- سيتم عرض معلومات العميل هنا -->
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-users me-2"></i>
                                        ${lang === 'ar' ? 'معلومات العامل' : 'Worker Information'}
                                    </h6>
                                </div>
                                <div class="card-body" id="workerPreview">
                                    <!-- سيتم عرض معلومات العامل هنا -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-file-contract me-2"></i>
                                ${lang === 'ar' ? 'تفاصيل العقد' : 'Contract Details'}
                            </h6>
                        </div>
                        <div class="card-body" id="contractPreview">
                            <!-- سيتم عرض تفاصيل العقد هنا -->
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0">
                                <i class="fas fa-money-bill-wave me-2"></i>
                                ${lang === 'ar' ? 'ملخص التكاليف' : 'Cost Summary'}
                            </h6>
                        </div>
                        <div class="card-body" id="costPreview">
                            <!-- سيتم عرض ملخص التكاليف هنا -->
                        </div>
                    </div>
                    
                    <!-- شروط وأحكام -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-gavel me-2"></i>
                                ${lang === 'ar' ? 'الشروط والأحكام' : 'Terms and Conditions'}
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                                <label class="form-check-label" for="agreeTerms">
                                    ${lang === 'ar' ? 'أوافق على جميع الشروط والأحكام المذكورة أعلاه' : 'I agree to all terms and conditions mentioned above'}
                                </label>
                            </div>
                            
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="confirmAccuracy" required>
                                <label class="form-check-label" for="confirmAccuracy">
                                    ${lang === 'ar' ? 'أؤكد صحة جميع البيانات المدخلة' : 'I confirm the accuracy of all entered data'}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * تهيئة الخطوة الأولى
     * Initialize Step 1
     */
    initializeStep1() {
        this.loadClients();
        this.setupClientSearch();
        
        // ربط أحداث التصفية
        $('#clientEmirate').on('change', () => {
            this.loadClients();
        });
    },

    /**
     * تحميل قائمة العملاء
     * Load Clients List
     */
    async loadClients(search = '') {
        try {
            const emirate = $('#clientEmirate').val();
            
            const response = await $.ajax({
                url: this.config.apiUrl + 'clients/search',
                method: 'GET',
                data: { 
                    search, 
                    emirate,
                    status: 'نشط', 
                    limit: 10 
                }
            });
            
            if (response.success) {
                this.renderClientsList(response.data);
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            console.error('Failed to load clients:', error);
            this.showError('فشل في تحميل قائمة العملاء');
            $('#clientsList').html('<p class="text-danger text-center">خطأ في تحميل البيانات</p>');
        }
    },

    /**
     * عرض قائمة العملاء
     * Render Clients List
     */
    renderClientsList(clients) {
        const lang = this.getCurrentLanguage();
        
        if (!clients || clients.length === 0) {
            $('#clientsList').html(`
                <div class="text-center text-muted">
                    <i class="fas fa-user-slash fa-2x mb-2"></i>
                    <p>${lang === 'ar' ? 'لا توجد عملاء' : 'No clients found'}</p>
                </div>
            `);
            return;
        }

        const clientsHTML = clients.map(client => `
            <div class="client-item border rounded p-3 mb-2 cursor-pointer hover-shadow" 
                 data-client-id="${client.id}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">${client.full_name_ar || client.full_name_en}</h6>
                        <small class="text-muted">
                            <i class="fas fa-id-card me-1"></i>
                            ${client.emirates_id}
                        </small>
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-phone me-1"></i>
                            ${client.phone_primary}
                        </small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-primary">${client.emirate}</span>
                        <br>
                        <small class="text-muted">${client.total_contracts || 0} عقود</small>
                    </div>
                </div>
            </div>
        `).join('');
        
        $('#clientsList').html(clientsHTML);
    },

    /**
     * إعداد البحث في العملاء
     * Setup Client Search
     */
    setupClientSearch() {
        $('#clientSearch').on('input', this.debounce((e) => {
            const search = $(e.target).val().trim();
            this.loadClients(search);
        }, 300));
        
        // اختيار العميل
        $(document).on('click', '.client-item', (e) => {
            const clientId = $(e.currentTarget).data('client-id');
            this.selectClient(clientId);
        });
    },

    /**
     * اختيار العميل
     * Select Client
     */
    async selectClient(clientId) {
        try {
            const response = await $.ajax({
                url: this.config.apiUrl + 'clients/' + clientId,
                method: 'GET'
            });
            
            if (response.success) {
                this.state.selectedClient = response.data;
                this.displayClientDetails(response.data);
                this.highlightSelectedClient(clientId);
                
                // تفعيل الخطوة التالية
                this.updateStepValidation(1, true);
            }
        } catch (error) {
            console.error('Failed to select client:', error);
            this.showError('فشل في اختيار العميل');
        }
    },

    /**
     * عرض تفاصيل العميل
     * Display Client Details
     */
    displayClientDetails(client) {
        const lang = this.getCurrentLanguage();
        
        const detailsHTML = `
            <div class="client-info">
                <div class="row">
                    <div class="col-6">
                        <strong>${lang === 'ar' ? 'الاسم:' : 'Name:'}</strong>
                        <br>
                        ${client.full_name_ar || client.full_name_en}
                    </div>
                    <div class="col-6">
                        <strong>${lang === 'ar' ? 'الهوية:' : 'Emirates ID:'}</strong>
                        <br>
                        ${client.emirates_id}
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-6">
                        <strong>${lang === 'ar' ? 'الهاتف:' : 'Phone:'}</strong>
                        <br>
                        ${client.phone_primary}
                    </div>
                    <div class="col-6">
                        <strong>${lang === 'ar' ? 'الإمارة:' : 'Emirate:'}</strong>
                        <br>
                        ${client.emirate}
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-6">
                        <strong>${lang === 'ar' ? 'نوع السكن:' : 'Housing Type:'}</strong>
                        <br>
                        ${client.housing_type}
                    </div>
                    <div class="col-6">
                        <strong>${lang === 'ar' ? 'حجم العائلة:' : 'Family Size:'}</strong>
                        <br>
                        ${client.family_size} ${lang === 'ar' ? 'فرد' : 'members'}
                    </div>
                </div>
                
                ${client.special_requirements ? `
                    <hr>
                    <div class="alert alert-info">
                        <strong>${lang === 'ar' ? 'متطلبات خاصة:' : 'Special Requirements:'}</strong>
                        <br>
                        ${client.special_requirements}
                    </div>
                ` : ''}
            </div>
        `;
        
        $('#clientDetails .client-info').html(detailsHTML);
        $('#clientDetails').show();
    },

    /**
     * تهيئة الخطوة الثانية
     * Initialize Step 2
     */
    initializeStep2() {
        this.loadWorkers();
        this.setupWorkerSearch();
        
        // ربط أحداث التصفية
        $('#workerProfession, #workerNationality, #workerStatus').on('change', () => {
            this.loadWorkers();
        });
    },

    /**
     * تحميل قائمة العمال
     * Load Workers List
     */
    async loadWorkers(search = '') {
        try {
            const profession = $('#workerProfession').val();
            const nationality = $('#workerNationality').val();
            const status = $('#workerStatus').val() || 'متاح';
            
            const response = await $.ajax({
                url: this.config.apiUrl + 'workers/search',
                method: 'GET',
                data: { 
                    search, 
                    profession,
                    nationality,
                    status,
                    limit: 10 
                }
            });
            
            if (response.success) {
                this.renderWorkersList(response.data);
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            console.error('Failed to load workers:', error);
            this.showError('فشل في تحميل قائمة العمال');
            $('#workersList').html('<p class="text-danger text-center">خطأ في تحميل البيانات</p>');
        }
    },

    /**
     * عرض قائمة العمال
     * Render Workers List
     */
    renderWorkersList(workers) {
        const lang = this.getCurrentLanguage();
        
        if (!workers || workers.length === 0) {
            $('#workersList').html(`
                <div class="text-center text-muted">
                    <i class="fas fa-users-slash fa-2x mb-2"></i>
                    <p>${lang === 'ar' ? 'لا توجد عمال متاحون' : 'No available workers'}</p>
                </div>
            `);
            return;
        }

        const workersHTML = workers.map(worker => `
            <div class="worker-item border rounded p-3 mb-2 cursor-pointer hover-shadow" 
                 data-worker-id="${worker.id}">
                <div class="d-flex align-items-center">
                    ${worker.photo ? `
                        <img src="${worker.photo}" class="worker-photo me-3" width="50" height="50">
                    ` : `
                        <div class="worker-placeholder me-3">
                            <i class="fas fa-user fa-2x text-muted"></i>
                        </div>
                    `}
                    
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${worker.full_name_ar || worker.full_name_en}</h6>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">
                                    <i class="fas fa-briefcase me-1"></i>
                                    ${worker.profession}
                                </small>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-flag me-1"></i>
                                    ${worker.nationality}
                                </small>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    ${worker.experience_years} ${lang === 'ar' ? 'سنة خبرة' : 'years exp'}
                                </small>
                                <br>
                                <span class="badge bg-success">${worker.status}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
        
        $('#workersList').html(workersHTML);
    },

    /**
     * إعداد البحث في العمال
     * Setup Worker Search
     */
    setupWorkerSearch() {
        $('#workerSearch').on('input', this.debounce((e) => {
            const search = $(e.target).val().trim();
            this.loadWorkers(search);
        }, 300));
        
        // اختيار العامل
        $(document).on('click', '.worker-item', (e) => {
            const workerId = $(e.currentTarget).data('worker-id');
            this.selectWorker(workerId);
        });
    },

    /**
     * اختيار العامل
     * Select Worker
     */
    async selectWorker(workerId) {
        try {
            const response = await $.ajax({
                url: this.config.apiUrl + 'workers/' + workerId,
                method: 'GET'
            });
            
            if (response.success) {
                this.state.selectedWorker = response.data;
                this.displayWorkerDetails(response.data);
                this.highlightSelectedWorker(workerId);
                
                // تفعيل الخطوة التالية
                this.updateStepValidation(2, true);
            }
        } catch (error) {
            console.error('Failed to select worker:', error);
            this.showError('فشل في اختيار العامل');
        }
    },

    /**
     * عرض تفاصيل العامل
     * Display Worker Details
     */
    displayWorkerDetails(worker) {
        const lang = this.getCurrentLanguage();
        
        const detailsHTML = `
            <div class="worker-info">
                <div class="text-center mb-3">
                    ${worker.photo ? `
                        <img src="${worker.photo}" class="rounded-circle" width="80" height="80">
                    ` : `
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-user fa-2x text-muted"></i>
                        </div>
                    `}
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <strong>${lang === 'ar' ? 'الاسم:' : 'Name:'}</strong>
                        <br>
                        ${worker.full_name_ar || worker.full_name_en}
                    </div>
                    <div class="col-6">
                        <strong>${lang === 'ar' ? 'الجواز:' : 'Passport:'}</strong>
                        <br>
                        ${worker.passport_number}
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-6">
                        <strong>${lang === 'ar' ? 'المهنة:' : 'Profession:'}</strong>
                        <br>
                        ${worker.profession}
                    </div>
                    <div class="col-6">
                        <strong>${lang === 'ar' ? 'الجنسية:' : 'Nationality:'}</strong>
                        <br>
                        ${worker.nationality}
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-6">
                        <strong>${lang === 'ar' ? 'الخبرة:' : 'Experience:'}</strong>
                        <br>
                        ${worker.experience_years} ${lang === 'ar' ? 'سنة' : 'years'}
                    </div>
                    <div class="col-6">
                        <strong>${lang === 'ar' ? 'العمر:' : 'Age:'}</strong>
                        <br>
                        ${this.calculateAge(worker.birth_date)} ${lang === 'ar' ? 'سنة' : 'years'}
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-6">
                        <strong>${lang === 'ar' ? 'التعليم:' : 'Education:'}</strong>
                        <br>
                        ${worker.education_level}
                    </div>
                    <div class="col-6">
                        <strong>${lang === 'ar' ? 'اللغات:' : 'Languages:'}</strong>
                        <br>
                        ${worker.languages_spoken}
                    </div>
                </div>
                
                ${worker.skills ? `
                    <hr>
                    <div class="alert alert-success">
                        <strong>${lang === 'ar' ? 'المهارات:' : 'Skills:'}</strong>
                        <br>
                        ${worker.skills}
                    </div>
                ` : ''}
                
                ${worker.salary_expected ? `
                    <hr>
                    <div class="text-center">
                        <strong class="text-primary">${lang === 'ar' ? 'الراتب المتوقع:' : 'Expected Salary:'}</strong>
                        <br>
                        <span class="h5 text-primary">${worker.salary_expected} AED</span>
                    </div>
                ` : ''}
            </div>
        `;
        
        $('#workerDetails .worker-info').html(detailsHTML);
        $('#workerDetails').show();
    },

    /**
     * تهيئة الخطوة الثالثة
     * Initialize Step 3
     */
    initializeStep3() {
        // تعيين التاريخ الافتراضي (اليوم)
        const today = new Date().toISOString().split('T')[0];
        $('#startDate').val(today);
        
        // تعيين الراتب الافتراضي إن وجد
        if (this.state.selectedWorker?.salary_expected) {
            $('#monthlySalary').val(this.state.selectedWorker.salary_expected);
        }
        
        // حساب تاريخ النهاية
        this.updateEndDate();
        
        // حساب التكاليف
        this.calculateCosts();
        
        // ربط أحداث الحاسبة
        $('.calculator-input').on('input change', () => {
            this.calculateCosts();
        });
        
        // ربط حدث تغيير مدة العقد
        $('#contractDuration').on('change', () => {
            this.updateEndDate();
        });
        
        // ربط حدث تغيير تاريخ البداية
        $('#startDate').on('change', () => {
            this.updateEndDate();
        });
    },

    /**
     * تحديث تاريخ النهاية
     * Update End Date
     */
    updateEndDate() {
        const startDate = $('#startDate').val();
        const duration = parseInt($('#contractDuration').val());
        
        if (startDate && duration) {
            const endDate = new Date(startDate);
            endDate.setMonth(endDate.getMonth() + duration);
            
            this.state.calculatedEndDate = endDate.toISOString().split('T')[0];
        }
    },

    /**
     * حساب التكاليف
     * Calculate Costs
     */
    calculateCosts() {
        const monthlySalary = parseFloat($('#monthlySalary').val()) || 0;
        const accommodationAllowance = parseFloat($('#accommodationAllowance').val()) || 0;
        const foodAllowance = parseFloat($('#foodAllowance').val()) || 0;
        const transportationAllowance = parseFloat($('#transportationAllowance').val()) || 0;
        const communicationAllowance = parseFloat($('#communicationAllowance').val()) || 0;
        const duration = parseInt($('#contractDuration').val()) || 24;
        
        // حساب إجمالي الراتب الشهري
        const totalMonthlySalary = monthlySalary + accommodationAllowance + foodAllowance + 
                                 transportationAllowance + communicationAllowance;
        
        // حساب إجمالي الراتب للمدة
        const totalSalaryForDuration = totalMonthlySalary * duration;
        
        // رسوم الخدمة (افتراضية)
        const serviceFee = 3000; // يمكن تخصيصها حسب نوع العقد
        
        // التأمين
        const insurance = 2000;
        
        // إجمالي التكلفة
        const totalCost = totalSalaryForDuration + serviceFee + insurance;
        
        // ضريبة القيمة المضافة
        const vatAmount = totalCost * this.config.vatRate;
        const finalTotal = totalCost + vatAmount;
        
        // حفظ النتائج
        this.state.calculatedAmounts = {
            monthlySalary: totalMonthlySalary,
            totalSalaryForDuration: totalSalaryForDuration,
            serviceFee: serviceFee,
            insurance: insurance,
            subTotal: totalCost,
            vatAmount: vatAmount,
            finalTotal: finalTotal,
            duration: duration
        };
        
        // عرض النتائج
        this.displayCalculatedCosts();
    },

    /**
     * عرض التكاليف المحسوبة
     * Display Calculated Costs
     */
    displayCalculatedCosts() {
        const amounts = this.state.calculatedAmounts;
        const lang = this.getCurrentLanguage();
        
        // عرض الملخص
        $('#totalSalaryDisplay').text(this.formatCurrency(amounts.totalSalaryForDuration));
        $('#serviceFeeDisplay').text(this.formatCurrency(amounts.serviceFee + amounts.insurance));
        $('#totalCostDisplay').text(this.formatCurrency(amounts.finalTotal));
        
        // عرض التفاصيل
        const breakdownHTML = `
            <tr>
                <td>${lang === 'ar' ? 'الراتب الشهري' : 'Monthly Salary'}</td>
                <td class="text-end">${this.formatCurrency(amounts.monthlySalary)}</td>
            </tr>
            <tr>
                <td>${lang === 'ar' ? 'إجمالي الراتب للمدة' : 'Total Salary for Duration'} (${amounts.duration} ${lang === 'ar' ? 'شهر' : 'months'})</td>
                <td class="text-end">${this.formatCurrency(amounts.totalSalaryForDuration)}</td>
            </tr>
            <tr>
                <td>${lang === 'ar' ? 'رسوم الخدمة' : 'Service Fee'}</td>
                <td class="text-end">${this.formatCurrency(amounts.serviceFee)}</td>
            </tr>
            <tr>
                <td>${lang === 'ar' ? 'التأمين' : 'Insurance'}</td>
                <td class="text-end">${this.formatCurrency(amounts.insurance)}</td>
            </tr>
            <tr class="table-light">
                <td><strong>${lang === 'ar' ? 'المجموع الفرعي' : 'Subtotal'}</strong></td>
                <td class="text-end"><strong>${this.formatCurrency(amounts.subTotal)}</strong></td>
            </tr>
            <tr>
                <td>${lang === 'ar' ? 'ضريبة القيمة المضافة' : 'VAT'} (${(this.config.vatRate * 100)}%)</td>
                <td class="text-end">${this.formatCurrency(amounts.vatAmount)}</td>
            </tr>
            <tr class="table-primary">
                <td><strong>${lang === 'ar' ? 'الإجمالي النهائي' : 'Final Total'}</strong></td>
                <td class="text-end"><strong>${this.formatCurrency(amounts.finalTotal)}</strong></td>
            </tr>
        `;
        
        $('#costBreakdown').html(breakdownHTML);
    },

    /**
     * التنقل للخطوة التالية
     * Navigate to Next Step
     */
    nextStep() {
        if (this.validateCurrentStep()) {
            if (this.state.formStep < this.state.maxSteps) {
                this.goToStep(this.state.formStep + 1);
            }
        }
    },

    /**
     * التنقل للخطوة السابقة
     * Navigate to Previous Step
     */
    prevStep() {
        if (this.state.formStep > 1) {
            this.goToStep(this.state.formStep - 1);
        }
    },

    /**
     * الانتقال لخطوة محددة
     * Go to Specific Step
     */
    goToStep(step) {
        // إخفاء الخطوة الحالية
        $(`.wizard-step[data-step="${this.state.formStep}"]`).hide();
        
        // عرض الخطوة الجديدة
        $(`.wizard-step[data-step="${step}"]`).show();
        
        // تحديث مؤشر التقدم
        const progressPercentage = (step / this.state.maxSteps) * 100;
        $('#wizardProgress').css('width', `${progressPercentage}%`);
        
        // تحديث مؤشر الخطوات
        $('.step').removeClass('active completed');
        for (let i = 1; i < step; i++) {
            $(`.step[data-step="${i}"]`).addClass('completed');
        }
        $(`.step[data-step="${step}"]`).addClass('active');
        
        // تحديث الأزرار
        if (step === 1) {
            $('#prevStep').hide();
        } else {
            $('#prevStep').show();
        }
        
        if (step === this.state.maxSteps) {
            $('#nextStep').hide();
            $('#submitContract').show();
            this.generateContractPreview();
        } else {
            $('#nextStep').show();
            $('#submitContract').hide();
        }
        
        // تحديث الحالة
        this.state.formStep = step;
        
        // تهيئة الخطوة الجديدة
        switch (step) {
            case 2:
                this.initializeStep2();
                break;
            case 3:
                this.initializeStep3();
                break;
            case 4:
                this.generateContractPreview();
                break;
        }
    },

    /**
     * التحقق من صحة الخطوة الحالية
     * Validate Current Step
     */
    validateCurrentStep() {
        switch (this.state.formStep) {
            case 1:
                return this.validateStep1();
            case 2:
                return this.validateStep2();
            case 3:
                return this.validateStep3();
            case 4:
                return this.validateStep4();
            default:
                return false;
        }
    },

    /**
     * التحقق من صحة الخطوة الأولى
     * Validate Step 1
     */
    validateStep1() {
        if (!this.state.selectedClient) {
            this.showError('يرجى اختيار العميل');
            return false;
        }
        return true;
    },

    /**
     * التحقق من صحة الخطوة الثانية
     * Validate Step 2
     */
    validateStep2() {
        if (!this.state.selectedWorker) {
            this.showError('يرجى اختيار العامل');
            return false;
        }
        return true;
    },

    /**
     * التحقق من صحة الخطوة الثالثة
     * Validate Step 3
     */
    validateStep3() {
        const requiredFields = [
            'contractType',
            'startDate',
            'contractDuration',
            'monthlySalary'
        ];
        
        for (const field of requiredFields) {
            const value = $(`#${field}`).val();
            if (!value || value.trim() === '') {
                $(`#${field}`).addClass('is-invalid');
                this.showError(`يرجى ملء حقل ${$(`label[for="${field}"]`).text()}`);
                return false;
            } else {
                $(`#${field}`).removeClass('is-invalid');
            }
        }
        
        // التحقق من صحة الراتب
        const salary = parseFloat($('#monthlySalary').val());
        if (salary < this.config.minSalary || salary > this.config.maxSalary) {
            $('#monthlySalary').addClass('is-invalid');
            this.showError(`الراتب يجب أن يكون بين ${this.config.minSalary} و ${this.config.maxSalary} درهم`);
            return false;
        }
        
        // التحقق من صحة تاريخ البداية
        const startDate = new Date($('#startDate').val());
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (startDate < today) {
            $('#startDate').addClass('is-invalid');
            this.showError('تاريخ بداية العقد لا يمكن أن يكون في الماضي');
            return false;
        }
        
        return true;
    },

    /**
     * التحقق من صحة الخطوة الرابعة
     * Validate Step 4
     */
    validateStep4() {
        if (!$('#agreeTerms').is(':checked')) {
            this.showError('يرجى الموافقة على الشروط والأحكام');
            return false;
        }
        
        if (!$('#confirmAccuracy').is(':checked')) {
            this.showError('يرجى تأكيد صحة البيانات');
            return false;
        }
        
        return true;
    },

    /**
     * إنشاء معاينة العقد
     * Generate Contract Preview
     */
    generateContractPreview() {
        if (this.state.selectedClient) {
            this.renderClientPreview(this.state.selectedClient);
        }
        
        if (this.state.selectedWorker) {
            this.renderWorkerPreview(this.state.selectedWorker);
        }
        
        this.renderContractPreview();
        this.renderCostPreview();
    },

    /**
     * عرض معاينة العميل
     * Render Client Preview
     */
    renderClientPreview(client) {
        const lang = this.getCurrentLanguage();
        
        const previewHTML = `
            <div class="row">
                <div class="col-md-6">
                    <strong>${lang === 'ar' ? 'الاسم:' : 'Name:'}</strong><br>
                    ${client.full_name_ar || client.full_name_en}
                </div>
                <div class="col-md-6">
                    <strong>${lang === 'ar' ? 'رقم العميل:' : 'Client Code:'}</strong><br>
                    ${client.client_code}
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6">
                    <strong>${lang === 'ar' ? 'الهوية:' : 'Emirates ID:'}</strong><br>
                    ${client.emirates_id}
                </div>
                <div class="col-md-6">
                    <strong>${lang === 'ar' ? 'الهاتف:' : 'Phone:'}</strong><br>
                    ${client.phone_primary}
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6">
                    <strong>${lang === 'ar' ? 'الإمارة:' : 'Emirate:'}</strong><br>
                    ${client.emirate}
                </div>
                <div class="col-md-6">
                    <strong>${lang === 'ar' ? 'نوع السكن:' : 'Housing Type:'}</strong><br>
                    ${client.housing_type}
                </div>
            </div>
        `;
        
        $('#clientPreview').html(previewHTML);
    },

    /**
     * عرض معاينة العامل
     * Render Worker Preview
     */
    renderWorkerPreview(worker) {
        const lang = this.getCurrentLanguage();
        
        const previewHTML = `
            <div class="row">
                <div class="col-md-6">
                    <strong>${lang === 'ar' ? 'الاسم:' : 'Name:'}</strong><br>
                    ${worker.full_name_ar || worker.full_name_en}
                </div>
                <div class="col-md-6">
                    <strong>${lang === 'ar' ? 'رمز العامل:' : 'Worker Code:'}</strong><br>
                    ${worker.worker_code}
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6">
                    <strong>${lang === 'ar' ? 'رقم الجواز:' : 'Passport:'}</strong><br>
                    ${worker.passport_number}
                </div>
                <div class="col-md-6">
                    <strong>${lang === 'ar' ? 'الجنسية:' : 'Nationality:'}</strong><br>
                    ${worker.nationality}
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6">
                    <strong>${lang === 'ar' ? 'المهنة:' : 'Profession:'}</strong><br>
                    ${worker.profession}
                </div>
                <div class="col-md-6">
                    <strong>${lang === 'ar' ? 'سنوات الخبرة:' : 'Experience:'}</strong><br>
                    ${worker.experience_years} ${lang === 'ar' ? 'سنة' : 'years'}
                </div>
            </div>
        `;
        
        $('#workerPreview').html(previewHTML);
    },

    /**
     * عرض معاينة العقد
     * Render Contract Preview
     */
    renderContractPreview() {
        const lang = this.getCurrentLanguage();
        
        const previewHTML = `
            <div class="row">
                <div class="col-md-6">
                    <strong>${lang === 'ar' ? 'نوع العقد:' : 'Contract Type:'}</strong><br>
                    ${$('#contractType option:selected').text()}
                </div>
                <div class="col-md-6">
                    <strong>${lang === 'ar' ? 'مدة العقد:' : 'Duration:'}</strong><br>
                    ${$('#contractDuration').val()} ${lang === 'ar' ? 'شهر' : 'months'}
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6">
                    <strong>${lang === 'ar' ? 'تاريخ البداية:' : 'Start Date:'}</strong><br>
                    ${this.formatDate($('#startDate').val())}
                </div>
                <div class="col-md-6">
                    <strong>${lang === 'ar' ? 'تاريخ النهاية:' : 'End Date:'}</strong><br>
                    ${this.formatDate(this.state.calculatedEndDate)}
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6">
                    <strong>${lang === 'ar' ? 'فترة التجربة:' : 'Probation Period:'}</strong><br>
                    ${$('#probationPeriod').val()} ${lang === 'ar' ? 'يوم' : 'days'}
                </div>
                <div class="col-md-6">
                    <strong>${lang === 'ar' ? 'الراتب الشهري:' : 'Monthly Salary:'}</strong><br>
                    ${this.formatCurrency($('#monthlySalary').val())}
                </div>
            </div>
            
            ${$('#jobDescription').val() ? `
                <div class="row mt-2">
                    <div class="col-12">
                        <strong>${lang === 'ar' ? 'وصف الوظيفة:' : 'Job Description:'}</strong><br>
                        <div class="alert alert-light">
                            ${$('#jobDescription').val()}
                        </div>
                    </div>
                </div>
            ` : ''}
            
            ${$('#specialConditions').val() ? `
                <div class="row mt-2">
                    <div class="col-12">
                        <strong>${lang === 'ar' ? 'شروط خاصة:' : 'Special Conditions:'}</strong><br>
                        <div class="alert alert-warning">
                            ${$('#specialConditions').val()}
                        </div>
                    </div>
                </div>
            ` : ''}
        `;
        
        $('#contractPreview').html(previewHTML);
    },

    /**
     * عرض معاينة التكاليف
     * Render Cost Preview
     */
    renderCostPreview() {
        const lang = this.getCurrentLanguage();
        const amounts = this.state.calculatedAmounts;
        
        const previewHTML = `
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <td><strong>${lang === 'ar' ? 'الراتب الشهري:' : 'Monthly Salary:'}</strong></td>
                            <td class="text-end"><strong>${this.formatCurrency(amounts.monthlySalary)}</strong></td>
                        </tr>
                        <tr>
                            <td>${lang === 'ar' ? 'إجمالي الراتب للمدة:' : 'Total Salary for Duration:'}</td>
                            <td class="text-end">${this.formatCurrency(amounts.totalSalaryForDuration)}</td>
                        </tr>
                        <tr>
                            <td>${lang === 'ar' ? 'رسوم الخدمة:' : 'Service Fee:'}</td>
                            <td class="text-end">${this.formatCurrency(amounts.serviceFee)}</td>
                        </tr>
                        <tr>
                            <td>${lang === 'ar' ? 'التأمين:' : 'Insurance:'}</td>
                            <td class="text-end">${this.formatCurrency(amounts.insurance)}</td>
                        </tr>
                        <tr class="table-light">
                            <td><strong>${lang === 'ar' ? 'المجموع الفرعي:' : 'Subtotal:'}</strong></td>
                            <td class="text-end"><strong>${this.formatCurrency(amounts.subTotal)}</strong></td>
                        </tr>
                        <tr>
                            <td>${lang === 'ar' ? 'ضريبة القيمة المضافة:' : 'VAT:'} (5%)</td>
                            <td class="text-end">${this.formatCurrency(amounts.vatAmount)}</td>
                        </tr>
                        <tr class="table-primary">
                            <td><strong>${lang === 'ar' ? 'الإجمالي النهائي:' : 'Final Total:'}</strong></td>
                            <td class="text-end"><strong class="h5">${this.formatCurrency(amounts.finalTotal)}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        `;
        
        $('#costPreview').html(previewHTML);
    },

    /**
     * إرسال العقد
     * Submit Contract
     */
    async submitContract() {
        if (!this.validateCurrentStep()) {
            return;
        }

        try {
            this.setLoading(true);
            
            const contractData = this.collectContractData();
            
            const response = await $.ajax({
                url: this.config.apiUrl,
                method: 'POST',
                data: JSON.stringify(contractData),
                contentType: 'application/json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            
            if (response.success) {
                this.showSuccess('تم إنشاء العقد بنجاح');
                
                // إغلاق النافذة
                $('#contractWizardModal').modal('hide');
                
                // إعادة تحميل الجدول أو توجيه للعقد الجديد
                if (typeof window.contractsTable !== 'undefined' && window.contractsTable.ajax) {
                    window.contractsTable.ajax.reload();
                }
                
                // توجيه للعقد الجديد بعد ثانيتين
                setTimeout(() => {
                    window.location.href = this.config.baseUrl + 'contract_view.php?id=' + response.contract_id;
                }, 2000);
                
            } else {
                throw new Error(response.message || 'فشل في إنشاء العقد');
            }
            
        } catch (error) {
            console.error('Contract submission error:', error);
            this.showError(error.responseJSON?.message || error.message || 'حدث خطأ أثناء إنشاء العقد');
        } finally {
            this.setLoading(false);
        }
    },

    /**
     * جمع بيانات العقد
     * Collect Contract Data
     */
    collectContractData() {
        return {
            client_id: this.state.selectedClient.id,
            worker_id: this.state.selectedWorker.id,
            contract_type: $('#contractType').val(),
            start_date: $('#startDate').val(),
            end_date: this.state.calculatedEndDate,
            duration_months: parseInt($('#contractDuration').val()),
            probation_period_days: parseInt($('#probationPeriod').val()) || 90,
            notice_period_days: parseInt($('#noticePeriod').val()) || 30,
            salary_amount: parseFloat($('#monthlySalary').val()),
            accommodation_allowance: parseFloat($('#accommodationAllowance').val()) || 0,
            food_allowance: parseFloat($('#foodAllowance').val()) || 0,
            transportation_allowance: parseFloat($('#transportationAllowance').val()) || 0,
            communication_allowance: parseFloat($('#communicationAllowance').val()) || 0,
            work_hours_per_day: parseInt($('#workHoursPerDay').val()) || 8,
            work_days_per_week: parseInt($('#workDaysPerWeek').val()) || 6,
            annual_leave_days: parseInt($('#annualLeaveDays').val()) || 30,
            medical_insurance: $('#medicalInsurance').is(':checked') ? 1 : 0,
            annual_ticket: $('#annualTicket').is(':checked') ? 1 : 0,
            end_of_service_benefit: $('#endOfServiceBenefit').is(':checked') ? 1 : 0,
            job_description: $('#jobDescription').val(),
            special_conditions: $('#specialConditions').val(),
            calculated_amounts: this.state.calculatedAmounts,
            contract_terms_ar: this.getContractTerms('ar'),
            contract_terms_en: this.getContractTerms('en')
        };
    },

    /**
     * الحصول على شروط العقد
     * Get Contract Terms
     */
    getContractTerms(language) {
        const contractType = $('#contractType').val();
        const template = this.contractTermsTemplates[this.contractTypes[contractType]] || this.contractTermsTemplates.standard;
        
        return template[language] || template.ar;
    },

    /**
     * معالج إجراءات الطباعة
     * Handle Print Actions
     */
    handlePrintActions(event) {
        const button = $(event.target).closest('[data-print]');
        if (!button.length) return;
        
        const action = button.data('print');
        const contractId = button.data('contract-id');
        
        event.preventDefault();
        
        switch (action) {
            case 'contract':
                this.printContract(contractId);
                break;
            case 'invoice':
                this.printInvoice(contractId);
                break;
            case 'receipt':
                this.printReceipt(contractId);
                break;
        }
    },

    /**
     * طباعة العقد
     * Print Contract
     */
    async printContract(contractId, language = 'ar') {
        try {
            this.showLoading('جاري تحضير العقد للطباعة...');
            
            const response = await $.ajax({
                url: this.config.apiUrl + contractId + '/print',
                method: 'GET',
                data: { language }
            });
            
            if (response.success) {
                // فتح نافذة طباعة جديدة
                const printWindow = window.open('', '_blank', 'width=800,height=600');
                printWindow.document.write(response.html);
                printWindow.document.close();
                
                // تشغيل الطباعة تلقائياً
                printWindow.onload = function() {
                    printWindow.print();
                };
                
            } else {
                throw new Error(response.message || 'فشل في تحضير العقد للطباعة');
            }
            
        } catch (error) {
            console.error('Print error:', error);
            this.showError('فشل في طباعة العقد: ' + error.message);
        } finally {
            this.hideLoading();
        }
    },

    /**
     * إنشاء PDF للعقد
     * Generate Contract PDF
     */
    async generateContractPDF(contractId, language = 'ar') {
        try {
            this.showLoading('جاري إنشاء ملف PDF...');
            
            // إنشاء رابط التحميل
            const downloadUrl = this.config.apiUrl + contractId + '/pdf?' + 
                               new URLSearchParams({ language }).toString();
            
            // تحميل الملف
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = `contract_${contractId}_${language}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            this.showSuccess('تم إنشاء ملف PDF بنجاح');
            
        } catch (error) {
            console.error('PDF generation error:', error);
            this.showError('فشل في إنشاء ملف PDF');
        } finally {
            this.hideLoading();
        }
    },

    /**
     * إرسال العقد بالبريد الإلكتروني
     * Send Contract Email
     */
    async sendContractEmail(contractId) {
        const lang = this.getCurrentLanguage();
        
        // إظهار نافذة تأكيد
        const confirmed = await this.showConfirm(
            lang === 'ar' ? 'إرسال العقد بالبريد الإلكتروني' : 'Send Contract by Email',
            lang === 'ar' ? 'هل تريد إرسال العقد إلى العميل بالبريد الإلكتروني؟' : 'Do you want to send the contract to the client by email?'
        );
        
        if (!confirmed) return;
        
        try {
            this.showLoading('جاري إرسال البريد الإلكتروني...');
            
            const response = await $.ajax({
                url: this.config.apiUrl + contractId + '/email',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            
            if (response.success) {
                this.showSuccess('تم إرسال العقد بالبريد الإلكتروني بنجاح');
            } else {
                throw new Error(response.message || 'فشل في إرسال البريد الإلكتروني');
            }
            
        } catch (error) {
            console.error('Email sending error:', error);
            this.showError('فشل في إرسال البريد الإلكتروني: ' + error.message);
        } finally {
            this.hideLoading();
        }
    },

    /**
     * تجديد العقد
     * Renew Contract
     */
    async renewContract(contractId) {
        const lang = this.getCurrentLanguage();
        
        const confirmed = await this.showConfirm(
            lang === 'ar' ? 'تجديد العقد' : 'Renew Contract',
            lang === 'ar' ? 'هل تريد تجديد هذا العقد؟' : 'Do you want to renew this contract?'
        );
        
        if (!confirmed) return;
        
        try {
            this.showLoading('جاري تجديد العقد...');
            
            const response = await $.ajax({
                url: this.config.apiUrl + contractId + '/renew',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            
            if (response.success) {
                this.showSuccess('تم تجديد العقد بنجاح');
                
                // إعادة تحميل الجدول
                if (typeof window.contractsTable !== 'undefined' && window.contractsTable.ajax) {
                    window.contractsTable.ajax.reload();
                }
                
                // توجيه للعقد الجديد
                setTimeout(() => {
                    window.location.href = this.config.baseUrl + 'contract_view.php?id=' + response.new_contract_id;
                }, 2000);
                
            } else {
                throw new Error(response.message || 'فشل في تجديد العقد');
            }
            
        } catch (error) {
            console.error('Contract renewal error:', error);
            this.showError('فشل في تجديد العقد: ' + error.message);
        } finally {
            this.hideLoading();
        }
    },

    /**
     * إلغاء العقد
     * Cancel Contract
     */
    async cancelContract(contractId) {
        const lang = this.getCurrentLanguage();
        
        // طلب سبب الإلغاء
        const reason = await this.showPrompt(
            lang === 'ar' ? 'إلغاء العقد' : 'Cancel Contract',
            lang === 'ar' ? 'يرجى إدخال سبب إلغاء العقد:' : 'Please enter the reason for cancellation:'
        );
        
        if (!reason || reason.trim() === '') return;
        
        try {
            this.showLoading('جاري إلغاء العقد...');
            
            const response = await $.ajax({
                url: this.config.apiUrl + contractId + '/cancel',
                method: 'POST',
                data: JSON.stringify({ reason: reason.trim() }),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            
            if (response.success) {
                this.showSuccess('تم إلغاء العقد بنجاح');
                
                // إعادة تحميل الجدول
                if (typeof window.contractsTable !== 'undefined' && window.contractsTable.ajax) {
                    window.contractsTable.ajax.reload();
                }
                
            } else {
                throw new Error(response.message || 'فشل في إلغاء العقد');
            }
            
        } catch (error) {
            console.error('Contract cancellation error:', error);
            this.showError('فشل في إلغاء العقد: ' + error.message);
        } finally {
            this.hideLoading();
        }
    },

    /**
     * الموافقة على العقد
     * Approve Contract
     */
    async approveContract(contractId) {
        const lang = this.getCurrentLanguage();
        
        const confirmed = await this.showConfirm(
            lang === 'ar' ? 'الموافقة على العقد' : 'Approve Contract',
            lang === 'ar' ? 'هل تريد الموافقة على هذا العقد؟' : 'Do you want to approve this contract?'
        );
        
        if (!confirmed) return;
        
        try {
            this.showLoading('جاري الموافقة على العقد...');
            
            const response = await $.ajax({
                url: this.config.apiUrl + contractId + '/approve',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            
            if (response.success) {
                this.showSuccess('تمت الموافقة على العقد بنجاح');
                
                // إعادة تحميل الجدول
                if (typeof window.contractsTable !== 'undefined' && window.contractsTable.ajax) {
                    window.contractsTable.ajax.reload();
                }
                
            } else {
                throw new Error(response.message || 'فشل في الموافقة على العقد');
            }
            
        } catch (error) {
            console.error('Contract approval error:', error);
            this.showError('فشل في الموافقة على العقد: ' + error.message);
        } finally {
            this.hideLoading();
        }
    },

    /**
     * تهيئة جداول البيانات
     * Initialize DataTables
     */
    initializeDataTables() {
        if ($('#contractsTable').length) {
            window.contractsTable = $('#contractsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: this.config.apiUrl + 'datatable',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                },
                columns: [
                    { data: 'contract_number', name: 'contract_number' },
                    { data: 'client_name', name: 'client_name' },
                    { data: 'worker_name', name: 'worker_name' },
                    { data: 'contract_type', name: 'contract_type' },
                    { data: 'start_date', name: 'start_date' },
                    { data: 'end_date', name: 'end_date' },
                    { data: 'salary_amount', name: 'salary_amount' },
                    { data: 'status', name: 'status' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                order: [[0, 'desc']],
                pageLength: 25,
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Arabic.json'
                }
            });
        }
    },

    /**
     * إعداد نظام التحقق
     * Setup Validation
     */
    setupValidation() {
        // إعداد jQuery Validate إذا كان متاحاً
        if (typeof $.fn.validate !== 'undefined') {
            $('#contractDetailsForm').validate({
                rules: {
                    contractType: 'required',
                    startDate: {
                        required: true,
                        date: true
                    },
                    contractDuration: 'required',
                    monthlySalary: {
                        required: true,
                        min: this.config.minSalary,
                        max: this.config.maxSalary
                    }
                },
                messages: {
                    contractType: 'يرجى اختيار نوع العقد',
                    startDate: {
                        required: 'يرجى إدخال تاريخ بداية العقد',
                        date: 'يرجى إدخال تاريخ صحيح'
                    },
                    contractDuration: 'يرجى اختيار مدة العقد',
                    monthlySalary: {
                        required: 'يرجى إدخال الراتب الشهري',
                        min: `الراتب يجب أن يكون ${this.config.minSalary} درهم على الأقل`,
                        max: `الراتب يجب ألا يتجاوز ${this.config.maxSalary} درهم`
                    }
                }
            });
        }
    },

    /**
     * إعادة تعيين حالة المعالج
     * Reset Wizard State
     */
    resetWizardState() {
        this.state = {
            currentContract: null,
            selectedClient: null,
            selectedWorker: null,
            calculatedAmounts: {},
            formStep: 1,
            maxSteps: 4,
            isLoading: false,
            validationErrors: {}
        };
    },

    // ==============================================
    // الدوال المساعدة (Utility Functions)
    // ==============================================

    /**
     * الحصول على اللغة الحالية
     * Get Current Language
     */
    getCurrentLanguage() {
        return document.documentElement.lang || 'ar';
    },

    /**
     * تنسيق العملة
     * Format Currency
     */
    formatCurrency(amount, currency = 'AED') {
        const number = parseFloat(amount) || 0;
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(number) + ' ' + currency;
    },

    /**
     * تنسيق التاريخ
     * Format Date
     */
    formatDate(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        const lang = this.getCurrentLanguage();
        
        if (lang === 'ar') {
            return date.toLocaleDateString('ar-AE');
        } else {
            return date.toLocaleDateString('en-US');
        }
    },

    /**
     * حساب العمر
     * Calculate Age
     */
    calculateAge(birthDate) {
        if (!birthDate) return 0;
        
        const birth = new Date(birthDate);
        const today = new Date();
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        
        return age;
    },

    /**
     * دالة التأخير (Debounce)
     * Debounce Function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * تحديد العنصر المختار
     * Highlight Selected Item
     */
    highlightSelectedClient(clientId) {
        $('.client-item').removeClass('selected border-primary bg-light');
        $(`.client-item[data-client-id="${clientId}"]`).addClass('selected border-primary bg-light');
    },

    highlightSelectedWorker(workerId) {
        $('.worker-item').removeClass('selected border-primary bg-light');
        $(`.worker-item[data-worker-id="${workerId}"]`).addClass('selected border-primary bg-light');
    },

    /**
     * تحديث تحقق الخطوة
     * Update Step Validation
     */
    updateStepValidation(step, isValid) {
        if (isValid) {
            $(`.step[data-step="${step}"]`).addClass('validated');
        } else {
            $(`.step[data-step="${step}"]`).removeClass('validated');
        }
    },

    /**
     * تعيين حالة التحميل
     * Set Loading State
     */
    setLoading(loading) {
        this.state.isLoading = loading;
        
        if (loading) {
            $('#submitContract').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>جاري المعالجة...');
        } else {
            $('#submitContract').prop('disabled', false).html('<i class="fas fa-save me-2"></i>إنشاء العقد');
        }
    },

    /**
     * عرض رسالة نجاح
     * Show Success Message
     */
    showSuccess(message) {
        if (typeof toastr !== 'undefined') {
            toastr.success(message);
        } else {
            alert(message);
        }
    },

    /**
     * عرض رسالة خطأ
     * Show Error Message
     */
    showError(message) {
        if (typeof toastr !== 'undefined') {
            toastr.error(message);
        } else {
            alert(message);
        }
    },

    /**
     * عرض رسالة تحميل
     * Show Loading Message
     */
    showLoading(message = 'جاري التحميل...') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: message,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }
    },

    /**
     * إخفاء رسالة التحميل
     * Hide Loading Message
     */
    hideLoading() {
        if (typeof Swal !== 'undefined') {
            Swal.close();
        }
    },

    /**
