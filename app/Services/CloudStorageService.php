<?php

namespace App\Services;

use Google\Cloud\Storage\StorageClient;
use Illuminate\Support\Facades\Log;

class CloudStorageService
{
    protected StorageClient $client;

    protected string $bucket;

    protected string $projectId;

    public function __construct()
    {
        $this->projectId = config('services.google.project_id');
        $this->bucket = config('services.text_to_video.gcs_bucket');

        $this->client = new StorageClient([
            'projectId' => $this->projectId,
            'keyFilePath' => config('services.google.credentials'),
        ]);
    }

    /**
     * Upload a file to Google Cloud Storage
     *
     * @param  string  $localPath  Full local path to the file
     * @param  string  $destinationPath  Destination path in bucket (e.g., 'videos/video-123.mp4')
     * @param  array  $options  Additional options (metadata, contentType, etc.)
     * @return array ['gcsUri' => 'gs://bucket/path', 'publicUrl' => 'https://...']
     */
    public function uploadFile(string $localPath, string $destinationPath, array $options = []): array
    {
        try {
            if (! file_exists($localPath)) {
                throw new \InvalidArgumentException("File not found: {$localPath}");
            }

            $bucket = $this->client->bucket($this->bucket);

            // Default options
            $uploadOptions = array_merge([
                'name' => $destinationPath,
                'metadata' => [
                    'uploaded_at' => now()->toIso8601String(),
                    'uploaded_by' => 'laravel-app',
                ],
            ], $options);

            // Upload the file
            $object = $bucket->upload(
                fopen($localPath, 'r'),
                $uploadOptions
            );

            $gcsUri = "gs://{$this->bucket}/{$destinationPath}";
            $publicUrl = $this->getPublicUrl($destinationPath);

            Log::info('File uploaded to Cloud Storage', [
                'local_path' => $localPath,
                'gcs_uri' => $gcsUri,
                'public_url' => $publicUrl,
            ]);

            return [
                'gcsUri' => $gcsUri,
                'publicUrl' => $publicUrl,
                'bucket' => $this->bucket,
                'path' => $destinationPath,
            ];

        } catch (\Exception $e) {
            Log::error('Cloud Storage upload failed', [
                'local_path' => $localPath,
                'destination' => $destinationPath,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException("Failed to upload file: {$e->getMessage()}");
        }
    }

    /**
     * Upload file content (from string/stream) to Cloud Storage
     *
     * @param  string  $content  File content
     * @param  string  $destinationPath  Destination path in bucket
     * @param  array  $options  Additional options
     * @return array ['gcsUri' => 'gs://bucket/path', 'publicUrl' => 'https://...']
     */
    public function uploadContent(string $content, string $destinationPath, array $options = []): array
    {
        try {
            $bucket = $this->client->bucket($this->bucket);

            $uploadOptions = array_merge([
                'name' => $destinationPath,
                'metadata' => [
                    'uploaded_at' => now()->toIso8601String(),
                    'uploaded_by' => 'laravel-app',
                ],
            ], $options);

            $object = $bucket->upload($content, $uploadOptions);

            $gcsUri = "gs://{$this->bucket}/{$destinationPath}";
            $publicUrl = $this->getPublicUrl($destinationPath);

            Log::info('Content uploaded to Cloud Storage', [
                'gcs_uri' => $gcsUri,
                'content_size' => strlen($content),
            ]);

            return [
                'gcsUri' => $gcsUri,
                'publicUrl' => $publicUrl,
                'bucket' => $this->bucket,
                'path' => $destinationPath,
            ];

        } catch (\Exception $e) {
            Log::error('Cloud Storage content upload failed', [
                'destination' => $destinationPath,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException("Failed to upload content: {$e->getMessage()}");
        }
    }

    /**
     * Download a file from Cloud Storage
     *
     * @param  string  $sourcePath  Source path in bucket
     * @param  string  $destinationPath  Local destination path
     */
    public function downloadFile(string $sourcePath, string $destinationPath): bool
    {
        try {
            $bucket = $this->client->bucket($this->bucket);
            $object = $bucket->object($sourcePath);

            if (! $object->exists()) {
                throw new \RuntimeException("Object not found: {$sourcePath}");
            }

            $object->downloadToFile($destinationPath);

            Log::info('File downloaded from Cloud Storage', [
                'source' => $sourcePath,
                'destination' => $destinationPath,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Cloud Storage download failed', [
                'source' => $sourcePath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete a file from Cloud Storage
     *
     * @param  string  $path  Path to file in bucket
     */
    public function deleteFile(string $path): bool
    {
        try {
            $bucket = $this->client->bucket($this->bucket);
            $object = $bucket->object($path);

            if (! $object->exists()) {
                Log::warning('Object not found for deletion', ['path' => $path]);

                return false;
            }

            $object->delete();

            Log::info('File deleted from Cloud Storage', ['path' => $path]);

            return true;

        } catch (\Exception $e) {
            Log::error('Cloud Storage deletion failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if a file exists in Cloud Storage
     */
    public function fileExists(string $path): bool
    {
        try {
            $bucket = $this->client->bucket($this->bucket);
            $object = $bucket->object($path);

            return $object->exists();
        } catch (\Exception $e) {
            Log::error('Cloud Storage existence check failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get public URL for a file
     *
     * @param  string  $path  Path to file in bucket
     */
    public function getPublicUrl(string $path): string
    {
        // Use authenticated URL format for Cloud Storage
        return "https://storage.cloud.google.com/{$this->bucket}/{$path}";
    }

    /**
     * Get signed URL for temporary access (valid for 1 hour by default)
     *
     * @param  string  $path  Path to file in bucket
     * @param  int  $expiresInMinutes  Expiration time in minutes
     */
    public function getSignedUrl(string $path, int $expiresInMinutes = 60): string
    {
        try {
            $bucket = $this->client->bucket($this->bucket);
            $object = $bucket->object($path);

            $url = $object->signedUrl(
                new \DateTime("+{$expiresInMinutes} minutes")
            );

            return $url;

        } catch (\Exception $e) {
            Log::error('Failed to generate signed URL', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException("Failed to generate signed URL: {$e->getMessage()}");
        }
    }

    /**
     * List all files in a directory
     *
     * @param  string  $prefix  Directory prefix (e.g., 'videos/')
     * @return array List of file paths
     */
    public function listFiles(string $prefix = ''): array
    {
        try {
            $bucket = $this->client->bucket($this->bucket);
            $objects = $bucket->objects(['prefix' => $prefix]);

            $files = [];
            foreach ($objects as $object) {
                $files[] = [
                    'name' => $object->name(),
                    'size' => $object->info()['size'] ?? 0,
                    'contentType' => $object->info()['contentType'] ?? 'unknown',
                    'updated' => $object->info()['updated'] ?? null,
                ];
            }

            return $files;

        } catch (\Exception $e) {
            Log::error('Cloud Storage list failed', [
                'prefix' => $prefix,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Convert GCS URI (gs://bucket/path) to public URL
     *
     * @param  string  $gcsUri  GCS URI (e.g., 'gs://bucket/path/video.mp4')
     */
    public function convertGcsUriToPublicUrl(string $gcsUri): string
    {
        // Remove 'gs://' prefix
        $path = str_replace('gs://', '', $gcsUri);

        // Extract bucket and file path
        $parts = explode('/', $path, 2);
        $bucket = $parts[0] ?? $this->bucket;
        $filePath = $parts[1] ?? '';

        // Use authenticated URL format
        return "https://storage.cloud.google.com/{$bucket}/{$filePath}";
    }

    /**
     * Get bucket name
     */
    public function getBucket(): string
    {
        return $this->bucket;
    }

    /**
     * Get project ID
     */
    public function getProjectId(): string
    {
        return $this->projectId;
    }
}
