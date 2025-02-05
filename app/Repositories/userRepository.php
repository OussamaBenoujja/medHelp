<?php
namespace App\Repositories;

use PDO;
use App\Models\User;

class UserRepository {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function findById($id): ?User {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) return null;
        return new User($data['id'], $data['first_name'], $data['last_name'], $data['email'], '', $data['role']);
    }

    public function findByEmail($email): ?User {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) return null;
        return new User($data['id'], $data['first_name'], $data['last_name'], $data['email'], '', $data['role']);
    }

    public function save(User $user): void {
        $stmt = $this->pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmail(),
            password_hash($user->getPassword(), PASSWORD_DEFAULT),
            $user->getRole()
        ]);
    }
}