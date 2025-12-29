<?php
/**
 * Eiche Hotel - Esqueci minha senha
 * 
 * @version 2.0
 */

declare(strict_types=1);

session_start();

$success = false;
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../config/database.php';
    
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Por favor, informe seu email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, informe um email válido.';
    } else {
        try {
            $db = \Eiche\Config\Database::getInstance();
            
            $user = $db->fetchOne(
                "SELECT ID, name, email FROM eiche_users WHERE email = ? LIMIT 1",
                [$email]
            );
            
            if ($user) {
                // Gerar token de recuperação
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                $db->query(
                    "UPDATE eiche_users SET reset_token = ?, reset_expires = ? WHERE ID = ?",
                    [$token, $expiry, $user['ID']]
                );
                
                // Aqui você enviaria o email com o link de recuperação
                // Por enquanto, apenas simulamos o sucesso
                
                $success = true;
            } else {
                // Por segurança, não revelamos se o email existe ou não
                $success = true;
            }
        } catch (\Exception $e) {
            $error = 'Erro ao processar solicitação. Tente novamente.';
            error_log('Password reset error: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Recuperar Senha - Eiche Hotel';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
    <div class="login-page">
        <div class="login-bg-pattern"></div>
        
        <div class="login-branding">
            <div class="login-branding-content">
                <div class="login-branding-logo">
                    <div class="login-branding-logo-icon">E</div>
                    <span class="login-branding-logo-text">Eiche Hotel</span>
                </div>
                
                <h1>
                    Recupere seu <span>acesso</span>
                </h1>
                
                <p>
                    Esqueceu sua senha? Não se preocupe! Informe seu email cadastrado 
                    e enviaremos um link para você criar uma nova senha.
                </p>
            </div>
        </div>
        
        <div class="login-form-wrapper">
            <div class="login-form-container">
                <div class="login-form-header">
                    <div class="mobile-logo">
                        <div class="mobile-logo-icon">E</div>
                        <span class="mobile-logo-text">Eiche Hotel</span>
                    </div>
                    
                    <?php if ($success): ?>
                    <div style="text-align: center;">
                        <svg width="64" height="64" fill="none" stroke="var(--success-500)" viewBox="0 0 24 24" style="margin: 0 auto var(--space-4);">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <?php endif; ?>
                    
                    <h2><?= $success ? 'Email enviado!' : 'Recuperar senha' ?></h2>
                    <p>
                        <?php if ($success): ?>
                        Se o email estiver cadastrado, você receberá um link para redefinir sua senha.
                        <?php else: ?>
                        Informe seu email para receber as instruções
                        <?php endif; ?>
                    </p>
                </div>
                
                <?php if ($error): ?>
                <div class="login-error">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!$success): ?>
                <form class="login-form" method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <div class="login-input-wrapper">
                            <svg class="login-input-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-control" 
                                placeholder="Digite seu email"
                                value="<?= htmlspecialchars($email) ?>"
                                autocomplete="email"
                                required
                                autofocus
                            >
                        </div>
                    </div>
                    
                    <button type="submit" class="login-btn">
                        <span>Enviar link de recuperação</span>
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </form>
                <?php endif; ?>
                
                <div class="login-footer" style="margin-top: var(--space-6);">
                    <p>
                        <a href="login.php" style="display: inline-flex; align-items: center; gap: var(--space-2);">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Voltar para o login
                        </a>
                    </p>
                </div>
                
                <div class="login-copyright">
                    <p>&copy; <?= date('Y') ?> Eiche Hotel. Todos os direitos reservados.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

