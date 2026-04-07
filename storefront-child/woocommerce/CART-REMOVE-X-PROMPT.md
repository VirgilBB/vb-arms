# Prompt: Fix Green X in Cart Delete Circles

The cart page has a "remove item" button for each product: a circular green-outline button that should show a **green × (X)** centered inside. The X is not appearing (circles are empty). Fix it so the × is visible, green, and centered inside each circle.

## Context

- **Theme:** Storefront child theme.
- **File:** `storefront-child/woocommerce/cart.php` — styles are in a `<style>` block in the template.
- **HTML:** WooCommerce outputs a link in the first column of each cart row: `<td class="product-remove"><a href="...?remove_item=..." class="remove" aria-label="Remove ...">×</a></td>`. The link may contain the × character as text, or it may be empty/icon.
- **Current attempt (failed):** The link is styled with `display: flex`, `align-items: center`, `justify-content: center`, `font-size: 0` (to hide original text), and `::after { content: "×"; color: var(--accent-green); font-size: 1.4rem; }`. The circles still render empty — either the ::after is not showing (overridden, stripped, or not supported in context) or something else is hiding the content.

## What to do

1. **Make the green × visible and centered** inside each `.product-remove a` circle (32px height/width, border-radius 50%, green border).
2. **Options to try:**
   - **Option A:** Do not hide the link text; style the link so its text content (×) is visible, green (`color: var(--accent-green)` or `#00ff94`), and centered with flexbox. Ensure no other rule (theme, WooCommerce) is hiding the link or its text.
   - **Option B:** If the theme/WooCommerce removes or overrides the ×, inject it with a **::before** or **::after** pseudo-element and ensure that pseudo-element is not stripped (e.g. use high-specificity selectors and `!important` on `content` and `color`). Check for any `content: none` or `display: none` on `.product-remove a` or its pseudo-elements elsewhere.
   - **Option C:** Use a small inline SVG or icon (e.g. dashicons or a custom SVG ×) inside the link via PHP/HTML if the template can be overridden; then style it green and centered.
3. **Specificity:** Use the class `.vb-cart-page .product-remove a` so these rules win over theme/WooCommerce. Add `!important` where needed so the × and its color are not overridden.
4. **Hover:** On hover, the × can turn white to match the existing hover style on the circle.

## Target selectors (in cart.php)

- `.vb-cart-page .product-remove` — table cell, 40px width.
- `.vb-cart-page .product-remove a` — the circular button (32×32px, border-radius 50%, green border). The × must appear inside this and be centered.

Provide the exact CSS (and if necessary minimal PHP/HTML) to add or replace in `storefront-child/woocommerce/cart.php` so the green × appears and is centered in every remove circle.
