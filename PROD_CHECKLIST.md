# Production Deployment Checklist

Bu doküman, muhasebe sisteminin production ortamına deploy edilmesi için gerekli adımları içerir.

## Ön Gereksinimler

- PHP 8.2+
- MySQL 8.0+
- Composer
- Node.js & NPM (frontend assets için)

---

## Deployment Adımları

### 1. Sunucu Hazırlığı

```bash
# PHP ve gerekli extension'ları kontrol edin
php -v
php -m | grep -E "pdo_mysql|mbstring|xml|curl|zip|gd"

# Composer kurulu mu kontrol edin
composer --version

# Node.js kurulu mu kontrol edin
node --version
npm --version
```

### 2. Kod Deploy

```bash
# Projeyi clone edin veya güncelleyin
git clone <repository-url>
cd muhasebe

# veya mevcut projeyi güncelleyin
git pull origin main
```

### 3. Bağımlılıkları Yükleyin

```bash
# PHP bağımlılıkları
composer install --no-dev --optimize-autoloader

# Frontend assets (eğer varsa)
npm install
npm run production
```

### 4. Environment Ayarları

```bash
# .env dosyasını oluşturun
cp .env.example .env

# .env dosyasını düzenleyin
nano .env
```

**Önemli .env ayarları:**

```env
APP_NAME=Muhasebe
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=muhasebe
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

LOG_CHANNEL=daily
LOG_LEVEL=error

SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

```bash
# Application key oluşturun
php artisan key:generate
```

### 5. Veritabanı Kurulumu

```bash
# Veritabanını oluşturun (MySQL'de)
mysql -u root -p
CREATE DATABASE muhasebe CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'muhasebe_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON muhasebe.* TO 'muhasebe_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Migration'ları çalıştırın
php artisan migrate --force

# Seed'leri çalıştırın (isteğe bağlı)
php artisan db:seed --force
```

### 6. Storage Link

```bash
# Storage link oluşturun
php artisan storage:link
```

### 7. Cache ve Optimizasyon

```bash
# Config cache
php artisan config:cache

# Route cache
php artisan route:cache

# View cache
php artisan view:cache

# Event cache (eğer varsa)
php artisan event:cache
```

### 8. İzinler

```bash
# Storage ve cache klasörlerine yazma izni verin
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# veya kullanıcınıza göre
chown -R $USER:www-data storage bootstrap/cache
```

### 9. Web Sunucusu Yapılandırması

#### Apache (.htaccess zaten mevcut)

```apache
# public/.htaccess dosyası zaten var
# DocumentRoot'u public klasörüne ayarlayın
DocumentRoot /path/to/muhasebe/public
```

#### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/muhasebe/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 10. SSL Sertifikası (Önerilir)

```bash
# Let's Encrypt ile SSL kurulumu
sudo certbot --nginx -d your-domain.com
```

### 11. Cron Job Kurulumu

```bash
# Crontab'ı düzenleyin
crontab -e

# Şu satırı ekleyin (Laravel scheduler için)
* * * * * cd /path/to/muhasebe && php artisan schedule:run >> /dev/null 2>&1
```

### 12. Queue Worker (Eğer queue kullanıyorsanız)

```bash
# Supervisor ile queue worker kurulumu
# /etc/supervisor/conf.d/muhasebe-worker.conf

