<?php
/**
 * Pousada Bona - Cadastrar Novo Cliente
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

$mensagem = '';
$erro = '';
$retorno = isset($_GET['retorno']) ? $_GET['retorno'] : 'reservations';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $razao = isset($_POST['razao']) ? trim($_POST['razao']) : '';
    $fantasia = isset($_POST['fantasia']) ? trim($_POST['fantasia']) : '';
    $cpf = isset($_POST['cpf']) ? trim($_POST['cpf']) : '000.000.000-00';
    $cnpj = isset($_POST['cnpj']) ? trim($_POST['cnpj']) : '000.000.000/0000-00';
    $rg = isset($_POST['rg']) ? trim($_POST['rg']) : '';
    $fone1 = isset($_POST['fone1']) ? trim($_POST['fone1']) : '';
    $fone2 = isset($_POST['fone2']) ? trim($_POST['fone2']) : '';
    $email1 = isset($_POST['email1']) ? trim($_POST['email1']) : '';
    $e_rua = isset($_POST['e_rua']) ? trim($_POST['e_rua']) : '';
    $e_numero = isset($_POST['e_numero']) ? trim($_POST['e_numero']) : '';
    $e_bairro = isset($_POST['e_bairro']) ? trim($_POST['e_bairro']) : '';
    $e_cidade = isset($_POST['e_cidade']) ? (int)$_POST['e_cidade'] : 1;
    $e_estado = isset($_POST['e_estado']) ? (int)$_POST['e_estado'] : 1;
    $e_cep = isset($_POST['e_cep']) ? trim($_POST['e_cep']) : '00000-000';
    
    // Se CPF estiver vazio, usar o padrão
    if (empty($cpf)) $cpf = '000.000.000-00';
    if (empty($cnpj)) $cnpj = '000.000.000/0000-00';
    if (empty($fantasia)) $fantasia = $razao;
    
    if (empty($razao)) {
        $erro = 'Nome é obrigatório';
    } else {
        // Verificar se já existe pelo CPF (se não for o padrão)
        $jaExiste = false;
        if ($cpf != '000.000.000-00') {
            $cpfEsc = mysqli_real_escape_string($conexao, $cpf);
            $verificar = mysqli_query($conexao, "SELECT ID FROM eiche_customers WHERE cpf = '$cpfEsc' LIMIT 1");
            if ($verificar && mysqli_num_rows($verificar) > 0) {
                $jaExiste = true;
                $erro = 'Já existe um cliente cadastrado com este CPF';
            }
        }
        
        if (!$jaExiste) {
            // Inserir - usando os campos corretos da tabela eiche_customers
            $sql = "INSERT INTO eiche_customers (
                        razao, fantasia, cpf, cnpj, rg, 
                        fone1, fone2, email1, 
                        e_rua, e_numero, e_bairro, e_cidade, e_estado, e_cep,
                        c_rua, c_numero, c_bairro, c_cidade, c_estado, c_cep,
                        rstatus, reg_date, ID_user
                    ) VALUES (
                        '" . mysqli_real_escape_string($conexao, $razao) . "',
                        '" . mysqli_real_escape_string($conexao, $fantasia) . "',
                        '" . mysqli_real_escape_string($conexao, $cpf) . "',
                        '" . mysqli_real_escape_string($conexao, $cnpj) . "',
                        '" . mysqli_real_escape_string($conexao, $rg) . "',
                        '" . mysqli_real_escape_string($conexao, $fone1) . "',
                        '" . mysqli_real_escape_string($conexao, $fone2) . "',
                        '" . mysqli_real_escape_string($conexao, $email1) . "',
                        '" . mysqli_real_escape_string($conexao, $e_rua) . "',
                        '" . mysqli_real_escape_string($conexao, $e_numero) . "',
                        '" . mysqli_real_escape_string($conexao, $e_bairro) . "',
                        $e_cidade,
                        $e_estado,
                        '" . mysqli_real_escape_string($conexao, $e_cep) . "',
                        '" . mysqli_real_escape_string($conexao, $e_rua) . "',
                        '" . mysqli_real_escape_string($conexao, $e_numero) . "',
                        '" . mysqli_real_escape_string($conexao, $e_bairro) . "',
                        $e_cidade,
                        $e_estado,
                        '" . mysqli_real_escape_string($conexao, $e_cep) . "',
                        'A',
                        '" . date('Y-m-d') . "',
                        " . ($_SESSION['user_id'] ?? 0) . "
                    )";
            
            if (mysqli_query($conexao, $sql)) {
                $novoId = mysqli_insert_id($conexao);
                $mensagem = 'Cliente cadastrado com sucesso! ID: ' . $novoId;
                
                // Redirecionar após 1.5 segundos
                header("Refresh: 1; URL=" . $retorno . ".php");
            } else {
                $erro = 'Erro ao salvar: ' . mysqli_error($conexao);
            }
        }
    }
}

// Buscar estados
$estados = [];
$resultEstados = mysqli_query($conexao, "SELECT ID, name, prefix FROM eiche_states ORDER BY name");
if ($resultEstados) {
    while ($row = mysqli_fetch_assoc($resultEstados)) {
        $estados[] = $row;
    }
}

$pageTitle = 'Novo Cliente - Pousada Bona';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
    <style>
        .page-container { max-width: 650px; margin: 0 auto; }
        .back-link { display: inline-flex; align-items: center; gap: 5px; color: #666; text-decoration: none; font-size: 13px; margin-bottom: 15px; }
        .back-link:hover { color: #333; }
        
        .card { background: white; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 15px; }
        .card-header { padding: 14px 18px; border-bottom: 1px solid #eee; font-weight: 600; font-size: 14px; background: #f9fafb; }
        .card-body { padding: 18px; }
        
        .section-title { font-size: 12px; color: #3b82f6; font-weight: 600; margin: 18px 0 10px 0; padding-bottom: 5px; border-bottom: 1px solid #e5e7eb; }
        .section-title:first-child { margin-top: 0; }
        
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; font-size: 11px; color: #555; margin-bottom: 3px; font-weight: 500; }
        .form-group label .required { color: #ef4444; }
        .form-group input, .form-group select { width: 100%; padding: 9px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px; }
        .form-group input:focus, .form-group select:focus { border-color: #3b82f6; outline: none; }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .form-row-3 { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 10px; }
        .form-row-4 { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 10px; }
        
        .btn { padding: 12px 18px; border-radius: 6px; font-size: 13px; cursor: pointer; border: none; }
        .btn-primary { background: #3b82f6; color: white; width: 100%; }
        .btn-primary:hover { background: #2563eb; }
        
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-wrapper">
        <?php include 'includes/topbar.php'; ?>
        
        <main class="main-content">
            <div class="page-container">
                <a href="<?php echo $retorno; ?>.php" class="back-link">← Voltar</a>
                
                <?php if ($mensagem): ?>
                    <div class="alert alert-success"><?php echo $mensagem; ?></div>
                <?php endif; ?>
                
                <?php if ($erro): ?>
                    <div class="alert alert-error"><?php echo $erro; ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">👤 Cadastrar Novo Cliente</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="section-title">📋 Dados Pessoais</div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Nome / Razão Social <span class="required">*</span></label>
                                    <input type="text" name="razao" required placeholder="Nome completo" value="<?php echo htmlspecialchars($_POST['razao'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Apelido / Nome Fantasia</label>
                                    <input type="text" name="fantasia" placeholder="Apelido" value="<?php echo htmlspecialchars($_POST['fantasia'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-row-3">
                                <div class="form-group">
                                    <label>CPF</label>
                                    <input type="text" name="cpf" placeholder="000.000.000-00" value="<?php echo htmlspecialchars($_POST['cpf'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>RG</label>
                                    <input type="text" name="rg" placeholder="RG" value="<?php echo htmlspecialchars($_POST['rg'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>CNPJ (empresas)</label>
                                    <input type="text" name="cnpj" placeholder="00.000.000/0000-00" value="<?php echo htmlspecialchars($_POST['cnpj'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="section-title">📞 Contato</div>
                            
                            <div class="form-row-3">
                                <div class="form-group">
                                    <label>Telefone Principal</label>
                                    <input type="text" name="fone1" placeholder="(00) 00000-0000" value="<?php echo htmlspecialchars($_POST['fone1'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Telefone Secundário</label>
                                    <input type="text" name="fone2" placeholder="(00) 00000-0000" value="<?php echo htmlspecialchars($_POST['fone2'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>E-mail</label>
                                    <input type="email" name="email1" placeholder="email@exemplo.com" value="<?php echo htmlspecialchars($_POST['email1'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="section-title">🏠 Endereço</div>
                            
                            <div class="form-row-4">
                                <div class="form-group">
                                    <label>Rua / Logradouro</label>
                                    <input type="text" name="e_rua" placeholder="Rua, Avenida..." value="<?php echo htmlspecialchars($_POST['e_rua'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Número</label>
                                    <input type="text" name="e_numero" placeholder="Nº" value="<?php echo htmlspecialchars($_POST['e_numero'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Bairro</label>
                                    <input type="text" name="e_bairro" placeholder="Bairro" value="<?php echo htmlspecialchars($_POST['e_bairro'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>CEP</label>
                                    <input type="text" name="e_cep" placeholder="00000-000" value="<?php echo htmlspecialchars($_POST['e_cep'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select name="e_estado" id="e_estado">
                                        <option value="1">Selecione...</option>
                                        <?php foreach ($estados as $uf): ?>
                                            <option value="<?php echo $uf['ID']; ?>" <?php echo (isset($_POST['e_estado']) && $_POST['e_estado'] == $uf['ID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($uf['name']); ?> (<?php echo $uf['prefix']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Cidade (ID)</label>
                                    <input type="number" name="e_cidade" placeholder="ID da cidade" value="<?php echo htmlspecialchars($_POST['e_cidade'] ?? '1'); ?>">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">💾 Cadastrar Cliente</button>
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
</body>
</html>
