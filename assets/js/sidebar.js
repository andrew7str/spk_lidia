// assets/js/sidebar.js
// JavaScript untuk mengontrol sidebar collapse/expand

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.querySelector('#sidebarToggle');
    
    // Toggle sidebar saat diklik
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            sidebar.classList.toggle('expanded');
            
            // Tambahkan efek visual pada tombol
            this.style.transform = sidebar.classList.contains('expanded') ? 'rotate(90deg)' : 'rotate(0deg)';
        });
    }
    
    // Klik di luar sidebar untuk menutup (hanya jika expanded)
    document.addEventListener('click', function(e) {
        if (sidebar && sidebar.classList.contains('expanded')) {
            if (!sidebar.contains(e.target)) {
                sidebar.classList.remove('expanded');
                if (sidebarToggle) {
                    sidebarToggle.style.transform = 'rotate(0deg)';
                }
            }
        }
    });
    
    // Handle resize window
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && sidebar) {
            sidebar.classList.remove('expanded');
            if (sidebarToggle) {
                sidebarToggle.style.transform = 'rotate(0deg)';
            }
        }
    });
    
    // Tambahkan active class pada menu yang sedang aktif
    const currentPage = window.location.pathname.split('/').pop();
    const menuLinks = document.querySelectorAll('.sidebar-menu a');
    
    menuLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage || (currentPage === '' && href === 'dashboard.php')) {
            link.classList.add('active');
        }
    });
    
    // Prevent sidebar collapse saat hover jika sudah di-expand dengan klik
    let isManuallyExpanded = false;
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            isManuallyExpanded = sidebar.classList.contains('expanded');
        });
    }
    
    // Reset manual expansion flag saat mouse leave
    if (sidebar) {
        sidebar.addEventListener('mouseleave', function() {
            if (!sidebar.classList.contains('expanded')) {
                isManuallyExpanded = false;
            }
        });
    }
});
