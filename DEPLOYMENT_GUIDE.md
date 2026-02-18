# Production Deployment Guide

## FusionPBX Laravel 12 + Filament 4 Deployment

Complete guide for deploying to production servers.

---

## 📋 Prerequisites

### System Requirements

- **Operating System**: Ubuntu 22.04 LTS or Debian 12
- **PHP**: 8.3 or higher
- **Database**: PostgreSQL 14+ or MySQL 8.0+
- **Redis**: 7.0+
- **Node.js**: 20.x LTS (optional, for asset compilation)
- **FreeSWITCH**: 1.10.x

### Server Specs (Minimum)

- **CPU**: 4 cores
- **RAM**: 8GB
- **Storage**: 50GB SSD
- **Network**: 100Mbps

---

## 🔧 Installation Steps

### 1. System Preparation

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y \
    php8.3 php8.3-fpm php8.3-cli php8.3-common \
    php8.3-pgsql php8.3-redis php8.3-xml php8.3-curl \
    php8.3-mbstring php8.3-zip php8.3-bcmath php8.3-intl \
    postgresql postgresql-contrib redis-server \
    nginx supervisor git curl unzip

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Database Setup

```bash
# PostgreSQL
sudo -u postgres psql
```

```sql
CREATE DATABASE fusionpbx;
CREATE USER fusionpbx WITH ENCRYPTED PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE fusionpbx TO fusionpbx;
\q
```

### 3. Clone and Configure

```bash
# Clone repository
cd /var/www
sudo git clone https://github.com/your-org/fusionpbx.git
cd fusionpbx

# Set permissions
sudo chown -R www-data:www-data /var/www/fusionpbx
sudo chmod -R 755 /var/www/fusionpbx
sudo chmod -R 775 /var/www/fusionpbx/storage
sudo chmod -R 775 /var/www/fusionpbx/bootstrap/cache

# Install dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader

# Environment configuration
sudo -u www-data cp .env.example .env
sudo -u www-data php artisan key:generate
```

### 4. Configure Environment

Edit `/var/www/fusionpbx/.env`:

```env
APP_NAME="FusionPBX"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=fusionpbx
DB_USERNAME=fusionpbx
DB_PASSWORD=secure_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# FreeSWITCH ESL
ESL_HOST=127.0.0.1
ESL_PORT=8021
ESL_PASSWORD=ClueCon

# WebRTC
WEBRTC_ENABLED=true
WEBRTC_WSS_URL=wss://your-domain.com:7443
SIP_DOMAIN=your-domain.com

# Laravel Reverb
BROADCAST_DRIVER=reverb
REVERB_APP_ID=fusionpbx
REVERB_APP_KEY=generate_random_key
REVERB_APP_SECRET=generate_random_secret
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=https
```

### 5. Optimize Application

```bash
cd /var/www/fusionpbx

# Cache configuration
sudo -u www-data php artisan config:cache

# Cache routes
sudo -u www-data php artisan route:cache

# Cache views
sudo -u www-data php artisan view:cache

# Create storage link
sudo -u www-data php artisan storage:link
```

---

## 🌐 Nginx Configuration

### Main Site Configuration

Create `/etc/nginx/sites-available/fusionpbx`:

```nginx
# HTTP to HTTPS redirect
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://$server_name$request_uri;
}

# HTTPS Configuration
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com www.your-domain.com;
    root /var/www/fusionpbx/public;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    ssl_session_timeout 1d;
    ssl_session_cache shared:SSL:50m;
    ssl_session_tickets off;

    # Modern SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # HSTS (optional)
    add_header Strict-Transport-Security "max-age=63072000" always;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    index index.php index.html;
    charset utf-8;

    # Logging
    access_log /var/log/nginx/fusionpbx-access.log;
    error_log /var/log/nginx/fusionpbx-error.log;

    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
    }

    # Deny access to hidden files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Static file caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}

# WebSocket Server (Laravel Reverb)
server {
    listen 8080 ssl http2;
    listen [::]:8080 ssl http2;
    server_name your-domain.com;

    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/fusionpbx /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 🔒 SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Auto-renewal (already set up by default)
sudo certbot renew --dry-run
```

---

## 🔄 Process Management (Supervisor)

### Configure Supervisor

Create `/etc/supervisor/conf.d/fusionpbx.conf`:

```ini
[program:fusionpbx-reverb]
command=php /var/www/fusionpbx/artisan reverb:start
directory=/var/www/fusionpbx
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/www/fusionpbx/storage/logs/reverb.log
stopwaitsecs=3600

[program:fusionpbx-cdr-monitor]
command=php /var/www/fusionpbx/artisan cdr:live-monitor
directory=/var/www/fusionpbx
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/www/fusionpbx/storage/logs/cdr-monitor.log
stopwaitsecs=3600

[program:fusionpbx-queue-worker]
command=php /var/www/fusionpbx/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
directory=/var/www/fusionpbx
user=www-data
autostart=true
autorestart=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/fusionpbx/storage/logs/queue-worker.log
stopwaitsecs=3600
```

