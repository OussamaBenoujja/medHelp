<?php


class DoctorRepo extends UserRepo{

    protected $pdo;

    public function __construct(PDO $pdo, Doctor $doctor){
        $this->pdo = $pdo;
        $this->doctor = $doctor;
    }

    public function loadAppointments() {
        $stmt = $this->pdo->prepare("SELECT * FROM appointments WHERE doctor_id = :doctor_id");
        $stmt->execute(['doctor_id' => $this->doctor->getId()]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($data as $appointment) {
            $patient = new Patient();
            $patient->setId($appointment['patient_id']);
            $patient->load();
            $this->doctor->appointments[] = new Appointment(
                $appointment['id'],
                $patient,
                $this->doctor,
                $appointment['date']
            );
        }
    }
}

?>