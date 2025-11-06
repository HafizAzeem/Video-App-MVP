<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SpeechToTextService
{
    public function __construct(
        protected string $apiKey = '',
        protected string $provider = 'openai'
    ) {
        $this->apiKey = config('services.openai.api_key');
        $this->provider = config('services.stt.provider', 'openai');
    }

    /**
     * Transcribe audio file to text using OpenAI Whisper API
     */
    public function transcribe(UploadedFile|string $audio): string
    {
        return match ($this->provider) {
            'openai' => $this->transcribeWithOpenAI($audio),
            'google' => $this->transcribeWithGoogle($audio),
            default => throw new \Exception("Unsupported STT provider: {$this->provider}"),
        };
    }

    protected function transcribeWithOpenAI(UploadedFile|string $audio): string
    {
        try {
            $audioPath = $audio instanceof UploadedFile
                ? $audio->getRealPath()
                : Storage::path($audio);

            $response = Http::withToken($this->apiKey)
                ->attach('file', file_get_contents($audioPath), basename($audioPath))
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => 'whisper-1',
                    'language' => 'en',
                ]);

            if ($response->failed()) {
                throw new \Exception('OpenAI Whisper API failed: ' . $response->body());
            }

            return $response->json('text');
        } catch (\Exception $e) {
            Log::error('STT transcription failed', [
                'error' => $e->getMessage(),
                'provider' => 'openai',
            ]);

            throw $e;
        }
    }

    protected function transcribeWithGoogle(UploadedFile|string $audio): string
    {
        // Placeholder for Google Speech-to-Text API integration
        throw new \Exception('Google STT not yet implemented');
    }

    /**
     * Validate audio file format
     */
    public function validateAudioFile(UploadedFile $file): bool
    {
        $allowedMimes = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/webm', 'audio/ogg'];
        $maxSize = 25 * 1024; // 25MB in KB

        return in_array($file->getMimeType(), $allowedMimes)
            && $file->getSize() <= ($maxSize * 1024);
    }
}
