<?php
require_once __DIR__ . '/../utils/Database.php';
require_once 'AppointmentModel.php';

class DoctorModel {
    private $db;
    private $appointmentModel;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->appointmentModel = new AppointmentModel();
    }

    // Create doctor profile with professional details
    public function createDoctorProfile($userId, $data) {
        try {
            $sql = "INSERT INTO doctors (
                user_id,
                specialty,
                license_number,
                years_experience,
                consultation_fee,
                available_days,
                start_time,
                end_time
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $userId,
                $data['specialty'],
                $data['license_number'],
                $data['years_experience'],
                $data['consultation_fee'],
                $data['available_days'],
                $data['start_time'],
                $data['end_time']
            ]);

        } catch (PDOException $e) {
            error_log("Doctor Profile Creation Error: " . $e->getMessage());
            throw new Exception("Failed to create doctor profile");
        }
    }

    // Get complete doctor profile
    public function getFullProfile($doctorId) {
        try {
            $sql = "SELECT 
                d.*,
                u.email,
                u.first_name,
                u.last_name,
                u.pfp_path
                FROM doctors d
                JOIN users u ON d.user_id = u.id
                WHERE d.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$doctorId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Profile Fetch Error: " . $e->getMessage());
            throw new Exception("Failed to retrieve doctor profile");
        }
    }

    // Update professional details
    public function updateProfessionalDetails($doctorId, $updateData) {
        try {
            $fields = [];
            $params = [];
            
            $allowedFields = [
                'specialty', 
                'license_number',
                'years_experience',
                'consultation_fee',
                'available_days',
                'start_time',
                'end_time'
            ];
            
            foreach ($updateData as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $fields[] = "$key = ?";
                    $params[] = $value;
                }
            }
            
            if (empty($fields)) {
                throw new Exception("No valid fields to update");
            }
            
            $sql = "UPDATE doctors SET 
                " . implode(', ', $fields) . "
                WHERE id = ?";
            
            $params[] = $doctorId;
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);

        } catch (PDOException $e) {
            error_log("Details Update Error: " . $e->getMessage());
            throw new Exception("Failed to update professional details");
        }
    }

   
    public function setAvailabilityException($doctorId, $date, $isAvailable, $reason = null) {
        $sql = "INSERT INTO doctor_availability (doctor_id, available_date, is_available, reason)
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        
        error_log("Setting availability for doctor_id: " . $doctorId);
    
        return $stmt->execute([$doctorId, $date, $isAvailable, $reason]);
    }
    



    public function getSchedule($doctorId, $filters) {
        try {
            // Match using the appointments table's doctor_id field
            $sql = "SELECT * FROM appointments WHERE doctor_id = :doctor_id";
    
            // Add optional filters for date range and status
            if (!empty($filters['start_date'])) {
                $sql .= " AND appointment_date >= :start_date";
            }
            if (!empty($filters['end_date'])) {
                $sql .= " AND appointment_date <= :end_date";
            }
            if (!empty($filters['status'])) {
                $sql .= " AND status = :status";
            }
    
            
            error_log("Generated SQL: " . $sql);
    
            $stmt = $this->db->prepare($sql);
    
            
            $stmt->bindParam(':doctor_id', $doctorId, PDO::PARAM_INT);
    
            
            if (!empty($filters['start_date'])) {
                $stmt->bindParam(':start_date', $filters['start_date'], PDO::PARAM_STR);
            }
            if (!empty($filters['end_date'])) {
                $stmt->bindParam(':end_date', $filters['end_date'], PDO::PARAM_STR);
            }
            if (!empty($filters['status'])) {
                $stmt->bindParam(':status', $filters['status'], PDO::PARAM_STR);
            }
    
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        } catch (Exception $e) {
            error_log("Error fetching doctor schedule: " . $e->getMessage());
            throw $e;
        }
    }
    

    
    public function getPracticeStats($doctorId) {
        try {
            return [
                'total_appointments' => $this->getTotalAppointments($doctorId),
                'earnings' => $this->calculateEarnings($doctorId),
                
            ];

        } catch (Exception $e) {
            error_log("Stats Error: " . $e->getMessage());
            return [];
        }
    }

    private function getTotalAppointments($doctorId) {
        $sql = "SELECT COUNT(*) FROM appointments 
                WHERE doctor_id = ? 
                AND status = 'completed'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$doctorId]);
        return $stmt->fetchColumn();
    }

    private function calculateEarnings($doctorId) {
        $sql = "SELECT SUM(consultation_fee) 
                FROM appointments a
                JOIN doctors d ON a.doctor_id = d.id
                WHERE a.doctor_id = ?
                AND a.status = 'completed'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$doctorId]);
        return $stmt->fetchColumn() ?? 0;
    }

    // Helper methods
    private function getAvailabilityException($doctorId, $date) {
        try {
            $sql = "SELECT * FROM doctor_availability 
                    WHERE doctor_id = ? 
                    AND available_date = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$doctorId, $date]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Availability Fetch Error: " . $e->getMessage());
            return false;
        }
    }

    public function getAvailableDoctorsBySpecialty($specialty) {
        try {
            $sql = "SELECT d.*, u.first_name, u.last_name 
                    FROM doctors d
                    JOIN users u ON d.user_id = u.id
                    WHERE specialty = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$specialty]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Specialty Search Error: " . $e->getMessage());
            return [];
        }
    }

    public function getDoctors($specialty = null) {
        $sql = "SELECT d.id AS doctor_id,
                d.user_id,
                u.first_name,
                u.last_name,
                d.specialty,
                d.years_experience,
                d.consultation_fee,
                d.available_days
            FROM doctors d
            JOIN users u ON u.id = d.user_id
            WHERE d.available_days IS NOT NULL;
            ";
    
        if ($specialty) {
            $sql .= " AND specialty = ?";
            return $this->db->query($sql, [$specialty])->fetchAll();
        } else {
            return $this->db->query($sql)->fetchAll();
        }
    }

    public function createDoctor($doctorData) {
        try {
            $sql = "
                INSERT INTO doctors (user_id, specialty, license_number, years_experience, 
                                     consultation_fee, available_days, start_time, end_time, 
                                     created_at, available)
                VALUES (:user_id, :specialty, :license_number, :years_experience, 
                        :consultation_fee, :available_days, 
                        :start_time, :end_time, :created_at, :available)";
    
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'user_id' => $doctorData['user_id'],
                'specialty' => $doctorData['specialty'],
                'license_number' => $doctorData['license_number'],
                'years_experience' => $doctorData['years_experience'],
                'consultation_fee' => $doctorData['consultation_fee'],
                'available_days' => $doctorData['available_days'], // JSON string
                'start_time' => $doctorData['start_time'],
                'end_time' => $doctorData['end_time'],
                'created_at' => $doctorData['created_at'],
                'available' => $doctorData['available']
            ]);
        } catch (PDOException $e) {
            error_log("Doctor Insertion Error: " . $e->getMessage());
            return false;
        }
    }
    

    public function getAvailableDays($doctorId) {
        $sql = "SELECT available_days FROM doctors WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$doctorId]);
        return $stmt->fetchColumn();
    }
    
    
    public function createDoctorUserAndData(array $userData, array $doctorData)
{
    try {
       
        $this->db->beginTransaction();

        
        $userSql = "
            INSERT INTO users (email, password_hash, role, first_name, last_name, birthdate, created_at)
            VALUES (:email, :password_hash, :role, :first_name, :last_name, :birthdate, :created_at)
        ";

        $userStmt = $this->db->prepare($userSql);
        $userStmt->execute([
            'email' => $userData['email'],
            'password_hash' => $userData['password_hash'],
            'role' => $userData['role'], // should be 'doctor'
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'birthdate' => $userData['birthdate'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        
        $userID = $this->db->lastInsertId();

        // Insert the doctor-specific data into the doctors table
        $doctorSql = "
            INSERT INTO doctors (user_id, specialty, license_number, years_experience, consultation_fee, 
                                 available_days, start_time, end_time, created_at, available)
            VALUES (:user_id, :specialty, :license_number, :years_experience, :consultation_fee, 
                    :available_days, :start_time, :end_time, :created_at, :available)
        ";

        $doctorStmt = $this->db->prepare($doctorSql);
        $doctorStmt->execute([
            'user_id' => $userID,
            'specialty' => $doctorData['specialty'],
            'license_number' => $doctorData['license_number'],
            'years_experience' => $doctorData['years_experience'],
            'consultation_fee' => $doctorData['consultation_fee'],
            'available_days' => json_encode($doctorData['available_days']),
            'start_time' => $doctorData['start_time'],
            'end_time' => $doctorData['end_time'],
            'created_at' => date('Y-m-d H:i:s'),
            'available' => $doctorData['available'],
        ]);

        
        $this->db->commit();

        return $userID; 

    } catch (PDOException $e) {
        
        $this->db->rollBack();
        error_log("Failed to create doctor: " . $e->getMessage());
        return false;
    }
}

public function getDoctorRegularSchedule($doctorId)
{
    try {
        $sql = "SELECT available_days FROM doctors WHERE user_id = :doctorId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':doctorId', $doctorId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && isset($result['available_days'])) {
            
            $availableDays = json_decode($result['available_days'], true);

            
            return is_array($availableDays) ? $availableDays : [];
        }

        return []; 

    } catch (Exception $e) {
        error_log("Error in getDoctorRegularSchedule: " . $e->getMessage());
        return [];
    }
}

}
?>
