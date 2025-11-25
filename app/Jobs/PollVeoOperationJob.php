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

    private const POLL_DELAY_SECONDS = 10;

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
            Log::info('Video no longer processing, skipping poll', [
                'video_id' => $video->id,
                'status' => $video->status,
            ]);

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

            if (isset($result['progress'])) {
                $video->update(['progress' => (int) min(100, max(0, $result['progress']))]);
            }

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

            if (! ($result['done'] ?? false)) {
                $this->reschedule($video);

                return;
            }

            if (empty($result['video_url'])) {
                throw new \RuntimeException('Video completed but no downloadable URL provided');
            }

            $video->update([
                'status' => 'completed',
                'video_url' => $result['video_url'],
                'progress' => 100,
                'metadata' => array_merge($video->metadata ?? [], [
                    'completed_at' => now()->toIso8601String(),
                ]),
            ]);

            Log::info('Video generation completed successfully', [
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


