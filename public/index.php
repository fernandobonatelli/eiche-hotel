<?php
/**
 * Pousada Bona - Página Inicial
 * Redireciona para login ou dashboard
 * 
 * @version 2.0
 */

declare(strict_types=1);

session_start();

// Se já estiver logado, vai para dashboard
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && $_SESSION['logged_in']) {
    header('Location: dashboard.php');
    exit;
}

// Senão, vai para login
header('Location: login.php');
exit;

