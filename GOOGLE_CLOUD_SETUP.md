# Google Cloud Setup Guide for Video Generation

This guide explains how to set up Google Cloud services for production video generation with Google Veo API.

---

## 🎯 Current Setup: Test Mode vs Production Mode

### Test Mode (Current - Default)
- **Environment Variable**: `VIDEO_MODE=test`
- **Cost**: **FREE** ✅
- **What it does**: Uses sample videos for testing (no API calls)
- **Use for**: Development, testing, demonstrations
- **No Google Cloud setup required**

### Production Mode (Real Video Generation)
- **Environment Variable**: `VIDEO_MODE=production`
- **Cost**: Requires billing (see pricing below)
- **What it does**: Generates real videos using Google Veo API
- **Use for**: Production deployment with real users
- **Requires**: Full Google Cloud setup (instructions below)

---

## 💰 Google Cloud Free Trial Information

### Yes! Google Cloud Offers a Generous Free Trial:

1. **$300 Free Credits**
   - Valid for **90 days** (3 months)
   - Can be used for ANY Google Cloud service
   - Includes Vertex AI, Cloud Storage, and more

2. **Always Free Tier** (After trial)
   - Cloud Storage: 5 GB per month
   - Some services remain free within usage limits
   - Video generation is NOT in always-free tier

3. **No Automatic Charges**
   - You must manually upgrade to paid account
   - Your card is required for verification only
   - You'll get notifications before credits run out

### Important Notes:
- ⚠️ **Google Veo API is currently in Limited Preview**
- You may need to request access through Google Cloud Console
- Pricing for Veo is not yet publicly announced
- The $300 credits will cover extensive testing

---

## 🚀 Step-by-Step Setup for Production Mode

### Step 1: Create Google Cloud Account

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click **"Get started for free"**
3. Sign in with your Google account
4. Enter billing information (for $300 free trial)
5. Verify your account

### Step 2: Create a New Project

1. In Google Cloud Console, click the project dropdown (top navigation)
2. Click **"New Project"**
3. Enter project details:
   - **Project Name**: `imagin-video-app` (or your choice)
   - **Organization**: Leave as default
4. Click **"Create"**
5. Wait for project creation (takes a few seconds)
6. **Copy the Project ID** - you'll need this for `.env` file

### Step 3: Enable Required APIs

1. In the Google Cloud Console, go to **"APIs & Services" > "Library"**
2. Search and enable these APIs (click "Enable" for each):
   - ✅ **Vertex AI API** (for Google Veo video generation)
   - ✅ **Cloud Storage API** (for storing generated videos)
   - ✅ **Speech-to-Text API** (already using this)
   - ✅ **Text-to-Speech API** (already using this)

### Step 4: Create a Service Account

1. Go to **"IAM & Admin" > "Service Accounts"**
2. Click **"Create Service Account"**
3. Enter details:
   - **Service Account Name**: `imagin-video-service`
   - **Description**: `Service account for video generation`
4. Click **"Create and Continue"**
5. Grant these roles:
   - **Vertex AI User** (for Veo API access)
   - **Storage Admin** (for Cloud Storage)
   - **Storage Object Admin** (for uploading videos)
6. Click **"Continue"** then **"Done"**

### Step 5: Create and Download Service Account Key

1. Click on the service account you just created
2. Go to **"Keys"** tab
3. Click **"Add Key" > "Create new key"**
4. Select **JSON** format
5. Click **"Create"**
6. A JSON file will download automatically
7. **Keep this file secure!** It contains your credentials

### Step 6: Set Up the Credentials File

1. Rename the downloaded file to `google-credentials.json`
2. Move it to: `storage/app/google-credentials.json` in your Laravel project
3. Make sure the path matches your `.env` file:
   ```
   GOOGLE_APPLICATION_CREDENTIALS=storage/app/google-credentials.json
   ```

### Step 7: Create Google Cloud Storage Bucket

1. In Google Cloud Console, go to **"Cloud Storage" > "Buckets"**
2. Click **"Create Bucket"**
3. Configure bucket:
   - **Bucket Name**: `imagin-videos-prod` (must be globally unique)
   - **Location Type**: Choose region closest to your users
     - **Multi-region**: `us`, `eu`, `asia` (higher cost, better global access)
     - **Single Region**: `us-central1`, `us-east1` (lower cost)
   - **Storage Class**: **Standard** (for frequently accessed videos)
   - **Access Control**: 
     - Choose **"Uniform"** (simpler permissions)
   - **Protection Tools**:
     - Uncheck "Enforce public access prevention" if videos will be public
     - Or keep checked if using signed URLs
4. Click **"Create"**

### Step 8: Make Bucket Publicly Accessible (if needed)

If you want videos to be publicly accessible:

1. Go to your bucket
2. Click **"Permissions"** tab
3. Click **"Grant Access"**
4. Add principal: `allUsers`
5. Select role: **Storage Object Viewer**
6. Click **"Save"**

⚠️ **Security Note**: Only do this for videos meant to be public!

### Step 9: Request Access to Google Veo API

