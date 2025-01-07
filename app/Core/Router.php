<?php

require_once __DIR__ . '/../utils/SessionManager.php';
require_once __DIR__ . '/../config/routes.php';

SessionManager::startSession();

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$routesFile = __DIR__ . '/../config/routes.php';
if (!file_exists($routesFile)) {
    die("Error: routes.php file not found!");
}

$routes = require $routesFile;

error_log("Available Routes:");
foreach ($routes as $key => $route) {
    error_log("Route Loaded: $key");
}


function matchRoute($method, $path, $routes) {
    foreach ($routes as $routeKey => $route) {
        [$routeMethod, $routePath] = explode('|', $routeKey);

        
        if ($method !== $routeMethod) {
            continue;
        }

        
        $routeRegex = preg_replace('/\{[^\}]+\}/', '([^/]+)', $routePath);

       
        if (preg_match('#^' . $routeRegex . '$#', $path, $matches)) {
            
            array_shift($matches);
            return [$route, $matches];
        }
    }

   
    return [null, []];
}


[$matchedRoute, $routeParams] = matchRoute($method, $path, $routes);

if (!$matchedRoute) {
    error_log("Route Missing: {$method}|{$path}");
    echo "Error: Route not found! ({$method}|{$path})";
    exit;
}


$controllerName = $matchedRoute['controller'];
$actionName = $matchedRoute['action'];
$middleware = $matchedRoute['middleware'] ?? [];


if (in_array('auth', $middleware) && !SessionManager::isAuthenticated()) {
    header('Location: /login');
    exit();
}

if (in_array('admin', $middleware) && !SessionManager::isAdmin()) {
    header('Location: /');
    exit();
}
if (in_array('doctor', $middleware) && !SessionManager::isDoctor()) {
    header('Location: /');
    exit();
}
if (in_array('patient', $middleware) && !SessionManager::isPatient()) {
    header('Location: /');
    exit();
}


$controllerFile = __DIR__ . "/../controller/{$controllerName}.php";
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $controller = new $controllerName();

    if (method_exists($controller, $actionName)) {
        
        call_user_func_array([$controller, $actionName], $routeParams);
    } else {
        echo "Error: Action not found!";
    }
} else {
    echo "Error: Controller not found!";
}

error_log('Session user_id: ' . SessionManager::get('user_id'));

?>
