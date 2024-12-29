<?php
// Параметры подключения к базе данных
$db_host = "db"; // Хост базы данных
$db_user = "admin"; // Имя пользователя
$db_pass = "qwerty"; // Пароль
$db_base = "hikari\ datailing"; // Имя базы данных
$db_port = 5432; // Порт базы данных


try {
    // Подключение к базе данных с использованием PDO
    $pdo = new PDO("pgsql:host=$db_host;port=$db_port;dbname=$db_base;user=$db_user;password=$db_pass");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>