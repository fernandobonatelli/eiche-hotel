<?php
/**
 * Debug - Consumos e Serviços
 */

$db_host = 'localhost';
$db_user = 'pous3527_root';
$db_pass = ';Fb6818103200';
$db_name = 'pous3527_eiche';

$conexao = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
mysqli_set_charset($conexao, 'utf8');

echo "<h1>🔍 Debug - Consumos e Serviços</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} table{border-collapse:collapse;margin:10px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;font-size:12px;} th{background:#f5f5f5;}</style>";

// ===== ESTRUTURA DAS TABELAS =====
echo "<h2>1️⃣ Estrutura da tabela eiche_hosp_lnk_cons_hosp (Consumos)</h2>";
$result = mysqli_query($conexao, "SHOW COLUMNS FROM eiche_hosp_lnk_cons_hosp");
if ($result) {
    echo "<table><tr><th>Campo</th><th>Tipo</th><th>Null</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>Erro: " . mysqli_error($conexao) . "</p>";
}

echo "<h2>2️⃣ Estrutura da tabela eiche_hosp_lnk_serv_hosp (Serviços)</h2>";
$result = mysqli_query($conexao, "SHOW COLUMNS FROM eiche_hosp_lnk_serv_hosp");
if ($result) {
    echo "<table><tr><th>Campo</th><th>Tipo</th><th>Null</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>Erro: " . mysqli_error($conexao) . "</p>";
}

// ===== CONTAGEM DE REGISTROS =====
echo "<h2>3️⃣ Contagem de Registros</h2>";

$result = mysqli_query($conexao, "SELECT COUNT(*) as total FROM eiche_hosp_lnk_cons_hosp");
$totalCons = mysqli_fetch_assoc($result)['total'] ?? 0;
echo "<p><strong>Total de Consumos:</strong> $totalCons registros</p>";

$result = mysqli_query($conexao, "SELECT COUNT(*) as total FROM eiche_hosp_lnk_serv_hosp");
$totalServ = mysqli_fetch_assoc($result)['total'] ?? 0;
echo "<p><strong>Total de Serviços:</strong> $totalServ registros</p>";

// ===== AMOSTRA DE CONSUMOS =====
echo "<h2>4️⃣ Últimos 10 Consumos</h2>";
$sql = "SELECT c.*, p.description as produto_nome 
        FROM eiche_hosp_lnk_cons_hosp c 
        LEFT JOIN eiche_prodorserv p ON c.ID_pors = p.ID 
        ORDER BY c.ID DESC LIMIT 10";
$result = mysqli_query($conexao, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    echo "<table><tr><th>ID</th><th>ID_hosp</th><th>ID_pors</th><th>Produto</th><th>Qtd</th><th>Valor Unit</th><th>Total</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        $total = ($row['valor_unit'] ?? 0) * ($row['qtd'] ?? 0);
        echo "<tr>
            <td>{$row['ID']}</td>
            <td>{$row['ID_hosp']}</td>
            <td>{$row['ID_pors']}</td>
            <td>" . htmlspecialchars($row['produto_nome'] ?? '-') . "</td>
            <td>{$row['qtd']}</td>
            <td>R$ " . number_format($row['valor_unit'] ?? 0, 2, ',', '.') . "</td>
            <td>R$ " . number_format($total, 2, ',', '.') . "</td>
        </tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange;'>Nenhum consumo encontrado ou erro: " . mysqli_error($conexao) . "</p>";
}

// ===== AMOSTRA DE SERVIÇOS =====
echo "<h2>5️⃣ Últimos 10 Serviços</h2>";
$sql = "SELECT s.*, p.description as servico_nome 
        FROM eiche_hosp_lnk_serv_hosp s 
        LEFT JOIN eiche_prodorserv p ON s.ID_pors = p.ID 
        ORDER BY s.ID DESC LIMIT 10";
$result = mysqli_query($conexao, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    echo "<table><tr><th>ID</th><th>ID_hosp</th><th>ID_pors</th><th>Serviço</th><th>Qtd</th><th>Valor Unit</th><th>Total</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        $total = ($row['valor_unit'] ?? 0) * ($row['qtd'] ?? 0);
        echo "<tr>
            <td>{$row['ID']}</td>
            <td>{$row['ID_hosp']}</td>
            <td>{$row['ID_pors']}</td>
            <td>" . htmlspecialchars($row['servico_nome'] ?? '-') . "</td>
            <td>{$row['qtd']}</td>
            <td>R$ " . number_format($row['valor_unit'] ?? 0, 2, ',', '.') . "</td>
            <td>R$ " . number_format($total, 2, ',', '.') . "</td>
        </tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange;'>Nenhum serviço encontrado ou erro: " . mysqli_error($conexao) . "</p>";
}

// ===== SOMA TOTAL =====
echo "<h2>6️⃣ Soma Total (todos os registros)</h2>";

