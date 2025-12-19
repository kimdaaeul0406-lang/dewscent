<?php
// includes/db.php
// MySQL 데이터베이스 연결 클래스

require_once __DIR__ . '/config.php';

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('DB 연결 실패: ' . $e->getMessage());
            // 프로덕션에서는 상세 오류 메시지 숨김
            if (defined('APP_DEBUG') && APP_DEBUG) {
                die("DB 연결 실패: " . $e->getMessage());
            } else {
                die("데이터베이스 연결에 실패했습니다. 잠시 후 다시 시도해주세요.");
            }
        }
    }

    // 싱글톤 패턴 - 연결 하나만 유지
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // PDO 객체 반환
    public function getConnection()
    {
        return $this->pdo;
    }

    // SELECT 쿼리 실행 (여러 행)
    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // SELECT 쿼리 실행 (한 행)
    public function fetchOne($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    // INSERT, UPDATE, DELETE 쿼리 실행
    public function execute($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // INSERT 후 마지막 ID 반환
    public function insert($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $this->pdo->lastInsertId();
    }
}

// 편의 함수 - DB 인스턴스 가져오기
function db()
{
    return Database::getInstance();
}
