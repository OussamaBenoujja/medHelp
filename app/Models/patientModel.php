<?php
require_once __DIR__ . '/../utils/Database.php';
require_once 'AppointmentModel.php';
require_once 'userModel.php';

class PatientModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    
    public function createPatient(array $data) {
        try {
            $query = "INSERT INTO patients (user_id, insurance_number, emergency_contact_name, emergency_contact_phone, emergency_contact_relation, created_at)
                      VALUES (:user_id, :insurance_number, :emergency_contact_name, :emergency_contact_phone, :emergency_contact_relation, :created_at)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->bindParam(':insurance_number', $data['insurance_number']);
            $stmt->bindParam(':emergency_contact_name', $data['emergency_contact_name']);
            $stmt->bindParam(':emergency_contact_phone', $data['emergency_contact_phone']);
            $stmt->bindParam(':emergency_contact_relation', $data['emergency_contact_relation']);
            $stmt->bindParam(':created_at', $data['created_at']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Database Error in createPatient: ' . $e->getMessage());
            throw new Exception('Could not create patient');
        }
    }
    
    

    // Récupération du profil complet
    public function getFullProfile($patientId) {
        try {
            $sql = "SELECT u.first_name, u.last_name, u.email, u.birthdate, p.insurance_number 
                    FROM users u
                    JOIN patients p ON u.id = p.user_id
                    WHERE u.id = :id";
    
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $patientId, PDO::PARAM_INT);
            $stmt->execute();
    
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching patient profile: " . $e->getMessage());
            return [];
        }
    }
    

    // Mise à jour des informations médicales
    public function updateMedicalInfo($userId, $updateData) {
        try {
            $fields = [];
            $params = [];
            
            $allowedFields = ['insurance_number', 'emergency_contact'];
            
            foreach ($updateData as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $fields[] = "$key = ?";
                    $params[] = $value;
                }
            }
            
            if (empty($fields)) {
                throw new Exception("Aucun champ valide à mettre à jour");
            }
            
            $sql = "UPDATE patients SET " 
                 . implode(', ', $fields) 
                 . " WHERE user_id = ?";
            
            $params[] = $userId;
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);

        } catch (PDOException $e) {
            error_log("Medical Update Error: " . $e->getMessage());
            throw new Exception("Erreur de mise à jour des informations médicales");
        }
    }

    
    public function getPatientAppointments($patientId, $filters) {
        try {
            // Match using the appointments table's patient_id field
            $sql = "SELECT * FROM appointments WHERE patient_id = :patient_id";
    
            // Add optional filters for date range and status
            if (!empty($filters['status'])) {
                $sql .= " AND status = :status";
            }
            if (!empty($filters['date_from'])) {
                $sql .= " AND appointment_date >= :date_from";
            }
            if (!empty($filters['date_to'])) {
                $sql .= " AND appointment_date <= :date_to";
            }
    
            // Debugging: log the SQL query
            error_log("Generated SQL: " . $sql);
    
            $stmt = $this->db->prepare($sql);
    
            // Bind parameters properly
            $stmt->bindParam(':patient_id', $patientId, PDO::PARAM_INT);
            if (!empty($filters['status'])) {
                $stmt->bindParam(':status', $filters['status'], PDO::PARAM_STR);
            }
            if (!empty($filters['date_from'])) {
                $stmt->bindParam(':date_from', $filters['date_from'], PDO::PARAM_STR);
            }
            if (!empty($filters['date_to'])) {
                $stmt->bindParam(':date_to', $filters['date_to'], PDO::PARAM_STR);
            }
    
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        } catch (Exception $e) {
            error_log("Error fetching patient appointments: " . $e->getMessage());
            throw $e;
        }
    }
    
    
    

    // Suppression du profil patient
    public function deletePatientProfile($userId) {
        try {
            $sql = "DELETE FROM patients WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$userId]);

        } catch (PDOException $e) {
            error_log("Profile Delete Error: " . $e->getMessage());
            throw new Exception("Erreur de suppression du profil patient");
        }
    }

    // Vérification existence profil patient
    public function patientProfileExists($userId) {
        try {
            $sql = "SELECT 1 FROM patients WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return (bool)$stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log("Existence Check Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
