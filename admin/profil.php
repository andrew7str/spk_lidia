<?php
// admin/profil.php
require_once 'includes/header.php';
require_once '../config/database.php';

$message = '';
$message_type = '';

$user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];

// Ambil data user dari database
$user_data = null;
$stmt = $conn->prepare("SELECT id, username, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 1) {
    $user_data = $result->fetch_assoc();
}
$stmt->close();

// Logika Update Profil
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $new_username = $_POST['username'];
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Cek password lama jika ingin mengubah password
    $stmt_check_pass = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt_check_pass->bind_param("i", $user_id);
    $stmt_check_pass->execute();
    $result_pass = $stmt_check_pass->get_result();
    $db_password_hash = $result_pass->fetch_assoc()['password'];
    $stmt_check_pass->close();

    // Untuk contoh ini, kita asumsikan password_admin di database adalah plain text
    // DI PRODUKSI, SELALU GUNAKAN password_verify($old_password, $db_password_hash)
    if ($old_password !== $db_password_hash) { // Ganti dengan password_verify() di produksi
        $message = "Password lama salah.";
        $message_type = "danger";
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $message = "Password baru dan konfirmasi password tidak cocok.";
        $message_type = "danger";
    } else {
        // Update username
        $stmt_update = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt_update->bind_param("si", $new_username, $user_id);
        if ($stmt_update->execute()) {
            $_SESSION['username'] = $new_username; // Update sesi username
            $message = "Profil berhasil diperbarui!";
            $message_type = "success";
        } else {
            $message = "Gagal memperbarui username: " . $stmt_update->error;
            $message_type = "danger";
        }
        $stmt_update->close();

        // Update password jika ada
        if (!empty($new_password) && $message_type != "danger") {
            // DI PRODUKSI, SELALU GUNAKAN password_hash($new_password, PASSWORD_DEFAULT)
            $hashed_new_password = $new_password; // Ganti dengan password_hash() di produksi
            $stmt_update_pass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_update_pass->bind_param("si", $hashed_new_password, $user_id);
            if ($stmt_update_pass->execute()) {
                $message .= " Password juga berhasil diperbarui.";
                $message_type = "success";
            } else {
                $message = "Gagal memperbarui password: " . $stmt_update_pass->error;
                $message_type = "danger";
            }
            $stmt_update_pass->close();
        }
    }
}
?>

<h2>Profil Admin</h2>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<div class="form-section">
    <h3>Informasi Profil</h3>
    <form action="profil.php" method="POST">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="role">Role:</label>
            <input type="text" id="role" name="role" value="<?php echo htmlspecialchars($user_data['role'] ?? ''); ?>" disabled>
        </div>
        <hr>
        <h3>Ubah Password (Opsional)</h3>
        <p>Isi kolom di bawah ini hanya jika Anda ingin mengubah password.</p>
        <div class="form-group">
            <label for="old_password">Password Lama:</label>
            <input type="password" id="old_password" name="old_password">
        </div>
        <div class="form-group">
            <label for="new_password">Password Baru:</label>
            <input type="password" id="new_password" name="new_password">
        </div>
        <div class="form-group">
            <label for="confirm_password">Konfirmasi Password Baru:</label>
            <input type="password" id="confirm_password" name="confirm_password">
        </div>
        <div class="form-actions">
            <button type="submit" name="update_profile" class="btn btn-primary">Update Profil</button>
        </div>
    </form>
</div>

<?php
require_once 'includes/footer.php';
$conn->close();
?>
