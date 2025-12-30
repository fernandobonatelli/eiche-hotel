<?php
/**
 * Script para remover espaços em branco no início e fim dos nomes dos clientes
 * Execute uma vez e depois apague este arquivo
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

echo "<h2>🔧 Corrigindo espaços em branco nos clientes</h2>";

// Atualizar campo razao (nome principal)
$sql = "UPDATE eiche_customers SET razao = TRIM(razao) WHERE razao != TRIM(razao)";
$result = mysqli_query($conexao, $sql);
$affected = mysqli_affected_rows($conexao);
echo "<p>✅ Campo 'razao' corrigido: <strong>$affected</strong> registros atualizados</p>";

// Atualizar campo fantasia
$sql = "UPDATE eiche_customers SET fantasia = TRIM(fantasia) WHERE fantasia IS NOT NULL AND fantasia != TRIM(fantasia)";
$result = mysqli_query($conexao, $sql);
$affected = mysqli_affected_rows($conexao);
echo "<p>✅ Campo 'fantasia' corrigido: <strong>$affected</strong> registros atualizados</p>";

// Atualizar CPF
$sql = "UPDATE eiche_customers SET cpf = TRIM(cpf) WHERE cpf IS NOT NULL AND cpf != TRIM(cpf)";
mysqli_query($conexao, $sql);
$affected = mysqli_affected_rows($conexao);
echo "<p>✅ Campo 'cpf' corrigido: <strong>$affected</strong> registros atualizados</p>";

// Atualizar CNPJ
$sql = "UPDATE eiche_customers SET cnpj = TRIM(cnpj) WHERE cnpj IS NOT NULL AND cnpj != TRIM(cnpj)";
mysqli_query($conexao, $sql);
$affected = mysqli_affected_rows($conexao);
echo "<p>✅ Campo 'cnpj' corrigido: <strong>$affected</strong> registros atualizados</p>";

// Atualizar telefones
$sql = "UPDATE eiche_customers SET fone1 = TRIM(fone1) WHERE fone1 IS NOT NULL AND fone1 != TRIM(fone1)";
mysqli_query($conexao, $sql);
$affected = mysqli_affected_rows($conexao);
echo "<p>✅ Campo 'fone1' corrigido: <strong>$affected</strong> registros atualizados</p>";

// Atualizar email
$sql = "UPDATE eiche_customers SET email1 = TRIM(email1) WHERE email1 IS NOT NULL AND email1 != TRIM(email1)";
mysqli_query($conexao, $sql);
$affected = mysqli_affected_rows($conexao);
echo "<p>✅ Campo 'email1' corrigido: <strong>$affected</strong> registros atualizados</p>";

// Atualizar cidade
$sql = "UPDATE eiche_customers SET e_cidade = TRIM(e_cidade) WHERE e_cidade IS NOT NULL AND e_cidade != TRIM(e_cidade)";
mysqli_query($conexao, $sql);
$affected = mysqli_affected_rows($conexao);
echo "<p>✅ Campo 'e_cidade' corrigido: <strong>$affected</strong> registros atualizados</p>";

mysqli_close($conexao);

echo "<hr>";
echo "<h3 style='color: green;'>✅ Correção concluída!</h3>";
echo "<p><strong>⚠️ IMPORTANTE:</strong> Apague este arquivo após executar!</p>";
echo "<p><a href='guests.php'>← Voltar para Clientes</a></p>";
?>

