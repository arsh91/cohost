<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db_connection.php';
$emailindex = base64_decode($_GET['emailindex']);
$currentdatetime=date('Y-m-d H:i:s');
$db->query('UPDATE EmailQueueCohost SET NotificationRead = ? WHERE 	Id = ?', $currentdatetime, $emailindex);
$URL = $db->query('SELECT AirbnbLink FROM EmailQueueCohost WHERE Id = ?', $emailindex)->fetchArray();
header("Location: ".$URL['AirbnbLink']);
exit();
?>