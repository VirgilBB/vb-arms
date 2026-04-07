# VB Arms — AI-Orchestrated Firearms E-Commerce

A WooCommerce-based storefront built with custom WordPress plugins that automate multi-distributor API routing, order fulfillment, and real-time inventory sync for a licensed firearms retailer.

## What This Is

VB Arms is a full-stack e-commerce platform where the fulfillment logic is driven by live distributor APIs rather than manual inventory management. Orders route automatically based on real-time stock telemetry from two wholesale distributors (Lipsey's and Zanders).

## Architecture

```
Customer Order
      │
      ▼
 WooCommerce
      │
      ├── Lipsey's Import Plugin ──► Lipsey's API (catalog, pricing, order submit)
      │         └── Proxy Server (IP-whitelisted relay for EasyWP compatibility)
      │
      └── Zanders Import Plugin ──► Zanders FTP/API (CSV + XML catalog feed)
```

## Plugins

### `lipseys-import-plugin/`
Custom WooCommerce plugin for full Lipsey's API integration.

- Full catalog import (50,000+ SKUs) with manufacturer/type/in-stock filters
- Real-time pricing and inventory sync ("Update Pricing Only" mode)
- Automatic order submission to Lipsey's API on WooCommerce payment
- Batch image attachment with 502-safe retry logic for shared hosting
- Proxy-aware HTTP client (routes through an IP-whitelisted relay server)
- Admin UI with progress bars, live status, and connection testing

### `zanders-import-plugin/`
Custom WooCommerce plugin for Zanders Sporting Goods catalog integration.

- CSV and XML catalog processing with category auto-mapping
- FTP handler with SSL support and EasyWP-compatible cURL fallback
- Product importer with deduplication, image handling, and batch processing
- Admin interface with import controls and status reporting

### `lipseys-proxy/`
Lightweight PHP proxy that runs on a dedicated VPS to relay Lipsey's API requests. Required because shared WordPress hosts don't have whitelisted IPs.

### `vb-arms-age-verification/`
Custom age verification gate plugin (21+ confirmation) with cookie persistence and ad-blocker-aware fallback rendering.

## Theme

### `storefront-child/`
Child theme built on WooCommerce Storefront with custom page templates:

- White Glove service page (curated builds, RFQ flow)
- Custom product archive, single product, cart, and checkout templates
- Age verification integration
- Crypto payment support page

## Stack

- WordPress + WooCommerce
- PHP 8.x
- Lipsey's REST API
- Zanders FTP/XML feed
- EasyWP (shared hosting) + Hetzner VPS (proxy)

## Status

Live at [vb-arms.com](https://vb-arms.com) — operational since December 2025.
