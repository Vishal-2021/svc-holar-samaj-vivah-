<?php
// Include necessary files
require_once $_SERVER['DOCUMENT_ROOT'] . '/api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/api/config/Auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/api/controllers/UserController.php';


// -- CORS Headers --
$allowedOrigins = [
    'https://www.holarsamaj.in',
    'https://holarsamaj.in',
    'http://localhost:4200'
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');




// Handle OPTIONS request (Preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    // Respond with a 200 status code and end the request for preflight.
    http_response_code(200);
    exit;
}
// Get the HTTP method, path, and request body
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestBody = file_get_contents('php://input');

// Remove the base path (to map the path correctly after "/api/")
$path = str_replace('/api', '', $path);

// Log the path for debugging purposes (you can check logs)
error_log("Request Method: $method");
error_log("Request Path: $path");



// Create a database connection
$database = new Database();
$db = $database->getConnection();

if ($path === '/user/register' || $path === '/user/login') {
    // No authentication required for registration and login
} else {
    // Authenticate token for other routes
    $auth = new Auth($db);
    $auth->authenticate();
}



// --- Basic routing ---
switch ($method) {
    case 'POST':

        if ($path === '/user/register') {
            $userController = new UserController($db);
            $userController->register($requestBody);
        }

        if ($path === '/user/login') {
            $userController = new UserController($db);
            $userController->login($requestBody);
        }

        if ($path === '/user/profile') {
            $userController = new UserController($db);
            $userController->CreateProfile($requestBody);
        }

        if ($path === '/user/profile/photo') {
            $userController = new UserController($db);
            $userController->UploadProfilePhoto(); // no neet to pass post file variable that is global
        }

        if ($path === '/user/search') {
            $userController = new UserController($db);
            $userController->searchProfiles($requestBody);
        }
        if ($path === '/messages') {
            $userController = new UserController($db);
            $userController->sendMessage($requestBody);
        }
        break;

    case 'GET':

        if ($path === '/user/profile') {
            if (isset($_GET['id'])) {
                $userId = $_GET['id'];
                $userController = new UserController($db);
                $userController->getUserProfile($userId);
            }
        }

        if ($path === '/interests') {
            if (isset($_GET['id']) && isset($_GET['status']) && isset($_GET['usertype'])) {
                $userId = $_GET['id'];
                $status = $_GET['status'];
                $userType = $_GET['usertype'];
                $userController = new UserController($db);
                $userController->getInterests($userId, $status, $userType);
            }
        }




        if (preg_match('#^/messages/([0-9]+)/([0-9]+)/?$#', $path, $matches)) {

            $senderUserId   = (int) $matches[1];
            $receiverUserId = (int) $matches[2];

            $userController = new UserController($db);
            $userController->getConversation($senderUserId, $receiverUserId);
        }




        break;

    case 'PUT':

        if (preg_match('#^/interests/([0-9]+)$#', $path, $matches)) {

            $interestId = (int) $matches[1];

            $userController = new UserController($db);
            $userController->updateInterest($interestId, $requestBody);
        }
        break;

    default:
        echo json_encode(['message' => 'Method not allowed']);
        break;
}
