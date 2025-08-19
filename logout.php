<?php
session_start();

// Hapus semua variabel sesi
$_SESSION = array();
session_destroy();

// Debugging
echo "Logout berhasil. Mengalihkan ke index.php...";
header("Location: http://localhost/spk_lidia_fashion/index.php");
exit();
?>
