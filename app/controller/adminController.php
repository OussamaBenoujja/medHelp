<?php
require_once __DIR__ . '/../Models/AdminModel.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Models/AppointmentModel.php';
require_once __DIR__ . '/../utils/SessionManager.php';
require_once __DIR__ . '/../utils/Validation.php';

class AdminController {
    private $adminModel;
    private $userModel;
    private $validator;
    private $currentAdminId;
    private $appointmentModel;

    public function __construct() {

        $this->adminModel = new AdminModel();
        $this->userModel = new UserModel();
        $this->validator = new Validation();
        $this->currentAdminId = SessionManager::get('user_id');
        $this->appointmentModel = new AppointmentModel();

        if (!SessionManager::isAdmin()) {
            $this->jsonResponse(['error' => 'Administrator privileges required'], 403);
            exit;
        }
    }

    // 1. Platform Statistics
    public function getPlatformStatistics() {
        try {
            $stats = $this->adminModel->getGlobalStatistics();
            $this->jsonResponse($stats);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    // 2. User Management
    public function listUsers($page = 1) {
        try {
            $perPage = 50;
            $users = $this->adminModel->getAllNonAdminUsers($page, $perPage);
            $this->jsonResponse([
                'page' => $page,
                'results' => $users,
                'total_users' => $this->adminModel->getTotalUserCount()
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function searchUsers() {
        try {
            $query = $_GET['q'] ?? '';
            $results = $this->adminModel->searchUsers($query);
            $this->jsonResponse($results);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function deleteUser() {
        try {
           
            $requestBody = file_get_contents('php://input');
            $data = json_decode($requestBody, true);
    
            
            $userId = $data['user_id'] ?? null;
    
            if (!$userId) {
                throw new Exception("User ID is required.");
            }
    
            
            $success = $this->userModel->deleteUserById($userId);
    
            if (!$success) {
                throw new Exception("Failed to delete user or user not found.");
            }
    
            $this->jsonResponse(['success' => true, 'message' => "User deleted successfully."]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
    

    // 3. System Operations
    public function getSystemHealth() {
        try {
            $health = $this->adminModel->getPlatformHealth();
            $this->jsonResponse($health);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function generateReport() {
        try {
            $reportType = $_POST['type'] ?? 'daily';
            $allowedTypes = ['daily', 'weekly', 'monthly'];
            
            if (!in_array($reportType, $allowedTypes)) {
                throw new Exception("Invalid report type");
            }

            $reportData = $this->adminModel->generatePlatformReport($reportType);
            $this->jsonResponse($reportData);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    // 4. Administrative Actions
    public function forceCancelAppointment() {
        try {
            $requestBody = file_get_contents('php://input');
            $data = json_decode($requestBody, true);
    
            // Extract appointment_id from the payload
            $appointmentId = $data['appointment_id'] ?? null;
    
            if (!$appointmentId) {
                throw new Exception("Appointment ID is required in the request.");
            }
    
            // Fetch appointment by ID
            $appointment = $this->appointmentModel->getAppointmentById($appointmentId);

    
            // Validate and update status
            $allowedStatuses = ['confirmed', 'completed', 'declined', 'cancelled'];
            $newStatus = $data['status'] ?? null;
    
            if (!in_array($newStatus, $allowedStatuses)) {
                throw new Exception("Invalid status value.");
            }
    
            $this->appointmentModel->updateAppointmentStatus($appointmentId, $newStatus);
    
            // Respond with success
            $this->jsonResponse(['success' => true, 'message' => "Status updated to '{$newStatus}'."]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    private function jsonResponse($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit();
    }

    public function adminDashboard() {
        if (!SessionManager::isAdmin()) {
            header('Location: /login');
            exit();
        }
        include __DIR__ . '/../views/admin/admin.html';
    }
}
?>
