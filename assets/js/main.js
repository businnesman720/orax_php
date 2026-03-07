document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;
    const icon = themeToggle.querySelector('i');

    // Theme Toggle Logic
    if (localStorage.getItem('theme') === 'light') {
        body.classList.add('light-mode');
        icon.classList.replace('fa-moon', 'fa-sun');
    }

    themeToggle.addEventListener('click', () => {
        body.classList.toggle('light-mode');
        const isLight = body.classList.contains('light-mode');

        if (isLight) {
            icon.classList.replace('fa-moon', 'fa-sun');
            localStorage.setItem('theme', 'light');
        } else {
            icon.classList.replace('fa-sun', 'fa-moon');
            localStorage.setItem('theme', 'dark');
        }

        // Animasyon efekti
        body.style.transition = 'background-color 0.5s ease';
    });

    // Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                // Mobile behavior
                sidebar.classList.toggle('mobile-open');

                // Add overlay
                let overlay = document.getElementById('mobile-sidebar-overlay');
                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.id = 'mobile-sidebar-overlay';
                    overlay.style.position = 'fixed';
                    overlay.style.top = '0';
                    overlay.style.left = '0';
                    overlay.style.width = '100vw';
                    overlay.style.height = '100vh';
                    overlay.style.background = 'rgba(0,0,0,0.6)';
                    overlay.style.zIndex = '1999';
                    overlay.addEventListener('click', () => {
                        sidebar.classList.remove('mobile-open');
                        overlay.style.display = 'none';
                    });
                    document.body.appendChild(overlay);
                }
                overlay.style.display = sidebar.classList.contains('mobile-open') ? 'block' : 'none';

                // Clear inline styles from desktop
                sidebar.style.width = '';
                document.querySelectorAll('.sidebar-menu span').forEach(s => s.style.display = '');
            } else {
                // Desktop behavior
                sidebar.classList.toggle('collapsed');
                if (sidebar.classList.contains('collapsed')) {
                    sidebar.style.width = '80px';
                    document.querySelectorAll('.sidebar-menu span').forEach(s => s.style.display = 'none');
                } else {
                    sidebar.style.width = '260px';
                    document.querySelectorAll('.sidebar-menu span').forEach(s => s.style.display = 'inline');
                }
            }
        });
    }
});
