<?php
require 'admin/db.php';
$error = '';

// SQL-запрос для получения данных о заказах, услугах, процедурах и их статусе выполнения
$sql = "SELECT 
    o.\"ИД_Заказа\",
    o.\"Текущий_статус_заказа\",
    a.\"Марка_автомобиля\", 
    a.\"Модель_автомобиля\", 
    a.\"Год_выпуска\", 
    oc.\"Название_услуги\", 
    cs.\"Название_процедуры\",
    p.\"Длительность_процедуры\",
    CASE 
        WHEN wm.\"ИД_Заказа\" IS NOT NULL THEN 'Выполнено'
        ELSE 'Предстоит'
    END AS \"Статус_выполнения\"
FROM 
    \"Автомобиль\" AS a
JOIN 
    \"Заказ\" AS o ON a.\"VIN_номер\" = o.\"VIN_номер\"
JOIN 
    \"Клиент\" AS c ON o.\"Электронная_почта\" = c.\"Электронная_почта\"
JOIN 
    \"Состав_заказа\" AS oc ON o.\"ИД_Заказа\" = oc.\"ИД_Заказа\"
JOIN 
    \"Состав_услуги\" AS cs ON oc.\"Название_услуги\" = cs.\"Название_услуги\"
JOIN 
    \"Процедура\" AS p ON cs.\"Название_процедуры\" = p.\"Название_процедуры\"
LEFT JOIN 
    \"Работа_мастера\" AS wm ON o.\"ИД_Заказа\" = wm.\"ИД_Заказа\" AND cs.\"Название_процедуры\" = wm.\"Название_процедуры\"