Start services:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
sudo supervisorctl status
```

---

## ⚙️ PHP-FPM Optimization

Edit `/etc/php/8.3/fpm/pool.d/www.conf`:

```ini
[www]
user = www-data
group = www-data
listen = /var/run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500

php_admin_value[memory_limit] = 256M
php_admin_value[upload_max_filesize] = 100M
php_admin_value[post_max_size] = 100M
php_admin_value[max_execution_time] = 300
```

Restart PHP-FPM:

```bash
sudo systemctl restart php8.3-fpm
```

---

## 🗄️ Database Optimization

### PostgreSQL

Edit `/etc/postgresql/14/main/postgresql.conf`:

```ini
shared_buffers = 2GB
effective_cache_size = 6GB
maintenance_work_mem = 512MB
checkpoint_completion_target = 0.9
wal_buffers = 16MB
default_statistics_target = 100
random_page_cost = 1.1
effective_io_concurrency = 200
work_mem = 10485kB
min_wal_size = 1GB
max_wal_size = 4GB
max_connections = 200
```

Restart PostgreSQL:

```bash
sudo systemctl restart postgresql
```

---

## 📊 Monitoring

### Application Monitoring

Install monitoring tools:

```bash
# Install node exporter (for Prometheus)
wget https://github.com/prometheus/node_exporter/releases/download/v1.7.0/node_exporter-1.7.0.linux-amd64.tar.gz
tar xvfz node_exporter-*.tar.gz
sudo mv node_exporter-*/node_exporter /usr/local/bin/
sudo useradd -rs /bin/false node_exporter

# Create systemd service
sudo tee /etc/systemd/system/node_exporter.service <<EOF
[Unit]
Description=Node Exporter
After=network.target

[Service]
User=node_exporter
Group=node_exporter
Type=simple
ExecStart=/usr/local/bin/node_exporter

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl daemon-reload
sudo systemctl start node_exporter
sudo systemctl enable node_exporter
```

### Log Rotation

Create `/etc/logrotate.d/fusionpbx`:

```
/var/www/fusionpbx/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

---

## 🔐 Security Hardening

### Firewall (UFW)

```bash
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw allow 8080/tcp  # WebSocket
sudo ufw allow 5060/tcp  # SIP
sudo ufw allow 5060/udp  # SIP
sudo ufw allow 16384:32768/udp  # RTP
sudo ufw enable
```

### Fail2Ban

```bash
sudo apt install -y fail2ban

# Create jail for Nginx
sudo tee /etc/fail2ban/jail.d/nginx.conf <<EOF
[nginx-http-auth]
enabled = true

[nginx-noscript]
enabled = true

[nginx-badbots]
enabled = true

[nginx-noproxy]
enabled = true
EOF

sudo systemctl restart fail2ban
```

---

## 🚀 Deployment Automation

### Deployment Script

Create `/var/www/deploy.sh`:

```bash
#!/bin/bash
set -e

echo "🚀 Deploying FusionPBX..."

cd /var/www/fusionpbx

# Pull latest code
sudo -u www-data git pull origin main

# Install dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader

# Clear caches
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear

# Optimize
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Restart services
sudo supervisorctl restart all
sudo systemctl reload php8.3-fpm
sudo systemctl reload nginx

echo "✅ Deployment complete!"
```

Make executable:

```bash
sudo chmod +x /var/www/deploy.sh
```

---

## 📈 Performance Testing

### Load Testing

```bash
# Install Apache Bench
sudo apt install -y apache2-utils

# Test performance
ab -n 1000 -c 10 https://your-domain.com/

# Install wrk for more advanced testing
sudo apt install -y wrk

# Run load test
wrk -t12 -c400 -d30s https://your-domain.com/
```

---

## 🐛 Troubleshooting

### Check Logs

```bash
# Application logs
tail -f /var/www/fusionpbx/storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/fusionpbx-error.log

# PHP-FPM logs
tail -f /var/log/php8.3-fpm.log

# Supervisor logs
tail -f /var/www/fusionpbx/storage/logs/reverb.log
tail -f /var/www/fusionpbx/storage/logs/cdr-monitor.log
```

### Common Issues

1. **Permission errors**:
```bash
sudo chown -R www-data:www-data /var/www/fusionpbx
sudo chmod -R 775 /var/www/fusionpbx/storage
```

2. **Cache issues**:
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

3. **Database connection**:
```bash
php artisan tinker
DB::connection()->getPdo();
```

---

## ✅ Post-Deployment Checklist

- [ ] SSL certificate installed and working
- [ ] All supervisor processes running
- [ ] Database connection working
- [ ] Redis connection working
- [ ] ESL connection to FreeSWITCH working
- [ ] WebSocket server running (Reverb)
- [ ] Live CDR monitoring working
- [ ] WebRTC dialer functional
- [ ] Filament admin panel accessible
- [ ] Firewall configured
- [ ] Fail2Ban configured
- [ ] Log rotation configured
- [ ] Backup system configured
- [ ] Monitoring tools installed

---

## 🎉 Complete!

Your FusionPBX Laravel 12 + Filament 4 system is now deployed and ready for production use!

For support, visit: https://forum.fusionpbx.com
