<?php
namespace App\Models;

class Patient extends User {
    private $PDO;
    private $medicalHistory;
    private $appointments = [];

    public function __construct(PDO $PDO, $id = null, $firstName, $lastName, $email, $password, $role, $medicalHistory = '', $appointments = []) {
        parent::__construct($id, $firstName, $lastName, $email, $password, $role);
        $this->medicalHistory = $medicalHistory;
        $this->appointments = $appointments;
        $this->pdo = $PDO;
    }

    // Getters and Setters
    public function getMedicalHistory() { return $this->medicalHistory; }
    public function setMedicalHistory($medicalHistory) { $this->medicalHistory = $medicalHistory; }
    public function getAppointments() { return $this->appointments; }
    public function setAppointments($appointments) { $this->appointments = $appointments; }

    public function loadAppointments() {
        $stmt = $this->pdo->prepare("SELECT * FROM appointments WHERE patient_id = :patient_id");
        $stmt->execute(['patient_id' => $this->getId()]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($data as $appointment) {
            $doctor = new Doctor();
            $doctor->setId($appointment['doctor_id']);
            $doctor->load();
            $this->appointments[] = new Appointment(
                $appointment['id'],
                $this,
                $doctor,
                $appointment['date']
            );
        }
    }

}