WHERE c.\"Электронная_почта\" = :email";
$stmt = $pdo->prepare($sql);
$stmt->execute(['email' => $_COOKIE['email']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Группировка данных по заказам, услугам и процедурам
$groupedOrders = [];
foreach ($orders as $order) {
    $orderId = $order['ИД_Заказа'];
    $serviceName = $order['Название_услуги'];

    if (!isset($groupedOrders[$orderId])) {
        $groupedOrders[$orderId] = [
            'Текущий_статус_заказа' => $order['Текущий_статус_заказа'],
            'Марка_автомобиля' => $order['Марка_автомобиля'],
            'Модель_автомобиля' => $order['Модель_автомобиля'],
            'Год_выпуска' => $order['Год_выпуска'],
            'Услуги' => []
        ];
    }

    if (!isset($groupedOrders[$orderId]['Услуги'][$serviceName])) {
        $groupedOrders[$orderId]['Услуги'][$serviceName] = [
            'Процедуры' => []
        ];
    }

    $groupedOrders[$orderId]['Услуги'][$serviceName]['Процедуры'][] = [
        'Название_процедуры' => $order['Название_процедуры'],
        'Длительность_процедуры' => $order['Длительность_процедуры'],
        'Статус_выполнения' => $order['Статус_выполнения']
    ];
}

// Получаем автомобили клиента
$sqlCars = "SELECT a.\"Марка_автомобиля\", a.\"Модель_автомобиля\", a.\"Год_выпуска\", a.\"Регистрационный_номер\", a.\"VIN_номер\"
            FROM \"Автомобиль_клиента\" AS ac
            JOIN \"Автомобиль\" AS a ON ac.\"VIN_номер\" = a.\"VIN_номер\"
            WHERE ac.\"Электронная_почта\" = :email";
$stmtCars = $pdo->prepare($sqlCars);
$stmtCars->execute(['email' => $_COOKIE['email']]);
$cars = $stmtCars->fetchAll(PDO::FETCH_ASSOC);

// Обработка удаления автомобиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_car'])) {
    $vin_number = $_POST['vin_number'];
    try {
        $sqlDelete = "DELETE FROM \"Автомобиль_клиента\" WHERE \"VIN_номер\" = :vin_number AND \"Электронная_почта\" = :email";
        $stmtDelete = $pdo->prepare($sqlDelete);
        $stmtDelete->execute([
            'vin_number' => $vin_number,
            'email' => $_COOKIE['email']
        ]);
        // Перенаправляем на эту же страницу, чтобы обновить список автомобилей
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } catch (PDOException $e) {
        $error = 'Ошибка при удалении автомобиля: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет</title>
    <link rel="stylesheet" href="home_page.css">
    <link rel="stylesheet" href="globals.css">
</head>
<body>
    <main>
        <div class="header">
            <p style="font-size: 2vw">
                <?php 
                    $current_time = intval(date('H')) + 4;
                    if($current_time >= 23)
                    {
                        echo "Доброй ночи, <b>".$_COOKIE['user_name']."!</b>";
                    }
                    else if($current_time >= 15) {
                        echo "Добрый вечер, <b>".$_COOKIE['user_name']."!</b>";
                    }
                    else if($current_time >= 12) {
                        echo "Добрый день, <b>".$_COOKIE['user_name']."!</b>";
                    }
                    else if($current_time >= 5) {
                        echo "Доброе утро, <b>".$_COOKIE['user_name']."!</b>";
                    }
            ?> </p>
            <button id="edit_button" class="header_button" style="display: none;">Редактировать профиль</button>
            <button id="exit_button" class="header_button">Выйти</button>
        </div>
        <div class="top_menu_container">
            <button id="new_order_button">Оформить заказ</button>
            <button>Оставить рекламацию</button>
            <button>История заказов</button>
            <button id="new_car_button">Добавить автомобиль</button>
        </div>
        <div class="main_container">
            <div class="current_orders">
                <?php if (!empty($groupedOrders)): ?>
                    <?php foreach ($groupedOrders as $orderId => $order): ?>
                        <?php if ($order['Текущий_статус_заказа'] !== 'Отменен' && $order['Текущий_статус_заказа'] !== 'Завершен'): ?>
                            <div class="order">
                                <div class="order_title">
                                    <p>Заказ №<?php echo htmlspecialchars($orderId); ?></p>
                                    <p><?php echo htmlspecialchars($order['Марка_автомобиля']) . " " . htmlspecialchars($order['Модель_автомобиля']) . " (" . htmlspecialchars($order['Год_выпуска']) . ") года"; ?></p>
                                    <p>Статус: <?php echo htmlspecialchars($order['Текущий_статус_заказа']); ?></p>
                                </div>
                                <div class="order_details">
                                    <?php foreach ($order['Услуги'] as $serviceName => $service): ?>
                                        <h4>Услуга: <?php echo htmlspecialchars($serviceName); ?></h4>
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Процедура</th>
                                                    <th>Длительность</th>
                                                    <th>Статус</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($service['Процедуры'] as $procedure): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($procedure['Название_процедуры']); ?></td>
                                                        <td><?php echo htmlspecialchars($procedure['Длительность_процедуры']); ?></td>
                                                        <td class="<?php echo ($procedure['Статус_выполнения'] === 'Выполнено') ? 'status-completed' : ''; ?>">
                                                        <?php echo htmlspecialchars($procedure['Статус_выполнения']); ?> </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="margin: auto; margin-top: 1vw; margin-bottom: 1vw;">У вас нет активных заказов.</p>
                <?php endif; ?>
            </div>
            <div class="own_cars">
                <h2 class="own_cars_title">Ваши автомобили</h2>
                <?php if (!empty($cars)): ?>
                    <?php foreach ($cars as $car): ?>
                        <div class="car_card">
                            <p><b><?php echo htmlspecialchars($car['Марка_автомобиля']) . " " . htmlspecialchars($car['Модель_автомобиля']) . " (" . htmlspecialchars($car['Год_выпуска']) . ")"; ?></b></p>
                            <p>Регистрационный номер: <b><?php echo htmlspecialchars($car['Регистрационный_номер']); ?></b></p>
                            <form method="post" style="display: inline;" onsubmit="return confirmDelete()">
                                <input type="hidden" name="vin_number" value="<?php echo htmlspecialchars($car['VIN_номер']); ?>">
                                <button type="submit" name="delete_car" class="delete_button">Удалить</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>У вас нет добавленных автомобилей.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script>
        // Функция для подтверждения удаления
        function confirmDelete() {
            return confirm("Вы уверены, что хотите удалить этот автомобиль?");
        }

        document.getElementById('exit_button').addEventListener('click', function() {
            document.cookie = "user_name=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            window.location.href = "index.html";
        });
        document.getElementById('new_order_button').addEventListener('click', function() {
            window.location.href = "new_order.php";
        });
        document.getElementById('new_car_button').addEventListener('click', function() {
            window.location.href = "new_car.php";
        });
    </script>
</body>
</html>