<?php
/**
 * Pousada Bona - Página de Login
 * Layout horizontal com imagem à esquerda
 * 
 * @version 2.0
 */

session_start();

// Se já estiver logado, redireciona
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$login = '';

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../config/database.php';
    
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($login) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $db = \Eiche\Config\Database::getInstance();
            
            $user = $db->fetchOne(
                "SELECT ID, name, login, password FROM eiche_users WHERE login = ? LIMIT 1",
                [$login]
            );
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['ID'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_login'] = $user['login'];
                $_SESSION['logged_in'] = true;
                
                if ($remember) {
                    try {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + (86400 * 30), '/', '', true, true);
                        $db->query("UPDATE eiche_users SET remember_token = ? WHERE ID = ?", 
                            [password_hash($token, PASSWORD_DEFAULT), $user['ID']]);
                    } catch (\Exception $rememberError) {}
                }
                
                try {
                    $db->query(
                        "INSERT INTO eiche_log (ID_user, action, ip, created_at) VALUES (?, ?, ?, NOW())",
                        [$user['ID'], 'login', $_SERVER['REMOTE_ADDR'] ?? 'unknown']
                    );
                } catch (\Exception $logError) {}
                
                header('Location: dashboard.php');
                exit;
            } else {
                if ($user && md5($password) === $user['password']) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $db->query("UPDATE eiche_users SET password = ? WHERE ID = ?", [$newHash, $user['ID']]);
                    
                    $_SESSION['user_id'] = $user['ID'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_login'] = $user['login'];
                    $_SESSION['logged_in'] = true;
                    
                    header('Location: dashboard.php');
                    exit;
                }
                
                $error = 'Usuário ou senha incorretos.';
            }
        } catch (\Exception $e) {
            $error = 'Erro: ' . $e->getMessage();
            error_log('Login error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Gestão - Pousada Bona">
    <title>Login - Pousada Bona</title>
    <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        html, body {
            height: 100%;
            font-family: 'Lato', sans-serif;
        }
        
        .login-page {
            display: flex;
            min-height: 100vh;
        }
        
        /* Lado esquerdo - Imagem */
        .login-image {
            flex: 1;
            background: url('assets/images/bg-pousada.jpg') center center;
            background-size: cover;
            position: relative;
            display: flex;
            align-items: flex-end;
            padding: 40px;
        }
        
        .login-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                to bottom,
                rgba(45, 90, 61, 0.3) 0%,
                rgba(45, 90, 61, 0.6) 100%
            );
        }
        
        .image-content {
            position: relative;
            z-index: 1;
            color: white;
            max-width: 400px;
        }
        
        .image-content h1 {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            margin-bottom: 15px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .image-content p {
            font-size: 16px;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .image-features {
            display: flex;
            gap: 20px;
            margin-top: 25px;
        }
        
        .image-feature {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* Lado direito - Formulário */
        .login-form-side {
            width: 480px;
            min-width: 400px;
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 50px;
            overflow-y: auto;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .logo-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #2d5a3d;
            margin-bottom: 20px;
        }
        
        .login-title {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            color: #2d5a3d;
            margin-bottom: 8px;
        }
        
        .login-subtitle {
            color: #6b7280;
            font-size: 14px;
        }
        
        /* Erro */
        .login-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #dc2626;
            font-size: 13px;
        }
        
        /* Formulário */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .form-input {
            width: 100%;
            padding: 14px 16px 14px 46px;
            font-size: 15px;
            font-family: inherit;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            background: #f9fafb;
            transition: all 0.2s;
            outline: none;
        }
        
        .form-input:focus {
            border-color: #2d5a3d;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(45, 90, 61, 0.1);
        }
        
        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
        }
        
        .form-input:focus ~ .input-icon {
            color: #2d5a3d;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 5px;
        }
        
        .password-toggle:hover {
            color: #2d5a3d;
        }
        
        /* Opções */
        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 13px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6b7280;
            cursor: pointer;
        }
        
        .remember-me input {
            width: 16px;
            height: 16px;
            accent-color: #2d5a3d;
        }
        
        .forgot-password {
            color: #2d5a3d;
            text-decoration: none;
            font-weight: 600;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        /* Botão */
        .login-btn {
            width: 100%;
            padding: 15px;
            font-size: 16px;
            font-weight: 700;
            font-family: inherit;
            color: white;
            background: linear-gradient(135deg, #2d5a3d, #1a5f2a);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.2s;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(45, 90, 61, 0.3);
        }
        
        .login-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Rodapé */
        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        
        .login-footer p {
            font-size: 12px;
            color: #9ca3af;
        }
        
        .nature-icons {
            margin-top: 10px;
            font-size: 16px;
            opacity: 0.5;
        }
        
        /* Responsivo */
        @media (max-width: 900px) {
            .login-image {
                display: none;
            }
            
            .login-form-side {
                width: 100%;
                min-width: auto;
                padding: 30px 25px;
            }
        }
        
        @media (max-height: 700px) {
            .login-form-side {
                padding: 25px;
                justify-content: flex-start;
            }
            
            .login-header {
                margin-bottom: 25px;
            }
            
            .logo-img {
                width: 60px;
                height: 60px;
                margin-bottom: 15px;
            }
            
            .login-title {
                font-size: 22px;
            }
            
            .form-group {
                margin-bottom: 15px;
            }
            
            .form-input {
                padding: 12px 14px 12px 42px;
                font-size: 14px;
            }
            
            .login-options {
                margin-bottom: 20px;
            }
            
            .login-btn {
                padding: 12px;
            }
            
            .login-footer {
                margin-top: 20px;
                padding-top: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="login-page">
        <!-- Lado esquerdo - Imagem -->
        <div class="login-image">
            <div class="image-content">
                <h1>🌿 Pousada Bona</h1>
                <p>Seu refúgio de tranquilidade. Sistema completo de gestão para uma experiência única em hospedagem.</p>
                <div class="image-features">
                    <div class="image-feature">🛏️ Hospedagens</div>
                    <div class="image-feature">💰 Financeiro</div>
                    <div class="image-feature">📊 Relatórios</div>
                </div>
            </div>
        </div>
        
        <!-- Lado direito - Formulário -->
        <div class="login-form-side">
            <div class="login-header">
                <img src="assets/images/logo.jpg" alt="Pousada Bona" class="logo-img" 
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2780%27 height=%2780%27%3E%3Crect fill=%27%232d5a3d%27 width=%2780%27 height=%2780%27 rx=%2740%27/%3E%3Ctext x=%2740%27 y=%2750%27 text-anchor=%27middle%27 fill=%27white%27 font-size=%2726%27 font-family=%27serif%27%3EPB%3C/text%3E%3C/svg%3E'">
                <h1 class="login-title">Bem-vindo de volta</h1>
                <p class="login-subtitle">Entre com suas credenciais</p>
            </div>
            
            <?php if ($error): ?>
            <div class="login-error">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>
            
            <form method="POST" id="login-form">
                <div class="form-group">
                    <label class="form-label" for="login">Usuário</label>
                    <div class="input-wrapper">
                        <input 
                            type="text" 
                            id="login" 
                            name="login" 
                            class="form-input" 
                            placeholder="Digite seu usuário"
                            value="<?= htmlspecialchars($login) ?>"
                            autocomplete="username"
                            required
                            autofocus
                        >
                        <svg class="input-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Senha</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Digite sua senha"
                            autocomplete="current-password"
                            required
                        >
                        <svg class="input-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <svg id="eye-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="login-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember">
                        <span>Lembrar de mim</span>
                    </label>
                    <a href="forgot-password.php" class="forgot-password">Esqueceu a senha?</a>
                </div>
                
                <button type="submit" class="login-btn" id="login-btn">
                    <span>Entrar</span>
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </form>
            
            <div class="login-footer">
                <p>&copy; <?= date('Y') ?> Pousada Bona. Todos os direitos reservados.</p>
                <div class="nature-icons">🌴 🌊 ☀️</div>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
            }
        }
        
        document.getElementById('login-form').addEventListener('submit', function() {
            const btn = document.getElementById('login-btn');
            btn.disabled = true;
            btn.innerHTML = '<div class="spinner"></div><span>Entrando...</span>';
        });
    </script>
</body>
</html>
