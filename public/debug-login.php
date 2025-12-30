<?php
/**
 * DEBUG LOGIN - Diagnóstico completo
 * APAGUE APÓS USAR!
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

$db_host = 'localhost';
$db_user = 'pous3527_root';
$db_pass = ';Fb6818103200';
$db_name = 'pous3527_eiche';

echo "<h1>🔍 Debug de Login</h1>";
echo "<style>body{font-family:Arial;max-width:800px;margin:40px auto;padding:20px;} .ok{color:green;} .err{color:red;} pre{background:#f5f5f5;padding:15px;border-radius:8px;overflow-x:auto;} code{background:#eee;padding:2px 6px;border-radius:4px;}</style>";

// Teste conexão
echo "<h2>1️⃣ Conexão com Banco</h2>";
$conexao = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if ($conexao) {
    echo "<p class='ok'>✅ Conexão OK</p>";
    mysqli_set_charset($conexao, 'utf8');
} else {
    echo "<p class='err'>❌ Erro: " . mysqli_connect_error() . "</p>";
    exit;
}

// Listar usuários
echo "<h2>2️⃣ Usuários no Banco</h2>";
$result = mysqli_query($conexao, "SELECT ID, name, login, password FROM eiche_users");
if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse;width:100%;'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Login</th><th>Tipo Senha</th><th>Hash (primeiros 40 chars)</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        $tipo = (strpos($row['password'], '$2y$') === 0) ? '✅ BCrypt' : '⚠️ MD5';
        echo "<tr>";
        echo "<td>{$row['ID']}</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['login']) . "</strong></td>";
        echo "<td>$tipo</td>";
        echo "<td><code>" . htmlspecialchars(substr($row['password'], 0, 40)) . "...</code></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='err'>❌ Nenhum usuário encontrado!</p>";
}

// Teste de senha
echo "<h2>3️⃣ Testar Login</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['testar'])) {
    $login = mysqli_real_escape_string($conexao, $_POST['login']);
    $senha = $_POST['senha'];
    
    echo "<h3>Testando: login='$login'</h3>";
    
    $sql = "SELECT ID, name, login, password FROM eiche_users WHERE login = '$login' LIMIT 1";
    echo "<p>Query: <code>$sql</code></p>";
    
    $result = mysqli_query($conexao, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        echo "<p class='ok'>✅ Usuário encontrado: {$user['name']}</p>";
        
        $senhaHash = $user['password'];
        echo "<p>Senha no banco (hash): <code>" . htmlspecialchars($senhaHash) . "</code></p>";
        echo "<p>Tamanho do hash: " . strlen($senhaHash) . " caracteres</p>";
        
        // Verificar tipo
        if (strpos($senhaHash, '$2y$') === 0) {
            echo "<p>Tipo: <strong>BCrypt</strong></p>";
            
            // Testar com password_verify
            $resultado = password_verify($senha, $senhaHash);
            echo "<p>password_verify('$senha', hash): " . ($resultado ? "<span class='ok'>✅ VÁLIDA</span>" : "<span class='err'>❌ INVÁLIDA</span>") . "</p>";
            
            if (!$resultado) {
                // Criar novo hash para comparar
                echo "<h4>Debug adicional:</h4>";
                $novoHash = password_hash($senha, PASSWORD_DEFAULT);
                echo "<p>Se a senha fosse '$senha', o hash seria:</p>";
                echo "<pre>$novoHash</pre>";
                echo "<p>Verificando novo hash: " . (password_verify($senha, $novoHash) ? "✅ OK" : "❌ Falhou") . "</p>";
            }
        } else {
            echo "<p>Tipo: <strong>MD5</strong></p>";
            $md5 = md5($senha);
            echo "<p>MD5 da senha digitada: <code>$md5</code></p>";
            echo "<p>MD5 no banco: <code>$senhaHash</code></p>";
            echo "<p>Comparação: " . ($md5 === $senhaHash ? "<span class='ok'>✅ IGUAIS</span>" : "<span class='err'>❌ DIFERENTES</span>") . "</p>";
        }
        
    } else {
        echo "<p class='err'>❌ Usuário '$login' não encontrado!</p>";
    }
}
?>

<form method="POST" style="background:#f9f9f9;padding:20px;border-radius:8px;margin-top:20px;">
    <h4>Testar credenciais:</h4>
    <p>
        <label>Login: </label>
        <input type="text" name="login" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" style="padding:8px;width:200px;">
    </p>
    <p>
        <label>Senha: </label>
        <input type="text" name="senha" value="<?= htmlspecialchars($_POST['senha'] ?? '') ?>" style="padding:8px;width:200px;">
        <small>(texto visível para debug)</small>
    </p>
    <button type="submit" name="testar" value="1" style="padding:10px 20px;background:#3b82f6;color:white;border:none;border-radius:4px;cursor:pointer;">
        🔍 Testar Login
    </button>
</form>

<h2>4️⃣ Sessão Atual</h2>
<pre><?php print_r($_SESSION); ?></pre>

<h2>5️⃣ Arquivos de Login</h2>
<?php
$arquivos = ['login.php', 'login-simples.php'];
foreach ($arquivos as $arq) {
    $existe = file_exists(__DIR__ . '/' . $arq);
    echo "<p>$arq: " . ($existe ? "✅ Existe" : "❌ Não existe") . "</p>";
}
?>

<hr>
<p style="color:red;font-weight:bold;">⚠️ APAGUE ESTE ARQUIVO APÓS USAR!</p>
<p><a href="login.php">← Ir para Login</a> | <a href="resetar-senha.php">🔐 Resetar Senha</a></p>

<?php mysqli_close($conexao); ?>

