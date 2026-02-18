# FusionPBX Infrastructure Guide

Complete documentation for all infrastructure components.

## Architecture Overview

```
Browser (WebRTC + Livewire)
    ↕
Laravel 12 (Reverb WebSocket + REST API)
    ↕
Horizon (Queue Processing)
    ↕
FreeSWITCH (ESL Events)
    ↕
PostgreSQL + Redis
```

## Database Migrations

### Available Migrations

1. **v_domains** - Multi-tenant domains
2. **v_users** - User accounts
3. **v_groups** - User groups
4. **v_extensions** - SIP extensions
5. **v_xml_cdr** - Call detail records

### Running Migrations

```bash
# Run all migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Reset all migrations
php artisan migrate:reset

# Refresh (reset + migrate)
php artisan migrate:refresh
```

## Database Seeders

### DemoDataSeeder

Seeds demonstration data for testing:
- Demo domain (demo.fusionpbx.com)
- Admin user (admin / admin123)
- 5 Extensions (1000-1004)

```bash
php artisan db:seed --class=DemoDataSeeder
```

### ProductionSeeder

Seeds production-ready data:
- System permissions
- Default configuration

```bash
php artisan db:seed --class=ProductionSeeder
```

## Laravel Horizon

### Purpose
Monitors and manages queue workers with a beautiful dashboard.

### Configuration
File: `config/horizon.php`

- Queue processing configuration
- Worker auto-scaling
- Job retry settings
- Memory limits

### Commands

```bash
# Start Horizon
php artisan horizon

# Pause processing
php artisan horizon:pause

# Continue processing
php artisan horizon:continue

# Terminate
php artisan horizon:terminate
```

### Dashboard
Access at: http://domain.com/horizon

Features:
- Active jobs monitoring
- Failed jobs management
- Queue metrics
- Throughput graphs

## Laravel Reverb

### Purpose
Native Laravel WebSocket server for real-time communication.

### Configuration
File: `config/reverb.php`

- WebSocket server settings
- App credentials
- Scaling options

### Commands

```bash
# Start Reverb server
php artisan reverb:start

# Start on specific port
php artisan reverb:start --port=8080

# With debugging
php artisan reverb:start --debug
```

### Broadcasting Channels

File: `routes/channels.php`

- **domain.{uuid}** - Domain-specific events
- **calls.{uuid}** - Call-specific events

## Queue System

### Jobs

#### ProcessCallEvent
Processes FreeSWITCH call events.

```php
ProcessCallEvent::dispatch($eventData);
```

#### SendCallNotification
Sends notifications about calls.

```php
SendCallNotification::dispatch($callData);
```

### Queues

- **default** - General purpose
- **cdr** - CDR processing
- **notifications** - User notifications

### Monitoring

```bash
# View failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {id}

# Retry all failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

## Artisan Commands

### SetupCommand

Interactive setup wizard.

```bash
php artisan fusionpbx:setup
```

Features:
- Runs migrations
- Seeds database
- Creates admin user
- Configures environment

### MonitorCallsCommand

Real-time call monitoring.

```bash
php artisan fusionpbx:monitor-calls
```

Features:
- ESL event listening
- Live call statistics
- Event broadcasting

## Supervisor

### Purpose
Manages Laravel processes in production.

### Configuration
File: `supervisor/fusionpbx-worker.conf`

Manages:
- **fusionpbx-horizon** - Queue workers
- **fusionpbx-reverb** - WebSocket server
- **fusionpbx-schedule** - Task scheduler

### Commands

```bash
# Start all processes
sudo supervisorctl start fusionpbx:*

# Stop all processes
sudo supervisorctl stop fusionpbx:*

# Restart all processes
sudo supervisorctl restart fusionpbx:*

# Check status
sudo supervisorctl status fusionpbx:*

