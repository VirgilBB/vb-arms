# Lipsey's API proxy

Run this on **your server whose IP is whitelisted by Lipsey's** (e.g. **35.134.230.192**). WordPress (EasyWP) sends API requests here; the proxy forwards them to `api.lipseys.com`. Lipsey's sees your server's IP, so the request is authorized.

## Setup

1. **Upload** `lipseys-proxy.php` to your server (the one with IP **35.134.230.192** or whatever Lipsey's whitelisted).
2. **Edit** the config at the top of `lipseys-proxy.php`:
   - `LIPSEYS_PROXY_SECRET`: set a random string (e.g. `openssl rand -hex 24`). Set the **same** value in **WooCommerce → Lipsey's API → Proxy secret**.
   - `LIPSEYS_PROXY_ALLOWED_IPS`: optional; comma-separated IPs allowed to call this proxy (e.g. EasyWP's outbound IP). Leave empty to allow any caller (use secret for auth).
3. In **WooCommerce → Lipsey's API**:
   - **Proxy URL**: `https://your-server.com/path/to/lipseys-proxy.php` (the full URL to this file).
   - **Proxy secret**: same as `LIPSEYS_PROXY_SECRET`.
4. **Save** and click **Test connection**. Requests will go: WordPress → your proxy → Lipsey's; Lipsey's sees your server's IP.

## Whitelisted IP (VB Arms)

Lipsey's approved: **35.134.230.192** and **https://vb-arms.com/**. The proxy must run on a server that uses that IP (or have Lipsey's whitelist the proxy server's IP).

## Server from notes (Zanders / proxy)

The Zanders proxy/VPS in the project notes is **Hetzner** at **YOUR_PROXY_SERVER_IP**:
- SSH: `ssh -i ~/.ssh/your_ssh_key root@YOUR_PROXY_SERVER_IP`
- Scripts were in `/root/zanders-sync/` (e.g. download-images.php)

If **35.134.230.192** is that server's outbound IP (or you deploy this on whatever server has that IP), upload **lipseys-proxy.php** there and set **Proxy URL** in WooCommerce → Lipsey's API to the script's URL.

## Security (proxy server YOUR_PROXY_SERVER_IP)

- **fail2ban**: Installed and enabled; **sshd** jail (4 failures → 1h ban) and **apache-auth** jail (3 failures → 1h ban). Config: `/etc/fail2ban/jail.local`.
- **UFW**: Already active; 22, 80, 443 and Akash ports allowed. PHP proxy listens only on **127.0.0.1:9999** (not exposed).
- **Proxy script**: Set **LIPSEYS_PROXY_SECRET** in `lipseys-proxy.php` and the same value in WooCommerce → Lipsey's API → Proxy secret. **(Current: guns123.)** Optionally set **LIPSEYS_PROXY_ALLOWED_IPS** to EasyWP's outbound IP (comma-separated) so only your WordPress site can call the proxy.

---

## Reverse tunnel (use your Mac IP via the proxy server)

If you only gave Lipsey's **your Mac IP (35.134.230.192)** and not the proxy server (YOUR_PROXY_SERVER_IP), you can make the proxy server forward requests to your **Mac**; the Mac then calls Lipsey's, so Lipsey's sees 35.134.230.192.

**Flow:** WordPress → YOUR_PROXY_SERVER_IP/lipseys-proxy/ → Apache → **SSH reverse tunnel** → your Mac:9999 → Mac runs proxy script → Lipsey's (sees Mac IP).

**On your Mac:**

1. **Run the proxy script locally** (same `lipseys-proxy.php`; PHP is built into macOS or install with Homebrew):
   ```bash
   cd /Users/vb/vb-arms/lipseys-proxy
   php -S 127.0.0.1:9999
   ```
   Leave this running (or run in a separate terminal / background).

2. **Create the reverse tunnel** to the proxy server so that port 9999 on the server forwards to your Mac's 9999:
   ```bash
   ssh -i ~/.ssh/your_ssh_key -R 9999:127.0.0.1:9999 root@YOUR_PROXY_SERVER_IP -N
   ```
   Leave this session open. (`-N` = no shell, just tunnel.) For a persistent tunnel you can use `autossh` or a launchd job.

**On the proxy server (YOUR_PROXY_SERVER_IP):**

3. **Stop the PHP proxy service** so port 9999 is free for the tunnel:
   ```bash
   ssh -i ~/.ssh/your_ssh_key root@YOUR_PROXY_SERVER_IP "systemctl stop lipseys-proxy.service"
   ```
   After this, traffic to YOUR_PROXY_SERVER_IP:9999 goes through the tunnel to your Mac.

**WordPress:** Keep **Proxy URL** as `http://provider.cerebro.host/lipseys-proxy/lipseys-proxy.php` (or `http://YOUR_PROXY_SERVER_IP/lipseys-proxy/lipseys-proxy.php`). **Proxy secret:** `guns123`.

**When your Mac is off or the tunnel is down**, Test connection will fail. To use the server again without the Mac, run on the server: `systemctl start lipseys-proxy.service` (and give Lipsey's the proxy server's outbound IP if you prefer that long-term).
