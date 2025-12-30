<?php
/**
 * Pousada Bona - Relatórios
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

// Relatórios que requerem permissão de valores
$relatoriosFinanceiros = ['receita', 'receita_quarto', 'consumos', 'despesas', 'lucro'];

$userName = $_SESSION['user_name'] ?? 'Usuário';

// Tipo de relatório
$tipo = $_GET['tipo'] ?? '';
$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
$dataFim = $_GET['data_fim'] ?? date('Y-m-d');

$pageTitle = 'Relatórios - Pousada Bona';

// Meses em português
$meses = [1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',
          7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'];

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>
    
    <main class="main-content">
        <?php if (empty($tipo)): ?>
        <!-- Tela inicial de seleção de relatórios -->
        <div class="content-header">
            <div class="content-header-left">
                <h1>📊 Relatórios</h1>
                <p>Selecione o tipo de relatório que deseja gerar</p>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            
            <!-- Relatórios de Hospedagens -->
            <div class="card">
                <div class="card-header" style="background: #dbeafe;">
                    <h3 style="color: #1e40af;">📅 Hospedagens</h3>
                </div>
                <div class="card-body" style="padding: 0;">
                    <a href="?tipo=hospedagens" class="report-item">
                        <span>📋 Hospedagens por Período</span>
                        <span>→</span>
                    </a>
                    <a href="?tipo=checkouts" class="report-item">
                        <span>🚪 Check-outs Realizados</span>
                        <span>→</span>
                    </a>
                    <a href="?tipo=ocupacao" class="report-item">
                        <span>📈 Taxa de Ocupação</span>
                        <span>→</span>
                    </a>
                </div>
            </div>
            
            <!-- Relatórios Financeiros -->
            <div class="card">
                <div class="card-header" style="background: #dcfce7;">
                    <h3 style="color: #166534;">💰 Financeiro <?php if (!$podeVerValores): ?><span style="font-size: 12px; background: #fee2e2; color: #dc2626; padding: 2px 8px; border-radius: 4px;">🔒 Restrito</span><?php endif; ?></h3>
                </div>
                <div class="card-body" style="padding: 0;">
                    <?php if ($podeVerValores): ?>
                    <a href="?tipo=receita" class="report-item">
                        <span>💵 Receita por Período</span>
                        <span>→</span>
                    </a>
                    <a href="?tipo=receita_quarto" class="report-item">
                        <span>🛏️ Receita por Quarto</span>
                        <span>→</span>
                    </a>
                    <a href="?tipo=consumos" class="report-item">
                        <span>🛎️ Serviços e Produtos</span>
                        <span>→</span>
                    </a>
                    <a href="?tipo=despesas" class="report-item">
                        <span>💸 Despesas</span>
                        <span>→</span>
                    </a>
                    <a href="?tipo=lucro" class="report-item">
                        <span>📈 Lucro (Receita - Despesas)</span>
                        <span>→</span>
                    </a>
                    <?php else: ?>
                    <div style="padding: 20px; text-align: center; color: #9ca3af;">
                        <div style="font-size: 24px; margin-bottom: 8px;">🔒</div>
                        <div style="font-size: 13px;">Você não tem permissão para acessar relatórios financeiros.</div>
                        <div style="font-size: 11px; margin-top: 4px;">Contate o administrador.</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Relatórios de Clientes -->
            <div class="card">
                <div class="card-header" style="background: #fef3c7;">
                    <h3 style="color: #92400e;">👥 Clientes</h3>
                </div>
                <div class="card-body" style="padding: 0;">
                    <a href="?tipo=clientes" class="report-item">
                        <span>📋 Lista de Clientes</span>
                        <span>→</span>
                    </a>
                    <a href="?tipo=clientes_frequentes" class="report-item">
                        <span>⭐ Clientes Frequentes</span>
                        <span>→</span>
                    </a>
                    <a href="?tipo=aniversariantes" class="report-item">
                        <span>🎂 Aniversariantes</span>
                        <span>→</span>
                    </a>
                </div>
            </div>
            
            <!-- Relatórios de Quartos -->
            <div class="card">
                <div class="card-header" style="background: #fee2e2;">
                    <h3 style="color: #991b1b;">🏨 Quartos</h3>
                </div>
                <div class="card-body" style="padding: 0;">
                    <a href="?tipo=quartos" class="report-item">
                        <span>🛏️ Lista de Quartos</span>
                        <span>→</span>
                    </a>
                    <a href="?tipo=quartos_performance" class="report-item">
                        <span>📊 Performance por Quarto</span>
                        <span>→</span>
                    </a>
                </div>
            </div>
            
        </div>
        
        <?php else: ?>
        
        <?php 
        // Verificar permissão para relatórios financeiros
        if (in_array($tipo, $relatoriosFinanceiros) && !$podeVerValores): 
        ?>
        <div class="card" style="margin-top: 20px;">
            <div class="card-body" style="text-align: center; padding: 60px;">
                <div style="font-size: 60px; margin-bottom: 20px;">🔒</div>
                <h2 style="color: #6b7280;">Acesso Restrito</h2>
                <p style="color: #9ca3af;">Você não tem permissão para visualizar relatórios financeiros.</p>
                <p style="color: #9ca3af; font-size: 13px;">Contate o administrador para obter acesso.</p>
                <a href="reports.php" class="btn btn-primary" style="margin-top: 20px;">← Voltar aos Relatórios</a>
            </div>
        </div>
        <?php else: ?>
        
        <!-- Relatório específico -->
        <div class="content-header no-print">
            <div class="content-header-left">
                <a href="reports.php" style="color: #666; text-decoration: none; font-size: 13px;">← Voltar aos Relatórios</a>
                <h1 style="margin-top: 5px;">
                    <?php
                    $titulos = [
                        'hospedagens' => '📅 Hospedagens por Período',
                        'checkouts' => '🚪 Check-outs Realizados',
                        'ocupacao' => '📈 Taxa de Ocupação',
                        'receita' => '💵 Receita por Período',
                        'receita_quarto' => '🛏️ Receita por Quarto',
                        'consumos' => '🛎️ Serviços e Produtos',
                        'despesas' => '💸 Despesas',
                        'lucro' => '📈 Lucro (Receita - Despesas)',
                        'clientes' => '👥 Lista de Clientes',
                        'clientes_frequentes' => '⭐ Clientes Frequentes',
                        'aniversariantes' => '🎂 Aniversariantes',
                        'quartos' => '🛏️ Lista de Quartos',
                        'quartos_performance' => '📊 Performance por Quarto'
                    ];
                    echo $titulos[$tipo] ?? 'Relatório';
                    ?>
                </h1>
            </div>
            <div class="content-header-actions">
                <button onclick="window.print()" class="btn btn-primary">
                    🖨️ Imprimir
                </button>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="card no-print" style="margin-bottom: 20px;">
            <div class="card-body" style="padding: 15px;">
                <form method="GET" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                    <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
                    <div>
                        <label style="display: block; font-size: 11px; color: #666; margin-bottom: 4px;">📅 Data Início</label>
                        <input type="date" name="data_inicio" value="<?= $dataInicio ?>" class="form-input" style="padding: 8px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; color: #666; margin-bottom: 4px;">📅 Data Fim</label>
                        <input type="date" name="data_fim" value="<?= $dataFim ?>" class="form-input" style="padding: 8px;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 20px;">🔍 Filtrar</button>
                </form>
            </div>
        </div>
        
        <!-- Cabeçalho de impressão -->
        <div class="print-header" style="display: none;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h2 style="margin: 0;">POUSADA BONA</h2>
                <p style="margin: 5px 0; font-size: 14px;"><?= $titulos[$tipo] ?? 'Relatório' ?></p>
                <p style="margin: 0; font-size: 12px; color: #666;">
                    Período: <?= date('d/m/Y', strtotime($dataInicio)) ?> a <?= date('d/m/Y', strtotime($dataFim)) ?>
                </p>
            </div>
        </div>
        
        <!-- Conteúdo do Relatório -->
        <div class="card report-content">
            <div class="card-body">
                <?php
                // =========================================
                // RELATÓRIO: HOSPEDAGENS POR PERÍODO
                // =========================================
                if ($tipo === 'hospedagens'):
                    $sql = "SELECT h.ID, MIN(h.data) as entrada, MAX(h.data) as saida, 
                                   h.valor_diaria, h.rstatus, COUNT(*) as num_diarias,
                                   c.razao as cliente, q.numero as quarto
                            FROM eiche_hospedagem h
                            LEFT JOIN eiche_customers c ON h.ID_cliente = c.ID
                            LEFT JOIN eiche_hosp_quartos q ON h.ID_quarto = q.ID
                            WHERE h.data >= '$dataInicio' AND h.data <= '$dataFim'
                            GROUP BY h.ID
                            ORDER BY entrada DESC";
                    $result = mysqli_query($conexao, $sql);
                    $total = 0;
                ?>
                <table class="table" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Quarto</th>
                            <th>Entrada</th>
                            <th>Saída</th>
                            <th>Diárias</th>
                            <th>Valor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): 
                            $valor = ($row['valor_diaria'] ?? 0) * ($row['num_diarias'] ?? 1);
                            $total += $valor;
                        ?>
                        <tr>
                            <td>#<?= str_pad($row['ID'], 6, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars(substr($row['cliente'] ?? '-', 0, 30)) ?></td>
                            <td><span style="background: #dbeafe; padding: 2px 6px; border-radius: 4px;"><?= $row['quarto'] ?></span></td>
                            <td><?= date('d/m/Y', strtotime($row['entrada'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($row['saida'])) ?></td>
                            <td style="text-align: center;"><?= $row['num_diarias'] ?></td>
                            <td style="text-align: right;">R$ <?= number_format($valor, 2, ',', '.') ?></td>
                            <td>
                                <?php 
                                $statusLabels = ['A'=>'Ativo','R'=>'Reserva','F'=>'Finalizado'];
                                $statusColors = ['A'=>'#dbeafe','R'=>'#fef3c7','F'=>'#dcfce7'];
                                echo '<span style="background:'.$statusColors[$row['rstatus']].'; padding: 2px 6px; border-radius: 4px; font-size: 10px;">'.$statusLabels[$row['rstatus']].'</span>';
                                ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr style="font-weight: bold; background: #f9fafb;">
                            <td colspan="6">TOTAL</td>
                            <td style="text-align: right;">R$ <?= number_format($total, 2, ',', '.') ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                
                <?php 
                // =========================================
                // RELATÓRIO: CHECK-OUTS REALIZADOS
                // =========================================
                elseif ($tipo === 'checkouts'):
                    $sql = "SELECT h.ID, MIN(h.data) as entrada, MAX(h.data) as saida, 
                                   h.valor_diaria, h.lg_checkout, COUNT(*) as num_diarias,
                                   c.razao as cliente, q.numero as quarto
                            FROM eiche_hospedagem h
                            LEFT JOIN eiche_customers c ON h.ID_cliente = c.ID
                            LEFT JOIN eiche_hosp_quartos q ON h.ID_quarto = q.ID
                            WHERE h.rstatus = 'F' AND h.data >= '$dataInicio' AND h.data <= '$dataFim'
                            GROUP BY h.ID
                            ORDER BY h.lg_checkout DESC";
                    $result = mysqli_query($conexao, $sql);
                    $total = 0;
                    $qtd = 0;
                ?>
                <table class="table" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Quarto</th>
                            <th>Entrada</th>
                            <th>Saída</th>
                            <th>Check-out</th>
                            <th>Valor Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): 
                            $valor = ($row['valor_diaria'] ?? 0) * ($row['num_diarias'] ?? 1);
                            $total += $valor;
                            $qtd++;
                        ?>
                        <tr>
                            <td>#<?= str_pad($row['ID'], 6, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars(substr($row['cliente'] ?? '-', 0, 30)) ?></td>
                            <td><?= $row['quarto'] ?></td>
                            <td><?= date('d/m/Y', strtotime($row['entrada'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($row['saida'])) ?></td>
                            <td><?= $row['lg_checkout'] ? date('d/m/Y H:i', strtotime($row['lg_checkout'])) : '-' ?></td>
                            <td style="text-align: right; font-weight: 600;">R$ <?= number_format($valor, 2, ',', '.') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr style="font-weight: bold; background: #f9fafb;">
                            <td colspan="6">TOTAL (<?= $qtd ?> check-outs)</td>
                            <td style="text-align: right;">R$ <?= number_format($total, 2, ',', '.') ?></td>
                        </tr>
                    </tfoot>
                </table>
                
                <?php 
                // =========================================
                // RELATÓRIO: TAXA DE OCUPAÇÃO
                // =========================================
                elseif ($tipo === 'ocupacao'):
                    $diasPeriodo = max(1, (strtotime($dataFim) - strtotime($dataInicio)) / 86400 + 1);
                    
                    // Total de quartos
                    $sqlQuartos = "SELECT COUNT(*) as total FROM eiche_hosp_quartos";
                    $totalQuartos = mysqli_fetch_assoc(mysqli_query($conexao, $sqlQuartos))['total'] ?? 1;
                    
                    // Ocupação por dia no período
                    $sqlOcupacao = "SELECT DATE(data) as dia, COUNT(DISTINCT ID_quarto) as quartos_ocupados
                                    FROM eiche_hospedagem 
                                    WHERE data >= '$dataInicio' AND data <= '$dataFim' 
                                    AND rstatus IN ('A', 'F')
                                    GROUP BY DATE(data)
                                    ORDER BY dia";
                    $result = mysqli_query($conexao, $sqlOcupacao);
                    
                    $totalDiasOcupados = 0;
                    $dadosPorDia = [];
                    while ($row = mysqli_fetch_assoc($result)) {
                        $dadosPorDia[] = $row;
                        $totalDiasOcupados += $row['quartos_ocupados'];
                    }
                    
                    // Taxa média
                    $totalPossivel = $diasPeriodo * $totalQuartos;
                    $taxaMedia = ($totalPossivel > 0) ? ($totalDiasOcupados / $totalPossivel) * 100 : 0;
                ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 25px;">
                    <div style="background: #dbeafe; padding: 20px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 11px; color: #1e40af;">Total de Quartos</div>
                        <div style="font-size: 28px; font-weight: 700; color: #1e40af;"><?= $totalQuartos ?></div>
                    </div>
                    <div style="background: #fef3c7; padding: 20px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 11px; color: #92400e;">Dias no Período</div>
                        <div style="font-size: 28px; font-weight: 700; color: #92400e;"><?= (int)$diasPeriodo ?></div>
                    </div>
                    <div style="background: #dcfce7; padding: 20px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 11px; color: #166534;">Diárias Ocupadas</div>
                        <div style="font-size: 28px; font-weight: 700; color: #166534;"><?= $totalDiasOcupados ?></div>
                    </div>
                    <div style="background: <?= $taxaMedia >= 60 ? '#166534' : ($taxaMedia >= 30 ? '#f59e0b' : '#dc2626') ?>; padding: 20px; border-radius: 8px; text-align: center; color: white;">
                        <div style="font-size: 11px; opacity: 0.9;">Taxa de Ocupação</div>
                        <div style="font-size: 28px; font-weight: 700;"><?= number_format($taxaMedia, 1) ?>%</div>
                    </div>
                </div>
                
                <h4 style="margin-bottom: 10px;">📊 Ocupação por Dia</h4>
                <table class="table" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Dia da Semana</th>
                            <th style="text-align: center;">Quartos Ocupados</th>
                            <th>Taxa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $diasSemana = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'];
                        foreach ($dadosPorDia as $row): 
                            $taxa = ($totalQuartos > 0) ? ($row['quartos_ocupados'] / $totalQuartos) * 100 : 0;
                            $diaSemana = $diasSemana[date('w', strtotime($row['dia']))];
                        ?>
                        <tr>
                            <td><strong><?= date('d/m/Y', strtotime($row['dia'])) ?></strong></td>
                            <td><?= $diaSemana ?></td>
                            <td style="text-align: center;"><?= $row['quartos_ocupados'] ?> / <?= $totalQuartos ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="flex: 1; background: #e5e7eb; height: 10px; border-radius: 5px; max-width: 150px;">
                                        <div style="width: <?= min(100, $taxa) ?>%; background: <?= $taxa >= 70 ? '#22c55e' : ($taxa >= 40 ? '#f59e0b' : '#ef4444') ?>; height: 100%; border-radius: 5px;"></div>
                                    </div>
                                    <span style="font-size: 11px; font-weight: 600;"><?= number_format($taxa, 0) ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php 
                // =========================================
                // RELATÓRIO: CONSUMOS E SERVIÇOS
                // =========================================
                elseif ($tipo === 'consumos'):
                    // Consumos no período (SEM duplicação - usando subqueries para dados da hospedagem)
                    $sqlConsumos = "SELECT c.id, c.ID_hosp, c.ID_cons, c.qtd, c.valor_unit,
                                           p.description as produto, 
                                           c.data as data_cons,
                                           (c.valor_unit * c.qtd) as valor_total,
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
                                    LIMIT 200";
                    $resultCons = mysqli_query($conexao, $sqlConsumos);
                    
                    // Serviços no período (SEM duplicação)
                    $sqlServicos = "SELECT s.id, s.ID_hosp, s.ID_serv, s.qtd, s.valor_unit,
                                           p.description as servico, 
                                           s.data as data_serv,
                                           (s.valor_unit * s.qtd) as valor_total,
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
                                    LIMIT 200";
                    $resultServ = mysqli_query($conexao, $sqlServicos);
                    
                    // Totais (sem duplicação)
                    $sqlTotalCons = "SELECT SUM(c.valor_unit * c.qtd) as total, COUNT(DISTINCT c.id) as qtd
                                     FROM eiche_hosp_lnk_cons_hosp c
                                     WHERE c.data >= '$dataInicio' AND c.data <= '$dataFim'";
                    $rowCons = mysqli_fetch_assoc(mysqli_query($conexao, $sqlTotalCons));
                    $totalConsumos = $rowCons['total'] ?? 0;
                    $qtdConsumos = $rowCons['qtd'] ?? 0;
                    
                    $sqlTotalServ = "SELECT SUM(s.valor_unit * s.qtd) as total, COUNT(DISTINCT s.id) as qtd
                                     FROM eiche_hosp_lnk_serv_hosp s
                                     WHERE s.data >= '$dataInicio' AND s.data <= '$dataFim'";
                    $rowServ = mysqli_fetch_assoc(mysqli_query($conexao, $sqlTotalServ));
                    $totalServicos = $rowServ['total'] ?? 0;
                    $qtdServicos = $rowServ['qtd'] ?? 0;
                ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
                    <div style="background: #fef3c7; padding: 20px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 11px; color: #92400e;">🛎️ Serviços</div>
                        <div style="font-size: 24px; font-weight: 700; color: #92400e;">R$ <?= number_format($totalConsumos, 2, ',', '.') ?></div>
                        <div style="font-size: 11px; color: #92400e;"><?= $qtdConsumos ?> registros</div>
                    </div>
                    <div style="background: #dbeafe; padding: 20px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 11px; color: #1e40af;">🛍️ Produtos</div>
                        <div style="font-size: 24px; font-weight: 700; color: #1e40af;">R$ <?= number_format($totalServicos, 2, ',', '.') ?></div>
                        <div style="font-size: 11px; color: #1e40af;"><?= $qtdServicos ?> registros</div>
                    </div>
                    <div style="background: #166534; padding: 20px; border-radius: 8px; text-align: center; color: white;">
                        <div style="font-size: 11px; opacity: 0.9;">💰 Total</div>
                        <div style="font-size: 24px; font-weight: 700;">R$ <?= number_format($totalConsumos + $totalServicos, 2, ',', '.') ?></div>
                    </div>
                </div>
                
                <!-- Consumos -->
                <h4 style="margin: 20px 0 10px;">🛎️ Serviços (<?= $qtdConsumos ?>)</h4>
                <table class="table" style="font-size: 11px; margin-bottom: 30px;">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Produto</th>
                            <th>Quarto</th>
                            <th>Cliente</th>
                            <th style="text-align: center;">Qtd</th>
                            <th style="text-align: right;">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($resultCons)): ?>
                        <tr>
                            <td><?= $row['data_cons'] ? date('d/m/Y', strtotime($row['data_cons'])) : '-' ?></td>
                            <td><strong><?= htmlspecialchars(substr($row['produto'] ?? $row['descricao'] ?? 'Consumo', 0, 30)) ?></strong></td>
                            <td><?= $row['quarto'] ?? '-' ?></td>
                            <td><?= htmlspecialchars(substr($row['cliente'] ?? '-', 0, 20)) ?></td>
                            <td style="text-align: center;"><?= $row['qtd'] ?? 1 ?></td>
                            <td style="text-align: right;">R$ <?= number_format($row['valor_total'] ?? 0, 2, ',', '.') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <!-- Serviços -->
                <h4 style="margin: 20px 0 10px;">🛍️ Produtos (<?= $qtdServicos ?>)</h4>
                <table class="table" style="font-size: 11px;">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Serviço</th>
                            <th>Quarto</th>
                            <th>Cliente</th>
                            <th style="text-align: center;">Qtd</th>
                            <th style="text-align: right;">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($resultServ)): ?>
                        <tr>
                            <td><?= $row['data_serv'] ? date('d/m/Y', strtotime($row['data_serv'])) : '-' ?></td>
                            <td><strong><?= htmlspecialchars(substr($row['servico'] ?? 'Serviço', 0, 30)) ?></strong></td>
                            <td><?= $row['quarto'] ?? '-' ?></td>
                            <td><?= htmlspecialchars(substr($row['cliente'] ?? '-', 0, 20)) ?></td>
                            <td style="text-align: center;"><?= $row['qtd'] ?? 1 ?></td>
                            <td style="text-align: right;">R$ <?= number_format($row['valor_total'] ?? 0, 2, ',', '.') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <?php 
                // =========================================
                // RELATÓRIO: DESPESAS
                // =========================================
                elseif ($tipo === 'despesas'):
                    // Verificar se tabela existe
                    $checkTable = mysqli_query($conexao, "SHOW TABLES LIKE 'eiche_despesas'");
                    if (mysqli_num_rows($checkTable) == 0):
                ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    <p>⚠️ Módulo de despesas não configurado.</p>
                    <p>Acesse o menu <a href="expenses.php">Despesas</a> para criar a primeira despesa.</p>
                </div>
                <?php
                    else:
                        // Buscar despesas
                        $sqlDesp = "SELECT * FROM eiche_despesas 
                                    WHERE data >= '$dataInicio' AND data <= '$dataFim'
                                    ORDER BY data DESC";
                        $result = mysqli_query($conexao, $sqlDesp);
                        $totalDesp = 0;
                        $totalPendentes = 0;
                        $totalPagas = 0;
                        $despesas = [];
                        while ($row = mysqli_fetch_assoc($result)) {
                            $despesas[] = $row;
                            $totalDesp += $row['valor'];
                            if ($row['status'] === 'P') $totalPendentes += $row['valor'];
                            else $totalPagas += $row['valor'];
                        }
                        
                        // Despesas por categoria
                        $sqlCat = "SELECT categoria, SUM(valor) as total, COUNT(*) as qtd
                                   FROM eiche_despesas 
                                   WHERE data >= '$dataInicio' AND data <= '$dataFim'
                                   GROUP BY categoria ORDER BY total DESC";
                        $resultCat = mysqli_query($conexao, $sqlCat);
                ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px;">
                    <div style="background: #dc2626; padding: 20px; border-radius: 8px; text-align: center; color: white;">
                        <div style="font-size: 11px; opacity: 0.9;">Total Despesas</div>
                        <div style="font-size: 28px; font-weight: 700;">R$ <?= number_format($totalDesp, 2, ',', '.') ?></div>
                    </div>
                    <div style="background: #fef3c7; padding: 20px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 11px; color: #92400e;">Pendentes</div>
                        <div style="font-size: 24px; font-weight: 700; color: #92400e;">R$ <?= number_format($totalPendentes, 2, ',', '.') ?></div>
                    </div>
                    <div style="background: #dcfce7; padding: 20px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 11px; color: #166534;">Pagas</div>
                        <div style="font-size: 24px; font-weight: 700; color: #166534;">R$ <?= number_format($totalPagas, 2, ',', '.') ?></div>
                    </div>
                    <div style="background: #dbeafe; padding: 20px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 11px; color: #1e40af;">Quantidade</div>
                        <div style="font-size: 24px; font-weight: 700; color: #1e40af;"><?= count($despesas) ?></div>
                    </div>
                </div>
                
                <?php
                    // Buscar soma por tipo de desconto
                    $despPorTipo = ['diaria' => 0, 'consumo' => 0, 'servico' => 0, 'nenhum' => 0];
                    $qtdPorTipo = ['diaria' => 0, 'consumo' => 0, 'servico' => 0, 'nenhum' => 0];
                    
                    // Verificar se tabela de categorias existe
                    $checkCatTable = mysqli_query($conexao, "SHOW TABLES LIKE 'eiche_despesas_categorias'");
                    if (mysqli_num_rows($checkCatTable) > 0) {
                        $sqlTipo = "SELECT c.desconta_de, SUM(d.valor) as total, COUNT(*) as qtd
                                    FROM eiche_despesas d
                                    LEFT JOIN eiche_despesas_categorias c ON d.categoria = c.nome
                                    WHERE d.data >= '$dataInicio' AND d.data <= '$dataFim'
                                    GROUP BY c.desconta_de";
                        $resultTipo = mysqli_query($conexao, $sqlTipo);
                        while ($row = mysqli_fetch_assoc($resultTipo)) {
                            $tipo = $row['desconta_de'] ?? 'nenhum';
                            $despPorTipo[$tipo] = (float)$row['total'];
                            $qtdPorTipo[$tipo] = (int)$row['qtd'];
                        }
                    }
                    
                    $tipoLabels = [
                        'diaria' => ['label' => 'Diária', 'icon' => '🛏️', 'color' => '#3b82f6', 'bg' => '#dbeafe'],
                        'consumo' => ['label' => 'Consumo', 'icon' => '🍽️', 'color' => '#f59e0b', 'bg' => '#fef3c7'],
                        'servico' => ['label' => 'Serviço', 'icon' => '🛎️', 'color' => '#8b5cf6', 'bg' => '#ede9fe'],
                        'nenhum' => ['label' => 'Sem desconto', 'icon' => '⬜', 'color' => '#6b7280', 'bg' => '#f3f4f6']
                    ];
                ?>
                
                <h4 style="margin-bottom: 10px;">💰 Despesas por Tipo de Desconto</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
                    <?php foreach ($tipoLabels as $tipo => $info): 
                        $valor = $despPorTipo[$tipo];
                        $qtd = $qtdPorTipo[$tipo];
                        if ($valor > 0 || $qtd > 0):
                    ?>
                    <div style="background: <?= $info['bg'] ?>; padding: 15px; border-radius: 10px; border-left: 4px solid <?= $info['color'] ?>;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <span style="font-size: 20px;"><?= $info['icon'] ?></span>
                            <span style="font-size: 12px; color: <?= $info['color'] ?>; font-weight: 600;"><?= $info['label'] ?></span>
                        </div>
                        <div style="font-size: 22px; font-weight: 700; color: <?= $info['color'] ?>;">
                            R$ <?= number_format($valor, 2, ',', '.') ?>
                        </div>
                        <div style="font-size: 11px; color: #666; margin-top: 4px;">
                            <?= $qtd ?> despesa(s)
                        </div>
                    </div>
                    <?php endif; endforeach; ?>
                </div>
                
                <table class="table" style="font-size: 12px; margin-bottom: 25px;">
                    <thead>
                        <tr style="background: #f0f9ff;">
                            <th style="text-align: center;">Tipo</th>
                            <th>Descrição</th>
                            <th style="text-align: center;">Qtd</th>
                            <th style="text-align: right;">Valor</th>
                            <th>% do Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tipoLabels as $tipo => $info): 
                            $valor = $despPorTipo[$tipo];
                            $qtd = $qtdPorTipo[$tipo];
                            $pct = $totalDesp > 0 ? ($valor / $totalDesp) * 100 : 0;
                        ?>
                        <tr>
                            <td style="text-align: center;">
                                <span style="background: <?= $info['bg'] ?>; padding: 4px 10px; border-radius: 12px; font-size: 12px;">
                                    <?= $info['icon'] ?>
                                </span>
                            </td>
                            <td>
                                <strong style="color: <?= $info['color'] ?>;"><?= $info['label'] ?></strong>
                                <br><small style="color: #999;">
                                    <?php if ($tipo === 'diaria'): ?>
                                        Desconta da receita de diárias
                                    <?php elseif ($tipo === 'consumo'): ?>
                                        Desconta da receita de consumos
                                    <?php elseif ($tipo === 'servico'): ?>
                                        Desconta da receita de serviços
                                    <?php else: ?>
                                        Não vinculado a nenhuma receita
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td style="text-align: center;"><?= $qtd ?></td>
                            <td style="text-align: right; font-weight: 600; color: #dc2626;">
                                R$ <?= number_format($valor, 2, ',', '.') ?>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="flex: 1; background: #e5e7eb; height: 8px; border-radius: 4px; max-width: 80px;">
                                        <div style="width: <?= min(100, $pct) ?>%; background: <?= $info['color'] ?>; height: 100%; border-radius: 4px;"></div>
                                    </div>
                                    <span style="font-size: 11px;"><?= number_format($pct, 1) ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="font-weight: bold; background: #f9fafb;">
                            <td colspan="3">TOTAL</td>
                            <td style="text-align: right; color: #dc2626;">R$ <?= number_format($totalDesp, 2, ',', '.') ?></td>
                            <td>100%</td>
                        </tr>
                    </tfoot>
                </table>
                
                <h4 style="margin-bottom: 10px;">📊 Despesas por Categoria</h4>
                <table class="table" style="font-size: 12px; margin-bottom: 25px;">
                    <thead>
                        <tr>
                            <th>Categoria</th>
                            <th style="text-align: center;">Qtd</th>
                            <th style="text-align: right;">Valor</th>
                            <th>% do Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($cat = mysqli_fetch_assoc($resultCat)): 
                            $pct = $totalDesp > 0 ? ($cat['total'] / $totalDesp) * 100 : 0;
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($cat['categoria'] ?? 'Geral') ?></strong></td>
                            <td style="text-align: center;"><?= $cat['qtd'] ?></td>
                            <td style="text-align: right; font-weight: 600; color: #dc2626;">R$ <?= number_format($cat['total'], 2, ',', '.') ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="flex: 1; background: #e5e7eb; height: 8px; border-radius: 4px; max-width: 100px;">
                                        <div style="width: <?= min(100, $pct) ?>%; background: #dc2626; height: 100%; border-radius: 4px;"></div>
                                    </div>
                                    <span style="font-size: 11px;"><?= number_format($pct, 1) ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <h4 style="margin-bottom: 10px;">📋 Lista de Despesas</h4>
                <table class="table" style="font-size: 11px;">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: right;">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($despesas as $d): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($d['data'])) ?></td>
                            <td><?= htmlspecialchars($d['descricao']) ?></td>
                            <td><span style="background: #e0e7ff; padding: 2px 6px; border-radius: 10px; font-size: 10px;"><?= htmlspecialchars($d['categoria']) ?></span></td>
                            <td style="text-align: center;">
                                <?php if ($d['status'] === 'P'): ?>
                                <span style="background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 10px; font-size: 10px;">Pendente</span>
                                <?php else: ?>
                                <span style="background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 10px; font-size: 10px;">Pago</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; font-weight: 600; color: #dc2626;">R$ <?= number_format($d['valor'], 2, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="font-weight: bold; background: #f9fafb;">
                            <td colspan="4">TOTAL</td>
                            <td style="text-align: right; color: #dc2626;">R$ <?= number_format($totalDesp, 2, ',', '.') ?></td>
                        </tr>
                    </tfoot>
                </table>
                <?php endif; ?>
                
                <?php 
                // =========================================
                // RELATÓRIO: LUCRO (RECEITA - DESPESAS)
                // =========================================
                elseif ($tipo === 'lucro'):
                    // Mesma lógica do financeiro para receita
                    $sqlFin = "SELECT SUM(valor_diaria) as total FROM eiche_hospedagem WHERE rstatus = 'F' AND DATE(lg_checkout) >= '$dataInicio' AND DATE(lg_checkout) <= '$dataFim'";
                    $receitaFin = mysqli_fetch_assoc(mysqli_query($conexao, $sqlFin))['total'] ?? 0;
                    
                    $sqlAtiv = "SELECT SUM(valor_diaria) as total FROM eiche_hospedagem WHERE rstatus = 'A' AND data >= '$dataInicio' AND data <= '$dataFim'";
                    $receitaAtiv = mysqli_fetch_assoc(mysqli_query($conexao, $sqlAtiv))['total'] ?? 0;
                    
                    $receitaDiarias = $receitaFin + $receitaAtiv;
                    
                    $sqlCons = "SELECT SUM(c.valor_unit * c.qtd) as total FROM eiche_hosp_lnk_cons_hosp c
                                WHERE c.data >= '$dataInicio' AND c.data <= '$dataFim'";
                    $receitaConsumos = mysqli_fetch_assoc(mysqli_query($conexao, $sqlCons))['total'] ?? 0;
                    
                    $sqlServ = "SELECT SUM(s.valor_unit * s.qtd) as total FROM eiche_hosp_lnk_serv_hosp s
                                WHERE s.data >= '$dataInicio' AND s.data <= '$dataFim'";
                    $receitaServicos = mysqli_fetch_assoc(mysqli_query($conexao, $sqlServ))['total'] ?? 0;
                    
                    // Calcular descontos
                    $totalDescontosLucro = 0;
                    $checkDescCol = mysqli_query($conexao, "SHOW COLUMNS FROM eiche_hospedagem LIKE 'desconto_tipo'");
                    if (mysqli_num_rows($checkDescCol) > 0) {
                        $sqlDescontos = "SELECT h.ID, h.desconto_tipo, h.desconto_valor, h.data_inicial, h.data_final, h.valor_diaria
                                         FROM eiche_hospedagem h 
                                         WHERE h.idonly = 1
                                         AND h.desconto_tipo != 'nenhum' AND h.desconto_valor > 0
                                         AND ((h.rstatus = 'F' AND DATE(h.lg_checkout) >= '$dataInicio' AND DATE(h.lg_checkout) <= '$dataFim')
                                              OR (h.rstatus = 'A' AND h.data >= '$dataInicio' AND h.data <= '$dataFim'))";
                        $resultDesc = mysqli_query($conexao, $sqlDescontos);
                        while ($rowDesc = mysqli_fetch_assoc($resultDesc)) {
                            $hospIdDesc = $rowDesc['ID'];
                            $di = $rowDesc['data_inicial'] ?? $rowDesc['data'];
                            $df = $rowDesc['data_final'] ?? $rowDesc['data'];
                            $numDiarias = max(1, (int)((strtotime($df) - strtotime($di)) / 86400) + 1);
                            $subtotalHosp = $rowDesc['valor_diaria'] * $numDiarias;
                            
                            $sqlConsHosp = "SELECT SUM(valor_unit * qtd) as total FROM eiche_hosp_lnk_cons_hosp WHERE ID_hosp = $hospIdDesc";
                            $subtotalHosp += mysqli_fetch_assoc(mysqli_query($conexao, $sqlConsHosp))['total'] ?? 0;
                            $sqlServHosp = "SELECT SUM(valor_unit * qtd) as total FROM eiche_hosp_lnk_serv_hosp WHERE ID_hosp = $hospIdDesc";
                            $subtotalHosp += mysqli_fetch_assoc(mysqli_query($conexao, $sqlServHosp))['total'] ?? 0;
                            
                            if ($rowDesc['desconto_tipo'] === 'percentual') {
                                $totalDescontosLucro += $subtotalHosp * ($rowDesc['desconto_valor'] / 100);
                            } else {
                                $totalDescontosLucro += $rowDesc['desconto_valor'];
                            }
                        }
                    }
                    
                    $receitaTotal = $receitaDiarias + $receitaConsumos + $receitaServicos - $totalDescontosLucro;
                    
                    // Despesas
                    $despesasTotal = 0;
                    $checkTable = mysqli_query($conexao, "SHOW TABLES LIKE 'eiche_despesas'");
                    if (mysqli_num_rows($checkTable) > 0) {
                        $sqlDesp = "SELECT SUM(valor) as total FROM eiche_despesas WHERE data >= '$dataInicio' AND data <= '$dataFim'";
                        $despesasTotal = mysqli_fetch_assoc(mysqli_query($conexao, $sqlDesp))['total'] ?? 0;
                    }
                    
                    $lucro = $receitaTotal - $despesasTotal;
                    $margem = $receitaTotal > 0 ? ($lucro / $receitaTotal) * 100 : 0;
                ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div style="background: #dcfce7; padding: 25px; border-radius: 12px; text-align: center;">
                        <div style="font-size: 12px; color: #166534;">💵 Receita Total</div>
                        <div style="font-size: 28px; font-weight: 700; color: #166534;">R$ <?= number_format($receitaTotal, 2, ',', '.') ?></div>
                        <div style="font-size: 11px; color: #166534; margin-top: 5px;">
                            Diárias: R$ <?= number_format($receitaDiarias, 0, ',', '.') ?> | 
                            Consumos: R$ <?= number_format($receitaConsumos, 0, ',', '.') ?> | 
                            Serviços: R$ <?= number_format($receitaServicos, 0, ',', '.') ?>
                            <?php if ($totalDescontosLucro > 0): ?>
                            | <span style="color: #c2410c;">Desc: -R$ <?= number_format($totalDescontosLucro, 0, ',', '.') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="background: #fee2e2; padding: 25px; border-radius: 12px; text-align: center;">
                        <div style="font-size: 12px; color: #dc2626;">💸 Despesas Total</div>
                        <div style="font-size: 28px; font-weight: 700; color: #dc2626;">R$ <?= number_format($despesasTotal, 2, ',', '.') ?></div>
                    </div>
                    <div style="background: <?= $lucro >= 0 ? '#166534' : '#dc2626' ?>; padding: 25px; border-radius: 12px; text-align: center; color: white;">
                        <div style="font-size: 12px; opacity: 0.9;"><?= $lucro >= 0 ? '📈 Lucro' : '📉 Prejuízo' ?></div>
                        <div style="font-size: 32px; font-weight: 700;">R$ <?= number_format(abs($lucro), 2, ',', '.') ?></div>
                        <div style="font-size: 12px; opacity: 0.8; margin-top: 5px;">
                            Margem: <?= number_format($margem, 1) ?>%
                        </div>
                    </div>
                </div>
                
                <div style="background: #f9fafb; padding: 20px; border-radius: 12px;">
                    <h4 style="margin-bottom: 15px;">📊 Composição do Resultado</h4>
                    <table class="table" style="font-size: 13px;">
                        <tbody>
                            <tr>
                                <td style="padding: 10px;">➕ Diárias (Finalizadas + Ativas)</td>
                                <td style="text-align: right; font-weight: 600; color: #166534;">R$ <?= number_format($receitaDiarias, 2, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 10px;">➕ Consumos</td>
                                <td style="text-align: right; font-weight: 600; color: #166534;">R$ <?= number_format($receitaConsumos, 2, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 10px;">➕ Serviços</td>
                                <td style="text-align: right; font-weight: 600; color: #166534;">R$ <?= number_format($receitaServicos, 2, ',', '.') ?></td>
                            </tr>
                            <tr style="background: #dcfce7;">
                                <td style="padding: 10px; font-weight: 600;">= RECEITA TOTAL</td>
                                <td style="text-align: right; font-weight: 700; font-size: 16px; color: #166534;">R$ <?= number_format($receitaTotal, 2, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 10px;">➖ Despesas</td>
                                <td style="text-align: right; font-weight: 600; color: #dc2626;">R$ <?= number_format($despesasTotal, 2, ',', '.') ?></td>
                            </tr>
                            <tr style="background: <?= $lucro >= 0 ? '#166534' : '#dc2626' ?>; color: white;">
                                <td style="padding: 12px; font-weight: 700;">= <?= $lucro >= 0 ? 'LUCRO' : 'PREJUÍZO' ?></td>
                                <td style="text-align: right; font-weight: 700; font-size: 20px;">R$ <?= number_format(abs($lucro), 2, ',', '.') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <?php 
                // =========================================
                // RELATÓRIO: RECEITA POR PERÍODO
                // =========================================
                elseif ($tipo === 'receita'):
                    // Mesma lógica do Financeiro
                    
                    // Diárias finalizadas (checkout no período)
                    $sqlFin = "SELECT SUM(valor_diaria) as total 
                               FROM eiche_hospedagem 
                               WHERE rstatus = 'F' 
                               AND DATE(lg_checkout) >= '$dataInicio' 
                               AND DATE(lg_checkout) <= '$dataFim'";
                    $receitaFinalizadas = mysqli_fetch_assoc(mysqli_query($conexao, $sqlFin))['total'] ?? 0;
                    
                    // Diárias ativas no período
                    $sqlAtiv = "SELECT SUM(valor_diaria) as total 
                                FROM eiche_hospedagem 
                                WHERE rstatus = 'A' 
                                AND data >= '$dataInicio' AND data <= '$dataFim'";
                    $receitaAtivas = mysqli_fetch_assoc(mysqli_query($conexao, $sqlAtiv))['total'] ?? 0;
                    
                    $totalDiarias = $receitaFinalizadas + $receitaAtivas;
                    
                    // Consumos (sem duplicação - filtra pela data do consumo)
                    $sqlConsumos = "SELECT SUM(c.valor_unit * c.qtd) as total 
                                    FROM eiche_hosp_lnk_cons_hosp c
                                    WHERE c.data >= '$dataInicio' AND c.data <= '$dataFim'";
                    $totalConsumos = mysqli_fetch_assoc(mysqli_query($conexao, $sqlConsumos))['total'] ?? 0;
                    
                    // Serviços (sem duplicação - filtra pela data do serviço)
                    $sqlServicos = "SELECT SUM(s.valor_unit * s.qtd) as total 
                                    FROM eiche_hosp_lnk_serv_hosp s
                                    WHERE s.data >= '$dataInicio' AND s.data <= '$dataFim'";
                    $totalServicos = mysqli_fetch_assoc(mysqli_query($conexao, $sqlServicos))['total'] ?? 0;
                    
                    // Calcular descontos do período
                    $totalDescontosRel = 0;
                    $checkDescCol = mysqli_query($conexao, "SHOW COLUMNS FROM eiche_hospedagem LIKE 'desconto_tipo'");
                    if (mysqli_num_rows($checkDescCol) > 0) {
                        $sqlDescontos = "SELECT h.ID, h.desconto_tipo, h.desconto_valor, h.data_inicial, h.data_final, h.valor_diaria
                                         FROM eiche_hospedagem h 
                                         WHERE h.idonly = 1
                                         AND h.desconto_tipo != 'nenhum' AND h.desconto_valor > 0
                                         AND ((h.rstatus = 'F' AND DATE(h.lg_checkout) >= '$dataInicio' AND DATE(h.lg_checkout) <= '$dataFim')
                                              OR (h.rstatus = 'A' AND h.data >= '$dataInicio' AND h.data <= '$dataFim'))";
                        $resultDesc = mysqli_query($conexao, $sqlDescontos);
                        while ($rowDesc = mysqli_fetch_assoc($resultDesc)) {
                            $hospIdDesc = $rowDesc['ID'];
                            $di = $rowDesc['data_inicial'] ?? $rowDesc['data'];
                            $df = $rowDesc['data_final'] ?? $rowDesc['data'];
                            $numDiarias = max(1, (int)((strtotime($df) - strtotime($di)) / 86400) + 1);
                            $subtotalHosp = $rowDesc['valor_diaria'] * $numDiarias;
                            
                            $sqlConsHosp = "SELECT SUM(valor_unit * qtd) as total FROM eiche_hosp_lnk_cons_hosp WHERE ID_hosp = $hospIdDesc";
                            $subtotalHosp += mysqli_fetch_assoc(mysqli_query($conexao, $sqlConsHosp))['total'] ?? 0;
                            $sqlServHosp = "SELECT SUM(valor_unit * qtd) as total FROM eiche_hosp_lnk_serv_hosp WHERE ID_hosp = $hospIdDesc";
                            $subtotalHosp += mysqli_fetch_assoc(mysqli_query($conexao, $sqlServHosp))['total'] ?? 0;
                            
                            if ($rowDesc['desconto_tipo'] === 'percentual') {
                                $totalDescontosRel += $subtotalHosp * ($rowDesc['desconto_valor'] / 100);
                            } else {
                                $totalDescontosRel += $rowDesc['desconto_valor'];
                            }
                        }
                    }
                    
                    $totalGeral = $totalDiarias + $totalConsumos + $totalServicos - $totalDescontosRel;
                ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 30px;">
                    <div style="background: #dcfce7; padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 11px; color: #166534;">Finalizadas</div>
                        <div style="font-size: 20px; font-weight: 700; color: #166534;">R$ <?= number_format($receitaFinalizadas, 2, ',', '.') ?></div>
                    </div>
                    <div style="background: #dbeafe; padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 11px; color: #1e40af;">Ativas</div>
                        <div style="font-size: 20px; font-weight: 700; color: #1e40af;">R$ <?= number_format($receitaAtivas, 2, ',', '.') ?></div>
                    </div>
                    <div style="background: #fef3c7; padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 11px; color: #92400e;">Consumos</div>
                        <div style="font-size: 20px; font-weight: 700; color: #92400e;">R$ <?= number_format($totalConsumos, 2, ',', '.') ?></div>
                    </div>
                    <div style="background: #e0e7ff; padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 11px; color: #4338ca;">Serviços</div>
                        <div style="font-size: 20px; font-weight: 700; color: #4338ca;">R$ <?= number_format($totalServicos, 2, ',', '.') ?></div>
                    </div>
                    <?php if ($totalDescontosRel > 0): ?>
                    <div style="background: #ffedd5; padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 11px; color: #c2410c;">Descontos</div>
                        <div style="font-size: 20px; font-weight: 700; color: #c2410c;">-R$ <?= number_format($totalDescontosRel, 2, ',', '.') ?></div>
                    </div>
                    <?php endif; ?>
                    <div style="background: #1e3a5f; padding: 15px; border-radius: 8px; text-align: center; color: white;">
                        <div style="font-size: 11px; opacity: 0.8;">TOTAL GERAL</div>
                        <div style="font-size: 24px; font-weight: 700;">R$ <?= number_format($totalGeral, 2, ',', '.') ?></div>
                    </div>
                </div>
                
                <p style="font-size: 11px; color: #666; margin-bottom: 20px;">
                    💡 <strong>Diárias:</strong> Finalizadas (checkout no período) + Ativas no período<br>
                    💡 <strong>Consumos/Serviços:</strong> Por data do registro ou data da hospedagem
                </p>
                
                <h4>Detalhamento por Data</h4>
                <?php
                    $sqlDia = "SELECT DATE(data) as dia, SUM(valor_diaria) as total, COUNT(DISTINCT ID) as qtd
                               FROM eiche_hospedagem 
                               WHERE data >= '$dataInicio' AND data <= '$dataFim'
                               GROUP BY DATE(data)
                               ORDER BY dia DESC";
                    $result = mysqli_query($conexao, $sqlDia);
                ?>
                <table class="table" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Hospedagens</th>
                            <th style="text-align: right;">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($row['dia'])) ?></td>
                            <td><?= $row['qtd'] ?></td>
                            <td style="text-align: right;">R$ <?= number_format($row['total'], 2, ',', '.') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <?php 
                // =========================================
                // RELATÓRIO: RECEITA POR QUARTO
                // =========================================
                elseif ($tipo === 'receita_quarto'):
                    $sql = "SELECT q.numero, q.ID, COUNT(DISTINCT h.ID) as qtd_hosp, 
                                   SUM(h.valor_diaria) as total
                            FROM eiche_hosp_quartos q
                            LEFT JOIN eiche_hospedagem h ON q.ID = h.ID_quarto 
                                AND h.data >= '$dataInicio' AND h.data <= '$dataFim'
                            GROUP BY q.ID
                            ORDER BY total DESC";
                    $result = mysqli_query($conexao, $sql);
                    $total = 0;
                ?>
                <table class="table" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th>Quarto</th>
                            <th style="text-align: center;">Hospedagens</th>
                            <th style="text-align: right;">Receita</th>
                            <th>% do Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $dados = [];
                        while ($row = mysqli_fetch_assoc($result)) {
                            $dados[] = $row;
                            $total += $row['total'];
                        }
                        foreach ($dados as $row):
                            $pct = $total > 0 ? ($row['total'] / $total) * 100 : 0;
                        ?>
                        <tr>
                            <td><strong><?= $row['numero'] ?></strong></td>
                            <td style="text-align: center;"><?= $row['qtd_hosp'] ?? 0 ?></td>
                            <td style="text-align: right;">R$ <?= number_format($row['total'] ?? 0, 2, ',', '.') ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="flex: 1; background: #e5e7eb; height: 8px; border-radius: 4px;">
                                        <div style="width: <?= $pct ?>%; background: #3b82f6; height: 100%; border-radius: 4px;"></div>
                                    </div>
                                    <span style="font-size: 11px; width: 40px;"><?= number_format($pct, 1) ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="font-weight: bold; background: #f9fafb;">
                            <td>TOTAL</td>
                            <td></td>
                            <td style="text-align: right;">R$ <?= number_format($total, 2, ',', '.') ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                
                <?php 
                // =========================================
                // RELATÓRIO: LISTA DE CLIENTES
                // =========================================
                elseif ($tipo === 'clientes'):
                    $sql = "SELECT c.*, COUNT(DISTINCT h.ID) as total_hosp, SUM(h.valor_diaria) as total_gasto
                            FROM eiche_customers c
                            LEFT JOIN eiche_hospedagem h ON c.ID = h.ID_cliente
                            GROUP BY c.ID
                            ORDER BY c.razao
                            LIMIT 200";
                    $result = mysqli_query($conexao, $sql);
                ?>
                <table class="table" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>CPF/CNPJ</th>
                            <th>Telefone</th>
                            <th>Cidade</th>
                            <th style="text-align: center;">Hospedagens</th>
                            <th style="text-align: right;">Total Gasto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars(substr($row['razao'] ?? '', 0, 35)) ?></strong></td>
                            <td><?= $row['cpf'] ?? $row['cnpj'] ?? '-' ?></td>
                            <td><?= $row['fone1'] ?? '-' ?></td>
                            <td><?= ($row['e_cidade'] ?? '') . ($row['e_estado'] ? '/'.$row['e_estado'] : '') ?></td>
                            <td style="text-align: center;"><?= $row['total_hosp'] ?? 0 ?></td>
                            <td style="text-align: right;">R$ <?= number_format($row['total_gasto'] ?? 0, 2, ',', '.') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <?php 
                // =========================================
                // RELATÓRIO: CLIENTES FREQUENTES
                // =========================================
                elseif ($tipo === 'clientes_frequentes'):
                    $sql = "SELECT c.razao, c.cpf, c.fone1, COUNT(DISTINCT h.ID) as total_hosp, 
                                   SUM(h.valor_diaria) as total_gasto
                            FROM eiche_customers c
                            INNER JOIN eiche_hospedagem h ON c.ID = h.ID_cliente
                            GROUP BY c.ID
                            HAVING total_hosp >= 2
                            ORDER BY total_hosp DESC, total_gasto DESC
                            LIMIT 50";
                    $result = mysqli_query($conexao, $sql);
                ?>
                <table class="table" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>CPF</th>
                            <th>Telefone</th>
                            <th style="text-align: center;">Hospedagens</th>
                            <th style="text-align: right;">Total Gasto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td>
                                <?php if ($i <= 3): ?>
                                <span style="font-size: 18px;"><?= ['🥇','🥈','🥉'][$i-1] ?></span>
                                <?php else: ?>
                                <?= $i ?>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($row['razao']) ?></strong></td>
                            <td><?= $row['cpf'] ?? '-' ?></td>
                            <td><?= $row['fone1'] ?? '-' ?></td>
                            <td style="text-align: center;">
                                <span style="background: #dcfce7; color: #166534; padding: 3px 10px; border-radius: 20px; font-weight: 600;">
                                    <?= $row['total_hosp'] ?>
                                </span>
                            </td>
                            <td style="text-align: right; font-weight: 600;">R$ <?= number_format($row['total_gasto'] ?? 0, 2, ',', '.') ?></td>
                        </tr>
                        <?php $i++; endwhile; ?>
                    </tbody>
                </table>
                
                <?php 
                // =========================================
                // RELATÓRIO: LISTA DE QUARTOS
                // =========================================
                elseif ($tipo === 'quartos'):
                    $sql = "SELECT q.*, g.nome as grupo_nome, g.valor as grupo_valor
                            FROM eiche_hosp_quartos q
                            LEFT JOIN eiche_hosp_gruposq g ON q.grupo = g.ID
                            ORDER BY q.numero";
                    $result = mysqli_query($conexao, $sql);
                ?>
                <table class="table" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th>Quarto</th>
                            <th>Grupo</th>
                            <th style="text-align: center;">Capacidade</th>
                            <th style="text-align: right;">Valor Diária</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): 
                            $valorFinal = ($row['valor'] > 0) ? $row['valor'] : ($row['grupo_valor'] ?? 0);
                        ?>
                        <tr>
                            <td><strong><?= $row['numero'] ?></strong></td>
                            <td><?= $row['grupo_nome'] ?? '-' ?></td>
                            <td style="text-align: center;"><?= $row['ocupantes'] ?? '-' ?> pessoas</td>
                            <td style="text-align: right;">R$ <?= number_format($valorFinal, 2, ',', '.') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <?php 
                // =========================================
                // RELATÓRIO: PERFORMANCE POR QUARTO
                // =========================================
                elseif ($tipo === 'quartos_performance'):
                    $diasPeriodo = max(1, (strtotime($dataFim) - strtotime($dataInicio)) / 86400 + 1);
                    
                    $sql = "SELECT q.numero, q.ID,
                                   COUNT(DISTINCT h.ID) as total_hosp,
                                   SUM(h.valor_diaria) as receita,
                                   COUNT(h.ID) as dias_ocupados
                            FROM eiche_hosp_quartos q
                            LEFT JOIN eiche_hospedagem h ON q.ID = h.ID_quarto 
                                AND h.data >= '$dataInicio' AND h.data <= '$dataFim'
                                AND h.rstatus IN ('A', 'F')
                            GROUP BY q.ID
                            ORDER BY receita DESC";
                    $result = mysqli_query($conexao, $sql);
                ?>
                <p style="margin-bottom: 15px; font-size: 13px; color: #666;">
                    Período analisado: <strong><?= (int)$diasPeriodo ?> dias</strong>
                </p>
                <table class="table" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th>Quarto</th>
                            <th style="text-align: center;">Hospedagens</th>
                            <th style="text-align: center;">Dias Ocupados</th>
                            <th>Taxa Ocupação</th>
                            <th style="text-align: right;">Receita</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): 
                            $taxaOcupacao = ($diasPeriodo > 0) ? ($row['dias_ocupados'] / $diasPeriodo) * 100 : 0;
                        ?>
                        <tr>
                            <td><strong><?= $row['numero'] ?></strong></td>
                            <td style="text-align: center;"><?= $row['total_hosp'] ?? 0 ?></td>
                            <td style="text-align: center;"><?= $row['dias_ocupados'] ?? 0 ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="flex: 1; background: #e5e7eb; height: 8px; border-radius: 4px; max-width: 100px;">
                                        <div style="width: <?= min(100, $taxaOcupacao) ?>%; background: <?= $taxaOcupacao >= 70 ? '#22c55e' : ($taxaOcupacao >= 40 ? '#f59e0b' : '#ef4444') ?>; height: 100%; border-radius: 4px;"></div>
                                    </div>
                                    <span style="font-size: 11px;"><?= number_format($taxaOcupacao, 1) ?>%</span>
                                </div>
                            </td>
                            <td style="text-align: right; font-weight: 600;">R$ <?= number_format($row['receita'] ?? 0, 2, ',', '.') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">
                    Relatório não encontrado
                </p>
                <?php endif; ?>
                
            </div>
        </div>
        
        <!-- Rodapé de impressão -->
        <div class="print-footer" style="display: none; margin-top: 30px; text-align: center; font-size: 11px; color: #999;">
            Relatório gerado em <?= date('d/m/Y H:i') ?> - Pousada Bona
        </div>
        
        <?php endif; // fim do else (relatório específico) ?>
        <?php endif; // fim do if (verificação de permissão para relatórios financeiros) ?>
    </main>
</div>

<style>
.report-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
    color: #333;
    text-decoration: none;
    transition: all 0.2s;
}
.report-item:hover {
    background: #f9fafb;
    color: #3b82f6;
}
.report-item:last-child {
    border-bottom: none;
}
.table {
    width: 100%;
    border-collapse: collapse;
}
.table th, .table td {
    padding: 10px;
    border-bottom: 1px solid #eee;
    text-align: left;
}
.table th {
    background: #f9fafb;
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    color: #666;
}
.table tr:hover {
    background: #fafafa;
}
.form-input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 13px;
}

@media print {
    .no-print { display: none !important; }
    .print-header { display: block !important; }
    .print-footer { display: block !important; }
    .sidebar, .main-header, .topbar { display: none !important; }
    .main-wrapper { margin: 0 !important; }
    .main-content { padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
    body { background: white !important; }
}
</style>

<?php 
mysqli_close($conexao);
include 'includes/footer.php'; 
?>
