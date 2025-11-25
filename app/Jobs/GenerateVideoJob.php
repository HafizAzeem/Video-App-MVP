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

    public function __construct(
        public int $videoId
    ) {}

    public function handle(TextToVideoService $videoService, GPTService $gptService): void
    {
        $video = Video::find($this->videoId);

        if (! $video) {
            Log::warning('GenerateVideoJob received missing video', ['video_id' => $this->videoId]);

            return;
        }

        if ($video->status !== 'pending') {
            Log::info('Video already processed, skipping GenerateVideoJob', [
                'video_id' => $video->id,
                'status' => $video->status,
            ]);

            return;
        }

        try {
            $prompt = $video->prompt ?: $gptService->generateVideoPrompt($video->summary_text);

            if (! $video->prompt) {
                $video->update(['prompt' => $prompt]);
            }

            $video->update([
                'status' => 'processing',
                'progress' => 0,
            ]);

            $result = $videoService->startGeneration($prompt);

            // Test mode returns a completed result immediately
            if (($result['status'] ?? null) === 'completed' && isset($result['video_url'])) {
                $video->update([
                    'status' => 'completed',
                    'video_url' => $result['video_url'],
                    'provider' => $result['provider'] ?? 'simulated',
                    'mode' => $result['mode'] ?? config('services.text_to_video.mode', 'test'),
                    'progress' => 100,
                ]);

                return;
            }

            $operationName = $result['operation_name'] ?? null;

            if (! $operationName) {
                throw new \RuntimeException('No operation name returned from Veo API');
            }

            $video->update([
                'provider' => $result['provider'] ?? config('services.text_to_video.provider', 'google_veo'),
                'mode' => $result['mode'] ?? config('services.text_to_video.mode', 'test'),
                'operation_name' => $operationName,
                'storage_uri' => $result['storage_uri'] ?? null,
                'metadata' => array_merge($video->metadata ?? [], [
                    'task_id' => $operationName,
                ]),
            ]);

            // Poll operation status and update progress
            $done = false;
            $maxAttempts = 120;
            $attempt = 0;
            while (! $done && $attempt < $maxAttempts) {
                sleep(10);
                $poll = $videoService->pollOperation($operationName);
                $progress = $poll['progress'] ?? null;
                if ($progress !== null) {
                    $video->update(['progress' => $progress]);
                }
                if ($poll['done'] ?? false) {
                    $videoUrl = $poll['video_url'] ?? null;
                    $video->update([
                        'status' => 'completed',
                        'video_url' => $videoUrl,
                        'progress' => 100,
                    ]);
                    $done = true;
                }
                $attempt++;
            }
        } catch (\Exception $e) {
            Log::error('Video generation failed', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            $video->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
