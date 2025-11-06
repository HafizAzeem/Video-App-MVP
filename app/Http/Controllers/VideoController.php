<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateVideoJob;
use App\Models\Video;
use App\Services\GPTService;
use App\Services\TextToVideoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VideoController extends Controller
{
    public function __construct(
        protected TextToVideoService $videoService,
        protected GPTService $gptService
    ) {}

    /**
     * Show video production page
     */
    public function index(): Response
    {
        $summary = session('confirmed_summary');

        if (!$summary) {
            return Inertia::render('Production', [
                'error' => 'No summary found. Please complete the review step first.',
            ]);
        }

        // Check if video generation is in progress
        $ongoingVideo = auth()->user()
            ->videos()
            ->whereIn('status', ['pending', 'processing'])
            ->latest()
            ->first();

        return Inertia::render('Production', [
            'summary' => $summary,
            'ongoingVideo' => $ongoingVideo,
        ]);
    }

    /**
     * Start video generation process
     */
    public function generate(Request $request)
    {
        $request->validate([
            'summary' => 'required|string',
        ]);

        try {
            // Create video record
            $video = Video::create([
                'user_id' => auth()->id(),
                'summary_text' => $request->summary,
                'status' => 'pending',
            ]);

            // Dispatch job for async video generation
            GenerateVideoJob::dispatch($video);

            return response()->json([
                'video_id' => $video->id,
                'status' => 'pending',
                'message' => 'Video generation started!',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Check video generation status
     */
    public function checkStatus(Video $video)
    {
        $this->authorize('view', $video);

        return response()->json([
            'status' => $video->status,
            'video_url' => $video->video_url,
            'error_message' => $video->error_message,
            'metadata' => $video->metadata,
        ]);
    }

    /**
     * Show gallery page with all user videos
     */
    public function gallery(): Response
    {
        $videos = auth()->user()
            ->videos()
            ->where('status', 'completed')
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
