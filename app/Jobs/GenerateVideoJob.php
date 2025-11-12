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
            Log::error('No task ID found in video metadata', ['video_id' => $this->video->id]);
            return;
        }

        $maxAttempts = 120; // 20 minutes max (120 * 10 seconds)
        $attempt = 0;

        Log::info('Starting video status polling', [
            'video_id' => $this->video->id,
            'task_id' => $taskId,
            'max_attempts' => $maxAttempts,
        ]);

        while ($attempt < $maxAttempts) {
            sleep(10); // Wait 10 seconds between checks

            try {
                $status = $videoService->checkStatus($taskId);

                Log::info('Video status check', [
                    'video_id' => $this->video->id,
                    'attempt' => $attempt + 1,
                    'status' => $status['status'],
                    'progress' => $status['progress'] ?? 0,
                ]);

                // Update video progress
                if (isset($status['progress'])) {
                    $this->video->update([
                        'metadata' => array_merge($this->video->metadata ?? [], [
                            'progress' => $status['progress'],
                            'last_checked' => now()->toIso8601String(),
                        ]),
                    ]);
                }

                if ($status['status'] === 'completed') {
                    if (empty($status['video_url'])) {
                        Log::error('Video marked as completed but no URL provided', [
                            'video_id' => $this->video->id,
                            'task_id' => $taskId,
                            'status_response' => $status,
                        ]);
                        
                        $this->video->update([
                            'status' => 'failed',
                            'error_message' => 'Video completed but no URL received from API',
                        ]);
                        
                        return;
                    }

                    $this->video->update([
                        'status' => 'completed',
                        'video_url' => $status['video_url'],
                    ]);

                    Log::info('Video generation completed successfully', [
                        'video_id' => $this->video->id,
                        'video_url' => $status['video_url'],
                        'attempts' => $attempt + 1,
                    ]);

                    return;
                }

                if ($status['status'] === 'failed' || $status['status'] === 'error') {
                    $errorMessage = $status['error'] ?? 'Video generation failed on provider side';
                    
                    Log::error('Video generation failed', [
                        'video_id' => $this->video->id,
                        'task_id' => $taskId,
                        'error' => $errorMessage,
                        'status_response' => $status,
                    ]);

                    $this->video->update([
                        'status' => 'failed',
                        'error_message' => $errorMessage,
                    ]);

                    return;
                }

                // Check if stuck at 95% for too long
                if (isset($status['progress']) && $status['progress'] >= 95 && $attempt > 30) {
                    Log::warning('Video stuck at high progress percentage', [
                        'video_id' => $this->video->id,
                        'progress' => $status['progress'],
                        'attempts' => $attempt + 1,
                    ]);
                }

            } catch (\Exception $e) {
                Log::error('Error checking video status', [
                    'video_id' => $this->video->id,
                    'task_id' => $taskId,
                    'attempt' => $attempt + 1,
                    'error' => $e->getMessage(),
                ]);
                
                // Continue polling unless it's a fatal error
                if (str_contains($e->getMessage(), 'not found') || 
                    str_contains($e->getMessage(), 'Authentication failed')) {
                    throw $e;
                }
            }

            $attempt++;
        }

        // Timeout - log detailed information
        Log::error('Video generation timeout', [
            'video_id' => $this->video->id,
            'task_id' => $taskId,
            'attempts' => $maxAttempts,
            'duration_minutes' => ($maxAttempts * 10) / 60,
            'last_metadata' => $this->video->fresh()->metadata,
        ]);

        $this->video->update([
            'status' => 'failed',
            'error_message' => 'Video generation timeout after ' . (($maxAttempts * 10) / 60) . ' minutes',
        ]);
    }
}
