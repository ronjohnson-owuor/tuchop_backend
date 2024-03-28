<?php

use App\Http\Controllers\notesController;
use App\Http\Controllers\openaiController;
use App\Http\Controllers\paymentController;
use App\Http\Controllers\topicController;
use App\Http\Controllers\uploadController;
use App\Http\Controllers\userController;
use Illuminate\Support\Facades\Route;


Route::post("/v1/get-topic", [openaiController ::class, 'getTopic']);
Route::post("/v1/normal-signin", [userController ::class, 'normalSignin']);
Route::post("/v1/prompt-image", [openaiController ::class, 'promtImage']);
Route::post("/v1/extract-text", [openaiController ::class, 'originalTextExtract']);
Route::post("/v1/normal-login", [userController ::class, 'normalLogin']);
Route::post("/v1/get-plan", [paymentController ::class, 'getPlans']);
Route::get("/v1/cardpayment-callback", [paymentController::class,'cardCallback']);
Route::post("/v1/get-notes", [notesController ::class, 'getNotes']);
Route::post("/v1/get-topic-notes", [notesController ::class, 'getTopic']);


Route::middleware('auth:sanctum')->group(function () {
   /* your protected routes */
   Route::post("/v1/save-edited-notes", [notesController ::class, 'saveEditedNotes']);
   Route::post("/v1/get-saved-notes", [notesController ::class, 'getSavedNotes']);
   Route::post("/v1/delete-notes", [notesController ::class, 'deleteNotes']);
   Route::post("/v1/get-edit-notes", [notesController ::class, 'getEditNotes']);
   Route::post("/v1/update-topic", [topicController ::class, 'updateSubTopics']);
   Route::post("/v1/save-topic", [topicController ::class, 'saveSubtopic']);
   Route::post("/v1/saved-topics", [topicController ::class, 'returnSavedTopics']);
   Route::post("/v1/get-topic-list", [topicController ::class, 'getTopicList']);
   Route::post("/v1/ask-file", [openaiController ::class, 'askPDF']);
   Route::post("/v1/ask-image", [openaiController ::class, 'askImage']);
   Route::post("/v1/get-user", [userController ::class, 'getUser']);
   Route::post("/v1/delete-topic", [topicController::class,'deleteTopic']);
   Route::post("/v1/delete-chat", [topicController::class,'deleteChats']);
   Route::post("/v1/save-prompt", [topicController::class,'savePrompt']);
   Route::post("/v1/saved-chat", [topicController::class,'savedChat']);
   Route::post("/v1/save-notes", [notesController ::class, 'saveNotes']);
   Route::post("/v1/save-video", [topicController::class,'saveVideo']);
   Route::post("/v1/upload-image", [uploadController::class,'uploadImage']);
   Route::post("/v1/upload-file", [uploadController::class,'uploadFile']);
   Route::post("/v1/get-media", [uploadController::class,'getMedia']);
   Route::post("/v1/delete-media", [uploadController::class,'deleteMedia']);
   Route::post("/v1/rename-media", [uploadController::class,'renameMedia']);
   Route::post("/v1/delete-media", [uploadController::class,'deleteMedia']);
   Route::post("/v1/card-payment", [paymentController::class,'cardPayment']);
   Route::post("/v1/confirm-payment", [paymentController::class,'confirmReceipt']);
   Route::post("/v1/normal-chat", [openaiController ::class, 'normalChat']);
   Route::post("/v1/get-video", [topicController::class,'getAvideo']);
});

