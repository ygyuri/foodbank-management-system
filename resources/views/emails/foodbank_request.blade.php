<h1 style="color: #4CAF50;">🌟 New Request Received!</h1>

<p>Dear {{ $foodbank->name ?? 'Unknown Foodbank' }},</p>

<p>We hope you are doing well! We wanted to let you know that a new request has been submitted by <strong>{{ $recipient->name }}</strong>. Below are the details of the request:</p>

<table style="border-collapse: collapse; width: 100%; margin: 20px 0;">
    <tr>
        <th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2;">Request Type</th>
        <td style="border: 1px solid #ddd; padding: 8px;">{{ $requestFB->type }}</td>
    </tr>
    <tr>
        <th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2;">Quantity</th>
        <td style="border: 1px solid #ddd; padding: 8px;">{{ $requestFB->quantity }}</td>
    </tr>
    <tr>
        <th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2;">Status</th>
        <td style="border: 1px solid #ddd; padding: 8px;">Pending</td>
    </tr>
</table>

<p>To view more details or to take action, please click the button below:</p>

<a href="{{ url('/requests/' . $requestFB->id) }}" style="display: inline-block; padding: 10px 20px; margin: 10px 0; background-color: #4CAF50; color: #fff; text-decoration: none; border-radius: 5px;">View Request</a>

<p>Thank you for your continued support and dedication. If you have any questions, feel free to reach out to us.</p>

<p>Best regards,<br>
The Support Team</p>

<hr style="margin: 20px 0; border: none; border-top: 1px solid #f2f2f2;">
<p style="font-size: 12px; color: #888;">This is an automated message. Please do not reply directly to this email.</p>
