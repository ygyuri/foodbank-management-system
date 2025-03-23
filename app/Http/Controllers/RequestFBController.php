<?php

namespace App\Http\Controllers;

use App\Models\RequestFB;
use App\Models\User;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Donation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Notifications\RecipientRequestStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Notifications\FoodbankRequestNotification as InAppFoodbankNotification;
use App\Notifications\RecipientRequestNotification as InAppRecipientNotification;
use App\Mail\FoodbankRequestNotification as EmailFoodbankNotification;
use App\Mail\RecipientRequestConfirmation as EmailRecipientNotification;
use Exception;

//use App\Notifications\DonationAssigned;


class RequestFBController extends Controller
{
    /**
     * Display a listing of the requests with pagination and optional filtering.
     *
     * @param HttpRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(HttpRequest $request)
    {
        Log::info('Fetching requests', [
            'user_id' => auth()->id(),
            'user_role' => auth()->user()->role,
            'request_data' => $request->all()
        ]);

        // Validate filters
        $validated = $request->validate([
            'type' => 'nullable|string|max:255',
            'quantity' => 'nullable|integer',
            'status' => 'nullable|string|in:pending,approved,rejected',
            'recipient_id' => 'nullable|integer|exists:users,id',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|string|in:type,quantity,status,created_at',
            'sort_order' => 'nullable|string|in:asc,desc',
            'search' => 'nullable|string|max:255'
        ]);

        try {
            // Base query
            $query = RequestFB::query();

            // Apply role-based filtering
            if (auth()->user()->role === 'recipient') {
                $query->where('recipient_id', auth()->id());
            }

            // Apply additional filters
            $query->when(Arr::get($validated, 'type'), fn($q, $type) => $q->where('type', $type))
            ->when(Arr::get($validated, 'quantity'), fn($q, $quantity) => $q->where('quantity', $quantity))
            ->when(Arr::get($validated, 'status'), fn($q, $status) => $q->where('status', $status))
            ->when(Arr::get($validated, 'recipient_id'), fn($q, $recipient_id) => $q->where('recipient_id', $recipient_id))
            ->when(Arr::get($validated, 'search'), fn($q, $search) => $q->where('type', 'LIKE', "%{$search}%")
                ->orWhere('status', 'LIKE', "%{$search}%"));

            // Apply sorting
            $sortBy = $validated['sort_by'] ?? 'created_at';
            $sortOrder = $validated['sort_order'] ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            // Paginate results
            $perPage = $validated['per_page'] ?? 10;
            $requests = $query->paginate($perPage);

            Log::info('Requests fetched successfully', ['total_requests' => $requests->total()]);
            return response()->json([
                'data' => $requests->items(),
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch requests', ['error' => $e->getMessage(), 'user_id' => auth()->id()]);
            return response()->json(['error' => 'Failed to fetch requests', 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * Store a newly created request in the database.
     *
     * @param HttpRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(HttpRequest $request)
    {
        Log::info('🔄 Starting request creation process', ['user_id' => auth()->id(), 'request_data' => $request->all()]);

        // Check if user is authenticated and is an admin or recipient
        if (!auth()->check() || !in_array(auth()->user()->role, ['recipient', 'admin'])) {
            Log::warning('❌ Unauthorized access attempt', ['user_id' => auth()->id()]);
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Validate incoming data with custom error messages
        $validated = $request->validate([
            'type' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'foodbank_id' => 'nullable|exists:users,id',  // Allow nullable and ensure it exists if provided
            'recipient_id' => 'nullable|exists:users,id'   // Validate only if provided
        ], [
            'foodbank_id.exists' => 'The selected foodbank does not exist.',
            'recipient_id.exists' => 'The selected recipient does not exist.'
        ]);

        try {
            // Determine recipient_id based on role
            $recipientId = auth()->user()->role === 'admin'
                ? ($validated['recipient_id'] ?? null)  // Admin can choose or leave null
                : auth()->id();  // Recipient must be their own ID

            if (auth()->user()->role === 'admin' && is_null($recipientId)) {
                Log::warning('❌ Admin did not specify a recipient', ['user_id' => auth()->id()]);
                return response()->json(['error' => 'Admin must choose a recipient'], 422);
            }

            // Create the request
            $req = RequestFB::create([
                'recipient_id' => $recipientId,
                'foodbank_id' => $validated['foodbank_id'] ?? null,  // Allow null
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'status' => 'pending',
            ]);

            Log::info('✅ Request created successfully', ['request_id' => $req->id]);

            // Get recipient user
            $recipient = User::find($recipientId);
            if (!$recipient) {
                Log::error('❌ Failed to find recipient user', ['recipient_id' => $recipientId]);
                return response()->json(['error' => 'Failed to find recipient.'], 404);
            }

            // Get foodbank user if provided
            $foodbank = $validated['foodbank_id'] ? User::find($validated['foodbank_id']) : null;

            // Handle missing foodbank gracefully
            if ($validated['foodbank_id'] && !$foodbank) {
                Log::error('❌ Failed to find foodbank user', ['foodbank_id' => $validated['foodbank_id']]);
                return response()->json(['error' => 'Failed to find foodbank.'], 404);
            }

            // Send Email Notifications if foodbank exists
            if ($foodbank) {
                Mail::to($foodbank->email)->send(new EmailFoodbankNotification($req, $foodbank, $recipient));
                Log::info('📧 Email sent to foodbank', ['email' => $foodbank->email]);
            } else {
                Log::info('⚠️ No foodbank selected, skipping email to foodbank.');
            }

            // Always send email to recipient
            Mail::to($recipient->email)->send(new EmailRecipientNotification($req, $foodbank, $recipient));
            Log::info('📧 Email sent to recipient', ['email' => $recipient->email]);

            // Send In-App Notifications
            if ($foodbank) {
                $foodbank->notify(new InAppFoodbankNotification($req, $recipient));
                Log::info('🔔 In-app notification sent to foodbank');
            } else {
                Log::info('⚠️ No foodbank selected, skipping in-app notification for foodbank.');
            }
            $recipient->notify(new InAppRecipientNotification($req, $foodbank));
            Log::info('🔔 In-app notification sent to recipient');

            return response()->json([
                'message' => 'Request submitted successfully',
                'data' => $req
            ], 201);
        } catch (Exception $e) {
            Log::error('❌ Failed to create request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to create request. Please try again.'], 500);
        }
    }



    /**
     * Display the specified request.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Display the specified request.
     */
    public function show($id)
    {
        Log::info("Fetching request with ID: $id");

        try {
            $req = RequestFB::where('id', $id)
                ->where(function ($query) {
                    $query->where('foodbank_id', auth()->id())
                        ->orWhereHas('foodbank', function ($subQuery) {
                            $subQuery->where('role', 'admin');
                        });
                })
                ->firstOrFail();

            Log::info('Request fetched successfully', ['request' => $req]);
            return response()->json(['data' => $req], 200);
        } catch (ModelNotFoundException $e) {
            Log::warning("Request not found with ID: $id", ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Request not found'], 404);
        } catch (\Exception $e) {
            Log::error("Failed to fetch request with ID: $id", ['error' => $e->getMessage()]);
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * Update the specified request in the database.
     *
     * @param HttpRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(HttpRequest $request, $id)
    {
        // Log the incoming request data
        Log::info("Updating request with ID: $id", ['request' => $request->all()]);

        try {
            // Fetch the request to update
            $req = RequestFB::findOrFail($id);

            // Check authorization
            if (
                !auth()->user()->hasRole('admin') &&
                auth()->id() !== $req->recipient_id
            ) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Separate validation rules for admin and recipient
            if (auth()->user()->hasRole('admin')) {
                // Admin can update all fields EXCEPT status
                $validatedData = $request->validate([
                    'type' => 'sometimes|string|max:255',
                    'quantity' => 'sometimes|integer|min:1',
                    // 'status' => 'sometimes|string|in:pending,approved,rejected', // Commented out
                ]);
            } else {
                // Recipient can update only type and quantity
                $validatedData = $request->validate([
                    'type' => 'sometimes|string|max:255',
                    'quantity' => 'sometimes|integer|min:1',
                ]);
            }

            // Sanitize and update the request
            $req->update($validatedData);

            // Log the successful update
            Log::info('Request updated successfully', ['request' => $req]);

            return response()->json(['message' => 'Request updated successfully', 'data' => $req], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Request not found with ID: $id", ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Request not found'], 404);
        } catch (\Exception $e) {
            Log::error("Failed to update request with ID: $id", ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update request', 'message' => $e->getMessage()], 500);
        }
    }




    /**
     * Remove the specified request from the database.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        Log::info("Deleting request with ID: $id");

        try {
            // Soft delete the request
            $req = RequestFB::findOrFail($id);
            $req->delete();

            Log::info('Request deleted successfully', ['request_id' => $id]);

            return response()->json(['message' => 'Request deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error("Failed to delete request with ID: $id", ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete request', 'message' => $e->getMessage()], 500);
        }
    }

    public function assignDonationToRequest($requestId, $donationId)
    {
        try {
            // Fetch the request and donation, ensuring they belong to the authenticated foodbank
            $request = RequestFB::where('id', $requestId)
                ->where('foodbank_id', auth()->id()) // Check if the request belongs to the authenticated foodbank
                ->firstOrFail();

            $donation = Donation::findOrFail($donationId); // Fetch the donation

            // Validate that the donation type and quantity meet the request's requirements
            if ($donation->type !== $request->type || $donation->quantity < $request->quantity) {
                return response()->json(['error' => 'Donation does not match request criteria'], 422);
            }

            // Log the assignment process
            Log::info('Assigning donation to request', [
                'request_id' => $requestId,
                'donation_id' => $donationId,
                'donation_type' => $donation->type,
                'donation_quantity' => $donation->quantity,
                'request_type' => $request->type,
                'request_quantity' => $request->quantity,
            ]);

            // Use a database transaction to ensure atomicity
            DB::transaction(function () use ($request, $donation) {
                // Update the request status to 'fulfilled'
                $request->update(['status' => 'fulfilled']);

                // Update the donation status to 'assigned' and associate it with the request
                $donation->update(['status' => 'assigned', 'assigned_request_id' => $request->id]);

                // Log the successful assignment
                Log::info('Donation successfully assigned to request', [
                    'request_id' => $request->id,
                    'donation_id' => $donation->id,
                ]);
            });

            // // Optionally, send notifications to foodbank and donor
            // $request->foodbank->notify(new DonationAssigned($donation, $request));
            // $donation->donor->notify(new DonationAssignedToDonor($donation, $request));

            return response()->json(['message' => 'Donation successfully assigned to request'], 200);
        } catch (\Exception $e) {
            // Log any errors
            Log::error('Failed to assign donation to request', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to assign donation', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Approve or Reject a Request
     */
    public function updateRequestStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,pending',
        ]);

        $requestFB = RequestFB::findOrFail($id);
        $requestFB->status = $validated['status'];
        $requestFB->save();

        // Send Notification to Recipient
        $recipient = User::find($requestFB->recipient_id);
        if ($recipient) {
            Notification::send($recipient, new RecipientRequestStatusNotification($requestFB, $validated['status']));
        }

        return response()->json([
            'message' => 'Request status updated successfully.',
            'data' => $requestFB,
        ]);
    }
}
