<!DOCTYPE html>
<html>
<head>
    <title>Welcome to Our System</title>
</head>
<body>
    <h1>Welcome, {{ $name }}!</h1>
    <p>Thank you for registering with us. Here are your login credentials:</p>
    <ul>
        <li><strong>Email:</strong> {{ $email }}</li>
        <li><strong>Password:</strong> {{ $password }}</li>
    </ul>
    <p>Please log in to your account and update your profile if needed.</p>
    <p>Best regards,<br>The Team</p>
</body>
</html>