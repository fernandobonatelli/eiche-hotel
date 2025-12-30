<?php
/**
 * Pousada Bona - Ações de Hospedagem (Check-in, Check-out, Excluir)
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
if (!$conexao) {
    die("Erro de conexão");
}
mysqli_set_charset($conexao, 'utf8');

$acao = isset($_GET['acao']) ? $_GET['acao'] : '';
$hospId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$hospId) {
    header('Location: reservations.php?erro=ID inválido');
    exit;
}

$mensagem = '';
$sucesso = false;

switch ($acao) {
    case 'checkin':
        // Mudar status de R (Reserva) para A (Ativo)
        $sql = "UPDATE eiche_hospedagem SET rstatus = 'A' WHERE ID = $hospId";
        if (mysqli_query($conexao, $sql)) {
            $mensagem = 'Check-in realizado com sucesso!';
            $sucesso = true;
        } else {
            $mensagem = 'Erro ao fazer check-in: ' . mysqli_error($conexao);
        }
        break;
        
    case 'checkout':
        // Mudar status para F (Fechado) e registrar data/hora
        $agora = date('Y-m-d H:i:s');
        $sql = "UPDATE eiche_hospedagem SET rstatus = 'F', lg_checkout = '$agora' WHERE ID = $hospId";
        if (mysqli_query($conexao, $sql)) {
            $mensagem = 'Check-out realizado com sucesso!';
            $sucesso = true;
        } else {
            $mensagem = 'Erro ao fazer check-out: ' . mysqli_error($conexao);
        }
        break;
    
    case 'reabrir':
        // Reabrir hospedagem fechada (voltar para Ativo)
        $sql = "UPDATE eiche_hospedagem SET rstatus = 'A', lg_checkout = NULL WHERE ID = $hospId";
        if (mysqli_query($conexao, $sql)) {
            $mensagem = 'Hospedagem reaberta com sucesso!';
            $sucesso = true;
        } else {
            $mensagem = 'Erro ao reabrir: ' . mysqli_error($conexao);
        }
        break;
        
    case 'excluir':
        // Excluir registros da hospedagem e relacionados
        // Primeiro exclui consumos
        mysqli_query($conexao, "DELETE FROM eiche_hosp_lnk_cons_hosp WHERE ID_hosp = $hospId");
        // Exclui serviços
        mysqli_query($conexao, "DELETE FROM eiche_hosp_lnk_serv_hosp WHERE ID_hosp = $hospId");
        // Exclui vínculo com hóspedes
        mysqli_query($conexao, "DELETE FROM eiche_hosp_lnk_reser_hosp WHERE ID_hosp = $hospId");
        // Exclui a hospedagem
        $sql = "DELETE FROM eiche_hospedagem WHERE ID = $hospId";
        if (mysqli_query($conexao, $sql)) {
            $mensagem = 'Hospedagem excluída com sucesso!';
            $sucesso = true;
        } else {
            $mensagem = 'Erro ao excluir: ' . mysqli_error($conexao);
        }
        break;
        
    default:
        $mensagem = 'Ação inválida';
}

mysqli_close($conexao);

// Redirecionar com mensagem
$param = $sucesso ? 'sucesso' : 'erro';
header('Location: reservations.php?' . $param . '=' . urlencode($mensagem));
exit;

