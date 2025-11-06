<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GPTService
{
    public function __construct(
        protected string $apiKey = '',
        protected string $model = 'gpt-4-turbo'
    ) {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.gpt.model', 'gpt-4-turbo');
    }

    /**
     * Summarize user answers into a compelling story
     */
    public function summarizeAnswers(array $answers): string
    {
        $prompt = $this->buildSummarizationPrompt($answers);

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a creative storyteller. Transform user answers into an engaging, inspiring narrative suitable for video production. Keep it concise (150-200 words), emotional, and visual.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.8,
                    'max_tokens' => 500,
                ]);

            if ($response->failed()) {
                throw new \Exception('OpenAI GPT API failed: ' . $response->body());
            }

            return $response->json('choices.0.message.content');
        } catch (\Exception $e) {
            Log::error('GPT summarization failed', [
                'error' => $e->getMessage(),
                'model' => $this->model,
            ]);

            throw $e;
        }
    }

    /**
     * Generate video prompt from summary
     */
    public function generateVideoPrompt(string $summary): string
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a video production specialist. Transform the story into a detailed video prompt with visual descriptions, camera movements, and atmosphere. Be specific and cinematic.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Story: {$summary}\n\nCreate a video prompt:",
                        ],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 400,
                ]);

            if ($response->failed()) {
                throw new \Exception('OpenAI GPT API failed: ' . $response->body());
            }

            return $response->json('choices.0.message.content');
        } catch (\Exception $e) {
            Log::error('Video prompt generation failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function buildSummarizationPrompt(array $answers): string
    {
        $formattedAnswers = collect($answers)
            ->map(fn ($answer, $index) => "Q" . ($index + 1) . ": {$answer}")
            ->join("\n");

        return <<<PROMPT
        Transform these user answers into a short, inspiring story:

        {$formattedAnswers}

        Create a narrative that:
        - Connects all answers into a cohesive story
        - Is emotional and uplifting
        - Uses vivid, visual language
        - Is 150-200 words
        - Ends with hope or motivation
        PROMPT;
    }
}
