<?php
// File: utils/Database.php

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $config = [
            'host' => 'medhelp-db-1',
            'dbname' => 'medical_db',
            'user' => 'admin',
            'pass' => 'admin',
            'charset' => 'utf8'
        ];

        $dsn = "pgsql:host={$config['host']};port=5432;dbname={$config['dbname']}";

        try {
            $this->pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

   
    public function getConnection(): PDO {
        return $this->pdo;
    }
}

?>
