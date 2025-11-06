# LAS MVP - Remaining Implementation Files

This file contains all remaining code snippets to complete the LAS (Learn And Share) MVP.

## Jobs (Copy code below to respective files)

### app/Jobs/GenerateTTSJob.php
```php
<?php
namespace App\Jobs;

use App\Models\User;
use App\Services\TextToSpeechService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateTTSJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user, public string $text) {}

    public function handle(TextToSpeechService $ttsService): void
    {
        try {
            $audioPath = $ttsService->generate($this->text);
            session(['summary_audio_path' => $audioPath]);
            Log::info('TTS generated', ['user_id' => $this->user->id]);
        } catch (\Exception $e) {
            Log::error('TTS failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
```

### app/Jobs/GenerateSummaryJob.php
```php
<?php
namespace App\Jobs;

use App\Models\User;
use App\Services\GPTService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateSummaryJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user) {}

    public function handle(GPTService $gptService): void
    {
        $answers = $this->user->answers()->with('question')->get()->pluck('text')->toArray();
        $summary = $gptService->summarizeAnswers($answers);
        session(['summary' => $summary]);
    }
}
```

### app/Jobs/GenerateVideoJob.php
```php
<?php
namespace App\Jobs;

use App\Models\Video;
use App\Services\GPTService;
use App\Services\TextToVideoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateVideoJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Video $video) {}

    public function handle(TextToVideoService $videoService, GPTService $gptService): void
    {
        try {
            $this->video->update(['status' => 'processing']);

            // Generate video prompt
            $videoPrompt = $gptService->generateVideoPrompt($this->video->summary_text);

            // Start video generation
            $result = $videoService->generate($videoPrompt);

            // Store task ID
            $this->video->update([
                'metadata' => ['task_id' => $result['task_id'], 'provider' => $result['provider']],
            ]);

            // Poll for completion (simplified - use queue retry in production)
            $this->pollVideoStatus($videoService);

        } catch (\Exception $e) {
            $this->video->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            Log::error('Video generation failed', ['video_id' => $this->video->id, 'error' => $e->getMessage()]);
        }
    }

    protected function pollVideoStatus(TextToVideoService $videoService): void
    {
        $taskId = $this->video->metadata['task_id'] ?? null;
        if (!$taskId) return;

        $maxAttempts = 60;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            sleep(10);
            $status = $videoService->checkStatus($taskId);

            if ($status['status'] === 'completed') {
                $this->video->update([
                    'status' => 'completed',
                    'video_url' => $status['video_url'],
                ]);
                return;
            }

            if ($status['status'] === 'failed') {
                $this->video->update(['status' => 'failed', 'error_message' => 'Video generation failed']);
                return;
            }

            $attempt++;
        }
    }
}
```

## Routes (routes/web.php)

Add these routes after the existing ones:

```php
// LAS MVP Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Questions
    Route::get('/questions', [App\Http\Controllers\QuestionController::class, 'index'])->name('questions.index');
    Route::post('/questions/answer', [App\Http\Controllers\QuestionController::class, 'storeAnswer'])->name('questions.answer');
    Route::post('/questions/transcribe', [App\Http\Controllers\QuestionController::class, 'transcribeAudio'])->name('questions.transcribe');
    Route::delete('/answers/{answer}', [App\Http\Controllers\QuestionController::class, 'deleteAnswer'])->name('answers.delete');

    // Review
    Route::get('/review', [App\Http\Controllers\ReviewController::class, 'index'])->name('review.index');
    Route::post('/review/summary', [App\Http\Controllers\ReviewController::class, 'generateSummary'])->name('review.summary');
    Route::post('/review/tts', [App\Http\Controllers\ReviewController::class, 'generateTTS'])->name('review.tts');
    Route::post('/review/confirm', [App\Http\Controllers\ReviewController::class, 'confirm'])->name('review.confirm');

    // Production
    Route::get('/production', [App\Http\Controllers\VideoController::class, 'index'])->name('production.index');
    Route::post('/production/generate', [App\Http\Controllers\VideoController::class, 'generate'])->name('production.generate');
    Route::get('/production/status/{video}', [App\Http\Controllers\VideoController::class, 'checkStatus'])->name('production.status');

    // Gallery
    Route::get('/gallery', [App\Http\Controllers\VideoController::class, 'gallery'])->name('gallery');
    Route::get('/videos/{video}', [App\Http\Controllers\VideoController::class, 'show'])->name('videos.show');
    Route::delete('/videos/{video}', [App\Http\Controllers\VideoController::class, 'destroy'])->name('videos.destroy');
});
```

## Environment Variables (.env additions)

```env
# OpenAI Configuration
OPENAI_API_KEY=your-openai-api-key-here

# ElevenLabs Configuration (optional)
ELEVENLABS_API_KEY=your-elevenlabs-api-key
ELEVENLABS_VOICE_ID=default-voice-id

# Text-to-Video Configuration
TEXT_TO_VIDEO_PROVIDER=runway
TEXT_TO_VIDEO_API_KEY=your-runway-api-key

# Service Providers
STT_PROVIDER=openai
TTS_PROVIDER=openai
GPT_MODEL=gpt-4-turbo
```

## Configuration File (config/services.php additions)

Add to the services array:

```php
'openai' => [
    'api_key' => env('OPENAI_API_KEY'),
],

'elevenlabs' => [
    'api_key' => env('ELEVENLABS_API_KEY'),
    'voice_id' => env('ELEVENLABS_VOICE_ID'),
],

'text_to_video' => [
    'provider' => env('TEXT_TO_VIDEO_PROVIDER', 'runway'),
    'api_key' => env('TEXT_TO_VIDEO_API_KEY'),
],

'stt' => [
    'provider' => env('STT_PROVIDER', 'openai'),
],

'tts' => [
    'provider' => env('TTS_PROVIDER', 'openai'),
],

'gpt' => [
    'model' => env('GPT_MODEL', 'gpt-4-turbo'),
],
```

## Database Seeder (database/seeders/DatabaseSeeder.php)

```php
<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create default questions
        $questions = [
            ['text' => 'What is your biggest dream or aspiration?', 'order' => 1],
            ['text' => 'What challenge have you overcome that made you stronger?', 'order' => 2],
            ['text' => 'What makes you unique or special?', 'order' => 3],
            ['text' => 'What message would you share with the world?', 'order' => 4],
        ];

        foreach ($questions as $question) {
            Question::create($question);
        }
    }
}
```

## Next Steps to Complete

1. Run migrations:
```bash
php artisan migrate
php artisan db:seed
```

2. Create Vue components and pages (files provided separately)

3. Install any missing npm packages if needed

4. Link storage:
```bash
php artisan storage:link
```

5. Configure queue driver in .env:
```env
QUEUE_CONNECTION=database
```

6. Run queue worker:
```bash
php artisan queue:work
```
