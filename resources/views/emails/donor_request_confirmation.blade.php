<h2 style="color: #4CAF50;">Thank You for Your Donation Request!</h2>

<p>Dear {{ $donor->name }},</p>

<p>We have received your donation request for <strong>{{ $donationRequest->type }}</strong> (Quantity: {{ $donationRequest->quantity }}).</p>

<p>
    @if($foodbank)
        Your request will be processed by <strong>{{ $foodbank->name }}</strong>.
    @else
        Your request is pending assignment to a foodbank.
    @endif
</p>

<p>We appreciate your generosity and will keep you updated.</p>

<a href="{{ url('/donations/' . $donationRequest->id) }}" style="display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: #fff; text-decoration: none; border-radius: 5px;">View Request</a>

<p>Warm regards,<br>The Support Team</p>
