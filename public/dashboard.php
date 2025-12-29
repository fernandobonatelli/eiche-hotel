<?php
/**
 * Eiche Hotel - Dashboard Principal
 * 
 * @version 2.0
 */

declare(strict_types=1);

session_start();

// Verificar autenticação
if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

use Eiche\Config\Database;
use function Eiche\Helpers\{formatMoney, dateToBr, e};

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

// Buscar estatísticas do dashboard
try {
    // Total de hóspedes ativos
    $totalGuests = $db->fetchOne("SELECT COUNT(*) as total FROM eiche_customers WHERE status = 'A'")['total'] ?? 0;
    
    // Reservas do dia
    $todayReservations = $db->fetchOne(
        "SELECT COUNT(*) as total FROM eiche_reservations WHERE DATE(check_in) = CURDATE()"
    )['total'] ?? 0;
    
    // Receita do mês
    $monthRevenue = $db->fetchOne(
        "SELECT COALESCE(SUM(value), 0) as total FROM eiche_payments WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status = 'paid'"
    )['total'] ?? 0;
    
    // Quartos ocupados
    $occupiedRooms = $db->fetchOne(
        "SELECT COUNT(*) as total FROM eiche_rooms WHERE status = 'occupied'"
    )['total'] ?? 0;
    
    // Últimas reservas
    $recentReservations = $db->fetchAll(
        "SELECT r.*, c.name as guest_name, rm.number as room_number 
         FROM eiche_reservations r 
         LEFT JOIN eiche_customers c ON r.customer_id = c.ID 
         LEFT JOIN eiche_rooms rm ON r.room_id = rm.ID 
         ORDER BY r.created_at DESC LIMIT 5"
    );
    
} catch (\Exception $e) {
    // Valores padrão em caso de erro
    $totalGuests = 0;
    $todayReservations = 0;
    $monthRevenue = 0;
    $occupiedRooms = 0;
    $recentReservations = [];
}

