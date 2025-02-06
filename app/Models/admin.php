<?php

class Admin extends User {
    private $PDO;
    private $appointments = [];

    public function __construct(PDO $PDO, $id = null, $firstName, $lastName, $email, $password, $role, $appointments = []) {
        parent::__construct($id, $firstName, $lastName, $email, $password, $role);
        $this->appointments = $appointments;
        $this->pdo = $PDO;
    }

    // Getters and Setters
    public function getAppointments() { return $this->appointments; }
    public function setAppointments($appointments) { $this->appointments = $appointments; }

}


?>