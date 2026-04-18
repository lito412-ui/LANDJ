<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /modules/site/login.html");
    exit();
}

readfile(__DIR__ . '/../modules/dashboard/cpanel.html');
?>