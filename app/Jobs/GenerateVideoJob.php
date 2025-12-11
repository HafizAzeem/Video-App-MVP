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

        // Allow reprocessing if video is stuck in processing for more than 5 minutes
        $isStuck = $video->status === 'processing' && 
                   $video->updated_at < now()->subMinutes(5);
        
        if ($video->status !== 'pending' && !$isStuck) {
            return;
        }
        
        if ($isStuck) {
            Log::warning('Video stuck in processing, resetting to pending', [
                'video_id' => $video->id,
            ]);
            
            $video->update([
                'status' => 'pending',
                'progress' => 0,
            ]);
        }

        try {
            $prompt = $video->prompt ?: $gptService->generateVideoPrompt($video->summary_text);

            if (! $video->prompt) {
                $video->update(['prompt' => $prompt]);
            }

            $video->update([
                'status' => 'processing',
                'progress' => 10, // Start at 10% to show something is happening
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

            // Dispatch polling job to check status asynchronously (start after 3 seconds)
            PollVeoOperationJob::dispatch($video->id)->delay(now()->addSeconds(3));
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
