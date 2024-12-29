<?php
// Подключение к базе данных
require 'admin/db.php';

// Список таблиц, которые нужно оставить
$allowed_tables = ['Заказ', 'Рекламация'];

$selected_table = null;
$orders_list = [];
$order_details = [];
$materials_by_service = []; // Группировка материалов по услугам
$column_names = [];
$archive_orders = []; // Добавлено для архива заказов

// Функция для форматирования даты
function formatDate($dateString) {
    if (empty($dateString)) {
        return ''; // Возвращаем пустую строку, если дата не передана
    }
    try {
        $date = new DateTime($dateString);
        return $date->format('H:i d.m.y'); // Формат: 20:52 28.12.24
    } catch (Exception $e) {
        return 'Некорректная дата'; // Обработка ошибок, если дата в неправильном формате
    }
}

// Функция для обновления статуса заказа
function updateOrderStatus($orderId, $pdo) {
    // Получаем все процедуры для заказа
    $sql_procedures = "
        SELECT Процедура.Название_процедуры, Работа_мастера.datatime_выполнения_процедуры
        FROM Состав_услуги
        JOIN Процедура ON Состав_услуги.Название_процедуры = Процедура.Название_процедуры
        LEFT JOIN Работа_мастера ON Процедура.Название_процедуры = Работа_мастера.Название_процедуры AND Работа_мастера.ИД_Заказа = :order_id
        WHERE Состав_услуги.Название_услуги IN (
            SELECT Название_услуги FROM Состав_заказа WHERE ИД_Заказа = :order_id
        )
    ";
    $stmt_procedures = $pdo->prepare($sql_procedures);
    $stmt_procedures->execute(['order_id' => $orderId]);
    $procedures = $stmt_procedures->fetchAll(PDO::FETCH_ASSOC);

    // Считаем общее количество процедур и количество выполненных процедур
    $totalProcedures = count($procedures);
    $completedProcedures = 0;

    foreach ($procedures as $procedure) {
        if (!empty($procedure['datatime_выполнения_процедуры'])) {
            $completedProcedures++;
        }
    }

    // Определяем новый статус
    if ($completedProcedures == 0) {
        $newStatus = 'Ожидается'; // Если ни одна процедура не выполнена
    } elseif ($completedProcedures < $totalProcedures) {
        $newStatus = 'Выполняется'; // Если выполнена хотя бы одна, но не все
    } else {
        $newStatus = 'Завершен'; // Если все процедуры выполнены
    }

    // Обновляем статус заказа
    $sql_update_status = "UPDATE Заказ SET Текущий_статус_заказа = :status WHERE ИД_Заказа = :order_id";
    $stmt_update_status = $pdo->prepare($sql_update_status);
    $stmt_update_status->execute(['status' => $newStatus, 'order_id' => $orderId]);
}

// Обработка удаления рекламации
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $sql_delete = "DELETE FROM Рекламация WHERE ИД_Заказа = :delete_id";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->execute(['delete_id' => $delete_id]);

    // Перенаправление на страницу с таблицей "Рекламация"
    header("Location: ?table=Рекламация");
    exit();
}

