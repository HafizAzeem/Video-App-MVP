<?php

namespace App\Services;

use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\SsmlVoiceGender;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TextToSpeechService
{
    protected ?TextToSpeechClient $client = null;
    protected string $provider;

    public function __construct()
    {
        $this->provider = config('services.tts.provider', 'google');
    }

    /**
     * Generate audio from text using Google Cloud Text-to-Speech
     */
    public function generate(string $text, string $voice = 'en-US-Neural2-C'): string
    {
        return match ($this->provider) {
            'google' => $this->generateWithGoogle($text, $voice),
            default => throw new \Exception("Unsupported TTS provider: {$this->provider}"),
        };
    }

    protected function generateWithGoogle(string $text, string $voiceName): string
    {
        try {
            // Initialize Google TTS client
            $this->initializeGoogleClient();

            // Set the text input to be synthesized
            $input = (new SynthesisInput())
                ->setText($text);

            // Build the voice request parameters
            // Voice name format: {language-code}-{voice-type}-{voice-name}
            // Example: en-US-Neural2-C, en-US-Wavenet-D
            $voice = (new VoiceSelectionParams())
                ->setLanguageCode('en-US')
                ->setName($voiceName)
                ->setSsmlGender(SsmlVoiceGender::NEUTRAL);

            // Select the type of audio file you want returned
            $audioConfig = (new AudioConfig())
                ->setAudioEncoding(AudioEncoding::MP3)
                ->setSpeakingRate(1.0)
                ->setPitch(0.0)
                ->setVolumeGainDb(0.0);

            // Perform the text-to-speech request
            $response = $this->client->synthesizeSpeech($input, $voice, $audioConfig);

            // Get the audio content
            $audioContent = $response->getAudioContent();

            if (empty($audioContent)) {
                throw new \Exception('No audio content returned from Google TTS');
            }

            // Store the audio file
            $filename = 'tts/' . uniqid() . '.mp3';
            Storage::put($filename, $audioContent);

            Log::info('Google TTS generated successfully', [
                'filename' => $filename,
                'voice' => $voiceName,
                'text_length' => strlen($text),
            ]);

            return $filename;

        } catch (\Exception $e) {
            Log::error('Google TTS generation failed', [
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

        $this->client = new TextToSpeechClient();
    }

    /**
     * Get available voices for a language
     */
    public function getAvailableVoices(string $languageCode = 'en-US'): array
    {
        try {
            $this->initializeGoogleClient();

            $response = $this->client->listVoices($languageCode);
            $voices = [];

            foreach ($response->getVoices() as $voice) {
                $voices[] = [
                    'name' => $voice->getName(),
                    'language_codes' => iterator_to_array($voice->getLanguageCodes()),
                    'ssml_gender' => SsmlVoiceGender::name($voice->getSsmlGender()),
                    'natural_sample_rate_hertz' => $voice->getNaturalSampleRateHertz(),
                ];
            }

            return $voices;

        } catch (\Exception $e) {
            Log::error('Failed to get Google TTS voices', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            if ($this->client) {
                $this->client->close();
            }
        }
    }
}
