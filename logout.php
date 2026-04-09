<?php 
include "config.php";
// Destroy session properly
$_SESSION = [];
session_destroy();
header("Location: login.php");
exit();
?>