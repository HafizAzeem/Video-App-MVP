# API Keys Setup Guide for LAS (Learn And Share)

This guide will help you obtain all necessary API keys for the LAS application's AI features.

## Overview

LAS uses Google AI services for all AI functionality:
- **Google Gemini API** - AI content generation and video prompts
- **Google Cloud Speech-to-Text** - Voice transcription
- **Google Cloud Text-to-Speech** - Audio narration
- **Google Veo Model** - AI video generation

---

## 1. Google Gemini API Key

**Purpose:** Powers AI summarization and video prompt generation using Gemini Pro model.

### Steps:

1. **Visit Google AI Studio**
   - Go to [https://makersuite.google.com/app/apikey](https://makersuite.google.com/app/apikey)
   - Sign in with your Google account

2. **Create API Key**
   - Click "Create API Key"
   - Select an existing Google Cloud project or create a new one
   - Copy the generated API key

3. **Add to .env**
   ```env
   GEMINI_API_KEY=your_gemini_api_key_here
   GEMINI_MODEL=gemini-2.0-flash-exp
   ```

### Pricing:
- **Free Tier:** 60 requests per minute
- **Paid Tier:** 
  - Gemini 1.5 Flash: $0.075 per 1M input tokens, $0.30 per 1M output tokens
  - Gemini 1.5 Pro: $1.25 per 1M input tokens, $5.00 per 1M output tokens

### Documentation:
- [Google AI Studio](https://ai.google.dev/)
- [Gemini API Quickstart](https://ai.google.dev/tutorials/quickstart)

---

## 2. Google Cloud Speech-to-Text

**Purpose:** Converts user voice recordings to text.

### Steps:

1. **Create Google Cloud Project**
   - Go to [Google Cloud Console](https://console.cloud.google.com/)
   - Create a new project or select existing one
   - Note your Project ID

2. **Enable Speech-to-Text API**
   - Navigate to "APIs & Services" > "Library"
   - Search for "Cloud Speech-to-Text API"
   - Click "Enable"

3. **Create Service Account**
   - Go to "APIs & Services" > "Credentials"
   - Click "Create Credentials" > "Service Account"
   - Fill in service account details
   - Grant role: "Cloud Speech Client"
   - Click "Done"

4. **Generate JSON Key**
   - Click on the created service account
   - Go to "Keys" tab
   - Click "Add Key" > "Create new key"
   - Choose JSON format
   - Save the downloaded JSON file to your project (e.g., `storage/app/google-credentials.json`)

5. **Add to .env**
   ```env
   GOOGLE_APPLICATION_CREDENTIALS=storage/app/google-credentials.json
   GOOGLE_CLOUD_PROJECT_ID=your-project-id
   STT_PROVIDER=google
   ```

### Pricing:
- **Free Tier:** First 60 minutes per month
- **Standard Models:** $0.006 per 15 seconds ($0.024 per minute)
- **Enhanced Models:** $0.009 per 15 seconds ($0.036 per minute)

### Documentation:
- [Speech-to-Text Quickstart](https://cloud.google.com/speech-to-text/docs/quickstart-client-libraries)
- [Pricing Details](https://cloud.google.com/speech-to-text/pricing)

---

## 3. Google Cloud Text-to-Speech

**Purpose:** Generates audio narration from summarized text.

### Steps:

1. **Enable Text-to-Speech API**
   - In the same Google Cloud project
   - Navigate to "APIs & Services" > "Library"
   - Search for "Cloud Text-to-Speech API"
   - Click "Enable"

2. **Update Service Account Permissions** (if using same service account)
   - Go to "IAM & Admin" > "IAM"
   - Find your service account
   - Click "Edit"
   - Add role: "Cloud Text-to-Speech Client"
   - Save

3. **Add to .env**
   ```env
   TTS_PROVIDER=google
   TTS_VOICE=en-US-Neural2-C
   TTS_LANGUAGE_CODE=en-US
   ```

### Available Voices:
- `en-US-Neural2-C` - Female, natural sounding
- `en-US-Neural2-D` - Male, natural sounding
- `en-US-Neural2-A` - Male, professional
- [Full voice list](https://cloud.google.com/text-to-speech/docs/voices)

### Pricing:
- **Free Tier:** First 1 million characters per month (Standard)
- **Standard Voices:** $4.00 per 1 million characters
- **Neural2 Voices:** $16.00 per 1 million characters
- **Studio Voices:** $160.00 per 1 million characters

### Documentation:
- [Text-to-Speech Quickstart](https://cloud.google.com/text-to-speech/docs/quickstart-client-libraries)
- [Voice Selection Guide](https://cloud.google.com/text-to-speech/docs/voices)

---

## 4. Google Veo Model (Video Generation)

**Purpose:** Generates AI videos from text prompts.

### Steps:

1. **Access Veo Through Vertex AI**
   - Veo is part of Google's Vertex AI platform
   - In Google Cloud Console, navigate to "Vertex AI"
   - Enable "Vertex AI API" if not already enabled

2. **Request Access to Veo**
   - Veo is currently in limited preview
   - Visit [https://cloud.google.com/vertex-ai/generative-ai/docs/video/overview](https://cloud.google.com/vertex-ai/generative-ai/docs/video/overview)
   - Fill out the access request form
   - Wait for approval (typically 1-2 weeks)

3. **Update Service Account Permissions**
   - Add role: "Vertex AI User"
   - This uses the same service account as Speech/TTS

4. **Add to .env**
   ```env
   VIDEO_PROVIDER=google_veo
   ```

### Current Status:
- **Veo 2** is Google's latest video generation model
- Supports up to 4K resolution
- Can generate videos from text prompts
- Currently in limited preview/beta

### Pricing:
- Pricing not yet publicly available (preview phase)
- Expected to be usage-based like other Vertex AI models
- Contact Google Cloud sales for pricing details

### Documentation:
- [Veo Overview](https://deepmind.google/technologies/veo/)
- [Vertex AI Video Generation](https://cloud.google.com/vertex-ai/generative-ai/docs/video/overview)

---

## Complete .env Configuration

After obtaining all keys, your `.env` should look like this:

```env
# Google Gemini API
GEMINI_API_KEY=AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXXX
GEMINI_MODEL=gemini-2.0-flash-exp

# Google Cloud Credentials
GOOGLE_APPLICATION_CREDENTIALS=storage/app/google-credentials.json
GOOGLE_CLOUD_PROJECT_ID=your-project-id

# Speech-to-Text
STT_PROVIDER=google

# Text-to-Speech
TTS_PROVIDER=google
TTS_VOICE=en-US-Neural2-C
TTS_LANGUAGE_CODE=en-US

# Text-to-Video
VIDEO_PROVIDER=google_veo
```

---

## Security Best Practices

1. **Never Commit API Keys**
   - Add `.env` to `.gitignore` (already configured)
   - Use `.env.example` as a template

2. **Restrict API Key Usage**
   - In Google AI Studio, restrict Gemini API key to your domain/IP
   - In Google Cloud, use IAM roles with least privilege

3. **Service Account JSON**
   - Store the JSON file outside the public directory
   - Set appropriate file permissions: `chmod 600 google-credentials.json`
   - Consider using environment variables in production

4. **Monitor Usage**
   - Set up billing alerts in Google Cloud Console
   - Monitor API quotas to avoid unexpected charges

5. **Rotate Keys Regularly**
   - Change API keys every 90 days
   - Generate new service account keys periodically

---

## Testing Your Setup

After configuration, test each service:

```bash
# Install dependencies
composer update

# Test Speech-to-Text
php artisan tinker
>>> $stt = app(\App\Services\SpeechToTextService::class);
>>> # Upload a test audio file and check transcription

# Test Text-to-Speech
>>> $tts = app(\App\Services\TextToSpeechService::class);
>>> $tts->generate('Hello from Google TTS!');

# Test Gemini
>>> $gpt = app(\App\Services\GPTService::class);
>>> $gpt->generateContent('Explain Laravel in one sentence');

# Test Video Generation
>>> $video = app(\App\Services\TextToVideoService::class);
>>> $result = $video->generate('A beautiful sunset over mountains');
>>> var_dump($result);
```

---

## Troubleshooting

### "Unable to authenticate" errors
- Verify `GOOGLE_APPLICATION_CREDENTIALS` path is correct
- Ensure JSON key file has proper permissions
- Check service account has required roles

### "API not enabled" errors
- Enable the specific API in Google Cloud Console
- Wait a few minutes for propagation

### "Quota exceeded" errors
- Check your usage in Google Cloud Console
- Upgrade to paid tier if needed
- Request quota increase for your project

### Gemini API errors
- Verify API key is correct and active
- Check rate limits (60 requests/min on free tier)
- Ensure you're using supported model name

---

## Cost Estimation

For a typical LAS session (1 user answering 4 questions):

| Service | Usage | Cost |
|---------|-------|------|
| Speech-to-Text | 4 audio files × 30 sec avg | $0.0048 |
| Gemini API | 1 summary + 1 prompt (~1K tokens) | ~$0.0001 |
| Text-to-Speech | 1 summary narration (~200 chars) | $0.0032 |
| Veo Video | 1 video generation (5 sec) | TBD |
| **Total per session** | | **~$0.01 - $0.05** |

**Monthly estimate for 100 users:**
- 400 sessions per month
- Estimated cost: **$4 - $20/month** (excluding Veo)

---

## Support & Resources

- **Google AI Studio:** [https://ai.google.dev/](https://ai.google.dev/)
- **Google Cloud Console:** [https://console.cloud.google.com/](https://console.cloud.google.com/)
- **Gemini API Docs:** [https://ai.google.dev/docs](https://ai.google.dev/docs)
- **Cloud Speech Docs:** [https://cloud.google.com/speech-to-text/docs](https://cloud.google.com/speech-to-text/docs)
- **Cloud TTS Docs:** [https://cloud.google.com/text-to-speech/docs](https://cloud.google.com/text-to-speech/docs)
- **Veo Docs:** [https://cloud.google.com/vertex-ai/generative-ai/docs/video](https://cloud.google.com/vertex-ai/generative-ai/docs/video)

For issues specific to this application, check `LAS_IMPLEMENTATION_GUIDE.md`.


---

## 🔑 Required API Keys

### 1. **OpenAI API Key** (Required for STT, TTS, and GPT)

OpenAI provides Speech-to-Text (Whisper), Text-to-Speech, and GPT-4 summarization.

**Steps to get API key:**

1. Go to [https://platform.openai.com/signup](https://platform.openai.com/signup)
2. Create an account or sign in
3. Navigate to [API Keys](https://platform.openai.com/api-keys)
4. Click **"Create new secret key"**
5. Give it a name (e.g., "LAS MVP")
6. Copy the key (starts with `sk-...`)
7. **Important:** Save it immediately - you won't see it again!

**Add to `.env`:**
```env
OPENAI_API_KEY=sk-your-actual-key-here
```

**Pricing (Pay-as-you-go):**
- Whisper (STT): $0.006 per minute
- TTS: $15 per 1M characters
- GPT-4 Turbo: $10 per 1M input tokens, $30 per 1M output tokens

**Free Credits:**
- New users get $5 free credits (expires after 3 months)

---

## 🎤 Optional: ElevenLabs (Alternative for TTS)

ElevenLabs provides high-quality, natural-sounding voice synthesis.

**Steps to get API key:**

1. Go to [https://elevenlabs.io](https://elevenlabs.io)
2. Sign up for a free account
3. Navigate to [Profile Settings](https://elevenlabs.io/settings/api)
4. Copy your API key

**Get Voice ID:**
1. Go to [Voice Lab](https://elevenlabs.io/voice-lab)
2. Choose a voice or create custom voice
3. Click on the voice → Copy the Voice ID

**Add to `.env`:**
```env
TTS_PROVIDER=elevenlabs
ELEVENLABS_API_KEY=your-elevenlabs-key
ELEVENLABS_VOICE_ID=your-voice-id
```

**Pricing:**
- Free: 10,000 characters/month
- Starter: $5/month - 30,000 characters
- Creator: $22/month - 100,000 characters

---

## 🎬 Text-to-Video API Keys

### Option A: **Runway ML** (Recommended)

Runway offers Gen-3 Alpha model for high-quality text-to-video generation.

**Steps to get API key:**

1. Go to [https://runwayml.com](https://runwayml.com)
2. Sign up and verify email
3. Navigate to [API Settings](https://app.runwayml.com/settings/api)
4. Create a new API key
5. Copy the key

**Add to `.env`:**
```env
TEXT_TO_VIDEO_PROVIDER=runway
TEXT_TO_VIDEO_API_KEY=your-runway-api-key
```

**Pricing:**
- Credits-based system
- Gen-3 Alpha Turbo: ~$0.05 per second of video
- 5 second video ≈ $0.25

**Note:** Runway requires a paid plan for API access (starts at $12/month for 625 credits).

---

### Option B: **Pika Labs**

Alternative text-to-video provider.

**Steps to get API key:**

1. Go to [https://pika.art](https://pika.art)
2. Join waitlist for API access
3. Once approved, get API key from dashboard

**Add to `.env`:**
```env
TEXT_TO_VIDEO_PROVIDER=pika
TEXT_TO_VIDEO_API_KEY=your-pika-api-key
```

**Note:** Pika's API is in limited beta. You may need to join a waitlist.

---

### Option C: **Synthesia** (Enterprise)

Professional text-to-video with AI avatars.

**Steps:**
1. Go to [https://www.synthesia.io/api](https://www.synthesia.io/api)
2. Contact sales for API access
3. Enterprise pricing required

---

## 🚀 Quick Start Configuration

### Minimum Setup (OpenAI Only)

For testing/development, you only need OpenAI:

```env
# Required
OPENAI_API_KEY=sk-your-key-here

# These use OpenAI by default
STT_PROVIDER=openai
TTS_PROVIDER=openai
GPT_MODEL=gpt-4-turbo

# Video generation (optional for testing)
TEXT_TO_VIDEO_PROVIDER=runway
TEXT_TO_VIDEO_API_KEY=
```

### Full Production Setup

```env
# OpenAI (Required)
OPENAI_API_KEY=sk-your-openai-key

# ElevenLabs (Optional, better TTS quality)
ELEVENLABS_API_KEY=your-elevenlabs-key
ELEVENLABS_VOICE_ID=your-voice-id

# Runway ML (Required for video generation)
TEXT_TO_VIDEO_PROVIDER=runway
TEXT_TO_VIDEO_API_KEY=your-runway-key

# Service Configuration
STT_PROVIDER=openai
TTS_PROVIDER=elevenlabs
GPT_MODEL=gpt-4-turbo
```

---

## 💰 Estimated Costs Per User Journey

**Per user completing all 4 questions:**

| Service | Cost |
|---------|------|
| STT (4 x 1 min audio) | ~$0.024 |
| GPT-4 Summarization | ~$0.01 |
| TTS (200 words) | ~$0.0015 |
| Video Generation (5 sec) | ~$0.25 |
| **Total per user** | **~$0.29** |

**For 100 users:** ~$29
**For 1,000 users:** ~$290

---

## 🔒 Security Best Practices

1. **Never commit `.env` to git** (already in `.gitignore`)
2. **Use environment variables** - never hardcode keys
3. **Rotate keys regularly** (every 90 days)
4. **Set spending limits** in API provider dashboards
5. **Monitor usage** to prevent unexpected charges
6. **Use separate keys** for dev/staging/production

---

## 🧪 Testing Without Real API Keys

For development/testing without spending money:

1. **Mock the services** - Create fake responses
2. **Use free tier limits** - OpenAI gives $5 free credits
3. **Comment out video generation** - Test other features first
4. **Use recorded sample responses** - For faster development

---

## ⚙️ Verifying Your Setup

After adding keys to `.env`, run:

```bash
# Clear config cache
php artisan config:clear

# Test that keys are loaded
php artisan tinker
>>> config('services.openai.api_key')
# Should return your API key

>>> config('services.text_to_video.provider')
# Should return 'runway' or your chosen provider
```

---

## 🆘 Troubleshooting

### "API key not found" error
- Run `php artisan config:clear`
- Check `.env` file has correct format (no spaces around `=`)
- Restart your local server

### "Invalid API key" error
- Verify key is correct (copy-paste carefully)
- Check key hasn't expired
- Verify billing is set up in provider dashboard

### "Rate limit exceeded"
- You've hit free tier limits
- Add payment method to provider
- Wait for rate limit reset (usually 1 minute)

---

## 📚 Additional Resources

- [OpenAI API Documentation](https://platform.openai.com/docs)
- [OpenAI Pricing](https://openai.com/pricing)
- [ElevenLabs API Docs](https://docs.elevenlabs.io/api-reference)
- [Runway ML API Docs](https://docs.runwayml.com)
- [Laravel Config Documentation](https://laravel.com/docs/configuration)

---

## 🎯 Recommended Setup for Production

1. **Start with OpenAI only** - Test core functionality
2. **Add ElevenLabs** - If you need better voice quality
3. **Add Runway** - When ready to generate videos
4. **Set up monitoring** - Track costs and usage
5. **Implement caching** - Reduce API calls for same inputs

---

## 💡 Cost Optimization Tips

1. **Cache TTS audio files** - Don't regenerate same text
2. **Compress audio before STT** - Reduce file size
3. **Use GPT-3.5-turbo** for testing (cheaper than GPT-4)
4. **Batch API calls** when possible
5. **Set video duration limits** (5-10 seconds max)
6. **Implement usage quotas** per user

---

Need help? Check the [OpenAI Community](https://community.openai.com) or [Laravel Discord](https://discord.gg/laravel) for support!
