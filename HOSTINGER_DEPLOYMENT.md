# BoyCold Café - Hostinger Deployment Guide

## Overview
This guide will help you deploy the BoyCold Café application to Hostinger and fix the HTTP 500 errors during registration and Google authentication.

---

## 📋 Prerequisites

1. **Active Hostinger Account** with:
   - cPanel access
   - At least one MySQL database
   - PHP 7.4+ installed (recommend 8.0+)
   - Composer support enabled

2. **Domain/Subdomain** configured
3. **Google OAuth Credentials** (from Google Cloud Console)
4. **Gmail App Password** (for OTP emails)

---

## 🔧 Step 1: Get Your Database Credentials

### From Hostinger cPanel:
1. Log in to **cPanel**
2. Go to **Databases** → **MySQL Databases**
3. Create a new database (or use existing)
4. Create a database user with **ALL PRIVILEGES**
5. Copy:
   - **Database Name** → `DB_NAME`
   - **Username** → `DB_USER`
   - **Password** → `DB_PASS`
   - **Host** (usually `localhost`)
   - **Port** (usually `3306`)

---

## 🌐 Step 2: Configure Environment Variables

### On Your Local Machine (before uploading):

1. **Create `.env` file** from `.env.example`:
```bash
cp .env.example .env
```

2. **Edit `.env` with your Hostinger database credentials**:
```
DB_HOST=localhost
DB_USER=your_hostinger_db_user
DB_PASS=your_hostinger_db_password
DB_NAME=your_hostinger_db_name
DB_PORT=3306

GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=https://boycoldcafe.com/User/google-callback.php

GMAIL_USERNAME=boycoldcafe19@gmail.com
GMAIL_APP_PASSWORD=your_gmail_app_password

APP_DEBUG=0
SMTP_DEBUG=0
```

3. **Save the file** (never commit to Git)

---

## 📤 Step 3: Upload Files to Hostinger

### Using FTP/SFTP or File Manager:

