# How to Edit the Age Verification Modal

## Option 1: Edit PHP File Directly (Recommended)

### Via FTP/SFTP
1. Connect to your WordPress site
2. Navigate to `/wp-content/plugins/vb-arms-age-verification/`
3. Edit `vb-arms-age-verification.php`
4. Find the `render_age_verification_modal()` function (around line 98)
5. Edit the HTML directly
6. Save and upload back to server

### Via WordPress Admin (if you have file editor access)
1. Go to **Plugins > Plugin Editor**
2. Select "VB Arms Age Verification" from dropdown
3. Click on `vb-arms-age-verification.php`
4. Edit the file
5. Click **Update File**

**⚠️ Warning**: Editing plugins directly can break your site if you make a syntax error. Always backup first!

---

## Option 2: Use a Code Editor Plugin

Install a plugin like:
- **WP File Manager**
- **File Manager**
- **Code Snippets** (for custom code)

These let you edit files through WordPress admin with syntax highlighting.

---

## Option 3: Edit CSS Only (Easier)

For styling changes only:

1. Go to **Appearance > Customize > Additional CSS**
2. Add custom CSS to override styles
3. Changes apply immediately
4. No file editing needed

Example:
```css
/* Make buttons even thinner */
.vb-arms-age-btn {
    min-width: 60px !important;
    padding: 0.75rem 1rem !important;
}

/* Adjust logo size */
.vb-arms-age-logo img {
    max-width: 150px !important;
}
```

---

## What You Can Edit

### In PHP File (`vb-arms-age-verification.php`)

**Modal Content** (lines ~104-128):
- Logo display
- Header text ("VERIFY YOUR AGE")
- Disclaimer text
- Question text
- Button labels ("Yes", "No")

**Example - Change Button Text:**
```php
<button id="vb-arms-confirm-age" class="vb-arms-age-btn vb-arms-age-btn-primary">
    I Confirm I'm 18+
</button>
```

### In CSS File (`assets/age-verification.css`)

**Styling:**
- Colors
- Font sizes
- Button sizes
- Spacing
- Logo size

**Example - Make Buttons Thinner:**
```css
.vb-arms-age-btn {
    min-width: 60px;
    padding: 0.75rem 0.5rem;
}
```

---

## Live Preview Options

### Option 1: Browser DevTools (Best for Testing)
1. Open your site
2. Press `F12` (or right-click > Inspect)
3. Go to **Elements** tab
4. Find the modal HTML
5. Right-click > **Edit as HTML**
6. Make changes and see them instantly
7. Copy your changes back to the PHP file

### Option 2: WordPress Customizer (CSS Only)
1. Go to **Appearance > Customize**
2. Click **Additional CSS**
3. Add CSS rules
4. See changes in real-time preview
5. Publish when satisfied

### Option 3: Staging Site
- Create a staging/test site
- Make changes there
- Test everything
- Copy working code to live site

---

## Quick Edits You Might Want

### Change Logo Size
In CSS file:
```css
.vb-arms-age-logo img {
    max-width: 150px; /* Change this value */
}
```

### Change Button Width
In CSS file:
```css
.vb-arms-age-btn {
    min-width: 80px; /* Change this value */
}
```

### Change Text Content
In PHP file, find and edit:
```php
<h2>VERIFY YOUR AGE</h2>  <!-- Change this -->
<p class="vb-arms-age-disclaimer">...</p>  <!-- Change this -->
<strong>Are you 18 years of age or older?</strong>  <!-- Change this -->
```

---

## Best Practice

1. **Backup first** - Always backup before editing
2. **Test in staging** - Test changes on a staging site first
3. **Use child theme** - If possible, add customizations to child theme
4. **Document changes** - Note what you changed and why

---

## Need Help?

If you want to make changes but aren't comfortable editing code:
1. Tell me what you want to change
2. I'll provide the exact code
3. You can copy/paste it in

Or I can make the changes for you!
