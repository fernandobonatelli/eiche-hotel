<?php
/**
 * Pousada Bona - Logout
 * 
 * @version 2.0
 */

declare(strict_types=1);

session_start();

// Limpar token de remember me
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/database.php';
    
    try {
        $db = \Eiche\Config\Database::getInstance();
        $db->query("UPDATE eiche_users SET remember_token = NULL WHERE ID = ?", [$_SESSION['user_id']]);
        
        // Registrar log de logout
        $db->query(
            "INSERT INTO eiche_log (ID_user, action, ip, created_at) VALUES (?, ?, ?, NOW())",
            [$_SESSION['user_id'], 'logout', $_SERVER['REMOTE_ADDR'] ?? 'unknown']
        );
    } catch (\Exception $e) {
        error_log('Logout error: ' . $e->getMessage());
    }
}

// Destruir sessão
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Remover cookie remember
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

session_destroy();

header('Location: login.php');
exit;

