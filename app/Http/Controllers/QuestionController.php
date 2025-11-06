<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnswerRequest;
use App\Jobs\TranscribeAudioJob;
use App\Models\Answer;
use App\Models\Question;
use App\Services\SpeechToTextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
    public function __construct(
        protected SpeechToTextService $sttService
    ) {}

    /**
     * Display the first question
     */
    public function index(): Response
    {
        $questions = Question::where('is_active', true)
            ->orderBy('order')
            ->get(['id', 'text', 'audio_path', 'order']);

        $userAnswers = auth()->user()
            ->answers()
            ->whereIn('question_id', $questions->pluck('id'))
            ->get(['id', 'question_id', 'text']);

        return Inertia::render('Question', [
            'questions' => $questions,
            'userAnswers' => $userAnswers,
            'currentQuestionIndex' => 0,
        ]);
    }

    /**
     * Store answer with voice transcription
     */
    public function storeAnswer(StoreAnswerRequest $request)
    {
        $validated = $request->validated();

        // Store audio file if provided
        $audioPath = null;
        if ($request->hasFile('audio')) {
            $audioPath = $request->file('audio')->store('answers', 'public');
        }

        // If audio is provided, transcribe it asynchronously
        if ($audioPath && !$request->filled('text')) {
            // Dispatch job for async transcription
            $answer = Answer::create([
                'user_id' => auth()->id(),
                'question_id' => $validated['question_id'],
                'text' => 'Transcribing...', // Placeholder
                'audio_path' => $audioPath,
            ]);

            TranscribeAudioJob::dispatch($answer);

            return back()->with('message', 'Audio uploaded. Transcribing...');
        }

        // If text is provided directly
        $answer = Answer::create([
            'user_id' => auth()->id(),
            'question_id' => $validated['question_id'],
            'text' => $validated['text'] ?? 'No text provided',
            'audio_path' => $audioPath,
        ]);

        return back()->with('success', 'Answer saved successfully!');
    }

    /**
     * Transcribe audio immediately (synchronous for demo)
     */
    public function transcribeAudio(Request $request)
    {
        $request->validate([
            'audio' => 'required|file|mimes:mp3,wav,webm,ogg|max:25600',
        ]);

        try {
            if (!$this->sttService->validateAudioFile($request->file('audio'))) {
                return response()->json(['error' => 'Invalid audio file'], 422);
            }

            $text = $this->sttService->transcribe($request->file('audio'));

            return response()->json(['text' => $text]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get question by ID
     */
    public function show(Question $question)
    {
        return response()->json($question);
    }

    /**
     * Delete answer
     */
    public function deleteAnswer(Answer $answer)
    {
        $this->authorize('delete', $answer);

        if ($answer->audio_path) {
            Storage::disk('public')->delete($answer->audio_path);
        }

        $answer->delete();

        return back()->with('success', 'Answer deleted successfully!');
    }
}
