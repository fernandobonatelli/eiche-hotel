<?php
/**
 * Pousada Bona - Aplicar Desconto na Hospedagem
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

// Garantir que colunas de desconto existem
$checkCol = mysqli_query($conexao, "SHOW COLUMNS FROM eiche_hospedagem LIKE 'desconto_tipo'");
if (mysqli_num_rows($checkCol) == 0) {
    mysqli_query($conexao, "ALTER TABLE eiche_hospedagem ADD COLUMN desconto_tipo ENUM('nenhum','percentual','valor') DEFAULT 'nenhum'");
    mysqli_query($conexao, "ALTER TABLE eiche_hospedagem ADD COLUMN desconto_valor DECIMAL(10,2) DEFAULT 0");
    mysqli_query($conexao, "ALTER TABLE eiche_hospedagem ADD COLUMN desconto_motivo VARCHAR(255) DEFAULT NULL");
}

$hospId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mensagem = '';
$erro = '';

if (!$hospId) {
    header('Location: reservations.php');
    exit;
}

// Buscar dados da hospedagem
$sql = "SELECT h.*, c.razao as cliente_nome, q.numero as quarto_nome,
               COALESCE(h.desconto_tipo, 'nenhum') as desconto_tipo,
               COALESCE(h.desconto_valor, 0) as desconto_valor,
               COALESCE(h.desconto_motivo, '') as desconto_motivo
        FROM eiche_hospedagem h
        LEFT JOIN eiche_customers c ON h.ID_cliente = c.ID
        LEFT JOIN eiche_hosp_quartos q ON h.ID_quarto = q.ID
        WHERE h.ID = $hospId AND h.idonly = 1";
$result = mysqli_query($conexao, $sql);
$hosp = mysqli_fetch_assoc($result);

if (!$hosp) {
    header('Location: reservations.php');
    exit;
}

// Calcular valores base
$dataInicial = $hosp['data_inicial'] ?? $hosp['data'];
$dataFinal = $hosp['data_final'] ?? $hosp['data'];
$diffDias = (strtotime($dataFinal) - strtotime($dataInicial)) / 86400;
$numDiarias = max(1, (int)$diffDias + 1);
$valorDiaria = (float)($hosp['valor_diaria'] ?? 0);
$totalDiarias = $valorDiaria * $numDiarias;

// Buscar consumos
$sqlConsumos = "SELECT SUM(valor_unit * qtd) as total FROM eiche_hosp_lnk_cons_hosp WHERE ID_hosp = $hospId";
$totalConsumos = mysqli_fetch_assoc(mysqli_query($conexao, $sqlConsumos))['total'] ?? 0;

// Buscar serviços
$sqlServicos = "SELECT SUM(valor_unit * qtd) as total FROM eiche_hosp_lnk_serv_hosp WHERE ID_hosp = $hospId";
$totalServicos = mysqli_fetch_assoc(mysqli_query($conexao, $sqlServicos))['total'] ?? 0;

$subtotal = $totalDiarias + $totalConsumos + $totalServicos;

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descontoTipo = $_POST['desconto_tipo'] ?? 'nenhum';
    $descontoValor = (float)str_replace(['.', ','], ['', '.'], $_POST['desconto_valor'] ?? 0);
    $descontoMotivo = mysqli_real_escape_string($conexao, $_POST['desconto_motivo'] ?? '');
    
    // Validar
    if ($descontoTipo !== 'nenhum' && $descontoValor <= 0) {
        $erro = 'Informe um valor de desconto válido.';
    } elseif ($descontoTipo === 'percentual' && $descontoValor > 100) {
        $erro = 'O percentual de desconto não pode ser maior que 100%.';
    } else {
        // Atualizar todas as linhas desta hospedagem
        $sqlUpdate = "UPDATE eiche_hospedagem 
                      SET desconto_tipo = '$descontoTipo', 
                          desconto_valor = $descontoValor,
                          desconto_motivo = '$descontoMotivo'
                      WHERE ID = $hospId OR (data_inicial = '{$hosp['data_inicial']}' AND ID_cliente = {$hosp['ID_cliente']} AND ID_quarto = {$hosp['ID_quarto']})";
        
        if (mysqli_query($conexao, $sqlUpdate)) {
            $mensagem = 'Desconto aplicado com sucesso!';
            // Recarregar dados
            $hosp['desconto_tipo'] = $descontoTipo;
            $hosp['desconto_valor'] = $descontoValor;
            $hosp['desconto_motivo'] = $descontoMotivo;
        } else {
            $erro = 'Erro ao salvar: ' . mysqli_error($conexao);
        }
    }
}

// Calcular desconto atual
$descontoCalculado = 0;
if ($hosp['desconto_tipo'] === 'percentual') {
    $descontoCalculado = $subtotal * ($hosp['desconto_valor'] / 100);
} elseif ($hosp['desconto_tipo'] === 'valor') {
    $descontoCalculado = $hosp['desconto_valor'];
}
$totalFinal = $subtotal - $descontoCalculado;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desconto - Hospedagem #<?= str_pad($hospId, 6, '0', STR_PAD_LEFT) ?></title>
    <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            min-height: 100vh;
            padding: 30px 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #2d5a3d, #1a5f2a);
            color: white;
            padding: 20px 25px;
        }
        
        .card-header h1 {
            font-size: 18px;
            font-weight: 600;
        }
        
        .card-header p {
            font-size: 13px;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-item label {
            display: block;
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 3px;
        }
        
        .info-item span {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .resumo-valores {
            background: #f9fafb;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .resumo-linha {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
            color: #4b5563;
        }
        
        .resumo-linha.subtotal {
            border-top: 1px solid #e5e7eb;
            padding-top: 12px;
            margin-top: 5px;
            font-weight: 600;
        }
        
        .resumo-linha.desconto {
            color: #dc2626;
        }
        
        .resumo-linha.total {
            border-top: 2px solid #2d5a3d;
            padding-top: 12px;
            margin-top: 5px;
            font-size: 18px;
            font-weight: 700;
            color: #2d5a3d;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .form-input, .form-select {
            width: 100%;
            padding: 12px 14px;
            font-size: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #2d5a3d;
            box-shadow: 0 0 0 3px rgba(45, 90, 61, 0.1);
        }
        
        .desconto-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 24px;
            font-size: 15px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2d5a3d, #1a5f2a);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(45, 90, 61, 0.3);
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .btn-danger {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .btn-danger:hover {
            background: #fecaca;
        }
        
        .actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #6b7280;
            text-decoration: none;
            font-size: 13px;
            margin-bottom: 15px;
        }
        
        .back-link:hover {
            color: #2d5a3d;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="reservations.php" class="back-link">← Voltar para Hospedagens</a>
        
        <div class="card">
            <div class="card-header">
                <h1>💰 Aplicar Desconto</h1>
                <p>Hospedagem #<?= str_pad($hospId, 6, '0', STR_PAD_LEFT) ?> - <?= htmlspecialchars($hosp['cliente_nome'] ?? 'N/A') ?></p>
            </div>
            
            <div class="card-body">
                <?php if ($mensagem): ?>
                <div class="alert alert-success">✅ <?= $mensagem ?></div>
                <?php endif; ?>
                
                <?php if ($erro): ?>
                <div class="alert alert-error">❌ <?= $erro ?></div>
                <?php endif; ?>
                
                <!-- Info da hospedagem -->
                <div class="info-grid">
                    <div class="info-item">
                        <label>Quarto</label>
                        <span><?= htmlspecialchars($hosp['quarto_nome'] ?? '-') ?></span>
                    </div>
                    <div class="info-item">
                        <label>Período</label>
                        <span><?= date('d/m/Y', strtotime($dataInicial)) ?> - <?= date('d/m/Y', strtotime($dataFinal)) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Diárias</label>
                        <span><?= $numDiarias ?> x R$ <?= number_format($valorDiaria, 2, ',', '.') ?></span>
                    </div>
                    <div class="info-item">
                        <label>Status</label>
                        <span><?= $hosp['rstatus'] === 'A' ? '🟢 Ativo' : ($hosp['rstatus'] === 'F' ? '🔵 Finalizado' : '🟡 Reserva') ?></span>
                    </div>
                </div>
                
                <!-- Resumo de valores -->
                <div class="resumo-valores">
                    <div class="resumo-linha">
                        <span>Diárias (<?= $numDiarias ?>x)</span>
                        <span>R$ <?= number_format($totalDiarias, 2, ',', '.') ?></span>
                    </div>
                    <?php if ($totalConsumos > 0): ?>
                    <div class="resumo-linha">
                        <span>Consumos</span>
                        <span>R$ <?= number_format($totalConsumos, 2, ',', '.') ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($totalServicos > 0): ?>
                    <div class="resumo-linha">
                        <span>Serviços</span>
                        <span>R$ <?= number_format($totalServicos, 2, ',', '.') ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="resumo-linha subtotal">
                        <span>Subtotal</span>
                        <span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
                    </div>
                    <?php if ($descontoCalculado > 0): ?>
                    <div class="resumo-linha desconto">
                        <span>Desconto <?= $hosp['desconto_tipo'] === 'percentual' ? '(' . number_format($hosp['desconto_valor'], 0) . '%)' : '' ?></span>
                        <span>- R$ <?= number_format($descontoCalculado, 2, ',', '.') ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="resumo-linha total">
                        <span>TOTAL</span>
                        <span>R$ <?= number_format($totalFinal, 2, ',', '.') ?></span>
                    </div>
                </div>
                
                <!-- Formulário de desconto -->
                <form method="POST">
                    <div class="desconto-row">
                        <div class="form-group">
                            <label class="form-label">Tipo de Desconto</label>
                            <select name="desconto_tipo" id="desconto_tipo" class="form-select" onchange="atualizarTipo()">
                                <option value="nenhum" <?= $hosp['desconto_tipo'] === 'nenhum' ? 'selected' : '' ?>>Sem desconto</option>
                                <option value="percentual" <?= $hosp['desconto_tipo'] === 'percentual' ? 'selected' : '' ?>>Percentual (%)</option>
                                <option value="valor" <?= $hosp['desconto_tipo'] === 'valor' ? 'selected' : '' ?>>Valor fixo (R$)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" id="label-valor">Valor</label>
                            <input type="text" name="desconto_valor" id="desconto_valor" class="form-input" 
                                   value="<?= $hosp['desconto_valor'] > 0 ? number_format($hosp['desconto_valor'], 2, ',', '.') : '' ?>"
                                   placeholder="0,00">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Motivo do Desconto (opcional)</label>
                        <input type="text" name="desconto_motivo" class="form-input" 
                               value="<?= htmlspecialchars($hosp['desconto_motivo']) ?>"
                               placeholder="Ex: Cliente frequente, promoção, cortesia...">
                    </div>
                    
                    <div class="actions">
                        <button type="submit" class="btn btn-primary">
                            💾 Salvar Desconto
                        </button>
                        <a href="hospedagem-detalhes.php?id=<?= $hospId ?>" class="btn btn-secondary">
                            📋 Ver Recibo
                        </a>
                        <?php if ($hosp['desconto_tipo'] !== 'nenhum'): ?>
                        <button type="submit" name="desconto_tipo" value="nenhum" class="btn btn-danger">
                            🗑️ Remover Desconto
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function atualizarTipo() {
            var tipo = document.getElementById('desconto_tipo').value;
            var label = document.getElementById('label-valor');
            var input = document.getElementById('desconto_valor');
            
            if (tipo === 'percentual') {
                label.textContent = 'Percentual (%)';
                input.placeholder = 'Ex: 10';
            } else if (tipo === 'valor') {
                label.textContent = 'Valor (R$)';
                input.placeholder = 'Ex: 50,00';
            } else {
                label.textContent = 'Valor';
                input.placeholder = '0,00';
                input.value = '';
            }
        }
        
        atualizarTipo();
    </script>
</body>
</html>
<?php mysqli_close($conexao); ?>

