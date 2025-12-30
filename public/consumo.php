<?php
/**
 * Pousada Bona - Adicionar Consumo
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

$hospId = isset($_GET['hosp_id']) ? (int)$_GET['hosp_id'] : 0;
$mensagem = '';
$erro = '';

if (!$hospId) {
    header('Location: reservations.php');
    exit;
}

// Buscar dados da hospedagem (qualquer tipo, não só entrada)
$sqlHosp = "SELECT h.ID, c.razao as cliente_nome, q.numero as quarto_numero
            FROM eiche_hospedagem h
            LEFT JOIN eiche_customers c ON h.ID_cliente = c.ID
            LEFT JOIN eiche_hosp_quartos q ON h.ID_quarto = q.ID
            WHERE h.ID = $hospId
            LIMIT 1";
$resultHosp = mysqli_query($conexao, $sqlHosp);
$hospedagem = mysqli_fetch_assoc($resultHosp);

if (!$hospedagem) {
    header('Location: reservations.php?erro=Hospedagem não encontrada');
    exit;
}

// Buscar produtos (pors = 'P' = Produto, rstatus = 'A' = Ativo)
// Campos corretos: description, price
$produtos = [];
$sqlProdutos = "SELECT ID, description, price FROM eiche_prodorserv WHERE rstatus = 'A' AND pors = 'P' ORDER BY description";
$resultProdutos = mysqli_query($conexao, $sqlProdutos);
if ($resultProdutos) {
    while ($row = mysqli_fetch_assoc($resultProdutos)) {
        $produtos[] = $row;
    }
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produtoId = isset($_POST['produto_id']) ? (int)$_POST['produto_id'] : 0;
    $qtd = isset($_POST['qtd']) ? (float)$_POST['qtd'] : 1;
    $valorUnit = isset($_POST['valor_unit']) ? (float)$_POST['valor_unit'] : 0;
    
    if ($produtoId && $qtd > 0) {
        $data = date('Y-m-d');
        $hora = date('H:i:s');
        $userId = $_SESSION['user_id'] ?? 0;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        $sql = "INSERT INTO eiche_hosp_lnk_cons_hosp (ID_hosp, ID_cons, qtd, valor_unit, ID_user, data, hora, ip) 
                VALUES ($hospId, $produtoId, $qtd, $valorUnit, $userId, '$data', '$hora', '$ip')";
        if (mysqli_query($conexao, $sql)) {
            $mensagem = 'Consumo adicionado com sucesso!';
        } else {
            $erro = 'Erro ao salvar: ' . mysqli_error($conexao);
        }
    } else {
        $erro = 'Selecione um produto e informe a quantidade';
    }
}

// Buscar consumos existentes
$consumos = [];
$sqlConsumos = "SELECT c.*, p.description as produto_nome 
                FROM eiche_hosp_lnk_cons_hosp c
                LEFT JOIN eiche_prodorserv p ON c.ID_cons = p.ID
                WHERE c.ID_hosp = $hospId
                ORDER BY c.data DESC, c.hora DESC";
$resultConsumos = mysqli_query($conexao, $sqlConsumos);
if ($resultConsumos) {
    while ($row = mysqli_fetch_assoc($resultConsumos)) {
        $consumos[] = $row;
    }
}

$pageTitle = 'Adicionar Consumo - Pousada Bona';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
    <style>
        .page-container { max-width: 700px; margin: 0 auto; }
        .back-link { display: inline-flex; align-items: center; gap: 5px; color: #666; text-decoration: none; font-size: 13px; margin-bottom: 15px; }
        .back-link:hover { color: #333; }
        
        .card { background: white; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 15px; }
        .card-header { padding: 12px 18px; border-bottom: 1px solid #eee; font-weight: 600; font-size: 13px; background: #f9fafb; display: flex; justify-content: space-between; align-items: center; }
        .card-body { padding: 18px; }
        
        .info-bar { display: flex; gap: 20px; margin-bottom: 15px; font-size: 12px; padding: 10px 15px; background: #f0f9ff; border-radius: 6px; border: 1px solid #bae6fd; }
        .info-bar label { color: #0369a1; font-weight: 500; }
        .info-bar span { font-weight: 600; color: #0c4a6e; }
        
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 12px; color: #555; margin-bottom: 4px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px; }
        .form-group input:focus, .form-group select:focus { border-color: #3b82f6; outline: none; }
        
        .form-row { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 10px; align-items: end; }
        
        .btn { padding: 10px 18px; border-radius: 6px; font-size: 13px; cursor: pointer; border: none; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        
        table.items { width: 100%; border-collapse: collapse; font-size: 12px; }
        table.items th, table.items td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        table.items th { background: #f9fafb; font-weight: 600; font-size: 10px; text-transform: uppercase; color: #666; }
        table.items .text-right { text-align: right; }
        
        .empty-msg { text-align: center; color: #999; padding: 20px; font-size: 12px; }
        
        .total-row { background: #f0fdf4; }
        .total-row td { font-weight: 600; color: #16a34a; }
        
        .badge { padding: 2px 8px; border-radius: 10px; font-size: 10px; background: #e5e7eb; }
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
                    <div class="card-header">🍽️ Adicionar Consumo</div>
                    <div class="card-body">
                        <?php if (empty($produtos)): ?>
                            <p class="empty-msg">
                                Nenhum produto cadastrado no sistema.<br>
                                <a href="products.php?tipo=P" style="color:#3b82f6;">➕ Cadastrar Produtos</a>
                            </p>
                        <?php else: ?>
                        <form method="POST">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Produto *</label>
                                    <select name="produto_id" id="produto_id" required onchange="atualizarValor()">
                                        <option value="">Selecione um produto...</option>
                                        <?php foreach ($produtos as $p): ?>
                                            <option value="<?php echo $p['ID']; ?>" data-valor="<?php echo $p['price']; ?>">
                                                <?php echo htmlspecialchars($p['description']); ?> - R$ <?php echo number_format($p['price'], 2, ',', '.'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Qtd *</label>
                                    <input type="number" name="qtd" value="1" min="0.5" step="0.5" required>
                                </div>
                                <div class="form-group">
                                    <label>Valor Unit. (R$)</label>
                                    <input type="number" name="valor_unit" id="valor_unit" step="0.01" value="0.00">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">➕ Adicionar</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <span>📋 Consumos Registrados</span>
                        <span class="badge"><?php echo count($consumos); ?> itens</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($consumos)): ?>
                            <p class="empty-msg">Nenhum consumo registrado nesta hospedagem</p>
                        <?php else: ?>
                            <table class="items">
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th class="text-right">Qtd</th>
                                        <th class="text-right">Valor Unit.</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total = 0;
                                    foreach ($consumos as $c): 
                                        $subtotal = ($c['valor_unit'] ?? 0) * ($c['qtd'] ?? 1);
                                        $total += $subtotal;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($c['produto_nome'] ?? 'Produto'); ?></td>
                                        <td class="text-right"><?php echo $c['qtd'] ?? 1; ?></td>
                                        <td class="text-right">R$ <?php echo number_format($c['valor_unit'] ?? 0, 2, ',', '.'); ?></td>
                                        <td class="text-right">R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="total-row">
                                        <td colspan="3"><strong>TOTAL</strong></td>
                                        <td class="text-right"><strong>R$ <?php echo number_format($total, 2, ',', '.'); ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
    function atualizarValor() {
        var select = document.getElementById('produto_id');
        var option = select.options[select.selectedIndex];
        var valor = option.getAttribute('data-valor') || 0;
        document.getElementById('valor_unit').value = parseFloat(valor).toFixed(2);
    }
    </script>

<?php 
mysqli_close($conexao);
include 'includes/footer.php'; 
?>
</body>
</html>
