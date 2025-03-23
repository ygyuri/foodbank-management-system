<!DOCTYPE html>
<html>
<head>
    <title>New Feedback Received</title>
</head>
<body>
    <h2>Hello {{ $receiverName }},</h2>
    <p>You have received new feedback from <strong>{{ $senderName }}</strong>.</p>
    
    <p><strong>Rating:</strong> {{ $rating }}/5</p>
    
    @if(is_string($message))
        <p><strong>Message:</strong> {{ $message }}</p>
    @endif


    @if($thankYouNote)
        <p><strong>Thank You Note:</strong> {{ $thankYouNote }}</p>
    @endif

    <p><strong>Feedback Type:</strong> {{ $feedbackType }}</p>
    
    @if($reference)
        <p><strong>Reference:</strong> {{ $reference }}</p>
    @endif

    <p>Best Regards,</p>
    <p>Your Team</p>
</body>
</html>
