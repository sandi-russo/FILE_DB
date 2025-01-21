<?php

require_once './config/constants.php';
require_once CONTROLLERS_DIR . '/auth_controller.php';
require_once HELPERS_DIR . '/messages.php';

$authController = new AuthController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'register':
            // Utilizzo le chiavi valori del file "register.php"
            $authController->register($_POST['nome'], $_POST['cognome'], $_POST['email'], $_POST['password']);
            break;
        case 'login':
            $authController->login($_POST['email'], $_POST['password']);
            break;
        default:
            echo showMessage("Azione non valida.", 'danger');
    }
} elseif ($_GET['action'] === 'logout') {
    $authController->logout();
}