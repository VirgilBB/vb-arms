# AI Studio prompt: Fix excessive gap above “Proceed to Checkout” in Cart Totals

Use this in AI Studio (or similar) to fix the large empty space between the Total row and the PROCEED TO CHECKOUT button on the cart page.

---

**Prompt for AI Studio:**

On our WooCommerce cart page (Storefront child theme), the **Cart Totals** box has a huge empty vertical gap between the “Total” line (and its green separator) and the **PROCEED TO CHECKOUT** button. The gap is way too large — it looks like excessive padding or margin and makes the box feel broken.

**Cause (in our code):**  
The cart layout uses a two-column grid with `align-items: stretch`, so the Cart Totals column stretches to match the height of the product list. The `.cart_totals` block has `display: flex`, `flex-direction: column`, and the `.wc-proceed-to-checkout` block has `margin-top: auto` so the button sits at the bottom of that tall column. All the “extra” height becomes empty space between the totals table and the button.

**What we want:**  
Either eliminate the stretch so the Cart Totals box is only as tall as its content (totals table + a small gap + button), or keep the stretch but limit the gap above the button to something reasonable (e.g. no more than about 1rem–1.5rem). The button should sit close to the Total line, not far below it.

**File and selectors:**  
- **File:** `storefront-child/woocommerce/cart.php` — all relevant styles are in the `<style>` block in the template.
- **Grid:** `.vb-cart-layout .woocommerce` — currently `align-items: stretch`.
- **Cart totals box:** `.vb-cart-page .cart_totals` — currently `flex: 1`, `display: flex`, `flex-direction: column`.
- **Button wrapper:** `.vb-cart-page .cart_totals .wc-proceed-to-checkout` — currently `margin-top: auto !important`.

**Please provide:**  
Exact CSS changes (and, if needed, a one-sentence layout approach) so the gap between the Total row and the PROCEED TO CHECKOUT button is small and consistent (e.g. about 1rem), and the Cart Totals box no longer has a huge empty band of space. Prefer changing only the cart template’s inline styles; avoid breaking the two-column layout or the green-bordered styling.

---

