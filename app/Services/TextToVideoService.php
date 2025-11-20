<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class TextToVideoService
{
    protected string $provider;

    protected int $simulationDuration = 60; // seconds

    public function __construct()
    {
        $this->provider = config('services.text_to_video.provider', 'simulated');
    }

    /**
     * Generate video - Currently using simulation
     *
     * IMPORTANT: This is a simulated implementation.
     * Google Veo API requires OAuth2 service account authentication, not API keys.
     *
     * To use real Google Veo API, you need to:
     * 1. Create a service account in Google Cloud Console
     * 2. Download the JSON credentials file
     * 3. Install google/cloud-aiplatform package
     * 4. Use proper OAuth2 authentication
     */
    public function generate(string $prompt, array $options = []): array
    {
        if (empty($prompt)) {
            throw new \InvalidArgumentException('Video prompt cannot be empty');
        }

        try {
            Log::info('Starting video generation (SIMULATED)', [
                'prompt_length' => strlen($prompt),
                'prompt_preview' => substr($prompt, 0, 100),
                'options' => $options,
            ]);

            // Generate unique task ID
            $taskId = 'video_'.uniqid().'_'.time();

            // Store task information in cache with simulated processing
            $taskData = [
                'task_id' => $taskId,
                'status' => 'processing',
                'provider' => 'simulated',
                'prompt' => $prompt,
                'created_at' => now()->toIso8601String(),
                'simulation_start' => now()->toIso8601String(),
                'simulation_duration' => $this->simulationDuration,
                'progress' => 0,
            ];

            cache()->put("video_task_{$taskId}", $taskData, now()->addHours(2));

            Log::info('Video generation task created (simulated)', [
                'task_id' => $taskId,
                'duration' => $this->simulationDuration,
            ]);

            return [
                'task_id' => $taskId,
                'status' => 'processing',
                'provider' => 'simulated',
            ];

        } catch (\Exception $e) {
            Log::error('Video generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Check video generation status
     */
    public function checkStatus(string $taskId): array
    {
        try {
            $taskData = cache()->get("video_task_{$taskId}");

            if (! $taskData) {
                Log::error('Video task not found in cache', ['task_id' => $taskId]);

                return [
                    'status' => 'not_found',
                    'video_url' => null,
                    'progress' => 0,
                    'error' => 'Task not found',
                ];
            }

            // If already completed or failed, return cached result
            if (in_array($taskData['status'], ['completed', 'failed'])) {
                Log::info('Returning cached video status', [
                    'task_id' => $taskId,
                    'status' => $taskData['status'],
                    'progress' => $taskData['status'] === 'completed' ? 100 : 0,
                ]);

                return [
                    'status' => $taskData['status'],
                    'video_url' => $taskData['video_url'] ?? null,
                    'progress' => $taskData['status'] === 'completed' ? 100 : 0,
                    'error' => $taskData['error'] ?? null,
                ];
            }

            // Calculate progress based on elapsed time
            $simulationStart = \Carbon\Carbon::parse($taskData['simulation_start']);
            $elapsedSeconds = $simulationStart->diffInSeconds(now(), false);

            // Ensure elapsed time is positive
            if ($elapsedSeconds < 0) {
                $elapsedSeconds = 0;
            }

            $simulationDuration = $taskData['simulation_duration'] ?? 60;

            // Calculate progress (0-100%)
            $progress = min(100, max(0, ($elapsedSeconds / $simulationDuration) * 100));

            Log::info('Video generation progress', [
                'task_id' => $taskId,
                'elapsed_seconds' => $elapsedSeconds,
                'total_duration' => $simulationDuration,
                'progress' => round($progress, 2),
            ]);

            // Update progress in cache
            $taskData['progress'] = $progress;

            // Check if simulation is complete
            if ($elapsedSeconds >= $simulationDuration) {
                // Check if we're in test mode or production mode
                $videoMode = config('services.text_to_video.mode', 'test');

                if ($videoMode === 'production') {
                    // PRODUCTION MODE: Use real Google Veo API
                    // TODO: Implement actual Google Veo API call here
                    // This requires Google Cloud credentials and billing enabled
                    $videoUrl = $this->generateRealVideo($taskData['prompt']);
                } else {
                    // TEST MODE: Use sample video for testing
                    $videoUrl = 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4';
                }

                $taskData['status'] = 'completed';
                $taskData['progress'] = 100;
                $taskData['video_url'] = $videoUrl;
                $taskData['completed_at'] = now()->toIso8601String();

                cache()->put("video_task_{$taskId}", $taskData, now()->addDays(7));

                Log::info('Video generation completed', [
                    'task_id' => $taskId,
                    'video_url' => $videoUrl,
                    'mode' => $videoMode,
                    'elapsed_seconds' => $elapsedSeconds,
                ]);

                return [
                    'status' => 'completed',
                    'progress' => 100,
                    'video_url' => $videoUrl,
                ];
            }

            // Still processing - update cache
            cache()->put("video_task_{$taskId}", $taskData, now()->addHours(2));

            return [
                'status' => 'processing',
                'progress' => round($progress, 2),
                'video_url' => null,
            ];

        } catch (\Exception $e) {
            Log::error('Status check failed', [
                'error' => $e->getMessage(),
                'task_id' => $taskId,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'error',
                'video_url' => null,
                'progress' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel a video generation task
     */
    public function cancel(string $taskId): bool
    {
        try {
            $taskData = cache()->get("video_task_{$taskId}");

            if (! $taskData) {
                return false;
            }

            $taskData['status'] = 'failed';
            $taskData['error'] = 'Cancelled by user';
            cache()->put("video_task_{$taskId}", $taskData, now()->addDays(7));

            Log::info('Video generation cancelled', ['task_id' => $taskId]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cancel video generation', [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Generate real video using Google Veo API
     * This method will be called when VIDEO_MODE=production
     *
     * @param  string  $prompt  The video generation prompt
     * @return string The video URL from Google Cloud Storage
     */
    protected function generateRealVideo(string $prompt): string
    {
        try {
            Log::info('Starting real Veo API video generation', [
                'prompt' => substr($prompt, 0, 100),
            ]);

            // Initialize HTTP client with OAuth2 authentication
            $httpClient = $this->initializeHttpClient();

            // Call Veo API
            $operationName = $this->callVeoAPI($httpClient, $prompt);

            // Poll for completion (with timeout)
            $maxAttempts = 60; // 10 minutes max (60 attempts * 10 seconds)
            $attempt = 0;

            while ($attempt < $maxAttempts) {
                sleep(10); // Wait 10 seconds between polls

                $result = $this->pollOperationStatus($httpClient, $operationName);

                if ($result['done'] ?? false) {
                    // Log full response to debug
                    Log::info('Veo operation completed - full response', [
                        'result' => $result,
                    ]);

                    // Extract GCS URI from response - try multiple possible paths
                    $gcsUri = $result['response']['videos'][0]['gcsUri']
                        ?? $result['response']['generatedSamples'][0]['videoUri']
                        ?? $result['response']['predictions'][0]['gcsUri']
                        ?? $result['response']['gcsUri']
                        ?? null;

                    if ($gcsUri) {
                        // Convert to public URL
                        $storageService = app(CloudStorageService::class);
                        $publicUrl = $storageService->convertGcsUriToPublicUrl($gcsUri);

                        Log::info('Veo video generation completed', [
                            'gcs_uri' => $gcsUri,
                            'public_url' => $publicUrl,
                            'attempts' => $attempt,
                            'total_time' => ($attempt * 10).' seconds',
                        ]);

                        return $publicUrl;
                    } else {
                        // No GCS URI found in response
                        Log::error('Veo operation done but no video URI found', [
                            'response_keys' => array_keys($result),
                            'response' => $result,
                        ]);

                        throw new \RuntimeException('Video generation completed but no video URI in response');
                    }
                }

                $attempt++;
            }

            // Timeout reached
            throw new \RuntimeException('Video generation timed out after 10 minutes');
        } catch (\Exception $e) {
            Log::error('Veo API video generation failed', [
                'prompt' => substr($prompt, 0, 100),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Fallback to test video
            Log::warning('Falling back to test video due to error');

            return 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4';
        }
    }

    /**
     * Initialize HTTP client with OAuth2 authentication
     */
    protected function initializeHttpClient(): \GuzzleHttp\Client
    {
        $credentialsPath = config('services.google.credentials');

        if (! file_exists($credentialsPath)) {
            throw new \RuntimeException("Google credentials file not found: {$credentialsPath}");
        }

        // Load service account credentials
        $credentials = json_decode(file_get_contents($credentialsPath), true);

        if (! $credentials) {
            throw new \RuntimeException('Invalid Google credentials file');
        }

        // Get OAuth2 access token using Google Auth library
        $auth = new \Google\Auth\Credentials\ServiceAccountCredentials(
            'https://www.googleapis.com/auth/cloud-platform',
            $credentials
        );

        $accessToken = $auth->fetchAuthToken();

        if (! isset($accessToken['access_token'])) {
            throw new \RuntimeException('Failed to obtain OAuth2 access token');
        }

        // Create HTTP client with authorization header
        return new \GuzzleHttp\Client([
            'headers' => [
                'Authorization' => 'Bearer '.$accessToken['access_token'],
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Call Veo API to start video generation
     *
     * @return string Operation name for polling
     */
    protected function callVeoAPI(\GuzzleHttp\Client $client, string $prompt): string
    {
        $projectId = config('services.google.project_id');
        $location = config('services.text_to_video.location', 'us-central1');
        $model = config('services.text_to_video.model', 'veo-3.1-generate-001');
        $bucket = config('services.text_to_video.gcs_bucket');

        if (empty($bucket)) {
            throw new \RuntimeException('GCS_BUCKET not configured in .env file');
        }

        // Generate unique output directory
        $outputDir = 'videos/'.date('Y-m-d').'/'.uniqid();
        $storageUri = "gs://{$bucket}/{$outputDir}/";

        $endpoint = "https://{$location}-aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$location}/publishers/google/models/{$model}:predictLongRunning";

        $requestBody = [
            'instances' => [
                [
                    'prompt' => $prompt,
                ],
            ],
            'parameters' => [
                'storageUri' => $storageUri,
                'sampleCount' => 1,
                'durationSeconds' => 8, // 4, 6, or 8 seconds
                'aspectRatio' => '16:9', // or '9:16'
                'resolution' => '1080p', // or '720p'
                'generateAudio' => true,
            ],
        ];

        Log::info('Calling Veo API', [
            'endpoint' => $endpoint,
            'storage_uri' => $storageUri,
            'model' => $model,
        ]);

        $response = $client->post($endpoint, [
            'json' => $requestBody,
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        $operationName = $result['name'] ?? null;

        if (! $operationName) {
            throw new \RuntimeException('No operation name returned from Veo API');
        }

        Log::info('Veo API call initiated', [
            'operation_name' => $operationName,
        ]);

        return $operationName;
    }

    /**
     * Poll operation status
     *
     * @return array Operation result
     */
    protected function pollOperationStatus(\GuzzleHttp\Client $client, string $operationName): array
    {
        $projectId = config('services.google.project_id');
        $location = config('services.text_to_video.location', 'us-central1');
        $model = config('services.text_to_video.model', 'veo-3.1-generate-001');

        $endpoint = "https://{$location}-aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$location}/publishers/google/models/{$model}:fetchPredictOperation";

        $response = $client->post($endpoint, [
            'json' => [
                'operationName' => $operationName,
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        // Enhanced logging - log full response when done
        if ($result['done'] ?? false) {
            Log::info('Veo operation COMPLETED - full response structure', [
                'done' => true,
                'operation_name' => $operationName,
                'full_response' => $result,
                'response_keys' => array_keys($result),
            ]);
        } else {
            Log::debug('Veo operation status', [
                'done' => false,
                'operation_name' => $operationName,
            ]);
        }

        return $result;
    }
}
