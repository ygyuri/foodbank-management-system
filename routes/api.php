<?php

use App\Models\Acl;
use Illuminate\Contracts\Routing\Registrar as RouteContract;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\RequestFBController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DonationRequestController;
use App\Models\Notification;
use App\Http\Controllers\AnalyticsReportController;

//use App\Http\Controllers\RequestFBController;
//use App\Http\Controllers\Api\DonationController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


// Analytics report routes
// Apply the 'auth:api' middleware to all routes under the 'analytics-reports' prefix
Route::middleware('auth:api')->prefix('analytics-reports')->group(function () {
    Route::get('/overview', [AnalyticsReportController::class, 'getOverview']);
    Route::get('/donation-trends', [AnalyticsReportController::class, 'getDonationTrends']);
    Route::get('/foodbank-stats', [AnalyticsReportController::class, 'getFoodbankStats']);
    Route::get('/donor-stats', [AnalyticsReportController::class, 'getDonorStats']);
    Route::get('/recipient-stats', [AnalyticsReportController::class, 'getRecipientStats']);
    Route::get('/donation-trends-by-day', [AnalyticsReportController::class, 'donationTrendsByDay']);
    Route::get('/recipient-demographics', [AnalyticsReportController::class, 'recipientDemographics']);
    Route::get('/foodbank-information', [AnalyticsReportController::class, 'foodbankInformation']);
    Route::get('/donor-information', [AnalyticsReportController::class, 'donorInformation']);
});


// Feedback routes
Route::middleware(['auth:api'])->group(function () {
    Route::get('/feedback', [FeedbackController::class, 'index']);
    Route::post('/feedback', [FeedbackController::class, 'store']);
    Route::get('/feedback/{id}', [FeedbackController::class, 'show']);
    Route::put('/feedback/{id}', [FeedbackController::class, 'update']);
    Route::delete('/feedback/{id}', [FeedbackController::class, 'destroy']);
});

//Notification routes
Route::middleware('auth:api')->group(function () {
    // 🔄 Admin - Fetch All Notifications
    Route::get('/notifications/admin', [NotificationController::class, 'getAllNotificationsForAdmin']);
    // 🔄 Auth User - Fetch Own Notifications
    Route::get('/notifications', [NotificationController::class, 'getUserNotifications']);
    // 🔄 Mark Notification as Read
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
});


// Donation Routes
Route::get('/donations', [DonationController::class, 'index'])->name('donations.index')->middleware('auth:api');     
Route::post('/donations', [DonationController::class, 'store'])->name('donations.store')->middleware('auth:api'); // Create a donation
Route::get('/donations/{donation}', [DonationController::class, 'show'])->name('donations.show'); // View a specific donation
Route::put('/donations/{donation}', [DonationController::class, 'update'])->name('donations.update')->middleware('auth:api'); // Update a donation
Route::delete('/donations/{donation}', [DonationController::class, 'destroy'])->name('donations.destroy')->middleware('auth:api'); // Delete a donation
Route::post('/donations/{donation}/assign-foodbank/{foodbank}', [DonationController::class, 'assignFoodbankToDonation'])->middleware('auth:api');
Route::post('/donations/{id}/update-status', [DonationController::class, 'updateDonationStatus'])->middleware('auth:api');
Route::post('/{donation}/complete', [DonationController::class, 'markAsCompleted'])->middleware('auth:api');            // Mark as completed



//Donation Requests routes
Route::middleware('auth:api')->group(function () {
    // Create a donation request
    Route::post('/donation-requests', [DonationRequestController::class, 'store']);
    // View donation requests
    Route::get('/donation-requests', [DonationRequestController::class, 'index']);
    // Update donation request status
    Route::put('/donation-requests/{id}/status', [DonationRequestController::class, 'updateStatus']);
    // Notifications
     Route::put('/donation-requests/{id}', [DonationRequestController::class, 'update']);

});


