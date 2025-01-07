<?php
return [

     // ✅ HANDLE ROOT PATH ("/")
     'GET|/' => ['controller' => 'UserController', 'action' => 'redirectUser'],

    // ✅ AUTHENTICATION ROUTES
    'GET|/login' => ['controller' => 'UserController', 'action' => 'showLoginForm', 'middleware' => ['guest']],
    'POST|/login' => ['controller' => 'UserController', 'action' => 'login', 'middleware' => ['guest']],
    'GET|/register' => ['controller' => 'UserController', 'action' => 'showRegistrationForm', 'middleware' => ['guest']],
    'POST|/register' => ['controller' => 'UserController', 'action' => 'register', 'middleware' => ['guest']],
    'POST|/logout' => ['controller' => 'UserController', 'action' => 'logout', 'middleware' => ['auth']],

    // ✅ USER PROFILE ROUTES
    'GET|/profile' => ['controller' => 'UserController', 'action' => 'viewProfile', 'middleware' => ['auth']],
    'POST|/profile/update' => ['controller' => 'UserController', 'action' => 'updateProfile', 'middleware' => ['auth']],

    // ✅ PASSWORD RECOVERY ROUTES
    'POST|/forgot-password' => ['controller' => 'UserController', 'action' => 'forgotPassword'],
    'POST|/reset-password' => ['controller' => 'UserController', 'action' => 'resetPassword'],

    // ✅ DOCTOR ROUTES
    'GET|/doctor/profile' => ['controller' => 'DoctorController', 'action' => 'getProfessionalProfile', 'middleware' => ['doctor']],
    'POST|/doctor/update' => ['controller' => 'DoctorController', 'action' => 'updateProfessionalDetails', 'middleware' => ['doctor']],
    'GET|/doctor/appointments' => ['controller' => 'DoctorController', 'action' => 'getScheduleAppointments', 'middleware' => ['doctor']],
    'POST|/doctor/appointment/update' => ['controller' => 'DoctorController', 'action' => 'updateAppointmentStatus', 'middleware' => ['doctor']],
    'POST|/doctor/set-availability' => ['controller' => 'DoctorController', 'action' => 'setCustomAvailability', 'middleware' => ['doctor']],
    'POST|/doctor/mark-day-off' => ['controller' => 'DoctorController', 'action' => 'markDayOff', 'middleware' => ['doctor']],
    'GET|/doctor/stats' => ['controller' => 'DoctorController', 'action' => 'getPracticeStatistics', 'middleware' => ['doctor']],

    // ✅ PATIENT ROUTES
    'GET|/patient/profile' => ['controller' => 'PatientController', 'action' => 'getFullProfile', 'middleware' => ['patient']],
    'POST|/patient/update-info' => ['controller' => 'PatientController', 'action' => 'updateMedicalInfo', 'middleware' => ['patient']],
    'POST|/patient/book-appointment' => ['controller' => 'PatientController', 'action' => 'createAppointment', 'middleware' => ['patient']],
    'GET|/patient/appointments' => ['controller' => 'PatientController', 'action' => 'getAppointments', 'middleware' => ['patient']],
    'POST|/patient/cancel-appointment' => ['controller' => 'PatientController', 'action' => 'cancelAppointment', 'middleware' => ['patient']],
    'GET|/patient/emergency-details' => ['controller' => 'PatientController', 'action' => 'getEmergencyDetails', 'middleware' => ['patient']],

    // ✅ APPOINTMENT ROUTES (Shared)
    'GET|/appointments' => ['controller' => 'AppointmentController', 'action' => 'index', 'middleware' => ['auth']],
    'GET|/appointment/{id}' => ['controller' => 'AppointmentController', 'action' => 'get', 'middleware' => ['auth']],
    'POST|/appointment/cancel' => ['controller' => 'AppointmentController', 'action' => 'cancel', 'middleware' => ['auth']],
    'POST|/appointment/status' => ['controller' => 'AppointmentController', 'action' => 'updateStatus', 'middleware' => ['auth']],

    // ✅ ADMIN ROUTES
    'GET|/admin/stats' => ['controller' => 'AdminController', 'action' => 'getPlatformStatistics', 'middleware' => ['admin']],
    'GET|/admin/users' => ['controller' => 'AdminController', 'action' => 'listUsers', 'middleware' => ['admin']],
    'POST|/admin/search-users' => ['controller' => 'AdminController', 'action' => 'searchUsers', 'middleware' => ['admin']],
    'POST|/admin/delete-user' => ['controller' => 'AdminController', 'action' => 'deleteUser', 'middleware' => ['admin']],
    'GET|/admin/system-health' => ['controller' => 'AdminController', 'action' => 'getSystemHealth', 'middleware' => ['admin']],
    'POST|/admin/generate-report' => ['controller' => 'AdminController', 'action' => 'generateReport', 'middleware' => ['admin']],
    'POST|/admin/cancel-appointment' => ['controller' => 'AdminController', 'action' => 'forceCancelAppointment', 'middleware' => ['admin']],

    // ✅ DOCTOR SEARCH & RATINGS
    'GET|/doctors/specialty' => ['controller' => 'DoctorController', 'action' => 'getAvailableDoctorsBySpecialty', 'middleware' => ['auth']],

    'POST|/doctor/review' => ['controller' => 'DoctorController', 'action' => 'postDoctorReview', 'middleware' => ['patient']],
    'GET|/doctor/reviews' => ['controller' => 'DoctorController', 'action' => 'getDoctorReviews', 'middleware' => ['auth']],

    'GET|/admin/dashboard' => ['controller' => 'AdminController', 'action' => 'adminDashboard', 'middleware' => ['admin']],
    'GET|/doctor/dashboard' => ['controller' => 'DoctorController', 'action' => 'doctorDashboard', 'middleware' => ['doctor']],
    'GET|/patient/dashboard' => ['controller' => 'PatientController', 'action' => 'patientDashboard', 'middleware' => ['patient']],
    // ✅ Get available days for a doctor
    'GET|/doctor/{id}/available-days' => ['controller' => 'DoctorController', 'action' => 'getAvailableDays', 'middleware' => ['auth']]


];
?>
