<?php
/**
 * Pousada Bona - Script de Migração de Senhas
 * Converte senhas MD5 para BCrypt
 * 
 * IMPORTANTE: Execute este script apenas uma vez após a migração!
 * 
 * @version 2.0
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Migração de Senhas</title>";
echo "<style>body{font-family:sans-serif;padding:40px;max-width:800px;margin:0 auto}";
echo ".success{color:#27ae60}.error{color:#e74c3c}.info{color:#3498db}";
echo "pre{background:#f5f5f5;padding:20px;border-radius:8px;overflow-x:auto}</style></head><body>";
echo "<h1>🔐 Migração de Senhas - Pousada Bona</h1>";

// Verificar se já foi executado
$lockFile = __DIR__ . '/.migration_lock';
if (file_exists($lockFile)) {
    echo "<p class='error'>⚠️ Este script já foi executado. Por segurança, não pode ser executado novamente.</p>";
    echo "<p>Se precisar executar novamente, remova o arquivo: <code>.migration_lock</code></p>";
    exit;
}

require_once __DIR__ . '/../config/database.php';

use Eiche\Config\Database;

try {
    $db = Database::getInstance();
    
    echo "<h2>Iniciando migração...</h2>";
    
    // Buscar todos os usuários
    $users = $db->fetchAll("SELECT ID, login, password FROM eiche_users");
    
    $migrated = 0;
    $alreadyMigrated = 0;
    $errors = 0;
    
    echo "<pre>";
    
    foreach ($users as $user) {
        $password = $user['password'];
        
        // Verificar se já é BCrypt (começa com $2y$ ou $2a$)
        if (substr($password, 0, 4) === '$2y$' || substr($password, 0, 4) === '$2a$') {
            echo "✓ Usuário <strong>{$user['login']}</strong>: Senha já em formato BCrypt\n";
            $alreadyMigrated++;
            continue;
        }
        
        // Se for MD5 (32 caracteres hex), não podemos converter diretamente
        // Precisamos gerar uma senha temporária
        if (preg_match('/^[a-f0-9]{32}$/', $password)) {
            // Gerar senha temporária
            $tempPassword = substr(bin2hex(random_bytes(4)), 0, 8);
            $newHash = password_hash($tempPassword, PASSWORD_DEFAULT);
            
            $db->query("UPDATE eiche_users SET password = ? WHERE ID = ?", [$newHash, $user['ID']]);
            
            echo "⚡ Usuário <strong>{$user['login']}</strong>: Senha MD5 convertida. Nova senha temporária: <strong>{$tempPassword}</strong>\n";
            $migrated++;
        } else {
            // Senha em texto plano ou formato desconhecido - converter para BCrypt
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $db->query("UPDATE eiche_users SET password = ? WHERE ID = ?", [$newHash, $user['ID']]);
            
            echo "✓ Usuário <strong>{$user['login']}</strong>: Senha convertida para BCrypt\n";
            $migrated++;
        }
    }
    
    echo "</pre>";
    
    echo "<h2>Resumo da Migração</h2>";
    echo "<ul>";
    echo "<li class='success'>✓ Senhas migradas: <strong>{$migrated}</strong></li>";
    echo "<li class='info'>ℹ️ Já em formato BCrypt: <strong>{$alreadyMigrated}</strong></li>";
    if ($errors > 0) {
        echo "<li class='error'>✗ Erros: <strong>{$errors}</strong></li>";
    }
    echo "</ul>";
    
    // Criar arquivo de lock
    file_put_contents($lockFile, date('Y-m-d H:i:s'));
    
    echo "<h2 class='success'>✅ Migração concluída com sucesso!</h2>";
    echo "<p><strong>IMPORTANTE:</strong> Usuários com senhas MD5 receberam senhas temporárias. ";
    echo "Informe a eles suas novas senhas ou peça que usem a opção 'Esqueci minha senha'.</p>";
    
} catch (\Exception $e) {
    echo "<h2 class='error'>❌ Erro na migração</h2>";
    echo "<pre class='error'>" . htmlspecialchars($e->getMessage()) . "</pre>";
}

echo "</body></html>";

