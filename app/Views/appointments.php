<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments</title>
</head>
<body>
    <h1>Your Appointments</h1>
    <ul>
        <?php foreach ($appointments as $appointment): ?>
            <li>
                Appointment ID: <?= htmlspecialchars($appointment['id']) ?><br>
                Date: <?= htmlspecialchars($appointment['appointment_date']) ?><br>
                Status: <?= htmlspecialchars($appointment['status']) ?><br>
                Reason: <?= htmlspecialchars($appointment['reason']) ?><br>
                <form method="POST" action="/update-appointment-status">
                    <input type="hidden" name="appointment_id" value="<?= htmlspecialchars($appointment['id']) ?>">
                    <select name="status">
                        <option value="scheduled">Scheduled</option>
                        <option value="completed">Completed</option>
                        <option value="canceled">Canceled</option>
                    </select>
                    <button type="submit">Update Status</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
    <a href="/dashboard">Back to Dashboard</a>
</body>
</html>