$sql = "SELECT SUM(valor_unit * qtd) as total FROM eiche_hosp_lnk_cons_hosp";
$result = mysqli_query($conexao, $sql);
$somaCons = mysqli_fetch_assoc($result)['total'] ?? 0;
echo "<p><strong>Soma de todos os Consumos:</strong> R$ " . number_format($somaCons, 2, ',', '.') . "</p>";

$sql = "SELECT SUM(valor_unit * qtd) as total FROM eiche_hosp_lnk_serv_hosp";
$result = mysqli_query($conexao, $sql);
$somaServ = mysqli_fetch_assoc($result)['total'] ?? 0;
echo "<p><strong>Soma de todos os Serviços:</strong> R$ " . number_format($somaServ, 2, ',', '.') . "</p>";

// ===== TESTE JOIN COM HOSPEDAGEM =====
echo "<h2>7️⃣ Teste JOIN com eiche_hospedagem</h2>";

$dataInicio = date('Y-m-01');
$dataFim = date('Y-m-d');

echo "<p>Período: $dataInicio até $dataFim</p>";

// Consumos
$sql = "SELECT COUNT(*) as qtd, SUM(c.valor_unit * c.qtd) as total 
        FROM eiche_hosp_lnk_cons_hosp c
        INNER JOIN eiche_hospedagem h ON c.ID_hosp = h.ID
        WHERE h.data >= '$dataInicio' AND h.data <= '$dataFim'";
$result = mysqli_query($conexao, $sql);
$row = mysqli_fetch_assoc($result);
echo "<p><strong>Consumos (JOIN por data hospedagem):</strong> {$row['qtd']} registros = R$ " . number_format($row['total'] ?? 0, 2, ',', '.') . "</p>";

// Consumos sem filtro de data
$sql = "SELECT COUNT(*) as qtd, SUM(c.valor_unit * c.qtd) as total 
        FROM eiche_hosp_lnk_cons_hosp c
        INNER JOIN eiche_hospedagem h ON c.ID_hosp = h.ID";
$result = mysqli_query($conexao, $sql);
$row = mysqli_fetch_assoc($result);
echo "<p><strong>Consumos (JOIN sem filtro data):</strong> {$row['qtd']} registros = R$ " . number_format($row['total'] ?? 0, 2, ',', '.') . "</p>";

// Serviços
$sql = "SELECT COUNT(*) as qtd, SUM(s.valor_unit * s.qtd) as total 
        FROM eiche_hosp_lnk_serv_hosp s
        INNER JOIN eiche_hospedagem h ON s.ID_hosp = h.ID
        WHERE h.data >= '$dataInicio' AND h.data <= '$dataFim'";
$result = mysqli_query($conexao, $sql);
$row = mysqli_fetch_assoc($result);
echo "<p><strong>Serviços (JOIN por data hospedagem):</strong> {$row['qtd']} registros = R$ " . number_format($row['total'] ?? 0, 2, ',', '.') . "</p>";

// Serviços sem filtro
$sql = "SELECT COUNT(*) as qtd, SUM(s.valor_unit * s.qtd) as total 
        FROM eiche_hosp_lnk_serv_hosp s
        INNER JOIN eiche_hospedagem h ON s.ID_hosp = h.ID";
$result = mysqli_query($conexao, $sql);
$row = mysqli_fetch_assoc($result);
echo "<p><strong>Serviços (JOIN sem filtro data):</strong> {$row['qtd']} registros = R$ " . number_format($row['total'] ?? 0, 2, ',', '.') . "</p>";

// ===== VERIFICAR HOSPEDAGENS VINCULADAS =====
echo "<h2>8️⃣ Verificar IDs de Hospedagem nos Consumos</h2>";
$sql = "SELECT DISTINCT c.ID_hosp, h.ID as hosp_existe, h.data, h.rstatus 
        FROM eiche_hosp_lnk_cons_hosp c 
        LEFT JOIN eiche_hospedagem h ON c.ID_hosp = h.ID 
        LIMIT 20";
$result = mysqli_query($conexao, $sql);
if ($result) {
    echo "<table><tr><th>ID_hosp (consumo)</th><th>Hospedagem existe?</th><th>Data</th><th>Status</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        $existe = $row['hosp_existe'] ? '✅ Sim' : '❌ Não';
        echo "<tr>
            <td>{$row['ID_hosp']}</td>
            <td>$existe</td>
            <td>{$row['data']}</td>
            <td>{$row['rstatus']}</td>
        </tr>";
    }
    echo "</table>";
}

mysqli_close($conexao);

echo "<hr>";
echo "<p><strong>⚠️ IMPORTANTE:</strong> Apague este arquivo após o diagnóstico!</p>";
echo "<p><a href='finance.php'>← Voltar para Financeiro</a></p>";
?>

