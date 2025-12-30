<?php
/**
 * Pousada Bona - Painel de Hospedagens
 */

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

$db_host = 'localhost';
$db_user = 'pous3527_root';
$db_pass = ';Fb6818103200';
$db_name = 'pous3527_eiche';

$conexao = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conexao) {
    die("Erro de conexão: " . mysqli_connect_error());
}
mysqli_set_charset($conexao, 'utf8');

$userName = $_SESSION['user_name'] ?? 'Usuário';

// Configurações
$dias = 21;
$hoje = date('Y-m-d');
$dataInicial = isset($_GET['data']) ? $_GET['data'] : $hoje;

// Buscar todos os quartos com valores do grupo
$quartos = [];
$sqlQuartos = "SELECT q.ID, q.numero, q.ocupantes, q.valor, q.vlr_ce, q.vlr_add_base, q.vlr_add, q.grupo,
               COALESCE(g.valor, q.valor) as valor_final,
               COALESCE(g.vlr_ce, q.vlr_ce) as vlr_ce_final,
               COALESCE(g.vlr_add_base, q.vlr_add_base) as vlr_add_base_final,
               COALESCE(g.vlr_add, q.vlr_add) as vlr_add_final,
               g.nome as grupo_nome
               FROM eiche_hosp_quartos q
               LEFT JOIN eiche_hosp_gruposq g ON q.grupo = g.ID
               ORDER BY q.numero";
$quartosResult = mysqli_query($conexao, $sqlQuartos);
if ($quartosResult) {
    while ($row = mysqli_fetch_assoc($quartosResult)) {
        $quartos[$row['ID']] = $row;
    }
}

// Buscar hospedagens do período
$dataFim = date('Y-m-d', strtotime($dataInicial . ' + ' . ($dias - 1) . ' days'));
$hospedagens = [];
$sql = "SELECT h.ID, h.ID_quarto, h.data, h.tipo, h.rstatus, h.ID_cliente, h.idonly, h.valor_diaria, h.data_inicial, c.razao as cliente_nome 
        FROM eiche_hospedagem h 
        LEFT JOIN eiche_customers c ON h.ID_cliente = c.ID 
        WHERE h.data >= '$dataInicial' AND h.data <= '$dataFim'
        ORDER BY h.data";
$hospResult = mysqli_query($conexao, $sql);
if ($hospResult) {
    while ($row = mysqli_fetch_assoc($hospResult)) {
        $key = $row['ID_quarto'] . '_' . $row['data'];
        $hospedagens[$key] = $row;
    }
}

// Estatísticas
$totalQuartos = count($quartos);

// Buscar IDs dos quartos ocupados hoje
$quartosOcupadosIds = [];
$sqlOcupadosIds = "SELECT DISTINCT ID_quarto FROM eiche_hospedagem WHERE data = '$hoje' AND rstatus = 'A'";
$resOcupadosIds = mysqli_query($conexao, $sqlOcupadosIds);
if ($resOcupadosIds) {
    while ($rowOcup = mysqli_fetch_assoc($resOcupadosIds)) {
        $quartosOcupadosIds[] = $rowOcup['ID_quarto'];
    }
}
$ocupadosHoje = count($quartosOcupadosIds);

$pageTitle = 'Hospedagens - Pousada Bona';

// Função para adicionar dias
function addDays($date, $days) {
    return date('Y-m-d', strtotime($date . ' + ' . $days . ' days'));
}

