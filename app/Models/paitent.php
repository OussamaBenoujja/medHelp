<?php
namespace App\Models;

class Patient extends User {
    private $medicalHistory;

    public function __construct($id = null, $firstName, $lastName, $email, $password, $role, $medicalHistory = '') {
        parent::__construct($id, $firstName, $lastName, $email, $password, $role);
        $this->medicalHistory = $medicalHistory;
    }

    // Getters and Setters
    public function getMedicalHistory() { return $this->medicalHistory; }
    public function setMedicalHistory($medicalHistory) { $this->medicalHistory = $medicalHistory; }
}