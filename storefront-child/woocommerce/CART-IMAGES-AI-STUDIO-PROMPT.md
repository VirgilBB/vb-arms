# AI Studio prompt: Cart product images fitting inside boxes

Use this in AI Studio (or similar) to get guidance on making product images fit entirely inside their green-bordered boxes on the cart page.

---

**Prompt for AI Studio:**

On our WooCommerce cart page (Storefront child theme), product thumbnails sit inside fixed-size boxes with a thin green border (60×60px display area with padding). Some product images (especially firearms with long barrels or odd aspect ratios) are being cropped: the subject of the image extends beyond the visible area or touches the edges, so the image does not fit entirely inside the box.

We want every product image to fit fully inside its box with no cropping. The box should contain the whole image (or the whole product within the image). We already use:

- `.vb-cart-page .product-thumbnail` — wrapper with overflow: hidden, 64×64px
- `.vb-cart-page .product-thumbnail img` — width/height 60px, max-width/max-height 60px, object-fit: contain, object-position: center, padding: 4px, box-sizing: border-box

Please provide CSS (and if needed minimal PHP/HTML changes) so that:

1. The image content (the product) always fits entirely inside the green-bordered box with no cropping.
2. If the image has a white or transparent background, that background can show; the important part is the product itself not being cut off.
3. Solutions can include: stronger containment (object-fit, object-position), slightly smaller effective area, or WooCommerce/thumbnail generation changes so cart thumbnails are cropped/sized in a way that keeps the product fully visible in a 60×60 (or similar) box.

Target file: `storefront-child/woocommerce/cart.php` (inline styles in the template) or theme `functions.php` if a filter is needed for thumbnail size/quality.

---
