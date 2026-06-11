# Lipsey's API proxy

A single-file PHP relay for the Lipsey's distributor API. Run it on a server whose IP is whitelisted by Lipsey's. WordPress sends API requests here; the proxy forwards them to `api.lipseys.com`. Lipsey's sees the proxy server's IP, so the request is authorized even when your WordPress host's outbound IP can't be whitelisted (common with managed hosts).

## Setup

1. **Upload** `lipseys-proxy.php` to the server with the whitelisted IP.
2. **Edit** the config at the top of `lipseys-proxy.php`:
   - `LIPSEYS_PROXY_SECRET`: set a long random string (e.g. `openssl rand -hex 24`). Set the **same** value in **WooCommerce → Lipsey's API → Proxy secret**.
   - `LIPSEYS_PROXY_ALLOWED_IPS`: optional; comma-separated IPs allowed to call this proxy (e.g. your WordPress host's outbound IP). Leave empty to allow any caller (the secret is then the only auth).
3. In **WooCommerce → Lipsey's API**:
   - **Proxy URL**: the full URL to the uploaded file, e.g. `https://your-server.com/lipseys-proxy/lipseys-proxy.php`.
   - **Proxy secret**: same as `LIPSEYS_PROXY_SECRET`.
4. **Save** and click **Test connection**. Requests flow: WordPress → proxy → Lipsey's.

## Security recommendations

- Always set a strong `LIPSEYS_PROXY_SECRET` — without it, anyone who finds the URL can relay requests through your whitelisted IP.
- Set `LIPSEYS_PROXY_ALLOWED_IPS` to your WordPress host's outbound IP(s) when known.
- Serve the proxy over HTTPS so the secret isn't sent in cleartext.
- Run the proxy as a systemd service (or under your web server) and keep the box patched; fail2ban + a firewall limiting inbound ports to 22/80/443 are good baselines.

## Reverse tunnel (use a different machine's IP)

If Lipsey's whitelisted a machine that can't host the proxy directly (e.g. a workstation behind NAT), the server can forward requests to it over an SSH reverse tunnel:

**Flow:** WordPress → proxy server → SSH reverse tunnel → workstation:9999 → workstation runs the proxy script → Lipsey's (sees the workstation's IP).

**On the workstation:**

1. Run the proxy script locally:
   ```bash
   cd lipseys-proxy
   php -S 127.0.0.1:9999
   ```
2. Create the reverse tunnel so port 9999 on the server forwards here:
   ```bash
   ssh -i ~/.ssh/your_ssh_key -R 9999:127.0.0.1:9999 root@YOUR_PROXY_SERVER_IP -N
   ```
   Leave this session open (`-N` = no shell, just tunnel). For persistence use `autossh` or a launchd/systemd job.

**On the proxy server:**

3. Stop any local proxy service so port 9999 is free for the tunnel:
   ```bash
   systemctl stop lipseys-proxy.service
   ```

WordPress keeps the same Proxy URL; traffic now rides the tunnel. When the tunnel is down, restart the server-side service instead: `systemctl start lipseys-proxy.service`.

A helper script for the workstation side is in `start-tunnel.sh`.