// Обработка назначения мастера на процедуру
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_master'])) {
    $procedure_name = $_POST['procedure_name'];
    $order_id = $_POST['order_id'];
    $master_id = $_POST['master_id'];

    // Добавляем запись в таблицу Работа_мастера
    $sql_assign = "INSERT INTO Работа_мастера (Название_процедуры, ИД_Заказа, Табельный_номер_мастера, datatime_выполнения_процедуры) VALUES (:procedure_name, :order_id, :master_id, NOW())";
    $stmt_assign = $pdo->prepare($sql_assign);
    $stmt_assign->execute([
        'procedure_name' => $procedure_name,
        'order_id' => $order_id,
        'master_id' => $master_id
    ]);

    // Обновляем статус заказа
    updateOrderStatus($order_id, $pdo);

    // Перенаправление на страницу с деталями заказа
    header("Location: ?table=Заказ&order_id=$order_id");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Если запрошен архив заказов
    if (isset($_GET['archive'])) {
        // Запрос для получения заказов со статусом "Завершен"
        $sql_archive = "SELECT * FROM Заказ WHERE Текущий_статус_заказа = 'Завершен'";
        $stmt_archive = $pdo->query($sql_archive);
        $archive_orders = $stmt_archive->fetchAll(PDO::FETCH_ASSOC);
    } elseif (isset($_GET['table'])) {
        $selected_table = $_GET['table'];

        // Проверяем, что выбранная таблица разрешена
        if (in_array($selected_table, $allowed_tables)) {
            // Если выбрана таблица "Заказ"
            if ($selected_table === 'Заказ') {
                // Запрос для получения списка всех заказов, кроме завершенных
                $sql_orders = "SELECT * FROM Заказ WHERE Текущий_статус_заказа != 'Завершен'";
                $stmt_orders = $pdo->query($sql_orders);
                $orders_list = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

                // Если передан order_id, получаем детали заказа
                if (isset($_GET['order_id'])) {
                    $order_id = $_GET['order_id'];

                    // Обновляем статус заказа
                    updateOrderStatus($order_id, $pdo);

                    // Запрос для получения данных о заказе, клиенте, автомобиле, услугах, процедурах и мастерах
                    $sql_details = "
                        SELECT 
                            Клиент.Фамилия_клиента, 
                            Клиент.Имя_клиента, 
                            Клиент.Отчество_клиента, 
                            Клиент.Номер_телефона_клиента, 
                            Автомобиль.Марка_автомобиля, 
                            Автомобиль.Модель_автомобиля, 
                            Автомобиль.Год_выпуска, 
                            Услуга.Название_услуги, 
                            Процедура.Название_процедуры, 
                            Мастер.Фамилия_мастера, 
                            Мастер.Имя_мастера, 
                            Мастер.Отчество_мастера, 
                            Работа_мастера.datatime_выполнения_процедуры,
                            Материал.Название_материала, 
                            Материалы_для_услуги.Количество_материала
                        FROM 
                            Заказ
                        JOIN 
                            Клиент ON Заказ.Электронная_почта = Клиент.Электронная_почта
                        JOIN 
                            Автомобиль ON Заказ.\"VIN_номер\" = Автомобиль.\"VIN_номер\"
                        JOIN 
                            Состав_заказа ON Заказ.ИД_Заказа = Состав_заказа.ИД_Заказа
                        JOIN 
                            Услуга ON Состав_заказа.Название_услуги = Услуга.Название_услуги
                        LEFT JOIN 
                            Состав_услуги ON Услуга.Название_услуги = Состав_услуги.Название_услуги
                        LEFT JOIN 
                            Процедура ON Состав_услуги.Название_процедуры = Процедура.Название_процедуры
                        LEFT JOIN 
                            Работа_мастера ON Процедура.Название_процедуры = Работа_мастера.Название_процедуры AND Заказ.ИД_Заказа = Работа_мастера.ИД_Заказа
                        LEFT JOIN 
                            Мастер ON Работа_мастера.Табельный_номер_мастера = Мастер.Табельный_номер_мастера
                        LEFT JOIN 
                            Материалы_для_услуги ON Услуга.Название_услуги = Материалы_для_услуги.Название_услуги
                        LEFT JOIN 
                            Материал ON Материалы_для_услуги.Название_материала = Материал.Название_материала
                        WHERE 
                            Заказ.ИД_Заказа = :order_id
                    ";
                    $stmt_details = $pdo->prepare($sql_details);
                    $stmt_details->execute(['order_id' => $order_id]);
                    $order_details = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

                    // Группировка материалов по услугам
                    $materials_by_service = [];
                    foreach ($order_details as $row) {
                        $service_name = $row['Название_услуги'];
                        $material_name = $row['Название_материала'];
                        $material_quantity = $row['Количество_материала'];

                        if (!isset($materials_by_service[$service_name])) {
                            $materials_by_service[$service_name] = [];
                        }

                        if (!isset($materials_by_service[$service_name][$material_name])) {
                            $materials_by_service[$service_name][$material_name] = $material_quantity;
                        }
                    }
                }
            } elseif ($selected_table === 'Рекламация') {
                // Запрос для получения данных из таблицы "Рекламация"
                $sql_data = "SELECT * FROM Рекламация";
                $stmt_data = $pdo->query($sql_data);
                $orders_list = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

                // Получаем названия колонок для таблицы "Рекламация"
                $sql_columns = "SELECT column_name FROM information_schema.columns WHERE table_name = 'Рекламация'";
                $stmt_columns = $pdo->query($sql_columns);
                $column_names = $stmt_columns->fetchAll(PDO::FETCH_COLUMN);
            }
        }
    }
}
?>