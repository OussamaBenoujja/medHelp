<?php
require_once __DIR__ . '/../Models/AppointmentModel.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Models/DoctorModel.php';
require_once __DIR__ . '/../utils/SessionManager.php';
require_once __DIR__ . '/../utils/Validation.php';

class AppointmentController {
    private $appointmentModel;
    private $userModel;
    private $doctorModel;
    private $validator;

    public function __construct() {
        $this->appointmentModel = new AppointmentModel();
        $this->userModel = new UserModel();
        $this->doctorModel = new DoctorModel();
        $this->validator = new Validation();
        SessionManager::startSession();
        
        // Global authentication check
        if (!SessionManager::isAuthenticated()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
    }

    // 1. Create New Appointment (Patient Only)
    public function create() {
        try {
            // Authorization check
            if (!SessionManager::isPatient()) {
                throw new Exception("Appointment creation requires patient role", 403);
            }

            // Validation
            $required = [
                'doctor_id' => 'Doctor', 
                'date' => 'Date',
                'start_time' => 'Start Time',
                'end_time' => 'End Time'
            ];
            $this->validator->validate($_POST, $required);

            // Business Logic Checks
            $doctorId = $_POST['doctor_id'];
            $date = $_POST['date'];
            $start = $_POST['start_time'];
            $end = $_POST['end_time'];
            
            if (!$this->doctorModel->isAvailable($doctorId, $date, $start, $end)) {
                throw new Exception("Doctor not available at requested time");
            }

            // Create Appointment
            $appointmentId = $this->appointmentModel->create([
                'patient_id' => SessionManager::get('user_id'),
                'doctor_id' => $doctorId,
                'date' => $date,
                'start_time' => $start,
                'end_time' => $end,
                'notes' => $_POST['notes'] ?? null
            ]);

            $this->jsonResponse([
                'success' => true,
                'appointment_id' => $appointmentId
            ]);

        } catch (Exception $e) {
            $this->jsonResponse(
                ['error' => $e->getMessage()], 
                $e->getCode() ?: 400
            );
        }
    }

    // 2. Get Single Appointment (All Roles with Ownership Check)
    public function get($appointmentId) {
        try {
            $appointment = $this->appointmentModel->find($appointmentId);
            $userId = SessionManager::get('user_id');

            // Ownership Validation
            $allowed = false;
            switch(SessionManager::get('role')) {
                case 'admin':
                    $allowed = true;
                    break;
                case 'doctor':
                    $allowed = ($appointment['doctor_id'] == $userId);
                    break;
                case 'patient':
                    $allowed = ($appointment['patient_id'] == $userId);
                    break;
            }

            if (!$allowed) {
                throw new Exception("Access to appointment denied", 403);
            }

            $this->jsonResponse($appointment);

        } catch (Exception $e) {
            $this->jsonResponse(
                ['error' => $e->getMessage()], 
                $e->getCode() ?: 404
            );
        }
    }

    // // 3. List Appointments (Role-Specific Filtering)
    // public function index() {        
    //     $filters = [];

    //     // Role-Based Filtering
    //     switch(SessionManager::get('role')) {
    //         case 'doctor':
    //             $filters['doctor_id'] = SessionManager::get('user_id');
    //             break;
    //         case 'patient':
    //             $filters['patient_id'] = SessionManager::get('user_id');
    //             break;
    //         // Admin: no filters
    //     }

    //     // Date Filtering
    //     if (!empty($_GET['start_date'])) {
    //         $filters['start_date'] = $_GET['start_date'];
    //     }
    //     if (!empty($_GET['end_date'])) {
    //         $filters['end_date'] = $_GET['end_date'];
    //     }

    //     // Status Filtering
    //     if (!empty($_GET['status'])) {
    //         $filters['status'] = $_GET['status'];
    //     }

    //     try {
    //         $appointments = $this->appointmentModel->getAll($filters);
    //         $this->jsonResponse($appointments);
    //     } catch (Exception $e) {
    //         $this->jsonResponse(['error' => $e->getMessage()], 500);
    //     }
    // }

    // 4. Update Appointment Status (Doctor/Admin Only)
    public function updateStatus($appointmentId) {
        try {
            $appointment = $this->appointmentModel->find($appointmentId);
            $userRole = SessionManager::get('role');
            $userId = SessionManager::get('user_id');

            // Authorization
            if ($userRole === 'doctor' && $appointment['doctor_id'] !== $userId) {
                throw new Exception("Cannot modify other doctors' appointments", 403);
            }

            if (!in_array($userRole, ['doctor', 'admin'])) {
                throw new Exception("Status updates require doctor/admin role", 403);
            }

            $newStatus = $_POST['status'];
            $allowedStatuses = ['confirmed', 'completed', 'cancelled'];
            
            if (!in_array($newStatus, $allowedStatuses)) {
                throw new Exception("Invalid status value");
            }

            if ($this->appointmentModel->updateStatus($appointmentId, $newStatus)) {
                $this->jsonResponse(['success' => true]);
            }
            
            throw new Exception("Status update failed");

        } catch (Exception $e) {
            $this->jsonResponse(
                ['error' => $e->getMessage()], 
                $e->getCode() ?: 400
            );
        }
    }

    // 5. Cancel Appointment (Patient/Admin)  
    public function cancel($appointmentId) {
        try {
            $appointment = $this->appointmentModel->find($appointmentId);
            $userId = SessionManager::get('user_id');
            $role = SessionManager::get('role');

            // Authorization
            $allowed = match(true) {
                ($role === 'admin') => true,
                ($role === 'patient' && $appointment['patient_id'] === $userId) => true,
                default => false
            };

            if (!$allowed) {
                throw new Exception("Cancellation not permitted", 403);
            }

            if ($this->appointmentModel->updateStatus($appointmentId, 'cancelled_by_'.strtolower($role))) {
                $this->jsonResponse(['success' => true]);
            }

            throw new Exception("Could not cancel appointment");

        } catch (Exception $e) {
            $this->jsonResponse(
                ['error' => $e->getMessage()], 
                $e->getCode() ?: 400
            );
        }
    }

    // private function jsonResponse($data, $statusCode = 200) {
    //     header('Content-Type: application/json');
    //     http_response_code($statusCode);
    //     echo json_encode($data);
    //     exit;
    // }

    public function index() {
        try {
            // Permissions: Check based on session user role
            $currentUser = SessionManager::get('user_id');
            $role = SessionManager::get('role');

            // Collect filters from URL query parameters
            $filters = [
                'patient_id' => ($role === 'patient') ? $this->getPatientId($currentUser) : null,
                'doctor_id' => ($role === 'doctor') ? $this->getDoctorId($currentUser) : null,
                'status' => $_GET['status'] ?? null,
                'start_date' => $_GET['start_date'] ?? null,
                'end_date' => $_GET['end_date'] ?? null,
            ];

            // Fetch filtered appointments
            $appointments = $this->appointmentModel->getAppointments($filters);

            // Output response
            $this->jsonResponse($appointments);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

  
    private function getPatientId($userId): ?int {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM patients WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

   
    private function getDoctorId($userId): ?int {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM doctors WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    private function jsonResponse($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit();
    }


}
?>
