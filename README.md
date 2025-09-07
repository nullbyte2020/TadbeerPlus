# 🏢 تدبير بلس - نظام إدارة العمالة المنزلية

<div align="center">
  <img src="assets/images/logo.png" alt="Tadbeer Plus Logo" width="200"/>
  
  [![PHP Version](https://img.shields.io/badge/PHP-8.0+-blue.svg)](https://php.net/)
  [![MySQL Version](https://img.shields.io/badge/MySQL-8.0+-orange.svg)](https://mysql.com/)
  [![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
  [![Arabic Support](https://img.shields.io/badge/العربية-مدعومة-success.svg)]()
</div>

## 📋 نظرة عامة

**تدبير بلس** هو نظام شامل لإدارة العمالة المنزلية والخدم في دولة الإمارات العربية المتحدة. يوفر النظام حلولاً متكاملة لإدارة العقود، المدفوعات، ومتابعة العمالة المنزلية بما يتوافق مع القوانين المحلية.

## ✨ المميزات الرئيسية

### 🏠 **إدارة العمالة المنزلية**
- تسجيل وإدارة بيانات الخدم والعمالة المنزلية
- نظام تصنيف حسب الخبرة والمهارات
- إدارة الوثائق والشهادات الطبية
- متابعة حالة التأشيرات وانتهاء صلاحيتها

### 👥 **إدارة العملاء**
- تسجيل وإدارة بيانات أصحاب العمل
- نظام تقييم العملاء والتعليقات
- إدارة المتطلبات الخاصة لكل عميل
- تتبع تاريخ التعاملات السابقة

### 📝 **نظام العقود المتقدم**
- قوالب عقود متوافقة مع القانون الإماراتي
- إنشاء عقود تلقائية باللغتين العربية والإنجليزية
- إدارة شروط العقود وتعديلها
- نظام التوقيع الإلكتروني
- تتبع تواريخ بداية وانتهاء العقود

### 💰 **النظام المالي الشامل**
- إدارة الفواتير والمدفوعات
- نظام محاسبي مزدوج القيد
- تتبع العمولات والأرباح
- تقارير مالية تفصيلية
- دعم العملات المختلفة مع تحويل تلقائي

### 👨‍💼 **إدارة متعددة المستويات**
- نظام أدوار وصلاحيات متقدم
- لوحات تحكم مخصصة لكل نوع مستخدم
- إدارة الموظفين والأقسام
- نظام تقييم الأداء

### 📊 **التقارير والإحصائيات**
- تقارير شاملة عن العمليات
- رسوم بيانية تفاعلية
- تصدير للـ PDF و Excel
- تقارير مالية دورية

### 🔔 **نظام الإشعارات**
- تنبيهات انتهاء العقود
- تذكيرات المدفوعات المستحقة
- إشعارات حالة العمالة
- تنبيهات النظام والأمان

## 🛠️ التقنيات المستخدمة

### **Backend**
- **PHP 8.0+** - لغة البرمجة الأساسية
- **MySQL 8.0+** - قاعدة البيانات
- **Composer** - إدارة المكتبات
- **PDO** - للتعامل مع قاعدة البيانات

### **Frontend**
- **HTML5 & CSS3** - الهيكل والتصميم
- **Bootstrap 5** - إطار العمل التصميمي
- **JavaScript & jQuery** - التفاعل والديناميكية
- **Chart.js** - الرسوم البيانية
- **DataTables** - جداول البيانات التفاعلية

### **Tools & Libraries**
- **mPDF** - إنشاء ملفات PDF
- **PHPSpreadsheet** - تصدير Excel
- **PHPMailer** - إرسال الإيميلات
- **JWT** - المصادقة الآمنة

## 📦 متطلبات التشغيل

### **متطلبات الخادم**
- **PHP**: 8.0 أو أحدث
- **MySQL**: 8.0 أو أحدث  
- **Apache/Nginx**: مع mod_rewrite مفعل
- **Extensions**: PDO, mbstring, JSON, cURL, GD, ZIP, OpenSSL

### **الذاكرة والأداء**
- **Memory Limit**: 256MB كحد أدنى
- **Max Execution Time**: 300 ثانية
- **Upload Max Size**: 32MB
- **Post Max Size**: 32MB

## 🚀 التثبيت والإعداد

### 1. **تحميل المشروع**
git clone https://github.com/tadbeerplus/domestic-workers-management.git
cd tadbeer-plu

text

### 2. **تثبيت المكتبات**
composer install
npm i

text

### 3. **إعداد قاعدة البيانات**
إنشاء قاعدة البيانات
mysql -u root -p < database/tadbeer_plus.sql

أو استخدام phpMyAdmin لاستيراد الملف
text

### 4. **إعداد ملف التكوين**
نسخ ملف الإعدادات
cp config/config.example.php config/config.php

تعديل إعدادات قاعدة البيانات
nano config/db.php

text

### 5. **إعداد الصلاحيات**
chmod -R 755 uploads/
chmod -R 755 logs/
chmod -R 755 backup/
text

### 6. **تشغيل البناء**
npm run build

text

## 🔧 الإعدادات الأساسية

### **قاعدة البيانات**
// config/db.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'tadbeer_plus');
define('DB_USER', 'your_username');
text

### **إعدادات البريد الإلكتروني**
// config/email.php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_
r

text

## 👥 المستخدمون الافتراضيون

| المستخدم | البريد الإلكتروني | كلمة المرور | الدور |
|-----------|------------------|------------|--------|
| Super Admin | superadmin@tadbeerplus.ae | admin123 | مدير عام |
| Admin | admin@tadbeerplus.ae | admin123 | مدير النظام |
| Client Demo | demo@client.ae | client123 | عميل تجريبي |

## 📱 واجهات النظام

### **لوحة تحكم المدير**
- `/admin/` - إدارة شاملة للنظام
- إدارة المستخدمين والصلاحيات
- إدارة العمالة والعملاء
- النظام المالي والتقارير

### **منطقة العملاء**
- `/client/` - واجهة العملاء
- عرض العقود والمدفوعات
- تقديم الطلبات الجديدة
- التواصل مع الدعم

### **وحدة المبيعات**
- `/sales/` - إدارة المبيعات
- متابعة العروض والعقود
- إدارة العملاء المحتملين

## 🔒 الأمان

- **تشفير كلمات المرور**: bcrypt
- **حماية من CSRF**: Token-based
- **حماية من XSS**: Input validation & output escaping
- **حماية من SQL Injection**: Prepared statements
- **جلسات آمنة**: Secure session handling
- **تسجيل العمليات**: Activity logging

## 📈 المراقبة والصيانة

### **السجلات**
- `logs/error.log` - أخطاء النظام
- `logs/activity.log` - سجل العمليات
- `logs/security.log` - سجل الأمان

### **النسخ الاحتياطية**
نسخة احتياطية تلقائية
php scripts/backup_database.php

نسخة احتياطية يدوية
./backup_system.sh

text

## 🤝 المساهمة

نرحب بالمساهمات من المطورين! يرجى:

1. Fork المشروع
2. إنشاء branch جديد (`git checkout -b feature/amazing-feature`)
3. Commit التغييرات (`git commit -m 'Add amazing feature'`)
4. Push للـ branch (`git push origin feature/amazing-feature`)
5. فتح Pull Request

## 📄 الترخيص

هذا المشروع مرخص تحت رخصة MIT. راجع ملف [LICENSE](LICENSE) للمزيد من التفاصيل.

## 📞 الدعم والتواصل

- **الموقع الرسمي**: [tadbeerplus.ae](https://tadbeerplus.ae)
- **البريد الإلكتروني**: support@tadbeerplus.ae
- **الهاتف**: +971-4-1234567
- **المطورين**: dev@tadbeerplus.ae

## 🚧 خارطة الطريق

### **الإصدار 1.1.0**
- [ ] تطبيق جوال
- [ ] API RESTful كامل
- [ ] نظام تقارير متقدم

### **الإصدار 1.2.0**
- [ ] دعم الذكاء الاصطناعي
- [ ] تحليلات متقدمة
- [ ] تكامل مع الجهات الحكومية

---

<div align="center">
  <p>صُنع بـ ❤️ في دولة الإمارات العربية المتحدة</p>
  <p>Made with ❤️ in United Arab Emirates</p>
</div>