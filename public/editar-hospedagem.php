<?php
/**
 * Pousada Bona - Editar Hospedagem
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

$hospId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mensagem = '';
$erro = '';

if (!$hospId) {
    header('Location: reservations.php');
    exit;
}

// Buscar dados da hospedagem
$sql = "SELECT h.*, c.razao as cliente_nome, q.numero as quarto_numero, q.ID as quarto_id
        FROM eiche_hospedagem h
        LEFT JOIN eiche_customers c ON h.ID_cliente = c.ID
        LEFT JOIN eiche_hosp_quartos q ON h.ID_quarto = q.ID
        WHERE h.ID = $hospId
        ORDER BY h.data ASC";
$result = mysqli_query($conexao, $sql);

$hospedagem = null;
$dias = [];
$dataEntrada = '';
$dataSaida = '';

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        if (!$hospedagem) {
            $hospedagem = $row;
            $dataEntrada = $row['data'];
        }
        $dataSaida = $row['data'];
        $dias[] = $row;
    }
}

if (!$hospedagem) {
    header('Location: reservations.php?erro=Hospedagem não encontrada');
    exit;
}

// Buscar número de hóspedes - primeiro tenta da tabela principal, depois da auxiliar
$numHospedes = 1;

// Tentar pegar num_hospedes diretamente da hospedagem (se existir)
if (isset($hospedagem['num_hospedes']) && $hospedagem['num_hospedes'] > 0) {
    $numHospedes = (int)$hospedagem['num_hospedes'];
} else {
    // Buscar da tabela de vínculos
    $sqlHospedes = "SELECT COUNT(*) as total FROM eiche_hosp_lnk_reser_hosp WHERE ID_hosp = $hospId";
    $resultHospedes = mysqli_query($conexao, $sqlHospedes);
    if ($resultHospedes) {
        $rowH = mysqli_fetch_assoc($resultHospedes);
        if ($rowH && $rowH['total'] > 0) {
            $numHospedes = (int)$rowH['total'];
        }
    }
}

// Buscar dados do quarto para cálculo de valores
$capacidadeQuarto = 4;
$valorBase = 0;
$valorCamaExtra = 0;
$valorAddBase = 0;
$valorAdd = 0;

$sqlQuarto = "SELECT q.ocupantes, q.valor, q.vlr_ce, q.vlr_add_base, q.vlr_add, q.grupo,
                     g.valor as g_valor, g.vlr_ce as g_vlr_ce, g.vlr_add_base as g_vlr_add_base, g.vlr_add as g_vlr_add
              FROM eiche_hosp_quartos q
              LEFT JOIN eiche_hosp_gruposq g ON q.grupo = g.ID
              WHERE q.ID = " . ($hospedagem['quarto_id'] ?? 0);
$resultQuarto = mysqli_query($conexao, $sqlQuarto);
if ($resultQuarto && $rowQ = mysqli_fetch_assoc($resultQuarto)) {
    $capacidadeQuarto = max(1, (int)$rowQ['ocupantes']);
    
    // Usar valor do quarto, ou se for 0, usar do grupo
    $valorBase = ($rowQ['valor'] > 0) ? $rowQ['valor'] : ($rowQ['g_valor'] ?? 0);
    $valorCamaExtra = ($rowQ['vlr_ce'] > 0) ? $rowQ['vlr_ce'] : ($rowQ['g_vlr_ce'] ?? 0);
    $valorAddBase = ($rowQ['vlr_add_base'] > 0) ? $rowQ['vlr_add_base'] : ($rowQ['g_vlr_add_base'] ?? 0);
    $valorAdd = ($rowQ['vlr_add'] > 0) ? $rowQ['vlr_add'] : ($rowQ['g_vlr_add'] ?? 0);
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novaDataEntrada = isset($_POST['data_entrada']) ? $_POST['data_entrada'] : $dataEntrada;
    $novaDataSaida = isset($_POST['data_saida']) ? $_POST['data_saida'] : $dataSaida;
    $novoValorDiaria = isset($_POST['valor_diaria']) ? (float)$_POST['valor_diaria'] : $hospedagem['valor_diaria'];
    
    $novoNumHospedes = isset($_POST['num_hospedes']) ? (int)$_POST['num_hospedes'] : $numHospedes;
    
    if ($novaDataSaida < $novaDataEntrada) {
        $erro = 'Data de saída deve ser maior ou igual à entrada';
    } else {
        // Verificar se as datas mudaram
        $datasIguais = ($novaDataEntrada === $dataEntrada && $novaDataSaida === $dataSaida);
        
        if (!$datasIguais) {
            // Verificar disponibilidade do quarto nas novas datas
            $quartoId = $hospedagem['quarto_id'];
            $sqlVerifica = "SELECT ID FROM eiche_hospedagem 
                            WHERE ID_quarto = $quartoId 
                            AND ID != $hospId
                            AND data >= '$novaDataEntrada' AND data <= '$novaDataSaida' 
                            AND rstatus IN ('A', 'R') 
                            LIMIT 1";
            $resultVerifica = mysqli_query($conexao, $sqlVerifica);
            
            if ($resultVerifica && mysqli_num_rows($resultVerifica) > 0) {
                $erro = 'Quarto já ocupado neste período por outra hospedagem';
            }
        }
        
        if (empty($erro)) {
            // Verificar se coluna num_hospedes existe
            $sqlCheckCol = "SHOW COLUMNS FROM eiche_hospedagem LIKE 'num_hospedes'";
            $resultCol = mysqli_query($conexao, $sqlCheckCol);
            $temColunaHospedes = ($resultCol && mysqli_num_rows($resultCol) > 0);
            
            if (!$temColunaHospedes) {
                // Tentar criar a coluna
                mysqli_query($conexao, "ALTER TABLE eiche_hospedagem ADD COLUMN num_hospedes INT DEFAULT 1 AFTER valor_diaria");
                $temColunaHospedes = true;
            }
            
            // Atualizar valor da diária e número de hóspedes em todos os registros
            if ($temColunaHospedes) {
                $sqlUpdate = "UPDATE eiche_hospedagem SET valor_diaria = $novoValorDiaria, num_hospedes = $novoNumHospedes WHERE ID = $hospId";
            } else {
                $sqlUpdate = "UPDATE eiche_hospedagem SET valor_diaria = $novoValorDiaria WHERE ID = $hospId";
            }
            mysqli_query($conexao, $sqlUpdate);
            
            // Atualizar número de hóspedes
            if ($novoNumHospedes != $numHospedes) {
                // Buscar hóspede principal (do cliente)
                $clienteId = $hospedagem['ID_cliente'];
                $quartoId = $hospedagem['quarto_id'];
                
                // Buscar ou criar hóspede do cliente
                $sqlHospedePrincipal = "SELECT ID FROM eiche_hosp_hospedes WHERE ID_cliente = $clienteId LIMIT 1";
                $resultHP = mysqli_query($conexao, $sqlHospedePrincipal);
                $hospedePrincipalId = 0;
                
                if ($resultHP && mysqli_num_rows($resultHP) > 0) {
                    $rowHP = mysqli_fetch_assoc($resultHP);
                    $hospedePrincipalId = $rowHP['ID'];
                } else {
                    // Criar hóspede a partir do cliente
                    $sqlCliente = "SELECT razao, cpf FROM eiche_customers WHERE ID = $clienteId LIMIT 1";
                    $resultCli = mysqli_query($conexao, $sqlCliente);
                    if ($resultCli && $rowCli = mysqli_fetch_assoc($resultCli)) {
                        $nome = mysqli_real_escape_string($conexao, $rowCli['razao']);
                        $cpf = mysqli_real_escape_string($conexao, $rowCli['cpf'] ?? '');
                        mysqli_query($conexao, "INSERT INTO eiche_hosp_hospedes (nome, cpf, ID_cliente) VALUES ('$nome', '$cpf', $clienteId)");
                        $hospedePrincipalId = mysqli_insert_id($conexao);
                    }
                }
                
                // Remover vínculos antigos
                mysqli_query($conexao, "DELETE FROM eiche_hosp_lnk_reser_hosp WHERE ID_hosp = $hospId");
                
                // Adicionar novos vínculos (um para cada hóspede)
                for ($i = 1; $i <= $novoNumHospedes; $i++) {
                    $idonly = 1;
                    if ($i == 1 && $hospedePrincipalId > 0) {
                        // Primeiro hóspede é o principal (cliente)
                        mysqli_query($conexao, "INSERT INTO eiche_hosp_lnk_reser_hosp (ID_hosp, ID_quarto, ID_hospede, idonly) VALUES ($hospId, $quartoId, $hospedePrincipalId, $idonly)");
                    } else {
                        // Hóspedes adicionais (podem ser cadastrados depois)
                        mysqli_query($conexao, "INSERT INTO eiche_hosp_lnk_reser_hosp (ID_hosp, ID_quarto, ID_hospede, idonly) VALUES ($hospId, $quartoId, 0, $idonly)");
                    }
                }
                
                $numHospedes = $novoNumHospedes;
            }
            
            if (!$datasIguais) {
                // Deletar registros antigos
                mysqli_query($conexao, "DELETE FROM eiche_hospedagem WHERE ID = $hospId");
                
                // Inserir novos registros para cada dia
                $dataAtual = $novaDataEntrada;
                $idonly = $hospedagem['idonly'] ?? 1;
                $clienteId = $hospedagem['ID_cliente'];
                $quartoId = $hospedagem['quarto_id'];
                $rstatus = $hospedagem['rstatus'];
                $idEmp = $hospedagem['ID_emp'] ?? 1;
                
                while ($dataAtual <= $novaDataSaida) {
                    $tipoDia = 'T';
                    if ($dataAtual === $novaDataEntrada) $tipoDia = 'E';
                    if ($dataAtual === $novaDataSaida) $tipoDia = 'S';
                    if ($novaDataEntrada === $novaDataSaida) $tipoDia = 'E';
                    
                    if ($temColunaHospedes) {
                        $sqlInsert = "INSERT INTO eiche_hospedagem (
                                        ID, idonly, data, ID_quarto, tipo, rstatus, data_inicial, 
                                        ID_cliente, valor_diaria, num_hospedes, ID_emp
                                    ) VALUES (
                                        $hospId, $idonly, '$dataAtual', $quartoId, '$tipoDia', '$rstatus', '$novaDataEntrada', 
                                        $clienteId, $novoValorDiaria, $novoNumHospedes, $idEmp
                                    )";
                    } else {
                        $sqlInsert = "INSERT INTO eiche_hospedagem (
                                        ID, idonly, data, ID_quarto, tipo, rstatus, data_inicial, 
                                        ID_cliente, valor_diaria, ID_emp
                                    ) VALUES (
                                        $hospId, $idonly, '$dataAtual', $quartoId, '$tipoDia', '$rstatus', '$novaDataEntrada', 
                                        $clienteId, $novoValorDiaria, $idEmp
                                    )";
                    }
                    mysqli_query($conexao, $sqlInsert);
                    
                    $dataAtual = date('Y-m-d', strtotime($dataAtual . ' + 1 day'));
                }
            }
            
            $mensagem = 'Hospedagem atualizada com sucesso!';
            
            // Atualizar variáveis para refletir mudanças
            $dataEntrada = $novaDataEntrada;
            $dataSaida = $novaDataSaida;
            $hospedagem['valor_diaria'] = $novoValorDiaria;
        }
    }
}

// Calcular número de diárias (dias de ocupação = diferença + 1)
$diffDias = (strtotime($dataSaida) - strtotime($dataEntrada)) / 86400;
$numDiarias = max(1, (int)$diffDias + 1); // Conta todos os dias de ocupação

$pageTitle = 'Editar Hospedagem - Pousada Bona';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
    <style>
        .page-container { max-width: 600px; margin: 0 auto; }
        .back-link { display: inline-flex; align-items: center; gap: 5px; color: #666; text-decoration: none; font-size: 13px; margin-bottom: 15px; }
        .back-link:hover { color: #333; }
        
        .card { background: white; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 15px; }
        .card-header { padding: 14px 18px; border-bottom: 1px solid #eee; font-weight: 600; font-size: 14px; background: #f9fafb; }
        .card-body { padding: 18px; }
        
        .info-bar { display: flex; gap: 20px; margin-bottom: 15px; font-size: 12px; padding: 12px 15px; background: #f0f9ff; border-radius: 6px; border: 1px solid #bae6fd; flex-wrap: wrap; }
        .info-bar div { display: flex; gap: 5px; }
        .info-bar label { color: #0369a1; font-weight: 500; }
        .info-bar span { font-weight: 600; color: #0c4a6e; }
        
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 12px; color: #555; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px; }
        .form-group input:focus, .form-group select:focus { border-color: #3b82f6; outline: none; }
        .form-group small { display: block; margin-top: 4px; font-size: 11px; color: #888; }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        
        .btn { padding: 12px 20px; border-radius: 6px; font-size: 13px; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-secondary { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
        .btn-secondary:hover { background: #e5e7eb; }
        
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        
        .calc-box { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 6px; padding: 12px 15px; margin-top: 15px; }
        .calc-box .title { font-size: 11px; color: #b45309; font-weight: 600; margin-bottom: 8px; }
        .calc-box .calc-row { display: flex; justify-content: space-between; font-size: 13px; padding: 4px 0; }
        .calc-box .calc-total { font-weight: 700; font-size: 15px; color: #92400e; border-top: 1px solid #fcd34d; margin-top: 8px; padding-top: 8px; }
        
        .action-bar { display: flex; gap: 10px; margin-top: 20px; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-wrapper">
        <?php include 'includes/topbar.php'; ?>
        
        <main class="main-content">
            <div class="page-container">
                <a href="hospedagem-detalhes.php?id=<?php echo $hospId; ?>" class="back-link">← Voltar para Detalhes</a>
                
                <?php if ($mensagem): ?>
                    <div class="alert alert-success"><?php echo $mensagem; ?></div>
                <?php endif; ?>
                
                <?php if ($erro): ?>
                    <div class="alert alert-error"><?php echo $erro; ?></div>
                <?php endif; ?>
                
                <div class="info-bar">
                    <div><label>Hospedagem:</label> <span>#<?php echo str_pad($hospId, 6, '0', STR_PAD_LEFT); ?></span></div>
                    <div><label>Cliente:</label> <span><?php echo htmlspecialchars($hospedagem['cliente_nome'] ?? '-'); ?></span></div>
                    <div><label>Quarto:</label> <span><?php echo htmlspecialchars($hospedagem['quarto_numero'] ?? '-'); ?></span></div>
                </div>
                
                <div class="card">
                    <div class="card-header">✏️ Editar Hospedagem</div>
                    <div class="card-body">
                        <form method="POST" id="form-editar">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>📅 Data de Entrada</label>
                                    <input type="date" name="data_entrada" id="data_entrada" value="<?php echo $dataEntrada; ?>" required onchange="calcularTotal()">
                                </div>
                                <div class="form-group">
                                    <label>📅 Data de Saída</label>
                                    <input type="date" name="data_saida" id="data_saida" value="<?php echo $dataSaida; ?>" required onchange="calcularTotal()">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>💵 Valor da Diária (R$)</label>
                                    <input type="number" name="valor_diaria" id="valor_diaria" step="0.01" value="<?php echo number_format($hospedagem['valor_diaria'] ?? 0, 2, '.', ''); ?>" required onchange="calcularTotal()">
                                </div>
                                <div class="form-group">
                                    <label>👥 Número de Hóspedes</label>
                                    <select name="num_hospedes" id="num_hospedes" onchange="recalcularDiaria()">
                                        <?php for ($i = 1; $i <= max($capacidadeQuarto, 10); $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo ($i == $numHospedes) ? 'selected' : ''; ?>>
                                                <?php echo $i; ?> pessoa(s)
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                    <small>Capacidade: <?php echo $capacidadeQuarto; ?> pessoas | Cama extra: R$ <?php echo number_format($valorCamaExtra, 2, ',', '.'); ?></small>
                                </div>
                            </div>
                            
                            <div class="calc-box">
                                <div class="title">📊 CÁLCULO ESTIMADO</div>
                                <div class="calc-row">
                                    <span>Diárias:</span>
                                    <span id="calc-diarias"><?php echo $numDiarias; ?></span>
                                </div>
                                <div class="calc-row">
                                    <span>Valor unitário:</span>
                                    <span>R$ <span id="calc-valor"><?php echo number_format($hospedagem['valor_diaria'] ?? 0, 2, ',', '.'); ?></span></span>
                                </div>
                                <div class="calc-row calc-total">
                                    <span>TOTAL DIÁRIAS:</span>
                                    <span>R$ <span id="calc-total"><?php echo number_format(($hospedagem['valor_diaria'] ?? 0) * $numDiarias, 2, ',', '.'); ?></span></span>
                                </div>
                            </div>
                            
                            <div class="action-bar">
                                <button type="submit" class="btn btn-primary">💾 Salvar Alterações</button>
                                <a href="hospedagem-detalhes.php?id=<?php echo $hospId; ?>" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
    // Valores do quarto/grupo para cálculo
    var valorBase = <?php echo $valorBase; ?>;
    var valorCamaExtra = <?php echo $valorCamaExtra; ?>; // valor da cama extra
    var capacidadeQuarto = <?php echo $capacidadeQuarto; ?>; // ocupantes base do quarto
    var numHospedesOriginal = <?php echo $numHospedes; ?>;
    var valorDiariaOriginal = <?php echo $hospedagem['valor_diaria'] ?? 0; ?>;
    
    function recalcularDiaria() {
        var numHospedes = parseInt(document.getElementById('num_hospedes').value) || 1;
        var novoValor = valorBase;
        
        // Se o número de hóspedes excede a capacidade do quarto, adiciona cama extra
        if (numHospedes > capacidadeQuarto) {
            var camasExtras = numHospedes - capacidadeQuarto;
            novoValor = valorBase + (camasExtras * valorCamaExtra);
        }
        
        document.getElementById('valor_diaria').value = novoValor.toFixed(2);
        calcularTotal();
    }
    
    function calcularTotal() {
        var dataEntrada = document.getElementById('data_entrada').value;
        var dataSaida = document.getElementById('data_saida').value;
        var valorDiaria = parseFloat(document.getElementById('valor_diaria').value) || 0;
        
        if (dataEntrada && dataSaida) {
            var entrada = new Date(dataEntrada + 'T00:00:00');
            var saida = new Date(dataSaida + 'T00:00:00');
            var diffMs = saida - entrada;
            var diffDias = Math.floor(diffMs / (1000 * 60 * 60 * 24));
            // Conta todos os dias de ocupação (diferença + 1)
            var numDiarias = Math.max(1, diffDias + 1);
            
            document.getElementById('calc-diarias').textContent = numDiarias;
            document.getElementById('calc-valor').textContent = valorDiaria.toFixed(2).replace('.', ',');
            document.getElementById('calc-total').textContent = (valorDiaria * numDiarias).toFixed(2).replace('.', ',');
        }
    }
    </script>

<?php 
mysqli_close($conexao);
include 'includes/footer.php'; 
?>
</body>
</html>

