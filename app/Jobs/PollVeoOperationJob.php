<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\TextToVideoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PollVeoOperationJob implements ShouldQueue
{
    use Queueable;

    private const POLL_DELAY_SECONDS = 3; // Poll every 3 seconds for faster progress updates

    private const MAX_ATTEMPTS = 60;

    public function __construct(
        public int $videoId,
        public int $attempt = 0
    ) {}

    public function handle(TextToVideoService $videoService): void
    {
        $video = Video::find($this->videoId);

        if (! $video) {
            Log::warning('PollVeoOperationJob received missing video', ['video_id' => $this->videoId]);

            return;
        }

        if ($video->status !== 'processing') {
            return;
        }

        if (! $video->operation_name) {
            $video->update([
                'status' => 'failed',
                'error_message' => 'Missing operation name for Veo polling',
            ]);

            Log::error('Missing operation name, failing video', ['video_id' => $video->id]);

            return;
        }

        try {
            $result = $videoService->pollOperation($video->operation_name);
            
            // Check for errors first
            if (! empty($result['error'])) {
                $video->update([
                    'status' => 'failed',
                    'error_message' => $result['error'],
                ]);

                Log::error('Veo operation failed', [
                    'video_id' => $video->id,
                    'operation' => $video->operation_name,
                    'error' => $result['error'],
                ]);

                return;
            }

            // Check if done - if done, skip progress updates and go straight to completion
            $isDone = $result['done'] ?? false;
            
            if ($isDone) {
                // Video is done - skip to completion handling below
                // Don't update progress here, we'll set it to 100% in the completion block
            } else {
                // Video is still processing - update progress
                if (isset($result['progress']) && $result['progress'] !== null && $result['progress'] > 0) {
                    // Cap progress at 90% until video is actually done (has video_url)
                    $newProgress = (int) min(90, max(0, $result['progress']));
                    // Always update if new progress is higher than current
                    $currentProgress = $video->progress ?? 10;
                    if ($newProgress > $currentProgress) {
                        $video->update(['progress' => $newProgress]);
                    }
                } else {
                    // If no progress from API, use smarter estimation
                    // Estimate based on time elapsed and attempt number
                    $currentProgress = $video->progress ?? 10;
                    
                    // Calculate time-based progress (assume video takes 2-3 minutes, more realistic)
                    $timeElapsed = now()->diffInSeconds($video->created_at);
                    // More realistic: assume video completes in ~120 seconds, so ~0.75% per second
                    // Cap at 90% max until video is actually done
                    $estimatedTimeProgress = min(90, (int) ($timeElapsed * 0.75));
                    
                    // Calculate attempt-based progress (each attempt = ~3% since we poll every 3s)
                    // More realistic: 3% per poll, max 90%
                    $attemptProgress = min(90, (int) (($this->attempt + 1) * 3));
                    
                    // Use the higher of the two estimates, but ensure it's always increasing
                    // Add a minimum increment to ensure progress always moves forward
                    $minIncrement = 2; // Minimum 2% increase per poll (more realistic)
                    $estimatedProgress = max(
                        $currentProgress + $minIncrement, // Always increase by at least 2%
                        min(90, max($estimatedTimeProgress, $attemptProgress)) // Cap at 90% max
                    );
                    
                    $video->update(['progress' => $estimatedProgress]);
                }
                
                // Video is still processing, reschedule polling
                $this->reschedule($video);
                return;
            }

            // Video is done - ensure progress is 100% and update everything in one go
            if (empty($result['video_url'])) {
                throw new \RuntimeException('Video completed but no downloadable URL provided');
            }

            // Update everything atomically - progress to 100%, status to completed, and video URL
            $video->update([
                'status' => 'completed',
                'progress' => 100,
                'video_url' => $result['video_url'],
                'metadata' => array_merge($video->metadata ?? [], [
                    'completed_at' => now()->toIso8601String(),
                ]),
            ]);
            
            Log::info('Video generation completed', [
                'video_id' => $video->id,
                'operation' => $video->operation_name,
            ]);
        } catch (\Exception $e) {
            Log::error('Polling Veo operation failed', [
                'video_id' => $video->id,
                'operation' => $video->operation_name,
                'attempt' => $this->attempt,
                'error' => $e->getMessage(),
            ]);

            $video->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function reschedule(Video $video): void
    {
        if ($this->attempt >= self::MAX_ATTEMPTS) {
            $video->update([
                'status' => 'failed',
                'error_message' => 'Video generation timed out while polling Veo',
            ]);

            Log::error('Veo polling timed out', [
                'video_id' => $video->id,
                'operation' => $video->operation_name,
                'attempts' => $this->attempt,
            ]);

            return;
        }

        self::dispatch($video->id, $this->attempt + 1)
            ->delay(now()->addSeconds(self::POLL_DELAY_SECONDS));
    }
}


