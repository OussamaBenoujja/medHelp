<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>
<body>
    <h1>Welcome, <?= htmlspecialchars($user['full_name']) ?>!</h1>
    <p>Your Role: <?= htmlspecialchars($user['role']) ?></p>

    <?php if ($user['role'] === 'patient'): ?>
        <h2>Your Appointments</h2>
        <ul>
            <?php foreach ($appointments as $appointment): ?>
                <li>
                    Appointment with Dr. <?= htmlspecialchars($appointment['doctor_name']) ?> 
                    on <?= htmlspecialchars($appointment['appointment_date']) ?> 
                    - Status: <?= htmlspecialchars($appointment['status']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <a href="/book-appointment">Book New Appointment</a>

    <?php elseif ($user['role'] === 'doctor'): ?>
        <h2>Your Scheduled Appointments</h2>
        <ul>
            <?php foreach ($appointments as $appointment): ?>
                <li>
                    Appointment with Patient <?= htmlspecialchars($appointment['patient_name']) ?> 
                    on <?= htmlspecialchars($appointment['appointment_date']) ?> 
                    - Status: <?= htmlspecialchars($appointment['status']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <a href="/manage-appointments">Manage Appointments</a>

    <?php endif; ?>

    <a href="/logout">Logout</a>
</body>
</html>