<?php
// index.php (Halaman Login)
require_once 'functions/auth.php'; // Sertakan file fungsi autentikasi

if (is_logged_in()) {
    header("Location: admin/dashboard.php"); // Jika sudah login, arahkan ke dashboard
    exit();
}

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'config/database.php'; // Sertakan koneksi database
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (login_user($username, $password, $conn)) {
        header("Location: admin/dashboard.php");
        exit();
    } else {
        $error_message = "Username atau password salah.";
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login SPK Lidia Fashion</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <h2>Login Admin</h2>
        <?php if ($error_message): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form action="index.php" method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>
</body>
</html>
