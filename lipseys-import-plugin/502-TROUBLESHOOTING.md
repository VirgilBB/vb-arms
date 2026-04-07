# API Import: HTTP 502 Error

## Two-phase import (recommended)

The plugin uses two steps to avoid 502 on the long catalog fetch:

1. **Fetch catalog** – One request that fetches the full catalog from Lipsey’s API, applies your filters, and caches it (may take 1–2 min). If this returns 502, your host is killing long requests.
2. **Start API Import** – Processes products in batches from the cached catalog only. Each batch is short, so 502 is unlikely.

**Workflow:** Click **Fetch catalog** first. Wait until you see “Catalog loaded: N products.” Then click **Start API Import**. If **Fetch catalog** returns 502, a timeout in the chain (proxy or EasyWP gateway) is killing the request. The Manufacturer filter does not shorten the fetch—we download the full catalog then filter. Try again; if it still 502s, run the fetch via WP-CLI or ask the host to increase the timeout.

## What 502 means

**HTTP 502 Bad Gateway** means the request reached your host’s proxy/gateway (e.g. EasyWP, Cloudflare), but the upstream PHP process didn’t return a valid response in time. The proxy then returns its own HTML error page (so you see “Server returned HTML”).

Common causes:

1. **Gateway timeout** – Fetching the full catalog can take 30–90+ seconds. If the proxy timeout is 60s, the proxy kills the request and returns 502.
2. **PHP process killed** – `max_execution_time` or `memory_limit` exceeded.
3. **PHP fatal error** – Uncaught exception or fatal; the server returns an error page and the proxy may report it as 502.

## What to do

1. **Use “Fetch catalog” first**  
   Always run **Fetch catalog** before **Start API Import**. The fetch request has a 3‑minute timeout; if it still 502s, the host is limiting request length.

2. **Manufacturer filter**  
   Only affects which products are *cached* and imported after the fetch—it does *not* reduce the size of the API request. The full catalog is always requested from Lipsey's; filtering happens in PHP afterward. So a 502 on "Fetch catalog" is a timeout limit, not something the filter can fix.

3. **Batch size**  
   After the catalog is loaded, **Start API Import** uses your batch size (e.g. 10–25). Larger batches are fine once the catalog is cached.

4. **Check PHP limits (if you have access)**  
   Increase `max_execution_time` (e.g. 180) and `memory_limit` (e.g. 256M) if your host allows.

5. **Proxy timeout**  
   If your site is behind Cloudflare or another proxy, 502 can be due to the proxy’s timeout (often 100s). Use a Manufacturer filter to reduce catalog size.

## Skip images during import (API and CSV)

If **Start API Import** or CSV import still hits 502 even with small batches, the main cost is **downloading product images** from Lipsey's (one HTTP request per product). Use:

- **API tab:** Check **"Skip image download during import"** (recommended). Products are imported without thumbnails. Then use **"Attach images to products missing thumbnails"** on the same tab. It runs in small batches (default **3** per request; you can set 1–15). **If you get HTTP 502 on Attach images:** keep batch size **1** (required on EasyWP). Click **"Attach images to products missing thumbnails"** again to continue from where it stopped; leave the tab open until remaining is 0. EasyWP’s gateway often times out at ~15–20s; batch 1 and short time limits are set to return before that.
- **CSV tab:** Check **"Skip image download"** for the same reason; use the API tab’s **"Attach images"** afterward, since the plugin stores the image name on each product for that purpose.

## Attach images: constant timeouts (e.g. 3530 remaining, batch 1)

If **Attach images** still returns **HTTP 502** even with **batch size 1** (status stays at “Attached this batch: 0 — remaining: 3530” or the request never returns):

1. **Run attach on the server (no gateway):** If you have SSH/WP-CLI on the host, run:  
   `wp lipseys attach-images`  
   (optional: `--batch=5` or `--batches=100`). This avoids the web gateway timeout entirely.
2. **Leave tab open:** The UI auto-continues; don’t close the tab. If each request times out, the gateway is likely under 60s — use WP-CLI above.
3. **Reset failed and retry:** Use **“Reset failed attempts”** then run Attach again (or WP-CLI) to retry products that were skipped due to timeout/404.

## Summary

- **502 = request took too long or PHP died before sending a response.**
- **Fix:** Use **Fetch catalog** first; if that 502s, add a **Manufacturer** filter (e.g. Glock) and try again. Then run **Start API Import** with **Skip image download** checked. Use **Attach images** afterward with **batch size 1** (required on EasyWP); if you get 502, click the button again to continue from where it stopped and leave the tab open until remaining is 0.
