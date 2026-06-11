# Automated Zanders Inventory Sync

This guide shows you how to set up **fully automated** inventory syncing from Zanders FTP to your WordPress site.

## How It Works

1. **Local Script** runs on your computer (connects to Zanders FTP - works!)
2. Downloads inventory files automatically
3. Uploads them to WordPress via REST API
4. Can run on a schedule (cron job) for hands-free automation

## Setup Instructions

### Step 1: Create WordPress Application Password

1. **Important:** Set the ZandersAuto user role to **"Shop Manager"** (not Subscriber!)
   - This role has the `manage_woocommerce` permission needed for the API

2. Go to **WordPress Admin → Users → ZandersAuto → Edit**
3. Scroll down to **"Application Passwords"** section
4. Enter a name: `Zanders Auto Sync`
5. Click **"Add New Application Password"**
6. **Copy the password** (you'll only see it once!)
   - Format: `xxxx xxxx xxxx xxxx xxxx xxxx` (24 characters)
   - This is DIFFERENT from the user password (Zandersautosync888!)
   - Application passwords are specifically for API access

### Step 2: Configure the Script

1. Open `automated-sync.php` in the plugin folder
2. **Zanders credentials are already configured!** ✅
3. You only need to update the WordPress Application Password:

```php
define('WORDPRESS_PASS', 'xxxx xxxx xxxx xxxx xxxx xxxx'); // Application password from Step 1
```

**Note:** Zanders FTP credentials (Benin / Control #309-25) are already set in the script.

### Step 3: Test the Script

Run it manually first to make sure it works:

```bash
cd /Users/vb/vb-arms/zanders-import-plugin
php automated-sync.php
```

You should see:
```
=== Zanders Inventory Automated Sync ===

Step 1: Connecting to Zanders FTP...
✓ Connected to Zanders FTP

Step 2: Downloading files...
  Downloading ZandersInv.csv... ✓ Success
  Downloading LiveInv.csv... ✓ Success

✓ Downloaded 2 file(s)

Step 3: Uploading to WordPress...
✓ Files uploaded successfully to WordPress!

=== Sync Complete ===
```

### Step 4: Set Up Automation (Cron Job)

#### On Mac (using crontab):

1. Open Terminal
2. Edit crontab:
   ```bash
   crontab -e
   ```

3. Add this line to run daily at 2 AM:
   ```bash
   0 2 * * * /usr/bin/php /Users/vb/vb-arms/zanders-import-plugin/automated-sync.php >> /Users/vb/vb-arms/zanders-sync.log 2>&1
   ```

4. Or run every 6 hours:
   ```bash
   0 */6 * * * /usr/bin/php /Users/vb/vb-arms/zanders-import-plugin/automated-sync.php >> /Users/vb/vb-arms/zanders-sync.log 2>&1
   ```

5. Save and exit (press `Esc`, then type `:wq` if using vim)

#### Verify cron is set up:
```bash
crontab -l
```

## Schedule Options

- **Daily at 2 AM**: `0 2 * * *`
- **Every 6 hours**: `0 */6 * * *`
- **Every 4 hours**: `0 */4 * * *`
- **Twice daily (6 AM and 6 PM)**: `0 6,18 * * *`

## What Happens Automatically

1. Script downloads latest files from Zanders FTP
2. Uploads them to WordPress
3. Files are ready for import in WordPress admin
4. You can then run the import manually, or we can automate that too!

## Next: Automate the Import Too?

We can also automate the actual product import! The script can trigger the WordPress import after uploading files.

## Troubleshooting

### Script won't run:
- Make sure PHP is installed: `php -v`
- Make sure FTP extension is enabled: `php -m | grep ftp`
- Check file permissions: `chmod +x automated-sync.php`

### Connection fails:
- Verify FTP credentials
- Check your internet connection
- Make sure Zanders FTP is accessible from your computer

### WordPress upload fails:
- Verify Application Password is correct
- Check WordPress URL is correct
- Make sure REST API is enabled (it is by default)

### Check logs:
```bash
tail -f /Users/vb/vb-arms/zanders-sync.log
```

## Security Notes

- **Application Passwords** are safer than regular passwords
- They can be revoked anytime from WordPress admin
- The script only needs to upload files, not full admin access
- Files are stored securely in WordPress uploads directory

---

**This gives you full automation!** The script runs automatically, downloads from Zanders, uploads to WordPress - all hands-free!
