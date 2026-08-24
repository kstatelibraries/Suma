#!/bin/bash
set -e

# Paths
WEB_DIR="/var/www/html"
SUMA_DIR="/app/suma"
SESSION_MGR_DIR="${SUMA_DIR}/session-manager"
SUMA_LOG="/var/log/suma.log"
HTPASSWD_FILE="/app/suma.htpasswd"

# Build the htpasswd file used to protect the Session Manager.
# The data collection client and /sumaserver must stay unauthenticated (tablets
# cannot send Basic credentials), so this guards only /suma-session-manager.
if [ -z "$SUMA_ADMIN_USER" ] || [ -z "$SUMA_ADMIN_PASS" ]; then
  echo "SUMA_ADMIN_USER and SUMA_ADMIN_PASS must be set."
  exit 1
fi

echo "Creating bcrypt htpasswd file at ${HTPASSWD_FILE}..."
htpasswd -Bbc "${HTPASSWD_FILE}" "$SUMA_ADMIN_USER" "$SUMA_ADMIN_PASS"
chown www-data:www-data "${HTPASSWD_FILE}"

# Generate Apache config with environment variables
cat > /etc/apache2/sites-available/000-default.conf <<EOF
<VirtualHost *:80>
    DocumentRoot ${WEB_DIR}

    Alias /sumaserver ${SUMA_DIR}/service/web
    Alias /suma/client ${SUMA_DIR}/web
    Alias /suma/analysis ${SUMA_DIR}/analysis
    Alias /suma-session-manager ${SESSION_MGR_DIR}

    <Location "/suma">
        Options -Indexes +FollowSymLinks
        Require all granted
    </Location>

    <Location "/sumaserver">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} -s [OR]
        RewriteCond %{REQUEST_FILENAME} -l [OR]
        RewriteCond %{REQUEST_FILENAME} -d
        RewriteRule ^.*\$ - [NC,L]
        RewriteRule ^.*\$ index.php [NC,L]
        Require all granted
    </Location>

    <Location "/suma-session-manager">
        Options -Indexes
        AllowOverride All
        RewriteEngine On
        RewriteRule ^.*\$ - [NC,L]
        RewriteRule ^.*\$ index.php [NC,L]

        AuthType Basic
        AuthName "Suma Session Manager"
        AuthUserFile ${HTPASSWD_FILE}
        Require valid-user
    </Location>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# Enable rewrite module (already enabled in Dockerfile but safe here)
a2enmod rewrite

# Generate Suma config.yaml from env vars if not exists
CONFIG_PATH="${SUMA_DIR}/service/web/config/config.yaml"
if [ ! -f "$CONFIG_PATH" ]; then
  echo "Generating Suma config.yaml..."
  cat > "$CONFIG_PATH" <<EOC
SUMA_SERVER_PATH: $SUMA_DIR/service
SUMA_CONTROLLER_PATH: $SUMA_DIR/service/controllers
SUMA_BASE_URL: /sumaserver
SUMA_DEBUG: ${SUMA_DEBUG}
EOC
fi

# Generate Suma config.yaml from env vars if not exists
ANALYSIS_CONFIG_PATH="${SUMA_DIR}/analysis/config/config.yaml"
if [ ! -f "$ANALYSIS_CONFIG_PATH" ]; then
  echo "Generating Suma Analysis config.yaml..."
  cat > "$ANALYSIS_CONFIG_PATH" <<EOC
showErrors: false
serverIO:
    baseUrl: ${SERVICE_URL}/sumaserver/query
analysisBaseUrl: ${SERVICE_URL}/suma/analysis/reports
nightly:
    timezone: ${SUMA_ANALYTICS_TIMEZONE}
    displayFormat: ${SUMA_ANALYTICS_DISPLAY_FORMAT:-Y-m-d}
    recipients: ${SUMA_ANALYTICS_RECIPIENTS}
    errorRecipients: ${SUMA_ANALYTICS_ERROR_RECIPIENTS}
    emailFrom: "${SUMA_ANALYTICS_EMAIL_FROM}"
    emailSubj: "${SUMA_ANALYTICS_EMAIL_SUBJECT}"
EOC
fi

# Generate Suma Database config.yaml from env vars if not exists
DB_CONFIG_PATH="${SUMA_DIR}/service/config/config.yaml"
if [ ! -f "$DB_CONFIG_PATH" ]; then
  echo "Generating Suma DB config.yaml..."
  cat > "$DB_CONFIG_PATH" <<EOC
production:
    sumaserver:
        db:
            host: ${DB_HOST}
            platform: Pdo_Mysql
            dbname: ${DB_NAME}
            user: ${DB_USER}
            pword: ${DB_PASS}
            port: ${DB_PORT}
        displayTimezone: ${SUMA_ANALYTICS_TIMEZONE:-UTC}
        log:
            path:
            name: ${SUMA_LOG}
        admin:
            user: ${SUMA_ADMIN_USER}
            pass: ${SUMA_ADMIN_PASS}
    queryserver:
        db:
            limit: 10000

development:
    _extends: production
    sumaserver:
        db:
            dbname: sumadev
        log:
            path:
EOC
fi

# Generate Session Manager config.php from env vars if not exists.
# Both apps now share one host, so its URLs are plain paths rather than a
# separately configured SERVICE_URL.
SESSION_MGR_CONFIG_PATH="${SESSION_MGR_DIR}/config.php"
if [ ! -f "$SESSION_MGR_CONFIG_PATH" ]; then
  echo "Generating Suma Session Manager config.php..."
  cat > "$SESSION_MGR_CONFIG_PATH" <<EOC
<?php
define ("DEBUG", false);
define ("SUMASERVER_URL", "${SERVICE_URL}/sumaserver");
define ("SUMA_REPORTS_URL", "${SERVICE_URL}/suma/analysis/reports");
define ("MYSQL_HOST", "${DB_HOST}");
define ("MYSQL_PORT", "${DB_PORT}");
define ("MYSQL_DATABASE", "${DB_NAME}");
define ("MYSQL_USER", "${DB_USER}");
define ("MYSQL_PASSWORD", "${DB_PASS}");

// Timestamps are stored in UTC; sessions are displayed in this timezone.
define ("SUMA_DISPLAY_TIMEZONE", "${SUMA_ANALYTICS_TIMEZONE:-UTC}");

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
  chown www-data:www-data "$SESSION_MGR_CONFIG_PATH"
fi

SUMA_CLIENT_CONFIG_PATH="${SUMA_DIR}/web/config/spaceassessConfig.js"
if [ ! -f "$SUMA_CLIENT_CONFIG_PATH" ]; then
  echo "Generating Suma spaceassessConfig.js..."
  cp ${SUMA_DIR}/web/config/spaceassessConfig_example.js ${SUMA_CLIENT_CONFIG_PATH}
fi


# NOTE: Composer is deliberately not run here.
#
# Every runtime dependency is vendored and committed (service/vendor for Zend
# Framework, analysis/reports/lib/php/vendor for the reports libraries), and is
# baked into the image by the COPY in the Dockerfile. The top-level composer.json
# declares only require-dev linting tools (PHP_CodeSniffer, PHPCompatibility),
# which nothing loads at runtime.
#
# Running `composer install` on startup made the container depend on outbound
# network access every time it booted, and a DNS failure aborted the entrypoint
# (set -e), leaving the container in a restart loop.

# create suma.log file
echo "Touching suma.log..."
touch ${SUMA_LOG}
chown www-data:www-data ${SUMA_LOG}

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
