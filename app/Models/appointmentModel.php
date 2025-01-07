<?php
require_once __DIR__ . '/../utils/Database.php';

class AppointmentModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Create appointment with full validation
    public function createAppointment(array $data)
    {
        try {
            // Map user_id to patient_id
            $stmt = $this->db->prepare("SELECT id FROM patients WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $data['user_id']]);
            $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if (!$patient || !$patient['id']) {
                throw new Exception("No patient found for user ID " . $data['user_id']);
            }
    
            $patient_id = $patient['id']; // Use the actual patient ID
    
            // Insert the appointment
            $sql = "
                INSERT INTO appointments (patient_id, doctor_id, appointment_date, start_time, end_time, status, created_at, updated_at)
                VALUES (:patient_id, :doctor_id, :appointment_date, :start_time, :end_time, :status, NOW(), NOW())
            ";
    
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'patient_id' => $patient_id,
                'doctor_id' => $data['doctor_id'],
                'appointment_date' => $data['appointment_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'status' => 'pending',
            ]);
    
            return $this->db->lastInsertId();
    
        } catch (Exception $e) {
            error_log("Failed to create appointment: " . $e->getMessage());
            throw $e;
        }
    }
    

    // Get single appointment
    public function getAppointmentById($id) {
        try {
            $sql = "SELECT * FROM appointments WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Appointment Fetch Error: " . $e->getMessage());
            return false;
        }
    }

    // Update appointment status 
    public function updateAppointmentStatus($id, $status) {
        $allowedStatuses = ['pending', 'confirmed', 'declined', 'completed','cancelled'];
        
        if (!in_array($status, $allowedStatuses)) {
            throw new Exception("Invalid appointment status");
        }

        try {
            $sql = "UPDATE appointments 
                    SET status = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$status, $id]);
        } catch (PDOException $e) {
            error_log("Status Update Error: " . $e->getMessage());
            throw new Exception("Error updating appointment status");
        }
    }

    // Delete appointment 
    public function deleteAppointment($id, $userId, $isPatient = true) {
        try {
            $appointment = $this->getAppointmentById($id);

            if ($isPatient && $appointment['status'] !== 'pending') {
                throw new Exception("Cannot delete non-pending appointment");
            }

            $sql = "DELETE FROM appointments WHERE id = ?";
            
            if ($isPatient) {
                $sql .= " AND patient_id = ?";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([$id, $userId]);
            }

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);

        } catch (PDOException $e) {
            error_log("Delete Error: " . $e->getMessage());
            throw new Exception("Error deleting appointment");
        }
    }

    // Core availability check logic
    private function isDoctorAvailable($doctorId, $date) {
        
        // $customAvailability = $this->getDoctorCustomAvailability($doctorId, $date);
        // if ($customAvailability !== null) {
        //     return (bool)$customAvailability['is_available'];
        // }
    
        
        $regularSchedule = $this->getDoctorRegularSchedule($doctorId, $date);
    
        return true; 
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

    private function getDoctorCustomAvailability($doctorId, $date) {
        try {
            $sql = "SELECT * FROM doctor_availability 
                    WHERE doctor_id = ? 
                    AND available_date = ? 
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$doctorId, $date]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Custom Availability Error: " . $e->getMessage());
            return null;
        }
    }

    private function isWithinTimeWindow($targetStart, $targetEnd, $windowStart, $windowEnd) {
        $targetStart = strtotime($targetStart);
        $targetEnd = strtotime($targetEnd);
        $windowStart = strtotime($windowStart);
        $windowEnd = strtotime($windowEnd);

        return ($targetStart >= $windowStart) && ($targetEnd <= $windowEnd);
    }

    private function hasAppointmentConflict($patientId, $date, $startTime, $endTime) {
        try {
            $sql = "SELECT 1 FROM appointments 
                    WHERE patient_id = ? 
                    AND appointment_date = ? 
                    AND (
                        (start_time < ? AND end_time > ?)
                        OR (start_time >= ? AND start_time < ?)
                    )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $patientId,
                $date,
                $endTime,
                $startTime,
                $startTime,
                $endTime
            ]);

            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Conflict Check Error: " . $e->getMessage());
            return true; // Fail-safe return
        }
    }

    // Get appointments with filters
    // public function getAppointments($filters = []) {
    //     $where = [];
    //     $params = [];
        
    //     foreach ($filters as $field => $value) {
    //         $where[] = "$field = ?";
    //         $params[] = $value;
    //     }

    //     try {
    //         $sql = "SELECT * FROM appointments";
            
    //         if (!empty($where)) {
    //             $sql .= " WHERE " . implode(" AND ", $where);
    //         }
            
    //         $stmt = $this->db->prepare($sql);
    //         $stmt->execute($params);
            
    //         return $stmt->fetchAll(PDO::FETCH_ASSOC);

    //     } catch (PDOException $e) {
    //         error_log("Appointments Fetch Error: " . $e->getMessage());
    //         return [];
    //     }
    // }

    public function isAvailable($doctorId, $date, $startTime = null, $endTime = null) {
        // Get the weekday name (e.g., "Monday") from the requested date
        $weekday = date('l', strtotime($date)); // Outputs "Monday", "Tuesday", etc.
    
        // Query to check if the doctor is available on the weekday (via JSON in `available_days`)
        $sql = "SELECT * FROM doctors
                WHERE id = ? AND JSON_CONTAINS(available_days, ?)";
    
        // Bind the doctor ID and weekday to query
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$doctorId, json_encode($weekday)]);
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch doctor's record
    
        if (!$doctor) {
            return false; // Doctor is not available on the requested weekday
        }
        
        return true; // Doctor is available
    }


    public function getAppointments(array $filters = []): array {
        try {
            // Base query
            $sql = "
                SELECT 
                    a.id AS appointment_id,
                    a.status,
                    a.appointment_date,
                    a.start_time,
                    a.end_time,
                    a.notes,
                    
                    -- Join patient details
                    p.id AS patient_id,
                    u.first_name AS patient_first_name,
                    u.last_name AS patient_last_name,
                    
                    -- Join doctor details
                    d.id AS doctor_id,
                    du.first_name AS doctor_first_name,
                    du.last_name AS doctor_last_name,
                    d.specialty

                FROM appointments a
                INNER JOIN patients p ON a.patient_id = p.id
                INNER JOIN users u ON p.user_id = u.id
                INNER JOIN doctors d ON a.doctor_id = d.id
                INNER JOIN users du ON d.user_id = du.id
            ";

            // Prepare where clause and query parameters
            $where = [];
            $queryParams = [];

            if (!empty($filters['doctor_id'])) {
                $where[] = "d.id = :doctor_id";
                $queryParams[':doctor_id'] = $filters['doctor_id'];
            }

            if (!empty($filters['patient_id'])) {
                $where[] = "p.id = :patient_id";
                $queryParams[':patient_id'] = $filters['patient_id'];
            }

            if (!empty($filters['status'])) {
                $where[] = "a.status = :status";
                $queryParams[':status'] = $filters['status'];
            }

            if (!empty($filters['start_date'])) {
                $where[] = "a.appointment_date >= :start_date";
                $queryParams[':start_date'] = $filters['start_date'];
            }

            if (!empty($filters['end_date'])) {
                $where[] = "a.appointment_date <= :end_date";
                $queryParams[':end_date'] = $filters['end_date'];
            }

            // Append WHERE clause if needed
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }

            // Append order clause
            $sql .= " ORDER BY a.appointment_date DESC, a.start_time ASC";

            // Prepare and execute
            $stmt = $this->db->prepare($sql);
            $stmt->execute($queryParams);

            // Fetch all results
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAppointments: " . $e->getMessage());
            return [];
        }
    }
    
    
}
?>
