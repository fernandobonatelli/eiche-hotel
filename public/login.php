<?php
/**
 * Eiche Hotel - Página de Login
 * Design moderno e responsivo
 * 
 * @version 2.0
 */

declare(strict_types=1);

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
                "SELECT ID, name, login, password, caste, status FROM eiche_users WHERE login = ? LIMIT 1",
                [$login]
            );
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'A') {
                    $error = 'Sua conta está inativa. Entre em contato com o administrador.';
                } else {
                    // Login bem-sucedido
                    $_SESSION['user_id'] = $user['ID'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_login'] = $user['login'];
                    $_SESSION['user_caste'] = $user['caste'];
                    $_SESSION['logged_in'] = true;
                    
                    // Remember me
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + (86400 * 30), '/', '', true, true);
                        $db->query("UPDATE eiche_users SET remember_token = ? WHERE ID = ?", 
                            [password_hash($token, PASSWORD_DEFAULT), $user['ID']]);
                    }
                    
                    // Registrar log de acesso
                    $db->query(
                        "INSERT INTO eiche_log (ID_user, action, ip, created_at) VALUES (?, ?, ?, NOW())",
                        [$user['ID'], 'login', $_SERVER['REMOTE_ADDR'] ?? 'unknown']
                    );
                    
                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                // Compatibilidade com senhas antigas (md5)
                if ($user && md5($password) === $user['password']) {
                    // Atualiza para bcrypt
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $db->query("UPDATE eiche_users SET password = ? WHERE ID = ?", [$newHash, $user['ID']]);
                    
                    $_SESSION['user_id'] = $user['ID'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_login'] = $user['login'];
                    $_SESSION['user_caste'] = $user['caste'];
                    $_SESSION['logged_in'] = true;
                    
                    header('Location: dashboard.php');
                    exit;
                }
                
                $error = 'Usuário ou senha incorretos.';
            }
        } catch (\Exception $e) {
            $error = 'Erro ao processar login. Tente novamente.';
            error_log('Login error: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Login - Eiche Hotel';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Hotelaria e Cobrança Eiche Hotel">
    <meta name="theme-color" content="#0d8fdb">
    
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="assets/css/app.css">
    
    <!-- Preconnect para fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body>
    <div class="login-page">
        <!-- Background Pattern -->
        <div class="login-bg-pattern"></div>
        
        <!-- Left Side - Branding -->
        <div class="login-branding">
            <div class="login-branding-content">
                <div class="login-branding-logo">
                    <div class="login-branding-logo-icon">E</div>
                    <span class="login-branding-logo-text">Eiche Hotel</span>
                </div>
                
                <h1>
                    Gestão <span>inteligente</span> para sua hospedagem
                </h1>
                
                <p>
                    Sistema completo para gerenciamento de hotéis, pousadas e hospedarias. 
                    Controle reservas, hóspedes, financeiro e muito mais em uma única plataforma.
                </p>
                
                <div class="login-features">
                    <div class="login-feature">
                        <div class="login-feature-icon">
                            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="login-feature-text">
                            <div class="login-feature-title">Gestão de Reservas</div>
                            <p class="login-feature-desc">Controle completo de check-in, check-out e ocupação</p>
                        </div>
                    </div>
                    
                    <div class="login-feature">
                        <div class="login-feature-icon">
                            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="login-feature-text">
                            <div class="login-feature-title">Controle Financeiro</div>
                            <p class="login-feature-desc">Boletos, cobranças e relatórios detalhados</p>
                        </div>
                    </div>
                    
                    <div class="login-feature">
                        <div class="login-feature-icon">
                            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <div class="login-feature-text">
                            <div class="login-feature-title">Relatórios Avançados</div>
                            <p class="login-feature-desc">Dashboards e análises em tempo real</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="login-form-wrapper">
            <div class="login-form-container">
                <div class="login-form-header">
                    <!-- Logo Mobile -->
                    <div class="mobile-logo">
                        <div class="mobile-logo-icon">E</div>
                        <span class="mobile-logo-text">Eiche Hotel</span>
                    </div>
                    
                    <h2>Bem-vindo de volta</h2>
                    <p>Entre com suas credenciais para acessar o sistema</p>
                </div>
                
                <?php if ($error): ?>
                <div class="login-error">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
                <?php endif; ?>
                
                <form class="login-form" method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="login">Usuário</label>
                        <div class="login-input-wrapper">
                            <svg class="login-input-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <input 
                                type="text" 
                                id="login" 
                                name="login" 
                                class="form-control" 
                                placeholder="Digite seu usuário"
                                value="<?= htmlspecialchars($login) ?>"
                                autocomplete="username"
                                required
                                autofocus
                            >
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">Senha</label>
                        <div class="login-input-wrapper">
                            <svg class="login-input-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-control" 
                                placeholder="Digite sua senha"
                                autocomplete="current-password"
                                required
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <svg id="eye-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </form>
                
                <div class="login-copyright">
                    <p>&copy; <?= date('Y') ?> Eiche Hotel. Todos os direitos reservados.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                `;
            } else {
                input.type = 'password';
                icon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                `;
            }
        }
        
        // Loading state no botão
        document.querySelector('.login-form').addEventListener('submit', function() {
            const btn = document.getElementById('login-btn');
            btn.disabled = true;
            btn.innerHTML = '<div class="spinner"></div><span>Entrando...</span>';
        });
    </script>
</body>
</html>

