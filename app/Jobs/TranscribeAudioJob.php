<?php

namespace App\Jobs;

use App\Models\Answer;
use App\Services\SpeechToTextService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class TranscribeAudioJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Answer $answer
    ) {}

    public function handle(SpeechToTextService $sttService): void
    {
        try {
            if (!$this->answer->audio_path) {
                throw new \Exception('No audio file found for answer');
            }

            $text = $sttService->transcribe($this->answer->audio_path);

            $this->answer->update(['text' => $text]);

            Log::info('Audio transcribed successfully', [
                'answer_id' => $this->answer->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to transcribe audio', [
                'answer_id' => $this->answer->id,
                'error' => $e->getMessage(),
            ]);

            $this->answer->update(['text' => 'Transcription failed']);

            throw $e;
        }
    }
}
