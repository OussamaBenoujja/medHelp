<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Profile</title>
</head>
<body>
    <h1>Patient Profile</h1>
    <p>Name: <?= htmlspecialchars($patient['full_name']) ?></p>
    <p>Email: <?= htmlspecialchars($patient['email']) ?></p>

    <a href="/dashboard">Back to Dashboard</a>
</body>
</html>