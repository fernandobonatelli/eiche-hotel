<?php
/**
 * LOGIN SIMPLES - Para teste
 * Funciona com banco existente (v1)
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

$error = '';
$success = '';

// Configuração direta do banco (mesmo do v1)
$db_host = 'localhost';
$db_user = 'pous3527_root';
$db_pass = ';Fb6818103200';
$db_name = 'pous3527_eiche';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($login) || empty($senha)) {
        $error = 'Preencha login e senha!';
    } else {
        try {
            // Conectar usando mysqli (como o v1)
            $conexao = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
            
            if (!$conexao) {
                throw new Exception('Erro conexão: ' . mysqli_connect_error());
            }
            
            mysqli_set_charset($conexao, 'utf8');
            
            // Buscar usuário
            $login_escaped = mysqli_real_escape_string($conexao, $login);
            $sql = "SELECT ID, name, login, password FROM eiche_users WHERE login = '$login_escaped' LIMIT 1";
            $result = mysqli_query($conexao, $sql);
            
            if (!$result) {
                throw new Exception('Erro SQL: ' . mysqli_error($conexao));
            }
            
            if (mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);
                $senhaValida = false;
                
                // Verificar se a senha no banco é BCrypt (começa com $2y$)
                if (strpos($user['password'], '$2y$') === 0) {
                    // Senha BCrypt - usar password_verify
                    if (password_verify($senha, $user['password'])) {
                        $senhaValida = true;
                    }
                } else {
                    // Senha MD5 (sistema antigo)
                    $senha_md5 = md5($senha);
                    if ($senha_md5 === $user['password']) {
                        $senhaValida = true;
                        // Atualizar para BCrypt
                        $novaSenha = password_hash($senha, PASSWORD_DEFAULT);
                        mysqli_query($conexao, "UPDATE eiche_users SET password = '$novaSenha' WHERE ID = {$user['ID']}");
                    }
                }
                
                if ($senhaValida) {
                    // Login OK!
                    $_SESSION['user_id'] = $user['ID'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_login'] = $user['login'];
                    $_SESSION['logged_in'] = true;
                    
                    $success = "✅ Login OK! Bem-vindo, {$user['name']}!";
                    
                    // Redirecionar após 2 segundos
                    header("Refresh: 2; url=dashboard.php");
                } else {
                    $error = "❌ Senha incorreta!";
                    $tipoCripto = (strpos($user['password'], '$2y$') === 0) ? 'BCrypt' : 'MD5';
                    $error .= "<br><small>Tipo de criptografia: $tipoCripto</small>";
                }
            } else {
                $error = "❌ Usuário '$login' não encontrado!";
                
                // Listar usuários disponíveis
                $result2 = mysqli_query($conexao, "SELECT login FROM eiche_users LIMIT 5");
                if ($result2 && mysqli_num_rows($result2) > 0) {
                    $error .= "<br><br>Usuários disponíveis:";
                    while ($row = mysqli_fetch_assoc($result2)) {
                        $error .= "<br>• {$row['login']}";
                    }
                }
            }
            
            mysqli_close($conexao);
            
        } catch (Exception $e) {
            $error = "❌ " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Simples - Teste</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        h1 { 
            text-align: center; 
            color: #333;
            margin-bottom: 30px;
        }
        .form-group { margin-bottom: 20px; }
        label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: bold;
            color: #555;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 14px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #0d8fdb;
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0d8fdb, #0a72af);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
        }
        .error {
            background: #ffe6e6;
            color: #c00;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c00;
        }
        .success {
            background: #e6ffe6;
            color: #080;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #080;
        }
        .warning {
            background: #fff3e0;
            color: #e65100;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏨 Login Teste</h1>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="login">Usuário:</label>
                <input type="text" id="login" name="login" 
                       value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" 
                       placeholder="Digite seu login" required>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" 
                       placeholder="Digite sua senha" required>
            </div>
            
            <button type="submit">Entrar</button>
        </form>
        
        <div class="warning">
            ⚠️ <strong>APAGUE este arquivo após testar!</strong>
        </div>
    </div>
</body>
</html>

