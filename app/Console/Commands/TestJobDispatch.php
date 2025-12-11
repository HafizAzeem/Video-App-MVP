<?php

namespace App\Console\Commands;

use App\Jobs\GenerateVideoJob;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestJobDispatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:job-dispatch {video_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test job dispatch to verify queue is working';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $videoId = $this->argument('video_id');
        
        if (!$videoId) {
            // Get the most recent video
            $video = Video::latest()->first();
            if (!$video) {
                $this->error('No videos found. Please create a video first.');
                return 1;
            }
            $videoId = $video->id;
        }
        
        $this->info("Testing job dispatch for video ID: {$videoId}");
        
        // Check current job count
        $beforeCount = DB::table('jobs')->count();
        $this->info("Jobs in queue before: {$beforeCount}");
        
        // Dispatch job
        try {
            GenerateVideoJob::dispatch($videoId);
            $this->info('Job dispatched successfully');
            
            // Check job count after
            $afterCount = DB::table('jobs')->count();
            $this->info("Jobs in queue after: {$afterCount}");
            
            if ($afterCount > $beforeCount) {
                $this->info('✅ Job was successfully added to queue!');
                $this->info('Run "php artisan queue:work" to process it.');
            } else {
                $this->warn('⚠️  Job was dispatched but not added to queue.');
                $this->warn('Check if queue connection is set correctly.');
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to dispatch job: ' . $e->getMessage());
            Log::error('Test job dispatch failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}
