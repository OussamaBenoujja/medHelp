<?php 
namespace App\Core;

use App\Controllers\UserController;
use App\Services\UserService;
use App\Repositories\UserRepository;

class Router {
    
    private $pdo; 

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function route() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        try {
            switch ($uri) {
                case '/register':
                    if ($method === 'POST') {
                        $userRepository = new UserRepository($this->pdo);
                        $userService = new UserService($userRepository);
                        $controller = new UserController($userService);
                        
                        $controller->register(
                            $_POST['firstName'] ?? '', 
                            $_POST['lastName'] ?? '', 
                            $_POST['email'] ?? '', 
                            $_POST['password'] ?? '', 
                            $_POST['role'] ?? ''
                        );
                    } else {
                        http_response_code(405);
                        echo "Method Not Allowed";
                    }
                    break;
                default:
                    http_response_code(404);
                    echo "Page not found";
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo "Server error: " . $e->getMessage();
        }
    }
}