<?php
// File: utils/SessionManager.php

class SessionManager {


        public static function startSession() {
            // Check if session is already started
            if (session_status() === PHP_SESSION_NONE) {
                // Set config before starting session
                ini_set('session.use_only_cookies', 1);
                ini_set('session.cookie_httponly', 1);
                ini_set('session.cookie_secure', 1);
    
                session_start();
            }
        }
    
        public static function isAuthenticated() {
            return isset($_SESSION['user_id']);
        }
    
        public static function isAdmin() {
            return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
        }
    
        public static function isDoctor() {
            return isset($_SESSION['role']) && $_SESSION['role'] === 'doctor';
        }
    
        public static function isPatient() {
            return isset($_SESSION['role']) && $_SESSION['role'] === 'patient';
        }
    
        public static function destroy() {
            session_destroy();
            $_SESSION = [];
        }
        

    /**
     * Set session value
     */
    public static function set(string $key, $value) {
        $_SESSION[$key] = $value;
    }

    /**
     * Get session value safely
     */
    public static function get(string $key) {
        return $_SESSION[$key] ?? null;
    }

    /**
     * Completely destroy session
     */


    /**
     * Regenerate session ID
     */
    public static function regenerate() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}
?>
