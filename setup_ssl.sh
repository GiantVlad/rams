#!/bin/bash
set -e

DOMAIN="rams.cloud-workflow.com"

# 1. Stop nginx on host to free port 80 for certbot standalone
echo "Stopping Nginx..."
sudo systemctl stop nginx

# 2. Run certbot via docker standalone to get certificate
echo "Running Certbot..."
sudo docker run --rm -p 80:80 \
  -v /home/vlad/smartd/certbot/conf:/etc/letsencrypt \
  -v /home/vlad/smartd/certbot/www:/var/www/certbot \
  certbot/certbot certonly \
  --standalone \
  --non-interactive \
  --agree-tos \
  --register-unsafely-without-email \
  -d $DOMAIN

# 3. Create new Nginx config for rams with SSL in conf.d
echo "Creating Nginx configuration..."
sudo bash -c "cat > /etc/nginx/conf.d/rams.conf << 'EOF'
server {
    listen 80;
    server_name rams.cloud-workflow.com;

    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl;
    server_name rams.cloud-workflow.com;

    ssl_certificate /home/vlad/smartd/certbot/conf/live/rams.cloud-workflow.com/fullchain.pem;
    ssl_certificate_key /home/vlad/smartd/certbot/conf/live/rams.cloud-workflow.com/privkey.pem;

    root /home/vlad/rams/web/dist;
    index index.html;

    location / {
        try_files \$uri \$uri/ /index.html;
    }

    location /api/ {
        proxy_pass http://127.0.0.1:8002;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }

    location /broadcasting/ {
        proxy_pass http://127.0.0.1:8002;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }

    location /ws {
        proxy_pass http://127.0.0.1:8087;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection \"Upgrade\";
        proxy_set_header Host \$host;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }
}
EOF"

# Remove the old HTTP-only site configuration
echo "Removing old configurations..."
if [ -f "/etc/nginx/sites-enabled/rams.cloud-workflow.com" ]; then
    sudo rm /etc/nginx/sites-enabled/rams.cloud-workflow.com
fi
if [ -f "/etc/nginx/sites-available/rams.cloud-workflow.com" ]; then
    sudo rm /etc/nginx/sites-available/rams.cloud-workflow.com
fi

echo "Testing Nginx configuration and starting Nginx..."
sudo nginx -t
sudo systemctl start nginx

echo "SSL and Nginx setup complete for $DOMAIN."
