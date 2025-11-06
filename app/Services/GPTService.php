<?php

namespace App\Services;

use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;

class GPTService
{
    protected string $model;

    public function __construct()
    {
        $this->model = config('services.gemini.model', 'gemini-2.0-flash-exp');
    }

    /**
     * Summarize user answers into a compelling story using Gemini
     */
    public function summarizeAnswers(array $answers): string
    {
        $prompt = $this->buildSummarizationPrompt($answers);

        try {
            $result = Gemini::geminiPro()->generateContent($prompt);

            $text = $result->text();

            if (empty($text)) {
                throw new \Exception('No content returned from Gemini');
            }

            return trim($text);

        } catch (\Exception $e) {
            Log::error('Gemini summarization failed', [
                'error' => $e->getMessage(),
                'model' => $this->model,
            ]);

            throw $e;
        }
    }

    /**
     * Generate video prompt from summary using Gemini
     */
    public function generateVideoPrompt(string $summary): string
    {
        try {
            $prompt = <<<PROMPT
You are a video production specialist. Transform the following story into a detailed video prompt with visual descriptions, camera movements, and atmosphere. Be specific and cinematic.

Story: {$summary}

Create a video prompt that includes:
- Opening scene description
- Camera movements and angles
- Visual elements and atmosphere
- Color palette and lighting
- Transition suggestions
- Closing scene

Keep it focused and under 400 words.
PROMPT;

            $result = Gemini::geminiPro()->generateContent($prompt);

            $text = $result->text();

            if (empty($text)) {
                throw new \Exception('No content returned from Gemini');
            }

            return trim($text);

        } catch (\Exception $e) {
            Log::error('Video prompt generation failed with Gemini', [
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

Focus on storytelling and creating an engaging narrative suitable for video production.
PROMPT;
    }

    /**
     * Generate content with specific model
     */
    public function generateContent(string $prompt, ?string $model = null): string
    {
        try {
            $modelToUse = $model ?? $this->model;

            $result = Gemini::geminiPro()->generateContent($prompt);

            $text = $result->text();

            if (empty($text)) {
                throw new \Exception('No content returned from Gemini');
            }

            return trim($text);

        } catch (\Exception $e) {
            Log::error('Gemini content generation failed', [
                'error' => $e->getMessage(),
                'model' => $modelToUse ?? 'default',
            ]);

            throw $e;
        }
    }
}
