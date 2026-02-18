# FusionPBX Laravel 12 - Installation Guide

## Quick Start (Development)

### 1. Install Dependencies
```bash
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Configure Database
Edit `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_DATABASE=fusionpbx
DB_USERNAME=fusionpbx
DB_PASSWORD=your_password
```

### 3. Setup Database
```bash
php artisan migrate
php artisan db:seed --class=DemoDataSeeder
```

### 4. Start Services
```bash
# Terminal 1: Web server
php artisan serve

# Terminal 2: Queue worker
php artisan horizon

# Terminal 3: WebSocket server
php artisan reverb:start
```

### 5. Access Application
- Admin Panel: http://localhost:8000/admin
- Login: admin / admin123

## Production Deployment

### 1. Server Setup
```bash
sudo apt-get install nginx postgresql redis-server supervisor
```

### 2. Deploy Application
```bash
cd /var/www
git clone https://github.com/mostakinads-design/fusionpbx.git
cd fusionpbx
composer install --no-dev --optimize-autoloader
php artisan migrate --force
```

### 3. Configure Services
```bash
# Nginx
sudo cp nginx/fusionpbx.conf /etc/nginx/sites-available/
sudo ln -s /etc/nginx/sites-available/fusionpbx /etc/nginx/sites-enabled/

# Supervisor
sudo cp supervisor/fusionpbx-worker.conf /etc/supervisor/conf.d/
sudo supervisorctl reread && sudo supervisorctl update
```

### 4. SSL Certificate
```bash
sudo certbot --nginx -d yourdomain.com
```

## Features

- ✅ Laravel 12 + Filament 4
- ✅ Laravel Horizon (Queue Dashboard)
- ✅ Laravel Reverb (WebSocket)
- ✅ Live CDR Monitoring
- ✅ WebRTC Dialer
- ✅ Multi-tenant Support
- ✅ RBAC Permissions

## Documentation

- INFRASTRUCTURE.md - Complete infrastructure guide
- COMPLETE_IMPLEMENTATION.md - Feature documentation
- DEPLOYMENT_GUIDE.md - Production deployment

## Support

For issues and questions, refer to the documentation files or create an issue on GitHub.
