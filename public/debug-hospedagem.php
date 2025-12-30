<?php
/**
 * Debug - Verificar estrutura das hospedagens
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$db_host = 'localhost';
$db_user = 'pous3527_root';
$db_pass = ';Fb6818103200';
$db_name = 'pous3527_eiche';

$conexao = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conexao) {
    die("Erro de conexão: " . mysqli_connect_error());
}
mysqli_set_charset($conexao, 'utf8');

echo "<html><head><title>Debug Hospedagem</title>";
echo "<style>body{font-family:Arial;font-size:12px;padding:20px;} table{border-collapse:collapse;margin:10px 0;} th,td{border:1px solid #ccc;padding:5px 8px;text-align:left;} th{background:#f0f0f0;} .ok{color:green;} .erro{color:red;}</style>";
echo "</head><body>";

echo "<h2>🔍 Debug - Estrutura de Hospedagens</h2>";

// 1. Verificar estrutura da tabela
echo "<h3>1️⃣ Estrutura da tabela eiche_hospedagem</h3>";
$cols = mysqli_query($conexao, "DESCRIBE eiche_hospedagem");
if ($cols) {
    echo "<table><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th></tr>";
    while ($col = mysqli_fetch_assoc($cols)) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p class='erro'>Erro ao ler estrutura: " . mysqli_error($conexao) . "</p>";
}

// 2. Contar registros
echo "<h3>2️⃣ Contagem de registros</h3>";
$total = mysqli_query($conexao, "SELECT COUNT(*) as total FROM eiche_hospedagem");
$row = mysqli_fetch_assoc($total);
echo "<p>Total de registros na tabela: <strong>{$row['total']}</strong></p>";

// Por status
$porStatus = mysqli_query($conexao, "SELECT rstatus, COUNT(*) as total FROM eiche_hospedagem GROUP BY rstatus");
if ($porStatus && mysqli_num_rows($porStatus) > 0) {
    echo "<table><tr><th>Status</th><th>Quantidade</th><th>Significado</th></tr>";
    while ($st = mysqli_fetch_assoc($porStatus)) {
        $sig = '';
        if ($st['rstatus'] == 'A') $sig = 'Ativo/Aberto';
        if ($st['rstatus'] == 'R') $sig = 'Reserva';
        if ($st['rstatus'] == 'F') $sig = 'Fechado';
        if ($st['rstatus'] == 'D') $sig = 'Desativado';
        if ($st['rstatus'] == 'S') $sig = 'No-Show';
        echo "<tr><td>{$st['rstatus']}</td><td>{$st['total']}</td><td>{$sig}</td></tr>";
    }
    echo "</table>";
}

// 3. Verificar formato de datas
echo "<h3>3️⃣ Amostra de dados (últimos 10 registros)</h3>";
$amostra = mysqli_query($conexao, "SELECT ID, ID_quarto, ID_cliente, rstatus, tipo, data, data_inicial, data_final, idonly FROM eiche_hospedagem ORDER BY ID DESC LIMIT 10");
if ($amostra && mysqli_num_rows($amostra) > 0) {
    echo "<table><tr><th>ID</th><th>ID_quarto</th><th>ID_cliente</th><th>rstatus</th><th>tipo</th><th>data</th><th>data_inicial</th><th>data_final</th><th>idonly</th></tr>";
    while ($am = mysqli_fetch_assoc($amostra)) {
        echo "<tr>";
        echo "<td>{$am['ID']}</td>";
        echo "<td>{$am['ID_quarto']}</td>";
        echo "<td>{$am['ID_cliente']}</td>";
        echo "<td>{$am['rstatus']}</td>";
        echo "<td>{$am['tipo']}</td>";
        echo "<td>{$am['data']}</td>";
        echo "<td>{$am['data_inicial']}</td>";
        echo "<td>{$am['data_final']}</td>";
        echo "<td>{$am['idonly']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='erro'>Nenhum registro encontrado ou erro.</p>";
}

// 4. Quartos cadastrados
echo "<h3>4️⃣ Quartos cadastrados</h3>";
$quartos = mysqli_query($conexao, "SELECT ID, numero, ocupantes FROM eiche_hosp_quartos ORDER BY numero LIMIT 20");
if ($quartos && mysqli_num_rows($quartos) > 0) {
    echo "<table><tr><th>ID</th><th>Número</th><th>Ocupantes</th></tr>";
    while ($q = mysqli_fetch_assoc($quartos)) {
        echo "<tr><td>{$q['ID']}</td><td>{$q['numero']}</td><td>{$q['ocupantes']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p class='erro'>Nenhum quarto cadastrado.</p>";
}

// 5. Hospedagens ativas HOJE
$hoje = date('Y-m-d');
echo "<h3>5️⃣ Hospedagens para hoje ($hoje)</h3>";

// Teste 1: pela coluna data
$teste1 = mysqli_query($conexao, "SELECT * FROM eiche_hospedagem WHERE data = '$hoje' LIMIT 10");
$count1 = $teste1 ? mysqli_num_rows($teste1) : 0;
echo "<p>Buscando por <code>data = '$hoje'</code>: <strong>$count1 registros</strong></p>";

// Teste 2: por data_inicial <= hoje e data_final >= hoje
$teste2 = mysqli_query($conexao, "SELECT * FROM eiche_hospedagem WHERE data_inicial <= '$hoje' AND data_final >= '$hoje' AND rstatus = 'A' LIMIT 10");
$count2 = $teste2 ? mysqli_num_rows($teste2) : 0;
echo "<p>Buscando por <code>data_inicial <= '$hoje' AND data_final >= '$hoje' AND rstatus='A'</code>: <strong>$count2 registros</strong></p>";

// Teste 3: todas ativas
$teste3 = mysqli_query($conexao, "SELECT * FROM eiche_hospedagem WHERE rstatus = 'A' LIMIT 10");
$count3 = $teste3 ? mysqli_num_rows($teste3) : 0;
echo "<p>Buscando por <code>rstatus = 'A'</code>: <strong>$count3 registros</strong></p>";

if ($teste3 && mysqli_num_rows($teste3) > 0) {
    echo "<h4>Detalhes das hospedagens ativas:</h4>";
    echo "<table><tr><th>ID</th><th>Quarto</th><th>Cliente</th><th>Data</th><th>Data Inicial</th><th>Data Final</th><th>Tipo</th></tr>";
    while ($t = mysqli_fetch_assoc($teste3)) {
        echo "<tr>";
        echo "<td>{$t['ID']}</td>";
        echo "<td>{$t['ID_quarto']}</td>";
        echo "<td>{$t['ID_cliente']}</td>";
        echo "<td>{$t['data']}</td>";
        echo "<td>{$t['data_inicial']}</td>";
        echo "<td>{$t['data_final']}</td>";
        echo "<td>{$t['tipo']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<p><strong>⚠️ IMPORTANTE:</strong> Apague este arquivo após o diagnóstico!</p>";
echo "<p><a href='reservations.php'>← Voltar para Hospedagens</a></p>";

mysqli_close($conexao);
echo "</body></html>";
?>

