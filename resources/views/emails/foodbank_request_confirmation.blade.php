<h2 style="color: #4CAF50;">New Donation Request Received!</h2>

<p>Dear {{ $foodbank->name }},</p>

<p>You have received a new donation request for <strong>{{ $donationRequest->type }}</strong> (Quantity: {{ $donationRequest->quantity }}) from {{ $donor->name }}.</p>

<p>Please log in to your dashboard to review and process the request.</p>

<a href="{{ url('/foodbank/requests/' . $donationRequest->id) }}" style="display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: #fff; text-decoration: none; border-radius: 5px;">View Request</a>

<p>Thank you for your efforts in serving the community!</p>

<p>Warm regards,<br>The Support Team</p>
