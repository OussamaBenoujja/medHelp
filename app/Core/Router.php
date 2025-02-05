<?php
namespace App\Core;

class Router {
    public function route() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        switch ($uri) {
            case '/register':
                require_once __DIR__ . '/../Controllers/UserController.php';
                $controller = new \App\Controllers\UserController(new \App\Services\UserService(new \App\Repositories\UserRepository($pdo)));
                $controller->register($_POST['firstName'], $_POST['lastName'], $_POST['email'], $_POST['password'], $_POST['role']);
                break;
            default:
                echo "Page not found.";
        }
    }
}