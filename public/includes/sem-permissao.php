<?php
/**
 * Pousada Bona - Página de Acesso Negado
 */

// Receber mensagem personalizada ou usar padrão
$mensagemPermissao = $mensagemPermissao ?? 'Você não tem permissão para acessar esta página.';
$tituloPermissao = $tituloPermissao ?? 'Acesso Restrito';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tituloPermissao ?> - Pousada Bona</title>
    <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            padding: 20px;
        }
        
        .error-container {
            background: white;
            border-radius: 16px;
            padding: 50px 40px;
            max-width: 450px;
            width: 100%;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .error-icon {
            width: 80px;
            height: 80px;
            background: #fef2f2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 40px;
        }
        
        .error-title {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 12px;
        }
        
        .error-message {
            font-size: 15px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .error-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 24px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2d5a3d, #1a5f2a);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(45, 90, 61, 0.3);
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .help-text {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 13px;
            color: #9ca3af;
        }
        
        .help-text a {
            color: #2d5a3d;
            text-decoration: none;
        }
        
        .help-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🔒</div>
        <h1 class="error-title"><?= htmlspecialchars($tituloPermissao) ?></h1>
        <p class="error-message"><?= htmlspecialchars($mensagemPermissao) ?></p>
        
        <div class="error-actions">
            <a href="dashboard.php" class="btn btn-primary">
                🏠 Ir para o Dashboard
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                ← Voltar
            </a>
        </div>
        
        <div class="help-text">
            Se você acredita que deveria ter acesso, entre em contato com o <a href="mailto:admin@pousadabona.com.br">administrador</a>.
        </div>
    </div>
</body>
</html>
<?php exit; ?>

