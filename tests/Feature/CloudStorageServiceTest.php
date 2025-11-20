<?php

namespace Tests\Feature;

use App\Services\CloudStorageService;
use Tests\TestCase;

class CloudStorageServiceTest extends TestCase
{
    /**
     * Test Cloud Storage service initialization
     */
    public function test_cloud_storage_service_initialization(): void
    {
        $service = app(CloudStorageService::class);

        $this->assertNotNull($service->getBucket());
        $this->assertNotNull($service->getProjectId());
        $this->assertEquals(config('services.text_to_video.gcs_bucket'), $service->getBucket());
    }

    /**
     * Test GCS URI to public URL conversion
     */
    public function test_gcs_uri_to_public_url_conversion(): void
    {
        $service = app(CloudStorageService::class);

        $gcsUri = 'gs://test-bucket/videos/test.mp4';
        $publicUrl = $service->convertGcsUriToPublicUrl($gcsUri);

        $this->assertEquals('https://storage.cloud.google.com/test-bucket/videos/test.mp4', $publicUrl);
    }

    /**
     * Test public URL generation
     */
    public function test_public_url_generation(): void
    {
        $service = app(CloudStorageService::class);

        $path = 'videos/test.mp4';
        $publicUrl = $service->getPublicUrl($path);

        $bucket = config('services.text_to_video.gcs_bucket');
        $expectedUrl = "https://storage.cloud.google.com/{$bucket}/{$path}";

        $this->assertEquals($expectedUrl, $publicUrl);
    }
}
