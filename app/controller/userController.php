<?php
require_once __DIR__ . '/../Models/userModel.php';
require_once __DIR__ . '/../Models/patientModel.php';
require_once __DIR__ . '/../Models/doctorModel.php';
require_once __DIR__ . '/../utils/Validation.php';
require_once __DIR__ . '/../utils/SessionManager.php';

class UserController {
    private $userModel;
    private $patientModel;
    private $doctorModel;
    private $validator;

    public function __construct() {
        $this->userModel = new UserModel();
        $this->patientModel = new PatientModel();
        $this->doctorModel = new DoctorModel();
        $this->validator = new Validation();
        SessionManager::startSession();
    }
    public function register() {
        
        try {
            
            $allowedFields = ['email', 'password', 'role', 'first_name', 'last_name', 'birthdate'];
            $userData = $_POST;
    
            
            $userData['password_hash'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
            
            if ($userData['role'] === 'doctor') {
                try {
                    $userData = [
                        'email' => $_POST['email'],
                        'password_hash' => password_hash($_POST['password'], PASSWORD_BCRYPT), // Secure hashing
                        'role' => 'doctor', // Always 'doctor' for this scenario
                        'first_name' => $_POST['first_name'],
                        'last_name' => $_POST['last_name'],
                        'birthdate' => $_POST['birthdate']
                    ];
            
                    $doctorData = [
                        'specialty' => $_POST['specialty'],
                        'license_number' => $_POST['license_number'],
                        'years_experience' => $_POST['years_experience'] ?? 0,
                        'consultation_fee' => $_POST['consultation_fee'] ?? 50.00,
                        'available_days' => $_POST['available_days'] ?? ["Monday", "Tuesday"],
                        'start_time' => $_POST['start_time'],
                        'end_time' => $_POST['end_time'],
                        'available' => true
                    ];
            
                    $doctorId = $this->doctorModel->createDoctorUserAndData($userData, $doctorData);
                    
                    if ($doctorId) {
                        $this->jsonResponse(['success' => true]);
                    } else {
                        throw new Exception("Failed to register doctor.");
                    }
            
                } catch (Exception $e) {
                    $this->jsonResponse(['error' => $e->getMessage()], 400);
                }
                return;
            } 

            
            $userId = $this->userModel->createUser($userData);
    
            
            if ($userData['role'] === 'patient') {
                $patientData = [
                    'user_id' => $userId,
                    'insurance_number' => $_POST['insurance_number'] ?? null,
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                $this->patientModel->createPatient($patientData);
            }
            else if ($userData['role'] === 'doctor') {
                $doctorData = [
                    'user_id' => $userId, 
                    'specialty' => $_POST['specialty'] ?? null,
                    'license_number' => $_POST['license_number'] ?? null,
                    'years_experience' => $_POST['years_experience'] ?? 0,
                    'consultation_fee' => $_POST['consultation_fee'] ?? 50.00,
                    'available_days' => json_encode($_POST['available_days'] ?? []),
                    'start_time' => $_POST['start_time'] ?? null,
                    'end_time' => $_POST['end_time'] ?? null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'available' => true
                ];
            
                // Debug logging for the data sent to createDoctor
                error_log("Creating new doctor: " . json_encode($doctorData));
            
                $this->doctorModel->createDoctor($doctorData);
            }
            
    
            // Respond with success
            $this->jsonResponse(['success' => true]);
    
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
    

    /**
     * Handle user login
     */
    public function login() {
        try {
            // Validate input
            Validation::required($_POST, ['email', 'password']);
            $email = Validation::email($_POST['email']);
    
            // Check credentials
            $user = $this->userModel->login($email, $_POST['password']);
    
            if ($user) {
                SessionManager::set('user_id', $user['id']);
                SessionManager::set('role', $user['role']);
    
                // Redirect user based on role
                $redirectPath = match ($user['role']) {
                    'admin' => '/admin/dashboard',
                    'doctor' => '/doctor/dashboard',
                    'patient' => '/patient/dashboard',
                    default => '/',
                };
    
                echo json_encode(['success' => true, 'redirect' => $redirectPath]);
            } else {
                throw new Exception("Invalid credentials");
            }
    
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle user logout
     */
    public function logout() {
        SessionManager::destroy();
        $this->jsonResponse(['success' => true]);
    }

    /**
     * Update user profile (common fields)
     */
    public function updateProfile() {
        try {
            $userId = SessionManager::get('user_id');
            
            $allowedFields = [
                'first_name',
                'last_name',
                'birthdate',
                'bio'
            ];
            
            $updateData = $this->validator->filterInput($_POST, [
                'first_name',
                'last_name',
                'birthdate',
                'bio'
            ]);
            
            if (!empty($_POST['new_password'])) {
                // Verify current password first
                $currentPassword = $_POST['current_password'] ?? '';
                if (!$this->userModel->verifyPassword($userId, $currentPassword)) {
                    throw new Exception("Current password incorrect");
                }
                $updateData['password'] = $_POST['new_password'];
            }

           

            $this->userModel->updateUser($userId, $updateData);
            $this->jsonResponse(['success' => true]);

        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Initiate password reset
     */
    public function forgotPassword() {
        try {
            $this->validator->validate($_POST, ['email' => 'Email']);
            
            // Generate and store reset token
            $token = bin2hex(random_bytes(32));
            $this->userModel->storeResetToken($_POST['email'], $token);
            
            // Send email with reset link
            // (Implementation specifics depend on your email service)
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Reset instructions sent'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    // Private helper methods

    private function jsonResponse($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit();
    }

    private function uploadProfilePicture($file) {
        // Secure implementation:
        $targetDir = __DIR__ . "/../uploads/";
        $allowedTypes = ['image/jpeg', 'image/png'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        // Verify upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error");
        }
    
        // Verify MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $allowedTypes)) {
            throw new Exception("Invalid file type");
        }
    
        // Verify extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
            throw new Exception("Invalid file extension");
        }
    
        // Generate safe filename
        $filename = sprintf("%s.%s", 
            bin2hex(random_bytes(8)),
            $ext
        );
        
        if (!move_uploaded_file($file['tmp_name'], $targetDir . $filename)) {
            throw new Exception("Failed to save file");
        }
    
        return '/uploads/' . $filename;
    }

    // Add these methods to UserController
public function showLoginForm() {
    // Redirect if already logged in
    if (SessionManager::isAuthenticated()) {
        header('Location: /');
        exit();
    }
    include __DIR__ . '/../views/auth/login.html';
}

public function showRegistrationForm() {
    if (SessionManager::isAuthenticated()) {
        header('Location: /');
        exit();
    }
    include __DIR__ . '/../views/auth/register.html';
}

public function viewProfile() {
    if (!SessionManager::isAuthenticated()) {
        header('Location: /login');
        exit();
    }
    $user = $this->userModel->getUserById(SessionManager::get('user_id'));
    include __DIR__ . '/../views/user/profile.php';
}

public function redirectUser() {
    if (!SessionManager::isAuthenticated()) {
        header('Location: /login');
        exit();
    }

    // Redirect based on user's role
    $role = SessionManager::get('role');
    switch ($role) {
        case 'admin':
            header('Location: /admin/dashboard');
            break;
        case 'doctor':
            header('Location: /doctor/dashboard');
            break;
        case 'patient':
            header('Location: /patient/dashboard');
            break;
        default:
            header('Location: /login');
    }
    exit();
}

}
?>
