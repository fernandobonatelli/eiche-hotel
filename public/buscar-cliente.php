<?php
/**
 * Pousada Bona - Buscar Cliente (AJAX)
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$db_host = 'localhost';
$db_user = 'pous3527_root';
$db_pass = ';Fb6818103200';
$db_name = 'pous3527_eiche';

$conexao = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conexao) {
    echo json_encode([]);
    exit;
}
mysqli_set_charset($conexao, 'utf8');

$termo = isset($_GET['termo']) ? trim($_GET['termo']) : '';

if (strlen($termo) < 2) {
    echo json_encode([]);
    exit;
}

// Escapar para SQL
$termo = mysqli_real_escape_string($conexao, $termo);

// Buscar por nome (razao/fantasia) ou CPF
// Campos corretos da tabela eiche_customers: razao, fantasia, cpf, cnpj, fone1, email1
$sql = "SELECT ID, razao, fantasia, cpf, cnpj, fone1, email1 
        FROM eiche_customers 
        WHERE razao LIKE '%$termo%' 
           OR fantasia LIKE '%$termo%' 
           OR cpf LIKE '%$termo%' 
           OR cnpj LIKE '%$termo%'
        ORDER BY razao 
        LIMIT 20";

$result = mysqli_query($conexao, $sql);
$clientes = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Determinar qual documento mostrar
        $doc = '';
        if (!empty($row['cpf']) && $row['cpf'] != '000.000.000-00') {
            $doc = $row['cpf'];
        } elseif (!empty($row['cnpj']) && $row['cnpj'] != '000.000.000/0000-00') {
            $doc = $row['cnpj'];
        }
        
        $clientes[] = [
            'ID' => $row['ID'],
            'razao' => $row['razao'] ?: $row['fantasia'],
            'cpf_cnpj' => $doc,
            'telefone' => $row['fone1'],
            'email' => $row['email1']
        ];
    }
}

mysqli_close($conexao);
echo json_encode($clientes);
