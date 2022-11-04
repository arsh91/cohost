<?php
session_start();
include_once 'inc/db.php';

$dbhost = 'localhost';
$dbuser = 'equisngs_website';
$dbpass = 'o8(p=lf!Qx2U';
$dbname = 'equisngs_vacationrentals';
$db = new db($dbhost, $dbuser, $dbpass, $dbname);
?>