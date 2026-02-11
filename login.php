<?php
require_once 'auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (login($username, $password)) {
        header("Location: index.php");
        exit;
    } else {
        $error = 'Geçersiz kullanıcı adı veya şifre.';
    }
}

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - YerliCRM</title>
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/main.css">
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <h1>YerliCRM</h1>
            <p>Devam etmek için giriş yapın</p>
        </div>

        <?php if ($error): ?>
            <div class="error-msg">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="username">Kullanıcı Adı</label>
                <input type="text" id="username" name="username" required placeholder="admin">
            </div>
            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn-login">Giriş Yap</button>
        </form>
    </div>
</body>

</html>