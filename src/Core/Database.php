<?php
namespace Core;
use PDO;
use PDOException;

class Database {
    private static ?PDO $pdo = null;
    
    public static function getInstance(): PDO {
        if (self::$pdo === null) {
            $cfg = Config::get('db');
            $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}";
            try {
                self::$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]);
            } catch (PDOException $e) {
                error_log("DB Connection failed: " . $e->getMessage());
                throw $e;
            }
        }
        return self::$pdo;
    }
    
    public static function query(string $sql, array $params = []): \PDOStatement {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public static function fetchOne(string $sql, array $params = []) {
        return self::query($sql, $params)->fetch();
    }
    
    public static function fetchAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll();
    }
    
    public static function lastInsertId(): string {
        return self::getInstance()->lastInsertId();
    }
}
