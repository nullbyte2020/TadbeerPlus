/**
 * ==============================================
 * نظام تدبير بلس - نظام التحقق من البيانات
 * Tadbeer Plus System - Validation System
 * ==============================================
 */

'use strict';

/**
 * نظام التحقق من البيانات
 * Data Validation System
 */
const TadbeerValidation = {
    // قواعد التحقق
    rules: {
        required: (value) => value !== null && value !== undefined && value.toString().trim() !== '',
        email: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
        phone: (value) => /^(\+971|00971|971)?[0-9]{8,9}$/.test(value.replace(/[\s-]/g, '')),
        emirates_id: (value) => /^784-\d{4}-\d{7}-\d$/.test(value) || /^\d{15}$/.test(value),
        passport: (value) => /^[A-Z0-9]{6,12}$/.test(value),
        numeric: (value) => /^\d+$/.test(value),
        decimal: (value) => /^\d+(\.\d+)?$/.test(value),
        alpha: (value) => /^[a-zA-Zأ-ي\s]+$/.test(value),
        alphanumeric: (value) => /^[a-zA-Z0-9أ-ي\s]+$/.test(value),
        url: (value) => {
            try {
                new URL(value);
                return true;
            } catch {
                return false;
            }
        },
        date: (value) => !isNaN(Date.parse(value)),
        min_length: (value, min) => value.length >= parseInt(min),
        max_length: (value, max) => value.length <= parseInt(max),
        min_value: (value, min) => parseFloat(value) >= parseFloat(min),
        max_value: (value, max) => parseFloat(value) <= parseFloat(max),
        confirmed: (value, confirmField) => {
            const confirmInput = document.querySelector(`[name="${confirmField}"]`);
            return confirmInput ? value === confirmInput.value : false;
        }
    },

    // رسائل الخطأ
    messages: {
        ar: {
            required: 'هذا الحقل مطلوب',
            email: 'يرجى إدخال بريد إلكتروني صحيح',
            phone: 'يرجى إدخال رقم هاتف صحيح',
            emirates_id: 'رقم الهوية الإماراتية غير صحيح',
            passport: 'رقم الجواز غير صحيح',
            numeric: 'يجب أن يحتوي على أرقام فقط',
            decimal: 'يجب أن يكون رقم صحيح',
            alpha: 'يجب أن يحتوي على أحرف فقط',
            alphanumeric: 'يجب أن يحتوي على أحرف وأرقام فقط',
            url: 'يرجى إدخال رابط صحيح',
            date: 'يرجى إدخال تاريخ صحيح',
            min_length: 'يجب أن يحتوي على {min} أحرف على الأقل',
            max_length: 'يجب ألا يتجاوز {max} حرف',
            min_value: 'يجب أن يكون أكبر من أو يساوي {min}',
            max_value: 'يجب أن يكون أصغر من أو يساوي {max}',
            confirmed: 'التأكيد غير مطابق'
        },
        en: {
            required: 'This field is required',
            email: 'Please enter a valid email address',
            phone: 'Please enter a valid phone number',
            emirates_id: 'Emirates ID number is invalid',
            passport: 'Passport number is invalid',
            numeric: 'Must contain numbers only',
            decimal: 'Must be a valid number',
            alpha: 'Must contain letters only',
            alphanumeric: 'Must contain letters and numbers only',
            url: 'Please enter a valid URL',
            date: 'Please enter a valid date',
            min_length: 'Must be at least {min} characters',
            max_length: 'Must not exceed {max} characters',
            min_value: 'Must be at least {min}',
            max_value: 'Must not exceed {max}',
            confirmed: 'Confirmation does not match'
        }
    },

    /**
     * تهيئة نظام التحقق
     * Initialize Validation System
     */
    init() {
        this.bindEvents();
        this.initializeCustomRules();
    },

    /**
     * ربط الأحداث
     * Bind Events
     */
    bindEvents() {
        // التحقق عند إرسال النموذج
        document.addEventListener('submit', this.handleFormSubmit.bind(this));
        
        // التحقق المباشر عند الكتابة
        document.addEventListener('input', this.handleRealtimeValidation.bind(this));
        
        // التحقق عند فقدان التركيز
        document.addEventListener('blur', this.handleBlurValidation.bind(this), true);
        
        // التحقق من كلمة المرور
        document.addEventListener('input', this.handlePasswordValidation.bind(this));
    },

    /**
     * معالج إرسال النموذج
     * Handle Form Submit
     */
    handleFormSubmit(event) {
        const form = event.target;
        if (!form.matches('form[data-validate="true"]')) return;
        
        const isValid = this.validateForm(form);
        if (!isValid) {
            event.preventDefault();
            event.stopPropagation();
            
            // التمرير إلى أول حقل يحتوي على خطأ
            const firstError = form.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
        }
        
        form.classList.add('was-validated');
    },

    /**
     * التحقق المباشر
     * Handle Realtime Validation
     */
    handleRealtimeValidation(event) {
        const input = event.target;
        if (!input.matches('[data-validate-realtime="true"]')) return;
        
        // تأخير بسيط لتجنب التحقق مع كل حرف
        clearTimeout(input.validationTimeout);
        input.validationTimeout = setTimeout(() => {
            this.validateField(input);
        }, 500);
    },

    /**
     * التحقق عند فقدان التركيز
     * Handle Blur Validation
     */
    handleBlurValidation(event) {
        const input = event.target;
        if (!input.matches('input, select, textarea')) return;
        
        this.validateField(input);
    },

    /**
     * التحقق من النموذج
     * Validate Form
     */
    validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        return isValid;
    },

    /**
     * التحقق من الحقل
     * Validate Field
     */
    validateField(input) {
        // تجاهل الحقول المخفية والمعطلة
        if (input.type === 'hidden' || input.disabled) return true;
        
        const value = input.value.trim();
        const rules = this.getFieldRules(input);
        let isValid = true;
        let errorMessage = '';

        // التحقق من كل قاعدة
        for (const rule of rules) {
            const result = this.checkRule(value, rule, input);
            if (!result.valid) {
                isValid = false;
                errorMessage = result.message;
                break;
            }
        }

        // تطبيق النتيجة على الحقل
        this.applyValidationResult(input, isValid, errorMessage);
        
        return isValid;
    },

    /**
     * الحصول على قواعد الحقل
     * Get Field Rules
     */
    getFieldRules(input) {
        const rulesAttr = input.dataset.rules || '';
        const rules = [];
        
        // قواعد من data-rules
        if (rulesAttr) {
            rulesAttr.split('|').forEach(ruleStr => {
                const [name, param] = ruleStr.split(':');
                rules.push({ name: name.trim(), param });
            });
        }
        
        // قواعد HTML5
        if (input.required) {
            rules.push({ name: 'required' });
        }
        
        if (input.type === 'email') {
            rules.push({ name: 'email' });
        }
        
        if (input.type === 'url') {
            rules.push({ name: 'url' });
        }
        
        if (input.type === 'number') {
            rules.push({ name: 'numeric' });
            if (input.min) rules.push({ name: 'min_value', param: input.min });
            if (input.max) rules.push({ name: 'max_value', param: input.max });
        }
        
        if (input.minLength) {
            rules.push({ name: 'min_length', param: input.minLength });
        }
        
        if (input.maxLength) {
            rules.push({ name: 'max_length', param: input.maxLength });
        }
        
        return rules;
    },

    /**
     * فحص القاعدة
     * Check Rule
     */
    checkRule(value, rule, input) {
        // تجاهل القواعد الأخرى إذا كان الحقل فارغ وليس مطلوب
        if (!value && rule.name !== 'required') {
            return { valid: true };
        }
        
        const validator = this.rules[rule.name];
        if (!validator) {
            console.warn(`Unknown validation rule: ${rule.name}`);
            return { valid: true };
        }
        
        let isValid;
        if (rule.param) {
            isValid = validator(value, rule.param, input);
        } else {
            isValid = validator(value, input);
        }
        
        if (!isValid) {
            const message = this.getErrorMessage(rule.name, rule.param, input);
            return { valid: false, message };
        }
        
        return { valid: true };
    },

    /**
     * الحصول على رسالة الخطأ
     * Get Error Message
     */
    getErrorMessage(ruleName, param, input) {
        const lang = TadbeerPlus?.config?.language || 'ar';
        const fieldName = input.dataset.fieldName || input.name || 'الحقل';
        
        let message = this.messages[lang]?.[ruleName] || this.messages.ar[ruleName] || 'خطأ في التحقق';
        
        // استبدال المعاملات
        if (param) {
            message = message.replace('{min}', param).replace('{max}', param);
        }
        
        return message.replace('{field}', fieldName);
    },

    /**
     * تطبيق نتيجة التحقق
     * Apply Validation Result
     */
    applyValidationResult(input, isValid, errorMessage) {
        // إزالة الحالات السابقة
        input.classList.remove('is-valid', 'is-invalid');
        
        // إزالة رسائل الخطأ السابقة
        const existingFeedback = input.parentElement.querySelector('.invalid-feedback, .valid-feedback');
        if (existingFeedback) {
            existingFeedback.remove();
        }
        
        if (isValid) {
            input.classList.add('is-valid');
            this.addFeedback(input, 'تم التحقق بنجاح', 'valid');
        } else {
            input.classList.add('is-invalid');
            this.addFeedback(input, errorMessage, 'invalid');
        }
        
        // تحديث عداد الأخطاء في النموذج
        this.updateFormErrorCount(input.form);
    },

    /**
     * إضافة رسالة التغذية الراجعة
     * Add Feedback Message
     */
    addFeedback(input, message, type) {
        const feedback = document.createElement('div');
        feedback.className = `${type}-feedback`;
        feedback.textContent = message;
        
        // إدراج الرسالة بعد الحقل
        input.parentElement.appendChild(feedback);
    },

    /**
     * تحديث عداد أخطاء النموذج
     * Update Form Error Count
     */
    updateFormErrorCount(form) {
        if (!form) return;
        
        const errors = form.querySelectorAll('.is-invalid').length;
        const errorCounter = form.querySelector('.form-error-count');
        
        if (errorCounter) {
            errorCounter.textContent = errors;
            errorCounter.style.display = errors > 0 ? 'inline' : 'none';
        }
    },

    /**
     * معالج تحقق كلمة المرور
     * Handle Password Validation
     */
    handlePasswordValidation(event) {
        const input = event.target;
        if (input.type !== 'password') return;
        
        const strength = this.calculatePasswordStrength(input.value);
        this.updatePasswordStrengthIndicator(input, strength);
    },

    /**
     * حساب قوة كلمة المرور
     * Calculate Password Strength
     */
    calculatePasswordStrength(password) {
        let score = 0;
        const checks = {
            length: password.length >= 8,
            lowercase: /[a-z]/.test(password),
            uppercase: /[A-Z]/.test(password),
            numbers: /\d/.test(password),
            special: /[^a-zA-Z0-9]/.test(password)
        };
        
        // حساب النقاط
        if (checks.length) score += 20;
        if (password.length >= 12) score += 10;
        if (checks.lowercase) score += 15;
        if (checks.uppercase) score += 15;
        if (checks.numbers) score += 20;
        if (checks.special) score += 20;
        
        // تحديد القوة
        let strength = 'weak';
        if (score >= 70) strength = 'strong';
        else if (score >= 50) strength = 'medium';
        
        return { score, strength, checks };
    },

    /**
     * تحديث مؤشر قوة كلمة المرور
     * Update Password Strength Indicator
     */
    updatePasswordStrengthIndicator(input, strength) {
        let indicator = input.parentElement.querySelector('.password-strength');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'password-strength mt-2';
            input.parentElement.appendChild(indicator);
        }
        
        const colors = {
            weak: '#dc3545',
            medium: '#ffc107',
            strong: '#28a745'
        };
        
        const labels = {
            weak: TadbeerPlus?.config?.language === 'ar' ? 'ضعيفة' : 'Weak',
            medium: TadbeerPlus?.config?.language === 'ar' ? 'متوسطة' : 'Medium',
            strong: TadbeerPlus?.config?.language === 'ar' ? 'قوية' : 'Strong'
        };
        
        indicator.innerHTML = `
            <div class="progress" style="height: 5px;">
                <div class="progress-bar" style="width: ${strength.score}%; background-color: ${colors[strength.strength]}"></div>
            </div>
            <small class="text-muted">${labels[strength.strength]}</small>
        `;
    },

    /**
     * تهيئة القواعد المخصصة
     * Initialize Custom Rules
     */
    initializeCustomRules() {
        // قاعدة التحقق من العمر
        this.rules.working_age = (birthDate) => {
            const today = new Date();
            const birth = new Date(birthDate);
            const age = today.getFullYear() - birth.getFullYear();
            return age >= 21 && age <= 60;
        };
        
        // قاعدة التحقق من تاريخ مستقبلي
        this.rules.future_date = (dateValue) => {
            const inputDate = new Date(dateValue);
            const today = new Date();
            return inputDate > today;
        };
        
        // قاعدة التحقق من تاريخ ماضي
        this.rules.past_date = (dateValue) => {
            const inputDate = new Date(dateValue);
            const today = new Date();
            return inputDate < today;
        };
        
        // قاعدة التحقق من IBAN
        this.rules.iban = (iban) => {
            const cleanIban = iban.replace(/\s/g, '').toUpperCase();
            if (!/^[A-Z]{2}\d{2}[A-Z0-9]{4,30}$/.test(cleanIban)) return false;
            
            // خوارزمية التحقق من IBAN
            const rearranged = cleanIban.slice(4) + cleanIban.slice(0, 4);
            const numeric = rearranged.replace(/[A-Z]/g, (match) => {
                return (match.charCodeAt(0) - 55).toString();
            });
            
            return this.mod97(numeric) === 1;
        };
        
        // قاعدة التحقق من رقم الهاتف الإماراتي
        this.rules.uae_phone = (phone) => {
            const cleanPhone = phone.replace(/[\s\-\(\)]/g, '');
            const patterns = [
                /^(\+971|00971|971)?[0-9]{8,9}$/,  // الأرقام الإماراتية
                /^05[0-9]{8}$/,                     // الهواتف المحمولة
                /^0[2-7,9][0-9]{7}$/               // الهواتف الأرضية
            ];
            
            return patterns.some(pattern => pattern.test(cleanPhone));
        };
    },

    /**
     * حساب mod97 للـ IBAN
     * Calculate mod97 for IBAN
     */
    mod97(string) {
        let remainder = '';
        for (let i = 0; i < string.length; i++) {
            remainder = (remainder + string[i]) % 97;
        }
        return remainder;
    },

    /**
     * التحقق من تطابق كلمة المرور
     * Validate Password Confirmation
     */
    validatePasswordConfirmation(passwordField, confirmField) {
        const password = passwordField.value;
        const confirm = confirmField.value;
        
        if (password !== confirm) {
            this.applyValidationResult(confirmField, false, this.getErrorMessage('confirmed'));
            return false;
        }
        
        this.applyValidationResult(confirmField, true, '');
        return true;
    },

    /**
     * التحقق من الملفات
     * Validate Files
     */
    validateFiles(input, options = {}) {
        const files = input.files;
        const maxSize = options.maxSize || 10 * 1024 * 1024; // 10MB
        const allowedTypes = options.allowedTypes || [];
        
        for (const file of files) {
            // فحص الحجم
            if (file.size > maxSize) {
                const sizeInMB = Math.round(maxSize / 1024 / 1024);
                this.applyValidationResult(input, false, `حجم الملف يجب أن يكون أقل من ${sizeInMB} ميجابايت`);
                return false;
            }
            
            // فحص النوع
            if (allowedTypes.length > 0 && !allowedTypes.includes(file.type)) {
                this.applyValidationResult(input, false, 'نوع الملف غير مدعوم');
                return false;
            }
        }
        
        this.applyValidationResult(input, true, '');
        return true;
    },

    /**
     * التحقق من تفرد القيمة
     * Validate Uniqueness
     */
    async validateUniqueness(input, table, column, excludeId = null) {
        const value = input.value.trim();
        if (!value) return true;
        
        try {
            const response = await TadbeerPlus.ajax('/api/validate/unique', {
                method: 'POST',
                data: { table, column, value, exclude_id: excludeId }
            });
            
            if (response.exists) {
                this.applyValidationResult(input, false, 'هذه القيمة موجودة بالفعل');
                return false;
            }
            
            this.applyValidationResult(input, true, '');
            return true;
        } catch (error) {
            console.error('Uniqueness validation error:', error);
            return true; // السماح في حالة الخطأ
        }
    },

    /**
     * إنشاء قاعدة مخصصة
     * Create Custom Rule
     */
    addRule(name, validator, message) {
        this.rules[name] = validator;
        
        if (message) {
            if (typeof message === 'string') {
                this.messages.ar[name] = message;
                this.messages.en[name] = message;
            } else {
                Object.assign(this.messages.ar, { [name]: message.ar });
                Object.assign(this.messages.en, { [name]: message.en });
            }
        }
    },

    /**
     * إزالة التحقق من النموذج
     * Remove Validation from Form
     */
    clearFormValidation(form) {
        form.classList.remove('was-validated');
        form.querySelectorAll('.is-valid, .is-invalid').forEach(input => {
            input.classList.remove('is-valid', 'is-invalid');
        });
        
        form.querySelectorAll('.invalid-feedback, .valid-feedback').forEach(feedback => {
            feedback.remove();
        });
    },

    /**
     * تصدير بيانات التحقق
     * Export Validation Data
     */
    getValidationSummary(form) {
        const summary = {
            isValid: true,
            errors: [],
            fields: {}
        };
        
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            const isValid = this.validateField(input);
            const fieldName = input.name || input.id;
            
            summary.fields[fieldName] = {
                valid: isValid,
                value: input.value,
                rules: this.getFieldRules(input)
            };
            
            if (!isValid) {
                summary.isValid = false;
                summary.errors.push({
                    field: fieldName,
                    message: input.parentElement.querySelector('.invalid-feedback')?.textContent || 'خطأ في التحقق'
                });
            }
        });
        
        return summary;
    }
};

// تهيئة نظام التحقق عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', () => {
    TadbeerValidation.init();
});

// تصدير النظام للاستخدام العام
window.TadbeerValidation = TadbeerValidation;
