<?php
// db.php
$host = 'localhost';
$port = 3307;         // پورت MySQL
$dbname = 'carwash_db';
$user = 'root';
$pass = '';

function getDBConnection()
{
    global $host, $port, $dbname, $user, $pass;

    try {
        // اتصال PDO با پورت مشخص
        $pdo = new PDO(
            "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
            $user,
            $pass
        );

        // نمایش خطاها بصورت Exception
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // بررسی اینکه دیتابیس واقعی وجود داره
        $stmt = $pdo->query("SELECT DATABASE()");
        if ($stmt->fetchColumn() !== $dbname) {
            throw new Exception("Database '$dbname' not found!");
        }

        return $pdo;
    } catch (PDOException $e) {
        echo "Database connection failed (PDOException): " . $e->getMessage();
        return null;
    } catch (Exception $e) {
        echo "Database error: " . $e->getMessage();
        return null;
    }
}

// تست اتصال
$pdo = getDBConnection();
if ($pdo) {
    echo "✅ Database connected successfully!";
} else {
    echo "❌ Database connection failed!";
}
