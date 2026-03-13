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

    // --- Video Hover Preview Logic ---
    function initVideoPreviews() {
        const containers = document.querySelectorAll('.thumbnail-container');

        containers.forEach(container => {
            if (container.dataset.previewInitialized) return;
            container.dataset.previewInitialized = "true";

            const video = container.querySelector('.preview-video');
            if (!video) return;

            const videoUrl = container.dataset.videoUrl;
            const videoType = container.dataset.videoType;

            // file veya url (vtt/mp4 direct link) tipindeki videoları önizleyebiliriz
            if (videoType !== 'file' && videoType !== 'url') return;

            let hoverTimer;
            let longPressTimer;

            const startPreview = () => {
                if (!video.src || video.src === window.location.href) {
                    // Video URL'si göreceli ise header'daki base path veya ../ durumuna göre düzenle
                    const finalUrl = videoUrl.startsWith('http') ? videoUrl : (videoUrl.startsWith('uploads') ? videoUrl : videoUrl);
                    video.src = finalUrl;
                }
                container.closest('.video-card').classList.add('playing-preview');
                video.play().catch(e => console.log("Preview play blocked:", e));
            };

            const stopPreview = () => {
                container.closest('.video-card').classList.remove('playing-preview');
                video.pause();
                video.currentTime = 0;
            };

            // Masaüstü: Mouse Hover
            container.addEventListener('mouseenter', () => {
                hoverTimer = setTimeout(startPreview, 150); // 150ms gecikme ile başlar
            });

            container.addEventListener('mouseleave', () => {
                clearTimeout(hoverTimer);
                stopPreview();
            });

            // Mobil: Uzun Basma (Long Press)
            container.addEventListener('touchstart', (e) => {
                // Tıklamayı engellememek için pasif geçiyoruz, sadece timer kuruyoruz
                longPressTimer = setTimeout(() => {
                    startPreview();
                    // Uzun basma olunca titreşim (varsa) verebiliriz
                    if (navigator.vibrate) navigator.vibrate(50);
                }, 600);
            }, { passive: true });

            container.addEventListener('touchend', () => {
                clearTimeout(longPressTimer);
                stopPreview();
            });

            container.addEventListener('touchmove', () => {
                clearTimeout(longPressTimer);
            });
        });
    }

    // İlk yüklemede çalıştır
    initVideoPreviews();

    // Sonsuz kaydırma (Infinite Scroll) ile yeni videolar gelince tekrar çalıştır
    const gridObserver = new MutationObserver(() => {
        initVideoPreviews();
    });
    const videoGrid = document.getElementById('video-grid-container');
    if (videoGrid) {
        gridObserver.observe(videoGrid, { childList: true });
    }
});
