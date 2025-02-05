<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Profile</title>
</head>
<body>
    <h1>Doctor Profile</h1>
    <p>Name: <?= htmlspecialchars($doctor['full_name']) ?></p>
    <p>Specialization: <?= htmlspecialchars($doctor['specialization']) ?></p>
    <p>License Number: <?= htmlspecialchars($doctor['license_number']) ?></p>
    <p>Experience: <?= htmlspecialchars($doctor['experience_years']) ?> years</p>

    <a href="/dashboard">Back to Dashboard</a>
</body>
</html>