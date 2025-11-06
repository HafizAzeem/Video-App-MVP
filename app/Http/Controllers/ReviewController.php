<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateSummaryJob;
use App\Jobs\GenerateTTSJob;
use App\Models\Answer;
use App\Models\Question;
use App\Services\GPTService;
use App\Services\TextToSpeechService;
use Illuminate\Http\Request;
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

        return Inertia::render('Review', [
            'questions' => $questions,
            'answers' => $answers,
            'allAnswered' => $allAnswered,
            'summary' => session('summary'),
            'summaryAudioPath' => session('summary_audio_path'),
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
            return back()->with('error', 'Failed to generate summary: ' . $e->getMessage());
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
                'audio_url' => asset('storage/' . $audioPath),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * User confirms summary and proceeds to video generation
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'summary' => 'required|string',
        ]);

        // Store confirmed summary
        session(['confirmed_summary' => $request->summary]);

        return redirect()->route('production.index');
    }
}
