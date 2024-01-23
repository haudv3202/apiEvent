<?php

use App\Http\Controllers\Api\GoogleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\atendanceController;
use App\Http\Controllers\chatController;
use App\Http\Controllers\eventController;
use App\Http\Controllers\notificationController;
use App\Http\Controllers\participantsController;
use App\Http\Controllers\feedbackController;
use App\Http\Controllers\resourceController;
use App\Http\Controllers\keywordsController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


// Google Sign In
Route::post('/get-google-sign-in-url', [GoogleController::class, 'getGoogleSignInUrl']);
Route::get('/auth/google', [GoogleController::class, 'loginCallback']);
Route::get('/callback', [GoogleController::class, 'callback']);
//
//Route::post('register',[UserAuthController::class,'register']);
Route::post('login',[UserAuthController::class,'login']);
Route::post('reset-password', [UserAuthController::class,'sendMail']);
Route::post('check-password', [UserAuthController::class,'checkPass']);
Route::put('reset-password/{token}', [UserAuthController::class,'reset']);
//Route::apiResource('employees',EmployeeController::class)->middleware('auth:api');

//Route::apiResource('atendances',atendanceController::class)->middleware('auth:api');
Route::middleware('auth:api')->prefix('attendances')->group(function() {
    Route::get('/join/{id_event}',[atendanceController::class,'index']);
    Route::post('/add',[atendanceController::class,'addEmail']);
    Route::post('/',[atendanceController::class,'store']);
    Route::get('/{id}',[atendanceController::class,'show']);
    Route::patch('/{id}',[atendanceController::class,'update']);
    Route::delete('/{id}',[atendanceController::class,'destroy']);
});
//Route::apiResource('feedback',feedbackController::class)->middleware('auth:api');
Route::middleware('auth:api')->prefix('feedback')->group(function() {
    Route::get('/{id_event}',[feedbackController::class,'index']);
    Route::get('/show/{id}',[feedbackController::class,'show']);
    Route::post('/',[feedbackController::class,'store']);
    Route::patch('/{id}',[feedbackController::class,'update']);
    Route::delete('/{id}',[feedbackController::class,'destroy']);
});
//Route::apiResource('notification',notificationController::class)->middleware('auth:api');
Route::middleware('auth:api')->prefix('notification')->group(function() {
    Route::post('/send',[notificationController::class,'create']);
//    Route::get('/test',[notificationController::class,'test']);
    Route::get('/',[notificationController::class,'index']);
//    Route::get('/search',[notificationController::class,'search']);
    Route::get('/getNotificationDel/{id}',[notificationController::class,'showNotificationDel']);
    Route::get('/restore/{id}',[notificationController::class,'restoreNotificationDel']);
    Route::get('/settings/{id}',[notificationController::class,'getSettingsNotification']);
    Route::post('/',[notificationController::class,'store']);
    Route::get('/show/{id}',[notificationController::class,'show']);
    Route::patch('/{id}',[notificationController::class,'update']);
    Route::delete('/{id}',[notificationController::class,'destroy']);
});

Route::get('/searchKeyword',[keywordsController::class,'searchEvent'])->middleware('auth:api');
Route::middleware('auth:api')->prefix('keywords')->group(function() {
    Route::get('/',[keywordsController::class,'index']);
    Route::get('/{id}',[keywordsController::class,'show']);
    Route::post('/',[keywordsController::class,'store']);
    Route::patch('/{id}',[keywordsController::class,'update']);
    Route::delete('/{id}',[keywordsController::class,'destroy']);
});

Route::get('eventJoin',[eventController::class,'eventJoin'])->middleware('auth:api');
Route::prefix('event')->group(function() {
    Route::get('/{id}',[eventController::class,'show']);
    Route::middleware(['auth:api'])->group(function () {
        Route::get('/',[eventController::class,'index']);
        Route::post('/', [eventController::class, 'store']);
        Route::get('/notification', [eventController::class, 'indexNotification']);
        Route::patch('/{id}', [eventController::class, 'update']);
        Route::delete('/{id}', [eventController::class, 'destroy']);
    });
});

//    Route::apiResource('event',eventController::class)->middleware('auth:api');

//Test api in swagger donn't need token
Route::apiResource('participants',participantsController::class)->middleware('auth:api');
Route::patch('updateUser',[participantsController::class, 'updateUser'])->middleware('auth:api');
Route::post('importUser',[participantsController::class,'importUser'])->middleware('auth:api');
Route::get('resourceByEventID/{event_id}',[resourceController::class,'GetRecordByEventId'])->middleware('auth:api');
Route::apiResource('resource',resourceController::class)->middleware('auth:api');

//Search
Route::post('searchUser',[participantsController::class,'getUserByEmailAndPhone'])->middleware('auth:api');
Route::post('searchEvent',[eventController::class,'searchEvent'])->middleware('auth:api');

//Real Time
Route::post('chat', [chatController::class, 'sendMessage']);
Route::get('messageBox/{event_id}', [chatController::class, 'showMessageInEvent']);

//Event statistics
Route::get('statistics',[eventController::class,'Statistics'])->middleware('auth:api');
Route::get('eventStatisticsStudent',[eventController::class,'StatisticsStudentJoin']);
Route::get('getNearstEvent',[eventController::class,'getNearstEvent'])->middleware('auth:api');
Route::post('eventStatistics',[eventController::class,'eventStatistics'])->middleware('auth:api');
Route::post('recreateEvent',[eventController::class,'recreateEvent'])->middleware('auth:api');

