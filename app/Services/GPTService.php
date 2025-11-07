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
     * Generate video prompt from child's book report using Gemini
     */
    public function generateVideoPrompt(string $summary): string
    {
        try {
            $prompt = <<<PROMPT
[System]
You are a video concept artist specializing in transforming a child's pure and heartfelt book report into a beautiful, picture-book-style animation. Your task is to extract the core emotions, symbolic imagery, and key events from the [Child's Text] provided below. Based on this, generate a video production prompt in a warm, dreamlike, and illustrative style.

[Visual Style Directives]

- Visual Aesthetic: A soft, watercolor-bleeding effect or a pastel-toned, fairy-tale illustration style.
- Core Expression: Visualize the child's emotions (e.g., joy, sadness, surprise) using color, light, and abstract shapes.
- Final Output: Produce a single, cohesive paragraph prompt. The description must be specific, lyrical, and ready for a video generation AI to understand and execute immediately.

[Child's Text]
{$summary}

Create a beautiful, warm video prompt that captures the essence of this child's book report experience.
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
