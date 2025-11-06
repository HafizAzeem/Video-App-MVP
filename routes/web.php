<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\VideoController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// LAS MVP Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Questions
    Route::get('/questions', [QuestionController::class, 'index'])->name('questions.index');
    Route::post('/questions/answer', [QuestionController::class, 'storeAnswer'])->name('questions.answer');
    Route::post('/questions/transcribe', [QuestionController::class, 'transcribeAudio'])->name('questions.transcribe');
    Route::delete('/answers/{answer}', [QuestionController::class, 'deleteAnswer'])->name('answers.delete');

    // Review
    Route::get('/review', [ReviewController::class, 'index'])->name('review.index');
    Route::post('/review/summary', [ReviewController::class, 'generateSummary'])->name('review.summary');
    Route::post('/review/tts', [ReviewController::class, 'generateTTS'])->name('review.tts');
    Route::post('/review/confirm', [ReviewController::class, 'confirm'])->name('review.confirm');

    // Production
    Route::get('/production', [VideoController::class, 'index'])->name('production.index');
    Route::post('/production/generate', [VideoController::class, 'generate'])->name('production.generate');
    Route::get('/production/status/{video}', [VideoController::class, 'checkStatus'])->name('production.status');

    // Gallery
    Route::get('/gallery', [VideoController::class, 'gallery'])->name('gallery');
    Route::get('/videos/{video}', [VideoController::class, 'show'])->name('videos.show');
    Route::delete('/videos/{video}', [VideoController::class, 'destroy'])->name('videos.destroy');
});

require __DIR__.'/auth.php';
