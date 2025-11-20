# 🚀 Quick Start: Using Real Veo Video Generation

This guide shows you how to switch from test mode to production mode and start generating real videos with Google Veo API.

---

## ✅ Prerequisites Checklist

Before switching to production mode, ensure you have:

- [ ] Google Cloud project created
- [ ] Vertex AI API enabled in Google Cloud Console
- [ ] Cloud Storage API enabled
- [ ] Service account created with these roles:
  - Vertex AI User
  - Storage Admin
  - Storage Object Admin
- [ ] Service account key downloaded to `storage/app/google-credentials.json`
- [ ] Google Cloud Storage bucket created (e.g., `lasbookbucket`)
- [ ] Billing enabled with free $300 credits or payment method
- [ ] `google/cloud-storage` package installed (`composer require google/cloud-storage`)
- [ ] `google/cloud-ai-platform` package installed (already included)

---

## 🔧 Step-by-Step Activation

### Step 1: Verify Your Credentials File

Check that your service account JSON file exists:

```bash
# Windows PowerShell
Test-Path storage\app\google-credentials.json

# Should return: True
```

If it returns `False`, download your service account key from Google Cloud Console.

### Step 2: Update Your .env File

Open `.env` and update these variables:

```env
# Change from test to production
VIDEO_MODE=production

# Add your bucket name
GCS_BUCKET=lasbookbucket

# Add your project ID (from google-credentials.json)
GCS_PROJECT_ID=gen-lang-client-0389900742

# Verify these are set correctly
GOOGLE_CLOUD_PROJECT_ID=gen-lang-client-0389900742
GOOGLE_CLOUD_LOCATION=us-central1
GOOGLE_APPLICATION_CREDENTIALS=storage/app/google-credentials.json

# Optional: Choose Veo model (default is veo-3.1-generate-001)
VEO_MODEL=veo-3.1-generate-001
```

### Step 3: Clear Laravel Cache

```bash
php artisan config:clear
php artisan cache:clear
```

### Step 4: Test the Connection

Run a quick test to verify Cloud Storage connection:

```bash
php artisan tinker
```

Then in Tinker:

```php
$storage = app(\App\Services\CloudStorageService::class);
echo "Bucket: " . $storage->getBucket() . "\n";
echo "Project: " . $storage->getProjectId() . "\n";
echo "Cloud Storage connected successfully!\n";
exit;
```

Expected output:
```
Bucket: lasbookbucket
Project: gen-lang-client-0389900742
Cloud Storage connected successfully!
```

### Step 5: Generate Your First Real Video

1. Go to your application (e.g., `http://localhost:8000`)
2. Navigate to the video generation page
3. Enter a video prompt, for example:
   ```
   A serene sunset over a mountain lake with birds flying
   ```
4. Submit the request
5. Wait for video generation (this will take 5-10 minutes for the first time)

### Step 6: Monitor the Process

**Watch the logs in real-time:**

```bash
# Windows PowerShell
Get-Content storage\logs\laravel.log -Wait -Tail 50
```

**What to look for:**
- ✅ `Starting real Veo API video generation`
- ✅ `Calling Veo API`
- ✅ `Veo API call initiated`
- ✅ `Veo operation status`
- ✅ `Veo video generation completed`

**Check Google Cloud Console:**
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Navigate to "Vertex AI" → "Dashboard"
3. You should see API requests being made

### Step 7: Verify Video in Cloud Storage