$pageTitle = 'Dashboard - Eiche Hotel';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dashboard - Sistema de Hotelaria Eiche">
    
    <title><?= e($pageTitle) ?></title>
    
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
    <div class="app-layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="sidebar-logo-icon">E</div>
                    <span class="sidebar-logo-text">Eiche Hotel</span>
                </div>
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                    </svg>
                </button>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Principal</div>
                    <ul class="nav-menu">
                        <li>
                            <a href="dashboard.php" class="nav-item active">
                                <span class="nav-item-icon">
                                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                    </svg>
                                </span>
                                <span class="nav-item-text">Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="reservations.php" class="nav-item">
                                <span class="nav-item-icon">
                                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </span>
                                <span class="nav-item-text">Reservas</span>
                                <span class="nav-item-badge">3</span>
                            </a>
                        </li>
                        <li>
                            <a href="rooms.php" class="nav-item">
                                <span class="nav-item-icon">
                                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </span>
                                <span class="nav-item-text">Quartos</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Gestão</div>
                    <ul class="nav-menu">
                        <li>
                            <a href="guests.php" class="nav-item">
                                <span class="nav-item-icon">
                                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                </span>
                                <span class="nav-item-text">Hóspedes</span>
                            </a>
                        </li>
                        <li>
                            <a href="finance.php" class="nav-item">
                                <span class="nav-item-icon">
                                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </span>
                                <span class="nav-item-text">Financeiro</span>
                            </a>
                        </li>
                        <li>
                            <a href="invoices.php" class="nav-item">
                                <span class="nav-item-icon">
                                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </span>
                                <span class="nav-item-text">Boletos</span>
                            </a>
                        </li>
                        <li>
                            <a href="reports.php" class="nav-item">
                                <span class="nav-item-icon">
                                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </span>
                                <span class="nav-item-text">Relatórios</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Sistema</div>
                    <ul class="nav-menu">
                        <li>
                            <a href="settings.php" class="nav-item">
                                <span class="nav-item-icon">
                                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </span>
                                <span class="nav-item-text">Configurações</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-menu" onclick="toggleUserDropdown()">
                    <div class="user-avatar"><?= strtoupper(substr($userName, 0, 1)) ?></div>
                    <div class="user-info">
                        <div class="user-name"><?= e($userName) ?></div>
                        <div class="user-role">Administrador</div>
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- Overlay para mobile -->
        <div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>
        
        <!-- Main Content -->
        <div class="main-wrapper">
            <header class="main-header">
                <div class="header-left">
                    <button class="mobile-menu-btn" onclick="openSidebar()">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <h1 class="page-title">Dashboard</h1>
                </div>
                
                <div class="header-right">
                    <div class="header-search">
                        <svg class="header-search-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" placeholder="Buscar...">
                    </div>
                    
                    <button class="header-btn">
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="header-btn-badge"></span>
                    </button>
                    
                    <button class="theme-toggle" onclick="toggleTheme()">
                        <svg id="theme-icon" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>
                    
                    <a href="logout.php" class="header-btn" title="Sair">
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </a>
                </div>
            </header>
            
            <main class="main-content">
                <div class="content-header">
                    <div class="content-header-left">
                        <h1>Olá, <?= e(explode(' ', $userName)[0]) ?>!</h1>
                        <p>Aqui está um resumo das atividades do seu hotel hoje.</p>
                    </div>
                    <div class="content-header-actions">
                        <button class="btn btn-secondary">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Exportar
                        </button>
                        <button class="btn btn-primary">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Nova Reserva
                        </button>
                    </div>
                </div>
                
                <!-- Stats Grid -->
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Total de Hóspedes</div>
                            <div class="stat-value"><?= number_format($totalGuests) ?></div>
                            <div class="stat-change positive">
                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z"/>
                                </svg>
                                +12% este mês
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Reservas Hoje</div>
                            <div class="stat-value"><?= number_format($todayReservations) ?></div>
                            <div class="stat-change positive">
                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z"/>
                                </svg>
                                +3 novos
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Receita do Mês</div>
                            <div class="stat-value"><?= formatMoney($monthRevenue) ?></div>
                            <div class="stat-change positive">
                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z"/>
                                </svg>
                                +8.5% vs mês anterior
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Quartos Ocupados</div>
                            <div class="stat-value"><?= number_format($occupiedRooms) ?></div>
                            <div class="stat-change negative">
                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z"/>
                                </svg>
                                -2% ocupação
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Reservations -->
                <div class="card" style="margin-top: var(--space-6);">
                    <div class="card-header">
                        <h3>Últimas Reservas</h3>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <div class="table-wrapper" style="border: none; border-radius: 0;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Hóspede</th>
                                        <th>Quarto</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentReservations)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: var(--space-8); color: var(--text-muted);">
                                            Nenhuma reserva encontrada
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($recentReservations as $reservation): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: var(--space-3);">
                                                <div class="avatar avatar-sm">
                                                    <?= strtoupper(substr($reservation['guest_name'] ?? 'H', 0, 1)) ?>
                                                </div>
                                                <span><?= e($reservation['guest_name'] ?? 'Hóspede') ?></span>
                                            </div>
                                        </td>
                                        <td><?= e($reservation['room_number'] ?? '-') ?></td>
                                        <td><?= dateToBr($reservation['check_in'] ?? '') ?></td>
                                        <td><?= dateToBr($reservation['check_out'] ?? '') ?></td>
                                        <td>
                                            <span class="badge badge-success">Confirmado</span>
                                        </td>
                                        <td>
                                            <button class="btn btn-ghost btn-sm">Ver</button>
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
    </div>
    
    <script>
        // Sidebar toggle
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
        
        function openSidebar() {
            document.getElementById('sidebar').classList.add('open');
            document.getElementById('sidebar-overlay').classList.add('open');
        }
        
        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebar-overlay').classList.remove('open');
        }
        
        // Theme toggle
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        }
        
        function updateThemeIcon(theme) {
            const icon = document.getElementById('theme-icon');
            if (theme === 'dark') {
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>';
            } else {
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>';
            }
        }
        
        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);
    </script>
</body>
</html>

