<?php
require 'admin/phpshka.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список таблиц и их данные</title>
    <link href="https://fonts.googleapis.com/css2?family=Tektur&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin/ad.css">
    <script>
        function showMasterList(event, procedureName, orderId) {
            console.log("Функция showMasterList вызвана"); // Отладка
            console.log("Процедура:", procedureName); // Отладка
            console.log("ID заказа:", orderId); // Отладка

            // Предотвращаем стандартное поведение формы (перезагрузку страницы)
            event.preventDefault();

            // Находим выпадающий список для данной процедуры
            var dropdown = document.getElementById('master-dropdown-' + procedureName);
            if (!dropdown) {
                console.error("Выпадающий список не найден"); // Отладка
                return;
            }

            // Переключаем видимость выпадающего списка
            if (dropdown.style.display === 'block') {
                dropdown.style.display = 'none';
            } else {
                // Скрываем все другие выпадающие списки
                document.querySelectorAll('.dropdown-content').forEach(function (otherDropdown) {
                    otherDropdown.style.display = 'none';
                });
                dropdown.style.display = 'block';
            }

            // Обработка выбора мастера через делегирование событий
            dropdown.addEventListener('click', function (event) {
                if (event.target.classList.contains('master-option')) {
                    console.log("Клик на мастера зарегистрирован"); // Отладка
                    var masterId = event.target.getAttribute('data-master-id');
                    console.log("Выбран мастер с ID:", masterId); // Отладка

                    // Создаем форму для отправки данных
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '?table=Заказ&order_id=' + orderId;

                    // Добавляем скрытые поля
                    var inputMasterId = document.createElement('input');
                    inputMasterId.type = 'hidden';
                    inputMasterId.name = 'master_id';
                    inputMasterId.value = masterId;

                    var inputProcedureName = document.createElement('input');
                    inputProcedureName.type = 'hidden';
                    inputProcedureName.name = 'procedure_name';
                    inputProcedureName.value = procedureName;

                    var inputOrderId = document.createElement('input');
                    inputOrderId.type = 'hidden';
                    inputOrderId.name = 'order_id';
                    inputOrderId.value = orderId;

                    var inputAssignMaster = document.createElement('input');
                    inputAssignMaster.type = 'hidden';
                    inputAssignMaster.name = 'assign_master';

                    // Добавляем поля в форму
                    form.appendChild(inputMasterId);
                    form.appendChild(inputProcedureName);
                    form.appendChild(inputOrderId);
                    form.appendChild(inputAssignMaster);

                    // Отправляем форму
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</head>
<body>
    <div class="sidebar">
        <h2>Список таблиц</h2>
        <ul>
            <?php foreach ($allowed_tables as $table): ?>
                <li>
                    <a href="?table=<?php echo urlencode($table); ?>" class="button">
                        <?php echo htmlspecialchars(str_replace('_', ' ', $table)); ?>
                    </a>
                </li>
            <?php endforeach; ?>
            <!-- Кнопка "Архив заказов" -->
            <li>
                <a href="?archive=1" class="button">Архив заказов</a>
            </li>
        </ul>
    </div>

    <div class="content">
        <?php if (isset($_GET['archive'])): ?>
            <!-- Отображение архива заказов -->
            <h2>Архив заказов</h2>
            <div class="bigblack">
                <?php if (!empty($archive_orders)): ?>
                    <table class="equal-columns">
                        <thead>
                            <tr>
                                <th>ID заказа</th>
                                <th>Дата создания</th>
                                <th>Итоговая стоимость</th>
                                <th>Статус заказа</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archive_orders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['ИД_Заказа']); ?></td>
                                    <td><?php echo formatDate($order['datatime_создания_заказа']); ?></td>
                                    <td><?php echo htmlspecialchars($order['Итоговая_стоимость']); ?></td>
                                    <td><?php echo htmlspecialchars($order['Текущий_статус_заказа']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Нет завершенных заказов для отображения.</p>
                <?php endif; ?>
            </div>
        <?php elseif ($selected_table === 'Заказ'): ?>
            <?php if (!isset($_GET['order_id'])): ?>
                <!-- Отображение списка заказов, если order_id не передан -->
                <h2>Список заказов</h2>
                <div class="bigblack">
                    <?php if (!empty($orders_list)): ?>
                        <table class="equal-columns">
                            <thead>
                                <tr>
                                    <th>ID заказа</th>
                                    <th>Дата создания</th>
                                    <th>Итоговая стоимость</th>
                                    <th>Статус заказа</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders_list as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['ИД_Заказа']); ?></td>
                                        <td><?php echo formatDate($order['datatime_создания_заказа']); ?></td>
                                        <td><?php echo htmlspecialchars($order['Итоговая_стоимость']); ?></td>
                                        <td><?php echo htmlspecialchars($order['Текущий_статус_заказа']); ?></td>
                                        <td>
                                            <a href="?table=Заказ&order_id=<?php echo htmlspecialchars($order['ИД_Заказа']); ?>" class="button">Подробнее</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Нет данных для отображения.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Отображение деталей заказа, если order_id передан -->
                <h2>Детали заказа</h2>
                <div class="bigblack">
                    <?php if (!empty($order_details)): ?>
                        <!-- Вывод данных о клиенте и автомобиле -->
                        <h3>Клиент: <?php echo htmlspecialchars($order_details[0]['Фамилия_клиента'] . ' ' . $order_details[0]['Имя_клиента'] . ' ' . $order_details[0]['Отчество_клиента']); ?>
                            <span style="margin-left: 20px;">Телефон: <?php echo htmlspecialchars($order_details[0]['Номер_телефона_клиента']); ?></span>
                        </h3>
                        <h3>Автомобиль: <?php echo htmlspecialchars($order_details[0]['Марка_автомобиля'] . ' ' . $order_details[0]['Модель_автомобиля'] . ' (' . $order_details[0]['Год_выпуска'] . ')'); ?></h3>

                        <!-- Группировка процедур по услугам -->
                        <?php
                        $services_with_procedures = [];
                        foreach ($order_details as $row) {
                            $service_name = $row['Название_услуги'];
                            $procedure_name = $row['Название_процедуры'];
                            $master_name = $row['Фамилия_мастера'] . ' ' . $row['Имя_мастера'] . ' ' . $row['Отчество_мастера'];
                            $procedure_date = $row['datatime_выполнения_процедуры'];

                            if (!isset($services_with_procedures[$service_name])) {
                                $services_with_procedures[$service_name] = [];
                            }
                            $services_with_procedures[$service_name][] = [
                                'procedure_name' => $procedure_name,
                                'master_name' => $master_name,
                                'procedure_date' => $procedure_date,
                            ];
                        }
                        ?>

                        <!-- Вывод данных об услугах и процедурах -->
                        <h3>Услуги и процедуры:</h3>
                        <?php foreach ($services_with_procedures as $service_name => $procedures): ?>
                            <div class="service-block">
                                <h4>Услуга: <?php echo htmlspecialchars($service_name); ?></h4>
                                <table class="equal-columns">
                                    <thead>
                                        <tr>
                                            <th>Процедура</th>
                                            <th>Мастер</th>
                                            <th>Дата выполнения</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($procedures as $procedure): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($procedure['procedure_name']); ?></td>
                                                <td>
                                                    <?php if (empty($procedure['master_name']) || trim($procedure['master_name']) === ''): ?>
                                                        <button type="button" class="button" onclick="showMasterList(event, '<?php echo htmlspecialchars($procedure['procedure_name']); ?>', '<?php echo $order_id; ?>')">Выполнил</button>
                                                        <div id="master-dropdown-<?php echo htmlspecialchars($procedure['procedure_name']); ?>" class="dropdown-content" style="display: none;">
                                                            <?php
                                                            $sql_masters = "
                                                                SELECT Мастер.Табельный_номер_мастера, Мастер.Фамилия_мастера, Мастер.Имя_мастера, Мастер.Отчество_мастера
                                                                FROM Мастер
                                                                JOIN Квалификация ON Мастер.Табельный_номер_мастера = Квалификация.Табельный_номер_мастера
                                                                WHERE Квалификация.Название_процедуры = :procedure_name
                                                            ";
                                                            $stmt_masters = $pdo->prepare($sql_masters);
                                                            $stmt_masters->execute(['procedure_name' => $procedure['procedure_name']]);
                                                            $masters = $stmt_masters->fetchAll(PDO::FETCH_ASSOC);
                                                            ?>
                                                            <?php foreach ($masters as $master): ?>
                                                                <div class="master-option" data-master-id="<?php echo htmlspecialchars($master['Табельный_номер_мастера']); ?>">
                                                                    <?php echo htmlspecialchars($master['Фамилия_мастера'] . ' ' . $master['Имя_мастера'] . ' ' . $master['Отчество_мастера']); ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <?php echo htmlspecialchars($procedure['master_name']); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (empty($procedure['procedure_date'])): ?>
                                                        <!-- Пусто, если процедура не выполнена -->
                                                    <?php else: ?>
                                                        <?php echo formatDate($procedure['procedure_date']); ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>

                        <!-- Вывод данных о материалах -->
                        <?php if (!empty($materials_by_service)): ?>
                            <h3>Материалы:</h3>
                            <?php foreach ($materials_by_service as $service_name => $materials): ?>
                                <div class="service-block">
                                    <h4>Услуга: <?php echo htmlspecialchars($service_name); ?></h4>
                                    <table class="equal-columns">
                                        <thead>
                                            <tr>
                                                <th>Материал</th>
                                                <th>Количество</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($materials as $material_name => $material_quantity): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($material_name); ?></td>
                                                    <td><?php echo htmlspecialchars($material_quantity); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Нет данных о материалах для отображения.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>Нет данных для отображения.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php elseif ($selected_table === 'Рекламация'): ?>
            <!-- Вывод данных для таблицы "Рекламация" -->
            <h2>Данные таблицы: <?php echo htmlspecialchars(str_replace('_', ' ', $selected_table)); ?></h2>
            <div class="bigblack">
                <?php if (!empty($orders_list) && !empty($column_names)): ?>
                    <table class="equal-columns">
                        <thead>
                            <tr>
                                <?php foreach ($column_names as $column): ?>
                                    <th><div class="smallpurple"><?php echo htmlspecialchars(str_replace('_', ' ', $column)); ?></div></th>
                                <?php endforeach; ?>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders_list as $row): ?>
                                <tr>
                                    <?php foreach ($column_names as $column): ?>
                                        <td>
                                            <?php
                                            if (isset($row[$column])) {
                                                echo htmlspecialchars($row[$column]);
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td>
                                        <form method="POST" action="?table=Рекламация" style="display: inline;">
                                            <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($row['ИД_Заказа']); ?>">
                                            <button type="submit" class="button" onclick="return confirm('Вы уверены, что хотите удалить эту рекламацию?');">Удалить</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Нет данных для отображения.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>Выберите таблицу слева, чтобы увидеть её данные.</p>
        <?php endif; ?>
    </div>
</body>
</html>