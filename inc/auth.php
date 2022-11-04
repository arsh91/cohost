<?php
include 'inc/checkCookies.php';
if(isset($_SESSION) && empty($_SESSION['user'])) {
	unset($_SESSION['user']);
	unset($_COOKIE['vacationrentals_login_cookie']);
	header("Location: index.php"); 
	exit;
}
?>