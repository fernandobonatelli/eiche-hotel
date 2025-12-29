<?php
/**
 * Eiche Hotel - Sistema de Hotelaria e Cobrança
 * Configurações Gerais do Sistema - PHP 8.x
 * 
 * @version 2.0
 * @license GNU GPL v3
 */

declare(strict_types=1);

// Configurações de ambiente
define('EICHE_VERSION', '2.0.0');
define('EICHE_ENV', $_ENV['APP_ENV'] ?? 'production');
define('EICHE_DEBUG', EICHE_ENV === 'development');

// Diretórios
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// URLs
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', $protocol . $host . '/eiche/v2');

// Sistema
$config = [
    'app_name' => 'Eiche Hotel',
    'app_description' => 'Sistema de Hotelaria e Cobrança',
    'timezone' => 'America/Sao_Paulo',
    'locale' => 'pt_BR',
    'charset' => 'UTF-8',
    
    // Sessão
    'session' => [
        'name' => 'eiche_session',
        'lifetime' => 7200, // 2 horas
        'secure' => EICHE_ENV === 'production',
        'httponly' => true,
        'samesite' => 'Lax'
    ],
    
    // Tema
    'theme' => [
        'default' => 'modern',
        'available' => ['modern', 'dark', 'light']
    ],
    
    // Idioma
    'language' => [
        'default' => 'pt_BR',
        'available' => ['pt_BR', 'en_US', 'es_ES']
    ],
    
    // Suporte
    'support' => [
        'email' => 'contato@eiche.com.br',
        'phone' => 'xx xxxx-xxxx',
        'website' => 'http://www.eiche.com.br'
    ]
];

// Configurar timezone
date_default_timezone_set($config['timezone']);

// Configurar erro handling
if (EICHE_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Configurar sessão
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', $config['session']['samesite']);

return $config;

