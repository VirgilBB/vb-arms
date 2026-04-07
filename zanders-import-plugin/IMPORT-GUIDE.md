# Zanders Import Guide - Import Products & Images

## Step 1: Import Products (Without Images First)

Since EasyWP blocks outbound FTP, we'll import products first, then download images separately.

1. **In WordPress Admin → Zanders Import:**
   - ✅ Make sure "Download Product Images" is **UNCHECKED** (faster import)
   - ✅ Set "Batch Size" to **50** (safe for 829 products)
   - ✅ Set "Limit Total Products" to **50** (for your test)
   - ✅ Click **"Start Import"**

2. **Wait for import to complete:**
   - The import will process 50 products at a time
   - You'll see progress updates
   - Products will be created without images

## Step 2: Download Images via VPS

After products are imported, download images using the VPS script:

1. **Upload the image download script to your VPS:**
   ```bash
   scp -i ~/.ssh/your_ssh_key zanders-import-plugin/download-images.php root@YOUR_PROXY_SERVER_IP:/root/zanders-sync/
   ```

2. **SSH into your VPS:**
   ```bash
   ssh -i ~/.ssh/your_ssh_key root@YOUR_PROXY_SERVER_IP
   ```

3. **Run the image download script:**
   
   **Option A: Download images for all products:**
   ```bash
   cd /root/zanders-sync
   php download-images.php --all
   ```
   
   **Option B: Download images for specific products:**
   ```bash
   php download-images.php ITEM001 ITEM002 ITEM003
   ```

4. **The script will:**
   - Connect to Zanders FTP
   - Find images in `Images/` folder
   - Download each image
   - Upload to WordPress
   - Attach to the correct product

## Step 3: Full Import (All 829 Products)

Once you've tested with 50 products:

1. **In WordPress Admin:**
   - Set "Limit Total Products" to **0** (no limit)
   - Click **"Start Import"** again
   - This will import all 829 Glock products

2. **Download all images:**
   ```bash
   php download-images.php --all
   ```

## Troubleshooting

### Images not downloading?
- Check if images exist on Zanders FTP: `Images/ITEMNUMBER.jpg`
- Check WordPress error logs
- Verify API credentials are correct

### Import is slow?
- Reduce batch size to 25
- Uncheck "Download Product Images" during import
- Download images separately after import

### Products not showing?
- Check WooCommerce → Products
- Verify products are published
- Check if filters are too restrictive

## Next Steps

After importing Glock products:
1. Review products in WooCommerce
2. Adjust categories if needed
3. Import more manufacturers (remove Glock filter)
4. Set up automated sync for daily updates