# View logs
tail -f /var/www/fusionpbx/storage/logs/horizon.log
tail -f /var/www/fusionpbx/storage/logs/reverb.log
```

## Nginx

### Configuration
File: `nginx/fusionpbx.conf`

Features:
- PHP-FPM proxy
- WebSocket proxy for Reverb
- Static file serving
- Security headers

### Commands

```bash
# Test configuration
sudo nginx -t

# Reload configuration
sudo systemctl reload nginx

# Restart Nginx
sudo systemctl restart nginx

# Check status
sudo systemctl status nginx
```

## Redis

### Purpose
Caching and queue backend.

### Configuration

```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

### Commands

```bash
# Connect to Redis
redis-cli

# Check connection
redis-cli ping

# Monitor commands
redis-cli monitor

# View keys
redis-cli keys '*'
```

## Testing

### PHPUnit Configuration
File: `phpunit.xml`

Features:
- SQLite in-memory database
- Fast test execution
- Parallel testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter=ApiTest

# With coverage
php artisan test --coverage
```

## Monitoring

### Log Files

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Horizon logs
tail -f storage/logs/horizon.log

# Reverb logs
tail -f storage/logs/reverb.log

# Nginx access logs
tail -f /var/log/nginx/access.log

# Nginx error logs
tail -f /var/log/nginx/error.log
```

### Health Checks

```bash
# Check database
php artisan tinker
>>> \DB::connection()->getPdo();

# Check Redis
>>> \Redis::ping();

# Check queue
>>> dispatch(new \App\Jobs\ProcessCallEvent([]));
```

## Performance Optimization

### Caching

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Clear all caches
php artisan optimize:clear
```

### OPcache

Enable in `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
```

### Database Optimization

```sql
-- Create indexes
CREATE INDEX idx_domain_uuid ON v_users(domain_uuid);
CREATE INDEX idx_start_stamp ON v_xml_cdr(start_stamp);

-- Analyze tables
ANALYZE v_users;
ANALYZE v_xml_cdr;
```

## Security

### Best Practices

1. **Environment File**
   - Never commit `.env`
   - Use strong passwords
   - Rotate keys regularly

2. **HTTPS**
   - Always use SSL/TLS
   - Configure with certbot

3. **Firewall**
   ```bash
   sudo ufw allow 22
   sudo ufw allow 80
   sudo ufw allow 443
   sudo ufw enable
   ```

4. **Database**
   - Use strong passwords
   - Limit connections
   - Regular backups

5. **File Permissions**
   ```bash
   sudo chown -R www-data:www-data /var/www/fusionpbx
   sudo chmod -R 755 /var/www/fusionpbx/storage
   ```

## Backup & Recovery

### Database Backup

```bash
# PostgreSQL backup
pg_dump -U fusionpbx fusionpbx > backup.sql

# Restore
psql -U fusionpbx fusionpbx < backup.sql
```

### Application Backup

```bash
# Backup storage
tar -czf storage-backup.tar.gz storage/

# Backup entire application
tar -czf fusionpbx-backup.tar.gz /var/www/fusionpbx
```

## Troubleshooting

### Common Issues

**Queue not processing:**
```bash
sudo supervisorctl restart fusionpbx-horizon
tail -f storage/logs/horizon.log
```

**WebSocket not connecting:**
```bash
sudo supervisorctl restart fusionpbx-reverb
tail -f storage/logs/reverb.log
```

**Permission denied:**
```bash
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/
```

**Database connection failed:**
```bash
# Check PostgreSQL status
sudo systemctl status postgresql

# Test connection
psql -U fusionpbx -h 127.0.0.1 -d fusionpbx
```

## Additional Resources

- Laravel Documentation: https://laravel.com/docs
- Filament Documentation: https://filamentphp.com/docs
- Horizon Documentation: https://laravel.com/docs/horizon
- Reverb Documentation: https://laravel.com/docs/reverb

## Support

For additional help:
1. Check application logs
2. Review documentation
3. Create GitHub issue
4. Contact support team
