<?php
session_start();
unset($_SESSION['user']);
unset($_COOKIE['vacationrentals_login_cookie']);
setcookie('vacationrentals_login_cookie', null, -1, '/'); 
header("Location: index.php"); 
exit;
?>