1. Go to [Cloud Storage Buckets](https://console.cloud.google.com/storage/browser)
2. Click on your bucket (e.g., `lasbookbucket`)
3. Navigate to `videos/` folder
4. You should see a folder with today's date
5. Inside, you'll find your generated video file (`sample_0.mp4`)

---

## 🎛️ Configuration Options

### Veo Model Selection

Choose the model that fits your needs:

```env
# Highest quality (slowest, most expensive)
VEO_MODEL=veo-3.1-generate-001

# Fast generation (faster, cheaper)
VEO_MODEL=veo-3.1-fast-generate-001

# Stable version
VEO_MODEL=veo-3.0-generate-001
```

### Video Parameters

Edit `app/Services/TextToVideoService.php` to customize video parameters:

```php
'parameters' => [
    'storageUri' => $storageUri,
    'sampleCount' => 1,              // Number of videos to generate
    'durationSeconds' => 8,          // 4, 6, or 8 seconds
    'aspectRatio' => '16:9',         // '16:9' or '9:16'
    'resolution' => '1080p',         // '1080p' or '720p'
    'generateAudio' => true,         // true or false
],
```

**Cost Optimization Tips:**
- Use `durationSeconds: 4` for cheaper videos
- Use `resolution: '720p'` to reduce costs
- Use `veo-3.1-fast-generate-001` model for faster generation

---

## 💰 Cost Monitoring

### Set Up Billing Alerts

1. Go to [Google Cloud Console - Billing](https://console.cloud.google.com/billing)
2. Select your billing account
3. Click "Budgets & alerts"
4. Click "Create Budget"
5. Set alerts at:
   - 50% of budget
   - 75% of budget
   - 90% of budget

**Recommended Budget:** Start with $50/month

### Estimated Costs Per Video

| Duration | Resolution | Model | Est. Cost |
|----------|-----------|-------|-----------|
| 4s | 720p | Fast | $0.25 |
| 8s | 720p | Fast | $0.50 |
| 8s | 1080p | Standard | $1.00 |

**Note:** Veo pricing is still in preview and subject to change. Monitor your actual costs in Google Cloud Console.

### Check Current Usage

```bash
# View total videos generated
php artisan tinker --execute="echo \App\Models\Video::where('status', 'completed')->count() . ' videos generated';"

# View today's videos
php artisan tinker --execute="echo \App\Models\Video::whereDate('created_at', today())->count() . ' videos today';"
```

---

## 🔄 Switching Back to Test Mode

If you want to switch back to free test mode:

1. Edit `.env`:
   ```env
   VIDEO_MODE=test
   ```

2. Clear cache:
   ```bash
   php artisan config:clear
   ```

3. Test videos will be used again (no API calls, FREE)

---

## 🐛 Common Issues & Solutions

### Issue: "Failed to obtain OAuth2 access token"

**Cause:** Service account credentials not found or invalid

**Solution:**
1. Verify file exists: `storage/app/google-credentials.json`
2. Check file path in `.env`: `GOOGLE_APPLICATION_CREDENTIALS=storage/app/google-credentials.json`
3. Re-download service account key from Google Cloud Console
4. Clear cache: `php artisan config:clear`

### Issue: "GCS_BUCKET not configured"

**Cause:** Bucket name not set in `.env`

**Solution:**
1. Add to `.env`: `GCS_BUCKET=your-bucket-name`
2. Clear cache: `php artisan config:clear`

### Issue: "Video generation timed out after 10 minutes"

**Cause:** Veo API is taking longer than expected (normal for high-quality videos)

**Solution:**
1. This is normal for first-time generation
2. Check Google Cloud Console → Vertex AI → Operations
3. If operation is still running, wait and check status manually
4. Consider using `veo-3.1-fast-generate-001` for faster generation

### Issue: "Permission denied" when accessing Vertex AI

**Cause:** Service account missing required roles

**Solution:**
1. Go to Google Cloud Console → IAM & Admin → IAM
2. Find your service account
3. Add these roles:
   - Vertex AI User
   - Storage Admin
   - Storage Object Admin
4. Wait 2-3 minutes for permissions to propagate

### Issue: Videos not appearing in Cloud Storage

**Cause:** Check operation status in Google Cloud Console

**Solution:**
1. Go to [Vertex AI Operations](https://console.cloud.google.com/vertex-ai/operations)
2. Find your video generation operation
3. Check status (Running, Succeeded, Failed)
4. If failed, check error message
5. View logs: `storage/logs/laravel.log`

---

## 📊 Monitoring Dashboard

### Application Logs

```bash
# Real-time log monitoring
tail -f storage/logs/laravel.log | grep -i "veo\|video\|cloud storage"
```

### Google Cloud Console

**Key Pages to Monitor:**

1. **Vertex AI Dashboard**
   - URL: https://console.cloud.google.com/vertex-ai
   - Shows: API requests, quotas, usage

2. **Cloud Storage Buckets**
   - URL: https://console.cloud.google.com/storage/browser
   - Shows: Generated videos, storage usage

3. **Billing Overview**
   - URL: https://console.cloud.google.com/billing
   - Shows: Current spend, forecasted costs

4. **Logs Explorer**
   - URL: https://console.cloud.google.com/logs
   - Shows: Detailed API call logs

---

## ✅ Verification Checklist

After switching to production mode, verify:

- [ ] `.env` has `VIDEO_MODE=production`
- [ ] `GCS_BUCKET` is set in `.env`
- [ ] `google-credentials.json` file exists
- [ ] Laravel cache cleared
- [ ] Cloud Storage service connects successfully
- [ ] First test video generates successfully
- [ ] Video appears in Cloud Storage bucket
- [ ] Public URL is accessible
- [ ] Billing alerts are configured
- [ ] Logs show successful API calls

---

## 📞 Support Resources

- **Application Logs:** `storage/logs/laravel.log`
- **Google Cloud Support:** https://cloud.google.com/support
- **Veo API Documentation:** https://cloud.google.com/vertex-ai/generative-ai/docs/video/overview
- **Cloud Storage Documentation:** https://cloud.google.com/storage/docs
- **Community Forums:** https://discuss.google.dev/c/google-cloud/14/

---

## 🎯 Next Steps

1. ✅ **Generate First Video:** Test with a simple prompt
2. ✅ **Monitor Costs:** Check Google Cloud billing after first video
3. ✅ **Optimize Settings:** Adjust duration, resolution based on needs
4. ✅ **Set Budgets:** Configure billing alerts
5. ✅ **Scale Up:** Start generating videos for your users

---

**Status:** Ready for production! 🚀

**Last Updated:** November 19, 2025
