<?php
namespace App\Models;

class Appointment {
    private $id;
    private $patientId;
    private $doctorId;
    private $appointmentDate;
    private $status;
    private $reason;
    private $PDO;

    public function __construct(PDO $PDO ,$id = null, $patientId, $doctorId, $appointmentDate, $status, $reason) {
        $this->id = $id;
        $this->patientId = $patientId;
        $this->doctorId = $doctorId;
        $this->appointmentDate = $appointmentDate;
        $this->status = $status;
        $this->reason = $reason;
        $this->pdo = $PDO;
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



    public function makeAppointment(){
        $stmt = $this->pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, status, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $this->getPatientId(),
            $this->getDoctorId(),
            $this->getAppointmentDate(),
            $this->getStatus(),
            $this->getReason()
        ]);
    }

    public function deleteAppointment(){
        $stmt = $this->pdo->prepare("DELETE FROM appointments WHERE id = :id");
        $stmt->execute(['id' => $this->getId()]);
    }

    public function updateAppointment(){
        $stmt = $this->pdo->prepare("UPDATE appointments SET patient_id = ?, doctor_id = ?, appointment_date = ?, status = ?, reason = ? WHERE id = ?");
        $stmt->execute([
            $this->getPatientId(),
            $this->getDoctorId(),
            $this->getAppointmentDate(),
            $this->getStatus(),
            $this->getReason(),
            $this->getId()
        ]);
    }


    static public function getAppointmentById(PDO $pdo, $id){
        $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) return null;
        return new Appointment(
            $data['id'],
            $data['patient_id'],
            $data['doctor_id'],
            $data['appointment_date'],
            $data['status'],
            $data['reason']
        );
    }

    static public function getAllAppointments(PDO $pdo){
        $stmt = $pdo->prepare("SELECT * FROM appointments");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $appointments = [];
        foreach ($data as $appointment) {
            $appointments[] = new Appointment(
                $appointment['id'],
                $appointment['patient_id'],
                $appointment['doctor_id'],
                $appointment['appointment_date'],
                $appointment['status'],
                $appointment['reason']
            );
        }
        return $appointments;
    }

    static function getAppointmentsByPatientId(PDO $pdo, $patientId){
        $stmt = $pdo->prepare("SELECT * FROM appointments WHERE patient_id = :patient_id");
        $stmt->execute(['patient_id' => $patientId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $appointments = [];
        foreach ($data as $appointment) {
            $appointments[] = new Appointment(
                $appointment['id'],
                $appointment['patient_id'],
                $appointment['doctor_id'],
                $appointment['appointment_date'],
                $appointment['status'],
                $appointment['reason']
            );
        }
        return $appointments;
    }

}