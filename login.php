<?php
require 'admin/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    if($email == 'admin' && $password == "admin") {
        header('Location: abvgd.php');
    }

    $sql = "SELECT * FROM Клиент WHERE Электронная_почта = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (password_verify($password, $user['Хэш_пароль'])) {
            setcookie("user_name", $user['Имя_клиента'], time() + (86400 * 30), "/");
            setcookie("email", $email, time() + (86400 * 30), "/");
            header('Location: home_page.php');
            exit();
        } else {
            $error = "Неверный пароль.";
        }
    } else {
        $error = "Пользователь с такой электронной почтой не найден.";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация</title>
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="globals.css">
</head>
<body>
    <main>
        <form action="login.php" method="post">
            <img src="Pictures/Logo.png">
            <h1>Вход</h1>
            <input type="text" required placeholder="Электронная почта" name="email" value="<?php echo isset($_COOKIE['email']) ? htmlspecialchars($_COOKIE['email']) : ''; ?>">
            <input type="password" required placeholder="Пароль" name="password">

            <?php if (!empty($error)): ?>
                <p style="color: red; text-align: center;"><?php echo $error; ?></p>
            <?php endif; ?>

            <button type="submit">Войти</button>
            <p>Еще нету аккаунта?</p>
            <a href="registration-first.php">Зарегистрируйтесь</a>
        </form>
    </main> 
</body>
</html>


