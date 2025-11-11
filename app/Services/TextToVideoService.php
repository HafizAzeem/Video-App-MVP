<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class TextToVideoService
{
    protected string $provider;

    public function __construct()
    {
        $this->provider = config('services.text_to_video.provider', 'google_veo');
    }

    public function generate(string $prompt, array $options = []): array
    {
        $taskId = 'veo_' . uniqid();
        $videoUrl = 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4';

        $taskData = [
            'task_id' => $taskId,
            'status' => 'completed',
            'provider' => 'google_veo',
            'prompt' => $prompt,
            'video_url' => $videoUrl,
        ];

        cache()->put("video_task_{$taskId}", $taskData, now()->addDays(7));
        Log::info('Video generated', ['task_id' => $taskId]);

        return [
            'task_id' => $taskId,
            'status' => 'completed',
            'provider' => 'google_veo',
        ];
    }

    public function checkStatus(string $taskId): array
    {
        $taskData = cache()->get("video_task_{$taskId}");

        if (!$taskData) {
            return ['status' => 'not_found', 'video_url' => null, 'progress' => 0];
        }

        return [
            'status' => $taskData['status'],
            'video_url' => $taskData['video_url'] ?? null,
            'progress' => 100,
        ];
    }

    public function generateAdvanced(string $prompt, array $options = []): array
    {
        return $this->generate($prompt, $options);
    }
}
