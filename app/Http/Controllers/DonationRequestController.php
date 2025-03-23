<?php

namespace App\Http\Controllers;

use App\Models\DonationRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Notifications\DonationRequestNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\DonorRequestConfirmation;
use App\Mail\FoodbankRequestConfirmation;
use App\Notifications\StatusDonationRequestNotification;

class DonationRequestController extends Controller
{
    // 1. Create a Donation Request
    // 1. Create a Donation Request
    public function store(Request $request)
    {
        $user = Auth::user();

        // ✅ Validate Request
        try {
            $validatedData = $request->validate([
                'donor_id' => 'required|exists:users,id',
                'type' => 'required|string',
                'quantity' => 'required|integer',
                'foodbank_id' => $user->role === 'admin' ? 'required|exists:users,id' : 'nullable',
                'description' => 'nullable|string|max:500',
            ]);
            Log::info('✅ Validation successful for donation request.', ['user_id' => $user->id]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Validation failed for donation request.', ['errors' => $e->errors()]);
            return response()->json(['error' => 'Invalid data provided.'], 422);
        }

        // ✅ Create Donation Request
        try {
            $donationRequest = DonationRequest::create([
                'foodbank_id' => $user->role === 'admin' ? $validatedData['foodbank_id'] : $user->id,
                'donor_id' => $validatedData['donor_id'],
                'type' => $validatedData['type'],
                'quantity' => $validatedData['quantity'],
                'status' => 'pending',
                'description' => $validatedData['description'],
                'created_by' => $user->id,
            ]);
            Log::info('✅ Donation request created successfully.', ['request_id' => $donationRequest->id]);
        } catch (\Exception $e) {
            Log::error('❌ Failed to create donation request.', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create donation request.'], 500);
        }

        // ✅ Fetch Donor and Foodbank Details
        try {
            $donor = User::findOrFail($validatedData['donor_id']);
            $foodbank = $validatedData['foodbank_id'] ? User::find($validatedData['foodbank_id']) : null;
        } catch (\Exception $e) {
            Log::error('❌ Failed to fetch user details.', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch user details.'], 500);
        }

        // ✅ Send In-App Notifications
        try {
            Notification::send($donor, new DonationRequestNotification($donationRequest));
            if ($foodbank) {
                Notification::send($foodbank, new DonationRequestNotification($donationRequest));
            }
            Log::info('✅ In-app notifications sent to donor and foodbank.');
        } catch (\Exception $e) {
            Log::error('❌ Failed to send in-app notifications.', ['error' => $e->getMessage()]);
        }

        // ✅ Send Emails to Donor and Foodbank
        try {
            Mail::to($donor->email)->send(new DonorRequestConfirmation($donationRequest, $donor, $foodbank));
            Log::info('📧 Email sent to donor.', ['email' => $donor->email]);

            if ($foodbank) {
                Mail::to($foodbank->email)->send(new FoodbankRequestConfirmation($donationRequest, $donor, $foodbank));
                Log::info('📧 Email sent to foodbank.', ['email' => $foodbank->email]);
            }
        } catch (\Exception $e) {
            Log::error('❌ Failed to send email notifications.', ['error' => $e->getMessage()]);
        }

        return response()->json($donationRequest, 201);
    }


    // 2. View Donation Requests
    public function index(Request $request)
    {
        $user = Auth::user();

        // Extract query parameters with default values
        $page = $request->query('page', 1);
        $pageSize = $request->query('pageSize', 10);
        $status = $request->query('status');
        $type = $request->query('type');
        $donorId = $request->query('donor_id');
        $sortField = $request->query('sortField', 'created_at'); // Default sort by created_at
        $sortOrder = $request->query('sortOrder', 'desc');        // Default sort order

        // Initialize query builder with necessary relationships
        $query = DonationRequest::with(['foodbank', 'donor', 'createdBy']);  // Include createdBy relationship

        // Apply role-based access control
        if ($user->role === 'foodbank') {
            $query->where('foodbank_id', $user->id);
        } elseif ($user->role === 'donor') {
            $query->where('donor_id', $user->id);
        }

        // Apply filters if present
        if ($status) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($donorId) {
            $query->where('donor_id', $donorId);
        }

        // Apply sorting
        $query->orderBy($sortField, $sortOrder);

        // Pagination
        $donationRequests = $query->paginate($pageSize, ['*'], 'page', $page);

        // Transform data to include creator's name
        $data = $donationRequests->items();
        $transformedData = collect($data)->map(function ($item) {
            return [
                'id' => $item->id,
                'foodbank' => $item->foodbank,
                'donor' => $item->donor,
                'type' => $item->type,
                'quantity' => $item->quantity,
                'description' => $item->description,
                'status' => $item->status,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'created_by' => $item->createdBy ? [
                    'id' => $item->createdBy->id,
                    'name' => $item->createdBy->name
                ] : null,  // Include createdBy details if available
            ];
        });

        // Return paginated and filtered results
        return response()->json([
            'data' => $transformedData,
            'meta' => [
                'current_page' => $donationRequests->currentPage(),
                'last_page' => $donationRequests->lastPage(),
                'per_page' => $donationRequests->perPage(),
                'total' => $donationRequests->total(),
            ]
        ]);
    }


    // 3. Update Status of Donation Request
    /**
     * Update the status of a donation request and notify the foodbank.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        Log::info("Received request to update status for Donation Request ID: {$id}");

        // Validate the request
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        try {
            // Find the donation request
            $donationRequest = DonationRequest::findOrFail($id);
            Log::info("Found Donation Request ID: {$id}");

            // Update the status
            $donationRequest->update(['status' => $validated['status']]);
            Log::info("Updated status for Donation Request ID: {$id} to {$validated['status']}");

            // Notify the foodbank via email and database
            $foodbank = $donationRequest->foodbank;
            if ($foodbank) {
                Notification::send($foodbank, new StatusDonationRequestNotification($donationRequest));
                Log::info("Notification sent to Foodbank ID: {$foodbank->id} for Donation Request ID: {$id}");
            } else {
                Log::warning("Foodbank not found for Donation Request ID: {$id}");
            }

            return response()->json([
                'message' => 'Status updated and notification sent successfully.',
                'data' => $donationRequest,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Donation Request ID: {$id} not found. Error: " . $e->getMessage());
            return response()->json(['error' => 'Donation Request not found.'], 404);
        } catch (\Exception $e) {
            Log::error("Failed to update status for Donation Request ID: {$id}. Error: " . $e->getMessage());
            return response()->json(['error' => 'Failed to update status.'], 500);
        }
    }
    // Fetch Donors for Dropdown
    public function getDonors()
    {
        $donors = User::donors()->get(['id', 'name']);
        return response()->json($donors);
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'donor_id' => 'required|exists:users,id',
            'type' => 'required|string',
            'quantity' => 'required|integer',
          //  'status' => 'required|string|in:pending,approved,rejected',
            'description' => 'nullable|string|max:500',  // Make sure this is here if you're sending it
        ]);

        $donationRequest = DonationRequest::findOrFail($id);
        $donationRequest->update($request->only('donor_id', 'foodbank_id', 'created_by', 'type', 'quantity', 'description'));  // Include description if needed

        return response()->json($donationRequest, 200);
    }
}
