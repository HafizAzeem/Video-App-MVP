<?php

namespace App\Console\Commands;

use App\Http\Controllers\VideoController;
use App\Models\User;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class TestVideoGeneration extends Command
{
    protected $signature = 'test:video-generation {user_id?}';
    protected $description = 'Test video generation endpoint';

    public function handle()
    {
        $userId = $this->argument('user_id') ?? User::first()?->id;
        
        if (!$userId) {
            $this->error('No user found. Please provide a user_id or create a user first.');
            return 1;
        }

        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return 1;
        }

        $this->info("Testing video generation for user: {$user->email} (ID: {$userId})");

        // Set up session data
        $summary = "Test summary for video generation";
        $videoPrompt = "Test video prompt";
        $answers = ["answer1", "answer2"];

        // Simulate a request
        $request = Request::create('/production/generate', 'POST');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Set session data
        session([
            'summary' => $summary,
            'video_prompt' => $videoPrompt,
            'answers' => $answers,
        ]);

        // Check jobs before
        $jobsBefore = DB::table('jobs')->count();
        $this->info("Jobs in queue before: {$jobsBefore}");

        // Call the controller method
        try {
            auth()->login($user);
            
            $controller = app(VideoController::class);
            $response = $controller->generate($request);
            
            // Check jobs after
            $jobsAfter = DB::table('jobs')->count();
            $this->info("Jobs in queue after: {$jobsAfter}");
            
            if ($jobsAfter > $jobsBefore) {
                $this->info('✅ Job was successfully created!');
            } else {
                $this->warn('⚠️  No job was created.');
            }

            // Check for new video
            $video = Video::where('user_id', $userId)->latest()->first();
            if ($video) {
                $this->info("Video created: ID {$video->id}, Status: {$video->status}");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}


