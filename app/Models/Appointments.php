<?php
namespace App\Models;

class Appointment {
    private $id;
    private $patientId;
    private $doctorId;
    private $appointmentDate;
    private $status;
    private $reason;

    public function __construct($id = null, $patientId, $doctorId, $appointmentDate, $status, $reason) {
        $this->id = $id;
        $this->patientId = $patientId;
        $this->doctorId = $doctorId;
        $this->appointmentDate = $appointmentDate;
        $this->status = $status;
        $this->reason = $reason;
    }

    // Getters and Setters
    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }
    public function getPatientId() { return $this->patientId; }
    public function setPatientId($patientId) { $this->patientId = $patientId; }
    public function getDoctorId() { return $this->doctorId; }
    public function setDoctorId($doctorId) { $this->doctorId = $doctorId; }
    public function getAppointmentDate() { return $this->appointmentDate; }
    public function setAppointmentDate($appointmentDate) { $this->appointmentDate = $appointmentDate; }
    public function getStatus() { return $this->status; }
    public function setStatus($status) { $this->status = $status; }
    public function getReason() { return $this->reason; }
    public function setReason($reason) { $this->reason = $reason; }
}