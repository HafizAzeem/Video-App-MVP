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
    Route::post('/review/generate-prompt', [ReviewController::class, 'generatePrompt'])->name('review.generate-prompt');
    Route::post('/review/confirm', [ReviewController::class, 'confirm'])->name('review.confirm');

    // Production
    Route::get('/production', [VideoController::class, 'index'])->name('production.index');
    Route::post('/production/generate', [VideoController::class, 'generate'])->name('production.generate');
    Route::get('/production/status/{video}', [VideoController::class, 'checkStatus'])->name('production.status');
    
    // Debug route to test job dispatch
    Route::post('/production/test-job', function () {
        \Illuminate\Support\Facades\Log::info('Test job endpoint called');
        $jobCountBefore = \Illuminate\Support\Facades\DB::table('jobs')->count();
        \App\Jobs\GenerateVideoJob::dispatch(1); // Use video ID 1 for testing
        $jobCountAfter = \Illuminate\Support\Facades\DB::table('jobs')->count();
        return response()->json([
            'success' => true,
            'jobs_before' => $jobCountBefore,
            'jobs_after' => $jobCountAfter,
            'job_created' => $jobCountAfter > $jobCountBefore,
        ]);
    })->name('production.test-job');

    // Gallery
    Route::get('/gallery', [VideoController::class, 'gallery'])->name('gallery');
    Route::get('/videos/{video}', [VideoController::class, 'show'])->name('videos.show');
    Route::delete('/videos/{video}', [VideoController::class, 'destroy'])->name('videos.destroy');
});

require __DIR__.'/auth.php';
