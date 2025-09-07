<?php
/**
 * ==============================================
 * تدبير بلس - إعدادات قاعدة البيانات
 * Tadbeer Plus - Database Configuration
 * ==============================================
 */

// منع الوصول المباشر
if (!defined('TADBEER_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * فئة اتصال قاعدة البيانات
 * Database Connection Class
 */
class Database {
    
    private static $instance = null;
    private $pdo = null;
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;
    private $options;
    
    /**
     * Constructor - إعداد معايير الاتصال
     */
    private function __construct() {
        $this->host = DB_HOST;
        $this->dbname = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;
        
        // إعدادات PDO
        $this->options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset} COLLATE utf8mb4_unicode_ci",
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_TIMEOUT => 30
        ];
        
        $this->connect();
    }
    
    /**
     * الحصول على مثيل قاعدة البيانات
     * Get Database Instance (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * الاتصال بقاعدة البيانات
     * Connect to Database
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $this->pdo = new PDO($dsn, $this->username, $this->password, $this->options);
            
            // تعيين المنطقة الزمنية لقاعدة البيانات
            $this->pdo->exec("SET time_zone = '+04:00'");
            
            // تعيين وضع SQL الآمن
            $this->pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            
        } catch (PDOException $e) {
            $this->logError("Database connection failed: " . $e->getMessage());
            
            if (DEBUG_MODE) {
                die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
            } else {
                die("خطأ في الاتصال بقاعدة البيانات. يرجى المحاولة لاحقاً.");
            }
        }
    }
    
    /**
     * الحصول على اتصال PDO
     * Get PDO Connection
     */
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * تنفيذ استعلام SELECT
     * Execute SELECT Query
     */
    public function select($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logError("Select query failed: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }
    
    /**
     * تنفيذ استعلام SELECT لسجل واحد
     * Execute SELECT Query for Single Record
     */
    public function selectOne($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logError("SelectOne query failed: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }
    
    /**
     * تنفيذ استعلام INSERT
     * Execute INSERT Query
     */
    public function insert($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            return $result ? $this->pdo->lastInsertId() : false;
        } catch (PDOException $e) {
            $this->logError("Insert query failed: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }
    
    /**
     * تنفيذ استعلام UPDATE
     * Execute UPDATE Query
     */
    public function update($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError("Update query failed: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }
    
    /**
     * تنفيذ استعلام DELETE
     * Execute DELETE Query
     */
    public function delete($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError("Delete query failed: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }
    
    /**
     * تنفيذ استعلام عام
     * Execute General Query
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            $this->logError("Query failed: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }
    
    /**
     * بدء معاملة
     * Begin Transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * تأكيد المعاملة
     * Commit Transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * إلغاء المعاملة
     * Rollback Transaction
     */
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    /**
     * الحصول على آخر معرف مُدرج
     * Get Last Insert ID
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * إدراج متعدد السجلات
     * Multiple Insert Records
     */
    public function insertMultiple($table, $data) {
        if (empty($data) || !is_array($data)) {
            return false;
        }
        
        try {
            $this->beginTransaction();
            
            $columns = array_keys($data[0]);
            $placeholders = ':' . implode(', :', $columns);
            $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES ({$placeholders})";
            
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($data as $row) {
                $stmt->execute($row);
            }
            
            $this->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->rollback();
            $this->logError("Multiple insert failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * البحث في الجدول
     * Search in Table
     */
    public function search($table, $columns, $searchTerm, $conditions = [], $limit = null) {
        $whereClause = [];
        $params = [];
        
        // إضافة شروط البحث
        foreach ($columns as $column) {
            $whereClause[] = "{$column} LIKE :search";
        }
        
        // إضافة الشروط الإضافية
        foreach ($conditions as $key => $value) {
            $whereClause[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }
        
        $sql = "SELECT * FROM {$table} WHERE (" . implode(' OR ', array_slice($whereClause, 0, count($columns))) . ")";
        
        if (!empty($conditions)) {
            $sql .= " AND (" . implode(' AND ', array_slice($whereClause, count($columns))) . ")";
        }
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $params['search'] = "%{$searchTerm}%";
        
        return $this->select($sql, $params);
    }
    
    /**
     * عدّ السجلات
     * Count Records
     */
    public function count($table, $conditions = []) {
        $whereClause = [];
        $params = [];
        
        foreach ($conditions as $key => $value) {
            $whereClause[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }
        
        $sql = "SELECT COUNT(*) as total FROM {$table}";
        
        if (!empty($whereClause)) {
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        $result = $this->selectOne($sql, $params);
        return $result ? $result['total'] : 0;
    }
    
    /**
     * التحقق من وجود سجل
     * Check if Record Exists
     */
    public function exists($table, $conditions) {
        return $this->count($table, $conditions) > 0;
    }
    
    /**
     * تسجيل الأخطاء
     * Log Errors
     */
    private function logError($message) {
        $logFile = LOGS_PATH . '/database_errors.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
        
        if (is_writable(LOGS_PATH)) {
            file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * إغلاق الاتصال
     * Close Connection
     */
    public function close() {
        $this->pdo = null;
    }
    
    /**
     * منع الاستنساخ
     * Prevent Cloning
     */
    private function __clone() {}
    
    /**
     * منع إلغاء التسلسل
     * Prevent Unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * ==============================================
 * الدوال المساعدة لقاعدة البيانات
 * Database Helper Functions
 * ==============================================
 */

/**
 * الحصول على اتصال قاعدة البيانات
 * Get Database Connection
 */
function getDB() {
    return Database::getInstance();
}

/**
 * الحصول على اتصال PDO مباشر
 * Get Direct PDO Connection
 */
function getPDO() {
    return Database::getInstance()->getConnection();
}

/**
 * تنفيذ استعلام بسيط
 * Execute Simple Query
 */
function dbQuery($sql, $params = []) {
    return getDB()->select($sql, $params);
}

/**
 * تنفيذ استعلام لسجل واحد
 * Execute Single Record Query
 */
function dbQueryOne($sql, $params = []) {
    return getDB()->selectOne($sql, $params);
}

/**
 * إدراج سجل جديد
 * Insert New Record
 */
function dbInsert($sql, $params = []) {
    return getDB()->insert($sql, $params);
}

/**
 * تحديث سجل
 * Update Record
 */
function dbUpdate($sql, $params = []) {
    return getDB()->update($sql, $params);
}

/**
 * حذف سجل
 * Delete Record
 */
function dbDelete($sql, $params = []) {
    return getDB()->delete($sql, $params);
}

/**
 * التحقق من الاتصال بقاعدة البيانات
 * Check Database Connection
 */
function checkDatabaseConnection() {
    try {
        $db = getDB();
        $result = $db->selectOne("SELECT 1 as test");
        return $result !== false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * إنشاء اتصال قاعدة البيانات عند التحميل
 * Initialize Database Connection
 */
try {
    $pdo = getPDO();
} catch (Exception $e) {
    if (DEBUG_MODE) {
        die("خطأ في إعداد قاعدة البيانات: " . $e->getMessage());
    } else {
        die("خطأ في النظام. يرجى المحاولة لاحقاً.");
    }
}

?>
