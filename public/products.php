<?php
/**
 * Pousada Bona - Cadastro de Produtos (Consumos) e Serviços
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

$mensagem = '';
$erro = '';

// Tipo padrão do filtro (P = Produtos/Consumos, S = Serviços)
$tipoAtual = $_GET['tipo'] ?? 'P';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'salvar') {
        $id = (int)($_POST['id'] ?? 0);
        $description = mysqli_real_escape_string($conexao, trim($_POST['description'] ?? ''));
        $price = (float)str_replace(['.', ','], ['', '.'], $_POST['price'] ?? 0);
        $pors = $_POST['pors'] ?? 'P';
        $unit = mysqli_real_escape_string($conexao, $_POST['unit'] ?? 'UN');
        $balance = (float)str_replace(['.', ','], ['', '.'], $_POST['balance'] ?? 0);
        $balance_min = (float)str_replace(['.', ','], ['', '.'], $_POST['balance_min'] ?? 0);
        $cost_price = (float)str_replace(['.', ','], ['', '.'], $_POST['cost_price'] ?? 0);
        $rstatus = $_POST['rstatus'] ?? 'A';
        
        if (empty($description)) {
            $erro = 'Descrição é obrigatória.';
        } else {
            if ($id > 0) {
                $sql = "UPDATE eiche_prodorserv SET 
                        description = '$description',
                        price = $price,
                        pors = '$pors',
                        unit = '$unit',
                        balance = $balance,
                        balance_min = $balance_min,
                        cost_price = $cost_price,
                        rstatus = '$rstatus'
                        WHERE ID = $id";
                if (mysqli_query($conexao, $sql)) {
                    $mensagem = ($pors === 'P' ? 'Produto' : 'Serviço') . ' atualizado com sucesso!';
                } else {
                    $erro = 'Erro ao atualizar: ' . mysqli_error($conexao);
                }
            } else {
                $sql = "INSERT INTO eiche_prodorserv (description, price, pors, unit, balance, balance_min, cost_price, rstatus) 
                        VALUES ('$description', $price, '$pors', '$unit', $balance, $balance_min, $cost_price, '$rstatus')";
                if (mysqli_query($conexao, $sql)) {
                    $mensagem = ($pors === 'P' ? 'Produto' : 'Serviço') . ' cadastrado com sucesso!';
                } else {
                    $erro = 'Erro ao cadastrar: ' . mysqli_error($conexao);
                }
            }
            $tipoAtual = $pors;
        }
    } elseif ($acao === 'excluir') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $checkCons = mysqli_query($conexao, "SELECT COUNT(*) as total FROM eiche_hosp_lnk_cons_hosp WHERE ID_cons = $id");
            $checkServ = mysqli_query($conexao, "SELECT COUNT(*) as total FROM eiche_hosp_lnk_serv_hosp WHERE ID_serv = $id");
            $totalCons = mysqli_fetch_assoc($checkCons)['total'] ?? 0;
            $totalServ = mysqli_fetch_assoc($checkServ)['total'] ?? 0;
            
            if ($totalCons > 0 || $totalServ > 0) {
                mysqli_query($conexao, "UPDATE eiche_prodorserv SET rstatus = 'I' WHERE ID = $id");
                $mensagem = 'Item desativado (possui histórico de uso).';
            } else {
                mysqli_query($conexao, "DELETE FROM eiche_prodorserv WHERE ID = $id");
                $mensagem = 'Item excluído com sucesso!';
            }
        }
    }
}

// Filtros
$filtroStatus = $_GET['status'] ?? 'A';
$busca = mysqli_real_escape_string($conexao, $_GET['busca'] ?? '');

// Montar query
$where = "WHERE pors = '$tipoAtual'";
if ($filtroStatus) {
    $where .= " AND rstatus = '$filtroStatus'";
}
if ($busca) {
    $where .= " AND description LIKE '%$busca%'";
}

$sql = "SELECT * FROM eiche_prodorserv $where ORDER BY description";
$result = mysqli_query($conexao, $sql);
$itens = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $itens[] = $row;
    }
}

// Buscar unidades
$sqlUnits = "SELECT * FROM eiche_pos_unit ORDER BY ID";
$resultUnits = mysqli_query($conexao, $sqlUnits);
$unidades = [];
if ($resultUnits) {
    while ($row = mysqli_fetch_assoc($resultUnits)) {
        $unidades[] = $row;
    }
}

// Estatísticas
$sqlStats = "SELECT 
    SUM(CASE WHEN pors = 'P' AND rstatus = 'A' THEN 1 ELSE 0 END) as produtos,
    SUM(CASE WHEN pors = 'S' AND rstatus = 'A' THEN 1 ELSE 0 END) as servicos
    FROM eiche_prodorserv";
$stats = mysqli_fetch_assoc(mysqli_query($conexao, $sqlStats));

$tituloTipo = $tipoAtual === 'P' ? '🍽️ Produtos (Consumos)' : '🛎️ Serviços';
$pageTitle = ($tipoAtual === 'P' ? 'Produtos' : 'Serviços') . ' - Pousada Bona';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title"><?= $tituloTipo ?></h1>
                <p class="page-subtitle">Cadastro de <?= $tipoAtual === 'P' ? 'produtos para consumo' : 'serviços oferecidos' ?></p>
            </div>
            <button class="btn btn-primary" onclick="abrirModalNovo()">
                <span>➕</span> Novo <?= $tipoAtual === 'P' ? 'Produto' : 'Serviço' ?>
            </button>
        </div>
        
        <?php if ($mensagem): ?>
        <div style="background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #bbf7d0;">
            ✅ <?= $mensagem ?>
        </div>
        <?php endif; ?>
        <?php if ($erro): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca;">
            ❌ <?= $erro ?>
        </div>
        <?php endif; ?>
        
        <!-- Tabs -->
        <div style="display: inline-flex; flex-direction: row; gap: 10px; margin-bottom: 20px; flex-wrap: nowrap;">
            <a href="?tipo=P" style="display: inline-block; padding: 10px 20px; background: <?= $tipoAtual === 'P' ? 'linear-gradient(135deg, #2d5a3d, #1a5f2a)' : '#f3f4f6' ?>; color: <?= $tipoAtual === 'P' ? 'white' : '#374151' ?>; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 500; white-space: nowrap;">
                🍽️ Produtos (<?= $stats['produtos'] ?? 0 ?>)
            </a>
            <a href="?tipo=S" style="display: inline-block; padding: 10px 20px; background: <?= $tipoAtual === 'S' ? 'linear-gradient(135deg, #2d5a3d, #1a5f2a)' : '#f3f4f6' ?>; color: <?= $tipoAtual === 'S' ? 'white' : '#374151' ?>; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 500; white-space: nowrap;">
                🛎️ Serviços (<?= $stats['servicos'] ?? 0 ?>)
            </a>
        </div>
        
        <!-- Filtros -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-body" style="padding: 15px;">
                <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                    <input type="hidden" name="tipo" value="<?= $tipoAtual ?>">
                    <div style="flex: 1; min-width: 200px;">
                        <input type="text" name="busca" class="form-input" placeholder="🔍 Buscar por descrição..." 
                               value="<?= htmlspecialchars($busca) ?>">
                    </div>
                    <div>
                        <select name="status" class="form-select" style="min-width: 130px;">
                            <option value="A" <?= $filtroStatus === 'A' ? 'selected' : '' ?>>✅ Ativos</option>
                            <option value="I" <?= $filtroStatus === 'I' ? 'selected' : '' ?>>🚫 Inativos</option>
                            <option value="" <?= $filtroStatus === '' ? 'selected' : '' ?>>📋 Todos</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-secondary">Filtrar</button>
                    <a href="?tipo=<?= $tipoAtual ?>" class="btn btn-outline">Limpar</a>
                </form>
            </div>
        </div>
        
        <!-- Lista -->
        <div class="card">
            <div class="card-body" style="padding: 0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th style="width: 80px;">Unidade</th>
                            <th style="width: 120px; text-align: right;">Preço</th>
                            <?php if ($tipoAtual === 'P'): ?>
                            <th style="width: 80px; text-align: center;">Estoque</th>
                            <?php endif; ?>
                            <th style="width: 80px; text-align: center;">Status</th>
                            <th style="width: 100px; text-align: center;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($itens)): ?>
                        <tr>
                            <td colspan="<?= $tipoAtual === 'P' ? 6 : 5 ?>" style="text-align: center; padding: 40px; color: #6b7280;">
                                Nenhum <?= $tipoAtual === 'P' ? 'produto' : 'serviço' ?> encontrado.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($itens as $item): ?>
                        <tr style="<?= $item['rstatus'] === 'I' ? 'opacity: 0.5;' : '' ?>">
                            <td>
                                <strong><?= htmlspecialchars($item['description']) ?></strong>
                            </td>
                            <td style="text-align: center; font-size: 12px; color: #6b7280;">
                                <?= htmlspecialchars($item['unit'] ?? 'UN') ?>
                            </td>
                            <td style="text-align: right; font-weight: 600; color: #166534;">
                                R$ <?= number_format($item['price'], 2, ',', '.') ?>
                            </td>
                            <?php if ($tipoAtual === 'P'): ?>
                            <td style="text-align: center;">
                                <span style="<?= ($item['balance'] <= $item['balance_min']) ? 'color: #dc2626; font-weight: 600;' : '' ?>">
                                    <?= number_format($item['balance'], 0, ',', '.') ?>
                                </span>
                            </td>
                            <?php endif; ?>
                            <td style="text-align: center;">
                                <?php if ($item['rstatus'] === 'A'): ?>
                                <span style="background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 10px; font-size: 10px;">Ativo</span>
                                <?php else: ?>
                                <span style="background: #fee2e2; color: #dc2626; padding: 3px 8px; border-radius: 10px; font-size: 10px;">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <button type="button" style="background:none;border:none;cursor:pointer;font-size:16px;" 
                                        onclick='editarItem(<?= json_encode($item) ?>)' title="Editar">✏️</button>
                                <button type="button" style="background:none;border:none;cursor:pointer;font-size:16px;" 
                                        onclick="excluirItem(<?= $item['ID'] ?>, '<?= addslashes($item['description']) ?>')" title="Excluir">🗑️</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modal de Cadastro/Edição -->
<div id="modal-cadastro" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:12px; width:90%; max-width:500px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:20px; border-bottom:1px solid #e5e7eb;">
            <h3 id="modal-titulo" style="margin:0; font-size:18px;">Novo Item</h3>
            <button onclick="fecharModal()" style="background:none; border:none; font-size:28px; cursor:pointer; color:#6b7280; line-height:1;">&times;</button>
        </div>
        
        <form method="POST" id="form-item">
            <input type="hidden" name="acao" value="salvar">
            <input type="hidden" name="id" id="item-id" value="0">
            <input type="hidden" name="pors" id="item-pors" value="<?= $tipoAtual ?>">
            
            <div style="padding:20px;">
                <div style="margin-bottom:15px;">
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Descrição *</label>
                    <input type="text" name="description" id="item-description" required 
                           style="width:100%; padding:12px; font-size:14px; border:1px solid #d1d5db; border-radius:8px; box-sizing:border-box;"
                           placeholder="Ex: <?= $tipoAtual === 'P' ? 'Água Mineral 500ml' : 'Lavanderia' ?>">
                </div>
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Preço de Venda *</label>
                        <input type="text" name="price" id="item-price" required 
                               style="width:100%; padding:12px; font-size:14px; border:1px solid #d1d5db; border-radius:8px; text-align:right; box-sizing:border-box;"
                               placeholder="0,00">
                    </div>
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Unidade</label>
                        <select name="unit" id="item-unit" style="width:100%; padding:12px; font-size:14px; border:1px solid #d1d5db; border-radius:8px; box-sizing:border-box;">
                            <option value="UN">UN - Unidade</option>
                            <?php foreach ($unidades as $un): ?>
                            <option value="<?= $un['ID'] ?>"><?= htmlspecialchars($un['ID']) ?> - <?= htmlspecialchars($un['description']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <?php if ($tipoAtual === 'P'): ?>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Estoque</label>
                        <input type="text" name="balance" id="item-balance" 
                               style="width:100%; padding:12px; font-size:14px; border:1px solid #d1d5db; border-radius:8px; text-align:right; box-sizing:border-box;"
                               placeholder="0" value="0">
                    </div>
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Est. Mínimo</label>
                        <input type="text" name="balance_min" id="item-balance_min" 
                               style="width:100%; padding:12px; font-size:14px; border:1px solid #d1d5db; border-radius:8px; text-align:right; box-sizing:border-box;"
                               placeholder="0" value="0">
                    </div>
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Custo</label>
                        <input type="text" name="cost_price" id="item-cost_price" 
                               style="width:100%; padding:12px; font-size:14px; border:1px solid #d1d5db; border-radius:8px; text-align:right; box-sizing:border-box;"
                               placeholder="0,00" value="0,00">
                    </div>
                </div>
                <?php else: ?>
                <input type="hidden" name="balance" value="0">
                <input type="hidden" name="balance_min" value="0">
                <input type="hidden" name="cost_price" value="0">
                <?php endif; ?>
                
                <div style="margin-bottom:15px;">
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Status</label>
                    <select name="rstatus" id="item-rstatus" style="width:100%; padding:12px; font-size:14px; border:1px solid #d1d5db; border-radius:8px; box-sizing:border-box;">
                        <option value="A">✅ Ativo</option>
                        <option value="I">🚫 Inativo</option>
                    </select>
                </div>
            </div>
            
            <div style="padding:15px 20px; border-top:1px solid #e5e7eb; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="fecharModal()" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">💾 Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- Form oculto para exclusão -->
<form id="form-excluir" method="POST" style="display:none;">
    <input type="hidden" name="acao" value="excluir">
    <input type="hidden" name="id" id="excluir-id">
</form>

<style>
.tab-btn {
    display: inline-block;
    padding: 10px 20px;
    background: #f3f4f6;
    color: #374151;
    text-decoration: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
}
.tab-btn:hover {
    background: #e5e7eb;
}
.tab-btn.active {
    background: linear-gradient(135deg, #2d5a3d, #1a5f2a);
    color: white;
}
.btn-outline {
    background: transparent;
    border: 1px solid #d1d5db;
    color: #374151;
}
.btn-outline:hover {
    background: #f3f4f6;
}
</style>

<script>
function abrirModalNovo() {
    document.getElementById('modal-titulo').textContent = 'Novo <?= $tipoAtual === "P" ? "Produto" : "Serviço" ?>';
    document.getElementById('item-id').value = 0;
    document.getElementById('item-description').value = '';
    document.getElementById('item-price').value = '';
    document.getElementById('item-unit').value = 'UN';
    document.getElementById('item-rstatus').value = 'A';
    <?php if ($tipoAtual === 'P'): ?>
    document.getElementById('item-balance').value = '0';
    document.getElementById('item-balance_min').value = '0';
    document.getElementById('item-cost_price').value = '0,00';
    <?php endif; ?>
    
    var modal = document.getElementById('modal-cadastro');
    modal.style.display = 'flex';
}

function fecharModal() {
    document.getElementById('modal-cadastro').style.display = 'none';
}

function editarItem(item) {
    document.getElementById('modal-titulo').textContent = 'Editar <?= $tipoAtual === "P" ? "Produto" : "Serviço" ?>';
    document.getElementById('item-id').value = item.ID;
    document.getElementById('item-description').value = item.description || '';
    document.getElementById('item-price').value = formatarValor(item.price);
    document.getElementById('item-unit').value = item.unit || 'UN';
    document.getElementById('item-rstatus').value = item.rstatus || 'A';
    <?php if ($tipoAtual === 'P'): ?>
    document.getElementById('item-balance').value = item.balance || 0;
    document.getElementById('item-balance_min').value = item.balance_min || 0;
    document.getElementById('item-cost_price').value = formatarValor(item.cost_price);
    <?php endif; ?>
    
    var modal = document.getElementById('modal-cadastro');
    modal.style.display = 'flex';
}

function excluirItem(id, nome) {
    if (confirm('Tem certeza que deseja excluir "' + nome + '"?')) {
        document.getElementById('excluir-id').value = id;
        document.getElementById('form-excluir').submit();
    }
}

function formatarValor(valor) {
    if (!valor) return '0,00';
    return parseFloat(valor).toFixed(2).replace('.', ',');
}

// Fechar modal ao clicar fora
document.getElementById('modal-cadastro').addEventListener('click', function(e) {
    if (e.target === this) fecharModal();
});

// Fechar modal com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') fecharModal();
});
</script>

<?php 
include 'includes/footer.php';
mysqli_close($conexao);
?>
