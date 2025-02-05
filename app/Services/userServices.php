<?php
namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;

class UserService {
    private $userRepository;

    public function __construct(UserRepository $userRepository) {
        $this->userRepository = $userRepository;
    }

    public function registerUser($firstName, $lastName, $email, $password, $role): User {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email address.");
        }
        if ($this->userRepository->findByEmail($email)) {
            throw new RuntimeException("User already exists.");
        }
        $user = new User(null, $firstName, $lastName, $email, $password, $role);
        $this->userRepository->save($user);
        return $user;
    }

    public function loginUser($email, $password): ?User {
        $user = $this->userRepository->findByEmail($email);
        if ($user && password_verify($password, $user->getPassword())) {
            return $user;
        }
        return null;
    }
}