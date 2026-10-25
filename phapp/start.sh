#!/bin/bash

set -e

# www-data can do anything under /var/www
chown -R www-data:www-data /var/www
chmod -R 777 /var/www

# MariaDB can read/write everything under /var/www
# (777 gives mysql write access regardless of ownership)

# Start MariaDB
service mariadb start

# Create database and database user
mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS database;

CREATE USER IF NOT EXISTS 'dbadmin'@'localhost'
IDENTIFIED BY 'StrongPassword123!';

ALTER USER 'dbadmin'@'localhost'
IDENTIFIED BY 'StrongPassword123!';

GRANT ALL PRIVILEGES ON *.* TO 'dbadmin'@'localhost' WITH GRANT OPTION;

FLUSH PRIVILEGES;
EOF

# Import schema/data
mysql -u root database < /var/www/html/database.sql

# Start Apache
apache2-foreground
