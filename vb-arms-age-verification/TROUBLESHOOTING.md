# Age Verification Troubleshooting Guide

## Quick Checks

### 1. Is the Plugin Activated?
- Go to **Plugins** in WordPress admin
- Find "VB Arms Age Verification"
- Make sure it says **Activated** (not just installed)

### 2. Clear Your Browser Cookies
The plugin uses cookies to remember verification. If you tested it before, clear cookies:
- **Chrome/Edge**: Settings > Privacy > Clear browsing data > Cookies
- **Firefox**: Settings > Privacy > Clear Data > Cookies
- Or use Incognito/Private mode to test

### 3. Check Browser Console for Errors
1. Open your website
2. Press `F12` (or right-click > Inspect)
3. Go to **Console** tab
4. Look for any red error messages
5. Share any errors you see

### 4. Verify Files Are Uploaded Correctly
Check that these files exist on your server:
- `/wp-content/plugins/vb-arms-age-verification/vb-arms-age-verification.php`
- `/wp-content/plugins/vb-arms-age-verification/assets/age-verification.css`
- `/wp-content/plugins/vb-arms-age-verification/assets/age-verification.js`

### 5. Check File Permissions
Files should have permissions:
- PHP file: 644
- CSS/JS files: 644
- Folder: 755

## Common Issues

### Issue: Modal Not Showing At All

**Possible Causes:**
1. Plugin not activated
2. Cookie already set (you verified before)
3. JavaScript conflict
4. jQuery not loaded

**Solutions:**
1. Deactivate and reactivate the plugin
2. Clear browser cookies
3. Check browser console for errors
4. Make sure jQuery is loaded (WordPress includes it by default)

### Issue: Modal Shows But Buttons Don't Work

**Possible Causes:**
1. AJAX not working
2. JavaScript error
3. Nonce issue

**Solutions:**
1. Check browser console for errors
2. Verify AJAX URL is correct
3. Check WordPress debug log

### Issue: Works on Some Pages But Not Others

**Possible Causes:**
1. Custom page template not calling `wp_footer()`
2. Theme conflict

**Solutions:**
1. Make sure your page templates include `<?php wp_footer(); ?>`
2. Check if your splash page template has `wp_footer()` at the bottom

## Testing Steps

1. **Clear all cookies** for your site
2. **Open in Incognito/Private window**
3. **Visit your homepage**
4. **Modal should appear immediately**

## Debug Mode

To enable debug mode, add this to your `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Then check `/wp-content/debug.log` for errors.

## Still Not Working?

1. Check WordPress debug log
2. Check browser console
3. Verify plugin is activated
4. Clear all cookies
5. Test in incognito mode
6. Check that `wp_footer()` is called in your templates
