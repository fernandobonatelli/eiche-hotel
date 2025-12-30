<?php
/**
 * Script para adicionar coluna num_hospedes na tabela eiche_hospedagem
 * Execute uma vez e depois delete este arquivo
 */

$db_host = 'localhost';
$db_user = 'pous3527_root';
$db_pass = ';Fb6818103200';
$db_name = 'pous3527_eiche';

$conexao = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conexao) {
    die("Erro de conexão: " . mysqli_connect_error());
}
mysqli_set_charset($conexao, 'utf8');

echo "<h2>Adicionando coluna num_hospedes</h2>";

// Verificar se a coluna já existe
$sqlCheck = "SHOW COLUMNS FROM eiche_hospedagem LIKE 'num_hospedes'";
$result = mysqli_query($conexao, $sqlCheck);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✅ Coluna num_hospedes já existe!</p>";
} else {
    // Adicionar a coluna
    $sqlAdd = "ALTER TABLE eiche_hospedagem ADD COLUMN num_hospedes INT DEFAULT 1 AFTER valor_diaria";
    
    if (mysqli_query($conexao, $sqlAdd)) {
        echo "<p style='color: green;'>✅ Coluna num_hospedes adicionada com sucesso!</p>";
    } else {
        echo "<p style='color: red;'>❌ Erro ao adicionar coluna: " . mysqli_error($conexao) . "</p>";
    }
}

mysqli_close($conexao);

echo "<br><br>";
echo "<p><strong>⚠️ IMPORTANTE:</strong> Delete este arquivo após executar!</p>";
echo "<p><a href='reservations.php'>← Voltar para Hospedagens</a></p>";
?>

