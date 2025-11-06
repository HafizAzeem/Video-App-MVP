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

    public function __construct(
        public User $user,
        public string $text
    ) {}

    public function handle(TextToSpeechService $ttsService): void
    {
        try {
            $audioPath = $ttsService->generate($this->text);

            session(['summary_audio_path' => $audioPath]);

            Log::info('TTS generated successfully', [
                'user_id' => $this->user->id,
                'audio_path' => $audioPath,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate TTS', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
