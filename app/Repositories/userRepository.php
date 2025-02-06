<?php

class UserRepo{

    protected $pdo;
    protected $user;

    public function __construct(PDO $pdo, User $user){
        $this->pdo = $pdo;
        $this->user = $user;
    }

    public function createUser(){
        $stmt = $this->pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (:first_name, :last_name, :email, :password, :role)");
        $stmt->execute([
            'first_name' => $this->user->getFirstName(),
            'last_name' => $this->user->getLastName(),
            'email' => $this->user->getEmail(),
            'password' => $this->user->getPassword(),
            'role' => $this->user->getRole()
        ]);
    }

    public function deleteUser(){
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute(['id' => $this->user->getId()]);
    }

    public function login($email, $password){
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email AND password = :password");
        $stmt->execute(['email' => $email, 'password' => $password]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if($data){
            $this->user->setId($data['id']);
            $this->user->setFirstName($data['first_name']);
            $this->user->setLastName($data['last_name']);
            $this->user->setEmail($data['email']);
            $this->user->setPassword($data['password']);
            $this->user->setRole($data['role']);
            return $this->user;
        }else{
            return null;
        }
    }

}


?>