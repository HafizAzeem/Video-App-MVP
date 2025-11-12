# Google Veo Video Generation - Debugging Guide

## Summary of Changes

I've completely rewritten your `TextToVideoService` to properly integrate with the Google Veo API and added comprehensive error handling, logging, and debugging capabilities.

## Key Issues Fixed

### 1. **Quota Limits (HTTP 429 - Too Many Requests)**

**What was missing:**
- No handling for quota exceeded errors
- No rate limit detection or retry logic

**What's now implemented:**
```php
case 429:
    // Quota exceeded
    $retryAfter = $response->header('Retry-After') ?? 60;
    Log::warning('API quota exceeded', [
        'retry_after' => $retryAfter,
        'error' => $errorMessage,
    ]);
    throw new \RuntimeException("API quota exceeded: {$errorMessage}. Please try again later or upgrade your quota.");
```

**How to check:**
- Look in `storage/logs/laravel.log` for `API quota exceeded` messages
- Check error message for `Please try again later or upgrade your quota`
- Google Cloud Console → IAM & Admin → Quotas

### 2. **Content Policy Violations**

**What was missing:**
- No pre-validation of prompts
- Silent failures when content violates safety policies

**What's now implemented:**
```php
protected function checkContentPolicy(string $prompt): array
{
    $forbiddenPatterns = [
        '/\b(violence|gore|explicit)\b/i',
        '/\b(hate|racist|discriminatory)\b/i',
    ];
    // Returns: ['safe' => false, 'reason' => 'Details...']
}
```

**How to check:**
- Error message will explicitly state: `Content policy violation: [reason]`
- Logged before API call is made
- Extend `$forbiddenPatterns` array as needed

### 3. **API Key/Authentication Issues**

**What was missing:**
- No validation that API key exists before making requests
- Generic error messages on auth failures

**What's now implemented:**
```php
if (empty($this->apiKey)) {
    throw new \RuntimeException('Google API key is not configured. Check GEMINI_API_KEY in .env');
}

case 401:
    throw new \RuntimeException("Authentication failed: {$errorMessage}. Check your API key in .env (GEMINI_API_KEY).");

case 403:
    throw new \RuntimeException("Permission denied: {$errorMessage}. Ensure your API key has access to Veo API.");
```

**How to check:**
1. Verify `.env` has `GEMINI_API_KEY` set
2. Check logs for "Authentication failed" or "Permission denied"
3. Verify API key in Google Cloud Console → APIs & Services → Credentials

### 4. **Network/Connectivity Issues**

**What was missing:**
- No timeout configuration
- No retry logic for transient failures
- No specific handling for connection errors

**What's now implemented:**
```php
// Configurable timeout
$response = Http::timeout(120) // 2 minutes timeout

// Automatic retry with exponential backoff
protected function makeApiRequestWithRetry(string $prompt, array $options): array
{
    $attempt = 0;
    while ($attempt < $this->maxRetries) {
        try {
            return $this->makeVeoApiRequest($prompt, $options);
        } catch (RequestException $e) {
            $delay = $this->retryDelay * pow(2, $attempt - 1); // Exponential backoff
            sleep($delay);
        }
    }
}

// Network error handling
protected function handleNetworkError(ConnectionException $e): array
{
    Log::error('Network connection failed', ['error' => $e->getMessage()]);
    throw new \RuntimeException('Network connection failed. Please check your internet connection and try again.');
}
```

**How to check:**
- Look for "Network connection failed" in logs
- Check for retry attempts: "API request failed, retrying in Xs"
- Error will mention "check your internet connection"

### 5. **95% Progress Stuck Issue**

**What was wrong:**
- Progress was capped at 95% in the old mock code
- No real API polling happening
- No detection of "stuck" progress

**What's now fixed:**
```php
// In checkVeoOperationStatus()
$progress = $data['metadata']['progressPercent'] ?? 50;
return [
    'status' => 'processing',
    'progress' => min($progress, 95), // Cap at 95% until truly done - prevents false 100%
];

// In GenerateVideoJob - detect stuck progress
if (isset($status['progress']) && $status['progress'] >= 95 && $attempt > 30) {
    Log::warning('Video stuck at high progress percentage', [
        'video_id' => $this->video->id,
        'progress' => $status['progress'],
        'attempts' => $attempt + 1,
    ]);
}
```

**Real progress is now:**
- Read from actual API response (`progressPercent`)
- Logged on every check
- Stuck detection alerts you if at 95%+ for 5+ minutes

## Complete Error Logging

### Where to Find Errors

**All errors are now logged to `storage/logs/laravel.log` with full details:**

```php
// API errors include:
Log::error('Detailed API Error', [
    'status_code' => $response->status(),
    'status_text' => $response->reason(),
    'error_code' => $body['error']['code'] ?? null,
    'error_message' => $body['error']['message'] ?? null,
    'error_status' => $body['error']['status'] ?? null,
    'error_details' => $body['error']['details'] ?? null,
    'full_body' => $body,
    'headers' => $response->headers(),
]);
```

