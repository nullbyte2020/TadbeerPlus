#!/bin/bash

# ==============================================
# 🏗️ سكريبت تثبيت نظام تدبير بلس
# Tadbeer Plus Installation Script
# ==============================================

echo "🚀 بدء تثبيت نظام تدبير بلس..."
echo "🚀 Starting Tadbeer Plus installation..."

# تعيين الألوان للرسائل
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# دالة لطباعة الرسائل الملونة
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

# التحقق من صلاحيات المدير
if [[ $EUID -eq 0 ]]; then
   print_error "لا تقم بتشغيل هذا السكريبت كـ root مباشرة"
   print_error "Do not run this script as root directly"
   exit 1
fi

# التحقق من وجود sudo
if ! command -v sudo &> /dev/null; then
    print_error "sudo غير موجود. يرجى تثبيته أولاً"
    print_error "sudo is not installed. Please install it first"
    exit 1
fi

# تحديث قائمة الحزم
print_status "تحديث قائمة الحزم..."
print_status "Updating package lists..."
sudo apt-get update -y

if [ $? -ne 0 ]; then
    print_error "فشل في تحديث قائمة الحزم"
    print_error "Failed to update package lists"
    exit 1
fi

# تثبيت الأدوات الأساسية
print_status "تثبيت الأدوات الأساسية..."
print_status "Installing essential tools..."
sudo apt-get install -y software-properties-common apt-transport-https lsb-release ca-certificates wget curl unzip git

# إضافة مستودع PHP (للإصدارات الحديثة)
print_status "إضافة مستودع PHP..."
print_status "Adding PHP repository..."
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update -y

# تثبيت PHP 8.0 أو أحدث
print_status "تثبيت PHP 8.0 والملحقات المطلوبة..."
print_status "Installing PHP 8.0 and required extensions..."

PHP_PACKAGES=(
    "php8.0"
    "php8.0-cli"
    "php8.0-common"
    "php8.0-fpm"
    "php8.0-mysql"
    "php8.0-pdo"
    "php8.0-mbstring"
    "php8.0-xml"
    "php8.0-curl"
    "php8.0-gd"
    "php8.0-zip"
    "php8.0-bcmath"
    "php8.0-intl"
    "php8.0-soap"
    "php8.0-json"
    "php8.0-opcache"
    "php8.0-readline"
    "php8.0-imagick"
    "php8.0-redis"
    "php8.0-memcached"
    "libapache2-mod-php8.0"
)

for package in "${PHP_PACKAGES[@]}"; do
    print_status "تثبيت $package..."
    sudo apt-get install -y $package
    
    if [ $? -ne 0 ]; then
        print_warning "فشل في تثبيت $package - المتابعة..."
    fi
done

# التحقق من تثبيت PHP
if ! command -v php &> /dev/null; then
    print_error "فشل في تثبيت PHP"
    print_error "Failed to install PHP"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;")
print_success "تم تثبيت PHP بنجاح - الإصدار: $PHP_VERSION"
print_success "PHP installed successfully - Version: $PHP_VERSION"

# تكوين PHP.ini
print_status "تكوين إعدادات PHP..."
print_status "Configuring PHP settings..."

PHP_INI_PATH=$(php --ini | grep "Loaded Configuration File" | cut -d: -f2 | xargs)
if [ -n "$PHP_INI_PATH" ] && [ -f "$PHP_INI_PATH" ]; then
    # إنشاء نسخة احتياطية
    sudo cp "$PHP_INI_PATH" "$PHP_INI_PATH.backup.$(date +%Y%m%d_%H%M%S)"
    
    # تحديث الإعدادات
    sudo sed -i 's/memory_limit = .*/memory_limit = 256M/' "$PHP_INI_PATH"
    sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 32M/' "$PHP_INI_PATH"
    sudo sed -i 's/post_max_size = .*/post_max_size = 32M/' "$PHP_INI_PATH"
    sudo sed -i 's/max_execution_time = .*/max_execution_time = 300/' "$PHP_INI_PATH"
    sudo sed -i 's/max_input_vars = .*/max_input_vars = 3000/' "$PHP_INI_PATH"
    
    print_success "تم تحديث إعدادات PHP بنجاح"
else
    print_warning "لم يتم العثور على ملف php.ini"
fi

# تثبيت Composer
print_status "تحقق من وجود Composer..."
print_status "Checking for Composer..."

if ! command -v composer &> /dev/null; then
    print_status "Composer غير موجود، جاري التثبيت..."
    print_status "Composer not found, installing..."
    
    # تحميل Composer
    cd /tmp
    curl -sS https://getcomposer.org/installer -o composer-setup.php
    
    if [ $? -ne 0 ]; then
        print_error "فشل في تحميل Composer installer"
        print_error "Failed to download Composer installer"
        exit 1
    fi
    
    # التحقق من صحة المثبت
    HASH=`curl -sS https://composer.github.io/installer.sig`
    php -r "if (hash_file('SHA384', 'composer-setup.php') === '$HASH') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
    
    # تثبيت Composer
    sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    
    if [ $? -eq 0 ]; then
        print_success "تم تثبيت Composer بنجاح"
        print_success "Composer installed successfully"
    else
        print_error "فشل في تثبيت Composer"
        print_error "Failed to install Composer"
        exit 1
    fi
    
    # تنظيف الملفات المؤقتة
    rm composer-setup.php
else
    print_success "Composer موجود بالفعل"
    print_success "Composer is already installed"
fi

# التحقق من إصدار Composer
COMPOSER_VERSION=$(composer --version)
print_status "إصدار Composer: $COMPOSER_VERSION"

