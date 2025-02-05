<?php
namespace App\Repositories;

use PDO;
use App\Models\Doctor;

class DoctorRepository {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function findByUserId($userId): ?Doctor {
        $stmt = $this->pdo->prepare("SELECT * FROM doctors WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) return null;
        return new Doctor(
            $data['id'],
            '', '', '', '',
            'doctor',
            $data['specialization'],
            $data['license_number'],
            $data['experience_years']
        );
    }

    public function save(Doctor $doctor): void {
        $stmt = $this->pdo->prepare("INSERT INTO doctors (user_id, specialization, license_number, experience_years) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $doctor->getId(),
            $doctor->getSpecialization(),
            $doctor->getLicenseNumber(),
            $doctor->getExperienceYears()
        ]);
    }
}