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
     * Show review page with user answers
     */
    public function index(): Response
    {
        $questions = Question::where('is_active', true)
            ->orderBy('order')
            ->get();

        $answers = auth()->user()
            ->answers()
            ->with('question')
            ->whereIn('question_id', $questions->pluck('id'))
            ->get();

        // Check if all questions are answered
        $allAnswered = $questions->count() === $answers->count();

        Log::info('Review page loaded', [
            'questions_count' => $questions->count(),
            'answers_count' => $answers->count(),
            'all_answered' => $allAnswered,
            'answers' => $answers->map(fn($a) => [
                'question_id' => $a->question_id,
                'question_text' => $a->question->text ?? 'N/A',
                'answer_text' => $a->text,
            ])->toArray(),
        ]);

        // Auto-generate video prompt if not in session
        $videoPrompt = session('video_prompt');

        if ($allAnswered && ! $videoPrompt) {
            try {
                // Format answers for GPT
                $formattedAnswers = $answers->map(function ($answer) {
                    return $answer->text;
                })->toArray();

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

                // Store in session
                session(['video_prompt' => $videoPrompt]);
            } catch (\Exception $e) {
                Log::error('Failed to generate video prompt', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        } else {
            Log::info('Skipping video prompt generation', [
                'all_answered' => $allAnswered,
                'has_session_prompt' => ! empty($videoPrompt),
                'session_prompt' => $videoPrompt,
            ]);
        }

        return Inertia::render('ReviewSimple', [
            'videoPrompt' => $videoPrompt,
        ]);
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
