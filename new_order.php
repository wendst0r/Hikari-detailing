<?php
require 'admin/db.php';
$error = '';

if (!isset($_COOKIE['email'])) {
    $error = 'Пользователь не авторизован.';
} else {
    try {
        // Получаем автомобили клиента
        $sql = "SELECT a.\"Марка_автомобиля\",  a.\"Модель_автомобиля\", a.\"Год_выпуска\", a.\"Регистрационный_номер\", a.\"VIN_номер\"
                FROM \"Автомобиль_клиента\" AS ac
                JOIN \"Автомобиль\" AS a
                ON ac.\"VIN_номер\" = a.\"VIN_номер\" 
                WHERE ac.\"Электронная_почта\" = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $_COOKIE['email']]);
        $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Получаем список услуг
        $sqlServices = "SELECT * FROM \"Услуга\"";
        $stmtServices = $pdo->query($sqlServices);
        $services = $stmtServices->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_COOKIE['email'])) {
        $error = 'Пользователь не авторизован.';
    } else {
        try {
            // Получаем данные из формы
            $vin_number = $_POST['vin_number'];
            $total_cost = $_POST['total_cost'];
            $payment_method = $_POST['payment_method'];
            $selected_services = $_POST['selected_services'] ?? [];

            // Вставляем данные в таблицу "Заказ"
            $sqlOrder = "INSERT INTO \"Заказ\" (\"Электронная_почта\", \"VIN_номер\", \"Итоговая_стоимость\", \"Способ_оплаты\")
                         VALUES (:email, :vin_number, :total_cost, :payment_method) RETURNING \"ИД_Заказа\"";
            $stmtOrder = $pdo->prepare($sqlOrder);
            $stmtOrder->execute([
                'email' => $_COOKIE['email'],
                'vin_number' => $vin_number,
                'total_cost' => $total_cost,
                'payment_method' => $payment_method
            ]);
            $order_id = $stmtOrder->fetchColumn();

            // Вставляем данные в таблицу "Состав_заказа"
            if (!empty($selected_services)) {
                $sqlOrderComposition = "INSERT INTO \"Состав_заказа\" (\"Название_услуги\", \"ИД_Заказа\")
                                        VALUES (:service_name, :order_id)";
                $stmtOrderComposition = $pdo->prepare($sqlOrderComposition);
                foreach ($selected_services as $service_name) {
                    $stmtOrderComposition->execute([
                        'service_name' => $service_name,
                        'order_id' => $order_id
                    ]);
                }
            }

            // Перенаправляем пользователя на страницу успешного оформления заказа
            header('Location: success_order_add.php');
            exit();
        } catch (PDOException $e) {
            $error = 'Ошибка при оформлении заказа: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить автомобиль</title>
    <link rel="stylesheet" href="new_form.css">
    <link rel="stylesheet" href="globals.css">
</head>
<body>
    <main>
        <form action="new_order.php" method="post">
            <img src="Pictures/order-icon.png">
            <h1>Офорление заказа</h1>
            <div class="car_select">
                <img src="Pictures/prew_arrow.png" id="prew_item" class="arrow">
                <div class="car_item_frame">
                    <?php if (!empty($cars)): ?>
                        <?php foreach ($cars as $index => $car): ?>
                            <div class="car <?php echo $index === 0 ? 'active' : ''; ?>" id="car_<?php echo $index; ?>" data-vin="<?php echo htmlspecialchars($car['VIN_номер']); ?>">
                                <p><?php echo htmlspecialchars($car['Марка_автомобиля']) . " " . htmlspecialchars($car['Модель_автомобиля']) . " (" . htmlspecialchars($car['Год_выпуска']) . ")"; ?></p>
                                <p>Регистрационный номер: <?php echo htmlspecialchars($car['Регистрационный_номер']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Автомобили не найдены.</p>
                    <?php endif; ?>
                    <div class="add_car_item <?php echo empty($cars) ? 'active' : ''; ?>" id="add_car_item">
                        <p>Добавить автомобиль</p>
                        <img src="Pictures/+.png">
                    </div>
                </div>
                <img src="Pictures/next_arrow.png" id="next_item" class="arrow">
            </div>

            <!-- Скрытое поле для передачи VIN-номера -->
            <input type="hidden" name="vin_number" id="vin_number" value="<?php echo !empty($cars) ? htmlspecialchars($cars[0]['VIN_номер']) : ''; ?>">

            <!-- Таблица с услугами -->
            <table>
                <thead>
                    <tr>
                        <th>Услуга</th>
                        <th>Тип услуги</th>
                        <th>Стоимость</th>
                        <th>Выбрать</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($services)): ?>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($service['Название_услуги']); ?></td>
                                <td><?php echo htmlspecialchars($service['Тип_услуги']); ?></td>
                                <td><?php echo htmlspecialchars($service['Стоимость_услуги']); ?> руб.</td>
                                <td><input type="checkbox" class="service-checkbox" data-cost="<?php echo htmlspecialchars($service['Стоимость_услуги']); ?>" name="selected_services[]" value="<?php echo htmlspecialchars($service['Название_услуги']); ?>"></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">Услуги не найдены.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="total-cost">
                Итоговая стоимость: <span id="totalCost">0</span> руб.
                <!-- Скрытый элемент для передачи итоговой стоимости -->
                <input type="hidden" name="total_cost" id="totalCostHidden" value="0">
            </div>

            <div class="payment-method">
                <label>
                    <input type="radio" name="payment_method" value="Карта" checked>
                    Карта
                </label>
                <label>
                    <input type="radio" name="payment_method" value="Наличные">
                    Наличные
                </label>
            </div>

            <?php if (!empty($error)): ?>
                <p style="color: red; text-align: center;"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <button type="submit">Сохранить</button>
        </form>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const items = document.querySelectorAll('.car, .add_car_item'); // Все элементы, включая add_car_item
            const prevButton = document.getElementById('prew_item');
            const nextButton = document.getElementById('next_item');
            const vinNumberInput = document.getElementById('vin_number'); // Скрытое поле для VIN-номера
            let currentIndex = 0;

            // Функция для показа текущего элемента
            function showItem(index) {
                items.forEach((item, i) => {
                    if (i === index) {
                        item.classList.add('active');
                        // Обновляем значение скрытого поля vin_number
                        if (item.classList.contains('car')) {
                            vinNumberInput.value = item.getAttribute('data-vin');
                        }
                    } else {
                        item.classList.remove('active');
                    }
                });
            }

            // Обработчик для кнопки "Назад"
            prevButton.addEventListener('click', function() {
                if (currentIndex > 0) {
                    currentIndex--;
                } else {
                    currentIndex = items.length - 1; // Переход к последнему элементу
                }
                showItem(currentIndex);
            });

            // Обработчик для кнопки "Вперёд"
            nextButton.addEventListener('click', function() {
                if (currentIndex < items.length - 1) {
                    currentIndex++;
                } else {
                    currentIndex = 0; // Переход к первому элементу
                }
                showItem(currentIndex);
            });

            // Обработчик для блока "Добавить автомобиль"
            document.querySelector('.car_item_frame').addEventListener('click', function(event) {
                if (event.target.closest('.add_car_item')) {
                    window.location.href = 'new_car.php';
                }
            });

            // Логика расчёта итоговой стоимости
            const checkboxes = document.querySelectorAll('.service-checkbox');
            const totalCostElement = document.getElementById('totalCost');

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateTotalCost);
            });

            function updateTotalCost() {
                let total = 0;
                checkboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        total += parseFloat(checkbox.getAttribute('data-cost'));
                    }
                });
                totalCostElement.textContent = total.toFixed(2); // Округляем до 2 знаков после запятой
                document.getElementById('totalCostHidden').value = total;
            }

            // Показываем первый элемент при загрузке
            showItem(currentIndex);
        });
    </script>
</body>
</html>