1. Connect to your Hostinger server via **SFTP** (recommended) or **FTP**
2. Upload all files to your **public_html** folder (or your domain's folder)
3. **Make sure to include:**
   - `.env` file (with your credentials)
   - `config/` folder
   - `User/` folder
   - `vendor/` folder (from Composer)
   - All other directories

### Using Git:
```bash
git clone your_repo.git
cd boycoldv2
cp .env.example .env
# Edit .env with your credentials
git add .
git commit -m "Add production .env"
git push origin main
```

---

## 🗂️ Step 4: Set Up Database on Hostinger

1. In **cPanel**, go to **phpMyAdmin**
2. Select your database
3. **Import the database schema** from `config/boycold_db.sql`:
   - Go to **Import** tab
   - Choose the `.sql` file
   - Click **Go**

### Configure automatic POS shift reset

After importing the schema, create a Hostinger/cPanel cron job that runs every minute:

```text
* * * * * /usr/bin/php /home/USERNAME/domains/DOMAIN/public_html/config/cron_reset_shifts.php >> /home/USERNAME/shift-cron.log 2>&1
```

Replace `USERNAME`, `DOMAIN`, and the PHP path with the values shown by Hostinger. The script uses `Asia/Manila`, closes any prior open sales-day shift, saves its totals, and creates at most one open shift for the current sales day. It is safe to run repeatedly.

---

## 🔍 Step 5: Verify Setup

### Create a Debug Script:

Create a file `config/debug.php` with:
```php
<?php
require_once 'db_config.php';

echo "<h2>BoyCold Café - Deployment Debug</h2>";

// Test 1: Database Connection
if ($connect && !$connect->connect_error) {
    echo "✅ Database Connected Successfully<br>";
    
    // Check if users table exists
    $result = $connect->query("SHOW TABLES LIKE 'users'");
    if ($result && $result->num_rows > 0) {
        echo "✅ Users table found<br>";
    } else {
        echo "❌ Users table NOT found - Import database schema!<br>";
    }
} else {
    echo "❌ Database Connection FAILED<br>";
    echo "Error: " . $connect->connect_error . "<br>";
}

// Test 2: PHPMailer
echo "<br><h3>Email Test:</h3>";
require_once 'mailer.php';
if (function_exists('sendOTPEmail')) {
    echo "✅ PHPMailer loaded successfully<br>";
} else {
    echo "❌ PHPMailer NOT loaded<br>";
}

// Test 3: Google OAuth Config
echo "<br><h3>Google OAuth Test:</h3>";
require_once 'google.php';
if (defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== '') {
    echo "✅ Google OAuth configured<br>";
    echo "Client ID: " . substr(GOOGLE_CLIENT_ID, 0, 20) . "...<br>";
} else {
    echo "❌ Google OAuth NOT configured<br>";
}

// Test 4: File Permissions
echo "<br><h3>File Permissions:</h3>";
$uploadDir = __DIR__ . '/../User/uploads';
if (is_writable($uploadDir)) {
    echo "✅ Upload directory is writable<br>";
} else {
    echo "⚠️ Upload directory may not be writable<br>";
}

// Test 5: Session
session_start();
if (isset($_SESSION)) {
    echo "✅ Sessions working<br>";
} else {
    echo "❌ Sessions NOT working<br>";
}

echo "<br><hr><p style='font-size: 12px; color: #666;'>After successful tests, delete this file immediately!</p>";
?>
```

Access it at: `https://boycoldcafe.com/config/debug.php`

**After testing, DELETE this file!**

---

## ❌ Troubleshooting Common Issues

### Issue 1: Error 500 on Registration/Callback
**Solution:**
- Check `.env` file exists and has correct database credentials
- Check error logs in cPanel → Errors
- Make sure `vendor/autoload.php` is uploaded
- Verify database tables exist

### Issue 2: "Database connection failed"
**Solution:**
- Verify credentials in `.env` match cPanel
- Check if database user has CREATE, INSERT, UPDATE privileges
- Test connection in cPanel → phpMyAdmin

### Issue 3: OTP Email Not Sending
**Solution:**
- Verify `GMAIL_APP_PASSWORD` is correct (enable 2FA first)
- Check SMTP settings in `config/mailer.php`
- Look for email errors in error logs

### Issue 4: Google Login Redirect Loop
**Solution:**
- Verify `GOOGLE_REDIRECT_URI` matches exactly in `.env` and Google Cloud Console
- Make sure `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` are correct
- Check browser cookies are enabled

---

## 📝 Step 6: Test the Application

### Test Registration:
1. Go to `https://boycoldcafe.com/User/register.php`
2. Fill in form and submit
3. Should redirect to OTP verification page
4. Check email for OTP code

### Test Google Login:
1. Go to `https://boycoldcafe.com/User/login.php`
2. Click "Continue with Google"
3. Should redirect back and create session

### Check Error Logs:
- cPanel → File Manager → `public_html/error_log`
- Or cPanel → Errors
- Look for database or PHP errors

---

## 🛡️ Security Best Practices

1. **Never commit `.env`** to Git
2. **Restrict access** to `config/debug.php` - delete it after testing
3. **Set proper file permissions:**
   - Directories: `755`
   - Files: `644`
   - `.env`: `600` (if possible)

4. **Disable PHP errors** in production:
   - Set `APP_DEBUG=0` in `.env`
   - Configure error logging to files

---

## 📞 Support

If issues persist:
1. Check Hostinger's error logs
2. Review `.env` configuration
3. Verify database schema is imported
4. Check file/folder permissions (755/644)
5. Contact Hostinger support if database credentials are needed

---

## ✅ Deployment Checklist

- [ ] `.env` file created with correct credentials
- [ ] All files uploaded to public_html
- [ ] Database imported from `boycold_db.sql`
- [ ] File permissions set correctly (755/644)
- [ ] `config/debug.php` created and tested
- [ ] Google OAuth credentials verified
- [ ] Gmail app password configured
- [ ] Registration test completed
- [ ] Google login test completed
- [ ] `config/debug.php` deleted for security
- [ ] Error logs reviewed and cleared

---

**Last Updated:** 2026-09-02
