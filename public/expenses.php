<?php
/**
 * Pousada Bona - Gestão de Despesas
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
    $mensagemPermissao = 'Você não tem permissão para acessar o controle de despesas. Contate o administrador para obter acesso.';
    include 'includes/sem-permissao.php';
}

$userName = $_SESSION['user_name'] ?? 'Usuário';

// Criar tabela se não existir
$sqlCreateTable = "CREATE TABLE IF NOT EXISTS eiche_despesas (
    ID INT(11) NOT NULL AUTO_INCREMENT,
    descricao VARCHAR(200) NOT NULL,
    categoria VARCHAR(50) DEFAULT 'Geral',
    valor DECIMAL(10,2) NOT NULL DEFAULT 0,
    data DATE NOT NULL,
    data_pagamento DATE DEFAULT NULL,
    status CHAR(1) DEFAULT 'P',
    observacao TEXT,
    ID_user INT(11) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
mysqli_query($conexao, $sqlCreateTable);

$message = '';
$messageType = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $descricao = mysqli_real_escape_string($conexao, trim($_POST['descricao'] ?? ''));
        $categoria = mysqli_real_escape_string($conexao, trim($_POST['categoria'] ?? 'Geral'));
        $valor = floatval($_POST['valor'] ?? 0);
        $data = $_POST['data'] ?? date('Y-m-d');
        $observacao = mysqli_real_escape_string($conexao, trim($_POST['observacao'] ?? ''));
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!empty($descricao) && $valor > 0) {
            $sql = "INSERT INTO eiche_despesas (descricao, categoria, valor, data, observacao, ID_user) 
                    VALUES ('$descricao', '$categoria', $valor, '$data', '$observacao', $userId)";
            if (mysqli_query($conexao, $sql)) {
                $message = 'Despesa cadastrada com sucesso!';
                $messageType = 'success';
            } else {
                $message = 'Erro ao cadastrar despesa: ' . mysqli_error($conexao);
                $messageType = 'error';
            }
        } else {
            $message = 'Preencha descrição e valor corretamente.';
            $messageType = 'error';
        }
    }
    
    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $descricao = mysqli_real_escape_string($conexao, trim($_POST['descricao'] ?? ''));
        $categoria = mysqli_real_escape_string($conexao, trim($_POST['categoria'] ?? 'Geral'));
        $valor = floatval($_POST['valor'] ?? 0);
        $data = $_POST['data'] ?? date('Y-m-d');
        $observacao = mysqli_real_escape_string($conexao, trim($_POST['observacao'] ?? ''));
        $status = $_POST['status'] ?? 'P';
        $dataPagamento = $status === 'P' ? 'NULL' : "'" . ($_POST['data_pagamento'] ?? date('Y-m-d')) . "'";
        
        if ($id > 0 && !empty($descricao) && $valor > 0) {
            $sql = "UPDATE eiche_despesas SET 
                    descricao = '$descricao', 
                    categoria = '$categoria', 
                    valor = $valor, 
                    data = '$data',
                    observacao = '$observacao',
                    status = '$status',
                    data_pagamento = $dataPagamento
                    WHERE ID = $id";
            if (mysqli_query($conexao, $sql)) {
                $message = 'Despesa atualizada com sucesso!';
                $messageType = 'success';
            } else {
                $message = 'Erro ao atualizar despesa: ' . mysqli_error($conexao);
                $messageType = 'error';
            }
        }
    }
    
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $sql = "DELETE FROM eiche_despesas WHERE ID = $id";
            if (mysqli_query($conexao, $sql)) {
                $message = 'Despesa excluída com sucesso!';
                $messageType = 'success';
            } else {
                $message = 'Erro ao excluir: ' . mysqli_error($conexao);
                $messageType = 'error';
            }
        }
    }
    
    if ($action === 'pagar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $hoje = date('Y-m-d');
            $sql = "UPDATE eiche_despesas SET status = 'G', data_pagamento = '$hoje' WHERE ID = $id";
            if (mysqli_query($conexao, $sql)) {
                $message = 'Despesa marcada como paga!';
                $messageType = 'success';
            }
        }
    }
}

// Filtros
$filtroMes = $_GET['mes'] ?? date('Y-m');
$filtroCategoria = $_GET['categoria'] ?? '';
$filtroStatus = $_GET['status'] ?? '';
$filtroDesconta = $_GET['desconta'] ?? '';

$whereClause = "WHERE DATE_FORMAT(d.data, '%Y-%m') = '$filtroMes'";
if (!empty($filtroCategoria)) {
    $filtroCategoria = mysqli_real_escape_string($conexao, $filtroCategoria);
    $whereClause .= " AND d.categoria = '$filtroCategoria'";
}
if (!empty($filtroStatus)) {
    $filtroStatus = mysqli_real_escape_string($conexao, $filtroStatus);
    $whereClause .= " AND d.status = '$filtroStatus'";
}
if (!empty($filtroDesconta)) {
    $filtroDesconta = mysqli_real_escape_string($conexao, $filtroDesconta);
    $whereClause .= " AND c.desconta_de = '$filtroDesconta'";
}

// Buscar despesas com JOIN para categorias
$checkCatTableDesp = mysqli_query($conexao, "SHOW TABLES LIKE 'eiche_despesas_categorias'");
if (mysqli_num_rows($checkCatTableDesp) > 0) {
    $sql = "SELECT d.*, c.desconta_de, c.cor as cat_cor 
            FROM eiche_despesas d
            LEFT JOIN eiche_despesas_categorias c ON d.categoria = c.nome
            $whereClause 
            ORDER BY d.data DESC, d.ID DESC";
} else {
    $sql = "SELECT d.*, 'nenhum' as desconta_de, '#6366f1' as cat_cor 
            FROM eiche_despesas d
            $whereClause 
            ORDER BY d.data DESC, d.ID DESC";
}
$result = mysqli_query($conexao, $sql);
$despesas = [];
$totalDespesas = 0;
$totalPendentes = 0;
$totalPagas = 0;
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $despesas[] = $row;
        $totalDespesas += $row['valor'];
        if ($row['status'] === 'P') {
            $totalPendentes += $row['valor'];
        } else {
            $totalPagas += $row['valor'];
        }
    }
}

// Buscar categorias cadastradas
$categorias = [];
$categoriasInfo = [];
$checkCatTable = mysqli_query($conexao, "SHOW TABLES LIKE 'eiche_despesas_categorias'");
if (mysqli_num_rows($checkCatTable) > 0) {
    $sqlCats = "SELECT * FROM eiche_despesas_categorias WHERE ativo = 'S' ORDER BY nome";
    $resultCats = mysqli_query($conexao, $sqlCats);
    while ($row = mysqli_fetch_assoc($resultCats)) {
        $categorias[] = $row['nome'];
        $categoriasInfo[$row['nome']] = $row;
    }
}
// Fallback se não houver categorias
if (empty($categorias)) {
    $categorias = ['Água', 'Energia', 'Internet', 'Telefone', 'Aluguel', 'Funcionários', 'Manutenção', 'Limpeza', 'Alimentação', 'Impostos', 'Outros'];
}

$descontaLabels = [
    'nenhum' => ['label' => 'Nenhum', 'icon' => '⬜'],
    'diaria' => ['label' => 'Diária', 'icon' => '🛏️'],
    'consumo' => ['label' => 'Consumo', 'icon' => '🍽️'],
    'servico' => ['label' => 'Serviço', 'icon' => '🛎️']
];

$pageTitle = 'Despesas - Pousada Bona';

$meses = [1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',
          7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'];

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>
    
    <main class="main-content">
        <div class="content-header">
            <div class="content-header-left">
                <h1>💸 Despesas</h1>
                <p>Controle de despesas da pousada</p>
            </div>
            <div class="content-header-actions">
                <a href="expense-categories.php" class="btn btn-secondary" style="margin-right: 10px;">
                    📁 Categorias
                </a>
                <button onclick="abrirModalNovo()" class="btn btn-primary">
                    ➕ Nova Despesa
                </button>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>" style="margin-bottom: 20px; padding: 12px; border-radius: 8px; background: <?= $messageType === 'success' ? '#dcfce7' : '#fee2e2' ?>; color: <?= $messageType === 'success' ? '#166534' : '#991b1b' ?>;">
            <?= $message ?>
        </div>
        <?php endif; ?>
        
        <!-- Cards de resumo -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 20px;">
            <div style="background: linear-gradient(135deg, #dc2626, #b91c1c); padding: 20px; border-radius: 12px; color: white;">
                <div style="font-size: 11px; opacity: 0.9;">Total do Mês</div>
                <div style="font-size: 24px; font-weight: 700;">R$ <?= number_format($totalDespesas, 2, ',', '.') ?></div>
            </div>
            <div style="background: linear-gradient(135deg, #f59e0b, #d97706); padding: 20px; border-radius: 12px; color: white;">
                <div style="font-size: 11px; opacity: 0.9;">Pendentes</div>
                <div style="font-size: 24px; font-weight: 700;">R$ <?= number_format($totalPendentes, 2, ',', '.') ?></div>
            </div>
            <div style="background: linear-gradient(135deg, #22c55e, #16a34a); padding: 20px; border-radius: 12px; color: white;">
                <div style="font-size: 11px; opacity: 0.9;">Pagas</div>
                <div style="font-size: 24px; font-weight: 700;">R$ <?= number_format($totalPagas, 2, ',', '.') ?></div>
            </div>
            <div style="background: linear-gradient(135deg, #6366f1, #4f46e5); padding: 20px; border-radius: 12px; color: white;">
                <div style="font-size: 11px; opacity: 0.9;">Quantidade</div>
                <div style="font-size: 24px; font-weight: 700;"><?= count($despesas) ?></div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-body" style="padding: 15px;">
                <form method="GET" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                    <div>
                        <label style="display: block; font-size: 11px; color: #666; margin-bottom: 4px;">📅 Mês</label>
                        <input type="month" name="mes" value="<?= $filtroMes ?>" class="form-input" style="padding: 8px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; color: #666; margin-bottom: 4px;">📁 Categoria</label>
                        <select name="categoria" class="form-input" style="padding: 8px;">
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $filtroCategoria === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; color: #666; margin-bottom: 4px;">📊 Status</label>
                        <select name="status" class="form-input" style="padding: 8px;">
                            <option value="">Todos</option>
                            <option value="P" <?= $filtroStatus === 'P' ? 'selected' : '' ?>>Pendente</option>
                            <option value="G" <?= $filtroStatus === 'G' ? 'selected' : '' ?>>Pago</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; color: #666; margin-bottom: 4px;">💰 Desconta de</label>
                        <select name="desconta" class="form-input" style="padding: 8px;">
                            <option value="">Todos</option>
                            <option value="diaria" <?= $filtroDesconta === 'diaria' ? 'selected' : '' ?>>🛏️ Diária</option>
                            <option value="consumo" <?= $filtroDesconta === 'consumo' ? 'selected' : '' ?>>🍽️ Consumo</option>
                            <option value="servico" <?= $filtroDesconta === 'servico' ? 'selected' : '' ?>>🛎️ Serviço</option>
                            <option value="nenhum" <?= $filtroDesconta === 'nenhum' ? 'selected' : '' ?>>⬜ Nenhum</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 20px;">🔍 Filtrar</button>
                    <a href="expenses.php" class="btn btn-secondary" style="padding: 8px 20px;">🔄 Limpar</a>
                </form>
            </div>
        </div>
        
        <!-- Lista de despesas -->
        <div class="card">
            <div class="card-body" style="padding: 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #f9fafb;">
                            <th style="padding: 12px; text-align: left; font-size: 11px; color: #666;">DATA</th>
                            <th style="padding: 12px; text-align: left; font-size: 11px; color: #666;">DESCRIÇÃO</th>
                            <th style="padding: 12px; text-align: left; font-size: 11px; color: #666;">CATEGORIA</th>
                            <th style="padding: 12px; text-align: right; font-size: 11px; color: #666;">VALOR</th>
                            <th style="padding: 12px; text-align: center; font-size: 11px; color: #666;">STATUS</th>
                            <th style="padding: 12px; text-align: center; font-size: 11px; color: #666;">AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($despesas)): ?>
                        <tr>
                            <td colspan="6" style="padding: 40px; text-align: center; color: #999;">
                                Nenhuma despesa encontrada para o período selecionado.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($despesas as $d): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 12px;">
                                <strong><?= date('d/m/Y', strtotime($d['data'])) ?></strong>
                            </td>
                            <td style="padding: 12px;">
                                <?= htmlspecialchars($d['descricao']) ?>
                                <?php if ($d['observacao']): ?>
                                <br><small style="color: #999;"><?= htmlspecialchars(substr($d['observacao'], 0, 50)) ?></small>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px;">
                                <?php 
                                    $catCor = $d['cat_cor'] ?? '#e0e7ff';
                                    $descDe = $d['desconta_de'] ?? 'nenhum';
                                    $descInfo = $descontaLabels[$descDe] ?? $descontaLabels['nenhum'];
                                ?>
                                <span style="background: <?= $catCor ?>20; color: <?= $catCor ?>; border: 1px solid <?= $catCor ?>; padding: 3px 8px; border-radius: 12px; font-size: 11px;">
                                    <?= htmlspecialchars($d['categoria']) ?>
                                </span>
                                <?php if ($descDe !== 'nenhum'): ?>
                                <span title="Desconta de: <?= $descInfo['label'] ?>" style="font-size: 12px; margin-left: 3px;">
                                    <?= $descInfo['icon'] ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px; text-align: right; font-weight: 600; color: #dc2626;">
                                R$ <?= number_format($d['valor'], 2, ',', '.') ?>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <?php if ($d['status'] === 'P'): ?>
                                <span style="background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 12px; font-size: 11px;">
                                    ⏳ Pendente
                                </span>
                                <?php else: ?>
                                <span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 12px; font-size: 11px;">
                                    ✅ Pago
                                </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <div style="display: flex; gap: 5px; justify-content: center;">
                                    <?php if ($d['status'] === 'P'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="pagar">
                                        <input type="hidden" name="id" value="<?= $d['ID'] ?>">
                                        <button type="submit" class="btn-icon" title="Marcar como pago" style="background: #dcfce7; color: #166534; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer;">
                                            ✅
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <button onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($d)) ?>)" class="btn-icon" title="Editar" style="background: #dbeafe; color: #1e40af; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer;">
                                        ✏️
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Excluir esta despesa?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $d['ID'] ?>">
                                        <button type="submit" class="btn-icon" title="Excluir" style="background: #fee2e2; color: #991b1b; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer;">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($despesas)): ?>
                    <tfoot>
                        <tr style="background: #f9fafb; font-weight: bold;">
                            <td colspan="3" style="padding: 12px;">TOTAL</td>
                            <td style="padding: 12px; text-align: right; color: #dc2626;">
                                R$ <?= number_format($totalDespesas, 2, ',', '.') ?>
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modal Nova/Editar Despesa -->
<div id="modal-despesa" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modal-titulo" style="margin: 0;">Nova Despesa</h3>
            <button onclick="fecharModal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">✕</button>
        </div>
        <form id="form-despesa" method="POST" style="padding: 20px;">
            <input type="hidden" name="action" id="form-action" value="add">
            <input type="hidden" name="id" id="form-id" value="">
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">Descrição *</label>
                <input type="text" name="descricao" id="form-descricao" required class="form-input" style="width: 100%; padding: 10px;">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">Categoria <a href="expense-categories.php" style="font-size: 10px;">⚙️ Gerenciar</a></label>
                    <select name="categoria" id="form-categoria" class="form-input" style="width: 100%; padding: 10px;">
                        <?php foreach ($categorias as $cat): 
                            $catInfo = $categoriasInfo[$cat] ?? null;
                            $descDe = $catInfo['desconta_de'] ?? 'nenhum';
                            $descInfo = $descontaLabels[$descDe] ?? $descontaLabels['nenhum'];
                        ?>
                        <option value="<?= htmlspecialchars($cat) ?>"><?= $descInfo['icon'] ?> <?= htmlspecialchars($cat) ?><?= $descDe !== 'nenhum' ? ' (desconta ' . $descInfo['label'] . ')' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">Valor *</label>
                    <input type="number" name="valor" id="form-valor" step="0.01" min="0.01" required class="form-input" style="width: 100%; padding: 10px;">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">Data *</label>
                    <input type="date" name="data" id="form-data" required class="form-input" style="width: 100%; padding: 10px;" value="<?= date('Y-m-d') ?>">
                </div>
                <div id="div-status" style="display: none;">
                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="status" id="form-status" class="form-input" style="width: 100%; padding: 10px;">
                        <option value="P">Pendente</option>
                        <option value="G">Pago</option>
                    </select>
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">Observação</label>
                <textarea name="observacao" id="form-observacao" rows="3" class="form-input" style="width: 100%; padding: 10px;"></textarea>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="fecharModal()" class="btn btn-secondary" style="flex: 1;">Cancelar</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">💾 Salvar</button>
            </div>
        </form>
    </div>
</div>

<style>
.form-input {
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 13px;
}
.form-input:focus {
    outline: none;
    border-color: #3b82f6;
}
</style>

<script>
function abrirModalNovo() {
    document.getElementById('modal-titulo').textContent = 'Nova Despesa';
    document.getElementById('form-action').value = 'add';
    document.getElementById('form-id').value = '';
    document.getElementById('form-descricao').value = '';
    document.getElementById('form-categoria').value = 'Geral';
    document.getElementById('form-valor').value = '';
    document.getElementById('form-data').value = '<?= date('Y-m-d') ?>';
    document.getElementById('form-observacao').value = '';
    document.getElementById('div-status').style.display = 'none';
    document.getElementById('modal-despesa').style.display = 'flex';
}

function abrirModalEditar(despesa) {
    document.getElementById('modal-titulo').textContent = 'Editar Despesa';
    document.getElementById('form-action').value = 'edit';
    document.getElementById('form-id').value = despesa.ID;
    document.getElementById('form-descricao').value = despesa.descricao;
    document.getElementById('form-categoria').value = despesa.categoria;
    document.getElementById('form-valor').value = despesa.valor;
    document.getElementById('form-data').value = despesa.data;
    document.getElementById('form-observacao').value = despesa.observacao || '';
    document.getElementById('form-status').value = despesa.status;
    document.getElementById('div-status').style.display = 'block';
    document.getElementById('modal-despesa').style.display = 'flex';
}

function fecharModal() {
    document.getElementById('modal-despesa').style.display = 'none';
}

// Fechar modal clicando fora
document.getElementById('modal-despesa').addEventListener('click', function(e) {
    if (e.target === this) fecharModal();
});
</script>

<?php 
mysqli_close($conexao);
include 'includes/footer.php'; 
?>

