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
            echo showSweetAlert('Welcome', "Register successful: " . $user->getFirstName(), 'success');
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function login($email, $password) {
        $user = $this->userService->loginUser($email, $password);
        if ($user) {
            echo showSweetAlert('Welcome', "Login successful: " . $user->getFirstName(), 'success');
        } else {
            echo showSweetAlert('Error', "Invalid Credentials", 'error');
        }
    }


    function showSweetAlert($title, $text, $icon = 'success') {
        return <<<EOT
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        Swal.fire('{$title}', '{$text}', '{$icon}');
    </script>
    EOT;
    }
}