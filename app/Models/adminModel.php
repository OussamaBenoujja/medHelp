<?php
require_once __DIR__ . '/../utils/Database.php';
require_once 'AppointmentModel.php';

class AdminModel {
    private $db;
    private $appointmentModel;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->appointmentModel = new AppointmentModel();
    }
    public function getUserStatistics() {
        $sql = "SELECT 
                role,
                COUNT(*) as count,
                EXTRACT(MONTH FROM created_at) AS month
                FROM users
                GROUP BY role, month
                ORDER BY month DESC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Appointment Statistics
    public function getAppointmentStats() {
        return [
            'status_distribution' => $this->getAppointmentStatusCounts(),
            'monthly_trend' => $this->getMonthlyAppointmentTrend()
        ];
    }

    private function getAppointmentStatusCounts() {
        $sql = "SELECT 
                status, 
                COUNT(*) as count 
                FROM appointments
                GROUP BY status";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getMonthlyAppointmentTrend() {
        $sql = "SELECT
                DATE_TRUNC('month', created_at) AS month,
                COUNT(*) as appointments
                FROM appointments
                GROUP BY month
                ORDER BY month DESC
                LIMIT 6";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // User Management
    public function getNonAdminUsers() {
        $sql = "SELECT 
                id, email, role, 
                first_name, last_name, 
                created_at
                FROM users
                WHERE role != 'admin'";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteUser($userId) {
        try {
            // Verify user isn't admin first
            $sqlCheck = "SELECT role FROM users WHERE id = ?";
            $stmt = $this->db->prepare($sqlCheck);
            $stmt->execute([$userId]);
            
            if ($stmt->fetchColumn() === 'admin') {
                throw new Exception("Cannot delete admin users");
            }

            // Cascading delete (from schema ON DELETE CASCADE)
            $sqlDelete = "DELETE FROM users WHERE id = ?";
            $stmt = $this->db->prepare($sqlDelete);
            return $stmt->execute([$userId]);

        } catch (PDOException $e) {
            error_log("Delete Error: " . $e->getMessage());
            return false;
        }
    }

    // System Statistics from Schema Only
    public function getPlatformHealth() {
        return [
            'users' => $this->getTableCount('users'),
            'doctors' => $this->getTableCount('doctors'),
            'patients' => $this->getTableCount('patients'),
            'appointments' => $this->getTableCount('appointments')
        ];
    }

    private function getTableCount($tableName) {
        $sql = "SELECT COUNT(*) FROM $tableName";
        $stmt = $this->db->query($sql);
        return $stmt->fetchColumn();
    }

    public function getGlobalStatistics() {
        try {
            $statistics = [];
    
            // Get the total number of users
            $sqlUsers = "SELECT COUNT(*) as total_users FROM users";
            $stmt = $this->db->query($sqlUsers);
            $statistics['total_users'] = $stmt->fetchColumn();
    
            // Get the total number of doctors
            $sqlDoctors = "SELECT COUNT(*) as total_doctors FROM doctors";
            $stmt = $this->db->query($sqlDoctors);
            $statistics['total_doctors'] = $stmt->fetchColumn();
    
            // Get the total number of patients
            $sqlPatients = "SELECT COUNT(*) as total_patients FROM patients";
            $stmt = $this->db->query($sqlPatients);
            $statistics['total_patients'] = $stmt->fetchColumn();
    
            // Get the total number of appointments
            $sqlAppointments = "SELECT COUNT(*) as total_appointments FROM appointments";
            $stmt = $this->db->query($sqlAppointments);
            $statistics['total_appointments'] = $stmt->fetchColumn();
    
            return $statistics;
    
        } catch (PDOException $e) {
            error_log("Error fetching global statistics: " . $e->getMessage());
            return [
                'total_users' => 0,
                'total_doctors' => 0,
                'total_patients' => 0,
                'total_appointments' => 0,
            ];
        }
    }

    public function getAllNonAdminUsers($page = 1, $perPage = 50) {
        try {
            // Calculate offset for pagination
            $offset = ($page - 1) * $perPage;
    
            // Query to fetch all non-admin users with pagination
            $sql = "SELECT id, email, role, first_name, last_name, created_at 
                    FROM users
                    WHERE role != 'admin'
                    ORDER BY created_at DESC
                    LIMIT :limit OFFSET :offset";
    
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching non-admin users: " . $e->getMessage());
            return [];
        }
    }
    
    public function getTotalUserCount() {
        try {
            // Query to count all non-admin users
            $sql = "SELECT COUNT(*) AS total_users FROM users WHERE role != 'admin'";
            $stmt = $this->db->query($sql);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting non-admin users: " . $e->getMessage());
            return 0;
        }
    }
    
    
}
?>
