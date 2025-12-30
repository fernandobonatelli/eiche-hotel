<?php
/**
 * Pousada Bona - Financeiro
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

// Incluir sistema de permissões
include_once 'includes/permissoes.php';
$podeVerValores = podeVerValores($conexao);

// Se não pode ver valores, mostrar mensagem
if (!$podeVerValores) {
    $tituloPermissao = 'Acesso Restrito';
    $mensagemPermissao = 'Você não tem permissão para acessar dados financeiros. Contate o administrador para obter acesso.';
    include 'includes/sem-permissao.php';
}

// Filtros de data
$dataInicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-01');
$dataFim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

// ===== ESTATÍSTICAS DO PERÍODO (TODAS BASEADAS NO FILTRO) =====

// Hospedagens finalizadas no período (checkout realizado no período)
$sqlFinalizadas = "SELECT h.ID, MIN(h.data) as data_entrada, MAX(h.data) as data_saida, 
                          h.valor_diaria, h.lg_checkout, COUNT(*) as num_diarias,
                          c.razao as cliente_nome, q.numero as quarto_numero
                   FROM eiche_hospedagem h 
                   LEFT JOIN eiche_customers c ON h.ID_cliente = c.ID 
                   LEFT JOIN eiche_hosp_quartos q ON h.ID_quarto = q.ID 
                   WHERE h.rstatus = 'F' 
                   AND DATE(h.lg_checkout) >= '$dataInicio' 
                   AND DATE(h.lg_checkout) <= '$dataFim'
                   GROUP BY h.ID
                   ORDER BY h.lg_checkout DESC";
$result = mysqli_query($conexao, $sqlFinalizadas);
$finalizadasList = [];
$receitaFinalizadas = 0;
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $row['valor_total'] = ($row['valor_diaria'] ?? 0) * ($row['num_diarias'] ?? 1);
        $receitaFinalizadas += $row['valor_total'];
        $finalizadasList[] = $row;
    }
}
$hospFinalizadas = count($finalizadasList);

// Hospedagens ativas no período
$sqlAtivas = "SELECT h.ID, MIN(h.data) as data_entrada, MAX(h.data) as data_saida, 
                     h.valor_diaria, COUNT(*) as num_diarias,
                     c.razao as cliente_nome, q.numero as quarto_numero
              FROM eiche_hospedagem h 
              LEFT JOIN eiche_customers c ON h.ID_cliente = c.ID 
              LEFT JOIN eiche_hosp_quartos q ON h.ID_quarto = q.ID 
              WHERE h.rstatus = 'A' 
              AND h.data >= '$dataInicio' AND h.data <= '$dataFim'
              GROUP BY h.ID
              ORDER BY MIN(h.data) ASC";
$result = mysqli_query($conexao, $sqlAtivas);
$ativasList = [];
$receitaAtivas = 0;
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $row['valor_total'] = ($row['valor_diaria'] ?? 0) * ($row['num_diarias'] ?? 1);
        $receitaAtivas += $row['valor_total'];
        $ativasList[] = $row;
    }
}
$hospAtivas = count($ativasList);

// Reservas no período
$sqlReservas = "SELECT COUNT(DISTINCT ID) as total FROM eiche_hospedagem 
                WHERE rstatus = 'R' AND data >= '$dataInicio' AND data <= '$dataFim'";
$result = mysqli_query($conexao, $sqlReservas);
$hospReservas = mysqli_fetch_assoc($result)['total'] ?? 0;

// Total de consumos no período (sem duplicação)
// Usa subquery para pegar dados da hospedagem principal (idonly=1) baseado no ID_hosp
$sqlTotalConsumos = "SELECT SUM(c.valor_unit * c.qtd) as total, COUNT(DISTINCT c.id) as qtd
                     FROM eiche_hosp_lnk_cons_hosp c
                     WHERE c.data >= '$dataInicio' AND c.data <= '$dataFim'";
$result = mysqli_query($conexao, $sqlTotalConsumos);
$row = mysqli_fetch_assoc($result);
$totalConsumos = (float)($row['total'] ?? 0);
$qtdConsumos = (int)($row['qtd'] ?? 0);

// Lista de consumos (últimos 100 para exibição) - SEM duplicação
$sqlConsumos = "SELECT c.id, c.ID_hosp, c.ID_cons, c.qtd, c.valor_unit, c.descricao,
                       (c.valor_unit * c.qtd) as valor_total,
                       p.description as produto, 
                       c.data as data_cons,
                       (SELECT cu.razao FROM eiche_hospedagem hx 
                        LEFT JOIN eiche_customers cu ON hx.ID_cliente = cu.ID 
                        WHERE hx.ID = c.ID_hosp LIMIT 1) as cliente,
                       (SELECT q.numero FROM eiche_hospedagem hx 
                        LEFT JOIN eiche_hosp_quartos q ON hx.ID_quarto = q.ID 
                        WHERE hx.ID = c.ID_hosp LIMIT 1) as quarto
                FROM eiche_hosp_lnk_cons_hosp c
                LEFT JOIN eiche_prodorserv p ON c.ID_cons = p.ID
                WHERE c.data >= '$dataInicio' AND c.data <= '$dataFim'
                ORDER BY c.data DESC
                LIMIT 100";
$result = mysqli_query($conexao, $sqlConsumos);
$consumosList = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $consumosList[] = $row;
    }
}

// Top consumos - usando ID_cons (sem duplicação)
$sqlTopConsumos = "SELECT COALESCE(p.description, c.descricao, 'Produto') as description, 
                          SUM(c.qtd) as qtd_total, SUM(c.valor_unit * c.qtd) as valor_total
                   FROM eiche_hosp_lnk_cons_hosp c
                   LEFT JOIN eiche_prodorserv p ON c.ID_cons = p.ID
                   WHERE c.data >= '$dataInicio' AND c.data <= '$dataFim'
                   GROUP BY c.ID_cons
                   ORDER BY valor_total DESC
                   LIMIT 10";
$result = mysqli_query($conexao, $sqlTopConsumos);
$topConsumos = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $topConsumos[] = $row;
    }
}

// Total de serviços no período (sem duplicação)
$sqlTotalServicos = "SELECT SUM(s.valor_unit * s.qtd) as total, COUNT(DISTINCT s.id) as qtd
                     FROM eiche_hosp_lnk_serv_hosp s
                     WHERE s.data >= '$dataInicio' AND s.data <= '$dataFim'";
$result = mysqli_query($conexao, $sqlTotalServicos);
$row = mysqli_fetch_assoc($result);
$totalServicos = (float)($row['total'] ?? 0);
$qtdServicos = (int)($row['qtd'] ?? 0);

// Lista de serviços (últimos 100 para exibição) - SEM duplicação
$sqlServicos = "SELECT s.id, s.ID_hosp, s.ID_serv, s.qtd, s.valor_unit, 
                       (s.valor_unit * s.qtd) as valor_total,
                       p.description as servico, 
                       s.data as data_serv,
                       (SELECT cu.razao FROM eiche_hospedagem hx 
                        LEFT JOIN eiche_customers cu ON hx.ID_cliente = cu.ID 
                        WHERE hx.ID = s.ID_hosp LIMIT 1) as cliente,
                       (SELECT q.numero FROM eiche_hospedagem hx 
                        LEFT JOIN eiche_hosp_quartos q ON hx.ID_quarto = q.ID 
                        WHERE hx.ID = s.ID_hosp LIMIT 1) as quarto
                FROM eiche_hosp_lnk_serv_hosp s
                LEFT JOIN eiche_prodorserv p ON s.ID_serv = p.ID
                WHERE s.data >= '$dataInicio' AND s.data <= '$dataFim'
                ORDER BY s.data DESC
                LIMIT 100";
$result = mysqli_query($conexao, $sqlServicos);
$servicosList = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $servicosList[] = $row;
    }
}

// Top serviços - usando ID_serv (sem duplicação)
$sqlTopServicos = "SELECT COALESCE(p.description, 'Serviço') as description, 
                          SUM(s.qtd) as qtd_total, SUM(s.valor_unit * s.qtd) as valor_total
                   FROM eiche_hosp_lnk_serv_hosp s
                   LEFT JOIN eiche_prodorserv p ON s.ID_serv = p.ID
                   WHERE s.data >= '$dataInicio' AND s.data <= '$dataFim'
                   GROUP BY s.ID_serv
                   ORDER BY valor_total DESC
                   LIMIT 10";
$result = mysqli_query($conexao, $sqlTopServicos);
$topServicos = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $topServicos[] = $row;
    }
}

// Calcular descontos do período
$totalDescontos = 0;
$checkDescontoCol = mysqli_query($conexao, "SHOW COLUMNS FROM eiche_hospedagem LIKE 'desconto_tipo'");
if (mysqli_num_rows($checkDescontoCol) > 0) {
    // Buscar hospedagens com desconto no período
    $sqlDescontos = "SELECT h.ID, h.desconto_tipo, h.desconto_valor, h.data_inicial, h.data_final, h.valor_diaria
                     FROM eiche_hospedagem h 
                     WHERE h.idonly = 1
                     AND h.desconto_tipo != 'nenhum' AND h.desconto_valor > 0
                     AND ((h.rstatus = 'F' AND DATE(h.lg_checkout) >= '$dataInicio' AND DATE(h.lg_checkout) <= '$dataFim')
                          OR (h.rstatus = 'A' AND h.data >= '$dataInicio' AND h.data <= '$dataFim'))";
    $resultDesc = mysqli_query($conexao, $sqlDescontos);
    while ($rowDesc = mysqli_fetch_assoc($resultDesc)) {
        // Calcular subtotal desta hospedagem
        $hospIdDesc = $rowDesc['ID'];
        $di = $rowDesc['data_inicial'] ?? $rowDesc['data'];
        $df = $rowDesc['data_final'] ?? $rowDesc['data'];
        $numDiarias = max(1, (int)((strtotime($df) - strtotime($di)) / 86400) + 1);
        $subtotalHosp = $rowDesc['valor_diaria'] * $numDiarias;
        
        // Adicionar consumos e serviços desta hospedagem
        $sqlConsHosp = "SELECT SUM(valor_unit * qtd) as total FROM eiche_hosp_lnk_cons_hosp WHERE ID_hosp = $hospIdDesc";
        $subtotalHosp += mysqli_fetch_assoc(mysqli_query($conexao, $sqlConsHosp))['total'] ?? 0;
        $sqlServHosp = "SELECT SUM(valor_unit * qtd) as total FROM eiche_hosp_lnk_serv_hosp WHERE ID_hosp = $hospIdDesc";
        $subtotalHosp += mysqli_fetch_assoc(mysqli_query($conexao, $sqlServHosp))['total'] ?? 0;
        
        // Calcular desconto
        if ($rowDesc['desconto_tipo'] === 'percentual') {
            $totalDescontos += $subtotalHosp * ($rowDesc['desconto_valor'] / 100);
        } else {
            $totalDescontos += $rowDesc['desconto_valor'];
        }
    }
}

// Receita total do período (finalizadas + ativas + consumos + serviços - descontos)
$receitaDiarias = $receitaFinalizadas + $receitaAtivas;
$receitaTotal = $receitaDiarias + $totalConsumos + $totalServicos - $totalDescontos;

// Despesas do período (verificar se tabela existe)
$totalDespesas = 0;
$qtdDespesas = 0;
$despesasList = [];
$checkTable = mysqli_query($conexao, "SHOW TABLES LIKE 'eiche_despesas'");
if (mysqli_num_rows($checkTable) > 0) {
    $sqlTotalDesp = "SELECT SUM(valor) as total, COUNT(*) as qtd FROM eiche_despesas 
                     WHERE data >= '$dataInicio' AND data <= '$dataFim'";
    $result = mysqli_query($conexao, $sqlTotalDesp);
    $row = mysqli_fetch_assoc($result);
    $totalDespesas = (float)($row['total'] ?? 0);
    $qtdDespesas = (int)($row['qtd'] ?? 0);
    
    // Lista de despesas
    $sqlDesp = "SELECT * FROM eiche_despesas 
                WHERE data >= '$dataInicio' AND data <= '$dataFim'
                ORDER BY data DESC LIMIT 50";
    $result = mysqli_query($conexao, $sqlDesp);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $despesasList[] = $row;
        }
    }
}

// Lucro do período (receita - despesas)
$lucroTotal = $receitaTotal - $totalDespesas;

// Ticket médio
$totalHosp = $hospFinalizadas + $hospAtivas;
$ticketMedio = $totalHosp > 0 ? $receitaTotal / $totalHosp : 0;

function formatDate($date) {
    if (empty($date) || $date == '0000-00-00') return '-';
    return date('d/m/Y', strtotime($date));
}

$meses = [1=>'Jan',2=>'Fev',3=>'Mar',4=>'Abr',5=>'Mai',6=>'Jun',
          7=>'Jul',8=>'Ago',9=>'Set',10=>'Out',11=>'Nov',12=>'Dez'];

$pageTitle = 'Financeiro - Pousada Bona';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>
    
    <main class="main-content">
        <div class="content-header">
            <div class="content-header-left">
                <h1>💰 Financeiro</h1>
                <p>Período: <?= formatDate($dataInicio) ?> até <?= formatDate($dataFim) ?></p>
            </div>
            <div class="content-header-actions">
                <button onclick="toggleValores()" class="btn btn-secondary" id="btn-toggle">👁️ Mostrar Valores</button>
            </div>
        </div>
        
        <!-- Filtro -->
        <div class="card" style="margin-bottom: 15px;">
            <div class="card-body" style="padding: 12px;">
                <form method="GET" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <input type="date" name="data_inicio" value="<?= $dataInicio ?>" style="padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                    <span>até</span>
                    <input type="date" name="data_fim" value="<?= $dataFim ?>" style="padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                    <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
                    <a href="finance.php" class="btn btn-ghost">Limpar</a>
                    <div style="margin-left: auto; display: flex; gap: 5px;">
                        <a href="?data_inicio=<?= date('Y-m-01') ?>&data_fim=<?= date('Y-m-d') ?>" class="btn btn-ghost btn-sm">Mês</a>
                        <a href="?data_inicio=<?= date('Y-01-01') ?>&data_fim=<?= date('Y-m-d') ?>" class="btn btn-ghost btn-sm">Ano</a>
                        <a href="?data_inicio=<?= date('Y-m-d', strtotime('-30 days')) ?>&data_fim=<?= date('Y-m-d') ?>" class="btn btn-ghost btn-sm">30d</a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Resumo -->
        <div style="background: linear-gradient(135deg, #1e3a5f, #0f172a); border-radius: 10px; padding: 15px; margin-bottom: 15px; color: white;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div style="flex: 1; min-width: 80px;">
                    <div style="font-size: 9px; opacity: 0.7; text-transform: uppercase;">Receita</div>
                    <div style="font-size: 16px; font-weight: 700;">
                        <span class="v-oculto">••••</span>
                        <span class="v-real" style="display:none;">R$ <?= number_format($receitaTotal, 0, ',', '.') ?></span>
                    </div>
                </div>
                <div style="flex: 1; min-width: 70px;">
                    <div style="font-size: 9px; opacity: 0.7;">Diárias</div>
                    <div style="font-size: 12px; font-weight: 600;">
                        <span class="v-oculto">••••</span>
                        <span class="v-real" style="display:none;">R$ <?= number_format($receitaDiarias, 0, ',', '.') ?></span>
                    </div>
                </div>
                <div style="flex: 1; min-width: 70px;">
                    <div style="font-size: 9px; opacity: 0.7;">Serviços</div>
                    <div style="font-size: 12px; font-weight: 600;">
                        <span class="v-oculto">••••</span>
                        <span class="v-real" style="display:none;">R$ <?= number_format($totalConsumos, 0, ',', '.') ?></span>
                    </div>
                </div>
                <div style="flex: 1; min-width: 70px;">
                    <div style="font-size: 9px; opacity: 0.7;">Produtos</div>
                    <div style="font-size: 12px; font-weight: 600;">
                        <span class="v-oculto">••••</span>
                        <span class="v-real" style="display:none;">R$ <?= number_format($totalServicos, 0, ',', '.') ?></span>
                    </div>
                </div>
                <?php if ($totalDescontos > 0): ?>
                <div style="flex: 1; min-width: 70px; color: #fb923c;">
                    <div style="font-size: 9px; opacity: 0.8;">Descontos</div>
                    <div style="font-size: 12px; font-weight: 600;">
                        <span class="v-oculto">••••</span>
                        <span class="v-real" style="display:none;">-R$ <?= number_format($totalDescontos, 0, ',', '.') ?></span>
                    </div>
                </div>
                <?php endif; ?>
                <div style="flex: 1; min-width: 80px; color: #fca5a5;">
                    <div style="font-size: 9px; opacity: 0.8;">Despesas</div>
                    <div style="font-size: 12px; font-weight: 600;">
                        <span class="v-oculto">••••</span>
                        <span class="v-real" style="display:none;">R$ <?= number_format($totalDespesas, 0, ',', '.') ?></span>
                    </div>
                </div>
                <div style="flex: 1; min-width: 90px; background: <?= $lucroTotal >= 0 ? 'rgba(34,197,94,0.3)' : 'rgba(239,68,68,0.3)' ?>; padding: 8px; border-radius: 6px;">
                    <div style="font-size: 9px; opacity: 0.8; text-transform: uppercase;">Lucro</div>
                    <div style="font-size: 16px; font-weight: 700;">
                        <span class="v-oculto">••••</span>
                        <span class="v-real" style="display:none;">R$ <?= number_format($lucroTotal, 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Cards Clicáveis -->
        <div class="dashboard-grid" style="margin-bottom: 20px;">
            <div class="stat-card clickable" onclick="abrirModal('finalizadas')">
                <div class="stat-icon green">✅</div>
                <div class="stat-content">
                    <div class="stat-label">Finalizadas 👆</div>
                    <div class="stat-value"><?= $hospFinalizadas ?></div>
                    <div class="stat-change">
                        <span class="v-oculto">••••</span>
                        <span class="v-real" style="display:none;">R$ <?= number_format($receitaFinalizadas, 2, ',', '.') ?></span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card clickable" onclick="abrirModal('ativas')">
                <div class="stat-icon blue">🔵</div>
                <div class="stat-content">
                    <div class="stat-label">Ativas 👆</div>
                    <div class="stat-value"><?= $hospAtivas ?></div>
                    <div class="stat-change">
                        <span class="v-oculto">••••</span>
                        <span class="v-real" style="display:none;">R$ <?= number_format($receitaAtivas, 2, ',', '.') ?></span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card clickable" onclick="abrirModal('consumos')">
                <div class="stat-icon orange">🍽️</div>
                <div class="stat-content">
                    <div class="stat-label">Serviços 👆</div>
                    <div class="stat-value"><?= $qtdConsumos ?></div>
                    <div class="stat-change">
                        <span class="v-oculto">••••</span>
                        <span class="v-real" style="display:none;">R$ <?= number_format($totalConsumos, 2, ',', '.') ?></span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card clickable" onclick="abrirModal('servicos')">
                <div class="stat-icon" style="background:#e0e7ff;">🛍️</div>
                <div class="stat-content">
                    <div class="stat-label">Produtos 👆</div>
                    <div class="stat-value"><?= $qtdServicos ?></div>
                    <div class="stat-change">
                        <span class="v-oculto">••••</span>
                        <span class="v-real" style="display:none;">R$ <?= number_format($totalServicos, 2, ',', '.') ?></span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card clickable" onclick="abrirModal('despesas')" style="border: 2px solid #fecaca;">
                <div class="stat-icon" style="background:#fef2f2; color:#dc2626;">💸</div>
                <div class="stat-content">
                    <div class="stat-label">Despesas 👆</div>
                    <div class="stat-value" style="color:#dc2626;"><?= $qtdDespesas ?></div>
                    <div class="stat-change" style="color:#dc2626;">
                        <span class="v-oculto">••••</span>
                        <span class="v-real" style="display:none;">R$ <?= number_format($totalDespesas, 2, ',', '.') ?></span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card" style="background: <?= $lucroTotal >= 0 ? 'linear-gradient(135deg, #166534, #22c55e)' : 'linear-gradient(135deg, #dc2626, #ef4444)' ?>; color: white;">
                <div class="stat-icon" style="background:rgba(255,255,255,0.2); color:white;"><?= $lucroTotal >= 0 ? '📈' : '📉' ?></div>
                <div class="stat-content">
                    <div class="stat-label" style="color:rgba(255,255,255,0.9);">Lucro</div>
                    <div class="stat-value" style="color:white;">
                        <span class="v-oculto" style="color:white;">••••••</span>
                        <span class="v-real" style="display:none;">R$ <?= number_format($lucroTotal, 0, ',', '.') ?></span>
                    </div>
                    <div class="stat-change" style="color:rgba(255,255,255,0.8);">
                        Receita - Despesas
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal Finalizadas -->
<div id="modal-finalizadas" class="modal-bg">
    <div class="modal-box modal-lg">
        <div class="modal-head" style="background:#f0fdf4;">
            <h3 style="color:#166534;">✅ Finalizadas no Período (<?= $hospFinalizadas ?>)</h3>
            <button onclick="fecharModal('finalizadas')" class="modal-x">×</button>
        </div>
        <div class="modal-body">
            <div class="resumo-modal">Total: <strong>R$ <?= number_format($receitaFinalizadas, 2, ',', '.') ?></strong></div>
            <table class="table" style="font-size:12px;">
                <thead><tr><th>Checkout</th><th>Quarto</th><th>Cliente</th><th>Período</th><th style="text-align:right;">Valor</th></tr></thead>
                <tbody>
                <?php if (empty($finalizadasList)): ?>
                <tr><td colspan="5" class="empty">Nenhuma hospedagem finalizada no período</td></tr>
                <?php else: foreach ($finalizadasList as $f): ?>
                <tr onclick="window.location='hospedagem-detalhes.php?id=<?= $f['ID'] ?>'" style="cursor:pointer;">
                    <td><?= $f['lg_checkout'] ? date('d/m/Y H:i', strtotime($f['lg_checkout'])) : '-' ?></td>
                    <td><span class="badge-q"><?= $f['quarto_numero'] ?></span></td>
                    <td><?= htmlspecialchars(substr($f['cliente_nome'] ?? '-', 0, 25)) ?></td>
                    <td style="font-size:11px;"><?= formatDate($f['data_entrada']) ?> - <?= formatDate($f['data_saida']) ?></td>
                    <td style="text-align:right;font-weight:600;">R$ <?= number_format($f['valor_total'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Ativas -->
<div id="modal-ativas" class="modal-bg">
    <div class="modal-box modal-lg">
        <div class="modal-head" style="background:#dbeafe;">
            <h3 style="color:#1e40af;">🔵 Ativas no Período (<?= $hospAtivas ?>)</h3>
            <button onclick="fecharModal('ativas')" class="modal-x">×</button>
        </div>
        <div class="modal-body">
            <div class="resumo-modal">Total: <strong>R$ <?= number_format($receitaAtivas, 2, ',', '.') ?></strong></div>
            <table class="table" style="font-size:12px;">
                <thead><tr><th>Entrada</th><th>Quarto</th><th>Cliente</th><th>Saída</th><th style="text-align:right;">Valor</th></tr></thead>
                <tbody>
                <?php if (empty($ativasList)): ?>
                <tr><td colspan="5" class="empty">Nenhuma hospedagem ativa no período</td></tr>
                <?php else: foreach ($ativasList as $a): ?>
                <tr onclick="window.location='hospedagem-detalhes.php?id=<?= $a['ID'] ?>'" style="cursor:pointer;">
                    <td><?= formatDate($a['data_entrada']) ?></td>
                    <td><span class="badge-q"><?= $a['quarto_numero'] ?></span></td>
                    <td><?= htmlspecialchars(substr($a['cliente_nome'] ?? '-', 0, 25)) ?></td>
                    <td><?= formatDate($a['data_saida']) ?></td>
                    <td style="text-align:right;font-weight:600;">R$ <?= number_format($a['valor_total'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Serviços (antigo Consumos) -->
<div id="modal-consumos" class="modal-bg">
    <div class="modal-box modal-lg">
        <div class="modal-head" style="background:#fef3c7;">
            <h3 style="color:#92400e;">🛎️ Serviços no Período (<?= $qtdConsumos ?>)</h3>
            <button onclick="fecharModal('consumos')" class="modal-x">×</button>
        </div>
        <div class="modal-body">
            <div class="resumo-modal">Total: <strong>R$ <?= number_format($totalConsumos, 2, ',', '.') ?></strong></div>
            
            <!-- Top Serviços -->
            <?php if (!empty($topConsumos)): ?>
            <h4 style="margin:15px 0 10px;font-size:13px;">🏆 Top Serviços</h4>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;margin-bottom:20px;">
            <?php $i=1; foreach ($topConsumos as $tc): ?>
            <div style="background:#fffbeb;padding:10px;border-radius:6px;display:flex;justify-content:space-between;">
                <span style="font-size:12px;"><?= $i <= 3 ? ['🥇','🥈','🥉'][$i-1] : $i.'.' ?> <?= htmlspecialchars(substr($tc['description'] ?? '-', 0, 20)) ?></span>
                <strong style="font-size:12px;">R$ <?= number_format($tc['valor_total'], 2, ',', '.') ?></strong>
            </div>
            <?php $i++; endforeach; ?>
            </div>
            <?php endif; ?>
            
            <h4 style="margin:15px 0 10px;font-size:13px;">📋 Lista Completa</h4>
            <table class="table" style="font-size:11px;">
                <thead><tr><th>Data</th><th>Quarto</th><th>Produto</th><th>Qtd</th><th style="text-align:right;">Valor</th></tr></thead>
                <tbody>
                <?php if (empty($consumosList)): ?>
                <tr><td colspan="5" class="empty">Nenhum consumo no período</td></tr>
                <?php else: foreach ($consumosList as $c): 
                    $prodNome = $c['produto'] ?? $c['descricao'] ?? '-';
                ?>
                <tr>
                    <td><?= formatDate($c['data_cons'] ?? '') ?></td>
                    <td><span class="badge-q"><?= $c['quarto'] ?? '-' ?></span></td>
                    <td><?= htmlspecialchars(substr($prodNome, 0, 25)) ?></td>
                    <td style="text-align:center;"><?= $c['qtd'] ?? 0 ?></td>
                    <td style="text-align:right;">R$ <?= number_format((float)($c['valor_total'] ?? 0), 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Produtos (antigo Serviços) -->
<div id="modal-servicos" class="modal-bg">
    <div class="modal-box modal-lg">
        <div class="modal-head" style="background:#e0e7ff;">
            <h3 style="color:#4338ca;">🛍️ Produtos no Período (<?= $qtdServicos ?>)</h3>
            <button onclick="fecharModal('servicos')" class="modal-x">×</button>
        </div>
        <div class="modal-body">
            <div class="resumo-modal">Total: <strong>R$ <?= number_format($totalServicos, 2, ',', '.') ?></strong></div>
            
            <!-- Top Produtos -->
            <?php if (!empty($topServicos)): ?>
            <h4 style="margin:15px 0 10px;font-size:13px;">🏆 Top Produtos</h4>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;margin-bottom:20px;">
            <?php $i=1; foreach ($topServicos as $ts): ?>
            <div style="background:#eef2ff;padding:10px;border-radius:6px;display:flex;justify-content:space-between;">
                <span style="font-size:12px;"><?= $i <= 3 ? ['🥇','🥈','🥉'][$i-1] : $i.'.' ?> <?= htmlspecialchars(substr($ts['description'] ?? '-', 0, 20)) ?></span>
                <strong style="font-size:12px;">R$ <?= number_format($ts['valor_total'], 2, ',', '.') ?></strong>
            </div>
            <?php $i++; endforeach; ?>
            </div>
            <?php endif; ?>
            
            <h4 style="margin:15px 0 10px;font-size:13px;">📋 Lista Completa</h4>
            <table class="table" style="font-size:11px;">
                <thead><tr><th>Data</th><th>Quarto</th><th>Serviço</th><th>Qtd</th><th style="text-align:right;">Valor</th></tr></thead>
                <tbody>
                <?php if (empty($servicosList)): ?>
                <tr><td colspan="5" class="empty">Nenhum serviço no período</td></tr>
                <?php else: foreach ($servicosList as $s): ?>
                <tr>
                    <td><?= formatDate($s['data_serv'] ?? '') ?></td>
                    <td><span class="badge-q"><?= $s['quarto'] ?? '-' ?></span></td>
                    <td><?= htmlspecialchars(substr($s['servico'] ?? '-', 0, 25)) ?></td>
                    <td style="text-align:center;"><?= $s['qtd'] ?? 0 ?></td>
                    <td style="text-align:right;">R$ <?= number_format((float)($s['valor_total'] ?? 0), 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Despesas -->
<div id="modal-despesas" class="modal-bg">
    <div class="modal-box modal-lg">
        <div class="modal-head" style="background:#fef2f2;">
            <h3 style="color:#dc2626;">💸 Despesas no Período (<?= $qtdDespesas ?>)</h3>
            <button onclick="fecharModal('despesas')" class="modal-x">×</button>
        </div>
        <div class="modal-body">
            <div class="resumo-modal" style="background:#fef2f2;color:#dc2626;">Total: <strong>R$ <?= number_format($totalDespesas, 2, ',', '.') ?></strong></div>
            
            <div style="text-align: right; margin-bottom: 15px;">
                <a href="expenses.php" class="btn btn-primary" style="font-size: 12px;">➕ Gerenciar Despesas</a>
            </div>
            
            <table class="table" style="font-size:11px;">
                <thead><tr><th>Data</th><th>Descrição</th><th>Categoria</th><th style="text-align:center;">Status</th><th style="text-align:right;">Valor</th></tr></thead>
                <tbody>
                <?php if (empty($despesasList)): ?>
                <tr><td colspan="5" class="empty">Nenhuma despesa no período</td></tr>
                <?php else: foreach ($despesasList as $d): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($d['data'])) ?></td>
                    <td><?= htmlspecialchars(substr($d['descricao'] ?? '-', 0, 30)) ?></td>
                    <td><span style="background:#e0e7ff;padding:2px 6px;border-radius:10px;font-size:10px;"><?= htmlspecialchars($d['categoria'] ?? 'Geral') ?></span></td>
                    <td style="text-align:center;">
                        <?php if ($d['status'] === 'P'): ?>
                        <span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:10px;font-size:10px;">Pendente</span>
                        <?php else: ?>
                        <span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:10px;font-size:10px;">Pago</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;font-weight:600;color:#dc2626;">R$ <?= number_format((float)($d['valor'] ?? 0), 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.stat-card.clickable{cursor:pointer;}
.stat-card.clickable:hover{transform:translateY(-3px);box-shadow:0 8px 20px rgba(0,0,0,0.15);}
.stat-icon{width:48px;height:48px;display:flex;align-items:center;justify-content:center;border-radius:12px;font-size:22px;}
.stat-icon.green{background:#dcfce7;}
.stat-icon.blue{background:#dbeafe;}
.stat-icon.orange{background:#fef3c7;}
.v-oculto{color:#9ca3af;letter-spacing:2px;}
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;overflow-y:auto;}
.modal-box{background:white;max-width:800px;margin:40px auto;border-radius:12px;max-height:85vh;display:flex;flex-direction:column;}
.modal-box.modal-lg{max-width:900px;}
.modal-head{padding:15px 20px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;}
.modal-head h3{margin:0;font-size:15px;}
.modal-x{background:none;border:none;font-size:26px;cursor:pointer;color:#666;}
.modal-body{flex:1;overflow-y:auto;padding:20px;}
.resumo-modal{background:#f9fafb;padding:12px;border-radius:6px;margin-bottom:15px;font-size:14px;}
.table{width:100%;border-collapse:collapse;}
.table th,.table td{padding:8px;border-bottom:1px solid #eee;text-align:left;}
.table th{background:#f9fafb;font-size:10px;text-transform:uppercase;color:#666;}
.table tr:hover{background:#f9fafb;}
.badge-q{background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:4px;font-size:10px;}
.empty{text-align:center;padding:30px;color:#999;}
</style>

<script>
var visivel = false;
function toggleValores(){
    visivel = !visivel;
    document.querySelectorAll('.v-oculto').forEach(e=>e.style.display=visivel?'none':'inline');
    document.querySelectorAll('.v-real').forEach(e=>e.style.display=visivel?'inline':'none');
    document.getElementById('btn-toggle').textContent = visivel ? '👁️ Ocultar Valores' : '👁️ Mostrar Valores';
}

function abrirModal(tipo){
    document.getElementById('modal-'+tipo).style.display='block';
    document.body.style.overflow='hidden';
}
function fecharModal(tipo){
    document.getElementById('modal-'+tipo).style.display='none';
    document.body.style.overflow='';
}

document.querySelectorAll('.modal-bg').forEach(m=>{
    m.addEventListener('click',e=>{if(e.target===m)fecharModal(m.id.replace('modal-',''));});
});
</script>

<?php 
mysqli_close($conexao);
include 'includes/footer.php'; 
?>