### What Gets Logged

1. **On every API request:**
   - Endpoint URL
   - Full request payload
   - Prompt preview (first 100 chars)

2. **On every API response:**
   - Status code
   - Response headers
   - Response body (first 500 chars)

3. **On status checks:**
   - Current attempt number
   - Status value
   - Progress percentage
   - Video URL (when available)

4. **On errors:**
   - Full exception stack trace
   - Error code and message from API
   - Categorized error type (quota, auth, network, etc.)

## How to Debug Step-by-Step

### Step 1: Check Environment Variables

```bash
# Check if API key is set
php artisan tinker
>>> config('gemini.api_key')
>>> config('services.google.project_id')
>>> config('services.google.location')
```

### Step 2: Monitor Logs in Real-Time

```bash
# Watch logs as video generates
tail -f storage/logs/laravel.log

# On Windows PowerShell:
Get-Content storage\logs\laravel.log -Wait -Tail 50
```

### Step 3: Check Specific Error Patterns

```bash
# Find quota errors
grep -i "quota exceeded" storage/logs/laravel.log

# Find authentication errors
grep -i "authentication failed" storage/logs/laravel.log

# Find stuck progress
grep -i "stuck at high progress" storage/logs/laravel.log
```

### Step 4: Verify API Access

Run this test command:
```bash
php artisan tinker

# Test API connectivity
>>> $service = app(\App\Services\TextToVideoService::class);
>>> $service->generate("A happy child reading a colorful book");
```

## Common Error Messages & Solutions

| Error Message | Cause | Solution |
|---------------|-------|----------|
| `Google API key is not configured` | Missing `GEMINI_API_KEY` in `.env` | Add your API key to `.env` file |
| `Authentication failed` | Invalid or expired API key | Generate new key in Google Cloud Console |
| `Permission denied` | API key lacks Veo access | Enable Veo API for your project |
| `API quota exceeded` | Hit rate limits | Wait or upgrade quota |
| `Content policy violation` | Unsafe prompt content | Modify prompt to remove flagged content |
| `Network connection failed` | Internet/connectivity issue | Check connection, retry |
| `Video stuck at high progress` | API processing delay | Wait longer or contact Google support |
| `Video completed but no URL received` | API returned success without video | Check API response in logs |

## Configuration Reference

### Required Environment Variables

```env
# Google AI API Key (for Gemini/Veo)
GEMINI_API_KEY=your-api-key-here

# Google Cloud Project ID
GOOGLE_CLOUD_PROJECT_ID=your-project-id

# Google Cloud Region (optional, defaults to us-central1)
GOOGLE_CLOUD_LOCATION=us-central1
```

### Retry Configuration

Adjust in `TextToVideoService.php`:
```php
protected int $maxRetries = 3;      // Number of retry attempts
protected int $retryDelay = 2;      // Base delay in seconds (uses exponential backoff)
```

### Polling Configuration

Adjust in `GenerateVideoJob.php`:
```php
$maxAttempts = 120;  // 20 minutes max (120 * 10 seconds)
sleep(10);           // Wait 10 seconds between checks
```

## Important Notes

### Google Veo API Availability

**Google Veo is currently in limited preview.** If you don't have access:

1. The API endpoint may return 404 or 403
2. You'll see errors like "Permission denied" or "API not found"
3. **Solution:** Request access at https://cloud.google.com/vertex-ai or contact Google

### Alternative: Use Imagen Instead

If Veo isn't available, you can temporarily use Imagen for image generation:

```php
// In makeVeoApiRequest(), change endpoint to:
$endpoint = "https://{$this->location}-aiplatform.googleapis.com/v1/projects/{$this->projectId}/locations/{$this->location}/publishers/google/models/imagegeneration@006:predict";
```

## Testing Checklist

- [ ] API key is valid and configured
- [ ] Project ID is correct
- [ ] Veo API is enabled in Google Cloud Console
- [ ] Logs show successful API requests
- [ ] Progress updates are visible in logs
- [ ] Videos complete successfully
- [ ] Error messages are clear and actionable
- [ ] Stuck progress is detected and logged

## Need More Help?

1. **Check logs first:** `storage/logs/laravel.log`
2. **Run queue worker with verbose output:** `php artisan queue:work --verbose`
3. **Enable debug mode:** Set `APP_DEBUG=true` in `.env`
4. **Check Google Cloud Console:** Look for API errors and quota usage

## Summary

Your new implementation now has:

✅ **Quota limit detection** with specific error messages  
✅ **Content policy pre-validation** before API calls  
✅ **API key validation** with helpful error messages  
✅ **Network retry logic** with exponential backoff  
✅ **Comprehensive error logging** with full API responses  
✅ **Progress tracking** from real API data  
✅ **Stuck detection** for progress above 95%  
✅ **Timeout handling** with detailed logging  

All errors now provide **actionable** messages telling you exactly what went wrong and how to fix it!
