<?php
// File: utils/Validation.php

class Validation {

    public static function required(array $data, array $fields) : void {
        $missing = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $missing[] = $field;
            }
        }
        if (!empty($missing)) {
            throw new InvalidArgumentException(
                "Missing required fields: " . implode(', ', $missing)
            );
        }
    }

    public static function email(string $email) : string {
        $clean = filter_var($email, FILTER_SANITIZE_EMAIL);
        if (!filter_var($clean, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email format");
        }
        return $clean;
    }


    public static function password(string $password) : void {
        if (strlen($password) < 12) {
            throw new InvalidArgumentException("Password must be at least 12 characters");
        }
        if (!preg_match('/[A-Z]/', $password)) {
            throw new InvalidArgumentException("Password requires at least one uppercase letter");
        }
        if (!preg_match('/[a-z]/', $password)) {
            throw new InvalidArgumentException("Password requires at least one lowercase letter");
        }
        if (!preg_match('/[0-9]/', $password)) {
            throw new InvalidArgumentException("Password requires at least one number");
        }
    }


    public static function sanitizeString(string $value) : string {
        return htmlspecialchars(
            trim($value), 
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
            true 
        );
    }


    public static function futureDate(string $date) : DateTime {
        try {
            $dateObj = new DateTime($date);
            $now = new DateTime();
            
            if ($dateObj < $now) {
                throw new InvalidArgumentException("Date must be in the future");
            }
            
            return $dateObj;
        } catch (Exception $e) {
            throw new InvalidArgumentException("Invalid date format");
        }
    }


    public static function timeSlot(string $time) : string {
        if (!preg_match('/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
            throw new InvalidArgumentException("Invalid time format (HH:MM required)");
        }
        return $time;
    }

    public static function csrf(string $token) : void {
        if (!hash_equals(SessionManager::get('csrf_token'), $token)) {
            throw new InvalidArgumentException("Invalid CSRF token");
        }
    }


    public static function fileUpload(array $file, array $allowedTypes = ['image/jpeg', 'image/png']) : void {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException("File upload error");
        }
        
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        
        if (!in_array($mime, $allowedTypes)) {
            throw new InvalidArgumentException("Unsupported file type");
        }

        
        if ($file['size'] > 2097152) {
            throw new InvalidArgumentException("File exceeds 2MB limit");
        }

        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($extension), ['jpg', 'jpeg', 'png'])) {
            throw new InvalidArgumentException("Invalid file extension");
        }
    }

    public static function validate(array $fields, array $requiredFields) {
        foreach ($requiredFields as $field) {
            if (!isset($fields[$field]) || empty(trim($fields[$field]))) {
                throw new Exception("The field '$field' is required and cannot be empty.");
            }
        }
    }
}
?>
