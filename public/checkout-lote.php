<?php
/**
 * Pousada Bona - Check-out em Lote
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db_host = 'localhost';
$db_user = 'pous3527_root';
$db_pass = ';Fb6818103200';
$db_name = 'pous3527_eiche';

$conexao = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
mysqli_set_charset($conexao, 'utf8');

$ids = isset($_POST['ids']) ? $_POST['ids'] : '';

if (empty($ids)) {
    $_SESSION['erro'] = 'Nenhuma hospedagem selecionada.';
    header('Location: dashboard.php');
    exit;
}

// Separar os IDs
$idsArray = explode(',', $ids);
$idsArray = array_map('intval', $idsArray);
$idsArray = array_filter($idsArray, function($id) { return $id > 0; });

if (empty($idsArray)) {
    $_SESSION['erro'] = 'IDs inválidos.';
    header('Location: dashboard.php');
    exit;
}

$dataHora = date('Y-m-d H:i:s');
$sucesso = 0;
$erros = 0;

foreach ($idsArray as $id) {
    // Atualizar todas as entradas com este ID
    $sql = "UPDATE eiche_hospedagem 
            SET rstatus = 'F', 
                lg_checkout = '$dataHora'
            WHERE ID = $id";
    
    if (mysqli_query($conexao, $sql)) {
        $sucesso++;
    } else {
        $erros++;
    }
}

mysqli_close($conexao);

if ($erros > 0) {
    $_SESSION['mensagem'] = "Check-out realizado: $sucesso com sucesso, $erros com erro.";
} else {
    $_SESSION['mensagem'] = "✅ Check-out realizado com sucesso para $sucesso hospedagem(ns)!";
}

header('Location: dashboard.php');
exit;

