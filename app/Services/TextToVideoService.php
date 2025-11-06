<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TextToVideoService
{
    public function __construct(
        protected string $apiKey = '',
        protected string $provider = 'runway'
    ) {
        $this->apiKey = config('services.text_to_video.api_key');
        $this->provider = config('services.text_to_video.provider', 'runway');
    }

    /**
     * Generate video from text prompt
     */
    public function generate(string $prompt, array $options = []): array
    {
        return match ($this->provider) {
            'runway' => $this->generateWithRunway($prompt, $options),
            'pika' => $this->generateWithPika($prompt, $options),
            default => throw new \Exception("Unsupported video provider: {$this->provider}"),
        };
    }

    /**
     * Check video generation status
     */
    public function checkStatus(string $taskId): array
    {
        return match ($this->provider) {
            'runway' => $this->checkRunwayStatus($taskId),
            'pika' => $this->checkPikaStatus($taskId),
            default => throw new \Exception("Unsupported video provider: {$this->provider}"),
        };
    }

    protected function generateWithRunway(string $prompt, array $options): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(120)
                ->post('https://api.runwayml.com/v1/generations', [
                    'model' => 'gen3a_turbo',
                    'prompt' => $prompt,
                    'duration' => $options['duration'] ?? 5,
                    'ratio' => $options['ratio'] ?? '16:9',
                ]);

            if ($response->failed()) {
                throw new \Exception('Runway API failed: ' . $response->body());
            }

            return [
                'task_id' => $response->json('id'),
                'status' => 'processing',
                'provider' => 'runway',
            ];
        } catch (\Exception $e) {
            Log::error('Runway video generation failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function checkRunwayStatus(string $taskId): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->get("https://api.runwayml.com/v1/generations/{$taskId}");

            if ($response->failed()) {
                throw new \Exception('Runway status check failed: ' . $response->body());
            }

            $data = $response->json();

            return [
                'status' => $data['status'], // processing, completed, failed
                'video_url' => $data['output']['url'] ?? null,
                'progress' => $data['progress'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Runway status check failed', [
                'error' => $e->getMessage(),
                'task_id' => $taskId,
            ]);

            throw $e;
        }
    }

    protected function generateWithPika(string $prompt, array $options): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->timeout(120)
                ->post('https://api.pika.art/generate', [
                    'prompt' => $prompt,
                    'options' => $options,
                ]);

            if ($response->failed()) {
                throw new \Exception('Pika API failed: ' . $response->body());
            }

            return [
                'task_id' => $response->json('job_id'),
                'status' => 'processing',
                'provider' => 'pika',
            ];
        } catch (\Exception $e) {
            Log::error('Pika video generation failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function checkPikaStatus(string $taskId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->get("https://api.pika.art/jobs/{$taskId}");

            if ($response->failed()) {
                throw new \Exception('Pika status check failed: ' . $response->body());
            }

            $data = $response->json();

            return [
                'status' => $data['status'],
                'video_url' => $data['result_url'] ?? null,
                'progress' => $data['progress'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Pika status check failed', [
                'error' => $e->getMessage(),
                'task_id' => $taskId,
            ]);

            throw $e;
        }
    }
}
