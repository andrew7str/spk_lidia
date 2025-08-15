// assets/js/script.js

document.addEventListener('DOMContentLoaded', function() {
    // Contoh: Menandai menu sidebar yang aktif
    const currentPath = window.location.pathname;
    const sidebarLinks = document.querySelectorAll('.sidebar-menu a');

    sidebarLinks.forEach(link => {
        // Cek apakah href link cocok dengan path saat ini
        // Contoh: /spk_lidia_fashion/admin/dashboard.php
        // Kita hanya perlu bagian setelah /admin/
        if (currentPath.includes(link.getAttribute('href').replace('../', ''))) {
            link.classList.add('active');
        }
    });

    // Contoh: Konfirmasi sebelum menghapus (jika ada tombol delete)
    document.querySelectorAll('.btn-danger').forEach(button => {
        button.addEventListener('click', function(event) {
            if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                event.preventDefault(); // Batalkan aksi jika tidak yakin
            }
        });
    });

    // Anda bisa menambahkan lebih banyak interaktivitas di sini
    // seperti validasi form sisi klien, efek UI, dll.
});