// JSON dos quartos para JavaScript
$quartosJson = json_encode(array_values($quartos));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
    <style>
        * { box-sizing: border-box; }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .page-title { font-size: 18px; font-weight: 600; margin: 0; }
        .page-subtitle { font-size: 12px; color: #666; margin: 0; }
        
        .stats-row { display: flex; gap: 10px; margin-bottom: 12px; flex-wrap: wrap; }
        .stat-box { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 8px 14px; font-size: 12px; }
        .stat-box strong { font-size: 16px; display: block; color: #333; }
        .stat-box.blue strong { color: #3b82f6; }
        .stat-box.green strong { color: #22c55e; }
        
        .stat-box.filter-btn { cursor: pointer; transition: all 0.2s; }
        .stat-box.filter-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .stat-box.filter-btn.active { border: 2px solid #3b82f6; background: #eff6ff; }
        .stat-box.filter-btn.active.blue { border-color: #3b82f6; background: #dbeafe; }
        .stat-box.filter-btn.active.green { border-color: #22c55e; background: #dcfce7; }
        
        .quarto-row { transition: all 0.3s; }
        .quarto-row.hidden { display: none; }
        
        .legend { display: flex; gap: 15px; margin-bottom: 12px; font-size: 11px; color: #666; flex-wrap: wrap; }
        .legend span { display: flex; align-items: center; gap: 4px; }
        .legend i { width: 14px; height: 14px; border-radius: 3px; display: inline-block; }
        .legend .green { background: #22c55e; }
        .legend .blue { background: #3b82f6; }
        .legend .yellow { background: #f59e0b; }
        .legend .red { background: #ef4444; }
        
        .panel-wrap { background: white; border: 1px solid #ddd; border-radius: 8px; overflow: auto; max-height: calc(100vh - 280px); }
        .grid-table { border-collapse: collapse; min-width: 100%; }
        .grid-table th, .grid-table td { border: 1px solid #e5e7eb; text-align: center; vertical-align: middle; }
        
        .col-room { position: sticky; left: 0; background: #f3f4f6; z-index: 2; width: 70px; min-width: 70px; max-width: 70px; padding: 4px 5px; font-size: 10px; text-align: left; white-space: nowrap; overflow: hidden; }
        .col-room strong { display: block; font-size: 10px; overflow: hidden; text-overflow: ellipsis; }
        .col-room small { color: #888; font-size: 9px; display: block; overflow: hidden; text-overflow: ellipsis; }
        
        .col-date { min-width: 32px; width: 32px; padding: 3px 1px; background: #f9fafb; font-size: 9px; }
        .col-date .dow { display: block; font-size: 9px; color: #999; text-transform: uppercase; }
        .col-date .day { display: block; font-size: 12px; font-weight: 600; }
        .col-date.today { background: #dbeafe; }
        .col-date.today .day { color: #2563eb; }
        .col-date.weekend { background: #fef3c7; }
        
        .cell { width: 32px; height: 28px; cursor: pointer; transition: transform 0.1s; position: relative; }
        .cell:hover { transform: scale(1.2); z-index: 5; }
        .cell.free { background: rgba(34, 197, 94, 0.3); }
        .cell.occupied { background: #3b82f6; }
        .cell.reserved { background: #f59e0b; }
        .cell.checkout { background: #ef4444; }
        .cell.closed { background: #9ca3af; }
        .cell.today-border { box-shadow: inset 0 0 0 2px #1d4ed8; }
        .cell-icon { font-size: 9px; color: white; }
        
        /* MODAL */
        .modal-bg { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center; padding: 20px; }
        .modal-bg.open { display: flex; }
        .modal-content { background: white; border-radius: 10px; width: 100%; max-width: 480px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
        .modal-head { background: #f8f9fa; padding: 14px 18px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 10; }
        .modal-head h3 { margin: 0; font-size: 15px; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #666; line-height: 1; }
        .modal-close:hover { color: #333; }
        .modal-body { padding: 18px; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 18px; }
        .info-item { font-size: 12px; }
        .info-item label { display: block; color: #888; font-size: 10px; margin-bottom: 2px; }
        .info-item span { font-weight: 600; color: #333; }
        
        .action-list { display: flex; flex-direction: column; gap: 8px; }
        .action-btn { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 1px solid #e5e7eb; border-radius: 6px; background: #fafafa; cursor: pointer; font-size: 13px; color: #333; transition: all 0.15s; text-decoration: none; }
        .action-btn:hover { background: #3b82f6; color: white; border-color: #3b82f6; }
        .action-btn.green { border-color: #22c55e; color: #16a34a; }
        .action-btn.green:hover { background: #22c55e; color: white; }
        .action-btn.red { border-color: #ef4444; color: #dc2626; }
        .action-btn.red:hover { background: #ef4444; color: white; }
        .action-btn .icon { font-size: 16px; min-width: 20px; text-align: center; }
        
        /* Formulário */
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 12px; color: #555; margin-bottom: 4px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px; }
        .form-group input:focus, .form-group select:focus { border-color: #3b82f6; outline: none; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
        
        .search-box { position: relative; }
        .search-results { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ddd; border-top: none; border-radius: 0 0 5px 5px; max-height: 200px; overflow-y: auto; z-index: 100; display: none; }
        .search-results.show { display: block; }
        .search-result-item { padding: 10px; cursor: pointer; font-size: 12px; border-bottom: 1px solid #eee; }
        .search-result-item:hover { background: #f0f9ff; }
        .search-result-item .nome { font-weight: 600; }
        .search-result-item .cpf { color: #666; font-size: 11px; }
        .search-result-new { padding: 10px; cursor: pointer; font-size: 12px; background: #f0fdf4; color: #16a34a; font-weight: 500; }
        .search-result-new:hover { background: #dcfce7; }
        
        .cliente-selecionado { background: #f0f9ff; border: 1px solid #3b82f6; border-radius: 5px; padding: 10px; margin-bottom: 14px; display: none; }
        .cliente-selecionado.show { display: block; }
        .cliente-selecionado .nome { font-weight: 600; font-size: 13px; }
        .cliente-selecionado .info { font-size: 11px; color: #666; }
        .cliente-selecionado .btn-trocar { float: right; font-size: 11px; color: #3b82f6; cursor: pointer; }
        
        .valor-info { background: #fef3c7; border: 1px solid #f59e0b; border-radius: 5px; padding: 10px; margin-bottom: 14px; font-size: 12px; }
        .valor-info strong { color: #b45309; }
        
        .btn-submit { width: 100%; padding: 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; }
        .btn-submit:hover { background: #2563eb; }
        .btn-submit.green { background: #22c55e; }
        .btn-submit.green:hover { background: #16a34a; }
        
        .tabs { display: flex; gap: 5px; margin-bottom: 15px; }
        .tab { padding: 8px 16px; border: 1px solid #ddd; border-radius: 5px; cursor: pointer; font-size: 12px; background: #f9fafb; }
        .tab.active { background: #3b82f6; color: white; border-color: #3b82f6; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-wrapper">
        <?php include 'includes/topbar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Painel de Hospedagens</h1>
                    <p class="page-subtitle">Visualize e gerencie a ocupação dos quartos</p>
                </div>
                <button onclick="location.reload()" style="padding: 8px 14px; border: 1px solid #ddd; border-radius: 5px; background: white; cursor: pointer; font-size: 12px;">🔄 Atualizar</button>
            </div>
            
            <div class="stats-row">
                <div class="stat-box filter-btn active" data-filter="todos" onclick="filtrarQuartos('todos')"><strong><?php echo $totalQuartos; ?></strong> Todos</div>
                <div class="stat-box blue filter-btn" data-filter="ocupados" onclick="filtrarQuartos('ocupados')"><strong><?php echo $ocupadosHoje; ?></strong> Ocupados</div>
                <div class="stat-box green filter-btn" data-filter="disponiveis" onclick="filtrarQuartos('disponiveis')"><strong><?php echo $totalQuartos - $ocupadosHoje; ?></strong> Disponíveis</div>
                <div class="stat-box"><strong><?php echo $totalQuartos > 0 ? round(($ocupadosHoje / $totalQuartos) * 100) : 0; ?>%</strong> Ocupação</div>
            </div>
            
            <div class="legend">
                <span><i class="green"></i> Disponível</span>
                <span><i class="blue"></i> Ocupado</span>
                <span><i class="yellow"></i> Reserva</span>
                <span><i class="red"></i> Saída Hoje</span>
            </div>
            
            <div class="panel-wrap">
                <table class="grid-table">
                    <thead>
                        <tr>
                            <th class="col-room">Quarto</th>
                            <?php 
                            $dayNames = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'];
                            for ($i = 0; $i < $dias; $i++): 
                                $currentDate = addDays($dataInicial, $i);
                                $isToday = $currentDate === $hoje;
                                $dow = date('w', strtotime($currentDate));
                                $isWeekend = in_array($dow, [0, 6]);
                            ?>
                            <th class="col-date <?php echo $isToday ? 'today' : ($isWeekend ? 'weekend' : ''); ?>">
                                <span class="dow"><?php echo $dayNames[$dow]; ?></span>
                                <span class="day"><?php echo date('d', strtotime($currentDate)); ?></span>
                            </th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quartos as $quarto): 
                            $estaOcupado = in_array($quarto['ID'], $quartosOcupadosIds);
                        ?>
                        <tr class="quarto-row" data-ocupado="<?php echo $estaOcupado ? '1' : '0'; ?>">
                            <td class="col-room">
                                <strong><?php echo htmlspecialchars($quarto['numero']); ?></strong>
                                <small><?php echo $quarto['grupo_nome'] ?? '-'; ?> | R$ <?php echo number_format($quarto['valor_final'] ?? 0, 0, ',', '.'); ?></small>
                            </td>
                            <?php for ($i = 0; $i < $dias; $i++): 
                                $currentDate = addDays($dataInicial, $i);
                                $isToday = $currentDate === $hoje;
                                $key = $quarto['ID'] . '_' . $currentDate;
                                $hosp = isset($hospedagens[$key]) ? $hospedagens[$key] : null;
                                
                                $cellClass = 'free';
                                $icon = '';
                                $hospId = 0;
                                $clienteNome = '';
                                $status = '';
                                
                                if ($hosp) {
                                    $hospId = $hosp['ID'];
                                    $clienteNome = $hosp['cliente_nome'] ?? 'Cliente';
                                    $status = $hosp['rstatus'] ?? '';
                                    $tipo = $hosp['tipo'] ?? '';
                                    
                                    if ($status === 'A') {
                                        $cellClass = 'occupied';
                                        if ($tipo === 'E') $icon = '▶';
                                        if ($tipo === 'S') {
                                            $icon = '◀';
                                            if ($currentDate === $hoje) $cellClass = 'checkout';
                                        }
                                    } elseif ($status === 'R') {
                                        $cellClass = 'reserved';
                                        if ($tipo === 'E') $icon = '▶';
                                        if ($tipo === 'S') $icon = '◀';
                                    } elseif ($status === 'F') {
                                        $cellClass = 'closed';
                                    }
                                }
                            ?>
                            <td class="cell <?php echo $cellClass; ?> <?php echo $isToday ? 'today-border' : ''; ?>"
                                data-quarto-id="<?php echo $quarto['ID']; ?>"
                                data-quarto-nome="<?php echo htmlspecialchars($quarto['numero']); ?>"
                                data-quarto-ocupantes="<?php echo $quarto['ocupantes']; ?>"
                                data-quarto-valor="<?php echo $quarto['valor_final']; ?>"
                                data-quarto-vlr-ce="<?php echo $quarto['vlr_ce_final']; ?>"
                                data-quarto-vlr-add-base="<?php echo $quarto['vlr_add_base_final']; ?>"
                                data-quarto-vlr-add="<?php echo $quarto['vlr_add_final']; ?>"
                                data-data="<?php echo $currentDate; ?>"
                                data-hosp-id="<?php echo $hospId; ?>"
                                data-cliente="<?php echo htmlspecialchars($clienteNome); ?>"
                                data-status="<?php echo $status; ?>"
                                onclick="abrirModal(this)">
                                <span class="cell-icon"><?php echo $icon; ?></span>
                            </td>
                            <?php endfor; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- MODAL -->
    <div id="modal" class="modal-bg" onclick="fecharModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-head">
                <h3 id="modal-titulo">Informações</h3>
                <button class="modal-close" onclick="fecharModal()">&times;</button>
            </div>
            <div class="modal-body" id="modal-corpo"></div>
        </div>
    </div>

    <script>
    // Dados dos quartos
    var quartosData = <?php echo $quartosJson; ?>;
    var clienteSelecionado = null;
    var quartoAtual = null;
    var filtroAtual = 'todos';
    
    // Função para filtrar quartos
    function filtrarQuartos(filtro) {
        filtroAtual = filtro;
        
        // Atualizar botões
        document.querySelectorAll('.filter-btn').forEach(function(btn) {
            btn.classList.remove('active');
            if (btn.getAttribute('data-filter') === filtro) {
                btn.classList.add('active');
            }
        });
        
        // Filtrar linhas
        document.querySelectorAll('.quarto-row').forEach(function(row) {
            var ocupado = row.getAttribute('data-ocupado') === '1';
            
            if (filtro === 'todos') {
                row.classList.remove('hidden');
            } else if (filtro === 'ocupados') {
                if (ocupado) {
                    row.classList.remove('hidden');
                } else {
                    row.classList.add('hidden');
                }
            } else if (filtro === 'disponiveis') {
                if (!ocupado) {
                    row.classList.remove('hidden');
                } else {
                    row.classList.add('hidden');
                }
            }
        });
    }
    
    function abrirModal(cell) {
        var quartoId = cell.getAttribute('data-quarto-id');
        var quartoNome = cell.getAttribute('data-quarto-nome');
        var quartoOcupantes = parseInt(cell.getAttribute('data-quarto-ocupantes')) || 1;
        var quartoValor = parseFloat(cell.getAttribute('data-quarto-valor')) || 0;
        var quartoVlrCe = parseFloat(cell.getAttribute('data-quarto-vlr-ce')) || 0;
        var quartoVlrAddBase = parseFloat(cell.getAttribute('data-quarto-vlr-add-base')) || 0;
        var quartoVlrAdd = parseFloat(cell.getAttribute('data-quarto-vlr-add')) || 0;
        var data = cell.getAttribute('data-data');
        var hospId = cell.getAttribute('data-hosp-id');
        var cliente = cell.getAttribute('data-cliente');
        var status = cell.getAttribute('data-status');
        
        quartoAtual = {
            id: quartoId,
            nome: quartoNome,
            ocupantes: quartoOcupantes,
            valor: quartoValor,
            vlrCe: quartoVlrCe,
            vlrAddBase: quartoVlrAddBase,
            vlrAdd: quartoVlrAdd
        };
        
        var modal = document.getElementById('modal');
        var titulo = document.getElementById('modal-titulo');
        var corpo = document.getElementById('modal-corpo');
        
        var partes = data.split('-');
        var dataFormatada = partes[2] + '/' + partes[1] + '/' + partes[0];
        
        clienteSelecionado = null;
        
        if (hospId && hospId !== '0' && hospId !== '') {
            // HOSPEDAGEM EXISTENTE
            var statusTexto = 'Ativo';
            if (status === 'R') statusTexto = 'Reserva';
            if (status === 'F') statusTexto = 'Fechado';
            
            titulo.innerHTML = '🏨 Hospedagem #' + hospId.toString().padStart(6, '0');
            
            var html = '<div class="info-grid">' +
                '<div class="info-item"><label>Cliente</label><span>' + (cliente || '-') + '</span></div>' +
                '<div class="info-item"><label>Quarto</label><span>' + quartoNome + '</span></div>' +
                '<div class="info-item"><label>Data</label><span>' + dataFormatada + '</span></div>' +
                '<div class="info-item"><label>Status</label><span>' + statusTexto + '</span></div>' +
            '</div><div class="action-list">';
            
            if (status === 'A') {
                html += '<button class="action-btn green" onclick="acaoCheckout(' + hospId + ')"><span class="icon">✅</span> Fazer Check-out</button>';
                html += '<button class="action-btn" onclick="acaoConsumo(' + hospId + ')"><span class="icon">🍽️</span> Adicionar Consumo</button>';
                html += '<button class="action-btn" onclick="acaoServico(' + hospId + ')"><span class="icon">🛎️</span> Adicionar Serviço</button>';
                html += '<button class="action-btn" onclick="acaoDesconto(' + hospId + ')"><span class="icon">💰</span> Aplicar Desconto</button>';
                html += '<button class="action-btn" onclick="acaoDetalhes(' + hospId + ')"><span class="icon">📋</span> Ver Detalhes</button>';
                html += '<button class="action-btn" onclick="acaoEditar(' + hospId + ')"><span class="icon">✏️</span> Editar Dados</button>';
            } else if (status === 'R') {
                html += '<button class="action-btn green" onclick="acaoCheckin(' + hospId + ')"><span class="icon">✅</span> Fazer Check-in</button>';
                html += '<button class="action-btn" onclick="acaoDetalhes(' + hospId + ')"><span class="icon">📋</span> Ver Detalhes</button>';
                html += '<button class="action-btn" onclick="acaoEditar(' + hospId + ')"><span class="icon">✏️</span> Editar Dados</button>';
                html += '<button class="action-btn red" onclick="acaoExcluir(' + hospId + ')"><span class="icon">🗑️</span> Cancelar Reserva</button>';
            } else if (status === 'F') {
                html += '<button class="action-btn" onclick="acaoDetalhes(' + hospId + ')"><span class="icon">📋</span> Ver Detalhes / Recibo</button>';
                html += '<button class="action-btn" onclick="acaoDesconto(' + hospId + ')"><span class="icon">💰</span> Aplicar Desconto</button>';
                html += '<button class="action-btn" onclick="acaoEditar(' + hospId + ')"><span class="icon">✏️</span> Editar Dados</button>';
                html += '<button class="action-btn" onclick="acaoReabrir(' + hospId + ')"><span class="icon">🔄</span> Reabrir Hospedagem</button>';
                html += '<button class="action-btn red" onclick="acaoExcluirPermanente(' + hospId + ')"><span class="icon">🗑️</span> Excluir Permanente</button>';
            } else {
                html += '<button class="action-btn" onclick="acaoDetalhes(' + hospId + ')"><span class="icon">📋</span> Ver Detalhes</button>';
            }
            
            html += '</div>';
            corpo.innerHTML = html;
        } else {
            // NOVA HOSPEDAGEM/RESERVA
            titulo.innerHTML = '✨ Nova Hospedagem / Reserva';
            
            var html = '<div class="tabs">' +
                '<div class="tab active" onclick="trocarTab(\'hospedagem\')">🏨 Hospedagem</div>' +
                '<div class="tab" onclick="trocarTab(\'reserva\')">📅 Reserva</div>' +
            '</div>' +
            
            '<div class="info-grid" style="margin-bottom: 14px;">' +
                '<div class="info-item"><label>Quarto</label><span>' + quartoNome + '</span></div>' +
                '<div class="info-item"><label>Capacidade</label><span>' + quartoOcupantes + ' pessoa(s)</span></div>' +
            '</div>' +
            
            '<div class="valor-info">' +
                '<strong>💰 Valor base:</strong> R$ ' + quartoValor.toFixed(2).replace('.', ',') + '/diária' +
                (quartoVlrAddBase > 0 ? ' | +R$ ' + quartoVlrAddBase.toFixed(2).replace('.', ',') + ' por hóspede adicional' : '') +
            '</div>' +
            
            '<form id="form-nova" onsubmit="salvarHospedagem(event)">' +
                '<input type="hidden" name="quarto_id" value="' + quartoId + '">' +
                '<input type="hidden" name="cliente_id" id="cliente_id" value="">' +
                '<input type="hidden" name="tipo" id="tipo_hospedagem" value="hospedagem">' +
                
                '<!-- Cliente -->' +
                '<div class="form-group">' +
                    '<label>👤 Cliente *</label>' +
                    '<div id="cliente-selecionado" class="cliente-selecionado">' +
                        '<span class="btn-trocar" onclick="limparCliente()">✕ Trocar</span>' +
                        '<div class="nome" id="cliente-nome"></div>' +
                        '<div class="info" id="cliente-info"></div>' +
                    '</div>' +
                    '<div id="cliente-busca" class="search-box">' +
                        '<input type="text" id="busca-cliente" placeholder="Digite nome ou CPF para buscar..." oninput="buscarCliente(this.value)" autocomplete="off">' +
                        '<div id="search-results" class="search-results"></div>' +
                    '</div>' +
                '</div>' +
                
                '<!-- Datas e Hóspedes -->' +
                '<div class="form-row-3">' +
                    '<div class="form-group">' +
                        '<label>📅 Entrada *</label>' +
                        '<input type="date" name="data_entrada" id="data_entrada" value="' + data + '" required onchange="calcularValor()">' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label>📅 Saída *</label>' +
                        '<input type="date" name="data_saida" id="data_saida" required min="' + data + '" onchange="calcularValor()">' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label>👥 Hóspedes *</label>' +
                        '<select name="num_hospedes" id="num_hospedes" required onchange="calcularValor()">';
            
            for (var i = 1; i <= Math.max(quartoOcupantes, 10); i++) {
                html += '<option value="' + i + '"' + (i === 1 ? ' selected' : '') + '>' + i + '</option>';
            }
            
            html += '</select>' +
                    '</div>' +
                '</div>' +
                
                '<div class="form-row">' +
                    '<div class="form-group">' +
                        '<label>🛏️ Cama Extra?</label>' +
                        '<select name="cama_extra" id="cama_extra" onchange="calcularValor()">' +
                            '<option value="N">Não</option>' +
                            '<option value="S">Sim (+R$ ' + quartoVlrCe.toFixed(2).replace('.', ',') + ')</option>' +
                        '</select>' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label>💵 Valor Diária (R$)</label>' +
                        '<input type="number" name="valor_diaria" id="valor_diaria" step="0.01" value="' + quartoValor.toFixed(2) + '">' +
                    '</div>' +
                '</div>' +
                
                '<button type="submit" class="btn-submit green" id="btn-salvar">💾 Salvar Hospedagem</button>' +
            '</form>';
            
            corpo.innerHTML = html;
        }
        
        modal.classList.add('open');
    }
    
    function trocarTab(tipo) {
        var tabs = document.querySelectorAll('.tab');
        tabs.forEach(function(t) { t.classList.remove('active'); });
        event.target.classList.add('active');
        
        document.getElementById('tipo_hospedagem').value = tipo;
        var btn = document.getElementById('btn-salvar');
        if (tipo === 'reserva') {
            btn.innerHTML = '💾 Salvar Reserva';
            btn.classList.remove('green');
        } else {
            btn.innerHTML = '💾 Salvar Hospedagem';
            btn.classList.add('green');
        }
    }
    
    function calcularValor() {
        if (!quartoAtual) return;
        
        var numHospedes = parseInt(document.getElementById('num_hospedes').value) || 1;
        var camaExtra = document.getElementById('cama_extra').value;
        
        var valor = quartoAtual.valor;
        
        // Adicional cama extra
        if (camaExtra === 'S') {
            valor += quartoAtual.vlrCe;
        }
        
        // Adicional por hóspede (fórmula do sistema antigo)
        if (numHospedes > 1) {
            valor += quartoAtual.vlrAddBase * (numHospedes - 1);
            if (numHospedes > 2) {
                valor += quartoAtual.vlrAdd * (numHospedes - 2);
            }
        }
        
        document.getElementById('valor_diaria').value = valor.toFixed(2);
    }
    
    var buscaTimeout = null;
    function buscarCliente(termo) {
        clearTimeout(buscaTimeout);
        var results = document.getElementById('search-results');
        
        if (termo.length < 2) {
            results.classList.remove('show');
            return;
        }
        
        buscaTimeout = setTimeout(function() {
            fetch('buscar-cliente.php?termo=' + encodeURIComponent(termo))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var html = '';
                    if (data.length > 0) {
                        data.forEach(function(c) {
                            html += '<div class="search-result-item" onclick="selecionarCliente(' + c.ID + ', \'' + escapeHtml(c.razao) + '\', \'' + escapeHtml(c.cpf_cnpj || '') + '\', \'' + escapeHtml(c.telefone || '') + '\')">' +
                                '<div class="nome">' + c.razao + '</div>' +
                                '<div class="cpf">' + (c.cpf_cnpj || 'Sem CPF') + ' | ' + (c.telefone || 'Sem telefone') + '</div>' +
                            '</div>';
                        });
                    }
                    html += '<div class="search-result-new" onclick="novoCliente()">➕ Cadastrar novo cliente</div>';
                    results.innerHTML = html;
                    results.classList.add('show');
                });
        }, 300);
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/'/g, "\\'").replace(/"/g, '\\"');
    }
    
    function selecionarCliente(id, nome, cpf, telefone) {
        clienteSelecionado = { id: id, nome: nome, cpf: cpf, telefone: telefone };
        document.getElementById('cliente_id').value = id;
        document.getElementById('cliente-nome').textContent = nome;
        document.getElementById('cliente-info').textContent = (cpf || 'Sem CPF') + ' | ' + (telefone || 'Sem telefone');
        document.getElementById('cliente-selecionado').classList.add('show');
        document.getElementById('cliente-busca').style.display = 'none';
        document.getElementById('search-results').classList.remove('show');
    }
    
    function limparCliente() {
        clienteSelecionado = null;
        document.getElementById('cliente_id').value = '';
        document.getElementById('cliente-selecionado').classList.remove('show');
        document.getElementById('cliente-busca').style.display = 'block';
        document.getElementById('busca-cliente').value = '';
    }
    
    function novoCliente() {
        document.getElementById('search-results').classList.remove('show');
        window.location.href = 'novo-cliente.php?retorno=reservations';
    }
    
    function fecharModal(event) {
        if (event && event.target !== event.currentTarget) return;
        document.getElementById('modal').classList.remove('open');
    }
    
    // AÇÕES
    function acaoCheckout(hospId) {
        if (confirm('Confirma o CHECK-OUT da hospedagem #' + hospId + '?')) {
            window.location.href = 'acao-hospedagem.php?acao=checkout&id=' + hospId;
        }
    }
    
    function acaoCheckin(hospId) {
        if (confirm('Confirma o CHECK-IN da reserva #' + hospId + '?')) {
            window.location.href = 'acao-hospedagem.php?acao=checkin&id=' + hospId;
        }
    }
    
    function acaoConsumo(hospId) { window.location.href = 'consumo.php?hosp_id=' + hospId; }
    function acaoServico(hospId) { window.location.href = 'servico.php?hosp_id=' + hospId; }
    function acaoDetalhes(hospId) { window.location.href = 'hospedagem-detalhes.php?id=' + hospId; }
    function acaoEditar(hospId) { window.location.href = 'editar-hospedagem.php?id=' + hospId; }
    function acaoDesconto(hospId) { window.location.href = 'desconto-hospedagem.php?id=' + hospId; }
    
    function acaoExcluir(hospId) {
        if (confirm('Tem certeza que deseja CANCELAR a reserva #' + hospId + '?')) {
            window.location.href = 'acao-hospedagem.php?acao=excluir&id=' + hospId;
        }
    }
    
    function acaoReabrir(hospId) {
        if (confirm('Deseja REABRIR a hospedagem #' + hospId + '?')) {
            window.location.href = 'acao-hospedagem.php?acao=reabrir&id=' + hospId;
        }
    }
    
    function acaoExcluirPermanente(hospId) {
        if (confirm('ATENÇÃO: Isso irá EXCLUIR permanentemente a hospedagem #' + hospId + ' e todos os seus registros (consumos, serviços, etc).\n\nDeseja continuar?')) {
            window.location.href = 'acao-hospedagem.php?acao=excluir&id=' + hospId;
        }
    }
    
    function salvarHospedagem(event) {
        event.preventDefault();
        
        if (!document.getElementById('cliente_id').value) {
            alert('Selecione um cliente!');
            return;
        }
        
        var form = document.getElementById('form-nova');
        var formData = new FormData(form);
        
        fetch('salvar-hospedagem.php', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                alert('Salvo com sucesso!');
                location.reload();
            } else {
                alert('Erro: ' + (data.error || 'Erro desconhecido'));
            }
        })
        .catch(function(error) {
            alert('Erro ao salvar: ' + error);
        });
    }
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') fecharModal();
    });
    
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-box')) {
            var results = document.getElementById('search-results');
            if (results) results.classList.remove('show');
        }
    });
    </script>

<?php 
mysqli_close($conexao);
include 'includes/footer.php'; 
?>
</body>
</html>
