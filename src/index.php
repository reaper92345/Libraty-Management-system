<?php

require_once __DIR__ . '/controllers/BookController.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/BookCopyController.php';
require_once __DIR__ . '/controllers/TransactionController.php';
require_once __DIR__ . '/controllers/ReservationController.php';
require_once __DIR__ . '/controllers/ReportController.php';

// Simple routing
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_segments = explode('/', trim($path, '/'));

// Assuming the API base path is /api
if (isset($path_segments[0]) && $path_segments[0] === 'api') {
    if (isset($path_segments[1])) {
        $resource = $path_segments[1];

        switch ($resource) {
            case 'books':
                $controller = new BookController();
                $controller->handleRequest();
                break;
            case 'users':
                $controller = new UserController();
                $controller->handleRequest();
                break;
            case 'bookcopies':
                $controller = new BookCopyController();
                $controller->handleRequest();
                break;
            case 'transactions':
                $controller = new TransactionController();
                $controller->handleRequest();
                break;
            case 'reservations':
                $controller = new ReservationController();
                $controller->handleRequest();
                break;
            case 'reports':
                $controller = new ReportController();
                $controller->handleRequest();
                break;
            // Add other resources (users, transactions) here later
            default:
                http_response_code(404);
                echo json_encode(array("message" => "Resource not found."));
                break;
        }
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "No resource specified."));
    }
} else {
    // Serve frontend or a welcome page
    echo "Welcome to the Kibrary API!";
    // For a full frontend, you would typically serve an index.html here
}
?>
