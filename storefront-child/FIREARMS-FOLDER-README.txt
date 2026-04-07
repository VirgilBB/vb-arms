Six hero images (homepage product grid)
======================================

HOW IT WORKS (Zanders or manual)
--------------------------------
1) Zanders / manual products: If a WooCommerce product has _upc or SKU equal to one of the 6 UPCs below, the template uses that product's name, link, and featured image. (Zanders import can set _upc.)
2) Manual upload: If no product image, the template looks for an image whose filename CONTAINS that UPC in one of these folders (first found wins):
   - wp-content/themes/storefront-child/logos/firearms/
   - wp-content/uploads/logos/firearms/
   - wp-content/uploads/firearms/

WHERE TO UPLOAD (manual)
-------------------------
Create one of these on the server and upload the 6 files (filename must contain the UPC):
  storefront-child/logos/firearms/   (theme)
  wp-content/uploads/logos/firearms/
  wp-content/uploads/firearms/

Required filenames (contain UPC):
  glock43xmos 764503064999.jpg
  ruger RUPRCN-RF922MAG PRECISION 22MAG 736676084050.jpg
  sig p365 798681719983.jpg
  springfield hellcat  706397999650.jpg
  tikka t3x 082442017921.jpg
  winchester 048702017261.jpg

UPCs used: 764503064999, 736676084050, 798681719983, 706397999650, 082442017921, 048702017261

Running upload-template-fix.sh from the repo root uploads to theme logos/firearms/.
