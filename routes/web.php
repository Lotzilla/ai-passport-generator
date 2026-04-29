<?php

use App\Http\Controllers\PassportPhotoController;
use App\Http\Controllers\SupportChatController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
});

// Single endpoint: handles upload + full agent pipeline + response
Route::post('/process-photo', [PassportPhotoController::class, 'process'])->name('photo.process');
Route::get('/download-photo/{filename}', [PassportPhotoController::class, 'download'])->name('photo.download');

// Support chat bot
Route::post('/support-chat', [SupportChatController::class, 'handle'])->name('support.chat');
