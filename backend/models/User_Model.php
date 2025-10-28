<?php
declare(strict_types=1);

namespace App\Models;

use App\Classes\Database;

/**
 * مدل کاربران با پشتیبانی از رمزنگاری ایمن
 */
class User_Model
{
    private $db;
    protected $table = 'users';
    
    /**
     * سازنده
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * هش‌کردن رمز عبور با استفاده از الگوریتم bcrypt
     * 
     * @param string $password رمز عبور خام
     * @return string رمز عبور هش‌شده
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    }
    
    /**
     * تأیید رمز عبور
     * 
     * @param string $password رمز عبور خام
     * @param string $hash رمز عبور هش‌شده
     * @return bool نتیجه تأیید رمز عبور
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    /**
     * بررسی نیاز به هش مجدد رمز عبور
     * 
     * @param string $hash رمز عبور هش‌شده
     * @return bool آیا نیاز به هش مجدد دارد
     */
    public function passwordNeedsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_DEFAULT, ['cost' => 12]);
    }
    
    /**
     * به‌روزرسانی رمز عبور کاربر
     * 
     * @param int $userId شناسه کاربر
     * @param string $newHash رمز عبور هش‌شده جدید
     * @return bool نتیجه به‌روزرسانی
     */
    public function updatePassword(int $userId, string $newHash): bool
    {
        return $this->db->update(
            $this->table,
            ['password' => $newHash],
            ['id' => $userId]
        );
    }
    
    /**
     * ثبت کاربر جدید
     * 
     * @param array $userData اطلاعات کاربر
     * @return int|false شناسه کاربر جدید یا false در صورت خطا
     */
    public function register(array $userData)
    {
        if (isset($userData['password'])) {
            $userData['password'] = $this->hashPassword($userData['password']);
        }
        
        return $this->db->insert($this->table, $userData);
    }
    
    /**
     * احراز هویت کاربر
     * 
     * @param string $email ایمیل کاربر
     * @param string $password رمز عبور
     * @return array|false اطلاعات کاربر یا false در صورت عدم تطابق
     */
    public function authenticate(string $email, string $password)
    {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            return false;
        }
        
        // تأیید رمز عبور
        if (!$this->verifyPassword($password, $user['password'])) {
            return false;
        }
        
        // بررسی نیاز به هش مجدد رمز عبور
        if ($this->passwordNeedsRehash($user['password'])) {
            $newHash = $this->hashPassword($password);
            $this->updatePassword($user['id'], $newHash);
        }
        
        return $user;
    }
    
    /**
     * یافتن کاربر با شناسه
     * 
     * @param int $id شناسه کاربر
     * @return array|null داده کاربر یا null اگر یافت نشد
     */
    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * یافتن کاربر با ایمیل
     * 
     * @param string $email ایمیل کاربر
     * @return array|null داده کاربر یا null اگر یافت نشد
     */
    public function findByEmail(string $email): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE email = :email",
            ['email' => $email]
        );
    }
    
    /**
     * یافتن همه کاربران با فیلترهای اختیاری
     * 
     * @param array $conditions شرط‌های WHERE
     * @param string $orderBy مرتب‌سازی بر اساس فیلد
     * @param string $direction جهت مرتب‌سازی
     * @return array داده‌های کاربران
     */
    public function findAll(array $conditions = [], string $orderBy = 'id', string $direction = 'ASC'): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereConditions = [];
            
            foreach ($conditions as $column => $value) {
                $whereConditions[] = "$column = :$column";
                $params[$column] = $value;
            }
            
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $sql .= " ORDER BY $orderBy $direction";
        
        return $this->db->fetchAll($sql, $params);
    }
}
