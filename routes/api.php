<?php

use App\Http\Controllers\openaiController;
use App\Http\Controllers\paymentController;
use App\Http\Controllers\topicController;
use App\Http\Controllers\uploadController;
use App\Http\Controllers\userController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post("/v1/prompt-model", [openaiController ::class, 'promptModel']);
Route::post("/v1/normal-signin", [userController ::class, 'normalSignin']);
Route::post("/v1/normal-login", [userController ::class, 'normalLogin']);
Route::post("/v1/callback-status", [paymentController::class,'callBackFunction']);

Route::middleware('auth:sanctum')->group(function () {
   /* your protected routes */
   Route::post("/v1/save-topic", [topicController ::class, 'saveSubtopic']);
   Route::post("/v1/saved-topics", [topicController ::class, 'returnSavedTopics']);
   Route::post("/v1/get-topic-list", [topicController ::class, 'getTopicList']);
   Route::post("/v1/get-user", [userController ::class, 'getUser']);
   Route::post("/v1/delete-topic", [topicController::class,'deleteTopic']);
   Route::post("/v1/delete-chat", [topicController::class,'deleteChats']);
   Route::post("/v1/save-prompt", [topicController::class,'savePrompt']);
   Route::post("/v1/saved-chat", [topicController::class,'savedChat']);
   Route::post("/v1/get-video", [topicController::class,'getAvideo']);
   Route::post("/v1/save-video", [topicController::class,'saveVideo']);
   Route::post("/v1/upload-image", [uploadController::class,'uploadImage']);
   Route::post("/v1/upload-file", [uploadController::class,'uploadFile']);
   Route::post("/v1/get-media", [uploadController::class,'getMedia']);
   Route::post("/v1/delete-media", [uploadController::class,'deleteMedia']);
   Route::post("/v1/rename-media", [uploadController::class,'renameMedia']);
   Route::post("/v1/delete-media", [uploadController::class,'deleteMedia']);
   Route::post("/v1/mobile-payment", [paymentController::class,'mobilePayment']);
});
