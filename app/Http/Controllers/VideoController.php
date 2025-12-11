<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateVideoJob;
use App\Models\Video;
use App\Services\GPTService;
use App\Services\TextToVideoService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class VideoController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected TextToVideoService $videoService,
        protected GPTService $gptService
    ) {}

    /**
     * Show video production page
     */
    public function index(): Response
    {
        $videoPrompt = session('video_prompt');
        $summary = session('summary');

        if (! $videoPrompt || ! $summary) {
            return Inertia::render('Production', [
                'error' => 'No video prompt found. Please complete all questions and review first.',
            ]);
        }

        // Check if video generation is in progress
        $ongoingVideo = auth()->user()
            ->videos()
            ->whereIn('status', ['pending', 'processing'])
            ->latest()
            ->first();

        return Inertia::render('Production', [
            'videoPrompt' => $videoPrompt,
            'summary' => $summary,
            'video' => $ongoingVideo,
        ]);
    }

    /**
     * Start video generation process
     */
    public function generate(Request $request)
    {
        // Get data from session
        $videoPrompt = session('video_prompt');
        $summary = session('summary');
        $answers = session('answers');

        if (! $videoPrompt || ! $summary) {
            Log::warning('Video generation attempted without required session data');
            return redirect()->back()->with('error', 'Missing required data');
        }

        try {
            // Create video record with summary and prompt
            $video = Video::create([
                'user_id' => auth()->id(),
                'summary_text' => $summary,
                'prompt' => $videoPrompt,
                'status' => 'pending',
                'progress' => 0,
                'metadata' => [
                    'answers' => $answers,
                    'created_at' => now()->toIso8601String(),
                ],
            ]);

            // Dispatch job for async video generation
            GenerateVideoJob::dispatch($video->id);

            // Redirect back to production page which will now show the new video
            return redirect()->back()->with([
                'success' => true,
                'message' => 'Video generation started!',
            ]);
        } catch (\Exception $e) {
            Log::error('Video generation failed in controller', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Check video generation status
     */
    public function checkStatus(Video $video)
    {
        try {
            $this->authorize('view', $video);

            return response()->json([
                'status' => $video->status,
                'video_url' => $video->video_url,
                'error_message' => $video->error_message,
                'metadata' => $video->metadata,
                'progress' => $video->progress ?? 0,
                'provider' => $video->provider,
                'mode' => $video->mode,
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::warning('Unauthorized status check attempt', [
                'video_id' => $video->id,
                'user_id' => auth()->id(),
            ]);
            
            return response()->json([
                'error' => 'Unauthorized access',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Error checking video status', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'error' => 'Failed to check video status',
            ], 500);
        }
    }

    /**
     * Show gallery page with all user videos
     */
    public function gallery(): Response
    {
        $videos = auth()->user()
            ->videos()
            ->latest()
            ->paginate(12);

        $stats = [
            'total' => auth()->user()->videos()->count(),
            'completed' => auth()->user()->videos()->where('status', 'completed')->count(),
            'processing' => auth()->user()->videos()->where('status', 'processing')->count(),
            'failed' => auth()->user()->videos()->where('status', 'failed')->count(),
        ];

        return Inertia::render('Gallery', [
            'videos' => $videos,
            'stats' => $stats,
        ]);
    }

    /**
     * Show single video
     */
    public function show(Video $video): Response
    {
        $this->authorize('view', $video);

        return Inertia::render('VideoShow', [
            'video' => $video,
        ]);
    }

    /**
     * Delete video
     */
    public function destroy(Video $video)
    {
        $this->authorize('delete', $video);

        // TODO: Delete video file from storage/S3

        $video->delete();

        return redirect()->route('gallery')->with('success', 'Video deleted successfully!');
    }
}
