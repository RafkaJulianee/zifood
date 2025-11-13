<?php
include '../koneksi.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);

    $query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) === 1) {
        $data = mysqli_fetch_assoc($result);
        $_SESSION['id_user'] = $data['id_user'];
        $_SESSION['username'] = $data['username'];
        header("Location: dashboard.php");
        exit;
    } else {
        echo "<script>alert('Username atau password salah!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login User - Zifood</title>
    <script>
        function validateLogin() {
            const user = document.getElementById("username").value.trim();
            const pass = document.getElementById("password").value.trim();

            if (user === "" || pass === "") {
                alert("Username dan Password tidak boleh kosong!");
                return false;
            }
            if (pass.length < 8) {
                alert("Password harus minimal 8 karakter!");
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
    <h2>Login ke ZIFOOD</h2>
    <form method="POST" action="" onsubmit="return validateLogin()">
        <label>Username:</label><br>
        <input type="text" name="username" id="username" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" id="password" required><br><br>

        <button type="submit">Masuk</button>
    </form>

    <p>Belum punya akun? <a href="register.php">Daftar Sekarang</a></p>
</body>
</html>
