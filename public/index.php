<?php
session_start();
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Finance.php';
$action = $_GET['action'] ?? 'login';
switch ($action) {
    case 'register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            User::register($_POST['username'], $_POST['password']);
            header('Location: index.php?action=login');
            exit;
        }
        include '../src/views/register.php';
        break;
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = User::login($_POST['username'], $_POST['password']);
            if ($user) {
                $_SESSION['user'] = $user;
                header('Location: index.php?action=dashboard');
                exit;
            } else {
                $error = "Invalid login.";
            }
        }
        include '../src/views/login.php';
        break;
    case 'logout':
        session_destroy();
        header('Location: index.php');
        break;
    case 'finance':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Finance::save($_SESSION['user']['id'], $_POST['month'], $_POST['budget'], $_POST['expenses']);
        }
        header('Location: index.php?action=dashboard');
        break;
    case 'dashboard':
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?action=login');
            exit;
        }
        $data = Finance::getByUser($_SESSION['user']['id']);
        include '../src/views/dashboard.php';
        break;
    default:
        header('Location: index.php?action=login');
}
?>