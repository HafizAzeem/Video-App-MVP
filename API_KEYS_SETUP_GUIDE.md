# API Keys Setup Guide for LAS MVP

This guide will help you obtain all the necessary API keys to run the Learn And Share (LAS) application.

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
