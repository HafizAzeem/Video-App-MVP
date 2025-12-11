<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckQueueStatus extends Command
{
    protected $signature = 'queue:status';
    protected $description = 'Check queue status and job details';

    public function handle()
    {
        $jobs = DB::table('jobs')->get();
        
        $this->info("Total jobs in queue: " . $jobs->count());
        
        if ($jobs->count() > 0) {
            $this->newLine();
            $this->table(
                ['ID', 'Queue', 'Attempts', 'Available At', 'Created At', 'Reserved At'],
                $jobs->map(function ($job) {
                    return [
                        $job->id,
                        $job->queue,
                        $job->attempts,
                        date('Y-m-d H:i:s', $job->available_at),
                        date('Y-m-d H:i:s', $job->created_at),
                        $job->reserved_at ? date('Y-m-d H:i:s', $job->reserved_at) : 'null',
                    ];
                })
            );
        }
        
        return 0;
    }
}


