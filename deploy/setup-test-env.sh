#!/usr/bin/env bash
# Creates / updates the TEST environment (test.qreach.online) next to the real one.
#
#   real:  /var/www/qreach        database qreach        https://qreach.online
#   test:  /var/www/qreach-test   database qreach_test   https://test.qreach.online
#
# Run as root on the server AFTER the backend code has been shipped to
# /var/www/qreach-test (git archive HEAD:backend | ssh ... tar -x -C /var/www/qreach-test)
# and public/build copied beside it. Safe to run again: every step checks first.
#
# What the test site is:
#   - its own database and its own APP_KEY (nothing is shared with the real site)
#   - the real site's roles, permissions and dashboard admins are COPIED once, so you sign in
#     with the same email and password (the authenticator is enrolled again, as a new entry)
#   - made-up customers, vehicles, tags, vouchers and messages (DemoDataSeeder)
#   - a yellow "test environment" ribbon, noindex, no reCAPTCHA, no push, no SMS, sandbox payments
set -euo pipefail

DOMAIN="${TEST_DOMAIN:-test.qreach.online}"
PROD=/var/www/qreach
TEST=/var/www/qreach-test
DB=qreach_test
DBUSER=qreach_test
SERVER_IP="${SERVER_IP:-$(curl -4 -s --max-time 8 https://ifconfig.me || ip -4 addr show scope global | awk '/inet /{print $2}' | cut -d/ -f1 | head -1)}"

[ -f "$TEST/artisan" ] || { echo "Ship the backend code to $TEST first." >&2; exit 1; }
[ -f "$PROD/.env" ] || { echo "$PROD/.env not found." >&2; exit 1; }

env_get() { grep -E "^$2=" "$1" | head -1 | cut -d= -f2- | sed -E 's/^"(.*)"$/\1/'; }
env_set() { # file key value
    if grep -qE "^$2=" "$1"; then
        sed -i -E "s|^$2=.*|$2=$3|" "$1"
    else
        printf '%s=%s\n' "$2" "$3" >> "$1"
    fi
}
as_www() { sudo -u www-data bash -c "cd $TEST && $*"; }

echo "== database"
if ! sudo -u postgres psql -Atc "select 1 from pg_roles where rolname='$DBUSER'" | grep -q 1; then
    DBPASS="$(openssl rand -hex 20)"
    sudo -u postgres psql -qc "create role $DBUSER login password '$DBPASS'"
    umask 077; printf '%s' "$DBPASS" > /root/.qreach_test_dbpass
fi
DBPASS="$(cat /root/.qreach_test_dbpass)"
if ! sudo -u postgres psql -Atc "select 1 from pg_database where datname='$DB'" | grep -q 1; then
    sudo -u postgres createdb -O "$DBUSER" "$DB"
fi

echo "== .env"
FRESH_ENV=0
if [ ! -f "$TEST/.env" ]; then
    FRESH_ENV=1
    cp "$PROD/.env" "$TEST/.env"
    env_set "$TEST/.env" APP_NAME '"Qreach Test"'
    env_set "$TEST/.env" APP_URL "https://$DOMAIN"
    env_set "$TEST/.env" DB_DATABASE "$DB"
    env_set "$TEST/.env" DB_USERNAME "$DBUSER"
    env_set "$TEST/.env" DB_PASSWORD "$DBPASS"
    env_set "$TEST/.env" APP_TEST_BANNER true
    env_set "$TEST/.env" SESSION_COOKIE qreach_test_session
    env_set "$TEST/.env" CACHE_PREFIX qreach_test_
    # The real site's reCAPTCHA key is bound to its own domain: off here (the login still has the lockout).
    env_set "$TEST/.env" RECAPTCHA_SITE_KEY ""
    env_set "$TEST/.env" RECAPTCHA_SECRET_KEY ""
    env_set "$TEST/.env" DEV_OTP_INBOX true
    env_set "$TEST/.env" MAIL_MAILER log
fi
chown www-data:www-data "$TEST/.env"; chmod 640 "$TEST/.env"

echo "== files"
[ -d "$TEST/vendor" ] || cp -a "$PROD/vendor" "$TEST/vendor"
mkdir -p "$TEST/storage/framework/"{cache,sessions,views} "$TEST/storage/logs" "$TEST/storage/app/public" "$TEST/bootstrap/cache"
chown -R www-data:www-data "$TEST"
chmod -R 775 "$TEST/storage" "$TEST/bootstrap/cache"
if [ "$FRESH_ENV" = 1 ]; then
    as_www "php artisan key:generate --force --no-interaction"
