<?php
require_once __DIR__ . '/../utils/Database.php';

class UserModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    
    public function createUser($data) {
        try {
            
            if ($this->getUserByEmail($data['email'])) {
                throw new Exception("Email already exists");
            }

            $sql = "INSERT INTO users (
                email, 
                password_hash, 
                role, 
                first_name, 
                last_name, 
                birthdate, 
                bio
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $success = $stmt->execute([
                $data['email'],
                $passwordHash,
                $data['role'],
                $data['first_name'],
                $data['last_name'],
                $data['birthdate'],
                $data['bio'] ?? null
            ]);

            if ($success) {
                return $this->getUserById($this->db->lastInsertId());
            }
            return false;

        } catch (PDOException $e) {
            error_log("UserModel Error: " . $e->getMessage());
            throw new Exception("Erreur lors de la création de l'utilisateur");
        }
    }

    public function login($email, $password) {
        try {
            $user = $this->getUserByEmail($email);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                    $this->updatePassword($user['id'], $password);
                }
                return $user;
            }
            return false;

        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            throw new Exception("Erreur d'authentification");
        }
    }

    public function getUserById($id) {
        try {
            $sql = "SELECT 
                id, 
                email, 
                role, 
                first_name, 
                last_name, 
                pfp_path, 
                birthdate, 
                bio, 
                created_at, 
                updated_at 
                FROM users WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("UserModel Error: " . $e->getMessage());
            throw new Exception("Erreur de récupération utilisateur");
        }
    }

    public function updateUser($userId, $updateData) {
        try {
            $fields = [];
            $params = [];
            
            $allowedFields = ['first_name', 'last_name', 'birthdate', 'bio', 'pfp_path'];
            
            foreach ($updateData as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $fields[] = "$key = ?";
                    $params[] = $value;
                }
            }
            
            if (empty($fields)) {
                throw new Exception("Aucun champ valide à mettre à jour");
            }
            
            $sql = "UPDATE users SET 
                " . implode(', ', $fields) . ", 
                updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
            
            $params[] = $userId;
            
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute($params);
            
            return $success ? $this->getUserById($userId) : false;

        } catch (PDOException $e) {
            error_log("Update Error: " . $e->getMessage());
            throw new Exception("Erreur de mise à jour du profil");
        }
    }

   
    public function getAllUsers($page = 1, $limit = 20) {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT 
                id, 
                email, 
                role, 
                first_name, 
                last_name, 
                created_at 
                FROM users 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit, $offset]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("FetchAll Error: " . $e->getMessage());
            throw new Exception("Erreur de récupération des utilisateurs");
        }
    }

    
    private function getUserByEmail($email) {
        try {
            $sql = "SELECT * FROM users WHERE email = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Email Fetch Error: " . $e->getMessage());
            return false;
        }
    }

   
    public function updatePassword($userId, $newPassword) {
        try {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $sql = "UPDATE users SET 
                password_hash = ?, 
                updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$passwordHash, $userId]);

        } catch (PDOException $e) {
            error_log("Password Update Error: " . $e->getMessage());
            throw new Exception("Erreur de mise à jour du mot de passe");
        }
    }

   
    public function deleteUser($userId) {
        try {
            
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$userId]);

        } catch (PDOException $e) {
            error_log("Delete Error: " . $e->getMessage());
            throw new Exception("Erreur de suppression du compte");
        }
    }

   
    public function getUsersByRole($role, $page = 1, $limit = 20) {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT 
                id, 
                email, 
                first_name, 
                last_name, 
                created_at 
                FROM users 
                WHERE role = ? 
                LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$role, $limit, $offset]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Role Filter Error: " . $e->getMessage());
            throw new Exception("Erreur de filtrage par rôle");
        }
    }

    
    public function updateProfilePicture($userId, $filePath) {
        try {
            $sql = "UPDATE users SET 
                pfp_path = ?, 
                updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$filePath, $userId]);

        } catch (PDOException $e) {
            error_log("Profile Picture Error: " . $e->getMessage());
            throw new Exception("Erreur de mise à jour de la photo");
        }
    }

    public function emailExists($email) {
        $sql = "SELECT COUNT(*) FROM users WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetchColumn() > 0;
    }


    
    public function deleteUserById($userId) {
        try {
            // Execute the delete query
            $sql = "DELETE FROM users WHERE id = :userId";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0; // Return true if a row was deleted
        } catch (PDOException $e) {
            // Optional: Log the error
            error_log("Failed to delete user by ID: " . $e->getMessage());
            return false;
        }
    }
}


?>