<?php

namespace App\Services;

use Google\Cloud\Speech\V1\SpeechClient;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\RecognitionAudio;
use Google\Cloud\Speech\V1\RecognitionConfig\AudioEncoding;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SpeechToTextService
{
    protected ?SpeechClient $client = null;
    protected string $provider;

    public function __construct()
    {
        $this->provider = config('services.stt.provider', 'google');
    }

    /**
     * Transcribe audio file to text using Google Cloud Speech-to-Text
     */
    public function transcribe(UploadedFile|string $audio): string
    {
        return match ($this->provider) {
            'google' => $this->transcribeWithGoogle($audio),
            default => throw new \Exception("Unsupported STT provider: {$this->provider}"),
        };
    }

    protected function transcribeWithGoogle(UploadedFile|string $audio): string
    {
        try {
            // Initialize Google Speech client with credentials
            $this->initializeGoogleClient();

            // Get audio content
            $audioPath = $audio instanceof UploadedFile
                ? $audio->getRealPath()
                : Storage::path($audio);

            $audioContent = file_get_contents($audioPath);

            // Determine audio encoding
            $mimeType = $audio instanceof UploadedFile
                ? $audio->getMimeType()
                : mime_content_type($audioPath);

            $encoding = $this->getAudioEncoding($mimeType);

            // Configure recognition
            $config = (new RecognitionConfig())
                ->setEncoding($encoding)
                ->setSampleRateHertz(16000)
                ->setLanguageCode('en-US')
                ->setEnableAutomaticPunctuation(true);

            $recognitionAudio = (new RecognitionAudio())
                ->setContent($audioContent);

            // Perform transcription
            $response = $this->client->recognize($config, $recognitionAudio);

            $transcript = '';
            foreach ($response->getResults() as $result) {
                $alternatives = $result->getAlternatives();
                if ($alternatives && count($alternatives) > 0) {
                    $transcript .= $alternatives[0]->getTranscript() . ' ';
                }
            }

            if (empty(trim($transcript))) {
                throw new \Exception('No transcription results returned');
            }

            return trim($transcript);

        } catch (\Exception $e) {
            Log::error('Google STT transcription failed', [
                'error' => $e->getMessage(),
                'provider' => 'google',
            ]);

            throw $e;
        } finally {
            if ($this->client) {
                $this->client->close();
            }
        }
    }

    protected function initializeGoogleClient(): void
    {
        $credentialsPath = config('services.google_cloud.credentials');

        if ($credentialsPath && file_exists($credentialsPath)) {
            putenv("GOOGLE_APPLICATION_CREDENTIALS={$credentialsPath}");
        }

        $this->client = new SpeechClient();
    }

    protected function getAudioEncoding(string $mimeType): int
    {
        return match ($mimeType) {
            'audio/wav', 'audio/wave' => AudioEncoding::LINEAR16,
            'audio/mp3', 'audio/mpeg' => AudioEncoding::MP3,
            'audio/ogg' => AudioEncoding::OGG_OPUS,
            'audio/webm' => AudioEncoding::WEBM_OPUS,
            default => AudioEncoding::LINEAR16,
        };
    }

    /**
     * Validate audio file format
     */
    public function validateAudioFile(UploadedFile $file): bool
    {
        $allowedMimes = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/webm', 'audio/ogg'];
        $maxSize = 25 * 1024; // 25MB in KB

        return in_array($file->getMimeType(), $allowedMimes)
            && $file->getSize() <= ($maxSize * 1024);
    }
}
