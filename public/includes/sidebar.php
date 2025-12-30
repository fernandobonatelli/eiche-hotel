<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="assets/images/logo.jpg" alt="Pousada Bona" class="sidebar-logo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="sidebar-logo-icon" style="display: none;">PB</div>
            <span class="sidebar-logo-text">Pousada Bona</span>
        </div>
        <button class="sidebar-toggle" onclick="toggleSidebar()" title="Abrir/Fechar Menu" style="display: flex;">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Principal</div>
            <ul class="nav-menu">
                <li>
                    <a href="dashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                        <span class="nav-item-icon">
                            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                        </span>
                        <span class="nav-item-text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="reservations.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'reservations.php' ? 'active' : '' ?>">
                        <span class="nav-item-icon">
                            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        <span class="nav-item-text">Hospedagens</span>
                    </a>
                </li>
                <li>
                    <a href="rooms.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'rooms.php' ? 'active' : '' ?>">
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
                    <a href="guests.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'guests.php' ? 'active' : '' ?>">
                        <span class="nav-item-icon">
                            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </span>
                        <span class="nav-item-text">Clientes</span>
                    </a>
                </li>
                <li>
                    <a href="finance.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'finance.php' ? 'active' : '' ?>">
                        <span class="nav-item-icon">
                            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                        <span class="nav-item-text">Financeiro</span>
                    </a>
                </li>
                <li>
                    <a href="expenses.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'expenses.php' ? 'active' : '' ?>">
                        <span class="nav-item-icon">
                            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </span>
                        <span class="nav-item-text">Despesas</span>
                    </a>
                </li>
                <li>
                    <a href="products.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>">
                        <span class="nav-item-icon">
                            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </span>
                        <span class="nav-item-text">Produtos/Serviços</span>
                    </a>
                </li>
                <li>
                    <a href="reports.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
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
        
        <?php 
        // Verificar se é admin para mostrar configurações
        $showSettings = false;
        if (isset($conexao) && isset($_SESSION['user_id'])) {
            $checkAdmin = mysqli_query($conexao, "SELECT nivel FROM eiche_users WHERE ID = " . (int)$_SESSION['user_id']);
            if ($checkAdmin && $row = mysqli_fetch_assoc($checkAdmin)) {
                $showSettings = ($row['nivel'] === 'admin');
            }
        }
        if ($showSettings): 
        ?>
        <div class="nav-section">
            <div class="nav-section-title">Sistema</div>
            <ul class="nav-menu">
                <li>
                    <a href="settings.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
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
        <?php endif; ?>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-menu">
            <div class="user-avatar"><?= strtoupper(substr($userName ?? 'U', 0, 1)) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($userName ?? 'Usuário') ?></div>
                <div class="user-role">Administrador</div>
            </div>
        </div>
    </div>
</aside>

<!-- Overlay para mobile -->
<div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

