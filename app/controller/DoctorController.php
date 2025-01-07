<?php
require_once __DIR__ . '/../Models/DoctorModel.php';
require_once __DIR__ . '/../Models/AppointmentModel.php';
require_once __DIR__ . '/../utils/SessionManager.php';
require_once __DIR__ . '/../utils/Validation.php';

class DoctorController {
    private $doctorModel;
    private $appointmentModel;
    private $validator;
    private $currentDoctorId;

    public function __construct() {
        $this->doctorModel = new DoctorModel();
        $this->appointmentModel = new AppointmentModel();
        $this->validator = new Validation();
        $this->currentDoctorId = SessionManager::get('user_id');
    
        // Allow specific endpoints for all authenticated users
        $allowedPublicEndpoints = [
            '/doctors/specialty', // Public endpoint for listing doctors by specialty
        ];
    
        // Dynamically match `/doctor/{id}/available-days`
        if (preg_match('#^/doctor/\d+/available-days$#', $_SERVER['REQUEST_URI'])) {
            return; 
        }
    
        if (in_array($_SERVER['REQUEST_URI'], $allowedPublicEndpoints)) {
            return; 
        }
    
        
        // if (!SessionManager::isDoctor()) {
        //     $this->jsonResponse(['error' => 'Unauthorized access'], 403);
        // }
    }
    

    
    public function getProfessionalProfile() {
        try {
            $profile = $this->doctorModel->getFullProfile($this->currentDoctorId);
            $this->jsonResponse($profile);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 404);
        }
    }

    public function updateProfessionalDetails() {
        try {
            $allowedFields = [
                'specialty', 
                'license_number',
                'years_experience',
                'consultation_fee',
                'available_days',
                'start_time',
                'end_time'
            ];
            
            $updateData = $this->validator->filterInput($_POST, $allowedFields);
            $this->doctorModel->updateProfessionalDetails($this->currentDoctorId, $updateData);
            $this->jsonResponse(['success' => true]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    // 2. Appointment Management
    public function getScheduleAppointments() {
        try {
            $filters = [
                'start_date' => $_GET['from'] ?? date('Y-m-d'),
                'end_date' => $_GET['to'] ?? date('Y-m-d', strtotime('+1 month')),
                'status' => $_GET['status'] ?? ['confirmed']
            ];
    
            // Use the doctor_id from the session (or retrieve it appropriately)
            $appointments = $this->doctorModel->getSchedule($this->currentDoctorId, $filters);
    
            $this->jsonResponse($appointments);
    
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
    

    public function updateAppointmentStatus() {
        try {
            // Parse the incoming request body
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
    

    // 3. Availability Management
    public function setCustomAvailability() {
        try {
            $required = ['date'];
    
            Validation::validate($_POST, $required);
    
            // Log the posted data for debugging
            error_log("Setting custom availability: " . json_encode($_POST));
    
            $success = $this->doctorModel->setAvailabilityException(
                $this->currentDoctorId,
                $_POST['date'],
                true, 
                $_POST['reason'] ?? 'General availability'
            );
    
            if (!$success) {
                throw new Exception("Error setting availability.");
            }
    
            $this->jsonResponse(['success' => true]);
        } catch (Exception $e) {
            error_log("Error in setCustomAvailability: " . $e->getMessage());
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
    
    

    public function markDayOff() {
        try {
            $this->validator->validate($_POST, ['date' => 'Date']);
            
            $this->doctorModel->setAvailabilityException(
                $this->currentDoctorId,
                $_POST['date'],
                false, 
                $_POST['reason'] ?? 'Day off'
            );
            
            $this->jsonResponse(['success' => true]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    
    public function getPracticeStatistics() {
        try {
            $stats = $this->doctorModel->getPracticeStats($this->currentDoctorId);
            $this->jsonResponse($stats);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    // Helper method
    private function jsonResponse($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit();
    }

    public function doctorDashboard() {
        if (!SessionManager::isDoctor()) {
            header('Location: /login');
            exit();
        }
        include __DIR__ . '/../views/doctor/doctor.html';
    }

    public function getAvailableDoctorsBySpecialty() {
        try {
            $specialty = $_GET['specialty'] ?? null; 
            $doctors = $this->doctorModel->getDoctors($specialty);
    
            $this->jsonResponse($doctors);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Could not fetch doctors'], 500);
        }
    }
    
    public function getAvailableDays($doctorId) {
        try {
            
            error_log("Fetching available days for doctor with id: {$doctorId}");
    
            $availableDays = $this->doctorModel->getAvailableDays($doctorId);
    
            
            if ($availableDays === false) {
                throw new Exception("No available days found for doctor {$doctorId}");
            }
    
            $this->jsonResponse(['available_days' => json_decode($availableDays) ?? []]);
        } catch (Exception $e) {
            error_log("Error fetching available days: " . $e->getMessage());
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    
    
    
    
}
?>