Google Veo is currently in Limited Preview:

1. Go to [Google AI Studio](https://ai.google.dev/) or Vertex AI Console
2. Look for **"Veo"** in available models
3. If not available, click **"Request Access"**
4. Fill out the access request form
5. Wait for approval (can take a few days)

### Step 10: Update Your `.env` File

Update these variables in your `.env` file:

```env
# Switch to production mode
VIDEO_MODE=production

# Google Cloud Storage Configuration
GCS_BUCKET=imagin-videos-prod
GCS_PROJECT_ID=your-project-id-here

# Google Cloud Project (should already be set)
GOOGLE_CLOUD_PROJECT_ID=your-project-id-here
GOOGLE_CLOUD_LOCATION=us-central1
GOOGLE_APPLICATION_CREDENTIALS=storage/app/google-credentials.json
```

### Step 11: Test the Setup

1. Clear Laravel cache:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

2. Restart queue workers:
   ```bash
   php artisan queue:restart
   ```

3. Try generating a video through your application
4. Monitor the Laravel logs for any errors:
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## 📊 Monitoring Your Usage and Costs

### Check Your Free Credits

1. Go to **"Billing"** in Google Cloud Console
2. View **"Credits"** section to see remaining balance
3. Set up **billing alerts** to notify you at thresholds (e.g., 50%, 75%, 90%)

### Monitor API Usage

1. Go to **"APIs & Services" > "Dashboard"**
2. View requests, errors, and latency for each API
3. Check **"Quotas"** to see your limits

### Estimate Costs (After Free Trial)

Approximate pricing (subject to change):
- **Cloud Storage**: $0.020 per GB/month (Standard class)
- **Vertex AI (Veo)**: Pricing TBD (currently in preview)
- **Network Egress**: First 1 GB free per month, then $0.12/GB

**Example**: Storing 100 videos (5 GB total) = ~$0.10/month

---

## 🔄 Switching Between Test and Production

### To Use Test Mode (FREE):
```env
VIDEO_MODE=test
```
- No API calls made
- Uses sample video URLs
- Perfect for development

### To Use Production Mode:
```env
VIDEO_MODE=production
```
- Calls real Google Veo API
- Generates actual videos
- Stores in Google Cloud Storage
- Uses your credits/billing

### When to Use Each Mode:

**Test Mode**:
- ✅ Local development
- ✅ Testing new features
- ✅ Demonstrations
- ✅ CI/CD pipelines
- ✅ Before you have Google Cloud access

**Production Mode**:
- ✅ Live production server
- ✅ Real user requests
- ✅ After Google Cloud setup complete
- ✅ After Veo API access granted

---

## 🛠️ Troubleshooting

### "Permission Denied" Errors
- Check service account has correct roles
- Verify credentials file path in `.env`
- Ensure APIs are enabled

### "Bucket Not Found" Errors
- Verify bucket name in `.env` matches Google Cloud
- Check bucket is in correct project
- Ensure service account has Storage permissions

### "Quota Exceeded" Errors
- Check your API quotas in Google Cloud Console
- Request quota increase if needed
- Monitor usage in "APIs & Services" dashboard

### "Veo API Not Available"
- Confirm you've requested and received API access
- Check if API is enabled in your project
- Try a different Google Cloud region

---

## 🔐 Security Best Practices

1. **Never commit credentials to Git**:
   - `google-credentials.json` is already in `.gitignore`
   - Double-check before pushing code

2. **Use environment variables**:
   - All sensitive data in `.env` file
   - Different `.env` for dev/staging/production

3. **Restrict service account permissions**:
   - Only grant necessary roles
   - Use principle of least privilege

4. **Monitor access logs**:
   - Regularly check Cloud Storage access logs
   - Set up alerts for unusual activity

5. **Rotate credentials periodically**:
   - Create new service account keys every 90 days
   - Delete old keys after rotation

---

## 📞 Support and Resources

- **Google Cloud Documentation**: https://cloud.google.com/docs
- **Vertex AI Documentation**: https://cloud.google.com/vertex-ai/docs
- **Cloud Storage Documentation**: https://cloud.google.com/storage/docs
- **Google Cloud Free Trial**: https://cloud.google.com/free
- **Support**: https://cloud.google.com/support

---

## ✅ Quick Setup Checklist

- [ ] Create Google Cloud account ($300 free trial)
- [ ] Create new project and copy Project ID
- [ ] Enable Vertex AI, Cloud Storage, STT, TTS APIs
- [ ] Create service account with proper roles
- [ ] Download service account JSON key
- [ ] Place credentials in `storage/app/google-credentials.json`
- [ ] Create Cloud Storage bucket
- [ ] Configure bucket permissions
- [ ] Request Google Veo API access (if needed)
- [ ] Update `.env` with all configuration
- [ ] Test with `VIDEO_MODE=test` first
- [ ] Switch to `VIDEO_MODE=production` when ready
- [ ] Monitor usage and costs

---

**Need Help?** Check the Laravel logs in `storage/logs/laravel.log` for detailed error messages.
