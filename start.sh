#!/bin/bash
set -e

# Render assigns dynamic PORT (defaults to 10000)
PORT=${PORT:-10000}
echo "Starting Apache on port ${PORT}..."

# Configure Apache listening port
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Reverse Proxy & Port 10000 leak prevention configuration
cat << 'EOF' > /etc/apache2/conf-available/render-proxy.conf
ServerName localhost
UseCanonicalName Off
UseCanonicalPhysicalPort Off

<Directory /var/www/html/>
    Options FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>

Alias /cdsms /var/www/html
EOF

a2enconf render-proxy > /dev/null 2>&1 || true

# Ensure uploads directory exists and is writable by www-data
mkdir -p /var/www/html/uploads
chown -R www-data:www-data /var/www/html/uploads
chmod -R 775 /var/www/html/uploads

echo "Apache configured successfully. Executing apache2-foreground..."
exec apache2-foreground