fi
as_www "php artisan storage:link --force >/dev/null 2>&1 || true"

echo "== migrate"
as_www "php artisan migrate --force --no-interaction"

echo "== roles, admins, plans and settings from the real site (once)"
if [ "$(sudo -u postgres psql -d "$DB" -Atc 'select count(*) from admins')" = "0" ]; then
    # Replace what the migrations seeded, then load the real site's rows.
    sudo -u postgres psql -d "$DB" -q -c "truncate table admin_role, role_permission, admins, roles, permissions, subscription_plans, system_settings restart identity cascade"
    sudo -u postgres pg_dump -d qreach --data-only --column-inserts --disable-triggers \
        -t roles -t permissions -t role_permission -t admin_role -t admins -t subscription_plans -t system_settings \
        | sudo -u postgres psql -d "$DB" -q -v ON_ERROR_STOP=1 >/dev/null
    # Same email and password; the authenticator is enrolled again on first sign-in.
    sudo -u postgres psql -d "$DB" -q -c "update admins set totp_secret = null, totp_enabled = false, failed_login_attempts = 0, locked_until = null, remember_token = null"
fi

echo "== sandbox payments (no real money in the test site)"
cat > /tmp/qreach-test-gateway.php <<'PHP'
<?php
chdir('/var/www/qreach-test');
require '/var/www/qreach-test/vendor/autoload.php';
$app = require '/var/www/qreach-test/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
App\Models\PaymentGateway::updateOrCreate(['driver' => 'sandbox'], [
    'name' => 'Sandbox (test)', 'is_enabled' => true, 'is_test_mode' => true,
    'credentials' => ['signing_secret' => bin2hex(random_bytes(16))],
]);
echo "sandbox gateway ready\n";
PHP
chmod 644 /tmp/qreach-test-gateway.php
sudo -u www-data php /tmp/qreach-test-gateway.php || true
rm -f /tmp/qreach-test-gateway.php

echo "== made-up data (once)"
if [ "$(sudo -u postgres psql -d "$DB" -Atc 'select count(*) from users')" = "0" ]; then
    as_www "php artisan db:seed --class=DemoDataSeeder --force --no-interaction"
fi

echo "== caches"
as_www "php artisan optimize:clear >/dev/null"
as_www "php artisan config:cache >/dev/null && php artisan route:cache >/dev/null && php artisan view:cache >/dev/null"

echo "== queue worker and scheduler"
if [ ! -f /etc/systemd/system/qreach-test-queue.service ]; then
    sed -e "s#/var/www/qreach#$TEST#g" -e 's#Qreach queue worker#Qreach TEST queue worker#' \
        /etc/systemd/system/qreach-queue.service > /etc/systemd/system/qreach-test-queue.service
    systemctl daemon-reload
fi
systemctl enable --now qreach-test-queue >/dev/null 2>&1 || true
systemctl restart qreach-test-queue
echo "* * * * * www-data cd $TEST && /usr/bin/php artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/qreach-test-scheduler
chmod 644 /etc/cron.d/qreach-test-scheduler

echo "== nginx"
SITE=/etc/nginx/sites-available/qreach-test
if [ ! -f "$SITE" ]; then
cat > "$SITE" <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN;
    root $TEST/public;
    index index.php;
    charset utf-8;
    client_max_body_size 12M;
    include snippets/qreach-hardening.conf;

    # The test site must never be indexed.
    add_header X-Robots-Tag "noindex, nofollow" always;

    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location = /favicon.ico { access_log off; log_not_found off; }
    location ~ \.php\$ {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 60s;
        fastcgi_send_timeout 60s;
    }
    location ~ /\.(?!well-known).* { deny all; }
}
NGINX
    ln -sf "$SITE" /etc/nginx/sites-enabled/qreach-test
fi
nginx -t
systemctl reload nginx
systemctl reload php8.5-fpm

echo "== certificate"
RESOLVED="$(dig +short "$DOMAIN" @8.8.8.8 | tail -1)"
if [ -d "/etc/letsencrypt/live/$DOMAIN" ]; then
    echo "certificate already issued"
elif [ -n "$SERVER_IP" ] && [ "$RESOLVED" = "$SERVER_IP" ]; then
    certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos --redirect --no-eff-email \
        --register-unsafely-without-email || certbot --nginx -d "$DOMAIN" --non-interactive --redirect
else
    echo "DNS for $DOMAIN does not point to this server yet (got '$RESOLVED', server is '$SERVER_IP')."
    echo "Fix the A record, then run this script again to get the certificate."
fi

echo "done."
