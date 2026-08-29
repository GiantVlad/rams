#!/bin/bash
set -e

echo "Pulling latest code..."
cd ~/rams
if [ -d "docker/nginx.conf" ]; then
  echo "Removing docker/nginx.conf directory created by docker..."
  sudo rm -rf docker/nginx.conf
fi
git pull origin master

echo "Building frontend using Docker..."
cd web
docker run --rm -v $(pwd):/app -w /app node:20 sh -c "npm install && npm run build"
cd ..

echo "Starting Docker containers..."
docker compose down
docker compose build
docker compose up -d

echo "Configuring Nginx..."
sudo bash -c 'cat > /etc/nginx/sites-available/rams.cloud-workflow.com << "EOF"
server {
    listen 80;
    server_name rams.cloud-workflow.com;

    root /home/vlad/rams/web/dist;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /api/ {
        proxy_pass http://127.0.0.1:8002;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /broadcasting/ {
        proxy_pass http://127.0.0.1:8002;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /ws {
        proxy_pass http://127.0.0.1:8087;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
    }
}
EOF'

sudo ln -sf /etc/nginx/sites-available/rams.cloud-workflow.com /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

echo "Deployment finished successfully!"