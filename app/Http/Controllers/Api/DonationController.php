<?php

namespace App\Http\Controllers\Api;

use App\Models\Donation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Notifications\DonationStatusNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;
use App\Notifications\DonationCreatedNotification;
use App\Mail\DonationCreatedMail;
use Exception;

class DonationController extends BaseController
{
    /**
     * Display a listing of donations with pagination and optional filtering.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        Log::info('Fetching donations', ['request' => $request->all()]);
        Log::info('Donations route accessed');

        $validated = $request->validate([
            'type' => 'nullable|in:food,clothing,money',
            'donor_id' => 'nullable|exists:users,id',
            'foodbank_id' => 'nullable|exists:users,id',
            'recipient_id' => 'nullable|exists:users,id',
        ]);

        try {
            $donations = Donation::with(['donor', 'foodbank', 'recipient'])
                ->when($validated['type'] ?? null, fn($query, $type) => $query->where('type', $type))
                ->when($validated['donor_id'] ?? null, fn($query, $donorId) => $query->where('donor_id', $donorId))
                ->when($validated['foodbank_id'] ?? null, fn($query, $foodbankId) => $query->where('foodbank_id', $foodbankId))
                ->when($validated['recipient_id'] ?? null, fn($query, $recipientId) => $query->where('recipient_id', $recipientId))
                ->paginate(10);

            Log::info('Donations fetched successfully', ['donations' => $donations]);

            return response()->json($donations, 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch donations', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch donations', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created donation in the database.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // 🟢 1. Request received
        Log::info('Request received to create a donation', ['request' => $request->all()]);

        // 🟢 2. Starting validation
        Log::info('Starting validation for donation creation');
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:food,clothing,money',
            'quantity' => 'required|integer|min:1',
            'donor_id' => 'nullable|exists:users,id',        // Allow nullable for donor_id
            'foodbank_id' => 'nullable|exists:users,id',      // Allow nullable for foodbank_id
            'description' => 'nullable|string|max:500',
        ]);

        // 🟢 3. Validation failed
        if ($validator->fails()) {
            Log::warning('Validation failed while creating donation', ['errors' => $validator->errors()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // 🟢 4. Validation successful
            Log::info('Validation successful');
            $validatedData = $validator->validated();
            $validatedData['status'] = 'pending';  // Set default status to pending

            $user = auth()->user();
            Log::info('Authenticated user', ['user_id' => $user->id, 'role' => $user->role]);

            // 🟢 5. Role-based handling
            if ($user->role === 'donor') {
                $validatedData['donor_id'] = $user->id;
                Log::info('Donor ID set from authenticated user', ['donor_id' => $user->id]);
            } elseif ($user->role === 'admin') {
                if (empty($validatedData['donor_id'])) {
                    Log::warning('Admin attempted to create donation without donor ID');
                    return response()->json(['error' => 'Donor ID is required for admin'], 422);
                }
            } else {
                Log::warning('Unauthorized action attempted', ['role' => $user->role]);
                return response()->json(['error' => 'Unauthorized action'], 403);
            }

            // 🟢 6. Creating donation
            Log::info('Creating donation with validated data', ['data' => $validatedData]);
            $donation = Donation::create($validatedData);
            Log::info('Donation created successfully', ['donation_id' => $donation->id]);

            // 🟢 7. Sending notifications
            if (!empty($validatedData['donor_id'])) {
                $donor = User::find($validatedData['donor_id']);
                if ($donor) {
                    Log::info('Sending email and in-app notification to donor', ['donor_email' => $donor->email]);
                    // Send email to donor
                    Mail::to($donor->email)->send(new DonationCreatedMail($donation, $donor));
                    // Send in-app notification to donor
                    $donor->notify(new DonationCreatedNotification($donation, 'donor'));
                    Log::info('Notifications sent to donor successfully');
                } else {
                    Log::warning('Donor not found', ['donor_id' => $validatedData['donor_id']]);
                }
            } else {
                Log::info('Donor ID not provided, skipping donor notifications');
            }

            if (!empty($validatedData['foodbank_id'])) {
                $foodbank = User::find($validatedData['foodbank_id']);
                if ($foodbank) {
                    Log::info('Sending email and in-app notification to foodbank', ['foodbank_email' => $foodbank->email]);
                    // Send email to foodbank
                    Mail::to($foodbank->email)->send(new DonationCreatedMail($donation, $foodbank));
                    // Send in-app notification to foodbank
                    $foodbank->notify(new DonationCreatedNotification($donation, 'foodbank'));
                    Log::info('Notifications sent to foodbank successfully');
                } else {
                    Log::warning('Foodbank not found', ['foodbank_id' => $validatedData['foodbank_id']]);
                }
            } else {
                Log::info('Foodbank ID not provided, skipping foodbank notifications');
            }

            // 🟢 8. Completion of donation creation and notifications
            Log::info('Donation created and notifications sent successfully', ['donation_id' => $donation->id]);
            return response()->json($donation, 201);
        } catch (Exception $e) {
            // 🔴 9. Error handling
            Log::error('Failed to create donation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to create donation', 'message' => $e->getMessage()], 500);
        }
    }




    /**
     * Update the specified donation in the database.
     *
     * @param Request $request
     * @param Donation $donation
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Donation $donation)
    {
        Log::info('Updating donation', ['request' => $request->all(), 'donation_id' => $donation->id]);

        // Validation rules
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|in:food,clothing,money',
            'quantity' => 'nullable|integer|min:1',
            'donor_id' => 'nullable|exists:users,id',
            'foodbank_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:pending,assigned,delivered',
            'description' => 'nullable|string|max:500',

        ]);

        // If validation fails
        if ($validator->fails()) {
            Log::warning('Validation failed while updating donation', ['errors' => $validator->errors()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $validatedData = $validator->validated();

            // Maintain status as pending if not explicitly updated
            if (!isset($validatedData['status'])) {
                $validatedData['status'] = $donation->status ?? 'pending';
            }

             $user = auth()->user();
                // Handle donor ID based on user role
            if ($user->role === 'donor') {
                $validatedData['donor_id'] = $user->id;
            } elseif ($user->role !== 'admin') {
                return response()->json(['error' => 'Unauthorized action'], 403);
            }

            // Update donation
            $donation->update($validatedData);

            Log::info('Donation updated successfully', ['donation' => $donation]);
            return response()->json($donation, 200);
        } catch (\Exception $e) {
            Log::error('Failed to update donation', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update donation', 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * Display the specified donation.
     *
     * @param Donation $donation
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Donation $donation)
    {
        try {
            return response()->json($donation->load(['donor', 'foodbank', 'recipient']), 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch donation', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch donation', 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * Remove the specified donation from the database.
     *
     * @param Donation $donation
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Donation $donation)
    {
        try {
            $donation->delete();

            Log::info('Donation deleted successfully', ['donation_id' => $donation->id]);

            return response()->json(['message' => 'Donation deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Failed to delete donation', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete donation', 'message' => $e->getMessage()], 500);
        }
    }

    // Assign a foodbank to a donation if it's pending and has no associated foodbank
    public function assignFoodbankToDonation(Request $request, Donation $donation, $foodbankId)
    {
        // Check if the foodbank exists
        $foodbank = User::find($foodbankId);
        if (!$foodbank) {
            return response()->json(['error' => 'Foodbank not found'], 404);
        }

        // Check if the donation status is pending and has no associated foodbank
        if ($donation->status !== 'pending' || $donation->foodbank_id !== null) {
            return response()->json(['error' => 'Donation is not eligible for assignment'], 400);
        }

        // Assign the foodbank and update the status to 'assigned'
        try {
            $donation->foodbank_id = $foodbankId;
            $donation->status = 'assigned'; // Update status to assigned
            $donation->save();

            Log::info('Donation assigned to foodbank', [
                'donation_id' => $donation->id,
                'foodbank_id' => $foodbankId
            ]);

            return response()->json([
                'message' => 'Donation assigned to foodbank successfully',
                'donation' => $donation
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to assign foodbank to donation', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Failed to assign foodbank',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Method to update the status of a donation
    public function updateDonationStatus(Request $request, $id)
    {
        // Log: Starting status update
        Log::info("Starting status update for donation ID: {$id}");

        // Ensure that the authenticated user is an admin or donor
        if (!Auth::user() || !in_array(Auth::user()->role, ['admin', 'donor'])) {
            Log::warning("Unauthorized action attempted by user ID: " . (Auth::user()->id ?? 'Guest'));
            return response()->json(['error' => 'Unauthorized action. Only admins and donors can update the status.'], 403);
        }

        // Log: User authentication successful
        Log::info("User ID " . Auth::user()->id . " authorized to update donation status.");

        // Validate the request
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected,assigned,completed',
        ]);
        Log::info("Validation successful for status: " . $validated['status']);

        try {
            $donation = Donation::findOrFail($id);
            $previousStatus = $donation->status; // Track previous status

            // Log: Previous and new status
            Log::info("Previous status: {$previousStatus}, New status: {$validated['status']}");

            // Update donation status
            $donation->update(['status' => $validated['status']]);
            Log::info("Donation status updated successfully for ID: {$id}");

            // Notify Foodbank user if status changes and foodbank_id is set
            if ($previousStatus !== $validated['status'] && $donation->foodbank_id) {
                $foodbank = \App\Models\User::where('id', $donation->foodbank_id)
                    ->where('role', 'foodbank')
                    ->first();

                if ($foodbank) {
                    Notification::send($foodbank, new DonationStatusNotification($donation));
                    Log::info("Notification sent to Foodbank ID: " . $donation->foodbank_id);
                } else {
                    Log::warning("Foodbank user not found for ID: " . $donation->foodbank_id);
                }
            } else {
                Log::info("No notification sent. Either status did not change or no foodbank ID.");
            }

            return response()->json([
                'message' => 'Donation status updated successfully',
                'donation' => $donation,
            ]);
        } catch (\Exception $e) {
            Log::error("Error updating donation status for ID: {$id} - " . $e->getMessage());
            return response()->json(['error' => 'Failed to update donation status.'], 500);
        }
    }




    // Mark a donation as completed
    public function markAsCompleted(Donation $donation)
    {
        // Check if the donation is already completed
        if ($donation->status === 'completed') {
            return response()->json(['message' => 'Donation is already completed'], 400);
        }

        // Mark the donation as completed
        try {
            $donation->status = 'completed'; // Update status to 'completed'
            $donation->save();

            Log::info('Donation marked as completed', ['donation_id' => $donation->id]);

            return response()->json(['message' => 'Donation marked as completed', 'donation' => $donation], 200);
        } catch (\Exception $e) {
            Log::error('Failed to mark donation as completed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to mark donation as completed', 'message' => $e->getMessage()], 500);
        }
    }
}
