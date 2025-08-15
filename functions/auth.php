<?php
// functions/auth.php

session_start(); // Mulai sesi PHP

// Fungsi untuk memeriksa apakah user sudah login
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Fungsi untuk mengarahkan user ke halaman login jika belum login
function redirect_if_not_logged_in() {
    if (!is_logged_in()) {
        header("Location: ../index.php"); // Arahkan ke halaman login
        exit();
    }
}

// Fungsi untuk login user
function login_user($username, $password, $conn) {
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        // Verifikasi password (gunakan password_verify untuk password_hash)
        // Untuk contoh ini, kita asumsikan password_admin di database adalah plain text
        // DI PRODUKSI, SELALU GUNAKAN password_hash() DAN password_verify()
        if ($password === $user['password']) { // Ganti dengan password_verify($password, $user['password']) di produksi
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
    }
    return false;
}

// Fungsi untuk logout user
function logout_user() {
    session_unset(); // Hapus semua variabel sesi
    session_destroy(); // Hancurkan sesi
    header("Location: ../index.php"); // Arahkan ke halaman login
    exit();
}
?>
