<?php
/**
 * Pousada Bona - Categorias de Despesas
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
    $mensagemPermissao = 'Você não tem permissão para acessar as categorias de despesas.';
    include 'includes/sem-permissao.php';
}

$userName = $_SESSION['user_name'] ?? 'Usuário';

// Criar tabela de categorias se não existir
$sqlCreateTable = "CREATE TABLE IF NOT EXISTS eiche_despesas_categorias (
    ID INT(11) NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    desconta_de ENUM('nenhum', 'diaria', 'consumo', 'servico') DEFAULT 'nenhum',
    cor VARCHAR(7) DEFAULT '#6366f1',
    ativo CHAR(1) DEFAULT 'S',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
mysqli_query($conexao, $sqlCreateTable);

// Inserir categorias padrão se tabela estiver vazia
$result = mysqli_query($conexao, "SELECT COUNT(*) as total FROM eiche_despesas_categorias");
if (mysqli_fetch_assoc($result)['total'] == 0) {
    $catsPadrao = [
        ['Água', 'nenhum', '#3b82f6'],
        ['Energia', 'nenhum', '#f59e0b'],
        ['Internet', 'nenhum', '#8b5cf6'],
        ['Telefone', 'nenhum', '#06b6d4'],
        ['Aluguel', 'nenhum', '#ef4444'],
        ['Funcionários', 'nenhum', '#22c55e'],
        ['Manutenção', 'nenhum', '#f97316'],
        ['Limpeza', 'consumo', '#14b8a6'],
        ['Alimentação', 'consumo', '#ec4899'],
        ['Impostos', 'nenhum', '#64748b'],
        ['Lavanderia', 'servico', '#a855f7'],
        ['Outros', 'nenhum', '#6b7280']
    ];
    foreach ($catsPadrao as $cat) {
        $sql = "INSERT INTO eiche_despesas_categorias (nome, desconta_de, cor) VALUES ('{$cat[0]}', '{$cat[1]}', '{$cat[2]}')";
        mysqli_query($conexao, $sql);
    }
}

$message = '';
$messageType = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $nome = mysqli_real_escape_string($conexao, trim($_POST['nome'] ?? ''));
        $desconta_de = mysqli_real_escape_string($conexao, $_POST['desconta_de'] ?? 'nenhum');
        $cor = mysqli_real_escape_string($conexao, $_POST['cor'] ?? '#6366f1');
        
        if (!empty($nome)) {
            $sql = "INSERT INTO eiche_despesas_categorias (nome, desconta_de, cor) VALUES ('$nome', '$desconta_de', '$cor')";
            if (mysqli_query($conexao, $sql)) {
                $message = 'Categoria cadastrada com sucesso!';
                $messageType = 'success';
            } else {
                $message = 'Erro ao cadastrar: ' . mysqli_error($conexao);
                $messageType = 'error';
            }
        }
    }
    
    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nome = mysqli_real_escape_string($conexao, trim($_POST['nome'] ?? ''));
        $desconta_de = mysqli_real_escape_string($conexao, $_POST['desconta_de'] ?? 'nenhum');
        $cor = mysqli_real_escape_string($conexao, $_POST['cor'] ?? '#6366f1');
        $ativo = $_POST['ativo'] ?? 'S';
        
        if ($id > 0 && !empty($nome)) {
            $sql = "UPDATE eiche_despesas_categorias SET 
                    nome = '$nome', desconta_de = '$desconta_de', cor = '$cor', ativo = '$ativo'
                    WHERE ID = $id";
            if (mysqli_query($conexao, $sql)) {
                $message = 'Categoria atualizada com sucesso!';
                $messageType = 'success';
            }
        }
    }
    
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Verificar se há despesas usando esta categoria
            $checkTable = mysqli_query($conexao, "SHOW TABLES LIKE 'eiche_despesas'");
            if (mysqli_num_rows($checkTable) > 0) {
                $result = mysqli_query($conexao, "SELECT nome FROM eiche_despesas_categorias WHERE ID = $id");
                $catNome = mysqli_fetch_assoc($result)['nome'] ?? '';
                $result = mysqli_query($conexao, "SELECT COUNT(*) as total FROM eiche_despesas WHERE categoria = '$catNome'");
                $count = mysqli_fetch_assoc($result)['total'] ?? 0;
                if ($count > 0) {
                    $message = "Não é possível excluir. Existem $count despesa(s) usando esta categoria.";
                    $messageType = 'error';
                } else {
                    mysqli_query($conexao, "DELETE FROM eiche_despesas_categorias WHERE ID = $id");
                    $message = 'Categoria excluída com sucesso!';
                    $messageType = 'success';
                }
            } else {
                mysqli_query($conexao, "DELETE FROM eiche_despesas_categorias WHERE ID = $id");
                $message = 'Categoria excluída com sucesso!';
                $messageType = 'success';
            }
        }
    }
}

// Buscar categorias
$sql = "SELECT * FROM eiche_despesas_categorias ORDER BY nome";
$result = mysqli_query($conexao, $sql);
$categorias = [];
while ($row = mysqli_fetch_assoc($result)) {
    $categorias[] = $row;
}

$pageTitle = 'Categorias de Despesas - Pousada Bona';

$descontaLabels = [
    'nenhum' => ['label' => 'Nenhum', 'icon' => '⬜', 'color' => '#6b7280'],
    'diaria' => ['label' => 'Diária', 'icon' => '🛏️', 'color' => '#3b82f6'],
    'consumo' => ['label' => 'Consumo', 'icon' => '🍽️', 'color' => '#f59e0b'],
    'servico' => ['label' => 'Serviço', 'icon' => '🛎️', 'color' => '#8b5cf6']
];

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>
    
    <main class="main-content">
        <div class="content-header">
            <div class="content-header-left">
                <a href="expenses.php" style="color: #666; text-decoration: none; font-size: 13px;">← Voltar para Despesas</a>
                <h1 style="margin-top: 5px;">📁 Categorias de Despesas</h1>
                <p>Gerencie as categorias e defina de onde será descontado</p>
            </div>
            <div class="content-header-actions">
                <button onclick="abrirModalNovo()" class="btn btn-primary">
                    ➕ Nova Categoria
                </button>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>" style="margin-bottom: 20px; padding: 12px; border-radius: 8px; background: <?= $messageType === 'success' ? '#dcfce7' : '#fee2e2' ?>; color: <?= $messageType === 'success' ? '#166534' : '#991b1b' ?>;">
            <?= $message ?>
        </div>
        <?php endif; ?>
        
        <!-- Legenda -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-body" style="padding: 15px;">
                <strong style="font-size: 12px;">📌 Desconta de:</strong>
                <div style="display: flex; gap: 20px; margin-top: 10px; flex-wrap: wrap;">
                    <?php foreach ($descontaLabels as $key => $val): ?>
                    <div style="display: flex; align-items: center; gap: 6px; font-size: 12px;">
                        <span style="width: 24px; height: 24px; background: <?= $val['color'] ?>; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px;">
                            <?= $val['icon'] ?>
                        </span>
                        <span><?= $val['label'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Lista de categorias -->
        <div class="card">
            <div class="card-body" style="padding: 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #f9fafb;">
                            <th style="padding: 12px; text-align: left; font-size: 11px; color: #666; width: 50px;">COR</th>
                            <th style="padding: 12px; text-align: left; font-size: 11px; color: #666;">NOME</th>
                            <th style="padding: 12px; text-align: center; font-size: 11px; color: #666;">DESCONTA DE</th>
                            <th style="padding: 12px; text-align: center; font-size: 11px; color: #666;">STATUS</th>
                            <th style="padding: 12px; text-align: center; font-size: 11px; color: #666;">AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categorias)): ?>
                        <tr>
                            <td colspan="5" style="padding: 40px; text-align: center; color: #999;">
                                Nenhuma categoria cadastrada.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($categorias as $cat): 
                            $descInfo = $descontaLabels[$cat['desconta_de']] ?? $descontaLabels['nenhum'];
                        ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 12px;">
                                <div style="width: 30px; height: 30px; background: <?= $cat['cor'] ?>; border-radius: 6px;"></div>
                            </td>
                            <td style="padding: 12px;">
                                <strong><?= htmlspecialchars($cat['nome']) ?></strong>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <span style="background: <?= $descInfo['color'] ?>; color: white; padding: 4px 12px; border-radius: 12px; font-size: 11px; display: inline-flex; align-items: center; gap: 5px;">
                                    <?= $descInfo['icon'] ?> <?= $descInfo['label'] ?>
                                </span>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <?php if ($cat['ativo'] === 'S'): ?>
                                <span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 12px; font-size: 11px;">
                                    ✅ Ativo
                                </span>
                                <?php else: ?>
                                <span style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 12px; font-size: 11px;">
                                    ❌ Inativo
                                </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <div style="display: flex; gap: 5px; justify-content: center;">
                                    <button onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($cat)) ?>)" class="btn-icon" title="Editar" style="background: #dbeafe; color: #1e40af; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer;">
                                        ✏️
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Excluir esta categoria?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $cat['ID'] ?>">
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
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modal Nova/Editar Categoria -->
<div id="modal-categoria" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; width: 90%; max-width: 450px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modal-titulo" style="margin: 0;">Nova Categoria</h3>
            <button onclick="fecharModal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">✕</button>
        </div>
        <form id="form-categoria" method="POST" style="padding: 20px;">
            <input type="hidden" name="action" id="form-action" value="add">
            <input type="hidden" name="id" id="form-id" value="">
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">Nome da Categoria *</label>
                <input type="text" name="nome" id="form-nome" required class="form-input" style="width: 100%; padding: 10px;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">Desconta de:</label>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                    <?php foreach ($descontaLabels as $key => $val): ?>
                    <label style="display: flex; align-items: center; gap: 8px; padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.2s;" class="desconta-option" data-value="<?= $key ?>">
                        <input type="radio" name="desconta_de" value="<?= $key ?>" <?= $key === 'nenhum' ? 'checked' : '' ?> style="display: none;">
                        <span style="width: 28px; height: 28px; background: <?= $val['color'] ?>; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px;">
                            <?= $val['icon'] ?>
                        </span>
                        <span style="font-size: 13px;"><?= $val['label'] ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">Cor</label>
                    <input type="color" name="cor" id="form-cor" value="#6366f1" class="form-input" style="width: 100%; padding: 5px; height: 45px;">
                </div>
                <div id="div-status" style="display: none;">
                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="ativo" id="form-ativo" class="form-input" style="width: 100%; padding: 10px;">
                        <option value="S">Ativo</option>
                        <option value="N">Inativo</option>
                    </select>
                </div>
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
.desconta-option.selected {
    border-color: #3b82f6;
    background: #eff6ff;
}
</style>

<script>
// Seleção de desconta_de
document.querySelectorAll('.desconta-option').forEach(function(opt) {
    opt.addEventListener('click', function() {
        document.querySelectorAll('.desconta-option').forEach(function(o) { o.classList.remove('selected'); });
        this.classList.add('selected');
        this.querySelector('input[type="radio"]').checked = true;
    });
    // Marcar inicialmente
    if (opt.querySelector('input[type="radio"]').checked) {
        opt.classList.add('selected');
    }
});

function abrirModalNovo() {
    document.getElementById('modal-titulo').textContent = 'Nova Categoria';
    document.getElementById('form-action').value = 'add';
    document.getElementById('form-id').value = '';
    document.getElementById('form-nome').value = '';
    document.getElementById('form-cor').value = '#6366f1';
    document.querySelectorAll('.desconta-option').forEach(function(o) { o.classList.remove('selected'); });
    document.querySelector('.desconta-option[data-value="nenhum"]').classList.add('selected');
    document.querySelector('input[name="desconta_de"][value="nenhum"]').checked = true;
    document.getElementById('div-status').style.display = 'none';
    document.getElementById('modal-categoria').style.display = 'flex';
}

function abrirModalEditar(cat) {
    document.getElementById('modal-titulo').textContent = 'Editar Categoria';
    document.getElementById('form-action').value = 'edit';
    document.getElementById('form-id').value = cat.ID;
    document.getElementById('form-nome').value = cat.nome;
    document.getElementById('form-cor').value = cat.cor;
    document.getElementById('form-ativo').value = cat.ativo;
    document.querySelectorAll('.desconta-option').forEach(function(o) { o.classList.remove('selected'); });
    var opt = document.querySelector('.desconta-option[data-value="' + cat.desconta_de + '"]');
    if (opt) {
        opt.classList.add('selected');
        opt.querySelector('input[type="radio"]').checked = true;
    }
    document.getElementById('div-status').style.display = 'block';
    document.getElementById('modal-categoria').style.display = 'flex';
}

function fecharModal() {
    document.getElementById('modal-categoria').style.display = 'none';
}

document.getElementById('modal-categoria').addEventListener('click', function(e) {
    if (e.target === this) fecharModal();
});
</script>

<?php 
mysqli_close($conexao);
include 'includes/footer.php'; 
?>

