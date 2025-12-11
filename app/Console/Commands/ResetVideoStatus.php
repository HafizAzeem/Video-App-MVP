<?php

namespace App\Console\Commands;

use App\Models\Video;
use Illuminate\Console\Command;

class ResetVideoStatus extends Command
{
    protected $signature = 'video:reset {video_id}';
    protected $description = 'Reset video status to pending so job can run';

    public function handle()
    {
        $videoId = $this->argument('video_id');
        $video = Video::find($videoId);
        
        if (!$video) {
            $this->error("Video {$videoId} not found");
            return 1;
        }
        
        $this->info("Resetting video {$videoId} from '{$video->status}' to 'pending'");
        
        $video->update([
            'status' => 'pending',
            'progress' => 0,
            'error_message' => null,
        ]);
        
        $this->info("✅ Video reset to pending. Now run: php artisan video:check-and-create-job {$videoId}");
        return 0;
    }
}


