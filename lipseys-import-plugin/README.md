# Lipsey's Product Importer for WooCommerce

WordPress plugin to import products from Lipsey's CSV catalog into WooCommerce with automatic image handling and Zen Payments integration.

## Installation

1. Upload the `lipseys-import-plugin` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **WooCommerce > Lipsey's Import** in your admin dashboard

## Features

- ✅ Import products from Lipsey's CSV catalog
- ✅ **Lipsey's API** — credentials, test connection, optional submit of paid orders to Lipsey's (APIOrder)
- ✅ Automatic category mapping (Firearms, Accessories, etc.)
- ✅ Image download and attachment
- ✅ FFL/SOT flag handling
- ✅ Zen Payments integration (for FFL-required products)
- ✅ Batch processing (process large imports safely)
- ✅ Update existing products or create new ones
- ✅ Progress tracking and error logging
- ✅ CSV preview before import

## Usage

### Lipsey's API (optional)

1. Go to **WooCommerce > Lipsey's API**
2. Enter your Lipsey's **email** and **password** (server IP must be whitelisted by Lipsey's)
3. Click "Test connection" to verify login
4. Optionally enable **Submit paid orders to Lipsey's API** — then when a customer pays (e.g. via Authorize.net), line items are sent to Lipsey's. Products must have Lipsey's item number in **SKU** or custom field **lipseys_item_no**

### Step 1: Configure Image Base URL (CSV import)

1. Go to **WooCommerce > Lipsey's Import**
2. Enter the base URL for Lipsey's product images (e.g., `https://lipseys.com/images/`)
3. Click "Test Image URL" to verify it works
4. Click "Initialize Category Structure" to create default WooCommerce categories

### Step 2: Import Products

1. Click "Choose File" and select your Lipsey's CSV file
2. Set batch size (recommended: 50 products per batch)
3. Check "Update Existing Products" if you want to update products that already exist
4. Click "Preview CSV" to see a sample of products that will be imported
5. Click "Start Import" to begin the import process

### Step 3: Monitor Progress

- Watch the progress bar and statistics
- Check the error log if any issues occur
- The import will process in batches to avoid timeouts

### Attach images (after import with "Skip images" checked)

If you imported with **Skip image download** to avoid 502 timeouts:

- **In admin:** Go to **WooCommerce → Lipsey's Import → API Import** tab, scroll to **Attach images later**, and click **Attach images to products missing thumbnails**. Leave the tab open until remaining is 0 (runs in small batches).
- **From command line (if your host has WP-CLI):** Run:
  ```bash
  wp lipseys attach-images
  ```
  Optional: `--batch=20` (products per batch; default 10), `--batches=50` (stop after 50 batches). Example: `wp lipseys attach-images --batch=20` runs until all products have thumbnails.

## Configuration

### Image Base URL

Update the image base URL in the plugin settings. Common formats:
- `https://lipseys.com/images/`
- `https://cdn.lipseys.com/products/`
- Or your custom image server URL

### Category Mapping

The plugin automatically maps Lipsey's product types to WooCommerce categories:

**Firearms:**
- Rifles
- Handguns (Semi-Auto Pistols, Revolvers)
- Shotguns
- Suppressors (NFA items)

**Accessories:**
- Magazines
- Optics
- Sights & Lasers
- Lights
- Mounts & Rings
- Suppressor Accessories
- Bipods
- Other Accessories

## Product Data Mapping

| Lipsey's Field | WooCommerce Field |
|---------------|-------------------|
| ITEMNO | SKU |
| DESCRIPTION1 | Product Name |
| DESCRIPTION2 | Short Description |
| CURRENTPRICE | Regular Price |
| QUANTITY | Stock Quantity |
| TYPE | Product Category |
| IMAGENAME | Featured Image |
| FFLREQUIRED | Custom Meta Field |
| SOTREQUIRED | Custom Meta Field |

## Zen Payments Integration

Products with `FFLREQUIRED = TRUE` and `SOTREQUIRED = FALSE` are automatically flagged for Zen Payments:
- Custom meta field `_use_zen_payments = 'yes'` is set
- Your Zen Payments integration should check this field

## Requirements

- WordPress 5.0+
- WooCommerce 5.0+
- PHP 7.4+
- Sufficient server memory for large imports (512MB+ recommended)

## Troubleshooting

### Images Not Downloading

1. Verify the image base URL is correct
2. Test the image URL using the "Test Image URL" button
3. Check that images are publicly accessible
4. Check WordPress file permissions

### Import Timeout

- Reduce batch size (try 25 or 10)
- Increase PHP `max_execution_time` in php.ini
- Use WP-CLI for command-line imports (coming soon)

### Products Not Appearing

- Check WooCommerce product visibility settings
- Verify products are published (not drafts)
- Check for PHP errors in WordPress debug log

## Support

For issues or questions, check:
1. WordPress debug log (`wp-content/debug.log`)
2. Browser console for JavaScript errors
3. Server error logs

## Changelog

### 1.0.0
- Initial release
- CSV import functionality
- Image download and attachment
- Category mapping
- FFL/SOT handling
- Zen Payments integration
