<?php
require 'admin/db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    function formatPhoneNumber($phone) {
        // Удаляем все нецифровые символы
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Проверяем, начинается ли номер с +7
        if (substr($phone, 0, 1) == '7') {
            $phone = '8' . substr($phone, 1); // Заменяем +7 на 8
        }

        return $phone;
    }

    $surname = $_POST['surname'];
    $user_name = $_POST['user_name'];
    $patronymic = $_POST['patronymic'];
    $phone = $_POST['phone'];
    $phone = formatPhoneNumber($phone);

    try {
        $sql = "UPDATE Клиент SET Фамилия_клиента = :surname, Имя_клиента = :user_name, Отчество_клиента = :patronymic, Номер_телефона_клиента = :phone WHERE Электронная_почта = :email;";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['surname' => $surname, 'user_name' => $user_name, 'patronymic' => $patronymic, 'phone' => $phone, 'email' => $_COOKIE['email']]);
    } catch (PDOException $e) {
        // Проверяем, связано ли исключение с дубликатом записи (например, уникальное поле "Номер_телефона_клиента")
        if ($e->getCode() === '23505') { // Код ошибки для нарушения уникальности в PostgreSQL
            $error = "Аккаунт с таким номером телефона уже существует";
        } else {
            $error = "Произошла ошибка при регистрации: " . $e->getMessage();
        }
    }
    if (empty($error)) {
        setcookie("user_name", $user_name, time() + (86400 * 30), "/");
        header('Location: home_page.php');
        exit();
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
        <form id="myForm" action="registration-second.php" method="post">
            <div class="stages-box">
                <div class="stage1">
                    <p style="color: #838383;">Этап 1</p>
                    <div class="stage-bar" style="background: var(--accent-color);">
                    </div>
                </div>
                <div class="stage2">
                    <p style="color: white">Этап 2</p>
                    <div class="stage-bar" style="background: var(--accent-color);">
                    </div>
                </div>
            </div>
            <h1>Регистрация</h1>
            <input type="text" required placeholder="Фамилия" name="surname">
            <input type="text" required placeholder="Имя" name="user_name">
            <input type="text" required placeholder="Отчество" name="patronymic">
            <input 
                type="tel" 
                id="phone" 
                name="phone" 
                placeholder="+7(___) ___-__-__" 
                maxlength="18" 
                required>
            <?php if (!empty($error)): ?>
                <p style="color: red; text-align: center;"><?php echo $error; ?></p>
            <?php endif; ?>
            <button type="submit">Зарегистрироваться</button>
        </form>
    </main>
</body>
<script>
    document.getElementById('phone').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, ''); // Удаляем все, кроме цифр
        
        // Добавляем +7 только если его еще нет
        if (!value.startsWith('7')) {
            value = '7' + value;
        }
        if (value == 7) {
            value = null;
        }

        if (value.length > 1) value = '+7 (' + value.slice(1); // Пропускаем первую цифру (7)
        if (value.length > 7) value = value.slice(0, 7) + ') ' + value.slice(7);
        if (value.length > 12) value = value.slice(0, 12) + '-' + value.slice(12);
        if (value.length > 15) value = value.slice(0, 15) + '-' + value.slice(15);
        
        e.target.value = value;
    });
</script>
</html>