<?php
/**
 * Debug - Valores dos Quartos e Grupos
 */
session_start();

$db_host = 'localhost';
$db_user = 'pous3527_root';
$db_pass = ';Fb6818103200';
$db_name = 'pous3527_eiche';

$conexao = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conexao) {
    die("Erro de conexão");
}
mysqli_set_charset($conexao, 'utf8');

echo "<h2>Grupos de Quartos (eiche_hosp_gruposq)</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Nome</th><th>Valor Base</th><th>Vlr Cama Extra</th><th>Vlr Add Base</th><th>Vlr Add</th></tr>";
$sql = "SELECT * FROM eiche_hosp_gruposq ORDER BY ID";
$result = mysqli_query($conexao, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>{$row['ID']}</td>";
    echo "<td>{$row['nome']}</td>";
    echo "<td>R$ " . number_format($row['valor'], 2, ',', '.') . "</td>";
    echo "<td>R$ " . number_format($row['vlr_ce'], 2, ',', '.') . "</td>";
    echo "<td>{$row['vlr_add_base']}</td>";
    echo "<td>R$ " . number_format($row['vlr_add'], 2, ',', '.') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Quartos (eiche_hosp_quartos)</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Número</th><th>Ocupantes</th><th>Valor</th><th>Vlr CE</th><th>Vlr Add Base</th><th>Vlr Add</th><th>Grupo ID</th></tr>";
$sql = "SELECT * FROM eiche_hosp_quartos ORDER BY ID";
$result = mysqli_query($conexao, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>{$row['ID']}</td>";
    echo "<td>{$row['numero']}</td>";
    echo "<td>{$row['ocupantes']}</td>";
    echo "<td>R$ " . number_format($row['valor'], 2, ',', '.') . "</td>";
    echo "<td>R$ " . number_format($row['vlr_ce'], 2, ',', '.') . "</td>";
    echo "<td>{$row['vlr_add_base']}</td>";
    echo "<td>R$ " . number_format($row['vlr_add'], 2, ',', '.') . "</td>";
    echo "<td>{$row['grupo']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Quartos com dados do Grupo</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Quarto</th><th>Valor Quarto</th><th>Grupo</th><th>Valor Grupo</th><th>Valor Final</th><th>Add Base (Q)</th><th>Add Base (G)</th><th>Add (Q)</th><th>Add (G)</th></tr>";
$sql = "SELECT q.ID, q.numero, q.valor, q.vlr_add_base, q.vlr_add, q.grupo,
               g.nome as grupo_nome, g.valor as g_valor, g.vlr_add_base as g_vlr_add_base, g.vlr_add as g_vlr_add
        FROM eiche_hosp_quartos q
        LEFT JOIN eiche_hosp_gruposq g ON q.grupo = g.ID
        ORDER BY q.ID";
$result = mysqli_query($conexao, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $valorFinal = ($row['valor'] > 0) ? $row['valor'] : ($row['g_valor'] ?? 0);
    echo "<tr>";
    echo "<td>{$row['numero']}</td>";
    echo "<td>R$ " . number_format($row['valor'], 2, ',', '.') . "</td>";
    echo "<td>{$row['grupo_nome']} (ID: {$row['grupo']})</td>";
    echo "<td>R$ " . number_format($row['g_valor'] ?? 0, 2, ',', '.') . "</td>";
    echo "<td style='font-weight:bold'>R$ " . number_format($valorFinal, 2, ',', '.') . "</td>";
    echo "<td>{$row['vlr_add_base']}</td>";
    echo "<td>{$row['g_vlr_add_base']}</td>";
    echo "<td>R$ " . number_format($row['vlr_add'], 2, ',', '.') . "</td>";
    echo "<td>R$ " . number_format($row['g_vlr_add'] ?? 0, 2, ',', '.') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p style='color:red'><strong>ATENÇÃO:</strong> Apague este arquivo após verificar!</p>";

mysqli_close($conexao);
?>

