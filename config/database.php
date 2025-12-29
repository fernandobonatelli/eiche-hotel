<?php
/**
 * Eiche Hotel - Sistema de Hotelaria e Cobrança
 * Configuração de Banco de Dados com PDO - PHP 8.x
 * 
 * @version 2.0
 * @license GNU GPL v3
 */

declare(strict_types=1);

namespace Eiche\Config;

class Database
{
    private static ?Database $instance = null;
    private ?\PDO $connection = null;
    
    private string $host;
    private string $database;
    private string $username;
    private string $password;
    private string $charset = 'utf8mb4';
    
    private function __construct()
    {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->database = $_ENV['DB_DATABASE'] ?? 'pous3527_eiche';
        $this->username = $_ENV['DB_USERNAME'] ?? 'pous3527_root';
        $this->password = $_ENV['DB_PASSWORD'] ?? ';Fb6818103200';
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection(): \PDO
    {
        if ($this->connection === null) {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
            
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
            ];
            
            try {
                $this->connection = new \PDO($dsn, $this->username, $this->password, $options);
            } catch (\PDOException $e) {
                throw new \RuntimeException('Erro de conexão: ' . $e->getMessage());
            }
        }
        
        return $this->connection;
    }
    
    /**
     * Executa uma query preparada com parâmetros
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Retorna um único registro
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }
    
    /**
     * Retorna todos os registros
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }
    
    /**
     * Retorna o último ID inserido
     */
    public function lastInsertId(): string
    {
        return $this->getConnection()->lastInsertId();
    }
    
    /**
     * Inicia uma transação
     */
    public function beginTransaction(): bool
    {
        return $this->getConnection()->beginTransaction();
    }
    
    /**
     * Confirma uma transação
     */
    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }
    
    /**
     * Reverte uma transação
     */
    public function rollback(): bool
    {
        return $this->getConnection()->rollBack();
    }
    
    // Previne clonagem e desserialização
    private function __clone() {}
    public function __wakeup() 
    {
        throw new \RuntimeException("Cannot unserialize singleton");
    }
}

/**
 * Função helper para manter compatibilidade com código legado
 */
function getDB(): Database
{
    return Database::getInstance();
}

