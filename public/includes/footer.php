</div><!-- /app-layout -->

<script>
    // Sidebar toggle para desktop
    function toggleSidebar() {
        var sidebar = document.getElementById('sidebar');
        var mainWrapper = document.querySelector('.main-wrapper');
        
        // Verifica se é mobile
        if (window.innerWidth < 1024) {
            // Mobile: abre/fecha com classe open
            if (sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
            return;
        }
        
        // Desktop: colapsa/expande
        if (sidebar.classList.contains('collapsed')) {
            // Expandir
            sidebar.classList.remove('collapsed');
            if (mainWrapper) mainWrapper.classList.remove('sidebar-collapsed');
            localStorage.setItem('sidebar_collapsed', 'false');
        } else {
            // Colapsar
            sidebar.classList.add('collapsed');
            if (mainWrapper) mainWrapper.classList.add('sidebar-collapsed');
            localStorage.setItem('sidebar_collapsed', 'true');
        }
    }
    
    function openSidebar() {
        var sidebar = document.getElementById('sidebar');
        var overlay = document.getElementById('sidebar-overlay');
        sidebar.classList.add('open');
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeSidebar() {
        var sidebar = document.getElementById('sidebar');
        var overlay = document.getElementById('sidebar-overlay');
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    // Carregar estado salvo da sidebar (só no desktop)
    document.addEventListener('DOMContentLoaded', function() {
        if (window.innerWidth >= 1024) {
            var sidebarCollapsed = localStorage.getItem('sidebar_collapsed');
            var sidebar = document.getElementById('sidebar');
            var mainWrapper = document.querySelector('.main-wrapper');
            
            if (sidebarCollapsed === 'true') {
                sidebar.classList.add('collapsed');
                if (mainWrapper) mainWrapper.classList.add('sidebar-collapsed');
            }
        }
    });
    
    // Ajustar no resize
    window.addEventListener('resize', function() {
        var sidebar = document.getElementById('sidebar');
        var overlay = document.getElementById('sidebar-overlay');
        
        if (window.innerWidth >= 1024) {
            // Saiu do mobile, remove open e aplica collapsed se necessário
            sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('active');
            document.body.style.overflow = '';
            
            var sidebarCollapsed = localStorage.getItem('sidebar_collapsed');
            if (sidebarCollapsed === 'true') {
                sidebar.classList.add('collapsed');
            }
        }
    });
    
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
        if (icon) {
            if (theme === 'dark') {
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>';
            } else {
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>';
            }
        }
    }
    
    // Load saved theme
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);
</script>
</body>
</html>
