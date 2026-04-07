# Zanders Inventory Importer for WooCommerce

WordPress plugin to import products from Zanders inventory via FTP with automatic image handling and Zen Payments integration.

## Installation

1. Upload the `zanders-import-plugin` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **WooCommerce > Zanders Import** in your admin dashboard

## Features

- ✅ Direct FTP connection to Zanders inventory server
- ✅ Support for both CSV and XML file formats
- ✅ Automatic download of inventory files from FTP
- ✅ Live inventory updates (every 5 minutes)
- ✅ Tier pricing support (Price1, Price2, Price3 with quantity breaks)
- ✅ Automatic category mapping (Firearms, Accessories, etc.)
- ✅ Image download from FTP Images folder
- ✅ FFL/SOT flag handling
- ✅ Zen Payments integration (for FFL-required products)
- ✅ Batch processing (process large imports safely)
- ✅ Update existing products or create new ones
- ✅ Progress tracking and error logging
- ✅ Inventory preview before import

## FTP Configuration

### Required Information

You'll need the following from Zanders:

- **FTP Host**: `ftp2.gzanders.com` (default)
- **Username**: Your Zanders FTP username
- **Password**: Your Zanders FTP password
- **Dedicated Folder**: Your assigned folder path (if applicable)
- **Control #**: Your control number (for reference)

### File Formats

**Method #1 - CSV Files:**
- `ZandersInv.csv` - Full inventory (updated daily)
- `LiveInv.csv` - Live inventory with pricing (updated every 5 minutes)

**Method #2 - XML Files:**
- `ZandersInv.xml` - Full inventory (updated daily)
- `Qtypricingout.xml` - Live pricing (updated every 5 minutes)

## Usage

### Step 1: Configure FTP Connection

1. Go to **WooCommerce > Zanders Import**
2. Enter your FTP credentials:
   - FTP Host: `ftp2.gzanders.com`
   - Username: Your Zanders username
   - Password: Your Zanders password
   - Dedicated Folder: (if provided)
3. Select file format (CSV or XML)
4. Enable "Use Live Inventory" to get real-time pricing updates
5. Click "Test FTP Connection" to verify
6. Click "Save Settings"

### Step 2: Download Files

1. Click "Download Files from FTP"
2. The plugin will download:
   - Main inventory file (ZandersInv.csv or ZandersInv.xml)
   - Live inventory file (if enabled)
3. Files are stored in `/wp-content/uploads/zanders-import/`

### Step 3: Preview Inventory

1. Click "Preview Inventory" to see a sample of products
2. Review the data structure and fields
3. Verify product counts and pricing

### Step 4: Import Products

1. Set batch size (recommended: 50 products per batch)
2. Check "Update Existing Products" if you want to update existing products
3. Check "Download Product Images" to download images from FTP
4. Click "Start Import" to begin the import process

### Step 5: Monitor Progress

- Watch the progress bar and statistics
- Check the error log if any issues occur
- The import will process in batches to avoid timeouts

## Field Mapping

### Zanders CSV Fields → WooCommerce

- `Item#` → Product SKU
- `Desc1` + `Desc2` → Product Name
- `MFG` → Manufacturer (meta + attribute)
- `MFGPNum` → Manufacturer Part Number (meta)
- `Category` → WooCommerce Categories
- `Price1`, `Price2`, `Price3` → Tier Pricing (stored as meta)
- `Qty1`, `Qty2`, `Qty3` → Stock Quantity
- `MSRP` → MSRP (meta)
- `UPC` → UPC (meta)
- `Weight` → Product Weight
- `Serialized` → Serialized Flag (meta)
- `Avail` → Stock Status

### Image Handling

- Images are downloaded from FTP `Images/` folder
- Image filename matches `Item#` (e.g., `12345.jpg`)
- Images are imported into WordPress media library
- Images are set as product featured images

## Category Mapping

The plugin automatically maps Zanders categories to WooCommerce:

