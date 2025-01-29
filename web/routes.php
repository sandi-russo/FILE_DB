<?php
ob_start();
require_once __DIR__ . '/controllers/auth_controller.php';
require_once __DIR__ . '/helpers/messages.php';

$authController = new AuthController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';
    $data = $_POST;
    $response = null;

    switch ($action) {
        case 'register':
            $response = $authController->register($data);
            break;
        case 'login':
            $response = $authController->login($data);
            break;
        case 'logout':
            $response = $authController->logout();
            break;
        case 'approve':
            $jsonData = json_decode(file_get_contents('php://input'), true);
            $response = $authController->approveUser(
                $jsonData['cf'],
                $jsonData['email'],
                $jsonData['type'],
                $_SESSION['user_id'],
                $jsonData
            );
            break;
        default:
            http_response_code(400);
            $response = ["message" => "Azione non valida.", "status" => "error"];
    }

    ob_clean();
    // header('Content-Type: application/json');
    echo json_encode($response);

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['action'] === 'logout') {
    $response = $authController->logout();
    ob_clean();
    //  header('Content-Type: application/json');
    echo json_encode($response);
} else {
    http_response_code(405);
    ob_clean();
    // header('Content-Type: application/json');
    echo json_encode(["message" => "Metodo non consentito.", "status" => "error"]);
}

ob_end_flush();
exit;