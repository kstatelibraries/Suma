#!/bin/bash
set -e

# Paths
SUMA_DIR="/app/suma-session-manager"

# Optional htpasswd setup if ENABLE_HTPASSWD is true
if [ -z "$SUMA_ADMIN_USER" ] || [ -z "$SUMA_ADMIN_PASS" ]; then
  echo "ENABLE_HTPASSWD is true, but SUMA_ADMIN_USER or SUMA_ADMIN_PASS is not set."
  exit 1
fi

echo "Creating bcrypt htpasswd file at /app/suma.htpasswd"
htpasswd -Bbc /app/suma.htpasswd "$SUMA_ADMIN_USER" "$SUMA_ADMIN_PASS"
chown www-data:www-data /app/suma.htpasswd

# Generate Apache config with environment variables
cat > /etc/apache2/sites-available/000-default.conf <<EOF
<VirtualHost *:80>
    DocumentRoot /var/www/html

    Alias /suma-session-manager ${SUMA_DIR}

    <Location "/suma-session-manager">
        Options -Indexes
        AllowOverride All
        RewriteEngine On
        RewriteRule ^.*$ - [NC,L]
        RewriteRule ^.*$ index.php [NC,L]

        AuthType Basic
        AuthName "Suma Login"
        AuthUserFile /app/suma.htpasswd
        Require valid-user
    </Location>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# Generate Suma config.php from env vars if not exists
CONFIG_PATH="${SUMA_DIR}/config.php"
if [ ! -f "$CONFIG_PATH" ]; then
  echo "Generating Suma Session Manager config.php..."
  cat > "$CONFIG_PATH" <<EOC
<?php
define ("DEBUG", false);
define ("SUMASERVER_URL", "${SERVICE_URL_SUMA_SESSION}/sumaserver");
define ("SUMA_REPORTS_URL", "${SERVICE_URL_SUMA_SESSION}/analysis/reports");
define ("MYSQL_HOST", "${DB_HOST}");
define ("MYSQL_PORT", "${DB_PORT}");
define ("MYSQL_DATABASE", "${DB_NAME}");
define ("MYSQL_USER", "${DB_USER}");
define ("MYSQL_PASSWORD", "${DB_PASS}");

\$ui_theme = "pepper-grinder";
\$default_init   = "";
\$entries_per_page = 100;
\$prevent_datepicker_future = true;
\$one_per_hour_inits = array();

\$adjust_time_options = array (
                                "-4 hrs"  => "subtime 04:00:00",
                                "-3 hrs"  => "subtime 03:00:00",
                                "-2 hrs"  => "subtime 02:00:00",
                                "-60 min" => "subtime 01:00:00",
                                "-30 min" => "subtime 00:30:00",
                                "-15 min" => "subtime 00:15:00",
                                "-10 min" => "subtime 00:10:00",
                                "-5 min"  => "subtime 00:05:00",
                                "+5 min"  => "addtime 00:05:00",
                                "+10 min" => "addtime 00:10:00",
                                "+15 min" => "addtime 00:15:00",
                                "+30 min" => "addtime 00:30:00",
                                "+60 min" => "addtime 01:00:00"
                                );
EOC
fi

# Chown of files for webserver
chown www-data:www-data ${SUMA_CLIENT_CONFIG_PATH} ${DB_CONFIG_PATH} ${CONFIG_PATH}

# Wait for MySQL to be ready before starting Apache
if [ -n "$DB_HOST" ]; then
  echo "Waiting for MySQL at $DB_HOST:$DB_PORT..."
  for i in {1..30}; do
    if mysqladmin ping -h"$DB_HOST" -P"${DB_PORT:-3306}" --silent; then
      echo "MySQL is up!"
      break
    fi
    echo "Waiting for MySQL... retry $i/30"
    sleep 2
  done
fi

exec "$@"
