<?php

namespace App\Console\Commands;

use App\Jobs\GenerateVideoJob;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckVideoAndCreateJob extends Command
{
    protected $signature = 'video:check-and-create-job {video_id}';
    protected $description = 'Check video status and create job if needed';

    public function handle()
    {
        $videoId = $this->argument('video_id');
        $video = Video::find($videoId);
        
        if (!$video) {
            $this->error("Video {$videoId} not found");
            return 1;
        }
        
        $this->info("Video {$videoId} Status: {$video->status}");
        $this->info("Progress: {$video->progress}%");
        $this->info("Created: {$video->created_at}");
        $this->info("User ID: {$video->user_id}");
        
        $jobsBefore = DB::table('jobs')->count();
        $this->info("Jobs in queue: {$jobsBefore}");
        
        if ($video->status === 'pending') {
            $this->info("Video is pending. Creating job...");
            
            try {
                GenerateVideoJob::dispatch($video->id);
                $jobsAfter = DB::table('jobs')->count();
                $this->info("✅ Job created! Jobs in queue now: {$jobsAfter}");
                return 0;
            } catch (\Exception $e) {
                $this->error("Failed to create job: " . $e->getMessage());
                return 1;
            }
        } else {
            $this->warn("Video status is '{$video->status}', not 'pending'. Job will only run for pending videos.");
            return 0;
        }
    }
}


