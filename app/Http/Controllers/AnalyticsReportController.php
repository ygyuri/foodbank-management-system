<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\RequestFB;
use App\Models\DonationRequest;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsReportController extends Controller
{
    public function getOverview()
    {
        Log::info('Fetching overview statistics started.');
        try {
            Log::info('Counting total donations.');
            $totalDonations = Donation::count();
            Log::info('Total Donations: ' . $totalDonations);

            Log::info('Counting total foodbanks.');
            $totalFoodbanks = User::foodbanks()->count();
            Log::info('Total Foodbanks: ' . $totalFoodbanks);

            Log::info('Counting total donors.');
            $totalDonors = User::donors()->count();
            Log::info('Total Donors: ' . $totalDonors);

            Log::info('Counting total recipients.');
            $totalRecipients = User::recipients()->count();
            Log::info('Total Recipients: ' . $totalRecipients);


            // Fetch historical data for the last 7 days
            $historicalDonations = Donation::getHistoricalData(7);
            $historicalFoodbanks = User::getHistoricalData('foodbanks', 7);
            $historicalDonors = User::getHistoricalData('donors', 7);
            $historicalRecipients = User::getHistoricalData('recipients', 7);

            Log::info('Overview statistics fetched successfully.');
            return response()->json([
                'total_donations' => $totalDonations,
                'total_foodbanks' => $totalFoodbanks,
                'total_donors' => $totalDonors,
                'total_recipients' => $totalRecipients,
                'historical_data' => [
                    'donations' => $historicalDonations,
                    'foodbanks' => $historicalFoodbanks,
                    'donors' => $historicalDonors,
                    'recipients' => $historicalRecipients,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching overview statistics: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch overview statistics'], 500);
        }
    }

    /**
     * Get donation trends (grouped by month or day) with status metrics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDonationTrends(Request $request)
    {
        Log::info('Fetching donation trends started.');

        try {
            $groupBy = $request->input('group_by', 'month'); // Default is month
            Log::info("Grouping donations by: $groupBy");

            $query = Donation::query();

            if ($groupBy === 'day') {
                $query->selectRaw('DATE(created_at) as date, COUNT(*) as total_count')
                    ->selectRaw('SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count')
                    ->selectRaw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count')
                    ->selectRaw('SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected_count')
                    ->groupBy('date')
                    ->orderBy('date', 'asc');
            } else {
                $query->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as total_count')
                    ->selectRaw('SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count')
                    ->selectRaw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count')
                    ->selectRaw('SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected_count')
                    ->groupBy('month')
                    ->orderBy('month', 'asc');
            }

            $donations = $query->get();

            Log::info('Donation trends fetched successfully.');
            return response()->json(['data' => $donations]);
        } catch (\Exception $e) {
            Log::error('Error fetching donation trends: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch donation trends'], 500);
        }
    }

   /**
     * Get foodbank statistics (total donations received and distributed).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFoodbankStats(Request $request)
    {
        Log::info('Fetching foodbank statistics started.');

        try {
            // Extract filters from the request
            $foodbankId = $request->query('foodbank_id');
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');
            $status = $request->query('status'); // Optional status filter

            Log::info('Applying filters', [
                'foodbank_id' => $foodbankId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'status' => $status,
            ]);

            // Base query for foodbanks
            $query = User::foodbanks()->with([
                'donationRequests' => function ($q) use ($dateFrom, $dateTo, $status) {
                    if ($dateFrom && $dateTo) {
                        $q->whereBetween('created_at', [$dateFrom, $dateTo]);
                    }
                    if ($status) {
                        $q->where('status', $status);
                    }
                },
                'donations' => function ($q) use ($dateFrom, $dateTo, $status) {
                    if ($dateFrom && $dateTo) {
                        $q->whereBetween('created_at', [$dateFrom, $dateTo]);
                    }
                    if ($status) {
                        $q->where('status', $status);
                    }
                }
            ]);

            // Apply foodbank ID filter if provided
            if ($foodbankId) {
                $query->where('id', $foodbankId);
            }

            // Execute the query
            $foodbanks = $query->get();

            // Transform the data into the required structure
            $transformedData = $foodbanks->map(function ($foodbank) {
                return [
                    'name' => $foodbank->name,
                    'total_requests' => $foodbank->donationRequests->count(),
                    'total_donations' => $foodbank->donations->count(),
                ];
            });

            Log::info('Foodbank statistics fetched successfully.', ['count' => $transformedData->count()]);

            return response()->json([
                'success' => true,
                'data' => $transformedData,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching foodbank statistics: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch foodbank statistics',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Get donor statistics (total donations made).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDonorStats()
    {
        Log::info('Fetching donor statistics started.');
        try {
            Log::info('Querying donor statistics with donations count.');
            $donors = User::donors()->withCount('donations')->get();

            Log::info('Donor statistics fetched successfully.');
            return response()->json($donors);
        } catch (\Exception $e) {
            Log::error('Error fetching donor statistics: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch donor statistics'], 500);
        }
    }

    /**
     * Get recipient statistics (total requests fulfilled, approved, and related requests with derived metrics).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecipientStats(Request $request)
    {
        Log::info('Fetching recipient statistics started.');

        try {
            // Extract filters from the request
            $recipientId = $request->query('recipient_id', null);
            $dateFrom = $request->query('date_from', null);
            $dateTo = $request->query('date_to', null);
            $status = $request->query('status', null);

            Log::info('Applying filters', [
                'recipient_id' => $recipientId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'status' => $status,
            ]);

            // Base query for recipients
            $query = User::recipients()
                ->with([
                    'requestsFb' => function ($q) use ($dateFrom, $dateTo, $status) {
                        if ($dateFrom && $dateTo) {
                            $q->whereBetween('created_at', [$dateFrom, $dateTo]);
                        }
                        if ($status) {
                            $q->where('status', $status);
                        }
                    }
                ])
                ->withCount([
                    'requestsFb as total_fulfilled_requests' => function ($q) {
                        $q->where('status', 'fulfilled');
                    },
                    'requestsFb as total_approved_requests' => function ($q) {
                        $q->where('status', 'approved');
                    },
                    'requestsFb as total_rejected_requests' => function ($q) {
                        $q->where('status', 'rejected');
                    },
                    'requestsFb as total_pending_requests' => function ($q) {
                        $q->where('status', 'pending');
                    },
                    'requestsFb as total_requests'
                ])
                ->select(['id', 'name', 'email']);

            // Apply recipient ID filter if provided
            if ($recipientId) {
                $query->where('id', $recipientId);
            }

            // Execute the query
            $recipients = $query->get()->map(function ($recipient) {
                // Calculate approval and fulfillment rates
                $recipient->approval_rate = $recipient->total_requests > 0
                    ? round(($recipient->total_approved_requests / $recipient->total_requests) * 100, 2)
                    : 0;

                $recipient->fulfillment_rate = $recipient->total_requests > 0
                    ? round(($recipient->total_fulfilled_requests / $recipient->total_requests) * 100, 2)
                    : 0;

                // Ensure all required fields are included
                return [
                    'id' => $recipient->id,
                    'name' => $recipient->name,
                    'email' => $recipient->email,
                    'approval_rate' => $recipient->approval_rate,
                    'fulfillment_rate' => $recipient->fulfillment_rate,
                    'total_requests' => $recipient->total_requests,
                    'total_approved_requests' => $recipient->total_approved_requests,
                    'total_rejected_requests' => $recipient->total_rejected_requests,
                    'total_pending_requests' => $recipient->total_pending_requests,
                    'total_fulfilled_requests' => $recipient->total_fulfilled_requests,
                    'requests_fb' => $recipient->requestsFb,
                ];
            });

            Log::info('Recipient statistics fetched successfully.', ['count' => $recipients->count()]);
            Log::info('Recipient statistics result', ['data' => $recipients]);

            return response()->json([
                'success' => true,
                'data' => $recipients,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching recipient statistics: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch recipient statistics',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Get donation trends by day.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function donationTrendsByDay()
    {
        Log::info('Fetching donation trends by day started.');
        try {
            Log::info('Querying donation trends grouped by day.');
            $donationTrends = Donation::selectRaw('DATE(created_at) as date, SUM(quantity) as total_quantity')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            Log::info('Donation trends by day fetched successfully.');
            return response()->json($donationTrends);
        } catch (\Exception $e) {
            Log::error('Error fetching donation trends by day: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch donation trends by day'], 500);
        }
    }

    /**
     * Get recipient demographics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function recipientDemographics()
    {
        Log::info('Fetching recipient demographics started.');
        try {
            Log::info('Querying recipient demographics grouped by sex.');
            $recipientDemographics = User::recipients()
                ->select('sex', DB::raw('COUNT(*) as total'))
                ->groupBy('sex')
                ->get();

            Log::info('Recipient demographics fetched successfully.');
            return response()->json($recipientDemographics);
        } catch (\Exception $e) {
            Log::error('Error fetching recipient demographics: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch recipient demographics'], 500);
        }
    }

    /**
     * Get foodbank information.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function foodbankInformation()
    {
        Log::info('Fetching foodbank information started.');
        try {
            Log::info('Querying foodbank information with donations received and distributed.');
            $foodbankInformation = User::foodbanks()
                ->withCount(['donationsReceived', 'donationsDistributed'])
                ->get();

            Log::info('Foodbank information fetched successfully.');
            return response()->json($foodbankInformation);
        } catch (\Exception $e) {
            Log::error('Error fetching foodbank information: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch foodbank information'], 500);
        }
    }

    /**
     * Get donor information.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function donorInformation()
    {
        Log::info('Fetching donor information started.');
        try {
            Log::info('Querying donor information with donations count.');
            $donorInformation = User::donors()
                ->withCount('donations')
                ->get();

            Log::info('Donor information fetched successfully.');
            return response()->json($donorInformation);
        } catch (\Exception $e) {
            Log::error('Error fetching donor information: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch donor information'], 500);
        }
    }
}
