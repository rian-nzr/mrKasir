#!/bin/bash
# This script sets the correct permissions for Laravel on shared hosting

# Make directories readable and executable
find . -type d -exec chmod 755 {} \;

# Make files readable
find . -type f -exec chmod 644 {} \;

# Make these directories writable by the web server
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Add execute permissions to artisan
chmod 755 artisan
