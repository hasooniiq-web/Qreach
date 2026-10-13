#!/usr/bin/env bash
# Hardens the Qreach server against flooding. Run as root on the server, from the
# folder that holds nginx/ (the repo's deploy/ directory). Safe to run again.
#
#   - nginx: request/connection limits, slow-client timeouts, no version banner
#   - php-fpm: a request that runs too long is killed; workers are recycled
#   - ufw: SSH is rate limited (6 connections / 30 s per address)
#   - fail2ban: bans addresses that keep hitting the limits or fail SSH
#
# Every file it changes is copied to /root/backups/hardening-<time>/ first, and the
# nginx change is only kept if `nginx -t` accepts it.
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SITE=/etc/nginx/sites-available/qreach
STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP="/root/backups/hardening-$STAMP"
mkdir -p "$BACKUP"

backup() { [ -e "$1" ] && cp -a "$1" "$BACKUP/$(echo "$1" | tr '/' '_')"; return 0; }

echo "== nginx"
backup /etc/nginx/nginx.conf
backup "$SITE"
backup /etc/nginx/conf.d/qreach-limits.conf
backup /etc/nginx/snippets/qreach-hardening.conf

install -m 644 "$HERE/nginx/qreach-limits.conf" /etc/nginx/conf.d/qreach-limits.conf
install -m 644 "$HERE/nginx/qreach-hardening.conf" /etc/nginx/snippets/qreach-hardening.conf

# No version banner.
sed -i -E 's/^(\s*)server_tokens\s+build;.*/\1server_tokens off;/' /etc/nginx/nginx.conf

# The site includes the snippet once, right after its body-size line.
if ! grep -q "qreach-hardening.conf" "$SITE"; then
    sed -i '0,/client_max_body_size 12M;/s//client_max_body_size 12M;\n    include snippets\/qreach-hardening.conf;/' "$SITE"
fi

# A request that PHP has not answered in 60 s is given up on.
if ! grep -q "fastcgi_read_timeout" "$SITE"; then
    sed -i 's#fastcgi_hide_header X-Powered-By;#fastcgi_hide_header X-Powered-By;\n        fastcgi_read_timeout 60s;\n        fastcgi_send_timeout 60s;#' "$SITE"
fi

if nginx -t; then
    systemctl reload nginx
    echo "nginx reloaded"
else
    echo "nginx rejected the change, restoring" >&2
    cp -a "$BACKUP/$(echo /etc/nginx/nginx.conf | tr '/' '_')" /etc/nginx/nginx.conf
    cp -a "$BACKUP/$(echo "$SITE" | tr '/' '_')" "$SITE"
    rm -f /etc/nginx/conf.d/qreach-limits.conf /etc/nginx/snippets/qreach-hardening.conf
    nginx -t && systemctl reload nginx
    exit 1
fi

echo "== php-fpm"
POOL=/etc/php/8.5/fpm/pool.d/www.conf
backup "$POOL"
sed -i -E 's/^;?\s*pm\.max_requests\s*=.*/pm.max_requests = 500/' "$POOL"
sed -i -E 's/^;?\s*request_terminate_timeout\s*=.*/request_terminate_timeout = 60s/' "$POOL"
php-fpm8.5 -t && systemctl reload php8.5-fpm

echo "== ufw"
ufw limit 22/tcp >/dev/null
ufw status | head -12

echo "== fail2ban"
if ! command -v fail2ban-client >/dev/null; then
    DEBIAN_FRONTEND=noninteractive apt-get install -y -qq fail2ban
fi
cat > /etc/fail2ban/jail.d/qreach.local <<'JAIL'
[DEFAULT]
bantime  = 1h
findtime = 10m
backend  = systemd

[sshd]
enabled  = true
maxretry = 5

# Addresses that keep running into the nginx request limits (a script, not a person).
[nginx-limit-req]
enabled  = true
backend  = auto
logpath  = /var/log/nginx/error.log
maxretry = 120
findtime = 10m
bantime  = 1h
JAIL
systemctl enable --now fail2ban >/dev/null 2>&1 || true
systemctl restart fail2ban
sleep 2
fail2ban-client status | head -5

echo "done. Backups: $BACKUP"
