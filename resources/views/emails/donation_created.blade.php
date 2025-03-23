<!DOCTYPE html>
<html>
<head>
    <title>New Donation Created</title>
</head>
<body>
    <h2>Hello {{ $recipient->name }},</h2>
    <p>A new donation has been created:</p>
    <ul>
        <li>Type: {{ $donation->type }}</li>
        <li>Quantity: {{ $donation->quantity }}</li>
        <li>Description: {{ $donation->description ?? 'N/A' }}</li>
    </ul>
    <p>Thank you for being part of our community!</p>
</body>
</html>
