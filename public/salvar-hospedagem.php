<?php
/**
 * Pousada Bona - Salvar Nova Hospedagem
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

$db_host = 'localhost';
$db_user = 'pous3527_root';
$db_pass = ';Fb6818103200';
$db_name = 'pous3527_eiche';

$conexao = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conexao) {
    echo json_encode(['success' => false, 'error' => 'Erro de conexão']);
    exit;
}
mysqli_set_charset($conexao, 'utf8');

// Receber dados
$quartoId = isset($_POST['quarto_id']) ? (int)$_POST['quarto_id'] : 0;
$clienteId = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : 0;
$dataEntrada = isset($_POST['data_entrada']) ? $_POST['data_entrada'] : '';
$dataSaida = isset($_POST['data_saida']) ? $_POST['data_saida'] : '';
$valorDiaria = isset($_POST['valor_diaria']) ? (float)$_POST['valor_diaria'] : 0;
$tipo = isset($_POST['tipo']) ? $_POST['tipo'] : 'hospedagem';
$numHospedes = isset($_POST['num_hospedes']) ? (int)$_POST['num_hospedes'] : 1;
$camaExtra = isset($_POST['cama_extra']) ? $_POST['cama_extra'] : 'N';

// Validações
if (!$quartoId || !$clienteId || !$dataEntrada || !$dataSaida) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos. Verifique cliente, datas e quarto.']);
    exit;
}

if ($dataSaida < $dataEntrada) {
    echo json_encode(['success' => false, 'error' => 'Data de saída deve ser maior ou igual à entrada']);
    exit;
}

// Verificar se o quarto está disponível no período
$sqlVerifica = "SELECT ID FROM eiche_hospedagem 
                WHERE ID_quarto = $quartoId 
                AND data >= '$dataEntrada' AND data <= '$dataSaida' 
                AND rstatus IN ('A', 'R') 
                LIMIT 1";
$resultVerifica = mysqli_query($conexao, $sqlVerifica);
if ($resultVerifica && mysqli_num_rows($resultVerifica) > 0) {
    echo json_encode(['success' => false, 'error' => 'Quarto já ocupado neste período']);
    exit;
}

// Gerar novo ID de hospedagem
$sqlMaxId = "SELECT MAX(ID) as maxid FROM eiche_hospedagem";
$resultMax = mysqli_query($conexao, $sqlMaxId);
$novoId = 1;
if ($resultMax) {
    $row = mysqli_fetch_assoc($resultMax);
    $novoId = ($row['maxid'] ?? 0) + 1;
}

// Status: A = Ativo (hospedagem), R = Reserva
$rstatus = ($tipo === 'reserva') ? 'R' : 'A';

// ID da empresa (default 1)
$idEmp = 1;

// Verificar se a coluna num_hospedes existe e criar se não existir
$sqlCheckCol = "SHOW COLUMNS FROM eiche_hospedagem LIKE 'num_hospedes'";
$resultCol = mysqli_query($conexao, $sqlCheckCol);
$temColunaHospedes = ($resultCol && mysqli_num_rows($resultCol) > 0);

if (!$temColunaHospedes) {
    // Tentar criar a coluna
    mysqli_query($conexao, "ALTER TABLE eiche_hospedagem ADD COLUMN num_hospedes INT DEFAULT 1 AFTER valor_diaria");
    $temColunaHospedes = true;
}

// Inserir registros para cada dia
$dataAtual = $dataEntrada;
$idonly = 1;
$sucesso = true;

while ($dataAtual <= $dataSaida) {
    // Tipo do dia: E = Entrada, T = Durante, S = Saída
    $tipoDia = 'T';
    if ($dataAtual === $dataEntrada) $tipoDia = 'E';
    if ($dataAtual === $dataSaida) $tipoDia = 'S';
    if ($dataEntrada === $dataSaida) $tipoDia = 'E'; // Mesmo dia = só entrada
    
    if ($temColunaHospedes) {
        $sql = "INSERT INTO eiche_hospedagem (
                    ID, idonly, data, ID_quarto, tipo, rstatus, data_inicial, 
                    ID_cliente, valor_diaria, num_hospedes, ID_emp
                ) VALUES (
                    $novoId, $idonly, '$dataAtual', $quartoId, '$tipoDia', '$rstatus', '$dataEntrada', 
                    $clienteId, $valorDiaria, $numHospedes, $idEmp
                )";
    } else {
        $sql = "INSERT INTO eiche_hospedagem (
                    ID, idonly, data, ID_quarto, tipo, rstatus, data_inicial, 
                    ID_cliente, valor_diaria, ID_emp
                ) VALUES (
                    $novoId, $idonly, '$dataAtual', $quartoId, '$tipoDia', '$rstatus', '$dataEntrada', 
                    $clienteId, $valorDiaria, $idEmp
                )";
    }
    
    if (!mysqli_query($conexao, $sql)) {
        $sucesso = false;
        $erro = mysqli_error($conexao);
        break;
    }
    
    $dataAtual = date('Y-m-d', strtotime($dataAtual . ' + 1 day'));
}

// Se sucesso, registrar hóspede principal
if ($sucesso) {
    // Buscar se o cliente já é um hóspede
    $sqlHospede = "SELECT ID FROM eiche_hosp_hospedes WHERE ID_cliente = $clienteId LIMIT 1";
    $resultHospede = mysqli_query($conexao, $sqlHospede);
    $hospedeId = 0;
    
    if ($resultHospede && mysqli_num_rows($resultHospede) > 0) {
        $rowH = mysqli_fetch_assoc($resultHospede);
        $hospedeId = $rowH['ID'];
    } else {
        // Buscar dados do cliente para criar hóspede
        $sqlCliente = "SELECT razao, cpf_cnpj, telefone FROM eiche_customers WHERE ID = $clienteId LIMIT 1";
        $resultCliente = mysqli_query($conexao, $sqlCliente);
        if ($resultCliente && mysqli_num_rows($resultCliente) > 0) {
            $cli = mysqli_fetch_assoc($resultCliente);
            $nome = mysqli_real_escape_string($conexao, $cli['razao']);
            $cpf = mysqli_real_escape_string($conexao, $cli['cpf_cnpj'] ?? '');
            
            $sqlInsertHosp = "INSERT INTO eiche_hosp_hospedes (nome, cpf, ID_cliente) VALUES ('$nome', '$cpf', $clienteId)";
            if (mysqli_query($conexao, $sqlInsertHosp)) {
                $hospedeId = mysqli_insert_id($conexao);
            }
        }
    }
    
    // Vincular hóspede à hospedagem
    if ($hospedeId > 0) {
        $sqlLink = "INSERT INTO eiche_hosp_lnk_reser_hosp (ID_hosp, ID_quarto, ID_hospede, idonly) 
                    VALUES ($novoId, $quartoId, $hospedeId, $idonly)";
        mysqli_query($conexao, $sqlLink);
    }
}

mysqli_close($conexao);

if ($sucesso) {
    echo json_encode(['success' => true, 'id' => $novoId, 'status' => $rstatus]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar: ' . ($erro ?? 'Desconhecido')]);
}
