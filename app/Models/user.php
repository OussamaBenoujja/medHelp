<?php
namespace App\Models;

class User {

    private $id;
    private $firstName;
    private $lastName;
    private $email;
    private $password;
    private $role;
    private $PDO;

    public function __construct(PDO $PDO, $id = null, $firstName, $lastName, $email, $password, $role) {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->password = $password;
        $this->role = $role;
        $this->pdo = $PDO;
    }

    // Getters and Setters
    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }
    public function getFirstName() { return $this->firstName; }
    public function setFirstName($firstName) { $this->firstName = $firstName; }
    public function getLastName() { return $this->lastName; }
    public function setLastName($lastName) { $this->lastName = $lastName; }
    public function getEmail() { return $this->email; }
    public function setEmail($email) { $this->email = $email; }
    public function getPassword() { return $this->password; }
    public function setPassword($password) { $this->password = $password; }
    public function getRole() { return $this->role; }
    public function setRole($role) { $this->role = $role; }

    
}