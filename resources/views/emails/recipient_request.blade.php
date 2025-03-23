<h1 style="color: #4CAF50;">✅ Request Confirmation</h1>

<p>Dear {{ $recipient->name }},</p>
{{-- 
<p>Thank you for submitting your request to <strong>{{ $foodbank->name ?? 'Unknown Foodbank'}}</strong>. We are pleased to inform you that your request has been received successfully. Below are the details of your request:</p> --}}
<p>{{ $foodbankMessage }}</p>

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

<p>To view the status of your request or for further details, please click the button below:</p>

<a href="{{ url('/requests/' . $requestFB->id) }}" style="display: inline-block; padding: 10px 20px; margin: 10px 0; background-color: #4CAF50; color: #fff; text-decoration: none; border-radius: 5px;">View Request</a>

<p>We appreciate your patience and will keep you updated on the progress of your request. If you need further assistance, feel free to contact us.</p>

<p>Thank you once again for trusting us!</p>

<p>Warm regards,<br>
The Support Team</p>

<hr style="margin: 20px 0; border: none; border-top: 1px solid #f2f2f2;">
<p style="font-size: 12px; color: #888;">This is an automated message. Please do not reply directly to this email.</p>
