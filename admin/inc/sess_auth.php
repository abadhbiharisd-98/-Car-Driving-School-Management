<?php 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$proto = (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
         (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = !empty($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : 
       (!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
$req_uri = $_SERVER['REQUEST_URI'] ?? '';
$link = $proto . "://" . $host . $req_uri;

if(!isset($_SESSION['userdata']) && !strpos($link, 'login.php') && !strpos($link, 'register.php')){
	redirect('admin/login.php');
}
if(isset($_SESSION['userdata']) && strpos($link, 'login.php')){
	redirect('admin/index.php');
}
$module = array('','admin','faculty','student');
if(isset($_SESSION['userdata']) && (strpos($link, 'index.php') || strpos($link, 'admin/')) && (!isset($_SESSION['userdata']['login_type']) || $_SESSION['userdata']['login_type'] !=  1)){
	echo "<script>alert('Access Denied!');location.replace('".base_url."admin/login.php');</script>";
    exit;
}
