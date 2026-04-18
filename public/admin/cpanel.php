<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /modules/site/login.html");
    exit();
}

include __DIR__ . '/../modules/dashboard/cpanel.php';
