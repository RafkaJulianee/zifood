<?php
session_start();
include '../koneksi.php';

// Kalau sudah login, langsung ke dashboard admin
if (isset($_SESSION['id_admin'])) {
    header("Location: dashboard.admin.php");
    exit;
}

// Saat tombol login diklik
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Cek ke tabel admin
    $query = mysqli_query($conn, "SELECT * FROM admin WHERE username='$username' AND password='$password'");
    if (mysqli_num_rows($query) === 1) {
        $row = mysqli_fetch_assoc($query);

        // Buat session admin
        $_SESSION['id_admin'] = $row['id_admin'];
        $_SESSION['username_admin'] = $row['username'];

        header("Location: dashboard.admin.php");
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Admin - ZIFOOD</title>
    <script>
        function validateForm() {
            const username = document.forms["loginAdmin"]["username"].value;
            const password = document.forms["loginAdmin"]["password"].value;

            if (username.trim() === "" || password.trim() === "") {
                alert("Username dan password harus diisi!");
                return false;
            }
            if (password.length < 8) {
                alert("Password minimal 8 karakter!");
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
    <h1>Login Admin ZIFOOD</h1>
    <form name="loginAdmin" method="POST" onsubmit="return validateForm()">
        <label>Username:</label><br>
        <input type="text" name="username"><br><br>

        <label>Password:</label><br>
        <input type="password" name="password"><br><br>

        <button type="submit">Login</button>
    </form>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= $error; ?></p>
    <?php endif; ?>
</body>
</html>
