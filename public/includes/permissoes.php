<?php
/**
 * Pousada Bona - Controle de Permissões
 * 
 * Verifica permissões do usuário logado
 */

// Garantir que a tabela tem a coluna de permissão
function verificarColunaPermissao($conexao) {
    // Verificar se coluna existe
    $result = mysqli_query($conexao, "SHOW COLUMNS FROM eiche_users LIKE 'ver_valores'");
    if (mysqli_num_rows($result) == 0) {
        // Adicionar coluna
        mysqli_query($conexao, "ALTER TABLE eiche_users ADD COLUMN ver_valores CHAR(1) DEFAULT 'S'");
    }
    
    // Verificar coluna nivel
    $result = mysqli_query($conexao, "SHOW COLUMNS FROM eiche_users LIKE 'nivel'");
    if (mysqli_num_rows($result) == 0) {
        mysqli_query($conexao, "ALTER TABLE eiche_users ADD COLUMN nivel VARCHAR(20) DEFAULT 'user'");
    }
}

// Verificar se usuário pode ver valores
function podeVerValores($conexao, $userId = null) {
    if ($userId === null) {
        $userId = $_SESSION['user_id'] ?? 0;
    }
    
    if ($userId <= 0) {
        return false;
    }
    
    // Verificar se coluna existe
    verificarColunaPermissao($conexao);
    
    $result = mysqli_query($conexao, "SELECT ver_valores, nivel FROM eiche_users WHERE ID = $userId");
    if ($result && $row = mysqli_fetch_assoc($result)) {
        // Admin sempre pode ver
        if ($row['nivel'] === 'admin') {
            return true;
        }
        return $row['ver_valores'] === 'S';
    }
    
    return false;
}

// Verificar se é administrador
function isAdmin($conexao, $userId = null) {
    if ($userId === null) {
        $userId = $_SESSION['user_id'] ?? 0;
    }
    
    if ($userId <= 0) {
        return false;
    }
    
    verificarColunaPermissao($conexao);
    
    $result = mysqli_query($conexao, "SELECT nivel FROM eiche_users WHERE ID = $userId");
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return $row['nivel'] === 'admin';
    }
    
    return false;
}

// Formatar valor com controle de permissão
function formatarValor($valor, $podeVer = true, $casasDecimais = 2) {
    if ($podeVer) {
        return 'R$ ' . number_format($valor, $casasDecimais, ',', '.');
    }
    return '<span class="valor-oculto" title="Sem permissão para visualizar">R$ •••••</span>';
}

// Formatar valor apenas número
function formatarValorNumero($valor, $podeVer = true, $casasDecimais = 2) {
    if ($podeVer) {
        return number_format($valor, $casasDecimais, ',', '.');
    }
    return '•••••';
}
?>

