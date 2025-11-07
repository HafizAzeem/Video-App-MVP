<?php

namespace App\Services;

use Google\Cloud\Speech\V1\RecognitionAudio;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\RecognitionConfig\AudioEncoding;
use Google\Cloud\Speech\V1\SpeechClient;
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
            $sampleRate = $this->getSampleRate($mimeType);

            Log::info('Processing audio for transcription', [
                'mime_type' => $mimeType,
                'encoding' => $encoding,
                'sample_rate' => $sampleRate,
                'use_auto_sample_rate' => in_array($encoding, [AudioEncoding::WEBM_OPUS, AudioEncoding::OGG_OPUS]),
            ]);

            // Configure recognition
            $config = (new RecognitionConfig)
                ->setEncoding($encoding)
                ->setLanguageCode('en-US')
                ->setEnableAutomaticPunctuation(true);

            // Only set sample rate for non-OPUS formats
            // For WEBM_OPUS, let it auto-detect from the file header
            if ($encoding !== AudioEncoding::WEBM_OPUS && $encoding !== AudioEncoding::OGG_OPUS) {
                $config->setSampleRateHertz($sampleRate);
            }

            $recognitionAudio = (new RecognitionAudio)
                ->setContent($audioContent);

            // Perform transcription
            $response = $this->client->recognize($config, $recognitionAudio);

            $transcript = '';
            foreach ($response->getResults() as $result) {
                $alternatives = $result->getAlternatives();
                if ($alternatives && count($alternatives) > 0) {
                    $transcript .= $alternatives[0]->getTranscript().' ';
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

        // Convert relative path to absolute path if needed
        if ($credentialsPath && ! file_exists($credentialsPath)) {
            $credentialsPath = base_path($credentialsPath);
        }

        if ($credentialsPath && file_exists($credentialsPath)) {
            putenv("GOOGLE_APPLICATION_CREDENTIALS={$credentialsPath}");
            Log::info('Google credentials set', ['path' => $credentialsPath]);
        } else {
            Log::error('Google credentials file not found', [
                'config_path' => config('services.google_cloud.credentials'),
                'checked_path' => $credentialsPath,
            ]);
            throw new \Exception('Google Cloud credentials file not found');
        }

        $this->client = new SpeechClient;
    }

    protected function getAudioEncoding(string $mimeType): int
    {
        return match ($mimeType) {
            'audio/wav', 'audio/wave' => AudioEncoding::LINEAR16,
            'audio/mp3', 'audio/mpeg' => AudioEncoding::MP3,
            'audio/ogg' => AudioEncoding::OGG_OPUS,
            'audio/webm', 'video/webm' => AudioEncoding::WEBM_OPUS,
            default => AudioEncoding::LINEAR16,
        };
    }

    protected function getSampleRate(string $mimeType): int
    {
        // Return sample rates for formats that need explicit configuration
        return match ($mimeType) {
            'audio/wav', 'audio/wave' => 16000,
            'audio/mp3', 'audio/mpeg' => 16000,
            default => 48000, // Default for most formats
        };
    }

    /**
     * Validate audio file format
     */
    public function validateAudioFile(UploadedFile $file): bool
    {
        $allowedMimes = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/webm', 'audio/ogg', 'video/webm'];
        $allowedExtensions = ['mp3', 'wav', 'webm', 'ogg'];
        $maxSize = 25 * 1024; // 25MB in KB

        $mimeTypeValid = in_array($file->getMimeType(), $allowedMimes);
        $extensionValid = in_array($file->getClientOriginalExtension(), $allowedExtensions);
        $sizeValid = $file->getSize() <= ($maxSize * 1024);

        Log::info('Audio file validation', [
            'mime_type' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension(),
            'size' => $file->getSize(),
            'mime_valid' => $mimeTypeValid,
            'ext_valid' => $extensionValid,
            'size_valid' => $sizeValid,
        ]);

        return ($mimeTypeValid || $extensionValid) && $sizeValid;
    }
}
