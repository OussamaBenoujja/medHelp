<?php
namespace App\Controllers;

use App\Services\UserService;

class UserController {
    private $userService;

    public function __construct(UserService $userService) {
        $this->userService = $userService;
    }

    public function register($firstName, $lastName, $email, $password, $role) {
        try {
            $user = $this->userService->registerUser($firstName, $lastName, $email, $password, $role);
            echo "User registered successfully: " . $user->getFirstName();
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function login($email, $password) {
        $user = $this->userService->loginUser($email, $password);
        if ($user) {
            echo "Login successful: " . $user->getFirstName();
        } else {
            echo "Invalid credentials.";
        }
    }
}