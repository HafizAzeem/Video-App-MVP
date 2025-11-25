<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class TextToVideoService
{
    private const TEST_VIDEO_URL = 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4';

    protected string $provider;

    protected string $mode;

    public function __construct(
        protected CloudStorageService $cloudStorageService
    ) {
        $this->provider = config('services.text_to_video.provider', 'google_veo');
        $this->mode = config('services.text_to_video.mode', 'test');
    }

    public function isProductionMode(): bool
    {
        return $this->mode === 'production';
    }

    public function isTestMode(): bool
    {
        return ! $this->isProductionMode();
    }

    /**
     * Start a Veo generation. In test mode this returns a completed sample video immediately.
     */
    public function startGeneration(string $prompt, array $options = []): array
    {
        if (empty($prompt)) {
            throw new \InvalidArgumentException('Video prompt cannot be empty');
        }

        if ($this->isTestMode()) {
            Log::info('TextToVideoService returning sample video (test mode)');

            return [
                'status' => 'completed',
                'provider' => 'simulated',
                'mode' => $this->mode,
                'video_url' => self::TEST_VIDEO_URL,
            ];
        }

        $client = $this->initializeHttpClient();
        $response = $this->callVeoAPI($client, $prompt, $options);

        return [
            'status' => 'processing',
            'provider' => $this->provider,
            'mode' => $this->mode,
            'operation_name' => $response['operation_name'],
            'storage_uri' => $response['storage_uri'],
        ];
    }

    /**
     * Poll an existing Veo operation for progress/completion.
     */
    public function pollOperation(string $operationName): array
    {
        if ($this->isTestMode()) {
            return [
                'done' => true,
                'progress' => 100,
                'video_url' => self::TEST_VIDEO_URL,
            ];
        }

        $client = $this->initializeHttpClient();
        $result = $this->pollOperationStatus($client, $operationName);

        $progress = Arr::get($result, 'metadata.progressPercent');
        $errorMessage = Arr::get($result, 'error.message');
        $gcsUri = $this->extractVideoUri($result);

        $payload = [
            'done' => (bool) Arr::get($result, 'done', false),
        ];

        if ($progress !== null) {
            $payload['progress'] = (int) round($progress);
        }

        if ($gcsUri) {
            $payload['gcs_uri'] = $gcsUri;
            $payload['video_url'] = $this->cloudStorageService->convertGcsUriToPublicUrl($gcsUri);
        }

        if ($errorMessage) {
            $payload['error'] = $errorMessage;
        }

        return $payload;
    }

    /**
     * Initialize HTTP client with OAuth2 authentication.
     */
    protected function initializeHttpClient(): Client
    {
        $credentialsConfig = config('services.google.credentials');
        $credentialsPath = $credentialsConfig;
        // If the path is not absolute, resolve using storage_path
        if ($credentialsPath && !str_starts_with($credentialsPath, DIRECTORY_SEPARATOR) && !preg_match('/^[A-Za-z]:\\\\/', $credentialsPath)) {
            $credentialsPath = storage_path('app/' . ltrim($credentialsPath, '/'));
        }
        // Debug credentials path
        Log::info('Checking credentials path', ['path' => $credentialsPath, 'exists' => file_exists($credentialsPath)]);

        if (! $credentialsPath || ! file_exists($credentialsPath)) {
            throw new \RuntimeException("Google credentials file not found: {$credentialsPath}");
        }

        $credentials = json_decode(file_get_contents($credentialsPath), true);

        if (! $credentials) {
            throw new \RuntimeException('Invalid Google credentials file');
        }

        $auth = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/cloud-platform',
            $credentials
        );

        $accessToken = $auth->fetchAuthToken();

        if (! isset($accessToken['access_token'])) {
            throw new \RuntimeException('Failed to obtain OAuth2 access token');
        }

        return new Client([
            'headers' => [
                'Authorization' => 'Bearer '.$accessToken['access_token'],
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Call Veo API to start generation.
     */
    protected function callVeoAPI(Client $client, string $prompt, array $options = []): array
    {
        $projectId = config('services.google.project_id');
        $location = config('services.text_to_video.location', 'us-central1');
        $model = config('services.text_to_video.model', 'veo-3.1-generate-001');
        $bucket = config('services.text_to_video.gcs_bucket');

        if (empty($projectId)) {
            throw new \RuntimeException('GOOGLE_CLOUD_PROJECT_ID is not configured');
        }

        if (empty($bucket)) {
            throw new \RuntimeException('GCS_BUCKET is not configured');
        }

        $outputDir = Arr::get($options, 'output_directory', 'videos/'.now()->format('Y-m-d').'/'.uniqid());
        $storageUri = Arr::get($options, 'storageUri', "gs://{$bucket}/{$outputDir}/");

        $requestBody = [
            'instances' => [
                [
                    'prompt' => $prompt,
                ],
            ],
            'parameters' => [
                'storageUri' => $storageUri,
                'sampleCount' => Arr::get($options, 'sampleCount', 1),
                'durationSeconds' => Arr::get($options, 'durationSeconds', 8),
                'aspectRatio' => Arr::get($options, 'aspectRatio', '16:9'),
                'resolution' => Arr::get($options, 'resolution', '1080p'),
                'generateAudio' => Arr::get($options, 'generateAudio', true),
            ],
        ];

        $endpoint = "https://{$location}-aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$location}/publishers/google/models/{$model}:predictLongRunning";

        Log::info('Calling Veo API', [
            'endpoint' => $endpoint,
            'model' => $model,
            'storage_uri' => $storageUri,
        ]);

        $response = $client->post($endpoint, ['json' => $requestBody]);
        $result = json_decode($response->getBody()->getContents(), true);

        $operationName = $result['name'] ?? null;

        if (! $operationName) {
            throw new \RuntimeException('No operation name returned from Veo API');
        }

        Log::info('Veo predictLongRunning accepted', [
            'operation_name' => $operationName,
        ]);

        return [
            'operation_name' => $operationName,
            'storage_uri' => $storageUri,
        ];
    }

    /**
     * Poll Veo operation status.
     */
    protected function pollOperationStatus(Client $client, string $operationName): array
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

        if ($result['done'] ?? false) {
            Log::info('Veo operation completed', [
                'operation_name' => $operationName,
                'progress' => Arr::get($result, 'metadata.progressPercent'),
            ]);
        } else {
            Log::debug('Veo operation poll', [
                'operation_name' => $operationName,
                'progress' => Arr::get($result, 'metadata.progressPercent'),
            ]);
        }

        return $result;
    }

    /**
     * Extract a usable video URI from the Veo response payload.
     */
    protected function extractVideoUri(array $result): ?string
    {
        $response = Arr::get($result, 'response', []);

        return $response['videos'][0]['gcsUri']
            ?? $response['generatedSamples'][0]['videoUri']
            ?? $response['predictions'][0]['gcsUri']
            ?? $response['gcsUri']
            ?? null;
    }
}
