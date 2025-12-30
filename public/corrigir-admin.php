<?php
/**
 * Pousada Bona - Corrigir Nível de Administrador
 * Execute uma vez e apague o arquivo!
 */

session_start();

$db_host = 'localhost';
$db_user = 'pous3527_root';
$db_pass = ';Fb6818103200';
$db_name = 'pous3527_eiche';

$conexao = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
mysqli_set_charset($conexao, 'utf8');

if (!$conexao) {
    die("Erro de conexão: " . mysqli_connect_error());
}

echo "<h2>🔧 Correção de Nível de Administrador</h2>";

// 1. Verificar se coluna 'nivel' existe
echo "<h3>1️⃣ Verificando coluna 'nivel'...</h3>";
$result = mysqli_query($conexao, "SHOW COLUMNS FROM eiche_users LIKE 'nivel'");
if (mysqli_num_rows($result) == 0) {
    echo "<p>⚠️ Coluna 'nivel' não existe. Criando...</p>";
    $sql = "ALTER TABLE eiche_users ADD COLUMN nivel VARCHAR(20) DEFAULT 'user'";
    if (mysqli_query($conexao, $sql)) {
        echo "<p style='color: green;'>✅ Coluna 'nivel' criada com sucesso!</p>";
    } else {
        echo "<p style='color: red;'>❌ Erro ao criar coluna: " . mysqli_error($conexao) . "</p>";
    }
} else {
    echo "<p style='color: green;'>✅ Coluna 'nivel' já existe.</p>";
}

// 2. Verificar se coluna 'ver_valores' existe
echo "<h3>2️⃣ Verificando coluna 'ver_valores'...</h3>";
$result = mysqli_query($conexao, "SHOW COLUMNS FROM eiche_users LIKE 'ver_valores'");
if (mysqli_num_rows($result) == 0) {
    echo "<p>⚠️ Coluna 'ver_valores' não existe. Criando...</p>";
    $sql = "ALTER TABLE eiche_users ADD COLUMN ver_valores CHAR(1) DEFAULT 'S'";
    if (mysqli_query($conexao, $sql)) {
        echo "<p style='color: green;'>✅ Coluna 'ver_valores' criada com sucesso!</p>";
    } else {
        echo "<p style='color: red;'>❌ Erro ao criar coluna: " . mysqli_error($conexao) . "</p>";
    }
} else {
    echo "<p style='color: green;'>✅ Coluna 'ver_valores' já existe.</p>";
}

// 3. Listar todos os usuários
echo "<h3>3️⃣ Usuários no sistema:</h3>";
$result = mysqli_query($conexao, "SELECT ID, name, login, COALESCE(nivel, 'user') as nivel, COALESCE(ver_valores, 'S') as ver_valores FROM eiche_users ORDER BY ID");
echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse;'>";
echo "<thead style='background: #f3f4f6;'><tr><th>ID</th><th>Nome</th><th>Login</th><th>Nível</th><th>Ver Valores</th><th>Ação</th></tr></thead>";
echo "<tbody>";
while ($row = mysqli_fetch_assoc($result)) {
    $nivelIcon = $row['nivel'] === 'admin' ? '👑' : '👤';
    $valorIcon = $row['ver_valores'] === 'S' ? '✅' : '🚫';
    echo "<tr>";
    echo "<td>{$row['ID']}</td>";
    echo "<td>{$row['name']}</td>";
    echo "<td><code>{$row['login']}</code></td>";
    echo "<td>{$nivelIcon} {$row['nivel']}</td>";
    echo "<td style='text-align:center;'>{$valorIcon}</td>";
    echo "<td><a href='?tornar_admin={$row['ID']}' style='background: #fef3c7; padding: 5px 10px; border-radius: 4px; text-decoration: none;'>👑 Tornar Admin</a></td>";
    echo "</tr>";
}
echo "</tbody></table>";

// 4. Processar ação de tornar admin
if (isset($_GET['tornar_admin'])) {
    $userId = (int)$_GET['tornar_admin'];
    $sql = "UPDATE eiche_users SET nivel = 'admin', ver_valores = 'S' WHERE ID = $userId";
    if (mysqli_query($conexao, $sql)) {
        echo "<p style='color: green; font-size: 18px; margin-top: 20px;'>✅ Usuário ID $userId agora é ADMINISTRADOR!</p>";
        echo "<p><strong>Faça logout e login novamente para ver o menu de Configurações.</strong></p>";
        echo "<meta http-equiv='refresh' content='2;url=corrigir-admin.php'>";
    } else {
        echo "<p style='color: red;'>❌ Erro: " . mysqli_error($conexao) . "</p>";
    }
}

// 5. Usuário logado
echo "<h3>4️⃣ Seu usuário atual:</h3>";
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $result = mysqli_query($conexao, "SELECT ID, name, login, COALESCE(nivel, 'user') as nivel FROM eiche_users WHERE ID = $userId");
    if ($row = mysqli_fetch_assoc($result)) {
        echo "<p>Você está logado como: <strong>{$row['name']}</strong> ({$row['login']})</p>";
        echo "<p>Nível atual: <strong>" . ($row['nivel'] === 'admin' ? '👑 Administrador' : '👤 Usuário') . "</strong></p>";
        
        if ($row['nivel'] !== 'admin') {
            echo "<p style='background: #fee2e2; padding: 15px; border-radius: 8px;'>";
            echo "⚠️ <strong>Seu usuário NÃO é administrador!</strong><br>";
            echo "Clique no botão '👑 Tornar Admin' ao lado do seu usuário na tabela acima.";
            echo "</p>";
        } else {
            echo "<p style='background: #dcfce7; padding: 15px; border-radius: 8px;'>";
            echo "✅ <strong>Seu usuário É administrador!</strong><br>";
            echo "Se o menu não aparece, faça <a href='logout.php'>logout</a> e login novamente.";
            echo "</p>";
        }
    }
} else {
    echo "<p style='color: orange;'>⚠️ Você não está logado. <a href='login.php'>Fazer login</a></p>";
}

mysqli_close($conexao);

echo "<hr style='margin-top: 30px;'>";
echo "<p style='color: #dc2626;'>⚠️ <strong>IMPORTANTE:</strong> Apague este arquivo após usar!</p>";
echo "<p><a href='dashboard.php' style='background: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none;'>← Voltar ao Dashboard</a></p>";
?>

