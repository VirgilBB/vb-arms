# VB Arms Age Verification Plugin

WordPress plugin that displays a mandatory 18+ age verification modal on every site visit.

## Features

✅ **Mandatory Verification** - Cannot be dismissed without confirming age  
✅ **Session-Based** - Remembers verification for browser session  
✅ **Dark Theme** - Matches VB Arms brand styling  
✅ **Mobile Responsive** - Works on all devices  
✅ **Secure** - Uses cookies and AJAX verification  
✅ **Non-Dismissible** - Cannot close by clicking outside or ESC key

## Installation

### Method 1: Via FTP/SFTP

1. Upload the `vb-arms-age-verification` folder to `/wp-content/plugins/`
2. Go to **Plugins** in WordPress admin
3. Find "VB Arms Age Verification"
4. Click **Activate**

### Method 2: Via WordPress Admin

1. Zip the `vb-arms-age-verification` folder
2. Go to **Plugins > Add New**
3. Click **Upload Plugin**
4. Select the zip file
5. Click **Install Now** then **Activate**

## Configuration

### Exit URL (Optional)

By default, clicking "I am under 18" redirects to Google. To change this:

1. Edit `vb-arms-age-verification.php`
2. Find line with `'redirect' => 'https://www.google.com'`
3. Change to your desired exit URL
4. Save file

### Cookie Duration

**24-hour rule:** Verification is remembered for **24 hours** in the same browser. After 24 hours, or in a new browser/device, the modal shows again.

- Same browser: no re-verify for 24 hours.
- New browser or after 24 hours: user must verify again.

To change the duration (e.g. 2 days), edit the plugin:

1. Edit `vb-arms-age-verification.php`
2. Find `$cookie_duration_days = 1;`
3. Change to number of days (e.g., `2` for 48 hours)
4. Save file

## How It Works

1. **On Page Load**: Checks if age is verified via cookie (24-hour expiry)
2. **If Not Verified**: Shows modal immediately
3. **User Confirms**: Sets 24-hour cookie and hides modal
4. **User Exits**: Redirects to exit URL (default: Google)
5. **Next Visit (same browser, within 24h)**: Cookie exists, modal doesn't show. **New browser or after 24h**: Modal shows again

## Customization

### Styling

Edit `assets/age-verification.css` to customize:
- Colors
- Fonts
- Modal size
- Animations
- Button styles

### Text Content

Edit the `render_age_verification_modal()` function in `vb-arms-age-verification.php` to change:
- Modal title
- Age message
- Disclaimer text
- Warning text
- Button labels

## Requirements

- WordPress 5.0+
- PHP 7.4+
- jQuery (bundled with WordPress)

## Browser Support

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers

## Security

- Uses WordPress nonces for AJAX security
- HttpOnly cookies (when SSL enabled)
- SameSite cookie attribute
- Secure flag on HTTPS

## Troubleshooting

### Modal Not Showing

1. Clear browser cookies
2. Check browser console for JavaScript errors
3. Verify plugin is activated
4. Check file permissions

### Modal Won't Close

- This is intentional - modal cannot be dismissed without confirming
- User must click "I am 18 or older" or "I am under 18"

### Cookie Not Working

1. Check if site is using HTTPS (required for Secure flag)
2. Verify cookie settings in browser
3. Check browser console for cookie errors

## Support

For issues:
1. Check WordPress debug log
2. Check browser console
3. Verify plugin is activated
4. Check file permissions

---

**Version**: 1.0.0  
**Last Updated**: December 27, 2025
