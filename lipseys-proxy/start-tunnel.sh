#!/bin/bash
# Run this on your Mac (Terminal, not Cursor) so Lipsey's sees IP 35.134.230.192.
# Usage: ./start-tunnel.sh   (or: bash start-tunnel.sh)

set -e
cd "$(dirname "$0")/.."
PROXY_DIR="$(pwd)"

# Free port 9999 on server so tunnel can bind
echo "Freeing port 9999 on server..."
ssh -i ~/.ssh/your_ssh_key -o ConnectTimeout=5 root@YOUR_PROXY_SERVER_IP "systemctl stop lipseys-proxy.service 2>/dev/null; ss -tlnp | grep 9999 | grep -oE 'pid=[0-9]+' | cut -d= -f2 | xargs -I {} kill -9 {} 2>/dev/null; true" 2>/dev/null || true
sleep 1

# Free port 9999 on Mac
if lsof -i :9999 -t >/dev/null 2>&1; then
  echo "Stopping existing process on 9999..."
  lsof -i :9999 -t | xargs kill -9 2>/dev/null || true
  sleep 2
fi

echo "Starting PHP proxy on 127.0.0.1:9999 (from $PROXY_DIR so /lipseys-proxy/ path works)..."
php -S 127.0.0.1:9999 &
PHP_PID=$!
sleep 1

echo "Starting SSH reverse tunnel (server 9999 → Mac 9999)..."
ssh -i ~/.ssh/your_ssh_key -R 9999:127.0.0.1:9999 -o ServerAliveInterval=60 root@YOUR_PROXY_SERVER_IP -N &
SSH_PID=$!

echo ""
echo "Proxy (PID $PHP_PID) and tunnel (PID $SSH_PID) are running."
echo "In WordPress use Proxy URL: http://YOUR_PROXY_SERVER_IP/lipseys-proxy/lipseys-proxy.php"
echo "Then click Test connection. Press Ctrl+C to stop both."
trap "kill $PHP_PID $SSH_PID 2>/dev/null; exit" INT TERM
wait
