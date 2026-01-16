# Environment Configuration Setup

## Security Notice 🔒

This project now uses environment variables to store sensitive credentials. The `.env` file contains your actual credentials and should **NEVER** be committed to Git.

## Setup Instructions

### First Time Setup

1. **Copy the example file**:
   ```bash
   cp .env.example .env
   ```

2. **Edit the `.env` file** and replace the placeholder values with your actual credentials:
   - `GOOGLE_CLIENT_ID`: Your Google OAuth Client ID from [Google Cloud Console](https://console.cloud.google.com/)
   - `GOOGLE_CLIENT_SECRET`: Your Google OAuth Client Secret
   - `GOOGLE_REDIRECT_URI`: The callback URL (usually `http://localhost/autotimetable/google-callback.php` for local development)

3. **Save the file** - Your credentials are now secure!

### How It Works

- The `.env` file stores your sensitive credentials
- The `.gitignore` file ensures `.env` is never committed to Git
- The `.env.example` file shows the required variables (with placeholder values)
- The `config/env_loader.php` file loads variables from `.env` into your application
- The `config/google_auth.php` file reads credentials from environment variables

### For Git Uploads

When you upload your project to Git:
- ✅ `.env.example` **WILL** be uploaded (safe, no real credentials)
- ✅ `.gitignore` **WILL** be uploaded (protects your secrets)
- ❌ `.env` **WILL NOT** be uploaded (contains your real credentials)

### Collaborating with Others

When someone clones your repository, they should:
1. Copy `.env.example` to `.env`
2. Fill in their own credentials in the `.env` file
3. The application will work with their credentials

## Files Overview

- **`.env`** - Your actual credentials (git-ignored)
- **`.env.example`** - Template with placeholder values (committed to git)
- **`.gitignore`** - Ensures `.env` stays local
- **`config/env_loader.php`** - Loads environment variables
- **`config/google_auth.php`** - Uses environment variables

## Important Notes

⚠️ **Never commit the `.env` file!**  
⚠️ **Never hardcode credentials in your code!**  
✅ **Always use `.env.example` as a template for new developers!**

---

Your credentials are now secure and ready for Git! 🚀
