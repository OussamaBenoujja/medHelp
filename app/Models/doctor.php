<?php
namespace App\Models;

class Doctor extends User {
    private $specialization;
    private $licenseNumber;
    private $experienceYears;

    public function __construct($id = null, $firstName, $lastName, $email, $password, $role, $specialization, $licenseNumber, $experienceYears) {
        parent::__construct($id, $firstName, $lastName, $email, $password, $role);
        $this->specialization = $specialization;
        $this->licenseNumber = $licenseNumber;
        $this->experienceYears = $experienceYears;
    }

    // Getters and Setters
    public function getSpecialization() { return $this->specialization; }
    public function setSpecialization($specialization) { $this->specialization = $specialization; }
    public function getLicenseNumber() { return $this->licenseNumber; }
    public function setLicenseNumber($licenseNumber) { $this->licenseNumber = $licenseNumber; }
    public function getExperienceYears() { return $this->experienceYears; }
    public function setExperienceYears($experienceYears) { $this->experienceYears = $experienceYears; }
}