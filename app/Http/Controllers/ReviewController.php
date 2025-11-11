<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateTTSJob;
use App\Models\Question;
use App\Services\GPTService;
use App\Services\TextToSpeechService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function __construct(
        protected GPTService $gptService,
        protected TextToSpeechService $ttsService
    ) {}

    /**
     * Show review page - answers come from localStorage on frontend
     */
    public function index(): Response
    {
        $questions = Question::where('is_active', true)
            ->orderBy('order')
            ->get(['id', 'text', 'order']);

        return Inertia::render('ReviewSimple', [
            'questions' => $questions,
        ]);
    }

    /**
     * Generate video prompt from answers (received from frontend localStorage)
     */
    public function generatePrompt(Request $request)
    {
        $request->validate([
            'answers' => 'required|array',
            'answers.*.question' => 'required|string',
            'answers.*.answer' => 'required|string',
        ]);

        try {
            $formattedAnswers = collect($request->answers)->pluck('answer')->toArray();

            Log::info('Generating summary from answers', [
                'answers' => $formattedAnswers,
            ]);

            // First, summarize the answers into a story
            $summary = $this->gptService->summarizeAnswers($formattedAnswers);

            Log::info('Summary generated', [
                'summary' => $summary,
            ]);

            // Then, generate video prompt from the summary
            $videoPrompt = $this->gptService->generateVideoPrompt($summary);

            Log::info('Video prompt generated', [
                'prompt' => $videoPrompt,
            ]);

            // Store in session for production page
            session([
                'video_prompt' => $videoPrompt,
                'summary' => $summary,
                'answers' => $request->answers,
            ]);

            return response()->json([
                'success' => true,
                'videoPrompt' => $videoPrompt,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate video prompt', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate AI summary from user answers
     */
    public function generateSummary(Request $request)
    {
        $answers = auth()->user()
            ->answers()
            ->with('question')
            ->get();

        if ($answers->isEmpty()) {
            return back()->with('error', 'No answers found. Please answer the questions first.');
        }

        try {
            // Format answers for GPT
            $formattedAnswers = $answers->map(function ($answer) {
                return $answer->text;
            })->toArray();

            // Generate summary
            $summary = $this->gptService->summarizeAnswers($formattedAnswers);

            // Store summary in session for now
            session(['summary' => $summary]);

            // Generate TTS asynchronously if requested
            if ($request->boolean('generate_audio')) {
                GenerateTTSJob::dispatch(auth()->user(), $summary);
            }

            return back()->with('success', 'Summary generated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate summary: '.$e->getMessage());
        }
    }

    /**
     * Generate TTS for summary
     */
    public function generateTTS(Request $request)
    {
        $request->validate([
            'summary' => 'required|string',
        ]);

        try {
            $audioPath = $this->ttsService->generate($request->summary);

            return response()->json([
                'audio_path' => $audioPath,
                'audio_url' => asset('storage/'.$audioPath),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * User confirms and proceeds to video generation
     */
    public function confirm(Request $request)
    {
        // Video prompt should already be in session from index()
        $videoPrompt = session('video_prompt');

        if (! $videoPrompt) {
            return back()->with('error', 'Please answer all questions first.');
        }

        // Store confirmed flag
        session(['video_confirmed' => true]);

        return redirect()->route('production.index');
    }
}
