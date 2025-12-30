<?php
/**
 * Teste de Conexão com Banco de Dados
 * Acesse: https://seusite.com/v2/public/teste-conexao.php
 * 
 * ⚠️ APAGUE ESTE ARQUIVO APÓS O TESTE!
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Teste de Conexão</title>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; background: #f5f5f5; }
    .box { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .success { color: #27ae60; }
    .error { color: #e74c3c; }
    .warning { color: #f39c12; }
    .info { color: #3498db; }
    pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: #f8f9fa; }
</style></head><body>";

echo "<h1>🔍 Teste de Conexão - Pousada Bona v2</h1>";

// Teste 1: Verificar versão do PHP
echo "<div class='box'>";
echo "<h2>1️⃣ Versão do PHP</h2>";
$phpVersion = phpversion();
if (version_compare($phpVersion, '7.4.0', '>=')) {
    echo "<p class='success'>✅ PHP {$phpVersion} - OK!</p>";
} else {
    echo "<p class='error'>❌ PHP {$phpVersion} - Versão muito antiga (mínimo 7.4)</p>";
}
echo "</div>";

// Teste 2: Extensões necessárias
echo "<div class='box'>";
echo "<h2>2️⃣ Extensões PHP</h2>";
echo "<table>";
$extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
foreach ($extensions as $ext) {
    $status = extension_loaded($ext);
    $icon = $status ? '✅' : '❌';
    $class = $status ? 'success' : 'error';
    echo "<tr><td>{$ext}</td><td class='{$class}'>{$icon}</td></tr>";
}
echo "</table>";
echo "</div>";

// Teste 3: Conexão com banco
echo "<div class='box'>";
echo "<h2>3️⃣ Conexão com Banco de Dados</h2>";

$host = 'localhost';
$database = 'pous3527_eiche';
$username = 'pous3527_root';
$password = ';Fb6818103200';

echo "<table>";
echo "<tr><th>Parâmetro</th><th>Valor</th></tr>";
echo "<tr><td>Host</td><td>{$host}</td></tr>";
echo "<tr><td>Banco</td><td>{$database}</td></tr>";
echo "<tr><td>Usuário</td><td>{$username}</td></tr>";
echo "<tr><td>Senha</td><td>••••••••</td></tr>";
echo "</table><br>";

try {
    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p class='success'>✅ Conexão com banco de dados OK!</p>";
    
    // Verificar tabela de usuários
    echo "<h3>4️⃣ Verificando tabela de usuários</h3>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM eiche_users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p class='info'>📊 Total de usuários: <strong>{$result['total']}</strong></p>";
    
    // Listar usuários (sem senha)
    $stmt = $pdo->query("SELECT ID, login, name FROM eiche_users LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Login</th><th>Nome</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['ID']}</td>";
            echo "<td><strong>{$user['login']}</strong></td>";
            echo "<td>{$user['name']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ Nenhum usuário encontrado na tabela!</p>";
    }
    
    // Verificar formato das senhas
    echo "<h3>5️⃣ Verificando formato das senhas</h3>";
    $stmt = $pdo->query("SELECT ID, login, password FROM eiche_users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $md5Count = 0;
    $bcryptCount = 0;
    $otherCount = 0;
    
    foreach ($users as $user) {
        $pass = $user['password'];
        if (substr($pass, 0, 4) === '$2y$' || substr($pass, 0, 4) === '$2a$') {
            $bcryptCount++;
        } elseif (preg_match('/^[a-f0-9]{32}$/', $pass)) {
            $md5Count++;
        } else {
            $otherCount++;
        }
    }
    
    echo "<table>";
    echo "<tr><th>Formato</th><th>Quantidade</th><th>Status</th></tr>";
    echo "<tr><td>BCrypt (seguro)</td><td>{$bcryptCount}</td><td class='success'>✅ OK</td></tr>";
    echo "<tr><td>MD5 (antigo)</td><td>{$md5Count}</td><td class='warning'>⚠️ Precisa migrar</td></tr>";
    echo "<tr><td>Outro</td><td>{$otherCount}</td><td class='info'>ℹ️ Verificar</td></tr>";
    echo "</table>";
    
    if ($md5Count > 0) {
        echo "<p class='warning'>⚠️ <strong>Atenção:</strong> Existem senhas em MD5. Execute o script de migração:</p>";
        echo "<pre>Acesse: /v2/migration/migrate_passwords.php</pre>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Erro de conexão:</p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    
    echo "<h3>Possíveis soluções:</h3>";
    echo "<ul>";
    echo "<li>Verifique se o nome do banco está correto</li>";
    echo "<li>Verifique se o usuário e senha estão corretos</li>";
    echo "<li>No HostGator, o usuário geralmente é: <code>nomedaconta_usuario</code></li>";
    echo "</ul>";
}

echo "</div>";

echo "<div class='box'>";
echo "<h2 class='warning'>⚠️ IMPORTANTE</h2>";
echo "<p><strong>Apague este arquivo após o teste!</strong></p>";
echo "<p>Ele expõe informações sensíveis do seu sistema.</p>";
echo "</div>";

echo "</body></html>";

