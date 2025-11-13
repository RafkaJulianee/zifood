<?php
include '../koneksi.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);

    // Cek apakah username sudah dipakai
    $cek = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>alert('Username sudah digunakan!');window.location='register.php';</script>";
    } else {
        if (strlen($password) < 8) {
            echo "<script>alert('Password minimal 8 karakter!');window.location='register.php';</script>";
            exit;
        }

        $password_hash = md5($password);
        $query = "INSERT INTO users (username, password, nama)
                  VALUES ('$username', '$password_hash', '$nama')";
        if (mysqli_query($conn, $query)) {
            echo "<script>alert('Registrasi berhasil! Silakan login.');window.location='login.php';</script>";
        } else {
            echo "<script>alert('Registrasi gagal!');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Akun - Zifood</title>
    <script>
        function validateForm() {
            const password = document.getElementById("password").value;

            if (password.length < 8) {
                alert("Password harus minimal 8 karakter!");
                return false; // hentikan pengiriman form
            }
            return true; // lanjut submit
        }
    </script>
</head>
<body>
    <h2>Form Register</h2>
    <form method="POST" action="" onsubmit="return validateForm()">
        <label>Nama Lengkap:</label><br>
        <input type="text" name="nama" required><br><br>

        <label>Username:</label><br>
        <input type="text" name="username" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" id="password" required><br><br>

        <button type="submit">Daftar</button>
    </form>

    <p>Sudah punya akun? <a href="index.php">Login</a></p>
</body>
</html>
