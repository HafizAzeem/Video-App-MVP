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
        public Video $video
    ) {}

    public function handle(TextToVideoService $videoService, GPTService $gptService): void
    {
        try {
            $this->video->update(['status' => 'processing']);

            // Generate video prompt from summary
            $videoPrompt = $gptService->generateVideoPrompt($this->video->summary_text);

            // Start video generation
            $result = $videoService->generate($videoPrompt);

            // Store task ID in metadata
            $this->video->update([
                'metadata' => [
                    'task_id' => $result['task_id'],
                    'provider' => $result['provider'],
                ],
            ]);

            // Poll for video completion (simplified - use queue retry in production)
            $this->pollVideoStatus($videoService);

        } catch (\Exception $e) {
            $this->video->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Video generation failed', [
                'video_id' => $this->video->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function pollVideoStatus(TextToVideoService $videoService): void
    {
        $taskId = $this->video->metadata['task_id'] ?? null;

        if (!$taskId) {
            return;
        }

        $maxAttempts = 60; // 10 minutes max
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            sleep(10); // Wait 10 seconds between checks

            $status = $videoService->checkStatus($taskId);

            if ($status['status'] === 'completed') {
                $this->video->update([
                    'status' => 'completed',
                    'video_url' => $status['video_url'],
                ]);

                Log::info('Video generation completed', [
                    'video_id' => $this->video->id,
                    'video_url' => $status['video_url'],
                ]);

                return;
            }

            if ($status['status'] === 'failed') {
                $this->video->update([
                    'status' => 'failed',
                    'error_message' => 'Video generation failed on provider side',
                ]);

                return;
            }

            $attempt++;
        }

        // Timeout
        $this->video->update([
            'status' => 'failed',
            'error_message' => 'Video generation timeout',
        ]);
    }
}
