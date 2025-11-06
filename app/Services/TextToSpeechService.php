<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TextToSpeechService
{
    public function __construct(
        protected string $apiKey = '',
        protected string $provider = 'openai'
    ) {
        $this->apiKey = config('services.openai.api_key');
        $this->provider = config('services.tts.provider', 'openai');
    }

    /**
     * Generate audio from text using OpenAI TTS API
     */
    public function generate(string $text, string $voice = 'alloy'): string
    {
        return match ($this->provider) {
            'openai' => $this->generateWithOpenAI($text, $voice),
            'elevenlabs' => $this->generateWithElevenLabs($text, $voice),
            default => throw new \Exception("Unsupported TTS provider: {$this->provider}"),
        };
    }

    protected function generateWithOpenAI(string $text, string $voice): string
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->post('https://api.openai.com/v1/audio/speech', [
                    'model' => 'tts-1',
                    'input' => $text,
                    'voice' => $voice, // alloy, echo, fable, onyx, nova, shimmer
                ]);

            if ($response->failed()) {
                throw new \Exception('OpenAI TTS API failed: ' . $response->body());
            }

            $filename = 'tts/' . uniqid() . '.mp3';
            Storage::put($filename, $response->body());

            return $filename;
        } catch (\Exception $e) {
            Log::error('TTS generation failed', [
                'error' => $e->getMessage(),
                'provider' => 'openai',
            ]);

            throw $e;
        }
    }

    protected function generateWithElevenLabs(string $text, string $voiceId): string
    {
        try {
            $apiKey = config('services.elevenlabs.api_key');
            $voiceId = $voiceId ?: config('services.elevenlabs.voice_id');

            $response = Http::withHeaders([
                'xi-api-key' => $apiKey,
            ])->post("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}", [
                'text' => $text,
                'model_id' => 'eleven_monolingual_v1',
            ]);

            if ($response->failed()) {
                throw new \Exception('ElevenLabs TTS API failed: ' . $response->body());
            }

            $filename = 'tts/' . uniqid() . '.mp3';
            Storage::put($filename, $response->body());

            return $filename;
        } catch (\Exception $e) {
            Log::error('TTS generation failed', [
                'error' => $e->getMessage(),
                'provider' => 'elevenlabs',
            ]);

            throw $e;
        }
    }
}
