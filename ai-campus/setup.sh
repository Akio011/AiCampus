#!/bin/bash

echo "=== AI Campus Setup ==="

# Install Docker if not present
if ! command -v docker &> /dev/null; then
    echo "Installing Docker..."
    sudo apt update
    sudo apt install -y docker.io docker-compose-plugin
    sudo usermod -aG docker $USER
    newgrp docker
else
    echo "Docker already installed: $(docker --version)"
fi

# Create google.php if missing
if [ ! -f config/google.php ]; then
    echo "Creating config/google.php..."
    cat > config/google.php << 'EOF'
<?php
$_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host   = $_SERVER['HTTP_HOST'] ?? '192.168.4.4:8000';
define('GOOGLE_CLIENT_ID',     'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI',  $_scheme . '://' . $_host . '/auth/callback.php');
define('GOOGLE_AUTH_URL',  'https://accounts.google.com/o/oauth2/v2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USER_URL',  'https://www.googleapis.com/oauth2/v3/userinfo');
EOF
    echo "config/google.php created. Edit it with your real credentials:"
    echo "  nano config/google.php"
    echo "Then run: docker compose up -d"
    exit 0
fi

# Start containers
echo "Starting Docker containers..."
docker compose up -d

echo ""
echo "=== Done ==="
docker compose ps
echo ""
echo "App running at: http://192.168.4.4:8000"
