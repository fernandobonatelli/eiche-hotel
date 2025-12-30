<?php
/**
 * Pousada Bona - Buscar Histórico do Cliente
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    echo '<div style="color: red;">Não autenticado</div>';
    exit;
}

$db_host = 'localhost';
$db_user = 'pous3527_root';
$db_pass = ';Fb6818103200';
$db_name = 'pous3527_eiche';

$conexao = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
mysqli_set_charset($conexao, 'utf8');

$clienteId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$clienteId) {
    echo '<div style="color: red;">ID do cliente inválido</div>';
    exit;
}

// Buscar hospedagens do cliente
$sql = "SELECT h.ID, MIN(h.data) as data_entrada, MAX(h.data) as data_saida,
               h.valor_diaria, h.rstatus, q.numero as quarto_numero, COUNT(*) as num_diarias
        FROM eiche_hospedagem h
        LEFT JOIN eiche_hosp_quartos q ON h.ID_quarto = q.ID
        WHERE h.ID_cliente = $clienteId
        GROUP BY h.ID
        ORDER BY h.ID DESC
        LIMIT 20";

$result = mysqli_query($conexao, $sql);
$hospedagens = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $hospedagens[] = $row;
    }
}

// Estatísticas do cliente
$sqlStats = "SELECT COUNT(DISTINCT ID) as total_hosp, SUM(valor_diaria) as total_gasto 
             FROM eiche_hospedagem WHERE ID_cliente = $clienteId";
$resultStats = mysqli_query($conexao, $sqlStats);
$stats = mysqli_fetch_assoc($resultStats);

mysqli_close($conexao);
?>

<div style="margin-bottom: 15px; padding: 12px; background: #f0f9ff; border-radius: 8px; display: flex; gap: 20px;">
    <div>
        <div style="font-size: 11px; color: #0369a1;">Total de Hospedagens</div>
        <div style="font-size: 20px; font-weight: 700; color: #0c4a6e;"><?= $stats['total_hosp'] ?? 0 ?></div>
    </div>
    <div>
        <div style="font-size: 11px; color: #0369a1;">Total Gasto</div>
        <div style="font-size: 20px; font-weight: 700; color: #0c4a6e;">R$ <?= number_format((float)($stats['total_gasto'] ?? 0), 2, ',', '.') ?></div>
    </div>
</div>

<?php if (empty($hospedagens)): ?>
<div style="text-align: center; padding: 30px; color: #999;">
    Nenhuma hospedagem encontrada para este cliente
</div>
<?php else: ?>
<table style="width: 100%; font-size: 12px; border-collapse: collapse;">
    <thead>
        <tr style="background: #f9fafb;">
            <th style="padding: 10px; text-align: left; border-bottom: 1px solid #eee;">ID</th>
            <th style="padding: 10px; text-align: left; border-bottom: 1px solid #eee;">Quarto</th>
            <th style="padding: 10px; text-align: left; border-bottom: 1px solid #eee;">Entrada</th>
            <th style="padding: 10px; text-align: left; border-bottom: 1px solid #eee;">Saída</th>
            <th style="padding: 10px; text-align: right; border-bottom: 1px solid #eee;">Valor</th>
            <th style="padding: 10px; text-align: center; border-bottom: 1px solid #eee;">Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($hospedagens as $hosp): ?>
        <tr style="cursor: pointer;" onclick="window.open('hospedagem-detalhes.php?id=<?= $hosp['ID'] ?>', '_blank')">
            <td style="padding: 10px; border-bottom: 1px solid #f3f4f6;">#<?= str_pad($hosp['ID'], 6, '0', STR_PAD_LEFT) ?></td>
            <td style="padding: 10px; border-bottom: 1px solid #f3f4f6;">
                <span style="background: #dbeafe; color: #1e40af; padding: 3px 8px; border-radius: 4px; font-size: 11px;">
                    <?= htmlspecialchars($hosp['quarto_numero'] ?? '-') ?>
                </span>
            </td>
            <td style="padding: 10px; border-bottom: 1px solid #f3f4f6;">
                <?= $hosp['data_entrada'] ? date('d/m/Y', strtotime($hosp['data_entrada'])) : '-' ?>
            </td>
            <td style="padding: 10px; border-bottom: 1px solid #f3f4f6;">
                <?= $hosp['data_saida'] ? date('d/m/Y', strtotime($hosp['data_saida'])) : '-' ?>
            </td>
            <td style="padding: 10px; border-bottom: 1px solid #f3f4f6; text-align: right; font-weight: 600;">
                R$ <?= number_format((float)($hosp['valor_diaria'] ?? 0) * ($hosp['num_diarias'] ?? 1), 2, ',', '.') ?>
            </td>
            <td style="padding: 10px; border-bottom: 1px solid #f3f4f6; text-align: center;">
                <?php if ($hosp['rstatus'] == 'A'): ?>
                    <span style="background: #dbeafe; color: #1e40af; padding: 3px 8px; border-radius: 4px; font-size: 10px;">Ativo</span>
                <?php elseif ($hosp['rstatus'] == 'R'): ?>
                    <span style="background: #fef3c7; color: #92400e; padding: 3px 8px; border-radius: 4px; font-size: 10px;">Reserva</span>
                <?php elseif ($hosp['rstatus'] == 'F'): ?>
                    <span style="background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 4px; font-size: 10px;">Finalizado</span>
                <?php else: ?>
                    <span style="background: #f3f4f6; color: #374151; padding: 3px 8px; border-radius: 4px; font-size: 10px;"><?= $hosp['rstatus'] ?></span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<div style="margin-top: 10px; font-size: 11px; color: #999; text-align: center;">
    Clique em uma linha para ver detalhes
</div>
<?php endif; ?>

