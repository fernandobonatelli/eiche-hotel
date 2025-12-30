<?php
/**
 * Pousada Bona - Configurações
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

// Verificar se é administrador
if (!isAdmin($conexao)) {
    $tituloPermissao = 'Acesso Restrito';
    $mensagemPermissao = 'Apenas administradores podem acessar as configurações do sistema.';
    include 'includes/sem-permissao.php';
}

$userName = $_SESSION['user_name'] ?? 'Usuário';
$userId = $_SESSION['user_id'];
$mensagem = '';
$erro = '';

// Processar ações de usuários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    // Salvar usuário (novo ou editar)
    if ($acao === 'salvar_usuario') {
        $id = (int)($_POST['usuario_id'] ?? 0);
        $name = mysqli_real_escape_string($conexao, $_POST['name'] ?? '');
        $login = mysqli_real_escape_string($conexao, $_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        $nivel = mysqli_real_escape_string($conexao, $_POST['nivel'] ?? 'user');
        $ver_valores = isset($_POST['ver_valores']) ? 'S' : 'N';
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        
        // Garantir que colunas existem
        $colCheck = mysqli_query($conexao, "SHOW COLUMNS FROM eiche_users LIKE 'ver_valores'");
        if (mysqli_num_rows($colCheck) == 0) {
            mysqli_query($conexao, "ALTER TABLE eiche_users ADD COLUMN ver_valores CHAR(1) DEFAULT 'S'");
        }
        $colCheck = mysqli_query($conexao, "SHOW COLUMNS FROM eiche_users LIKE 'nivel'");
        if (mysqli_num_rows($colCheck) == 0) {
            mysqli_query($conexao, "ALTER TABLE eiche_users ADD COLUMN nivel VARCHAR(20) DEFAULT 'user'");
        }
        
        if (empty($name) || empty($login)) {
            $erro = 'Nome e login são obrigatórios';
        } else {
            // Verificar se login já existe (exceto o próprio)
            $checkLogin = mysqli_query($conexao, "SELECT ID FROM eiche_users WHERE login = '$login' AND ID != $id");
            if ($checkLogin && mysqli_num_rows($checkLogin) > 0) {
                $erro = 'Este login já está em uso por outro usuário';
            } else {
                if ($id > 0) {
                    // Atualizar
                    if (!empty($password)) {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $sql = "UPDATE eiche_users SET name = '$name', login = '$login', password = '$hash', nivel = '$nivel', ver_valores = '$ver_valores' WHERE ID = $id";
                    } else {
                        $sql = "UPDATE eiche_users SET name = '$name', login = '$login', nivel = '$nivel', ver_valores = '$ver_valores' WHERE ID = $id";
                    }
                    if (mysqli_query($conexao, $sql)) {
                        $mensagem = 'Usuário atualizado com sucesso!';
                    } else {
                        $erro = 'Erro ao atualizar: ' . mysqli_error($conexao);
                    }
                } else {
                    // Novo usuário
                    if (empty($password)) {
                        $erro = 'Senha é obrigatória para novos usuários';
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $sql = "INSERT INTO eiche_users (name, login, password, nivel, ver_valores) VALUES ('$name', '$login', '$hash', '$nivel', '$ver_valores')";
                        if (mysqli_query($conexao, $sql)) {
                            $mensagem = 'Usuário criado com sucesso!';
                        } else {
                            $erro = 'Erro ao criar: ' . mysqli_error($conexao);
                        }
                    }
                }
            }
        }
    }
    
    // Excluir usuário
    if ($acao === 'excluir_usuario') {
        $id = (int)$_POST['usuario_id'];
        
        if ($id == $userId) {
            $erro = 'Você não pode excluir seu próprio usuário';
        } else {
            if (mysqli_query($conexao, "DELETE FROM eiche_users WHERE ID = $id")) {
                $mensagem = 'Usuário excluído com sucesso!';
            } else {
                $erro = 'Erro ao excluir: ' . mysqli_error($conexao);
            }
        }
    }
    
    // Alterar minha senha
    if ($acao === 'alterar_minha_senha') {
        $senhaAtual = $_POST['senha_atual'] ?? '';
        $novaSenha = $_POST['nova_senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';
        
        if (empty($senhaAtual) || empty($novaSenha)) {
            $erro = 'Preencha todos os campos de senha';
        } elseif ($novaSenha !== $confirmarSenha) {
            $erro = 'A nova senha e confirmação não conferem';
        } elseif (strlen($novaSenha) < 6) {
            $erro = 'A nova senha deve ter pelo menos 6 caracteres';
        } else {
            // Verificar senha atual
            $result = mysqli_query($conexao, "SELECT password FROM eiche_users WHERE ID = $userId");
            $user = mysqli_fetch_assoc($result);
            
            if (password_verify($senhaAtual, $user['password'])) {
                $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
                if (mysqli_query($conexao, "UPDATE eiche_users SET password = '$hash' WHERE ID = $userId")) {
                    $mensagem = 'Sua senha foi alterada com sucesso!';
                } else {
                    $erro = 'Erro ao alterar senha';
                }
            } else {
                $erro = 'Senha atual incorreta';
            }
        }
    }
}

// Buscar usuários (garantindo que colunas existem)
$colCheck = mysqli_query($conexao, "SHOW COLUMNS FROM eiche_users LIKE 'ver_valores'");
if (mysqli_num_rows($colCheck) == 0) {
    mysqli_query($conexao, "ALTER TABLE eiche_users ADD COLUMN ver_valores CHAR(1) DEFAULT 'S'");
}
$colCheck = mysqli_query($conexao, "SHOW COLUMNS FROM eiche_users LIKE 'nivel'");
if (mysqli_num_rows($colCheck) == 0) {
    mysqli_query($conexao, "ALTER TABLE eiche_users ADD COLUMN nivel VARCHAR(20) DEFAULT 'user'");
}

$usuarios = [];
$result = mysqli_query($conexao, "SELECT ID, name, login, password, COALESCE(nivel, 'user') as nivel, COALESCE(ver_valores, 'S') as ver_valores FROM eiche_users ORDER BY name");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $row['tipo_senha'] = (strpos($row['password'], '$2y$') === 0) ? 'BCrypt' : 'MD5';
        $usuarios[] = $row;
    }
}

// Buscar configurações
$config = [];
$result = mysqli_query($conexao, "SELECT * FROM eiche_hosp_config WHERE ID = 'A' LIMIT 1");
if ($result && mysqli_num_rows($result) > 0) {
    $config = mysqli_fetch_assoc($result);
}

$secao = $_GET['secao'] ?? 'geral';

$pageTitle = 'Configurações - Pousada Bona';

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>
    
    <main class="main-content">
        <div class="content-header">
            <div class="content-header-left">
                <h1>⚙️ Configurações</h1>
                <p>Configure o sistema e gerencie usuários</p>
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
        
        <div style="display: grid; grid-template-columns: 220px 1fr; gap: 20px;">
            
            <!-- Menu lateral -->
            <div class="card" style="height: fit-content; position: sticky; top: 20px;">
                <div class="card-body" style="padding: 8px;">
                    <nav style="display: flex; flex-direction: column; gap: 4px;">
                        <a href="?secao=geral" class="nav-config-item <?= $secao === 'geral' ? 'active' : '' ?>">
                            ⚙️ Geral
                        </a>
                        <a href="?secao=usuarios" class="nav-config-item <?= $secao === 'usuarios' ? 'active' : '' ?>">
                            👥 Usuários
                        </a>
                        <a href="?secao=minha_senha" class="nav-config-item <?= $secao === 'minha_senha' ? 'active' : '' ?>">
                            🔐 Minha Senha
                        </a>
                        <a href="?secao=empresa" class="nav-config-item <?= $secao === 'empresa' ? 'active' : '' ?>">
                            🏢 Dados da Empresa
                        </a>
                        <a href="?secao=backup" class="nav-config-item <?= $secao === 'backup' ? 'active' : '' ?>">
                            💾 Backup
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Conteúdo -->
            <div>
                
                <?php if ($secao === 'geral'): ?>
                <!-- Configurações Gerais -->
                <div class="card">
                    <div class="card-header">
                        <h3>⚙️ Configurações Gerais</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="acao" value="salvar_config">
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label>Dias exibidos no painel</label>
                                    <input type="number" name="ndias" value="<?= $config['ndias'] ?? 25 ?>" min="7" max="60" class="form-input">
                                    <small>Quantidade de dias na linha do tempo</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Quartos por página</label>
                                    <input type="number" name="nquartos" value="<?= $config['nquartos'] ?? 10 ?>" min="5" max="50" class="form-input">
                                </div>
                            </div>
                            
                            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;">
                                <button type="submit" class="btn btn-primary">💾 Salvar Configurações</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($secao === 'usuarios'): ?>
                <!-- Gerenciamento de Usuários -->
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3>👥 Usuários do Sistema</h3>
                        <button class="btn btn-primary" onclick="abrirModalUsuario(0)">
                            ➕ Novo Usuário
                        </button>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <table class="table" style="font-size: 13px;">
                            <thead>
                                <tr>
                                    <th>Usuário</th>
                                    <th>Login</th>
                                    <th style="text-align: center;">Nível</th>
                                    <th style="text-align: center;">Ver Valores</th>
                                    <th style="width: 120px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $u): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 36px; height: 36px; border-radius: 50%; background: <?= $u['nivel'] === 'admin' ? '#fef3c7' : '#dbeafe' ?>; display: flex; align-items: center; justify-content: center; font-weight: 600; color: <?= $u['nivel'] === 'admin' ? '#92400e' : '#1e40af' ?>;">
                                                <?= $u['nivel'] === 'admin' ? '👑' : strtoupper(substr($u['name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($u['name']) ?></strong>
                                                <?php if ($u['ID'] == $userId): ?>
                                                <span style="background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 4px; font-size: 10px; margin-left: 5px;">Você</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><code><?= htmlspecialchars($u['login']) ?></code></td>
                                    <td style="text-align: center;">
                                        <?php if ($u['nivel'] === 'admin'): ?>
                                        <span style="background: #fef3c7; color: #92400e; padding: 3px 8px; border-radius: 4px; font-size: 11px;">👑 Admin</span>
                                        <?php else: ?>
                                        <span style="background: #e0e7ff; color: #4338ca; padding: 3px 8px; border-radius: 4px; font-size: 11px;">👤 Usuário</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if ($u['ver_valores'] === 'S' || $u['nivel'] === 'admin'): ?>
                                        <span style="color: #22c55e; font-size: 18px;" title="Pode ver valores">✅</span>
                                        <?php else: ?>
                                        <span style="color: #dc2626; font-size: 18px;" title="Não pode ver valores">🚫</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <button onclick="abrirModalUsuario(<?= $u['ID'] ?>)" 
                                                    style="background: #dbeafe; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; color: #1e40af;">
                                                ✏️
                                            </button>
                                            <?php if ($u['ID'] != $userId): ?>
                                            <button onclick="confirmarExclusao(<?= $u['ID'] ?>, '<?= htmlspecialchars(addslashes($u['name'])) ?>')" 
                                                    style="background: #fee2e2; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; color: #991b1b;">
                                                🗑️
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($secao === 'minha_senha'): ?>
                <!-- Alterar Minha Senha -->
                <div class="card">
                    <div class="card-header">
                        <h3>🔐 Alterar Minha Senha</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="acao" value="alterar_minha_senha">
                            
                            <div class="form-group" style="max-width: 400px;">
                                <label>Senha Atual</label>
                                <input type="password" name="senha_atual" required class="form-input" placeholder="Digite sua senha atual">
                            </div>
                            
                            <div class="form-group" style="max-width: 400px;">
                                <label>Nova Senha</label>
                                <input type="password" name="nova_senha" required class="form-input" placeholder="Mínimo 6 caracteres" minlength="6">
                            </div>
                            
                            <div class="form-group" style="max-width: 400px;">
                                <label>Confirmar Nova Senha</label>
                                <input type="password" name="confirmar_senha" required class="form-input" placeholder="Repita a nova senha">
                            </div>
                            
                            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;">
                                <button type="submit" class="btn btn-primary">🔐 Alterar Senha</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($secao === 'empresa'): ?>
                <!-- Dados da Empresa -->
                <div class="card">
                    <div class="card-header">
                        <h3>🏢 Dados da Empresa</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="acao" value="salvar_empresa">
                            
                            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label>Nome da Empresa</label>
                                    <input type="text" name="nome_empresa" value="Pousada Bona" class="form-input">
                                </div>
                                <div class="form-group">
                                    <label>CNPJ</label>
                                    <input type="text" name="cnpj" class="form-input" placeholder="00.000.000/0001-00">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Endereço</label>
                                <input type="text" name="endereco" class="form-input" placeholder="Rua, número, bairro">
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label>Cidade</label>
                                    <input type="text" name="cidade" class="form-input">
                                </div>
                                <div class="form-group">
                                    <label>Estado</label>
                                    <input type="text" name="estado" class="form-input" maxlength="2">
                                </div>
                                <div class="form-group">
                                    <label>CEP</label>
                                    <input type="text" name="cep" class="form-input">
                                </div>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label>Telefone</label>
                                    <input type="text" name="telefone" class="form-input">
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-input">
                                </div>
                            </div>
                            
                            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;">
                                <button type="submit" class="btn btn-primary">💾 Salvar</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($secao === 'backup'): ?>
                <!-- Backup -->
                <div class="card">
                    <div class="card-header">
                        <h3>💾 Backup do Sistema</h3>
                    </div>
                    <div class="card-body" style="text-align: center; padding: 40px;">
                        <div style="width: 80px; height: 80px; border-radius: 50%; background: #dbeafe; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 32px;">
                            💾
                        </div>
                        <h4 style="margin-bottom: 10px;">Gerar Backup do Banco de Dados</h4>
                        <p style="color: #666; margin-bottom: 20px;">Faça backup regularmente para manter seus dados seguros.</p>
                        <button class="btn btn-primary" onclick="gerarBackup()">
                            📥 Gerar Backup Agora
                        </button>
                        <p style="margin-top: 20px; font-size: 12px; color: #999;">
                            O backup será baixado como arquivo SQL
                        </p>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </main>
</div>

<!-- Modal Usuário -->
<div id="modal-usuario" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; overflow-y: auto;">
    <div style="background: white; max-width: 500px; margin: 50px auto; border-radius: 12px;">
        <div style="padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modal-usuario-titulo" style="margin: 0;">Novo Usuário</h3>
            <button onclick="fecharModal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">×</button>
        </div>
        <form method="POST" id="form-usuario">
            <input type="hidden" name="acao" value="salvar_usuario">
            <input type="hidden" name="usuario_id" id="usuario_id" value="0">
            
            <div style="padding: 20px;">
                <div class="form-group">
                    <label>Nome Completo *</label>
                    <input type="text" name="name" id="usuario_name" required class="form-input">
                </div>
                
                <div class="form-group">
                    <label>Login *</label>
                    <input type="text" name="login" id="usuario_login" required class="form-input" pattern="[a-zA-Z0-9_]+" title="Apenas letras, números e underscore">
                    <small>Usado para acessar o sistema (apenas letras, números e _)</small>
                </div>
                
                <div class="form-group">
                    <label>Senha <span id="senha-obrig">*</span></label>
                    <input type="password" name="password" id="usuario_password" class="form-input">
                    <small id="senha-help">Mínimo 6 caracteres</small>
                </div>
                
                <div class="form-group">
                    <label>Nível de Acesso</label>
                    <select name="nivel" id="usuario_nivel" class="form-input">
                        <option value="user">👤 Usuário</option>
                        <option value="admin">👑 Administrador</option>
                    </select>
                    <small>Administradores têm acesso total ao sistema</small>
                </div>
                
                <div class="form-group" style="background: #fef3c7; padding: 12px; border-radius: 8px; margin-top: 15px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="ver_valores" id="usuario_ver_valores" value="1" checked style="width: 18px; height: 18px;">
                        <span>
                            <strong>💰 Pode visualizar valores</strong><br>
                            <small style="color: #92400e;">Se desmarcado, receitas, despesas e lucros ficarão ocultos</small>
                        </span>
                    </label>
                </div>
            </div>
            
            <div style="padding: 15px 20px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px; background: #f9fafb; border-radius: 0 0 12px 12px;">
                <button type="button" onclick="fecharModal()" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">💾 Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- Form excluir -->
<form id="form-excluir" method="POST" style="display: none;">
    <input type="hidden" name="acao" value="excluir_usuario">
    <input type="hidden" name="usuario_id" id="excluir_usuario_id">
</form>

<style>
.nav-config-item {
    display: block;
    padding: 10px 15px;
    color: #374151;
    text-decoration: none;
    border-radius: 6px;
    font-size: 13px;
}
.nav-config-item:hover {
    background: #f3f4f6;
}
.nav-config-item.active {
    background: #3b82f6;
    color: white;
}
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    font-size: 12px;
    color: #555;
    margin-bottom: 5px;
    font-weight: 500;
}
.form-group small {
    display: block;
    font-size: 11px;
    color: #888;
    margin-top: 4px;
}
.form-input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 13px;
}
.form-input:focus {
    border-color: #3b82f6;
    outline: none;
}
.table tr:hover {
    background: #f9fafb;
}
</style>

<script>
var usuariosData = <?= json_encode($usuarios) ?>;

function abrirModalUsuario(id) {
    var modal = document.getElementById('modal-usuario');
    var titulo = document.getElementById('modal-usuario-titulo');
    
    document.getElementById('form-usuario').reset();
    document.getElementById('usuario_id').value = '0';
    
    if (id > 0) {
        titulo.textContent = '✏️ Editar Usuário';
        document.getElementById('senha-obrig').textContent = '';
        document.getElementById('senha-help').textContent = 'Deixe em branco para manter a senha atual';
        document.getElementById('usuario_password').required = false;
        
        var usuario = usuariosData.find(u => u.ID == id);
        if (usuario) {
            document.getElementById('usuario_id').value = usuario.ID;
            document.getElementById('usuario_name').value = usuario.name;
            document.getElementById('usuario_login').value = usuario.login;
            document.getElementById('usuario_nivel').value = usuario.nivel || 'user';
            document.getElementById('usuario_ver_valores').checked = (usuario.ver_valores !== 'N');
        }
    } else {
        titulo.textContent = '➕ Novo Usuário';
        document.getElementById('senha-obrig').textContent = '*';
        document.getElementById('senha-help').textContent = 'Mínimo 6 caracteres';
        document.getElementById('usuario_password').required = true;
        document.getElementById('usuario_nivel').value = 'user';
        document.getElementById('usuario_ver_valores').checked = true;
    }
    
    modal.style.display = 'block';
}

function fecharModal() {
    document.getElementById('modal-usuario').style.display = 'none';
}

function confirmarExclusao(id, nome) {
    if (confirm('Deseja excluir o usuário:\n' + nome + '?\n\nEsta ação não pode ser desfeita.')) {
        document.getElementById('excluir_usuario_id').value = id;
        document.getElementById('form-excluir').submit();
    }
}

function gerarBackup() {
    alert('Funcionalidade de backup será implementada em breve.\n\nPor enquanto, use o phpMyAdmin do HostGator para exportar o banco.');
}

// Fechar modal clicando fora
document.getElementById('modal-usuario').addEventListener('click', function(e) {
    if (e.target === this) fecharModal();
});
</script>

<?php 
mysqli_close($conexao);
include 'includes/footer.php'; 
?>