# تثبيت Node.js و NPM (للـ Frontend assets)
print_status "تثبيت Node.js و NPM..."
print_status "Installing Node.js and NPM..."

curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

if command -v node &> /dev/null && command -v npm &> /dev/null; then
    NODE_VERSION=$(node --version)
    NPM_VERSION=$(npm --version)
    print_success "Node.js $NODE_VERSION و NPM $NPM_VERSION تم تثبيتهما بنجاح"
else
    print_warning "فشل في تثبيت Node.js/NPM"
fi

# تثبيت أدوات إضافية مفيدة
print_status "تثبيت أدوات إضافية..."
print_status "Installing additional tools..."

ADDITIONAL_TOOLS=(
    "mysql-client"
    "redis-tools"
    "imagemagick"
    "ghostscript"
    "poppler-utils"
    "wkhtmltopdf"
)

for tool in "${ADDITIONAL_TOOLS[@]}"; do
    sudo apt-get install -y $tool 2>/dev/null || print_warning "فشل في تثبيت $tool"
done

# إنشاء مجلدات المشروع إذا لم تكن موجودة
print_status "إنشاء مجلدات المشروع..."
print_status "Creating project directories..."

PROJECT_DIRS=(
    "uploads/documents/workers"
    "uploads/documents/clients" 
    "uploads/documents/contracts"
    "uploads/photos/workers"
    "uploads/photos/clients"
    "uploads/certificates/medical"
    "uploads/certificates/training"
    "uploads/certificates/experience"
    "logs"
    "backup/database"
    "backup/files"
    "contracts/generated"
    "reports/generated"
)

for dir in "${PROJECT_DIRS[@]}"; do
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
        print_status "تم إنشاء المجلد: $dir"
    fi
done

# تعيين الصلاحيات المناسبة
print_status "تعيين صلاحيات المجلدات..."
print_status "Setting directory permissions..."

chmod -R 755 uploads/ 2>/dev/null || print_warning "فشل في تعيين صلاحيات uploads"
chmod -R 755 logs/ 2>/dev/null || print_warning "فشل في تعيين صلاحيات logs" 
chmod -R 755 backup/ 2>/dev/null || print_warning "فشل في تعيين صلاحيات backup"
chmod -R 755 contracts/generated/ 2>/dev/null || print_warning "فشل في تعيين صلاحيات contracts"

# تثبيت مكتبات Composer إذا كان ملف composer.json موجوداً
if [ -f "composer.json" ]; then
    print_status "تثبيت مكتبات Composer..."
    print_status "Installing Composer packages..."
    
    composer install --optimize-autoloader --no-dev
    
    if [ $? -eq 0 ]; then
        print_success "تم تثبيت مكتبات Composer بنجاح"
        print_success "Composer packages installed successfully"
    else
        print_error "فشل في تثبيت مكتبات Composer"
        print_error "Failed to install Composer packages"
    fi
else
    print_warning "ملف composer.json غير موجود"
    print_warning "composer.json file not found"
fi

# تثبيت مكتبات NPM إذا كان ملف package.json موجوداً
if [ -f "package.json" ]; then
    print_status "تثبيت مكتبات NPM..."
    print_status "Installing NPM packages..."
    
    npm install
    
    if [ $? -eq 0 ]; then
        print_success "تم تثبيت مكتبات NPM بنجاح"
        print_success "NPM packages installed successfully"
        
        # بناء الـ Assets
        if [ -f "package.json" ] && grep -q "build" package.json; then
            print_status "بناء Frontend assets..."
            npm run build 2>/dev/null || print_warning "فشل في بناء الـ assets"
        fi
    else
        print_warning "فشل في تثبيت مكتبات NPM"
    fi
fi

# إنشاء ملف .env إذا كان غير موجود
if [ ! -f ".env" ] && [ -f ".env.example" ]; then
    print_status "إنشاء ملف .env..."
    cp .env.example .env
    print_success "تم إنشاء ملف .env من .env.example"
fi

# عرض معلومات النظام المثبت
print_success "🎉 تم تثبيت النظام بنجاح!"
print_success "🎉 System installation completed successfully!"

echo ""
echo "=============================================="
echo "📋 معلومات النظام المثبت / System Information"
echo "=============================================="
echo "🔹 PHP Version: $(php -r 'echo PHP_VERSION;')"
echo "🔹 Composer Version: $(composer --version --no-ansi | head -1)"

if command -v node &> /dev/null; then
    echo "🔹 Node.js Version: $(node --version)"
fi

if command -v npm &> /dev/null; then
    echo "🔹 NPM Version: $(npm --version)"
fi

echo ""
echo "=============================================="
echo "📝 الخطوات التالية / Next Steps"
echo "=============================================="
echo "1. قم بتكوين قاعدة البيانات في ملف config/db.php"
echo "   Configure database in config/db.php"
echo ""
echo "2. قم بإنشاء قاعدة البيانات:"
echo "   mysql -u root -p < tadbeer_plus.sql"
echo ""
echo "3. تأكد من إعدادات الخادم (Apache/Nginx)"
echo "   Make sure your web server is configured"
echo ""
echo "4. تصفح الموقع:"
echo "   http://your-domain.com"
echo ""

# التحقق من وجود أخطاء
if [ $? -eq 0 ]; then
    print_success "✅ انتهت عملية التثبيت بنجاح"
    print_success "✅ Installation completed successfully"
    exit 0
else
    print_error "❌ حدثت أخطاء أثناء التثبيت"
    print_error "❌ Errors occurred during installation"
    exit 1
fi
