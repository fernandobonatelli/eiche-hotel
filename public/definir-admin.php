<?php
/**
 * Pousada Bona - Definir Administrador
 * Este script define TODOS os usuários existentes como admin
 * Execute uma vez e apague!
 */

$db_host = 'localhost';
$db_user = 'pous3527_root';
$db_pass = ';Fb6818103200';
$db_name = 'pous3527_eiche';

$conexao = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
mysqli_set_charset($conexao, 'utf8');

if (!$conexao) {
    die("Erro de conexão: " . mysqli_connect_error());
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Definir Admin</title></head><body style='font-family: sans-serif; padding: 40px; max-width: 600px; margin: 0 auto;'>";
echo "<h1>🔧 Configurar Administrador</h1>";

// 1. Verificar/criar coluna nivel
$result = mysqli_query($conexao, "SHOW COLUMNS FROM eiche_users LIKE 'nivel'");
if (mysqli_num_rows($result) == 0) {
    echo "<p>⏳ Criando coluna 'nivel'...</p>";
    mysqli_query($conexao, "ALTER TABLE eiche_users ADD COLUMN nivel VARCHAR(20) DEFAULT 'user'");
    echo "<p style='color: green;'>✅ Coluna criada!</p>";
} else {
    echo "<p style='color: green;'>✅ Coluna 'nivel' existe.</p>";
}

// 2. Verificar/criar coluna ver_valores
$result = mysqli_query($conexao, "SHOW COLUMNS FROM eiche_users LIKE 'ver_valores'");
if (mysqli_num_rows($result) == 0) {
    echo "<p>⏳ Criando coluna 'ver_valores'...</p>";
    mysqli_query($conexao, "ALTER TABLE eiche_users ADD COLUMN ver_valores CHAR(1) DEFAULT 'S'");
    echo "<p style='color: green;'>✅ Coluna criada!</p>";
} else {
    echo "<p style='color: green;'>✅ Coluna 'ver_valores' existe.</p>";
}

// 3. Listar usuários atuais
echo "<h2>📋 Usuários no Sistema:</h2>";
$result = mysqli_query($conexao, "SELECT * FROM eiche_users ORDER BY ID");
echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f3f4f6;'><th>ID</th><th>Nome</th><th>Login</th><th>Nível Atual</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    $nivel = $row['nivel'] ?? 'NULL';
    echo "<tr>";
    echo "<td>{$row['ID']}</td>";
    echo "<td>{$row['name']}</td>";
    echo "<td><code>{$row['login']}</code></td>";
    echo "<td>" . ($nivel === 'admin' ? '👑 Admin' : '👤 ' . $nivel) . "</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Botão para tornar todos admin
if (isset($_GET['fazer'])) {
    echo "<h2>⚡ Executando...</h2>";
    
    $sql = "UPDATE eiche_users SET nivel = 'admin', ver_valores = 'S'";
    if (mysqli_query($conexao, $sql)) {
        $affected = mysqli_affected_rows($conexao);
        echo "<p style='background: #dcfce7; padding: 20px; border-radius: 8px; color: #166534; font-size: 18px;'>";
        echo "✅ <strong>$affected usuário(s) definido(s) como ADMINISTRADOR!</strong>";
        echo "</p>";
        echo "<p style='font-size: 16px;'>👉 Agora faça <a href='logout.php' style='color: #2d5a3d; font-weight: bold;'>LOGOUT</a> e login novamente.</p>";
        echo "<p style='font-size: 16px;'>👉 O menu <strong>⚙️ Configurações</strong> aparecerá na sidebar.</p>";
    } else {
        echo "<p style='color: red;'>❌ Erro: " . mysqli_error($conexao) . "</p>";
    }
    
    echo "<hr>";
    echo "<p><a href='definir-admin.php'>🔄 Verificar novamente</a></p>";
} else {
    echo "<br><br>";
    echo "<a href='?fazer=1' style='display: inline-block; background: #2d5a3d; color: white; padding: 15px 30px; border-radius: 8px; text-decoration: none; font-size: 16px; font-weight: bold;'>";
    echo "👑 TORNAR TODOS ADMINISTRADORES";
    echo "</a>";
    echo "<p style='margin-top: 15px; color: #666; font-size: 13px;'>Clique no botão acima para definir todos os usuários como admin.</p>";
}

mysqli_close($conexao);

echo "<hr style='margin-top: 40px;'>";
echo "<p style='color: #dc2626;'>⚠️ <strong>APAGUE ESTE ARQUIVO APÓS USAR!</strong></p>";
echo "<p><a href='login.php' style='color: #2d5a3d;'>← Ir para Login</a></p>";
echo "</body></html>";
?>

