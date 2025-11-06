<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\GPTService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateSummaryJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user
    ) {}

    public function handle(GPTService $gptService): void
    {
        try {
            $answers = $this->user->answers()
                ->with('question')
                ->get()
                ->pluck('text')
                ->toArray();

            if (empty($answers)) {
                throw new \Exception('No answers found for user');
            }

            $summary = $gptService->summarizeAnswers($answers);

            session(['summary' => $summary]);

            Log::info('Summary generated successfully', [
                'user_id' => $this->user->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate summary', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