**Firearms:**
- Rifles
- Handguns
- Shotguns
- Suppressors

**Accessories:**
- Magazines
- Optics
- Sights & Lasers
- Lights
- Mounts & Rings
- Suppressor Accessories
- Bipods
- Other Accessories

## FFL/SOT Handling

- **FFL Required**: Automatically detected for firearms (rifles, handguns, shotguns)
- **SOT Required**: Automatically detected for suppressors/silencers
- **Zen Payments**: Enabled for FFL-required products (non-NFA)

## Tier Pricing

Zanders supports tier pricing with quantity breaks:

- **Price1 / Qty1**: Base price and minimum quantity
- **Price2 / Qty2**: Second tier (higher quantity)
- **Price3 / Qty3**: Third tier (highest quantity)

The plugin stores all tier pricing as product meta and uses Price1 as the base WooCommerce price.

## Troubleshooting

### PHP FTP Extension Not Enabled

**Error:** "PHP FTP extension is not enabled"

**Solution:**
1. **cPanel/Shared Hosting:**
   - Go to "Select PHP Version" or "PHP Configuration"
   - Click "Extensions"
   - Enable "ftp"
   - Save and restart PHP

2. **VPS/Dedicated Server:**
   ```bash
   # Ubuntu/Debian
   sudo apt-get install php-ftp
   sudo systemctl restart php-fpm
   
   # CentOS/RHEL
   sudo yum install php-ftp
   sudo systemctl restart php-fpm
   ```

3. **Managed WordPress Hosting:**
   - Contact your hosting support
   - They can enable it quickly via support ticket

4. **Edit php.ini directly:**
   - Find php.ini: `php --ini`
   - Edit: Remove `;` from `;extension=ftp` → `extension=ftp`
   - Restart PHP/web server

**See `FTP-EXTENSION-GUIDE.md` for detailed instructions.**

### FTP Connection Issues

- Verify your FTP credentials are correct
- Check if your IP address is whitelisted (contact Zanders)
- Ensure passive mode is enabled (handled automatically)
- Verify the dedicated folder path is correct
- Try different ports (21 for FTP, 990 for FTPS)
- Try enabling/disabling SSL/TLS

### Image Download Issues

- Verify images exist in FTP `Images/` folder
- Check image file extensions (jpg, jpeg, png, gif)
- Ensure FTP connection is active during import
- Check WordPress upload directory permissions

### Import Errors

- Check error log in the progress section
- Verify CSV/XML file format matches expected structure
- Ensure WooCommerce is active and configured
- Check PHP memory limits and execution time

## Requirements

- WordPress 5.0+
- WooCommerce 5.0+
- PHP 7.4+
- FTP extension enabled in PHP
- XML extension enabled in PHP

## Automated Sync (VPS/Local Machine)

Since EasyWP blocks outbound FTP connections, use the automated sync script on your VPS or local machine:

### Setup

1. **Create WordPress Application Password:**
   - Go to **Users → ZandersAuto → Edit**
   - Scroll to **"Application Passwords"**
   - Create new password: `Zanders Auto Sync`
   - Copy the password (24 characters)

2. **Configure `automated-sync.php`:**
   - Update `WORDPRESS_PASS` with your Application Password
   - Zanders FTP credentials are already configured

3. **Run the script:**
   ```bash
   php automated-sync.php
   ```

4. **Set up cron job** (optional - for automation):
   ```bash
   # Daily at 2 AM
   0 2 * * * /usr/bin/php /path/to/automated-sync.php
   ```

### Download Images

After importing products, download images using the VPS script:

```bash
# Download images for all products
php download-images.php --all

# Or for specific products
php download-images.php ITEM001 ITEM002 ITEM003
```

**See `AUTOMATED-SYNC-GUIDE.md` and `IMPORT-GUIDE.md` for detailed instructions.**

## Support

For issues or questions:
1. Check the error log in the admin interface
2. Verify FTP credentials and connection
3. Review WordPress debug log
4. Contact Zanders support for FTP access issues