//requestfb Approve/reject
Route::middleware(['auth:api'])->group(function () {
    Route::post('/requestsfb/{id}/status', [RequestFBController::class, 'updateRequestStatus']);
    });




Route::namespace('Api')->group(function() {
    Route::post('auth/login', 'AuthController@login');
    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::post('auth/logout', 'AuthController@logout');

        Route::get('/user', 'AuthController@user');

        // Api resource routes
        Route::apiResource('roles', 'RoleController')->middleware('permission:' . Acl::PERMISSION_PERMISSION_MANAGE);
        Route::apiResource('users', 'UserController');
        Route::apiResource('permissions', 'PermissionController')->middleware('permission:' . Acl::PERMISSION_PERMISSION_MANAGE);
        //user approval matrix 
        Route::post('/users/{id}/approve', [UserController::class, 'approveUser']);
        Route::post('/users/{id}/reject', [UserController::class, 'rejectUser']);
        Route::post('/users/{id}/reset-status', [UserController::class, 'resetStatus']);
        //get all users 
        Route::get('/all-users', [UserController::class, 'getAllUsers']);
        //requestfb
        Route::get('/requestsfb', [RequestFBController::class, 'index']);
        Route::post('/requestsfb', [RequestFBController::class, 'store']);
        Route::put('/requestsfb/{id}/status', [RequestFBController::class, 'updateStatus']);
        Route::get('/{id}', [RequestFBController::class, 'show'])->name('show');   
        Route::delete('/{id}', [RequestFBController::class, 'destroy'])->name('destroy');
        Route::put('/requestsfb/{id}', [RequestFBController::class, 'update']);


        // Custom routes
        Route::group(['prefix' => 'users'], function (RouteContract $api) {
            $api->get('{user}/permissions', 'UserController@permissions')->middleware('permission:' . Acl::PERMISSION_PERMISSION_MANAGE);
            $api->put('{user}/permissions', 'UserController@updatePermissions')->middleware('permission:' .Acl::PERMISSION_PERMISSION_MANAGE);
            $api->get('{user}/logs', 'LogController@index');
        });

        Route::get('roles/{role}/permissions', 'RoleController@permissions')->middleware('permission:' . Acl::PERMISSION_PERMISSION_MANAGE);
        Route::get('requests', 'RequestController@index');
    });
});


    

Route::get('/orders', function () {
    $rowsNumber = 8;
    $data = [];
    for ($rowIndex = 0; $rowIndex < $rowsNumber; $rowIndex++) {
        $row = [
            'order_no' => 'LARAVUE' . mt_rand(1000000, 9999999),
            'price' => mt_rand(10000, 999999),
            'status' => randomInArray(['success', 'pending']),
        ];

        $data[] = $row;
    }

    return responseSuccess(['items' => $data]);
});


//routes from my not utilized yet  work
// Subscription routes
Route::middleware(['auth:sanctum'])->prefix('subscriptions')->name('subscriptions.')->group(function () {
    Route::get('/', [SubscriptionController::class, 'index'])->name('index');
    Route::post('/', [SubscriptionController::class, 'store'])->name('store');
    Route::get('/{id}', [SubscriptionController::class, 'show'])->name('show');
    Route::put('/{id}', [SubscriptionController::class, 'update'])->name('update');
    Route::delete('/{id}', [SubscriptionController::class, 'destroy'])->name('destroy');
});


// Route::prefix('users')->name('users.')->group(function () {
//     Route::get('/', [UserController::class, 'index'])->name('index');
//     Route::post('/register', [UserController::class, 'store'])->name('register');
//     Route::get('/{id}', [UserController::class, 'show'])->name('show');
//     Route::put('/{id}', [UserController::class, 'update'])->name('update');
//     Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
//     Route::put('/{id}/approve-reject/{status}', [UserController::class, 'approveRejectUser'])->name('approveRejectUser');
//     Route::get('/filtered', [UserController::class, 'getFilteredUsersByStatus'])->name('filtered');
// });