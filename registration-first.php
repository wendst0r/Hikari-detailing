<?php
require 'admin/db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $sql = "INSERT INTO Клиент (Электронная_почта, Хэш_пароль) VALUES (:email, :password)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $email, 'password' => $hashedPassword]); // Используем хэшированный пароль
    } catch (PDOException $e) {
        // Проверяем, связано ли исключение с дубликатом записи (например, уникальное поле "Электронная_почта")
        if ($e->getCode() === '23505') { // Код ошибки для нарушения уникальности в MySQL
            $error = "Аккаунт с такой почтой уже существует";
        } else {
            $error = "Произошла ошибка при регистрации: " . $e->getMessage();
        }
    }

    if (empty($error)) {
        setcookie("email", $email, time() + (86400 * 30), "/");
        setcookie("hashedPassword", $hashedPassword, time() + (86400 * 30), "/");
        header('Location: registration-second.php');
        exit(); // Завершаем выполнение скрипта после перенаправления
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация</title>
    <link rel="stylesheet" href="registration.css">
    <link rel="stylesheet" href="globals.css">
</head>
<body>
    <main>
        <form id="myForm" action="registration-first.php" method="post">
            <div class="stages-box">
                <div class="stage1">
                    <p style="color:white;">Этап 1</p>
                    <div class="stage-bar" style="background: var(--accent-color);">
                    </div>
                </div>
                <div class="stage2">
                    <p style="color: #838383">Этап 2</p>
                    <div class="stage-bar" style="background: #838383">
                    </div>
                </div>
            </div>
            <h1>Регистрация</h1>
            <input type="text" required placeholder="Электронная почта" name="email" id="email">
            <input type="password" required placeholder="Пароль" name="password" id="password">
            <input type="password" required placeholder="Повтор пароля" name="confirmPassword" id="confirmPassword">
            <?php if (!empty($error)): ?>
                <p style="color: red; text-align: center;"><?php echo $error; ?></p>
            <?php endif; ?>
            <button type="submit">Сохранить</button>
        </form>
    </main>
</body>
<script>
    function isEmailValid(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    document.getElementById('myForm').addEventListener('submit', function(event) {
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (password !== confirmPassword) {
            event.preventDefault(); // Отменяем отправку формы
            alert('Пароли не совпадают!');
            confirmPassword.classList.add('error');
        }

        if (!isEmailValid(email)) {
            event.preventDefault(); // Отменяем отправку формы
            alert('Неправильная электронная почта!');
        }
    });
</script>
</html>