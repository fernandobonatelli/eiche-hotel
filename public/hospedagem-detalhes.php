<?php
/**
 * Pousada Bona - Detalhes da Hospedagem / Recibo
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

// Incluir sistema de permissões
include_once 'includes/permissoes.php';
$podeVerValores = podeVerValores($conexao);

$hospId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$hospId) {
    header('Location: reservations.php');
    exit;
}

// Buscar dados da hospedagem
$sql = "SELECT h.*, c.razao as cliente_nome, c.cpf, c.cnpj, c.fone1, c.email1, 
               c.e_rua, c.e_numero, c.e_bairro, c.e_cidade, c.e_estado, c.e_cep,
               q.numero as quarto_numero, q.ocupantes as quarto_ocupantes
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

// Calcular diárias pela diferença de datas (entrada até saída = dias de ocupação)
$totalDiarias = 1;
if ($dataEntrada && $dataSaida) {
    $diff = (strtotime($dataSaida) - strtotime($dataEntrada)) / 86400;
    // Conta todos os dias de ocupação (diferença + 1)
    $totalDiarias = max(1, (int)$diff + 1);
}

if (!$hospedagem) {
    header('Location: reservations.php?erro=Hospedagem não encontrada');
    exit;
}

// Buscar consumos
$consumos = [];
$sqlConsumos = "SELECT c.*, p.description as produto_nome 
                FROM eiche_hosp_lnk_cons_hosp c
                LEFT JOIN eiche_prodorserv p ON c.ID_cons = p.ID
                WHERE c.ID_hosp = $hospId";
$resultConsumos = mysqli_query($conexao, $sqlConsumos);
if ($resultConsumos) {
    while ($row = mysqli_fetch_assoc($resultConsumos)) {
        $consumos[] = $row;
    }
}

// Buscar serviços
$servicos = [];
$sqlServicos = "SELECT s.*, p.description as servico_nome 
                FROM eiche_hosp_lnk_serv_hosp s
                LEFT JOIN eiche_prodorserv p ON s.ID_serv = p.ID
                WHERE s.ID_hosp = $hospId";
$resultServicos = mysqli_query($conexao, $sqlServicos);
if ($resultServicos) {
    while ($row = mysqli_fetch_assoc($resultServicos)) {
        $servicos[] = $row;
    }
}

// Calcular totais
$valorDiaria = (float)($hospedagem['valor_diaria'] ?? 0);
$totalDiariasValor = $valorDiaria * $totalDiarias;

$totalConsumos = 0;
foreach ($consumos as $c) {
    $totalConsumos += ($c['valor_unit'] ?? 0) * ($c['qtd'] ?? 1);
}

$totalServicos = 0;
foreach ($servicos as $s) {
    $totalServicos += ($s['valor_unit'] ?? 0) * ($s['qtd'] ?? 1);
}

$subtotal = $totalDiariasValor + $totalConsumos + $totalServicos;

// Buscar desconto (verificar se colunas existem)
$descontoTipo = 'nenhum';
$descontoValor = 0;
$descontoMotivo = '';
$descontoCalculado = 0;

$checkCol = mysqli_query($conexao, "SHOW COLUMNS FROM eiche_hospedagem LIKE 'desconto_tipo'");
if (mysqli_num_rows($checkCol) > 0) {
    $sqlDesconto = "SELECT desconto_tipo, desconto_valor, desconto_motivo FROM eiche_hospedagem WHERE ID = $hospId";
    $resultDesconto = mysqli_query($conexao, $sqlDesconto);
    if ($rowDesconto = mysqli_fetch_assoc($resultDesconto)) {
        $descontoTipo = $rowDesconto['desconto_tipo'] ?? 'nenhum';
        $descontoValor = (float)($rowDesconto['desconto_valor'] ?? 0);
        $descontoMotivo = $rowDesconto['desconto_motivo'] ?? '';
        
        if ($descontoTipo === 'percentual' && $descontoValor > 0) {
            $descontoCalculado = $subtotal * ($descontoValor / 100);
        } elseif ($descontoTipo === 'valor' && $descontoValor > 0) {
            $descontoCalculado = $descontoValor;
        }
    }
}

$totalGeral = $subtotal - $descontoCalculado;

// Status texto
$statusTexto = 'Desconhecido';
$statusCor = '#666';
switch ($hospedagem['rstatus']) {
    case 'A': $statusTexto = 'Ativo'; $statusCor = '#3b82f6'; break;
    case 'R': $statusTexto = 'Reserva'; $statusCor = '#f59e0b'; break;
    case 'F': $statusTexto = 'Fechado'; $statusCor = '#22c55e'; break;
}

// Documento do cliente
$docCliente = '';
if (!empty($hospedagem['cpf']) && $hospedagem['cpf'] != '000.000.000-00') {
    $docCliente = $hospedagem['cpf'];
} elseif (!empty($hospedagem['cnpj']) && $hospedagem['cnpj'] != '000.000.000/0000-00') {
    $docCliente = $hospedagem['cnpj'];
}

$pageTitle = 'Detalhes da Hospedagem - Pousada Bona';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">
    <link rel="stylesheet" href="assets/css/app.css">
    <style>
        .detail-page { max-width: 800px; margin: 0 auto; }
        
        .back-link { display: inline-flex; align-items: center; gap: 5px; color: #666; text-decoration: none; font-size: 13px; margin-bottom: 15px; }
        .back-link:hover { color: #333; }
        
        .recibo { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; }
        
        .recibo-header { text-align: center; padding-bottom: 15px; border-bottom: 2px solid #333; margin-bottom: 15px; }
        .recibo-header img { width: 60px; height: 60px; border-radius: 8px; object-fit: cover; }
        .recibo-header h1 { font-size: 18px; margin: 8px 0 3px 0; }
        .recibo-header p { margin: 0; font-size: 11px; color: #666; }
        .recibo-numero { font-size: 13px; font-weight: 600; margin-top: 8px; }
        .recibo-numero .status { margin-left: 10px; padding: 3px 10px; border-radius: 10px; font-size: 10px; color: white; }
        
        .recibo-section { margin-bottom: 12px; }
        .recibo-section-title { font-size: 11px; font-weight: 600; color: #333; margin-bottom: 6px; padding-bottom: 4px; border-bottom: 1px solid #eee; }
        
        .info-row { display: flex; flex-wrap: wrap; gap: 8px 20px; font-size: 11px; }
        .info-row .item { }
        .info-row .item label { color: #888; font-size: 9px; text-transform: uppercase; display: block; }
        .info-row .item span { font-weight: 500; }
        
        table.items { width: 100%; border-collapse: collapse; font-size: 10px; margin-top: 5px; }
        table.items th, table.items td { padding: 4px 6px; text-align: left; border-bottom: 1px solid #eee; }
        table.items th { background: #f9fafb; font-weight: 600; font-size: 9px; text-transform: uppercase; color: #666; }
        table.items .text-right { text-align: right; }
        
        .resumo-box { background: #f0fdf4; padding: 10px; border-radius: 6px; }
        .resumo-line { display: flex; justify-content: space-between; font-size: 11px; padding: 2px 0; }
        .resumo-line.subtotal { border-top: 1px dashed #ccc; padding-top: 5px; margin-top: 3px; }
        .resumo-line.desconto { color: #dc2626; font-weight: 600; }
        .resumo-line.total { font-size: 14px; font-weight: 700; color: #16a34a; border-top: 1px solid #22c55e; padding-top: 6px; margin-top: 4px; }
        
        .pagamento-box { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 8px; }
        .pagamento-item { text-align: center; padding: 10px; border-radius: 6px; font-size: 10px; }
        .pagamento-item.pix { background: #f0fdf4; border: 1px solid #86efac; }
        .pagamento-item.banco { background: #eff6ff; border: 1px solid #93c5fd; }
        .pagamento-item h4 { margin: 0 0 5px 0; font-size: 11px; }
        .pagamento-item p { margin: 2px 0; }
        
        .rodape { text-align: center; font-size: 9px; color: #888; margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee; }
        
        .action-bar { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }
        
        .btn { padding: 10px 18px; border-radius: 6px; font-size: 13px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; border: 1px solid #ddd; background: white; color: #333; }
        .btn:hover { background: #f5f5f5; }
        .btn.primary { background: #3b82f6; color: white; border-color: #3b82f6; }
        .btn.primary:hover { background: #2563eb; }
        .btn.success { background: #22c55e; color: white; border-color: #22c55e; }
        .btn.success:hover { background: #16a34a; }
        .btn.danger { background: #ef4444; color: white; border-color: #ef4444; }
        .btn.danger:hover { background: #dc2626; }
        
        /* Print - Uma folha */
        @media print {
            @page { size: A4; margin: 10mm; }
            
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            
            html, body { 
                background: white !important; 
                font-size: 10px; 
                margin: 0 !important; 
                padding: 0 !important;
                width: 100% !important;
            }
            
            /* Esconder elementos de navegação */
            .back-link, .action-bar, .sidebar, .topbar, .sidebar-overlay, .app-layout > aside { 
                display: none !important; 
                visibility: hidden !important;
            }
            
            /* Reset layout para impressão */
            .app-layout, .main-wrapper, .main-content, .detail-page {
                display: block !important;
                position: static !important;
                margin: 0 !important;
                padding: 0 !important;
                margin-left: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            
            /* Recibo visível */
            .recibo { 
                display: block !important;
                visibility: visible !important;
                border: none !important; 
                box-shadow: none !important; 
                padding: 0 !important;
                width: 100% !important;
            }
            
            .recibo-header { padding-bottom: 10px; margin-bottom: 10px; }
            .recibo-header img { width: 50px; height: 50px; }
            .recibo-header h1 { font-size: 16px; }
            .recibo-section { margin-bottom: 8px; }
            .pagamento-box { gap: 8px; }
            .resumo-box { background: #f0fdf4 !important; }
            .pagamento-item.pix { background: #f0fdf4 !important; }
            .pagamento-item.banco { background: #eff6ff !important; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-wrapper">
        <?php include 'includes/topbar.php'; ?>
        
        <main class="main-content">
            <div class="detail-page">
                <a href="reservations.php" class="back-link">← Voltar ao Painel</a>
                
                <div class="recibo" id="recibo">
                    <!-- Cabeçalho -->
                    <div class="recibo-header">
                        <img src="assets/images/logo.jpg" alt="Pousada Bona">
                        <h1>POUSADA BONA</h1>
                        <p>Comprovante de Hospedagem</p>
                        <div class="recibo-numero">
                            Nº <?php echo str_pad($hospId, 6, '0', STR_PAD_LEFT); ?>
                            <span class="status" style="background: <?php echo $statusCor; ?>"><?php echo $statusTexto; ?></span>
                        </div>
                    </div>
                    
                    <!-- Dados do Hóspede -->
                    <div class="recibo-section">
                        <div class="recibo-section-title">👤 HÓSPEDE</div>
                        <div class="info-row">
                            <div class="item"><label>Nome</label><span><?php echo htmlspecialchars($hospedagem['cliente_nome'] ?? '-'); ?></span></div>
                            <div class="item"><label>CPF/CNPJ</label><span><?php echo htmlspecialchars($docCliente ?: '-'); ?></span></div>
                            <div class="item"><label>Telefone</label><span><?php echo htmlspecialchars($hospedagem['fone1'] ?? '-'); ?></span></div>
                        </div>
                    </div>
                    
                    <!-- Dados da Hospedagem -->
                    <div class="recibo-section">
                        <div class="recibo-section-title">🛏️ HOSPEDAGEM</div>
                        <div class="info-row">
                            <div class="item"><label>Quarto</label><span><?php echo htmlspecialchars($hospedagem['quarto_numero'] ?? '-'); ?></span></div>
                            <div class="item"><label>Check-in</label><span><?php echo date('d/m/Y', strtotime($dataEntrada)); ?></span></div>
                            <div class="item"><label>Check-out</label><span><?php echo date('d/m/Y', strtotime($dataSaida)); ?></span></div>
                            <div class="item"><label>Diárias</label><span><?php echo $totalDiarias; ?></span></div>
                            <div class="item"><label>Valor/Diária</label><span>R$ <?php echo number_format($valorDiaria, 2, ',', '.'); ?></span></div>
                        </div>
                    </div>
                    
                    <?php if (!empty($consumos)): ?>
                    <!-- Consumos - só aparece se tiver -->
                    <div class="recibo-section">
                        <div class="recibo-section-title">🍽️ CONSUMOS</div>
                        <table class="items">
                            <thead>
                                <tr><th>Produto</th><th class="text-right">Qtd</th><th class="text-right">Unit.</th><th class="text-right">Total</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($consumos as $c): 
                                    $subtotal = ($c['valor_unit'] ?? 0) * ($c['qtd'] ?? 1);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($c['produto_nome'] ?? 'Produto'); ?></td>
                                    <td class="text-right"><?php echo $c['qtd'] ?? 1; ?></td>
                                    <td class="text-right">R$ <?php echo number_format($c['valor_unit'] ?? 0, 2, ',', '.'); ?></td>
                                    <td class="text-right">R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($servicos)): ?>
                    <!-- Serviços - só aparece se tiver -->
                    <div class="recibo-section">
                        <div class="recibo-section-title">🛎️ SERVIÇOS</div>
                        <table class="items">
                            <thead>
                                <tr><th>Serviço</th><th class="text-right">Qtd</th><th class="text-right">Unit.</th><th class="text-right">Total</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($servicos as $s): 
                                    $subtotal = ($s['valor_unit'] ?? 0) * ($s['qtd'] ?? 1);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($s['servico_nome'] ?? 'Serviço'); ?></td>
                                    <td class="text-right"><?php echo $s['qtd'] ?? 1; ?></td>
                                    <td class="text-right">R$ <?php echo number_format($s['valor_unit'] ?? 0, 2, ',', '.'); ?></td>
                                    <td class="text-right">R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Resumo Financeiro -->
                    <div class="recibo-section">
                        <div class="recibo-section-title">💰 RESUMO</div>
                        <div class="resumo-box">
                            <div class="resumo-line"><span>Diárias (<?php echo $totalDiarias; ?> x R$ <?php echo number_format($valorDiaria, 2, ',', '.'); ?>)</span><span>R$ <?php echo number_format($totalDiariasValor, 2, ',', '.'); ?></span></div>
                            <?php if ($totalConsumos > 0): ?>
                            <div class="resumo-line"><span>Consumos</span><span>R$ <?php echo number_format($totalConsumos, 2, ',', '.'); ?></span></div>
                            <?php endif; ?>
                            <?php if ($totalServicos > 0): ?>
                            <div class="resumo-line"><span>Serviços</span><span>R$ <?php echo number_format($totalServicos, 2, ',', '.'); ?></span></div>
                            <?php endif; ?>
                            <?php if ($descontoCalculado > 0): ?>
                            <div class="resumo-line subtotal"><span>Subtotal</span><span>R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></span></div>
                            <div class="resumo-line desconto"><span>Desconto <?php echo $descontoTipo === 'percentual' ? '(' . number_format($descontoValor, 0) . '%)' : ''; ?> <?php echo $descontoMotivo ? '- ' . htmlspecialchars($descontoMotivo) : ''; ?></span><span style="color: #dc2626;">- R$ <?php echo number_format($descontoCalculado, 2, ',', '.'); ?></span></div>
                            <?php endif; ?>
                            <div class="resumo-line total"><span>TOTAL</span><span>R$ <?php echo number_format($totalGeral, 2, ',', '.'); ?></span></div>
                        </div>
                    </div>
                    
                    <!-- Dados para Pagamento -->
                    <div class="recibo-section">
                        <div class="recibo-section-title">💳 PAGAMENTO</div>
                        <div class="pagamento-box">
                            <div class="pagamento-item pix">
                                <h4 style="color: #16a34a;">📱 Chave PIX</h4>
                                <p>Telefone: <strong>(48) 99982-9292</strong></p>
                                <p><strong>LUIZ FERNANDO BONATELLI</strong></p>
                            </div>
                            <div class="pagamento-item banco">
                                <h4 style="color: #2563eb;">🏦 Dados Bancários</h4>
                                <p><strong>LUIZ FERNANDO BONATELLI</strong></p>
                                <p>CPF: 096.403.209-00</p>
                                <p>BB (001) | Ag: 4772-4 | Cc: 13047-8</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="rodape">
                        Documento emitido em <?php echo date('d/m/Y H:i'); ?> | Pousada Bona - Sistema de Gestão
                    </div>
                </div>
                
                <div class="action-bar">
                    <button onclick="window.print()" class="btn">🖨️ Imprimir Recibo</button>
                    <a href="editar-hospedagem.php?id=<?php echo $hospId; ?>" class="btn">✏️ Editar</a>
                    
                    <?php if ($hospedagem['rstatus'] === 'A'): ?>
                        <a href="consumo.php?hosp_id=<?php echo $hospId; ?>" class="btn primary">+ Consumo</a>
                        <a href="servico.php?hosp_id=<?php echo $hospId; ?>" class="btn primary">+ Serviço</a>
                        <a href="acao-hospedagem.php?acao=checkout&id=<?php echo $hospId; ?>" class="btn success" onclick="return confirm('Confirma o CHECK-OUT?')">✅ Check-out</a>
                    <?php elseif ($hospedagem['rstatus'] === 'R'): ?>
                        <a href="acao-hospedagem.php?acao=checkin&id=<?php echo $hospId; ?>" class="btn success" onclick="return confirm('Confirma o CHECK-IN?')">✅ Check-in</a>
                        <a href="acao-hospedagem.php?acao=excluir&id=<?php echo $hospId; ?>" class="btn danger" onclick="return confirm('Cancelar esta reserva?')">🗑️ Cancelar</a>
                    <?php elseif ($hospedagem['rstatus'] === 'F'): ?>
                        <a href="acao-hospedagem.php?acao=reabrir&id=<?php echo $hospId; ?>" class="btn" onclick="return confirm('Reabrir esta hospedagem?')">🔄 Reabrir</a>
                        <a href="acao-hospedagem.php?acao=excluir&id=<?php echo $hospId; ?>" class="btn danger" onclick="return confirm('EXCLUIR permanentemente?')">🗑️ Excluir</a>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

<?php 
mysqli_close($conexao);
include 'includes/footer.php'; 
?>
</body>
</html>
