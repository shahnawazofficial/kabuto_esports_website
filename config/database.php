<?php
/**
 * KABUTO ESPORTS - Database Connection (Singleton PDO)
 */

require_once __DIR__ . '/config.php';

class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    /**
     * Get PDO singleton instance.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                if (APP_DEBUG) {
                    throw $e;
                }
                error_log('Database connection failed: ' . $e->getMessage());
                http_response_code(500);
                die(json_encode(['error' => 'Database connection error. Please try again later.']));
            }
        }

        return self::$instance;
    }

    /**
     * Execute a prepared statement and return the statement.
     */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $pdo  = self::getInstance();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch single row.
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $result = self::query($sql, $params)->fetch();
        return $result === false ? null : $result;
    }

    /**
     * Fetch all rows.
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Get last inserted ID.
     */
    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }

    /**
     * Begin transaction.
     */
    public static function beginTransaction(): void
    {
        self::getInstance()->beginTransaction();
    }

    /**
     * Commit transaction.
     */
    public static function commit(): void
    {
        self::getInstance()->commit();
    }

    /**
     * Rollback transaction.
     */
    public static function rollback(): void
    {
        self::getInstance()->rollBack();
    }
}
