<?php
session_start();
require_once __DIR__ . '/../models/Finance.php';
if (!isset($_SESSION['user'])) {
    header("Location: /login.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $month = $_POST['month'];
    $income = $_POST['income'];
    $expenses = $_POST['expenses'];
    $recurring = isset($_POST['is_recurring']) ? 1 : 0;
    Finance::save($_SESSION['user']['id'], $month, $income, $expenses, $recurring);
    header("Location: /index.php");
    exit;
}
?>