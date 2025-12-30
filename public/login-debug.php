<?php
/**
 * Login com Debug - TEMPORÁRIO
 * APAGUE APÓS RESOLVER O PROBLEMA!
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

echo "<pre style='background:#222;color:#0f0;padding:20px;font-family:monospace;'>";
echo "=== DEBUG LOGIN ===\n\n";

// Teste 1: Verificar requisição
echo "1. MÉTODO: " . $_SERVER['REQUEST_METHOD'] . "\n";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    echo "2. LOGIN RECEBIDO: '{$login}'\n";
    echo "3. SENHA RECEBIDA: " . (empty($password) ? '(vazia)' : '******') . "\n\n";
    
    // Teste 2: Carregar configuração
    echo "4. CARREGANDO DATABASE...\n";
    
    try {
        require_once __DIR__ . '/../config/database.php';
        echo "   ✓ Arquivo carregado\n";
    } catch (Throwable $e) {
        echo "   ✗ ERRO: " . $e->getMessage() . "\n";
        echo "   Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
        exit;
    }
    
    // Teste 3: Conectar ao banco
    echo "\n5. CONECTANDO AO BANCO...\n";
    
    try {
        $db = \Eiche\Config\Database::getInstance();
        echo "   ✓ Instância criada\n";
        
        $conn = $db->getConnection();
        echo "   ✓ Conexão estabelecida\n";
    } catch (Throwable $e) {
        echo "   ✗ ERRO CONEXÃO: " . $e->getMessage() . "\n";
        exit;
    }
    
    // Teste 4: Buscar usuário
    echo "\n6. BUSCANDO USUÁRIO '{$login}'...\n";
    
    try {
        $user = $db->fetchOne(
            "SELECT ID, name, login, password, caste, status FROM eiche_users WHERE login = ? LIMIT 1",
            [$login]
        );
        
        if ($user) {
            echo "   ✓ Usuário encontrado!\n";
            echo "   - ID: {$user['ID']}\n";
            echo "   - Nome: {$user['name']}\n";
            echo "   - Login: {$user['login']}\n";
            echo "   - Status: {$user['status']}\n";
            echo "   - Senha (hash): " . substr($user['password'], 0, 20) . "...\n";
            
            // Verificar tipo de senha
            $passHash = $user['password'];
            if (substr($passHash, 0, 4) === '$2y$' || substr($passHash, 0, 4) === '$2a$') {
                echo "   - Tipo: BCrypt ✓\n";
                $isBcrypt = true;
            } elseif (preg_match('/^[a-f0-9]{32}$/', $passHash)) {
                echo "   - Tipo: MD5 (antigo)\n";
                $isBcrypt = false;
            } else {
                echo "   - Tipo: Outro/Texto plano\n";
                $isBcrypt = false;
            }
            
            // Teste 5: Verificar senha
            echo "\n7. VERIFICANDO SENHA...\n";
            
            if ($isBcrypt) {
                $valid = password_verify($password, $passHash);
                echo "   password_verify(): " . ($valid ? 'TRUE ✓' : 'FALSE ✗') . "\n";
            } else {
                // Tentar MD5
                $md5Pass = md5($password);
                $valid = ($md5Pass === $passHash);
                echo "   MD5 comparação: " . ($valid ? 'TRUE ✓' : 'FALSE ✗') . "\n";
                echo "   MD5 digitada: {$md5Pass}\n";
                echo "   MD5 no banco: {$passHash}\n";
            }
            
            if ($valid) {
                echo "\n✅ SENHA CORRETA! Login deveria funcionar.\n";
            } else {
                echo "\n❌ SENHA INCORRETA!\n";
            }
            
        } else {
            echo "   ✗ Usuário NÃO encontrado no banco!\n";
            
            // Listar usuários disponíveis
            echo "\n   Usuários disponíveis:\n";
            $users = $db->fetchAll("SELECT login FROM eiche_users LIMIT 10");
            foreach ($users as $u) {
                echo "   - {$u['login']}\n";
            }
        }
        
    } catch (Throwable $e) {
        echo "   ✗ ERRO SQL: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "Faça POST com login e senha para testar.\n";
}

echo "\n=== FIM DEBUG ===\n";
echo "</pre>";

// Formulário simples
?>
<div style="max-width:400px;margin:20px auto;font-family:Arial;">
    <h3>Teste de Login</h3>
    <form method="POST">
        <p>
            <label>Login:</label><br>
            <input type="text" name="login" style="width:100%;padding:8px;" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">
        </p>
        <p>
            <label>Senha:</label><br>
            <input type="password" name="password" style="width:100%;padding:8px;">
        </p>
        <button type="submit" style="padding:10px 20px;background:#0d8fdb;color:white;border:none;cursor:pointer;">
            Testar Login
        </button>
    </form>
    <p style="color:red;margin-top:20px;"><strong>⚠️ APAGUE ESTE ARQUIVO APÓS O TESTE!</strong></p>
</div>