[program:muhasebe-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/muhasebe/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/muhasebe/storage/logs/worker.log
stopwaitsecs=3600
```

---

## Post-Deployment Kontroller

### 1. Route Kontrolü

```bash
php artisan route:list
```

### 2. Migration Kontrolü

```bash
php artisan migrate:status
```

### 3. Log Kontrolü

```bash
# Log dosyalarını kontrol edin
tail -f storage/logs/laravel.log
```

### 4. Web Arayüzü Kontrolü

- [ ] Ana sayfa yükleniyor mu?
- [ ] Login sayfası çalışıyor mu?
- [ ] Dashboard görüntüleniyor mu?
- [ ] Tüm menü öğeleri çalışıyor mu?

### 5. Veritabanı Kontrolü

```bash
# Veritabanı bağlantısını test edin
php artisan tinker
>>> DB::connection()->getPdo();
```

---

## Backup Stratejisi

### 1. Veritabanı Yedekleme

```bash
# Günlük backup script'i oluşturun
# /path/to/backup-db.sh

#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/path/to/backups"
DB_NAME="muhasebe"
DB_USER="muhasebe_user"
DB_PASS="secure_password"

mkdir -p $BACKUP_DIR
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/muhasebe_$DATE.sql.gz

# 30 günden eski backup'ları sil
find $BACKUP_DIR -name "muhasebe_*.sql.gz" -mtime +30 -delete
```

```bash
# Script'i çalıştırılabilir yapın
chmod +x /path/to/backup-db.sh

# Crontab'a ekleyin (her gece 02:00'de)
0 2 * * * /path/to/backup-db.sh
```

### 2. Dosya Yedekleme

```bash
# Storage klasörünü yedekleyin
tar -czf /path/to/backups/storage_$(date +%Y%m%d).tar.gz storage/
```

---

## Monitoring ve Logging

### 1. Log Rotation

Laravel'ın `daily` log driver'ı otomatik olarak log rotation yapar. Ancak sistem log'ları için:

```bash
# Logrotate yapılandırması
# /etc/logrotate.d/muhasebe

/path/to/muhasebe/storage/logs/*.log {
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

### 2. Monitoring

- **Uptime Monitoring**: UptimeRobot, Pingdom vb.
- **Error Tracking**: Sentry, Bugsnag (opsiyonel)
- **Performance Monitoring**: New Relic, DataDog (opsiyonel)

---

## Güvenlik Kontrolleri

- [ ] `.env` dosyası production ayarlarına sahip (`APP_DEBUG=false`)
- [ ] `.env` dosyası web erişiminden korunuyor
- [ ] `storage` ve `bootstrap/cache` klasörleri yazılabilir
- [ ] SSL sertifikası kurulu ve çalışıyor
- [ ] Veritabanı kullanıcısı sadece gerekli izinlere sahip
- [ ] Firewall kuralları yapılandırılmış
- [ ] Düzenli güvenlik güncellemeleri yapılıyor

---

## Performans Optimizasyonu

### 1. OPcache Etkinleştirin

```ini
# php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0  # Production'da 0 olmalı
```

### 2. Database Indexes

Tüm kritik indexler migration'larda tanımlı. Ek index gerekiyorsa:

```bash
php artisan migrate
```

### 3. Query Optimization

- Eager loading kullanın (`with()`)
- N+1 query problemlerini önleyin
- Slow query log'u aktif edin ve izleyin

---

## Troubleshooting

### Problem: 500 Internal Server Error

**Çözüm:**
```bash
# Log'ları kontrol edin
tail -f storage/logs/laravel.log

# İzinleri kontrol edin
ls -la storage bootstrap/cache

# Cache'i temizleyin
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Problem: Database Connection Error

**Çözüm:**
```bash
# .env dosyasını kontrol edin
cat .env | grep DB_

# Veritabanı bağlantısını test edin
php artisan tinker
>>> DB::connection()->getPdo();
```

### Problem: Storage Link Çalışmıyor

**Çözüm:**
```bash
# Mevcut link'i silin
rm public/storage

# Yeniden oluşturun
php artisan storage:link

# İzinleri kontrol edin
ls -la public/storage
```

---

## Güncelleme Prosedürü

```bash
# 1. Backup alın
/path/to/backup-db.sh

# 2. Kod güncellemesi
git pull origin main

# 3. Bağımlılıkları güncelleyin
composer install --no-dev --optimize-autoloader

# 4. Migration'ları çalıştırın
php artisan migrate --force

# 5. Cache'i temizleyin ve yeniden oluşturun
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Queue worker'ı yeniden başlatın (eğer varsa)
sudo supervisorctl restart muhasebe-worker:*
```

---

## İletişim ve Destek

Sorunlar için:
- Log dosyalarını kontrol edin: `storage/logs/`
- Sistem yöneticisine başvurun
- GitHub Issues (eğer varsa)

---

## Notlar

- Bu checklist, tek kullanıcılı (owner-operator) bir sistem için hazırlanmıştır
- Çok kullanıcılı ortamlar için ek güvenlik ve performans önlemleri gerekebilir
- Düzenli backup'lar kritik öneme sahiptir
- Production ortamında `APP_DEBUG` mutlaka `false` olmalıdır
