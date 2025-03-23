<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Mail\FeedbackReceivedMail;
use Exception;

class FeedbackController extends Controller
{
    /**
     * Display a listing of feedback with pagination and optional filtering.
     */
    public function index(Request $request)
    {
        Log::info('Fetching feedback', ['request' => $request->all()]);

        $validated = $request->validate([
            'receiver_id' => 'nullable|integer|exists:users,id',
            'sender_id' => 'nullable|integer|exists:users,id',
            'rating' => 'nullable|integer|min:1|max:5',
        ]);

        try {
            $feedbacks = Feedback::with(['sender', 'receiver'])
                ->when(isset($validated['receiver_id']), fn($query) => $query->where('receiver_id', $validated['receiver_id']))
                ->when(isset($validated['sender_id']), fn($query) => $query->where('sender_id', $validated['sender_id']))
                ->when(isset($validated['rating']), fn($query) => $query->where('rating', $validated['rating']))
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json($feedbacks, 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch feedback', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch feedback'], 500);
        }
    }

   /**
     * Store a newly created feedback in the database.
     */
    public function store(Request $request)
    {
        Log::info('Received request to create feedback', ['request' => $request->all()]);

        $user = auth()->user();

        if (!$user) {
            Log::warning('Unauthorized feedback creation attempt');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|integer|exists:users,id',
            'thank_you_note' => 'nullable|string|max:1000',
            'rating' => 'required|integer|min:1|max:5',
            'message' => 'nullable|string|max:5000',
           'type' => 'nullable|string|in:request_fb,donation_request,donation', // Ensure only valid ENUM values
            'reference' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed for feedback creation', ['errors' => $validator->errors()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            Log::info('Validation passed, creating feedback');

            $feedback = Feedback::create([
                'sender_id' => $user->id,
                'receiver_id' => $request->receiver_id,
                'thank_you_note' => $request->thank_you_note,
                'rating' => $request->rating,
                'message' => $request->message,
                'type' => $request->type,
                'reference' => $request->reference,
            ]);

            Log::info('Feedback created successfully', ['feedback_id' => $feedback->id]);

            // Fetch the receiver details
            $receiver = User::find($request->receiver_id);
            if ($receiver) {
                Log::info('Sending feedback email to receiver', ['receiver_email' => $receiver->email]);

                // Send email notification
                Mail::to($receiver->email)->send(new FeedbackReceivedMail($feedback, $user));

                Log::info('Feedback email sent successfully', ['receiver_id' => $receiver->id]);
            } else {
                Log::warning('Receiver not found for email notification', ['receiver_id' => $request->receiver_id]);
            }

            return response()->json([
                'message' => 'Feedback created and email sent successfully',
                'data' => $feedback
            ], 201);
        } catch (Exception $e) {
            Log::error('Error while creating feedback', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create feedback'], 500);
        }
    }




    /**
     * Display the specified feedback.
     */
    public function show($id)
    {
        try {
            $feedback = Feedback::with(['sender', 'receiver'])->findOrFail($id);
            return response()->json($feedback, 200);
        } catch (\Exception $e) {
            Log::error('Feedback not found', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Feedback not found'], 404);
        }
    }

    /**
     * Update the specified feedback.
     */
    public function update(Request $request, $id)
    {
        Log::info("Updating feedback with ID: $id", ['request' => $request->all()]);

        $validator = Validator::make($request->all(), [
            'receiver_id' => 'sometimes|required|integer|exists:users,id',
            'thank_you_note' => 'sometimes|nullable|string|max:1000',
            'rating' => 'sometimes|required|integer|min:1|max:5',
            'message' => 'sometimes|nullable|string|max:5000',
            'type' => 'sometimes|nullable|string|in:request_fb,donation_request,donation', // Ensure only valid ENUM values
            'reference' => 'sometimes|nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $feedback = Feedback::findOrFail($id);
            $feedback->update($validator->validated());
            return response()->json(['message' => 'Feedback updated successfully', 'data' => $feedback], 200);
        } catch (\Exception $e) {
            Log::error("Failed to update feedback with ID: $id", ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update feedback'], 500);
        }
    }

    /**
     * Remove the specified feedback from the database.
     */
    public function destroy($id)
    {
        try {
            $feedback = Feedback::findOrFail($id);
            $feedback->delete();
            return response()->json(['message' => 'Feedback deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error("Failed to delete feedback with ID: $id", ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete feedback'], 500);
        }
    }
}
