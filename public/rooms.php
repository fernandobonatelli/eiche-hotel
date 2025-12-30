<?php
/**
 * Pousada Bona - Listagem de Quartos
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
        $numero = mysqli_real_escape_string($conexao, $_POST['numero'] ?? '');
        $ocupantes = (int)($_POST['ocupantes'] ?? 1);
        $ramal = mysqli_real_escape_string($conexao, $_POST['ramal'] ?? '');
        $grupo = (int)($_POST['grupo'] ?? 0);
        $valor = (float)($_POST['valor'] ?? 0);
        $vlr_ce = (float)($_POST['vlr_ce'] ?? 0);
        
        if (empty($numero)) {
            $erro = 'Número/Nome do quarto é obrigatório';
        } else {
            // Verificar se já existe
            $checkSql = "SELECT ID FROM eiche_hosp_quartos WHERE numero = '$numero' AND ID != $id";
            $checkResult = mysqli_query($conexao, $checkSql);
            if ($checkResult && mysqli_num_rows($checkResult) > 0) {
                $erro = 'Já existe um quarto com este número/nome';
            } else {
                if ($id > 0) {
                    // Atualizar
                    $sql = "UPDATE eiche_hosp_quartos SET 
                            numero = '$numero', 
                            ocupantes = $ocupantes, 
                            ramal = '$ramal', 
                            grupo = $grupo, 
                            valor = $valor,
                            vlr_ce = $vlr_ce
                            WHERE ID = $id";
                    if (mysqli_query($conexao, $sql)) {
                        $mensagem = 'Quarto atualizado com sucesso!';
                    } else {
                        $erro = 'Erro ao atualizar: ' . mysqli_error($conexao);
                    }
                } else {
                    // Inserir
                    $sql = "INSERT INTO eiche_hosp_quartos (numero, ocupantes, ramal, grupo, valor, vlr_ce) 
                            VALUES ('$numero', $ocupantes, '$ramal', $grupo, $valor, $vlr_ce)";
                    if (mysqli_query($conexao, $sql)) {
                        $mensagem = 'Quarto criado com sucesso!';
                    } else {
                        $erro = 'Erro ao criar: ' . mysqli_error($conexao);
                    }
                }
            }
        }
    } elseif ($acao === 'excluir') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Verificar se tem hospedagens
            $check = mysqli_query($conexao, "SELECT COUNT(*) as total FROM eiche_hospedagem WHERE ID_quarto = $id");
            $row = mysqli_fetch_assoc($check);
            if ($row['total'] > 0) {
                $erro = 'Não é possível excluir: há hospedagens associadas a este quarto.';
            } else {
                if (mysqli_query($conexao, "DELETE FROM eiche_hosp_quartos WHERE ID = $id")) {
                    $mensagem = 'Quarto excluído com sucesso!';
                } else {
                    $erro = 'Erro ao excluir: ' . mysqli_error($conexao);
                }
            }
        }
    }
}

// Buscar grupos para o select
$grupos = [];
$resultGrupos = mysqli_query($conexao, "SELECT ID, nome FROM eiche_hosp_gruposq ORDER BY nome");
if ($resultGrupos) {
    while ($row = mysqli_fetch_assoc($resultGrupos)) {
        $grupos[] = $row;
    }
}

// Buscar quartos com dados do grupo
$quartos = [];
$result = mysqli_query($conexao, "SELECT q.*, g.nome as grupo_nome, COALESCE(g.valor, q.valor) as valor_final 
                                   FROM eiche_hosp_quartos q 
                                   LEFT JOIN eiche_hosp_gruposq g ON q.grupo = g.ID 
                                   ORDER BY q.numero");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $quartos[] = $row;
    }
}

// Estatísticas
$totalQuartos = count($quartos);
$quartosOcupados = 0;
$quartosDisponiveis = 0;
$quartosStatus = [];

$hoje = date('Y-m-d');
foreach ($quartos as $q) {
    // Verificar ocupação apenas para a data de hoje
    $check = mysqli_query($conexao, "SELECT ID FROM eiche_hospedagem WHERE ID_quarto = {$q['ID']} AND data = '$hoje' AND rstatus = 'A' LIMIT 1");
    $ocupado = $check && mysqli_num_rows($check) > 0;
    $quartosStatus[$q['ID']] = $ocupado;
    if ($ocupado) {
        $quartosOcupados++;
    } else {
        $quartosDisponiveis++;
    }
}

// JSON dos quartos para JavaScript
$quartosJson = json_encode($quartos);
$gruposJson = json_encode($grupos);

$pageTitle = 'Quartos - Pousada Bona';

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>
    
    <main class="main-content">
        <div class="content-header">
            <div class="content-header-left">
                <h1>Quartos</h1>
                <p>Gerencie os quartos e apartamentos da pousada</p>
            </div>
            <div class="content-header-actions" style="display: flex; gap: 10px;">
                <a href="room-groups.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 16px; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 6px; text-decoration: none; color: #374151; font-size: 14px;">
                    📋 Grupos
                </a>
                <button class="btn btn-primary" onclick="abrirModal()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Novo Quarto
                </button>
            </div>
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
        
        <!-- Stats -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px;">
                <div style="font-size: 24px; font-weight: 700; color: #3b82f6;"><?= $totalQuartos ?></div>
                <div style="font-size: 12px; color: #666;">Total de Quartos</div>
            </div>
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px;">
                <div style="font-size: 24px; font-weight: 700; color: #22c55e;"><?= $quartosDisponiveis ?></div>
                <div style="font-size: 12px; color: #666;">Disponíveis</div>
            </div>
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px;">
                <div style="font-size: 24px; font-weight: 700; color: #f59e0b;"><?= $quartosOcupados ?></div>
                <div style="font-size: 12px; color: #666;">Ocupados</div>
            </div>
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px;">
                <div style="font-size: 24px; font-weight: 700; color: #8b5cf6;"><?= $totalQuartos > 0 ? round(($quartosOcupados / $totalQuartos) * 100) : 0 ?>%</div>
                <div style="font-size: 12px; color: #666;">Taxa de Ocupação</div>
            </div>
        </div>
        
        <!-- Lista de Quartos -->
        <div class="card" style="background: white; border: 1px solid #e5e7eb; border-radius: 8px;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; border-bottom: 1px solid #eee;">
                <h3 style="margin: 0; font-size: 14px;">Lista de Quartos</h3>
                <input type="text" id="search-quartos" placeholder="🔍 Buscar quarto..." onkeyup="filterTable()" 
                       style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 12px; width: 200px;">
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;" id="table-quartos">
                    <thead>
                        <tr style="background: #f9fafb;">
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #eee;">Quarto</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #eee;">Grupo</th>
                            <th style="padding: 12px; text-align: center; border-bottom: 1px solid #eee;">Ocupantes</th>
                            <th style="padding: 12px; text-align: right; border-bottom: 1px solid #eee;">Valor Diária</th>
                            <th style="padding: 12px; text-align: center; border-bottom: 1px solid #eee;">Status</th>
                            <th style="padding: 12px; text-align: center; border-bottom: 1px solid #eee;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($quartos)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                                Nenhum quarto cadastrado
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($quartos as $quarto): 
                            $ocupado = $quartosStatus[$quarto['ID']] ?? false;
                        ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 12px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 36px; height: 36px; border-radius: 6px; background: <?= $ocupado ? '#fee2e2' : '#dcfce7' ?>; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 600; color: <?= $ocupado ? '#991b1b' : '#166534' ?>;">
                                        <?= strtoupper(substr($quarto['numero'], 0, 2)) ?>
                                    </div>
                                    <strong><?= htmlspecialchars($quarto['numero']) ?></strong>
                                </div>
                            </td>
                            <td style="padding: 12px;">
                                <span style="background: #e0e7ff; color: #3730a3; padding: 4px 10px; border-radius: 4px; font-size: 11px;">
                                    <?= htmlspecialchars($quarto['grupo_nome'] ?? 'Sem grupo') ?>
                                </span>
                            </td>
                            <td style="padding: 12px; text-align: center;"><?= $quarto['ocupantes'] ?? 1 ?> pessoas</td>
                            <td style="padding: 12px; text-align: right; font-weight: 600;">R$ <?= number_format($quarto['valor_final'] ?? 0, 2, ',', '.') ?></td>
                            <td style="padding: 12px; text-align: center;">
                                <?php if ($ocupado): ?>
                                    <span style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 4px; font-size: 11px;">Ocupado</span>
                                <?php else: ?>
                                    <span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 4px; font-size: 11px;">Disponível</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <button onclick="editarQuarto(<?= $quarto['ID'] ?>)" style="background: none; border: none; cursor: pointer; color: #3b82f6; padding: 5px;" title="Editar">✏️</button>
                                <?php if (!$ocupado): ?>
                                <button onclick="excluirQuarto(<?= $quarto['ID'] ?>, '<?= htmlspecialchars($quarto['numero']) ?>')" style="background: none; border: none; cursor: pointer; color: #ef4444; padding: 5px;" title="Excluir">🗑️</button>
                                <?php endif; ?>
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

<!-- Modal -->
<div id="modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;">
    <div style="background: white; border-radius: 10px; width: 100%; max-width: 450px; box-shadow: 0 20px 50px rgba(0,0,0,0.3);">
        <div style="padding: 16px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modal-titulo" style="margin: 0; font-size: 16px;">Novo Quarto</h3>
            <button onclick="fecharModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #999;">&times;</button>
        </div>
        <div style="padding: 20px;">
            <form method="POST" id="form-quarto">
                <input type="hidden" name="acao" value="salvar">
                <input type="hidden" name="id" id="quarto-id" value="0">
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px; font-weight: 500;">Número/Nome do Quarto *</label>
                    <input type="text" name="numero" id="quarto-numero" required
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px;"
                           placeholder="Ex: SUITE 01, QUARTO 05...">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px; font-weight: 500;">Ocupantes</label>
                        <input type="number" name="ocupantes" id="quarto-ocupantes" min="1" value="2"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px; font-weight: 500;">Ramal</label>
                        <input type="text" name="ramal" id="quarto-ramal"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px;"
                               placeholder="Ex: 101">
                    </div>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px; font-weight: 500;">Grupo</label>
                    <select name="grupo" id="quarto-grupo" onchange="atualizarValorGrupo()"
                            style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px;">
                        <option value="0">-- Sem grupo --</option>
                        <?php foreach ($grupos as $g): ?>
                        <option value="<?= $g['ID'] ?>"><?= htmlspecialchars($g['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #888; font-size: 10px;">O valor da diária será herdado do grupo</small>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px; font-weight: 500;">Valor Próprio (R$)</label>
                        <input type="number" name="valor" id="quarto-valor" step="0.01" value="0"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px;">
                        <small style="color: #888; font-size: 10px;">Se 0, usa valor do grupo</small>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; color: #555; margin-bottom: 5px; font-weight: 500;">Cama Extra (R$)</label>
                        <input type="number" name="vlr_ce" id="quarto-vlr-ce" step="0.01" value="0"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px;">
                        <small style="color: #888; font-size: 10px;">Se 0, usa valor do grupo</small>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" style="flex: 1; padding: 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-size: 13px; cursor: pointer;">
                        💾 Salvar
                    </button>
                    <button type="button" onclick="fecharModal()" style="padding: 12px 20px; background: #f3f4f6; color: #333; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; cursor: pointer;">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Form para exclusão -->
<form id="form-excluir" method="POST" style="display: none;">
    <input type="hidden" name="acao" value="excluir">
    <input type="hidden" name="id" id="excluir-id" value="0">
</form>

<script>
var quartosData = <?= $quartosJson ?>;
var gruposData = <?= $gruposJson ?>;

function filterTable() {
    var input = document.getElementById('search-quartos');
    var filter = input.value.toLowerCase();
    var table = document.getElementById('table-quartos');
    var rows = table.getElementsByTagName('tr');
    
    for (var i = 1; i < rows.length; i++) {
        var cells = rows[i].getElementsByTagName('td');
        var found = false;
        for (var j = 0; j < cells.length; j++) {
            if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }
        rows[i].style.display = found ? '' : 'none';
    }
}

function abrirModal() {
    document.getElementById('modal-titulo').textContent = 'Novo Quarto';
    document.getElementById('quarto-id').value = '0';
    document.getElementById('quarto-numero').value = '';
    document.getElementById('quarto-ocupantes').value = '2';
    document.getElementById('quarto-ramal').value = '';
    document.getElementById('quarto-grupo').value = '0';
    document.getElementById('quarto-valor').value = '0';
    document.getElementById('quarto-vlr-ce').value = '0';
    document.getElementById('modal').style.display = 'flex';
}

function editarQuarto(id) {
    var quarto = null;
    for (var i = 0; i < quartosData.length; i++) {
        if (quartosData[i].ID == id) {
            quarto = quartosData[i];
            break;
        }
    }
    
    if (quarto) {
        document.getElementById('modal-titulo').textContent = 'Editar Quarto';
        document.getElementById('quarto-id').value = quarto.ID;
        document.getElementById('quarto-numero').value = quarto.numero || '';
        document.getElementById('quarto-ocupantes').value = quarto.ocupantes || 1;
        document.getElementById('quarto-ramal').value = quarto.ramal || '';
        document.getElementById('quarto-grupo').value = quarto.grupo || 0;
        document.getElementById('quarto-valor').value = quarto.valor || 0;
        document.getElementById('quarto-vlr-ce').value = quarto.vlr_ce || 0;
        document.getElementById('modal').style.display = 'flex';
    }
}

function fecharModal() {
    document.getElementById('modal').style.display = 'none';
}

function excluirQuarto(id, nome) {
    if (confirm('Tem certeza que deseja excluir o quarto "' + nome + '"?')) {
        document.getElementById('excluir-id').value = id;
        document.getElementById('form-excluir').submit();
    }
}

function atualizarValorGrupo() {
    var grupoId = document.getElementById('quarto-grupo').value;
    var grupo = null;
    for (var i = 0; i < gruposData.length; i++) {
        if (gruposData[i].ID == grupoId) {
            grupo = gruposData[i];
            break;
        }
    }
    // Opcional: preencher valor do grupo
}

// Fechar modal ao clicar fora
document.getElementById('modal').addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModal();
    }
});
</script>

<?php 
mysqli_close($conexao);
include 'includes/footer.php'; 
?>
