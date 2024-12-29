<?
$db_host = "db";
$db_user = "admin";
$db_pass = "qwerty";
$db_base = "'hikari datailing'";
$db_port = 5432;

$db_conn_str = "host=".$db_host." port=".$db_port." dbname=".$db_base." user=".$db_user." password=".$db_pass." options='--client_encoding=UTF8'";
//echo $db_conn_str;

$db_conn = pg_connect($db_conn_str) or die('Не удалось соединиться: ' . pg_last_error());
$query = 'SELECT * FROM Клиент';
$result = pg_query($db_conn, $query) or die("Ошибка запроса:  ".pg_last_error());
var_dump($result);
?>
