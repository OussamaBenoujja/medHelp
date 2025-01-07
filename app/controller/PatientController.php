<?php
require_once __DIR__ . '/../Models/PatientModel.php';
require_once __DIR__ . '/../Models/AppointmentModel.php';
require_once __DIR__ . '/../utils/SessionManager.php';
require_once __DIR__ . '/../utils/Validation.php';

class PatientController {
    private $patientModel;
    private $appointmentModel;
    private $validator;
    private $currentUserId;

    public function __construct() {
        $this->patientModel = new PatientModel();
        $this->appointmentModel = new AppointmentModel();
        $this->validator = new Validation();
        $this->currentUserId = SessionManager::get('user_id');

        if (!SessionManager::isPatient()) {
            $this->jsonResponse(['error' => 'Unauthorized access'], 403);
        }
    }

   
    public function getFullProfile() {
        try {
            // Debug log to ensure currentUserId exists and is valid
            error_log('getFullProfile called with userId ' . $this->currentUserId);
    
            // Get the profile data
            $profile = $this->patientModel->getFullProfile($this->currentUserId);
    
            // Handle the case where no profile is found
            if (!$profile) {
                $this->jsonResponse(['error' => 'Patient profile not found'], 404);
            }
    
            // Successful response
            $this->jsonResponse($profile);
    
        } catch (Exception $e) {
            // Catching and returning any exceptions from the PatientModel or elsewhere
            error_log('Error fetching patient profile: ' . $e->getMessage());
            $this->jsonResponse(['error' => 'An error occurred while fetching patient profile'], 500);
        }
    }
    

    public function updateMedicalInfo() {
        try {
            $allowedFields = [
                'insurance_number',
                'emergency_contact_name',
                'emergency_contact_phone',
                'emergency_contact_relation'
            ];

            $updateData = $this->validator->filterInput($_POST, $allowedFields);
            
            if (!$this->patientModel->updateMedicalInfo($this->currentUserId, $updateData)) {
                throw new Exception("Failed to update medical information");
            }

            $this->jsonResponse(['success' => true]);

        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    
    public function createAppointment() {
        try {
            
            error_log("Appointment creation request: " . json_encode($_POST));
    
            Validation::required($_POST, ['doctor_id', 'date']);
    
            $appointmentId = $this->appointmentModel->createAppointment([
                'user_id' => $this->currentUserId,
                'doctor_id' => $_POST['doctor_id'],
                'appointment_date' => $_POST['date'],
                'start_time' => null, 
                'end_time' => null,   
                'notes' => $_POST['notes'] ?? ''
            ]);
            
            if (!$appointmentId) {
                throw new Exception("Failed to create appointment.");
            }
    
            $this->jsonResponse([
                'success' => true,
                'appointment_id' => $appointmentId
            ]);
        } catch (Exception $e) {
            error_log("Error creating appointment: " . $e->getMessage());
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
    
    
    public function getAppointments() {
        try {
            $filters = [
                'status' => $_GET['status'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null
            ];
    
            // Pass the actual patient_id they belong to
            $appointments = $this->patientModel->getPatientAppointments(
                $this->currentUserId, // Make sure currentUserId is the patient_id
                $filters
            );
    
            $this->jsonResponse($appointments);
    
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
    

    public function cancelAppointment() {
        try {
            // Get the POST body
            $requestBody = file_get_contents('php://input');
            $data = json_decode($requestBody, true);
    
            // Validate that appointment_id is provided
            $appointmentId = $data['appointment_id'] ?? null;
            if (!$appointmentId) {
                throw new Exception("Appointment ID is required.");
            }
    
            // Validate that the appointment belongs to the patient
            $appointment = $this->appointmentModel->getAppointmentById($appointmentId);
       
            // Update the status to 'cancelled'
            $this->appointmentModel->updateAppointmentStatus($appointmentId, 'cancelled');
            $this->jsonResponse(['success' => true, 'message' => "Appointment cancelled."]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
    

    public function getEmergencyDetails() {
        try {
            $details = $this->patientModel->getEmergencyDetails($this->currentUserId);
            $this->jsonResponse($details);

        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 404);
        }
    }

    private function jsonResponse($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit();
    }

    public function patientDashboard() {
        if (!SessionManager::isPatient()) {
            header('Location: /login');
            exit();
        }
        include __DIR__ . '/../views/patient/patient.html';
    }
    
}
?>
