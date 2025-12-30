<?php
/**
 * Pousada Bona - Grupos de Quartos
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

$userName = $_SESSION['user_name'] ?? 'Usuário';
$mensagem = '';
$erro = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'salvar') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $nome = mysqli_real_escape_string($conexao, $_POST['nome'] ?? '');
        $valor = (float)($_POST['valor'] ?? 0);
        $vlr_ce = (float)($_POST['vlr_ce'] ?? 0);
        $vlr_add_base = (float)($_POST['vlr_add_base'] ?? 0);
        $vlr_add = (float)($_POST['vlr_add'] ?? 0);
        
        if (empty($nome)) {
            $erro = 'Nome do grupo é obrigatório';
        } else {
            if ($id > 0) {
                // Atualizar
                $sql = "UPDATE eiche_hosp_gruposq SET nome = '$nome', valor = $valor, vlr_ce = $vlr_ce, vlr_add_base = $vlr_add_base, vlr_add = $vlr_add WHERE ID = $id";
                if (mysqli_query($conexao, $sql)) {
                    $mensagem = 'Grupo atualizado com sucesso!';
                } else {
                    $erro = 'Erro ao atualizar: ' . mysqli_error($conexao);
                }
            } else {
                // Inserir
                $sql = "INSERT INTO eiche_hosp_gruposq (nome, valor, vlr_ce, vlr_add_base, vlr_add) VALUES ('$nome', $valor, $vlr_ce, $vlr_add_base, $vlr_add)";
                if (mysqli_query($conexao, $sql)) {
                    $mensagem = 'Grupo criado com sucesso!';
                } else {
                    $erro = 'Erro ao criar: ' . mysqli_error($conexao);
                }
            }
        }
    } elseif ($acao === 'excluir') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Verificar se há quartos associados
            $check = mysqli_query($conexao, "SELECT COUNT(*) as total FROM eiche_hosp_quartos WHERE grupo = $id");
            $row = mysqli_fetch_assoc($check);
            if ($row['total'] > 0) {
                $erro = 'Não é possível excluir: há ' . $row['total'] . ' quarto(s) associado(s) a este grupo.';
            } else {
                if (mysqli_query($conexao, "DELETE FROM eiche_hosp_gruposq WHERE ID = $id")) {
                    $mensagem = 'Grupo excluído com sucesso!';
                } else {
                    $erro = 'Erro ao excluir: ' . mysqli_error($conexao);
                }
            }
        }
    }
}

// Buscar grupos
$grupos = [];
$sql = "SELECT g.*, (SELECT COUNT(*) FROM eiche_hosp_quartos WHERE grupo = g.ID) as qtd_quartos
        FROM eiche_hosp_gruposq g ORDER BY g.nome";
$result = mysqli_query($conexao, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $grupos[] = $row;
    }
}

// Buscar grupo para edição
$grupoEditar = null;
if (isset($_GET['editar'])) {
    $editId = (int)$_GET['editar'];
    $result = mysqli_query($conexao, "SELECT * FROM eiche_hosp_gruposq WHERE ID = $editId");
    if ($result && mysqli_num_rows($result) > 0) {
        $grupoEditar = mysqli_fetch_assoc($result);
    }
}

$pageTitle = 'Grupos de Quartos - Pousada Bona';

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>
    
    <main class="main-content">
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h1 style="font-size: 18px; margin: 0;">Grupos de Quartos</h1>
                <p style="font-size: 12px; color: #666; margin: 5px 0 0 0;">Gerencie os grupos e valores das acomodações</p>
            </div>
            <a href="rooms.php" class="btn" style="padding: 8px 14px; border: 1px solid #ddd; border-radius: 5px; background: white; text-decoration: none; font-size: 12px; color: #333;">← Voltar para Quartos</a>
        </div>
        
        <?php if ($mensagem): ?>
        <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 12px 16px; border-radius: 6px; margin-bottom: 15px; font-size: 13px;">
            ✅ <?php echo $mensagem; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
        <div style="background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 15px; font-size: 13px;">
            ❌ <?php echo $erro; ?>
        </div>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 1fr 350px; gap: 20px;">
            <!-- Lista de Grupos -->
            <div class="card" style="background: white; border: 1px solid #ddd; border-radius: 8px;">
                <div class="card-header" style="padding: 14px 18px; border-bottom: 1px solid #eee; font-weight: 600; font-size: 14px; background: #f9fafb;">
                    📋 Grupos Cadastrados
                </div>
                <div class="card-body" style="padding: 0;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                        <thead>
                            <tr style="background: #f9fafb;">
                                <th style="padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee;">Nome</th>
                                <th style="padding: 10px 12px; text-align: right; border-bottom: 1px solid #eee;">Valor Base</th>
                                <th style="padding: 10px 12px; text-align: right; border-bottom: 1px solid #eee;">Cama Extra</th>
                                <th style="padding: 10px 12px; text-align: center; border-bottom: 1px solid #eee;">Quartos</th>
                                <th style="padding: 10px 12px; text-align: center; border-bottom: 1px solid #eee;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($grupos)): ?>
                            <tr>
                                <td colspan="5" style="padding: 30px; text-align: center; color: #999;">
                                    Nenhum grupo cadastrado
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($grupos as $g): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 10px 12px; font-weight: 500;"><?php echo htmlspecialchars($g['nome']); ?></td>
                                <td style="padding: 10px 12px; text-align: right;">R$ <?php echo number_format($g['valor'], 2, ',', '.'); ?></td>
                                <td style="padding: 10px 12px; text-align: right;">R$ <?php echo number_format($g['vlr_ce'], 2, ',', '.'); ?></td>
                                <td style="padding: 10px 12px; text-align: center;">
                                    <span style="background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 10px; font-size: 11px;">
                                        <?php echo $g['qtd_quartos']; ?>
                                    </span>
                                </td>
                                <td style="padding: 10px 12px; text-align: center;">
                                    <a href="?editar=<?php echo $g['ID']; ?>" style="color: #3b82f6; text-decoration: none; margin-right: 10px;">✏️</a>
                                    <?php if ($g['qtd_quartos'] == 0): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Excluir este grupo?')">
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id" value="<?php echo $g['ID']; ?>">
                                        <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer;">🗑️</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Formulário -->
            <div class="card" style="background: white; border: 1px solid #ddd; border-radius: 8px; height: fit-content;">
                <div class="card-header" style="padding: 14px 18px; border-bottom: 1px solid #eee; font-weight: 600; font-size: 14px; background: #f9fafb;">
                    <?php echo $grupoEditar ? '✏️ Editar Grupo' : '➕ Novo Grupo'; ?>
                </div>
                <div class="card-body" style="padding: 18px;">
                    <form method="POST">
                        <input type="hidden" name="acao" value="salvar">
                        <input type="hidden" name="id" value="<?php echo $grupoEditar['ID'] ?? 0; ?>">
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px; font-weight: 500;">Nome do Grupo *</label>
                            <input type="text" name="nome" value="<?php echo htmlspecialchars($grupoEditar['nome'] ?? ''); ?>" required
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px;"
                                   placeholder="Ex: Suite, Quarto Standard...">
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px; font-weight: 500;">Valor Base (R$)</label>
                            <input type="number" name="valor" step="0.01" value="<?php echo $grupoEditar['valor'] ?? '0'; ?>"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px;"
                                   placeholder="150.00">
                            <small style="color: #888; font-size: 10px;">Valor padrão da diária para quartos deste grupo</small>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px; font-weight: 500;">Valor Cama Extra (R$)</label>
                            <input type="number" name="vlr_ce" step="0.01" value="<?php echo $grupoEditar['vlr_ce'] ?? '0'; ?>"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px;"
                                   placeholder="50.00">
                            <small style="color: #888; font-size: 10px;">Valor adicional por cama extra</small>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                            <div>
                                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px; font-weight: 500;">Hóspedes Base</label>
                                <input type="number" name="vlr_add_base" value="<?php echo $grupoEditar['vlr_add_base'] ?? '0'; ?>"
                                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px;">
                                <small style="color: #888; font-size: 10px;">Inclusos no valor base</small>
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px; font-weight: 500;">Valor Adicional (R$)</label>
                                <input type="number" name="vlr_add" step="0.01" value="<?php echo $grupoEditar['vlr_add'] ?? '0'; ?>"
                                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px;">
                                <small style="color: #888; font-size: 10px;">Por hóspede extra</small>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" style="flex: 1; padding: 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-size: 13px; cursor: pointer;">
                                💾 <?php echo $grupoEditar ? 'Salvar Alterações' : 'Criar Grupo'; ?>
                            </button>
                            <?php if ($grupoEditar): ?>
                            <a href="room-groups.php" style="padding: 12px 20px; background: #f3f4f6; color: #333; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; text-decoration: none;">
                                Cancelar
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php 
mysqli_close($conexao);
include 'includes/footer.php'; 
?>

