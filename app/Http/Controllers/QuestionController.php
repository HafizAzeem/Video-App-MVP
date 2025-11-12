<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Services\SpeechToTextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        return Inertia::render('Question', [
            'questions' => $questions,
            'currentQuestionIndex' => 0,
        ]);
    }

    /**
     * Transcribe audio immediately (synchronous for demo)
     *
     * @deprecated This method is no longer used. Speech recognition is now handled
     * client-side using the Web Speech API in SpeechRecorder.vue component.
     * This provides:
     * - Instant transcription without API calls
     * - No cost (free, browser-based)
     * - Real-time feedback as user speaks
     * - No server load or network latency
     *
     * Kept for backward compatibility or fallback if needed.
     */
    public function transcribeAudio(Request $request)
    {
        $request->validate([
            'audio' => 'required|file|max:25600',
        ]);

        try {
            $audioFile = $request->file('audio');

            Log::info('Transcription request received', [
                'mime_type' => $audioFile->getMimeType(),
                'extension' => $audioFile->getClientOriginalExtension(),
                'size' => $audioFile->getSize(),
            ]);

            if (! $this->sttService->validateAudioFile($audioFile)) {
                return response()->json([
                    'error' => 'Invalid audio file format or size',
                    'mime_type' => $audioFile->getMimeType(),
                    'extension' => $audioFile->getClientOriginalExtension(),
                ], 422);
            }

            $text = $this->sttService->transcribe($audioFile);

            return response()->json(['text' => $text]);
        } catch (\Exception $e) {
            Log::error('Transcription error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

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
}
