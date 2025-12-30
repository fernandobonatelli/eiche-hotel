<?php
/**
 * Pousada Bona - Dashboard Principal
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

$userName = $_SESSION['user_name'] ?? 'Usuário';

$hoje = date('Y-m-d');
$mesAtual = date('Y-m');
$primeiroDiaMes = date('Y-m-01');

// Estatísticas
$result = mysqli_query($conexao, "SELECT COUNT(*) as total FROM eiche_customers");
$totalClientes = mysqli_fetch_assoc($result)['total'] ?? 0;

$result = mysqli_query($conexao, "SELECT COUNT(DISTINCT ID) as total FROM eiche_hospedagem WHERE rstatus = 'A' AND idonly = 1");
$hospAtivas = mysqli_fetch_assoc($result)['total'] ?? 0;

$result = mysqli_query($conexao, "SELECT COUNT(DISTINCT ID) as total FROM eiche_hospedagem WHERE rstatus = 'R' AND idonly = 1");
$reservasPendentes = mysqli_fetch_assoc($result)['total'] ?? 0;

$result = mysqli_query($conexao, "SELECT COUNT(DISTINCT ID) as total FROM eiche_hospedagem WHERE rstatus = 'F' AND data >= '$primeiroDiaMes' AND idonly = 1");
$hospFinalizadasMes = mysqli_fetch_assoc($result)['total'] ?? 0;

$result = mysqli_query($conexao, "SELECT COUNT(DISTINCT ID) as total FROM eiche_hospedagem WHERE data = '$hoje' AND tipo = 'E' AND rstatus IN ('A', 'R')");
$checkinsHoje = mysqli_fetch_assoc($result)['total'] ?? 0;

$result = mysqli_query($conexao, "SELECT COUNT(DISTINCT ID) as total FROM eiche_hospedagem WHERE data = '$hoje' AND tipo = 'S' AND rstatus = 'A'");
$checkoutsHoje = mysqli_fetch_assoc($result)['total'] ?? 0;

// Receita do mês - mesma lógica do Financeiro
// Finalizadas no mês (checkout no mês)
$sqlFin = "SELECT SUM(h.valor_diaria) as total 
           FROM eiche_hospedagem h 
           WHERE h.rstatus = 'F' 
           AND DATE(h.lg_checkout) >= '$primeiroDiaMes' 
           AND DATE(h.lg_checkout) <= '$hoje'";
$result = mysqli_query($conexao, $sqlFin);
$receitaFinalizadas = mysqli_fetch_assoc($result)['total'] ?? 0;

// Ativas no mês
$sqlAtiv = "SELECT SUM(h.valor_diaria) as total 
            FROM eiche_hospedagem h 
            WHERE h.rstatus = 'A' 
            AND h.data >= '$primeiroDiaMes' AND h.data <= '$hoje'";
$result = mysqli_query($conexao, $sqlAtiv);
$receitaAtivas = mysqli_fetch_assoc($result)['total'] ?? 0;

// Consumos do mês (sem duplicação - filtra apenas pela data do consumo)
$sqlCons = "SELECT SUM(c.valor_unit * c.qtd) as total 
            FROM eiche_hosp_lnk_cons_hosp c
            WHERE c.data >= '$primeiroDiaMes' AND c.data <= '$hoje'";
$result = mysqli_query($conexao, $sqlCons);
$receitaConsumos = mysqli_fetch_assoc($result)['total'] ?? 0;

// Serviços do mês (sem duplicação - filtra apenas pela data do serviço)
$sqlServ = "SELECT SUM(s.valor_unit * s.qtd) as total 
            FROM eiche_hosp_lnk_serv_hosp s
            WHERE s.data >= '$primeiroDiaMes' AND s.data <= '$hoje'";
$result = mysqli_query($conexao, $sqlServ);
$receitaServicos = mysqli_fetch_assoc($result)['total'] ?? 0;

// Calcular descontos do mês (apenas hospedagens finalizadas/ativas no período)
$totalDescontos = 0;
$checkDescontoCol = mysqli_query($conexao, "SHOW COLUMNS FROM eiche_hospedagem LIKE 'desconto_tipo'");
if (mysqli_num_rows($checkDescontoCol) > 0) {
    // Descontos de hospedagens finalizadas no mês
    $sqlDescFin = "SELECT h.desconto_tipo, h.desconto_valor, SUM(h.valor_diaria) as subtotal_diarias
                   FROM eiche_hospedagem h 
                   WHERE h.rstatus = 'F' AND h.idonly = 1
                   AND DATE(h.lg_checkout) >= '$primeiroDiaMes' 
                   AND DATE(h.lg_checkout) <= '$hoje'
                   AND h.desconto_tipo != 'nenhum' AND h.desconto_valor > 0
                   GROUP BY h.ID";
    $resultDesc = mysqli_query($conexao, $sqlDescFin);
    while ($rowDesc = mysqli_fetch_assoc($resultDesc)) {
        // Precisa calcular o subtotal completo (diárias + consumos + serviços) para aplicar desconto percentual
        // Por simplicidade, aplicamos sobre as diárias apenas no dashboard
        if ($rowDesc['desconto_tipo'] === 'percentual') {
            $totalDescontos += ($rowDesc['subtotal_diarias'] ?? 0) * ($rowDesc['desconto_valor'] / 100);
        } else {
            $totalDescontos += $rowDesc['desconto_valor'];
        }
    }
}

// Receita total do mês (diárias + consumos + serviços - descontos)
$receitaDiariasMes = $receitaFinalizadas + $receitaAtivas;
$receitaTotalMes = $receitaDiariasMes + $receitaConsumos + $receitaServicos - $totalDescontos;

// Despesas do mês (verificar se tabela existe)
$despesasMes = 0;
$checkTable = mysqli_query($conexao, "SHOW TABLES LIKE 'eiche_despesas'");
if (mysqli_num_rows($checkTable) > 0) {
    $sqlDesp = "SELECT SUM(valor) as total FROM eiche_despesas 
                WHERE DATE_FORMAT(data, '%Y-%m') = '$mesAtual'";
    $result = mysqli_query($conexao, $sqlDesp);
    $despesasMes = mysqli_fetch_assoc($result)['total'] ?? 0;
}

// Lucro do mês (receita - despesas)
$lucroMes = $receitaTotalMes - $despesasMes;

$result = mysqli_query($conexao, "SELECT COUNT(*) as total FROM eiche_hosp_quartos");
$totalQuartos = mysqli_fetch_assoc($result)['total'] ?? 0;

$result = mysqli_query($conexao, "SELECT COUNT(DISTINCT ID_quarto) as total FROM eiche_hospedagem WHERE data = '$hoje' AND rstatus = 'A'");
$quartosOcupados = mysqli_fetch_assoc($result)['total'] ?? 0;

$taxaOcupacao = $totalQuartos > 0 ? round(($quartosOcupados / $totalQuartos) * 100) : 0;

// Hospedagens ativas para modal
$hospedagensAtivas = [];
$sql = "SELECT h.ID, MIN(h.data) as data_entrada, MAX(h.data) as data_saida, 
               h.valor_diaria, c.razao as cliente_nome, c.cpf, q.numero as quarto_numero,
               COUNT(*) as num_diarias
        FROM eiche_hospedagem h 
        LEFT JOIN eiche_customers c ON h.ID_cliente = c.ID 
        LEFT JOIN eiche_hosp_quartos q ON h.ID_quarto = q.ID 
        WHERE h.rstatus = 'A'
        GROUP BY h.ID
        ORDER BY MIN(h.data) ASC";
$result = mysqli_query($conexao, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $hospedagensAtivas[] = $row;
    }
}

// Check-ins de hoje (lista)
$checkinsHojeList = [];
$sql = "SELECT h.ID, h.valor_diaria, c.razao as cliente_nome, c.fone1, q.numero as quarto_numero
        FROM eiche_hospedagem h 
        LEFT JOIN eiche_customers c ON h.ID_cliente = c.ID 
        LEFT JOIN eiche_hosp_quartos q ON h.ID_quarto = q.ID 
        WHERE h.data = '$hoje' AND h.tipo = 'E' AND h.rstatus IN ('A', 'R')
        ORDER BY q.numero ASC";
$result = mysqli_query($conexao, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $checkinsHojeList[] = $row;
    }
}

// Check-outs de hoje (lista)
$checkoutsHojeList = [];
$sql = "SELECT h.ID, h.valor_diaria, c.razao as cliente_nome, c.fone1, q.numero as quarto_numero
        FROM eiche_hospedagem h 
        LEFT JOIN eiche_customers c ON h.ID_cliente = c.ID 
        LEFT JOIN eiche_hosp_quartos q ON h.ID_quarto = q.ID 
        WHERE h.data = '$hoje' AND h.tipo = 'S' AND h.rstatus = 'A'
        ORDER BY q.numero ASC";
$result = mysqli_query($conexao, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $checkoutsHojeList[] = $row;
    }
}

// Últimas hospedagens
$ultimasHospedagens = [];
$sql = "SELECT h.ID, MIN(h.data) as data_entrada, h.valor_diaria, h.rstatus, 
               c.razao as cliente_nome, q.numero as quarto_numero
        FROM eiche_hospedagem h 
        LEFT JOIN eiche_customers c ON h.ID_cliente = c.ID 
        LEFT JOIN eiche_hosp_quartos q ON h.ID_quarto = q.ID 
        GROUP BY h.ID
        ORDER BY h.ID DESC LIMIT 8";
$result = mysqli_query($conexao, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $ultimasHospedagens[] = $row;
    }
}

$meses = [1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',
          7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'];
$diasSemana = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];

$pageTitle = 'Dashboard - Pousada Bona';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>
    
    <main class="main-content">
        <div class="content-header">
            <div class="content-header-left">
                <h1>Olá, <?= htmlspecialchars(explode(' ', $userName)[0]) ?>! 👋</h1>
                <p><?= $diasSemana[(int)date('w')] ?>, <?= date('d') ?> de <?= $meses[(int)date('m')] ?></p>
            </div>
            <div class="content-header-actions">
                <a href="reservations.php" class="btn btn-primary">📅 Painel de Hospedagens</a>
            </div>
        </div>
        
        <!-- Cards Principais -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-icon blue">🏨</div>
                <div class="stat-content">
                    <div class="stat-label">Taxa de Ocupação</div>
                    <div class="stat-value"><?= $taxaOcupacao ?>%</div>
                    <div class="stat-change"><?= $quartosOcupados ?>/<?= $totalQuartos ?> quartos</div>
                </div>
            </div>
            
            <div class="stat-card clickable" onclick="abrirModalAtivas()">
                <div class="stat-icon green">✅</div>
                <div class="stat-content">
                    <div class="stat-label">Hospedagens Ativas 👆</div>
                    <div class="stat-value"><?= $hospAtivas ?></div>
                    <div class="stat-change"><?= $reservasPendentes ?> reservas pendentes</div>
                </div>
            </div>
            
            <div class="stat-card clickable" onclick="abrirModalHoje()">
                <div class="stat-icon orange">📊</div>
                <div class="stat-content">
                    <div class="stat-label">Hoje 👆</div>
                    <div class="stat-value"><?= $checkinsHoje ?> / <?= $checkoutsHoje ?></div>
                    <div class="stat-change">In / Out</div>
                </div>
            </div>
            
            <?php if ($podeVerValores): ?>
            <div class="stat-card clickable" onclick="window.location='finance.php'" title="Ver detalhes no Financeiro">
                <div class="stat-icon green">💵</div>
                <div class="stat-content">
                    <div class="stat-label">Receita <?= $meses[(int)date('m')] ?> 👆 <button onclick="event.stopPropagation();toggleReceita()" class="eye-btn">👁️</button></div>
                    <div class="stat-value">
                        <span id="receita-oculto">••••••</span>
                        <span id="receita-real" style="display:none;">R$ <?= number_format($receitaTotalMes, 0, ',', '.') ?></span>
                    </div>
                    <div class="stat-change" style="font-size:10px;">
                        <span class="receita-detalhe" style="display:none;">
                            Diárias: R$ <?= number_format($receitaDiariasMes, 0, ',', '.') ?> | 
                            Serv: R$ <?= number_format($receitaConsumos, 0, ',', '.') ?> | 
                            Prod: R$ <?= number_format($receitaServicos, 0, ',', '.') ?>
                        </span>
                        <span class="receita-detalhe-oculto"><?= $hospFinalizadasMes ?> finalizadas</span>
                    </div>
                </div>
            </div>
            
            <!-- Card Despesas -->
            <div class="stat-card clickable" onclick="window.location='expenses.php'" title="Ver Despesas">
                <div class="stat-icon" style="background: #fef2f2; color: #dc2626;">💸</div>
                <div class="stat-content">
                    <div class="stat-label">Despesas <?= $meses[(int)date('m')] ?> 👆 <button onclick="event.stopPropagation();toggleDespesa()" class="eye-btn">👁️</button></div>
                    <div class="stat-value">
                        <span id="despesa-oculto">••••••</span>
                        <span id="despesa-real" style="display:none;">R$ <?= number_format($despesasMes, 0, ',', '.') ?></span>
                    </div>
                    <div class="stat-change" style="font-size:10px; color: #dc2626;">
                        Clique para gerenciar
                    </div>
                </div>
            </div>
            
            <!-- Card Lucro -->
            <div class="stat-card" style="background: <?= $lucroMes >= 0 ? 'linear-gradient(135deg, #166534, #22c55e)' : 'linear-gradient(135deg, #dc2626, #ef4444)' ?>; color: white;">
                <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;"><?= $lucroMes >= 0 ? '📈' : '📉' ?></div>
                <div class="stat-content">
                    <div class="stat-label" style="color: rgba(255,255,255,0.9);">Lucro <?= $meses[(int)date('m')] ?> <button onclick="event.stopPropagation();toggleLucro()" class="eye-btn" style="color: white;">👁️</button></div>
                    <div class="stat-value" style="color: white;">
                        <span id="lucro-oculto">••••••</span>
                        <span id="lucro-real" style="display:none;">R$ <?= number_format($lucroMes, 0, ',', '.') ?></span>
                    </div>
                    <div class="stat-change" style="font-size:10px; color: rgba(255,255,255,0.8);">
                        Receita - Despesas
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Usuário sem permissão de ver valores -->
            <div class="stat-card" style="background: #f3f4f6; border: 2px dashed #d1d5db;">
                <div class="stat-icon" style="background: #e5e7eb; color: #9ca3af;">🔒</div>
                <div class="stat-content">
                    <div class="stat-label" style="color: #6b7280;">Valores Financeiros</div>
                    <div class="stat-value" style="color: #9ca3af; font-size: 16px;">Sem permissão</div>
                    <div class="stat-change" style="font-size:10px; color: #9ca3af;">
                        Contate o administrador
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Acesso Rápido -->
        <div class="card" style="margin-top: 20px;">
            <div class="card-header"><h3>Acesso Rápido</h3></div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px;">
                    <a href="reservations.php" class="quick-action"><div class="qa-icon">📅</div><span>Hospedagens</span></a>
                    <a href="rooms.php" class="quick-action"><div class="qa-icon">🏨</div><span>Quartos</span></a>
                    <a href="guests.php" class="quick-action"><div class="qa-icon">👥</div><span>Clientes</span></a>
                    <a href="finance.php" class="quick-action"><div class="qa-icon">💰</div><span>Financeiro</span></a>
                    <a href="reports.php" class="quick-action"><div class="qa-icon">📊</div><span>Relatórios</span></a>
                    <a href="settings.php" class="quick-action"><div class="qa-icon">⚙️</div><span>Config</span></a>
                </div>
            </div>
        </div>
        
        <!-- Últimas Hospedagens -->
        <div class="card" style="margin-top: 20px;">
            <div class="card-header" style="display: flex; justify-content: space-between;">
                <h3>Últimas Hospedagens</h3>
                <a href="reservations.php" class="btn btn-ghost btn-sm">Ver todas →</a>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="table" style="font-size: 12px;">
                    <thead><tr><th>Cliente</th><th>Quarto</th><th>Data</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($ultimasHospedagens as $h): ?>
                    <tr onclick="window.location='hospedagem-detalhes.php?id=<?= $h['ID'] ?>'" style="cursor:pointer;">
                        <td><?= htmlspecialchars(substr($h['cliente_nome'] ?? '-', 0, 30)) ?></td>
                        <td><span class="badge-quarto"><?= $h['quarto_numero'] ?></span></td>
                        <td><?= $h['data_entrada'] ? date('d/m/Y', strtotime($h['data_entrada'])) : '-' ?></td>
                        <td><span class="status-<?= $h['rstatus'] ?>"><?= ['A'=>'Ativo','R'=>'Reserva','F'=>'Fechado'][$h['rstatus']] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modal Hospedagens Ativas -->
<div id="modal-ativas" class="modal-bg">
    <div class="modal-box modal-lg">
        <div class="modal-head" style="background:#f0fdf4;">
            <h3 style="color:#166534;">✅ Hospedagens Ativas (<?= count($hospedagensAtivas) ?>)</h3>
            <button onclick="fecharModal()" class="modal-x">×</button>
        </div>
        <div class="modal-tools">
            <label><input type="checkbox" id="sel-all" onchange="toggleAll()"> Selecionar Todos</label>
            <span id="sel-count">0 selecionados</span>
            <button id="btn-lote" onclick="checkoutLote()" class="btn btn-primary" style="display:none;margin-left:auto;">🚪 Checkout em Lote</button>
        </div>
        <div class="modal-content">
            <table class="table" id="tbl-ativas" style="font-size:12px;">
                <thead>
                    <tr>
                        <th style="width:40px;"></th>
                        <th class="sortable" onclick="ordenar('quarto')">Quarto <span id="ico-quarto">↕</span></th>
                        <th class="sortable" onclick="ordenar('cliente')">Cliente <span id="ico-cliente">↕</span></th>
                        <th>CPF</th>
                        <th class="sortable" onclick="ordenar('entrada')">Entrada <span id="ico-entrada">↑</span></th>
                        <th class="sortable" onclick="ordenar('saida')">Saída <span id="ico-saida">↕</span></th>
                        <th style="text-align:right;">Valor</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="tbody-ativas">
                <?php foreach ($hospedagensAtivas as $h): 
                    $vt = ($h['valor_diaria'] ?? 0) * ($h['num_diarias'] ?? 1);
                ?>
                <tr class="row-hosp" 
                    data-id="<?= $h['ID'] ?>"
                    data-quarto="<?= htmlspecialchars($h['quarto_numero'] ?? '') ?>"
                    data-cliente="<?= htmlspecialchars($h['cliente_nome'] ?? '') ?>"
                    data-entrada="<?= $h['data_entrada'] ?? '' ?>"
                    data-saida="<?= $h['data_saida'] ?? '' ?>"
                    data-valor="<?= $vt ?>">
                    <td><input type="checkbox" class="cb-hosp" value="<?= $h['ID'] ?>" onchange="updateSel()"></td>
                    <td><span class="badge-quarto"><?= $h['quarto_numero'] ?></span></td>
                    <td><?= htmlspecialchars(substr($h['cliente_nome'] ?? '-', 0, 28)) ?></td>
                    <td style="font-size:11px;color:#666;"><?= $h['cpf'] ?? '-' ?></td>
                    <td><?= $h['data_entrada'] ? date('d/m/Y', strtotime($h['data_entrada'])) : '-' ?></td>
                    <td><?= $h['data_saida'] ? date('d/m/Y', strtotime($h['data_saida'])) : '-' ?></td>
                    <td style="text-align:right;font-weight:600;">R$ <?= number_format($vt, 2, ',', '.') ?></td>
                    <td>
                        <a href="hospedagem-detalhes.php?id=<?= $h['ID'] ?>" class="btn-mini">👁️</a>
                        <button onclick="doCheckout(<?= $h['ID'] ?>)" class="btn-mini btn-red">🚪</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="modal-foot">
            <button onclick="fecharModal()" class="btn btn-secondary">Fechar</button>
        </div>
    </div>
</div>

<!-- Modal Hoje: Check-ins e Check-outs -->
<div id="modal-hoje" class="modal-bg">
    <div class="modal-box" style="max-width:700px;">
        <div class="modal-head" style="background:#fef3c7;">
            <h3 style="color:#92400e;">📊 Movimentação de Hoje - <?= date('d/m/Y') ?></h3>
            <button onclick="fecharModalHoje()" class="modal-x">×</button>
        </div>
        <div class="modal-content" style="padding:20px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <!-- Check-ins -->
                <div>
                    <h4 style="margin:0 0 10px;color:#16a34a;font-size:14px;">✅ Check-ins (<?= count($checkinsHojeList) ?>)</h4>
                    <?php if (empty($checkinsHojeList)): ?>
                    <p style="color:#999;font-size:12px;">Nenhum check-in hoje</p>
                    <?php else: ?>
                    <div style="max-height:300px;overflow-y:auto;">
                    <?php foreach ($checkinsHojeList as $ci): ?>
                    <a href="hospedagem-detalhes.php?id=<?= $ci['ID'] ?>" class="item-hoje" style="border-left:3px solid #22c55e;">
                        <div class="item-quarto"><?= $ci['quarto_numero'] ?></div>
                        <div class="item-info">
                            <div class="item-nome"><?= htmlspecialchars(substr($ci['cliente_nome'] ?? '-', 0, 25)) ?></div>
                            <div class="item-tel"><?= $ci['fone1'] ?? '-' ?></div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Check-outs -->
                <div>
                    <h4 style="margin:0 0 10px;color:#dc2626;font-size:14px;">🚪 Check-outs (<?= count($checkoutsHojeList) ?>)</h4>
                    <?php if (empty($checkoutsHojeList)): ?>
                    <p style="color:#999;font-size:12px;">Nenhum check-out hoje</p>
                    <?php else: ?>
                    <div style="max-height:300px;overflow-y:auto;">
                    <?php foreach ($checkoutsHojeList as $co): ?>
                    <div class="item-hoje" style="border-left:3px solid #ef4444;">
                        <div class="item-quarto"><?= $co['quarto_numero'] ?></div>
                        <div class="item-info">
                            <div class="item-nome"><?= htmlspecialchars(substr($co['cliente_nome'] ?? '-', 0, 25)) ?></div>
                            <div class="item-tel"><?= $co['fone1'] ?? '-' ?></div>
                        </div>
                        <div class="item-actions">
                            <a href="hospedagem-detalhes.php?id=<?= $co['ID'] ?>" class="btn-mini">👁️</a>
                            <button onclick="doCheckout(<?= $co['ID'] ?>)" class="btn-mini btn-red">🚪</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="modal-foot">
            <button onclick="fecharModalHoje()" class="btn btn-secondary">Fechar</button>
        </div>
    </div>
</div>

<form id="frm-checkout" method="POST" action="acao-hospedagem.php" style="display:none;">
    <input type="hidden" name="acao" value="checkout">
    <input type="hidden" name="id" id="checkout-id">
    <input type="hidden" name="redirect" value="dashboard.php">
</form>
<form id="frm-lote" method="POST" action="checkout-lote.php" style="display:none;">
    <input type="hidden" name="ids" id="lote-ids">
</form>

<style>
.stat-icon{width:48px;height:48px;display:flex;align-items:center;justify-content:center;border-radius:12px;font-size:24px;flex-shrink:0;}
.stat-icon.blue{background:#dbeafe;}
.stat-icon.green{background:#dcfce7;}
.stat-icon.orange{background:#fef3c7;}
.stat-card.clickable{cursor:pointer;}
.stat-card.clickable:hover{box-shadow:0 8px 20px rgba(0,0,0,0.12);transform:translateY(-2px);}
.eye-btn{background:none;border:none;cursor:pointer;font-size:12px;opacity:0.6;}
.eye-btn:hover{opacity:1;}
.quick-action{display:flex;flex-direction:column;align-items:center;gap:6px;padding:12px;border-radius:8px;text-decoration:none;color:#333;}
.quick-action:hover{background:#f3f4f6;}
.qa-icon{width:40px;height:40px;background:#e5e7eb;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;}
.badge-quarto{background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:4px;font-size:11px;}
.status-A{background:#dcfce7;color:#166534;padding:2px 8px;border-radius:4px;font-size:10px;}
.status-R{background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:4px;font-size:10px;}
.status-F{background:#f3f4f6;color:#374151;padding:2px 8px;border-radius:4px;font-size:10px;}
.table{width:100%;border-collapse:collapse;}
.table th,.table td{padding:10px;border-bottom:1px solid #eee;text-align:left;}
.table th{background:#f9fafb;font-size:11px;text-transform:uppercase;color:#666;}
.table tr:hover{background:#f9fafb;}

/* Modal */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;overflow-y:auto;}
.modal-box{background:white;max-width:900px;margin:40px auto;border-radius:12px;display:flex;flex-direction:column;max-height:85vh;}
.modal-lg{max-width:950px;}
.modal-head{padding:15px 20px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;}
.modal-head h3{margin:0;font-size:16px;}
.modal-x{background:none;border:none;font-size:28px;cursor:pointer;color:#666;line-height:1;}
.modal-tools{padding:12px 20px;background:#fafafa;border-bottom:1px solid #eee;display:flex;align-items:center;gap:15px;font-size:13px;}
.modal-content{flex:1;overflow-y:auto;padding:0;}
.modal-foot{padding:15px 20px;border-top:1px solid #eee;display:flex;justify-content:flex-end;gap:10px;}
.btn-mini{padding:4px 8px;border:none;border-radius:4px;cursor:pointer;font-size:11px;text-decoration:none;background:#dbeafe;color:#1e40af;}
.btn-mini.btn-red{background:#fee2e2;color:#991b1b;}
.sortable{cursor:pointer;user-select:none;}
.sortable:hover{background:#e5e7eb;}
.row-hosp:hover{background:#f0fdf4!important;}
.item-hoje{display:flex;align-items:center;gap:10px;padding:10px;background:#f9fafb;border-radius:6px;margin-bottom:8px;text-decoration:none;color:inherit;}
.item-hoje:hover{background:#f0f0f0;}
.item-quarto{background:#dbeafe;color:#1e40af;padding:4px 10px;border-radius:4px;font-weight:600;font-size:12px;}
.item-info{flex:1;}
.item-nome{font-weight:600;font-size:12px;}
.item-tel{font-size:11px;color:#666;}
.item-actions{display:flex;gap:5px;}
</style>

<script>
function toggleReceita(){
    var o=document.getElementById('receita-oculto');
    var r=document.getElementById('receita-real');
    var d=document.querySelectorAll('.receita-detalhe');
    var do_=document.querySelectorAll('.receita-detalhe-oculto');
    if(o.style.display==='none'){
        o.style.display='inline';
        r.style.display='none';
        d.forEach(function(e){e.style.display='none';});
        do_.forEach(function(e){e.style.display='inline';});
    }else{
        o.style.display='none';
        r.style.display='inline';
        d.forEach(function(e){e.style.display='inline';});
        do_.forEach(function(e){e.style.display='none';});
    }
}

function toggleDespesa(){
    var o=document.getElementById('despesa-oculto');
    var r=document.getElementById('despesa-real');
    if(o.style.display==='none'){
        o.style.display='inline';
        r.style.display='none';
    }else{
        o.style.display='none';
        r.style.display='inline';
    }
}

function toggleLucro(){
    var o=document.getElementById('lucro-oculto');
    var r=document.getElementById('lucro-real');
    if(o.style.display==='none'){
        o.style.display='inline';
        r.style.display='none';
    }else{
        o.style.display='none';
        r.style.display='inline';
    }
}

function abrirModalAtivas(){
    document.getElementById('modal-ativas').style.display='block';
    document.body.style.overflow='hidden';
    ordenarInicial();
}
function fecharModal(){
    document.getElementById('modal-ativas').style.display='none';
    document.body.style.overflow='';
}
document.getElementById('modal-ativas').addEventListener('click',function(e){if(e.target===this)fecharModal();});

function abrirModalHoje(){
    document.getElementById('modal-hoje').style.display='block';
    document.body.style.overflow='hidden';
}
function fecharModalHoje(){
    document.getElementById('modal-hoje').style.display='none';
    document.body.style.overflow='';
}
document.getElementById('modal-hoje').addEventListener('click',function(e){if(e.target===this)fecharModalHoje();});

function toggleAll(){
    var c=document.getElementById('sel-all').checked;
    document.querySelectorAll('.cb-hosp').forEach(function(x){x.checked=c;});
    updateSel();
}
function updateSel(){
    var n=document.querySelectorAll('.cb-hosp:checked').length;
    document.getElementById('sel-count').textContent=n+' selecionados';
    document.getElementById('btn-lote').style.display=n>0?'inline-flex':'none';
}
function doCheckout(id){
    if(confirm('Fazer check-out?')){
        document.getElementById('checkout-id').value=id;
        document.getElementById('frm-checkout').submit();
    }
}
function checkoutLote(){
    var ids=[];
    document.querySelectorAll('.cb-hosp:checked').forEach(function(x){ids.push(x.value);});
    if(ids.length && confirm('Checkout de '+ids.length+' hospedagem(ns)?')){
        document.getElementById('lote-ids').value=ids.join(',');
        document.getElementById('frm-lote').submit();
    }
}

// Ordenação
var sortCol='entrada', sortDir='asc';
function ordenar(col){
    if(sortCol===col){sortDir=sortDir==='asc'?'desc':'asc';}
    else{sortCol=col;sortDir='asc';}
    var tbody=document.getElementById('tbody-ativas');
    var rows=Array.from(tbody.querySelectorAll('.row-hosp'));
    rows.sort(function(a,b){
        var va=a.dataset[col]||'', vb=b.dataset[col]||'';
        if(col==='valor'){va=parseFloat(va)||0;vb=parseFloat(vb)||0;}
        else if(col==='entrada'||col==='saida'){va=va?new Date(va).getTime():0;vb=vb?new Date(vb).getTime():0;}
        else{va=va.toLowerCase();vb=vb.toLowerCase();}
        var cmp=0;if(va<vb)cmp=-1;else if(va>vb)cmp=1;
        return sortDir==='asc'?cmp:-cmp;
    });
    rows.forEach(function(r){tbody.appendChild(r);});
    updateIcons();
}
function updateIcons(){
    ['quarto','cliente','entrada','saida'].forEach(function(c){
        var i=document.getElementById('ico-'+c);
        if(i){
            if(sortCol===c){i.textContent=sortDir==='asc'?'↑':'↓';i.style.color=sortDir==='asc'?'#16a34a':'#dc2626';}
            else{i.textContent='↕';i.style.color='#9ca3af';}
        }
    });
}
function ordenarInicial(){
    sortCol='entrada';sortDir='asc';
    var tbody=document.getElementById('tbody-ativas');
    var rows=Array.from(tbody.querySelectorAll('.row-hosp'));
    rows.sort(function(a,b){
        var va=a.dataset.entrada||'',vb=b.dataset.entrada||'';
        va=va?new Date(va).getTime():0;vb=vb?new Date(vb).getTime():0;
        return va-vb;
    });
    rows.forEach(function(r){tbody.appendChild(r);});
    updateIcons();
}
</script>

<?php 
mysqli_close($conexao);
include 'includes/footer.php'; 
?>
