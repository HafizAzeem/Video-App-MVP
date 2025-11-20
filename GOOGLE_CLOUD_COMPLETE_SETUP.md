# Complete Google Cloud Setup Guide for Laravel Video App

This comprehensive guide covers all Google Cloud services and APIs used in this application, including setup instructions, authentication, and implementation details.

---

## 📋 Table of Contents

1. [Overview of Google Services Used](#overview-of-google-services-used)
2. [Google Cloud Project Setup](#google-cloud-project-setup)
3. [API Enablement](#api-enablement)
4. [Service Account & Authentication](#service-account--authentication)
5. [Google Cloud Storage Setup](#google-cloud-storage-setup)
6. [Service-Specific Integration](#service-specific-integration)
   - [Gemini API (Text Generation)](#1-gemini-api-text-generation)
   - [Speech-to-Text API](#2-speech-to-text-api)
   - [Text-to-Speech API](#3-text-to-speech-api)
   - [Veo Video Generation API](#4-veo-video-generation-api)
7. [Required PHP Libraries](#required-php-libraries)
8. [Environment Configuration](#environment-configuration)
9. [Testing Your Setup](#testing-your-setup)
10. [Pricing & Free Tier](#pricing--free-tier)
11. [Troubleshooting](#troubleshooting)

---

## 📊 Overview of Google Services Used

This application integrates with **4 Google Cloud Services**:

| Service | Purpose | Library | Status |
|---------|---------|---------|--------|
| **Gemini API** | Text summarization & content generation | `google/generativeai-php` | ✅ Active |
| **Speech-to-Text** | Audio transcription (backup only) | `google/cloud-speech` | ⚠️ Optional (Browser-based now) |
| **Text-to-Speech** | Audio generation from text | `google/cloud-text-to-speech` | ✅ Active |
| **Veo (Vertex AI)** | Video generation from text/images | `google/cloud-ai-platform` | ✅ Implemented (Production Ready) |
| **Cloud Storage** | Store generated videos & media | `google/cloud-storage` | ✅ Implemented (Production Ready) |

---

## 🚀 Google Cloud Project Setup

### Step 1: Create Google Cloud Account

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click **"Get started for free"** or **"Start Free Trial"**
3. Sign in with your Google account
4. Complete account verification:
   - Enter billing information (required for verification)
   - Verify your identity
   - Accept terms of service

**💰 Free Trial Benefits:**
- **$300 USD in free credits**
- Valid for **90 days** (3 months)
- No automatic charges after trial
- Card required only for verification

### Step 2: Create a New Project

1. In [Google Cloud Console](https://console.cloud.google.com/), click the project dropdown (top left)
2. Click **"New Project"**
3. Enter project details:
   ```
   Project Name: video-app-production
   Organization: (Leave as default or select your org)
   Location: (Leave as default)
   ```
4. Click **"Create"**
5. Wait for project creation (takes a few seconds)
6. **IMPORTANT:** Copy your **Project ID** (not the project name)
   - Example: `video-app-production-123456`
   - You'll need this for API calls and configuration

### Step 3: Enable Billing

1. Go to **"Billing"** in the left menu
2. Link your project to a billing account
3. Verify free trial credits are active
4. Set up billing alerts:
   - Go to **"Budgets & alerts"**
   - Create alert at 50%, 75%, 90% of budget

---

## 🔌 API Enablement

Enable all required APIs for your project:

### Required APIs

1. **Vertex AI API** (for Veo video generation)
   ```
   https://console.cloud.google.com/apis/library/aiplatform.googleapis.com
   ```

2. **Cloud Storage API** (for storing videos)
   ```
   https://console.cloud.google.com/apis/library/storage-api.googleapis.com
   ```

3. **Speech-to-Text API** (for audio transcription)
   ```
   https://console.cloud.google.com/apis/library/speech.googleapis.com
   ```

4. **Text-to-Speech API** (for audio generation)
   ```
   https://console.cloud.google.com/apis/library/texttospeech.googleapis.com
   ```

5. **Cloud AI Platform API** (for ML operations)
   ```
   https://console.cloud.google.com/apis/library/ml.googleapis.com
   ```

### How to Enable APIs

1. Go to **"APIs & Services" > "Library"**
2. Search for each API by name
3. Click on the API
4. Click **"Enable"**
5. Wait for enablement (takes a few seconds)

**Verification:**
- Go to **"APIs & Services" > "Dashboard"**
- You should see all enabled APIs listed

---

## 🔐 Service Account & Authentication

### Step 1: Create Service Account

1. Go to **"IAM & Admin" > "Service Accounts"**
2. Click **"Create Service Account"**
3. Enter service account details:
   ```
   Service Account Name: video-app-service
   Service Account ID: video-app-service (auto-generated)
   Description: Service account for video generation app
   ```
4. Click **"Create and Continue"**

### Step 2: Grant Roles

Add the following roles to your service account:

| Role | Purpose |
|------|---------|
| **Vertex AI User** | Access to Veo video generation API |
| **Storage Admin** | Full control over Cloud Storage buckets |
| **Storage Object Admin** | Upload/download files from buckets |
| **Cloud Speech Administrator** | Access Speech-to-Text API |
| **Text-to-Speech Admin** | Access Text-to-Speech API |

**To Add Roles:**
1. In the role selection screen, click **"Select a role"**
2. Search for each role and add it
3. Click **"Continue"** when all roles are added
4. Click **"Done"**

### Step 3: Create Service Account Key

1. Click on the service account you just created
2. Go to **"Keys"** tab
3. Click **"Add Key" > "Create new key"**
4. Select **"JSON"** format
5. Click **"Create"**
6. A JSON file will download automatically

**⚠️ IMPORTANT:**
- This JSON file contains sensitive credentials
- Never commit it to Git
- Store it securely
- You cannot re-download the same key

### Step 4: Install Service Account Key

1. Rename the downloaded file to `google-credentials.json`
2. Move it to: `storage/app/google-credentials.json` in your Laravel project
3. Verify the path matches your `.env` file:
   ```env
   GOOGLE_APPLICATION_CREDENTIALS=storage/app/google-credentials.json
   ```

### Step 5: Extract Project ID

Open the `google-credentials.json` file and find:
```json
{
  "project_id": "your-project-id-here",
  ...
}
```

Copy the `project_id` value for your `.env` file.

---

## 💾 Google Cloud Storage Setup

### Step 1: Create Storage Bucket

1. Go to **"Cloud Storage" > "Buckets"**
2. Click **"Create Bucket"**
3. Configure bucket:

   **Bucket Name:**
   ```
   video-app-generated-videos
   ```
   (Must be globally unique - add suffix if taken)

   **Location Type:**
   - **Multi-region**: For global access (higher cost)
     - `US`, `EU`, `ASIA`
   - **Single Region**: For regional access (lower cost)
     - `us-central1`, `us-east1`, `europe-west1`

   **Storage Class:**
   - **Standard** (for frequently accessed videos)

   **Access Control:**
   - Choose **"Uniform"** (simpler permissions)

   **Protection Tools:**
   - Uncheck "Enforce public access prevention" (if videos will be public)
   - Or keep checked and use signed URLs

4. Click **"Create"**

### Step 2: Configure Bucket Permissions (Public Access)

If you want videos to be publicly accessible:

1. Go to your bucket
2. Click **"Permissions"** tab
3. Click **"Grant Access"**
4. Add new principal:
   ```
   New principals: allUsers
   Role: Storage Object Viewer
   ```
5. Click **"Save"**

**⚠️ Security Note:** Only make videos public if they're meant for public viewing!

### Step 3: Configure CORS (Optional)

If you'll access videos from a web browser:

1. Click on your bucket
2. Go to **"Configuration"** tab
3. Click **"Edit CORS configuration"**
4. Add this CORS configuration:
   ```json
   [
     {
       "origin": ["*"],
       "method": ["GET", "HEAD"],
       "responseHeader": ["Content-Type"],
       "maxAgeSeconds": 3600
     }
   ]
   ```
5. Click **"Save"**

---

## 🔧 Service-Specific Integration

### 1. Gemini API (Text Generation)

#### What it does:
- Summarizes user answers
- Generates video prompts from text
- Content enhancement

#### Setup:

**Option A: Using Gemini API (Recommended for Development)**

1. Go to [Google AI Studio](https://aistudio.google.com/app/apikey)
2. Click **"Get API Key"**
3. Click **"Create API Key in New Project"** or select existing project
4. Copy the API key
5. Add to `.env`:
   ```env
   GEMINI_API_KEY=your-api-key-here
   GEMINI_MODEL=gemini-2.0-flash
   ```

**Option B: Using Vertex AI Gemini (Production)**

1. Enable Vertex AI API (already done above)
2. Use service account credentials
3. Add to `.env`:
   ```env
   GOOGLE_CLOUD_PROJECT_ID=your-project-id
   GOOGLE_CLOUD_LOCATION=us-central1
   GOOGLE_APPLICATION_CREDENTIALS=storage/app/google-credentials.json
   ```

#### Current Implementation:
```php
// File: app/Services/SummarizationService.php
// Uses: google/generativeai-php or Vertex AI SDK
```

#### Pricing:
- **Free Tier:** 15 requests per minute
- **Paid:** $0.00025 per 1K characters (input), $0.001 per 1K characters (output)

---

### 2. Speech-to-Text API

#### What it does:
- Converts audio to text (backup only)
- Browser-based speech recognition is primary method

#### Setup:

1. API already enabled (step 3)
2. Service account already has permissions
3. Add to `.env`:
   ```env
   STT_PROVIDER=google
   GOOGLE_APPLICATION_CREDENTIALS=storage/app/google-credentials.json
   ```

#### Current Implementation:
```php
// File: app/Services/SpeechToTextService.php
// Status: DEPRECATED - Using Web Speech API in browser instead
// Keep as backup fallback option
```

#### Pricing:
- **Free Tier:** 60 minutes per month
- **Paid:** $0.006 per 15 seconds

**💡 Note:** This app now uses browser-based Web Speech API (FREE), so this service is optional.

---

### 3. Text-to-Speech API

#### What it does:
- Generates audio from text
- Creates voice narration

#### Setup:

1. API already enabled (step 3)
2. Service account already has permissions
3. Add to `.env`:
   ```env
   TTS_PROVIDER=google
   TTS_VOICE=en-US-Neural2-C
   TTS_LANGUAGE_CODE=en-US
   ```

#### Available Voices:

| Voice | Type | Description |
|-------|------|-------------|
| `en-US-Neural2-C` | Neural | Female, natural |
| `en-US-Neural2-D` | Neural | Male, natural |
| `en-US-Standard-A` | Standard | Male |
| `en-US-Standard-B` | Standard | Male |
| `en-US-Wavenet-A` | Wavenet | Male, high quality |

[Full voice list](https://cloud.google.com/text-to-speech/docs/voices)

#### Current Implementation:
```php
// File: app/Services/TextToSpeechService.php
// Uses: google/cloud-text-to-speech
```

#### Pricing:
- **Free Tier:** 1 million characters per month (Standard voices)
- **Paid:** $4 per 1 million characters (Standard), $16 per 1 million (Neural)

---

### 4. Veo Video Generation API

#### What it does:
- Generates videos from text prompts
- Generates videos from images
- Video editing and enhancement

#### Official Documentation:
- [Veo Overview](https://docs.cloud.google.com/vertex-ai/generative-ai/docs/video/overview)
- [Veo API Reference](https://docs.cloud.google.com/vertex-ai/generative-ai/docs/model-reference/veo-video-generation)

#### Setup:

1. **Enable Vertex AI API** (already done in step 3)

2. **Choose a Model:**

   | Model | Features | Speed | Quality |
   |-------|----------|-------|---------|
   | `veo-3.1-generate-001` | Latest, best quality | Slower | Highest |
   | `veo-3.1-fast-generate-001` | Fast generation | Faster | High |
   | `veo-3.0-generate-001` | Stable version | Medium | High |
   | `veo-2.0-generate-001` | Older version | Medium | Good |

3. **Add to `.env`:**
   ```env
   VIDEO_MODE=production
   VIDEO_PROVIDER=google_veo
   GCS_BUCKET=video-app-generated-videos
   GCS_PROJECT_ID=your-project-id
   GOOGLE_CLOUD_PROJECT_ID=your-project-id
   GOOGLE_CLOUD_LOCATION=us-central1
   GOOGLE_APPLICATION_CREDENTIALS=storage/app/google-credentials.json
   ```

#### API Endpoint Structure:

**Text-to-Video Request:**
```
POST https://us-central1-aiplatform.googleapis.com/v1/projects/{PROJECT_ID}/locations/us-central1/publishers/google/models/{MODEL_ID}:predictLongRunning
```

**Headers:**
```
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json
```

**Request Body:**
```json
{
  "instances": [
    {
      "prompt": "A fast-tracking shot through a bustling dystopian sprawl with bright neon signs"
    }
  ],
  "parameters": {
    "storageUri": "gs://your-bucket/output/",
    "sampleCount": 1,
    "durationSeconds": 8,
    "aspectRatio": "16:9",
    "resolution": "1080p",
    "generateAudio": true
  }
}
```

**Response:**
```json
{
  "name": "projects/{PROJECT_ID}/locations/us-central1/publishers/google/models/{MODEL_ID}/operations/{OPERATION_ID}"
}
```

**Poll Operation Status:**
```
POST https://us-central1-aiplatform.googleapis.com/v1/projects/{PROJECT_ID}/locations/us-central1/publishers/google/models/{MODEL_ID}:fetchPredictOperation
```

**Status Check Body:**
```json
{
  "operationName": "projects/{PROJECT_ID}/locations/us-central1/publishers/google/models/{MODEL_ID}/operations/{OPERATION_ID}"
}
```

**Completed Response:**
```json
{
  "name": "projects/.../operations/{OPERATION_ID}",
  "done": true,
  "response": {
    "@type": "type.googleapis.com/cloud.ai.large_models.vision.GenerateVideoResponse",
    "videos": [
      {
        "gcsUri": "gs://your-bucket/output/timestamped-dir/sample_0.mp4",
        "mimeType": "video/mp4"
      }
    ]
  }
}
```

#### Current Implementation Status:

**✅ FULLY IMPLEMENTED** (as of November 19, 2025)

The application now includes complete Veo API integration with:
- ✅ OAuth2 service account authentication
- ✅ Real Veo API calls via `predictLongRunning` endpoint
- ✅ Operation polling with `fetchPredictOperation`
- ✅ Automatic video upload to Cloud Storage
- ✅ GCS URI to public URL conversion
- ✅ Fallback to test videos on errors
- ✅ Comprehensive error handling and logging

**Implementation Files:**
- `app/Services/TextToVideoService.php` - Main video generation service
- `app/Services/CloudStorageService.php` - Cloud Storage integration
- `config/services.php` - Configuration for Veo and GCS

**To Use Real Veo API:**
1. Set `VIDEO_MODE=production` in `.env`
2. Configure `GCS_BUCKET` and `GCS_PROJECT_ID`
3. Ensure Google Cloud credentials are in place
4. Enable Vertex AI API in Google Cloud Console
5. Ensure billing is enabled with sufficient credits

#### Pricing:
- **⚠️ Veo is in Preview** - Pricing TBD
- Estimated: Per-second of generated video
- Use $300 free credits during trial
- Set billing alerts to monitor usage

---

## 📦 Required PHP Libraries

Install these Composer packages:

```bash
# Core Google Cloud Libraries
composer require google/cloud-core

# Individual Service Libraries
composer require google/cloud-storage          # Cloud Storage
composer require google/cloud-speech           # Speech-to-Text
composer require google/cloud-text-to-speech   # Text-to-Speech
composer require google/cloud-aiplatform       # Vertex AI (Veo)

# Gemini API (choose one)
composer require google/generativeai-php       # Direct Gemini API
# OR
composer require google/cloud-ai               # Vertex AI Gemini
```

**Current Installation Status:**

Check your `composer.json`:
```json
{
  "require": {
    "google/cloud-storage": "^1.43",
    "google/cloud-speech": "^1.19",
    "google/cloud-text-to-speech": "^1.9",
    "google/cloud-aiplatform": "^0.38",
    "google/generativeai-php": "^0.3"
  }
}
```

If missing, run:
```bash
composer install
```

---

## ⚙️ Environment Configuration

### Complete `.env` Configuration:

```env
# ===================================
# GOOGLE CLOUD CONFIGURATION
# ===================================

# --- Project Setup ---
GOOGLE_CLOUD_PROJECT_ID=your-project-id-here
GOOGLE_CLOUD_LOCATION=us-central1
GOOGLE_APPLICATION_CREDENTIALS=storage/app/google-credentials.json

# --- Gemini API (Text Generation & Summarization) ---
GEMINI_API_KEY=your-gemini-api-key-here
GEMINI_MODEL=gemini-2.0-flash

# --- Speech-to-Text (Optional - Browser-based is primary) ---
STT_PROVIDER=google
# No API key needed - uses service account

# --- Text-to-Speech ---
TTS_PROVIDER=google
TTS_VOICE=en-US-Neural2-C
TTS_LANGUAGE_CODE=en-US

# --- Video Generation (Veo) ---
VIDEO_MODE=test                    # 'test' or 'production'
VIDEO_PROVIDER=google_veo

# For production mode only:
GCS_BUCKET=video-app-generated-videos
GCS_PROJECT_ID=your-project-id-here

# ===================================
# VIDEO MODE EXPLANATION
# ===================================
# VIDEO_MODE=test       → FREE, uses sample videos
# VIDEO_MODE=production → PAID, generates real videos with Veo API
```

### Environment Variables Explained:

| Variable | Required | Purpose | Example |
|----------|----------|---------|---------|
| `GOOGLE_CLOUD_PROJECT_ID` | ✅ Yes | Your GCP project ID | `video-app-123456` |
| `GOOGLE_CLOUD_LOCATION` | ✅ Yes | Region for API calls | `us-central1` |
| `GOOGLE_APPLICATION_CREDENTIALS` | ✅ Yes | Path to service account JSON | `storage/app/google-credentials.json` |
| `GEMINI_API_KEY` | ✅ Yes | Gemini API key for text generation | `AIzaSyD...` |
| `GEMINI_MODEL` | ⚠️ Optional | Model version | `gemini-2.0-flash` |
| `STT_PROVIDER` | ⚠️ Optional | Speech-to-text provider | `google` |
| `TTS_PROVIDER` | ✅ Yes | Text-to-speech provider | `google` |
| `TTS_VOICE` | ⚠️ Optional | Voice ID | `en-US-Neural2-C` |
| `VIDEO_MODE` | ✅ Yes | Test or production | `test` or `production` |
| `VIDEO_PROVIDER` | ✅ Yes | Video API provider | `google_veo` |
| `GCS_BUCKET` | ⚠️ Production | Cloud Storage bucket name | `video-app-videos` |
| `GCS_PROJECT_ID` | ⚠️ Production | GCS project ID | `video-app-123456` |

---

## 🧪 Testing Your Setup

### Test 1: Verify Service Account

```bash
php artisan tinker
```

```php
// Test authentication
$keyPath = storage_path('app/google-credentials.json');
dd(file_exists($keyPath));
// Should return: true

// Test credentials content
$credentials = json_decode(file_get_contents($keyPath), true);
dd($credentials['project_id']);
// Should return: your-project-id
```

### Test 2: Test Gemini API

```bash
php artisan tinker
```

```php
$service = app(\App\Services\SummarizationService::class);
$result = $service->summarize("This is a test of the Gemini API");
dd($result);
// Should return summarized text
```

### Test 3: Test Text-to-Speech

```bash
php artisan tinker
```

```php
$service = app(\App\Services\TextToSpeechService::class);
$audioPath = $service->synthesize("Hello, this is a test.");
dd($audioPath);
// Should return: path to generated audio file
```

### Test 4: Test Cloud Storage Access

```bash
php artisan tinker
```

```php
use Google\Cloud\Storage\StorageClient;

$storage = new StorageClient([
    'projectId' => config('services.google.project_id'),
    'keyFilePath' => config('services.google.credentials')
]);

$buckets = $storage->buckets();
dd(iterator_to_array($buckets));
// Should return: list of your buckets
```

### Test 5: Test Video Generation (Simulated)

```bash
php artisan tinker
```

```php
$service = app(\App\Services\TextToVideoService::class);
$result = $service->generate("A beautiful sunset over mountains");
dd($result);
// Should return: task_id and status
```

---

## 💰 Pricing & Free Tier

### Google Cloud Free Trial

**Initial Credits:**
- **$300 USD** in free credits
- Valid for **90 days**
- Applies to ALL Google Cloud services

### Service-Specific Pricing

#### 1. Gemini API (Text Generation)
- **Free Tier:** 15 requests per minute
- **Paid:** 
  - Input: $0.00025 per 1K characters
  - Output: $0.001 per 1K characters

#### 2. Speech-to-Text
- **Free Tier:** 60 minutes per month
- **Paid:** $0.006 per 15 seconds

**💡 App Uses Browser-Based Speech Recognition (FREE)**

#### 3. Text-to-Speech
- **Free Tier:** 1 million characters per month (Standard voices)
- **Paid:**
  - Standard: $4 per 1M characters
  - Wavenet: $16 per 1M characters
  - Neural2: $16 per 1M characters

#### 4. Veo Video Generation
- **⚠️ Pricing TBD** (Currently in Preview)
- Likely based on:
  - Video duration
  - Resolution
  - Number of samples generated
- Monitor with $300 free credits

#### 5. Cloud Storage
- **Free Tier:** 5 GB per month
- **Paid:**
  - Storage: $0.020 per GB/month (Standard class)
  - Network egress: First 1 GB free, then $0.12/GB
  - Operations: $0.05 per 10,000 operations

### Monthly Cost Estimate (100 Users)

| Service | Usage | Cost |
|---------|-------|------|
| Gemini | 1000 summaries | ~$2 |
| TTS | 500 audio files | ~$1 |
| Veo | 100 videos (8s each) | ~$50-100 (estimated) |
| Storage | 10 GB | ~$0.20 |
| **Total** | | **~$53-103/month** |

**With $300 credits:** 3-6 months free usage

---

## 🐛 Troubleshooting

### Issue: "Service Account Not Found"

**Solution:**
1. Verify file path in `.env`:
   ```env
   GOOGLE_APPLICATION_CREDENTIALS=storage/app/google-credentials.json
   ```
2. Check file exists:
   ```bash
   ls -la storage/app/google-credentials.json
   ```
3. Verify file permissions:
   ```bash
   chmod 644 storage/app/google-credentials.json
   ```

### Issue: "API Not Enabled"

**Solution:**
1. Go to [API Library](https://console.cloud.google.com/apis/library)
2. Search for the API
3. Click "Enable"
4. Wait 2-3 minutes for propagation

### Issue: "Permission Denied"

**Solution:**
1. Go to [IAM & Admin](https://console.cloud.google.com/iam-admin/iam)
2. Find your service account
3. Click "Edit"
4. Add missing role (Vertex AI User, Storage Admin, etc.)
5. Save changes

### Issue: "Project ID Mismatch"

**Solution:**
1. Open `google-credentials.json`
2. Copy the `project_id` value
3. Update `.env`:
   ```env
   GOOGLE_CLOUD_PROJECT_ID=exact-project-id-from-json
   ```

### Issue: "Invalid Authentication Credentials"

**Solution:**
1. Re-download service account key
2. Replace old `google-credentials.json`
3. Clear Laravel cache:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

### Issue: "Veo API Returns 404"

**Solution:**
- Veo is in Preview and requires approval
- Request access through your Google Cloud account manager
- Or use test mode for now:
  ```env
  VIDEO_MODE=test
  ```

---

## 📚 Additional Resources

### Official Documentation

- [Google Cloud Console](https://console.cloud.google.com/)
- [Vertex AI Documentation](https://cloud.google.com/vertex-ai/docs)
- [Veo API Reference](https://docs.cloud.google.com/vertex-ai/generative-ai/docs/model-reference/veo-video-generation)
- [Cloud Storage Documentation](https://cloud.google.com/storage/docs)
- [Speech-to-Text Documentation](https://cloud.google.com/speech-to-text/docs)
- [Text-to-Speech Documentation](https://cloud.google.com/text-to-speech/docs)
- [Gemini API Documentation](https://ai.google.dev/docs)

### Code Examples

- [Veo Colab Notebook](https://colab.research.google.com/github/GoogleCloudPlatform/generative-ai/blob/main/vision/getting-started/veo3_video_generation.ipynb)
- [Vertex AI Samples](https://github.com/GoogleCloudPlatform/generative-ai)

### Support

- [Google Cloud Support](https://cloud.google.com/support)
- [Community Forums](https://discuss.google.dev/c/google-cloud/14/)
- [Stack Overflow](https://stackoverflow.com/questions/tagged/google-cloud-platform)

---

## ✅ Setup Checklist

Use this checklist to verify your setup:

- [ ] Google Cloud account created
- [ ] Project created and Project ID copied
- [ ] Billing enabled (free trial activated)
- [ ] Vertex AI API enabled
- [ ] Cloud Storage API enabled
- [ ] Speech-to-Text API enabled
- [ ] Text-to-Speech API enabled
- [ ] Service account created
- [ ] Roles assigned to service account:
  - [ ] Vertex AI User
  - [ ] Storage Admin
  - [ ] Storage Object Admin
  - [ ] Cloud Speech Administrator
  - [ ] Text-to-Speech Admin
- [ ] Service account key downloaded (JSON)
- [ ] Key file placed in `storage/app/google-credentials.json`
- [ ] Cloud Storage bucket created
- [ ] Bucket permissions configured
- [ ] Gemini API key obtained
- [ ] `.env` file configured with all variables
- [ ] Composer packages installed
- [ ] Laravel cache cleared
- [ ] Tests run successfully

---

## 🎯 Quick Reference

### API Endpoints

```bash
# Vertex AI (Veo) - Text-to-Video
POST https://us-central1-aiplatform.googleapis.com/v1/projects/{PROJECT_ID}/locations/us-central1/publishers/google/models/veo-3.1-generate-001:predictLongRunning

# Check Operation Status
POST https://us-central1-aiplatform.googleapis.com/v1/projects/{PROJECT_ID}/locations/us-central1/publishers/google/models/veo-3.1-generate-001:fetchPredictOperation

# Gemini API
POST https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key={API_KEY}
```

### Common Commands

```bash
# Install Google Cloud SDK
composer require google/cloud-storage google/cloud-speech google/cloud-text-to-speech google/cloud-aiplatform

# Clear cache
php artisan config:clear
php artisan cache:clear

# Test setup
php artisan tinker
```

---

**Last Updated:** November 19, 2025

**Questions?** Check the troubleshooting section or refer to official Google Cloud documentation.
