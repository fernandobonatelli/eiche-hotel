<?php
/**
 * Pousada Bona - Clientes
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Conexão
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
        $id = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : 0;
        // Aplicar trim para remover espaços em branco no início e fim
        $razao = mysqli_real_escape_string($conexao, trim($_POST['razao'] ?? ''));
        $fantasia = mysqli_real_escape_string($conexao, trim($_POST['fantasia'] ?? ''));
        $cpf = mysqli_real_escape_string($conexao, trim($_POST['cpf'] ?? ''));
        $cnpj = mysqli_real_escape_string($conexao, trim($_POST['cnpj'] ?? ''));
        $rg = mysqli_real_escape_string($conexao, trim($_POST['rg'] ?? ''));
        $fone1 = mysqli_real_escape_string($conexao, trim($_POST['fone1'] ?? ''));
        $fone2 = mysqli_real_escape_string($conexao, trim($_POST['fone2'] ?? ''));
        $email1 = mysqli_real_escape_string($conexao, trim($_POST['email1'] ?? ''));
        $e_rua = mysqli_real_escape_string($conexao, trim($_POST['e_rua'] ?? ''));
        $e_numero = mysqli_real_escape_string($conexao, trim($_POST['e_numero'] ?? ''));
        $e_complemento = mysqli_real_escape_string($conexao, trim($_POST['e_complemento'] ?? ''));
        $e_bairro = mysqli_real_escape_string($conexao, trim($_POST['e_bairro'] ?? ''));
        $e_cep = mysqli_real_escape_string($conexao, trim($_POST['e_cep'] ?? ''));
        $e_cidade = mysqli_real_escape_string($conexao, trim($_POST['e_cidade'] ?? ''));
        $e_estado = mysqli_real_escape_string($conexao, trim($_POST['e_estado'] ?? ''));
        $obs = mysqli_real_escape_string($conexao, trim($_POST['obs'] ?? ''));
        
        if (empty($razao)) {
            $erro = 'Nome do cliente é obrigatório';
        } else {
            if ($id > 0) {
                // Atualizar
                $sql = "UPDATE eiche_customers SET 
                            razao = '$razao', fantasia = '$fantasia', cpf = '$cpf', cnpj = '$cnpj',
                            rg = '$rg', fone1 = '$fone1', fone2 = '$fone2', email1 = '$email1',
                            e_rua = '$e_rua', e_numero = '$e_numero', e_complemento = '$e_complemento',
                            e_bairro = '$e_bairro', e_cep = '$e_cep', e_cidade = '$e_cidade', 
                            e_estado = '$e_estado', obs = '$obs'
                        WHERE ID = $id";
                if (mysqli_query($conexao, $sql)) {
                    $mensagem = 'Cliente atualizado com sucesso!';
                } else {
                    $erro = 'Erro ao atualizar: ' . mysqli_error($conexao);
                }
            } else {
                // Inserir
                $sql = "INSERT INTO eiche_customers (razao, fantasia, cpf, cnpj, rg, fone1, fone2, email1, 
                            e_rua, e_numero, e_complemento, e_bairro, e_cep, e_cidade, e_estado, obs) 
                        VALUES ('$razao', '$fantasia', '$cpf', '$cnpj', '$rg', '$fone1', '$fone2', '$email1',
                            '$e_rua', '$e_numero', '$e_complemento', '$e_bairro', '$e_cep', '$e_cidade', '$e_estado', '$obs')";
                if (mysqli_query($conexao, $sql)) {
                    $mensagem = 'Cliente cadastrado com sucesso!';
                } else {
                    $erro = 'Erro ao cadastrar: ' . mysqli_error($conexao);
                }
            }
        }
    }
    
    if ($acao === 'excluir') {
        $id = (int)$_POST['cliente_id'];
        
        // Verificar se tem hospedagens
        $check = mysqli_query($conexao, "SELECT ID FROM eiche_hospedagem WHERE ID_cliente = $id LIMIT 1");
        if ($check && mysqli_num_rows($check) > 0) {
            $erro = 'Não é possível excluir: cliente possui hospedagens vinculadas';
        } else {
            if (mysqli_query($conexao, "DELETE FROM eiche_customers WHERE ID = $id")) {
                $mensagem = 'Cliente excluído com sucesso!';
            } else {
                $erro = 'Erro ao excluir: ' . mysqli_error($conexao);
            }
        }
    }
}

// Busca
$busca = isset($_GET['busca']) ? mysqli_real_escape_string($conexao, $_GET['busca']) : '';

// Buscar clientes
$clientes = [];
$sqlWhere = "";
if (!empty($busca)) {
    $sqlWhere = "WHERE razao LIKE '%$busca%' OR fantasia LIKE '%$busca%' OR cpf LIKE '%$busca%' OR cnpj LIKE '%$busca%' OR fone1 LIKE '%$busca%'";
}
$sql = "SELECT * FROM eiche_customers $sqlWhere ORDER BY TRIM(razao) LIMIT 200";
$result = mysqli_query($conexao, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $clientes[] = $row;
    }
}

// Estatísticas
$totalClientes = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT COUNT(*) as total FROM eiche_customers"))['total'] ?? 0;

// Clientes novos este mês
$mesAtual = date('Y-m');
$novosEsteMes = 0;
$checkCol = mysqli_query($conexao, "SHOW COLUMNS FROM eiche_customers LIKE 'data_cadastro'");
if ($checkCol && mysqli_num_rows($checkCol) > 0) {
    $result = mysqli_query($conexao, "SELECT COUNT(*) as total FROM eiche_customers WHERE data_cadastro LIKE '$mesAtual%'");
    if ($result) {
        $novosEsteMes = mysqli_fetch_assoc($result)['total'] ?? 0;
    }
}

// Estados
$estados = [];
$resultEstados = mysqli_query($conexao, "SELECT ID, descricao FROM eiche_states ORDER BY descricao");
if ($resultEstados) {
    while ($row = mysqli_fetch_assoc($resultEstados)) {
        $estados[] = $row;
    }
}

$pageTitle = 'Clientes - Pousada Bona';

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>
    
    <main class="main-content">
        <div class="content-header">
            <div class="content-header-left">
                <h1>👥 Clientes</h1>
                <p>Gerencie os clientes cadastrados</p>
            </div>
            <div class="content-header-actions">
                <button class="btn btn-primary" onclick="abrirModalCliente(0)">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Novo Cliente
                </button>
            </div>
        </div>
        
        <?php if ($mensagem): ?>
        <div style="background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #bbf7d0;">
            ✅ <?= $mensagem ?>
        </div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #fecaca;">
            ❌ <?= $erro ?>
        </div>
        <?php endif; ?>
        
        <!-- Stats -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total de Clientes</div>
                    <div class="stat-value"><?= number_format($totalClientes) ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon green">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Novos este Mês</div>
                    <div class="stat-value"><?= $novosEsteMes ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon orange">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Exibindo</div>
                    <div class="stat-value"><?= count($clientes) ?></div>
                </div>
            </div>
        </div>
        
        <!-- Busca -->
        <div class="card" style="margin-bottom: 15px;">
            <div class="card-body" style="padding: 12px;">
                <form method="GET" style="display: flex; gap: 10px;">
                    <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" 
                           placeholder="🔍 Buscar por nome, CPF, CNPJ ou telefone..."
                           style="flex: 1; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                    <?php if ($busca): ?>
                    <a href="guests.php" class="btn btn-secondary">Limpar</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <!-- Lista de Clientes -->
        <div class="card">
            <div class="card-header">
                <h3>📋 Lista de Clientes</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-wrapper" style="border: none; border-radius: 0;">
                    <table class="table" id="table-clientes" style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>CPF/CNPJ</th>
                                <th>Telefone</th>
                                <th>Email</th>
                                <th>Cidade/UF</th>
                                <th style="width: 120px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($clientes)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                                    <?= $busca ? 'Nenhum cliente encontrado para: ' . htmlspecialchars($busca) : 'Nenhum cliente cadastrado' ?>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 36px; height: 36px; border-radius: 50%; background: #dbeafe; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #1e40af; font-size: 13px;">
                                            <?= strtoupper(substr($cliente['razao'] ?? 'C', 0, 1)) ?>
                                        </div>
                                        <div>
                                            <strong><?= htmlspecialchars(substr($cliente['razao'] ?? '', 0, 35)) ?></strong>
                                            <?php if (!empty($cliente['fantasia'])): ?>
                                            <div style="font-size: 11px; color: #666;"><?= htmlspecialchars($cliente['fantasia']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $doc = $cliente['cpf'] ?? $cliente['cnpj'] ?? '-';
                                    echo htmlspecialchars($doc);
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($cliente['fone1'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($cliente['email1'] ?? '-') ?></td>
                                <td>
                                    <?php 
                                    $cidade = $cliente['e_cidade'] ?? '';
                                    $estado = $cliente['e_estado'] ?? '';
                                    echo $cidade ? htmlspecialchars($cidade) . ($estado ? '/' . $estado : '') : '-';
                                    ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <button onclick="abrirModalCliente(<?= $cliente['ID'] ?>)" 
                                                style="background: #dbeafe; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; color: #1e40af; font-size: 12px;" 
                                                title="Editar">
                                            ✏️
                                        </button>
                                        <button onclick="verHistorico(<?= $cliente['ID'] ?>, '<?= htmlspecialchars(addslashes($cliente['razao'] ?? '')) ?>')" 
                                                style="background: #fef3c7; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; color: #92400e; font-size: 12px;" 
                                                title="Histórico">
                                            📋
                                        </button>
                                        <button onclick="confirmarExclusao(<?= $cliente['ID'] ?>, '<?= htmlspecialchars(addslashes($cliente['razao'] ?? '')) ?>')" 
                                                style="background: #fee2e2; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; color: #991b1b; font-size: 12px;" 
                                                title="Excluir">
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal Cliente -->
<div id="modal-cliente" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; overflow-y: auto;">
    <div style="background: white; max-width: 700px; margin: 30px auto; border-radius: 12px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #f9fafb; border-radius: 12px 12px 0 0;">
            <h3 id="modal-titulo" style="margin: 0; font-size: 16px;">Novo Cliente</h3>
            <button onclick="fecharModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #666;">×</button>
        </div>
        <form method="POST" id="form-cliente">
            <input type="hidden" name="acao" value="salvar">
            <input type="hidden" name="cliente_id" id="cliente_id" value="0">
            
            <div style="padding: 20px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div style="grid-column: span 2;">
                        <label style="display: block; font-size: 12px; color: #555; margin-bottom: 4px; font-weight: 500;">Nome Completo / Razão Social *</label>
                        <input type="text" name="razao" id="cliente_razao" required
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                    
                    <div style="grid-column: span 2;">
                        <label style="display: block; font-size: 12px; color: #555; margin-bottom: 4px; font-weight: 500;">Nome Fantasia / Apelido</label>
                        <input type="text" name="fantasia" id="cliente_fantasia"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                    
                    <div>
                        <label style="display: block; font-size: 12px; color: #555; margin-bottom: 4px; font-weight: 500;">CPF</label>
                        <input type="text" name="cpf" id="cliente_cpf" placeholder="000.000.000-00"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                    
                    <div>
                        <label style="display: block; font-size: 12px; color: #555; margin-bottom: 4px; font-weight: 500;">CNPJ</label>
                        <input type="text" name="cnpj" id="cliente_cnpj" placeholder="00.000.000/0000-00"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                    
                    <div>
                        <label style="display: block; font-size: 12px; color: #555; margin-bottom: 4px; font-weight: 500;">RG</label>
                        <input type="text" name="rg" id="cliente_rg"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                    
                    <div>
                        <label style="display: block; font-size: 12px; color: #555; margin-bottom: 4px; font-weight: 500;">Telefone Principal</label>
                        <input type="text" name="fone1" id="cliente_fone1"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                    
                    <div>
                        <label style="display: block; font-size: 12px; color: #555; margin-bottom: 4px; font-weight: 500;">Telefone Secundário</label>
                        <input type="text" name="fone2" id="cliente_fone2"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                    
                    <div>
                        <label style="display: block; font-size: 12px; color: #555; margin-bottom: 4px; font-weight: 500;">Email</label>
                        <input type="email" name="email1" id="cliente_email1"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                </div>
                
                <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">
                <h4 style="font-size: 13px; color: #666; margin-bottom: 15px;">📍 Endereço</h4>
                
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                    <div>
                        <label style="display: block; font-size: 12px; color: #555; margin-bottom: 4px; font-weight: 500;">Rua/Logradouro</label>
                        <input type="text" name="e_rua" id="cliente_e_rua"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                    
                    <div>
                        <label style="display: block; font-size: 12px; color: #555; margin-bottom: 4px; font-weight: 500;">Número</label>
                        <input type="text" name="e_numero" id="cliente_e_numero"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                    
                    <div>
                        <label style="display: block; font-size: 12px; color: #555; margin-bottom: 4px; font-weight: 500;">Complemento</label>
                        <input type="text" name="e_complemento" id="cliente_e_complemento"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                    
                    <div>
                        <label style="display: block; font-size: 12px; color: #555; margin-bottom: 4px; font-weight: 500;">Bairro</label>
                        <input type="text" name="e_bairro" id="cliente_e_bairro"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                    
                    <div>
                        <label style="display: block; font-size: 12px; color: #555; margin-bottom: 4px; font-weight: 500;">CEP</label>
                        <input type="text" name="e_cep" id="cliente_e_cep"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                    
                    <div>
                        <label style="display: block; font-size: 12px; color: #555; margin-bottom: 4px; font-weight: 500;">Cidade</label>
                        <input type="text" name="e_cidade" id="cliente_e_cidade"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                    
                    <div>
                        <label style="display: block; font-size: 12px; color: #555; margin-bottom: 4px; font-weight: 500;">Estado</label>
                        <select name="e_estado" id="cliente_e_estado"
                                style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                            <option value="">Selecione...</option>
                            <?php foreach ($estados as $estado): ?>
                            <option value="<?= $estado['ID'] ?>"><?= htmlspecialchars($estado['descricao']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div style="margin-top: 15px;">
                    <label style="display: block; font-size: 12px; color: #555; margin-bottom: 4px; font-weight: 500;">Observações</label>
                    <textarea name="obs" id="cliente_obs" rows="3"
                              style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; resize: vertical;"></textarea>
                </div>
            </div>
            
            <div style="padding: 15px 20px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px; background: #f9fafb; border-radius: 0 0 12px 12px;">
                <button type="button" onclick="fecharModal()" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">💾 Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Histórico -->
<div id="modal-historico" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; overflow-y: auto;">
    <div style="background: white; max-width: 600px; margin: 50px auto; border-radius: 12px;">
        <div style="padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
            <h3 id="historico-titulo" style="margin: 0; font-size: 16px;">Histórico</h3>
            <button onclick="fecharHistorico()" style="background: none; border: none; font-size: 20px; cursor: pointer;">×</button>
        </div>
        <div id="historico-conteudo" style="padding: 20px; max-height: 400px; overflow-y: auto;">
            Carregando...
        </div>
    </div>
</div>

<!-- Form oculto para excluir -->
<form id="form-excluir" method="POST" style="display: none;">
    <input type="hidden" name="acao" value="excluir">
    <input type="hidden" name="cliente_id" id="excluir_cliente_id">
</form>

<script>
// Dados dos clientes para edição
var clientesData = <?= json_encode($clientes) ?>;

function abrirModalCliente(id) {
    var modal = document.getElementById('modal-cliente');
    var titulo = document.getElementById('modal-titulo');
    
    // Limpar formulário
    document.getElementById('form-cliente').reset();
    document.getElementById('cliente_id').value = '0';
    
    if (id > 0) {
        titulo.textContent = '✏️ Editar Cliente';
        // Buscar dados do cliente
        var cliente = clientesData.find(c => c.ID == id);
        if (cliente) {
            document.getElementById('cliente_id').value = cliente.ID;
            document.getElementById('cliente_razao').value = cliente.razao || '';
            document.getElementById('cliente_fantasia').value = cliente.fantasia || '';
            document.getElementById('cliente_cpf').value = cliente.cpf || '';
            document.getElementById('cliente_cnpj').value = cliente.cnpj || '';
            document.getElementById('cliente_rg').value = cliente.rg || '';
            document.getElementById('cliente_fone1').value = cliente.fone1 || '';
            document.getElementById('cliente_fone2').value = cliente.fone2 || '';
            document.getElementById('cliente_email1').value = cliente.email1 || '';
            document.getElementById('cliente_e_rua').value = cliente.e_rua || '';
            document.getElementById('cliente_e_numero').value = cliente.e_numero || '';
            document.getElementById('cliente_e_complemento').value = cliente.e_complemento || '';
            document.getElementById('cliente_e_bairro').value = cliente.e_bairro || '';
            document.getElementById('cliente_e_cep').value = cliente.e_cep || '';
            document.getElementById('cliente_e_cidade').value = cliente.e_cidade || '';
            document.getElementById('cliente_e_estado').value = cliente.e_estado || '';
            document.getElementById('cliente_obs').value = cliente.obs || '';
        }
    } else {
        titulo.textContent = '➕ Novo Cliente';
    }
    
    modal.style.display = 'block';
}

function fecharModal() {
    document.getElementById('modal-cliente').style.display = 'none';
}

function verHistorico(id, nome) {
    var modal = document.getElementById('modal-historico');
    var titulo = document.getElementById('historico-titulo');
    var conteudo = document.getElementById('historico-conteudo');
    
    titulo.textContent = '📋 Histórico: ' + nome;
    conteudo.innerHTML = '<div style="text-align: center; padding: 30px; color: #666;">⏳ Carregando...</div>';
    modal.style.display = 'block';
    
    // Buscar histórico via AJAX
    fetch('buscar-historico-cliente.php?id=' + id)
        .then(response => response.text())
        .then(html => {
            conteudo.innerHTML = html;
        })
        .catch(err => {
            conteudo.innerHTML = '<div style="color: red; padding: 20px;">Erro ao carregar histórico</div>';
        });
}

function fecharHistorico() {
    document.getElementById('modal-historico').style.display = 'none';
}

function confirmarExclusao(id, nome) {
    if (confirm('Deseja excluir o cliente:\n' + nome + '?\n\nEsta ação não pode ser desfeita.')) {
        document.getElementById('excluir_cliente_id').value = id;
        document.getElementById('form-excluir').submit();
    }
}

// Fechar modais clicando fora
document.getElementById('modal-cliente').addEventListener('click', function(e) {
    if (e.target === this) fecharModal();
});
document.getElementById('modal-historico').addEventListener('click', function(e) {
    if (e.target === this) fecharHistorico();
});
</script>

<style>
.table tr:hover {
    background: #f9fafb;
}
</style>

<?php 
mysqli_close($conexao);
include 'includes/footer.php'; 
?>
