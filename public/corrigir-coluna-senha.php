<?php
/**
 * CORRIGIR COLUNA PASSWORD
 * A coluna está pequena demais para BCrypt (precisa de 60 chars)
 * APAGUE APÓS USAR!
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$db_host = 'localhost';
$db_user = 'pous3527_root';
$db_pass = ';Fb6818103200';
$db_name = 'pous3527_eiche';

echo "<h1>🔧 Corrigir Coluna Password</h1>";
echo "<style>body{font-family:Arial;max-width:800px;margin:40px auto;padding:20px;} .ok{color:green;} .err{color:red;} pre{background:#f5f5f5;padding:15px;border-radius:8px;}</style>";

$conexao = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conexao) {
    die("Erro: " . mysqli_connect_error());
}
mysqli_set_charset($conexao, 'utf8');

// Verificar estrutura atual
echo "<h2>1️⃣ Estrutura Atual da Coluna Password</h2>";
$result = mysqli_query($conexao, "SHOW COLUMNS FROM eiche_users LIKE 'password'");
if ($result && $row = mysqli_fetch_assoc($result)) {
    echo "<pre>";
    print_r($row);
    echo "</pre>";
    echo "<p>Tipo atual: <strong>{$row['Type']}</strong></p>";
} else {
    echo "<p class='err'>❌ Coluna password não encontrada!</p>";
}

// Alterar coluna para VARCHAR(255)
echo "<h2>2️⃣ Alterando para VARCHAR(255)</h2>";
$sql = "ALTER TABLE eiche_users MODIFY COLUMN password VARCHAR(255)";
if (mysqli_query($conexao, $sql)) {
    echo "<p class='ok'>✅ Coluna alterada com sucesso!</p>";
} else {
    echo "<p class='err'>❌ Erro: " . mysqli_error($conexao) . "</p>";
}

// Verificar nova estrutura
echo "<h2>3️⃣ Nova Estrutura</h2>";
$result = mysqli_query($conexao, "SHOW COLUMNS FROM eiche_users LIKE 'password'");
if ($result && $row = mysqli_fetch_assoc($result)) {
    echo "<pre>";
    print_r($row);
    echo "</pre>";
}

// Resetar senha do usuário fernando
echo "<h2>4️⃣ Resetando Senha do Usuário 'fernando'</h2>";
$novaSenha = ';Fb122000';
$novoHash = password_hash($novaSenha, PASSWORD_DEFAULT);
echo "<p>Nova senha: <code>$novaSenha</code></p>";
echo "<p>Novo hash: <code>$novoHash</code></p>";
echo "<p>Tamanho do hash: " . strlen($novoHash) . " caracteres</p>";

$sql = "UPDATE eiche_users SET password = '$novoHash' WHERE login = 'fernando'";
if (mysqli_query($conexao, $sql)) {
    $affected = mysqli_affected_rows($conexao);
    echo "<p class='ok'>✅ Senha atualizada! ($affected registro(s) afetado(s))</p>";
} else {
    echo "<p class='err'>❌ Erro: " . mysqli_error($conexao) . "</p>";
}

// Verificar se salvou corretamente
echo "<h2>5️⃣ Verificando</h2>";
$result = mysqli_query($conexao, "SELECT password FROM eiche_users WHERE login = 'fernando'");
if ($result && $row = mysqli_fetch_assoc($result)) {
    $hashSalvo = $row['password'];
    echo "<p>Hash salvo: <code>$hashSalvo</code></p>";
    echo "<p>Tamanho: " . strlen($hashSalvo) . " caracteres</p>";
    
    // Testar
    $teste = password_verify($novaSenha, $hashSalvo);
    echo "<p>Teste password_verify: " . ($teste ? "<span class='ok'>✅ VÁLIDA</span>" : "<span class='err'>❌ INVÁLIDA</span>") . "</p>";
}

mysqli_close($conexao);

echo "<hr>";
echo "<h2>✅ Pronto!</h2>";
echo "<p>Agora tente fazer login com:</p>";
echo "<ul>";
echo "<li><strong>Login:</strong> fernando</li>";
echo "<li><strong>Senha:</strong> ;Fb122000</li>";
echo "</ul>";
echo "<p><a href='login.php' style='background:#3b82f6;color:white;padding:10px 20px;border-radius:6px;text-decoration:none;'>→ Ir para Login</a></p>";
echo "<hr>";
echo "<p style='color:red;'>⚠️ <strong>APAGUE ESTE ARQUIVO!</strong></p>";
?>

