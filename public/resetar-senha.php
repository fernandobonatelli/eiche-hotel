<?php
/**
 * RESETAR SENHA - Ferramenta de emergência
 * APAGUE ESTE ARQUIVO APÓS USAR!
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$db_host = 'localhost';
$db_user = 'pous3527_root';
$db_pass = ';Fb6818103200';
$db_name = 'pous3527_eiche';

$mensagem = '';
$erro = '';

$conexao = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
mysqli_set_charset($conexao, 'utf8');

// Buscar usuários
$usuarios = [];
$result = mysqli_query($conexao, "SELECT ID, name, login, password FROM eiche_users ORDER BY login");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $row['tipo_senha'] = (strpos($row['password'], '$2y$') === 0) ? 'BCrypt' : 'MD5';
        $usuarios[] = $row;
    }
}

// Processar reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)$_POST['user_id'];
    $novaSenha = $_POST['nova_senha'] ?? '';
    
    if ($userId && !empty($novaSenha)) {
        $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $sql = "UPDATE eiche_users SET password = '$senhaHash' WHERE ID = $userId";
        
        if (mysqli_query($conexao, $sql)) {
            $mensagem = "✅ Senha do usuário ID $userId atualizada com sucesso!";
            $mensagem .= "<br>Nova senha: <strong>$novaSenha</strong>";
            $mensagem .= "<br>Hash: <code>" . substr($senhaHash, 0, 30) . "...</code>";
            
            // Atualizar lista
            $result = mysqli_query($conexao, "SELECT ID, name, login, password FROM eiche_users ORDER BY login");
            $usuarios = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $row['tipo_senha'] = (strpos($row['password'], '$2y$') === 0) ? 'BCrypt' : 'MD5';
                $usuarios[] = $row;
            }
        } else {
            $erro = "❌ Erro ao atualizar: " . mysqli_error($conexao);
        }
    } else {
        $erro = "❌ Preencha todos os campos";
    }
}

mysqli_close($conexao);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resetar Senha</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; }
        .card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f9f9f9; }
        .badge { padding: 3px 8px; border-radius: 4px; font-size: 11px; }
        .badge-bcrypt { background: #dcfce7; color: #166534; }
        .badge-md5 { background: #fee2e2; color: #991b1b; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { padding: 10px; border: 1px solid #ddd; border-radius: 4px; width: 100%; }
        button { background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #2563eb; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .warning { background: #fef3c7; color: #92400e; padding: 15px; border-radius: 8px; border: 1px solid #fcd34d; }
    </style>
</head>
<body>
    <h1>🔐 Resetar Senha de Usuário</h1>
    
    <div class="warning">
        ⚠️ <strong>ATENÇÃO:</strong> Este arquivo permite alterar senhas de usuários. 
        <strong>APAGUE-O IMEDIATAMENTE após usar!</strong>
    </div>
    
    <?php if ($mensagem): ?>
    <div class="alert alert-success"><?= $mensagem ?></div>
    <?php endif; ?>
    
    <?php if ($erro): ?>
    <div class="alert alert-error"><?= $erro ?></div>
    <?php endif; ?>
    
    <div class="card">
        <h3>👥 Usuários Cadastrados</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Login</th>
                    <th>Tipo Senha</th>
                    <th>Hash (início)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= $u['ID'] ?></td>
                    <td><?= htmlspecialchars($u['name']) ?></td>
                    <td><strong><?= htmlspecialchars($u['login']) ?></strong></td>
                    <td>
                        <span class="badge <?= $u['tipo_senha'] === 'BCrypt' ? 'badge-bcrypt' : 'badge-md5' ?>">
                            <?= $u['tipo_senha'] ?>
                        </span>
                    </td>
                    <td><code><?= substr($u['password'], 0, 20) ?>...</code></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="card">
        <h3>🔄 Alterar Senha</h3>
        <form method="POST">
            <div class="form-group">
                <label>Selecione o Usuário:</label>
                <select name="user_id" required>
                    <option value="">-- Selecione --</option>
                    <?php foreach ($usuarios as $u): ?>
                    <option value="<?= $u['ID'] ?>"><?= htmlspecialchars($u['login']) ?> - <?= htmlspecialchars($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Nova Senha:</label>
                <input type="text" name="nova_senha" placeholder="Digite a nova senha" required>
            </div>
            
            <button type="submit">🔐 Alterar Senha</button>
        </form>
    </div>
    
    <p style="text-align: center; margin-top: 30px;">
        <a href="login.php">← Voltar para Login</a>
    </p>
</body>
</html>

