<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\DonationRequest;
use App\Models\RequestFB;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    /**
     * Generate a report of all donors with their donations and donation requests.
     *
     * @param int|null $donorId Optional donor ID for filtering.
     * @return JsonResponse
     */

    public function generateDonorTransactionReport($donorId = null)
    {
        // Base query to fetch donors
        $query = User::where('role', 'donor')
            ->with([
                'donations' => function ($query) {
                    $query->with(['foodbank', 'recipient']); // Include foodbank and recipient for each donation
                },
                'donationRequests' => function ($query) {
                    $query->with(['foodbank', 'createdBy']); // Include foodbank and creator for each donation request
                }
            ]);
    
        // Apply filter if donorId is provided
        if ($donorId) {
            $query->where('id', $donorId);
        }
    
        // Fetch donors with their relationships
        $donors = $query->get();
    
        // Format the response
        $response = $donors->map(function ($donor) {
            return [
                'id' => $donor->id,
                'name' => $donor->name,
                'email' => $donor->email,
                'donations' => $donor->donations->map(function ($donation) {
                    return [
                        'id' => $donation->id,
                        'type' => $donation->type,
                        'quantity' => $donation->quantity,
                        'description' => $donation->description,
                        'status' => $donation->status,
                        'foodbank' => $donation->foodbank ? [
                            'id' => $donation->foodbank->id,
                            'name' => $donation->foodbank->name,
                        ] : null,
                        'recipient' => $donation->recipient ? [
                            'id' => $donation->recipient->id,
                            'name' => $donation->recipient->name,
                        ] : null,
                        'created_at' => $donation->created_at->toDateTimeString(),
                        'updated_at' => $donation->updated_at->toDateTimeString(),
                    ];
                }),
                'donationRequests' => $donor->donationRequests->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'type' => $request->type,
                        'quantity' => $request->quantity,
                        'description' => $request->description,
                        'status' => $request->status,
                        'foodbank' => $request->foodbank ? [
                            'id' => $request->foodbank->id,
                            'name' => $request->foodbank->name,
                        ] : null,
                        'created_by' => $request->createdBy ? [
                            'id' => $request->createdBy->id,
                            'name' => $request->createdBy->name,
                        ] : null,
                        'created_at' => $request->created_at->toDateTimeString(),
                        'updated_at' => $request->updated_at->toDateTimeString(),
                    ];
                }),
            ];
        });
    
        // Return the response as JSON
        return response()->json(['donors' => $response ?? []]);
    }


    /**
     * Generate a report of all foodbanks with their donations, donation requests, and recipient requests.
     *
     * @param int|null $foodbankId Optional foodbank ID for filtering.
     * @return JsonResponse
     */
    public function generateFoodbankActivityReport($foodbankId = null)
    {
        // Base query to fetch foodbanks
        $query = User::where('role', 'foodbank')
            ->with([
                // Include donations made to the foodbank
                'donations' => function ($query) {
                    $query->with(['donor', 'foodbank']); // Include donor and foodbank for each donation
                },
                // Include donation requests made by the foodbank
                'donationRequests' => function ($query) {
                    $query->with(['donor', 'foodbank', 'createdBy']); // Include donor, foodbank, and creator for each donation request
                },
                // Include recipient requests made to the foodbank
                'requestsFb' => function ($query) {
                    $query->with(['recipient', 'foodbank']); // Include recipient and foodbank for each request
                }
            ]);

        // Apply filter if foodbankId is provided
        if ($foodbankId) {
            $query->where('id', $foodbankId);
        }

        // Fetch foodbanks with their relationships
        $foodbanks = $query->get();

        // Format the response
        $response = $foodbanks->map(function ($foodbank) {
            return [
                'id' => $foodbank->id,
                'name' => $foodbank->name,
                'email' => $foodbank->email,
                'donations' => $foodbank->donations->map(function ($donation) {
                    return [
                        'id' => $donation->id,
                        'type' => $donation->type,
                        'quantity' => $donation->quantity,
                        'description' => $donation->description,
                        'status' => $donation->status,
                        'donor' => $donation->donor ? [
                            'id' => $donation->donor->id,
                            'name' => $donation->donor->name,
                        ] : null,
                        'recipient' => $donation->recipient ? [
                            'id' => $donation->recipient->id,
                            'name' => $donation->recipient->name,
                        ] : null,
                        'created_at' => $donation->created_at,
                        'updated_at' => $donation->updated_at,
                    ];
                }),
                'donationRequests' => $foodbank->donationRequests->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'type' => $request->type,
                        'quantity' => $request->quantity,
                        'description' => $request->description,
                        'status' => $request->status,
                        'donor' => $request->donor ? [
                            'id' => $request->donor->id,
                            'name' => $request->donor->name,
                        ] : null,
                        'created_by' => $request->createdBy ? [
                            'id' => $request->createdBy->id,
                            'name' => $request->createdBy->name,
                        ] : null,
                        'created_at' => $request->created_at,
                        'updated_at' => $request->updated_at,
                    ];
                }),
                'requestsFb' => $foodbank->requestsFb->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'type' => $request->type,
                        'quantity' => $request->quantity,
                        'status' => $request->status,
                        'recipient' => $request->recipient ? [
                            'id' => $request->recipient->id,
                            'name' => $request->recipient->name,
                        ] : null,
                        'created_at' => $request->created_at,
                        'updated_at' => $request->updated_at,
                    ];
                }),
            ];
        });

        // Return the response as JSON
        return response()->json(['foodbanks' => $response ?? []]);
    }

    /**
     * Generate a report of all recipients with their requests.
     *
     * @param int|null $recipientId Optional recipient ID for filtering.
     * @return JsonResponse
     */
    public function generateRecipientRequestReport($recipientId = null)
    {
        // Base query to fetch recipients with their requests and associated foodbanks
        $query = User::where('role', 'recipient')
            ->with([
                'requestsFb' => function ($query) {
                    $query->with('foodbank'); // Include the foodbank for each request
                }
            ]);
    
        // Apply filter if recipientId is provided
        if ($recipientId) {
            $query->where('id', $recipientId);
        }
    
        // Fetch recipients with their relationships
        $recipients = $query->get();
    
        // Format the response
        $response = $recipients->map(function ($recipient) {
            return [
                'id' => $recipient->id,
                'name' => $recipient->name,
                'email' => $recipient->email,
                'recipient_type' => $recipient->recipient_type, // Include recipient type
                'requests' => $recipient->requestsFb->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'type' => $request->type,
                        'quantity' => $request->quantity,
                        'status' => $request->status,
                        'created_at' => $request->created_at,
                        'updated_at' => $request->updated_at,
                        'foodbank' => $request->foodbank ? [
                            'id' => $request->foodbank->id,
                            'name' => $request->foodbank->name,
                            'email' => $request->foodbank->email,
                        ] : null,
                    ];
                }),
            ];
        });
    
        // Return the response as JSON
        return response()->json(['recipients' => $response ?? []]);
    }

    /**
     * Generate a summary report of all donors, foodbanks, and recipients.
     *
     * @return JsonResponse
     */
    public function generateSystemSummaryReport()
    {
        $donors = User::where('role', 'donor')->with(['donations', 'donationRequests'])->get();
        $foodbanks = User::where('role', 'foodbank')->with(['donations', 'donationRequests'])->get();
        $recipients = User::where('role', 'recipient')->with(['requestsFb'])->get();

        return response()->json([
            'summary' => [
                'donors' => $donors,
                'foodbanks' => $foodbanks,
                'recipients' => $recipients,
            ],
        ]);
    }
}