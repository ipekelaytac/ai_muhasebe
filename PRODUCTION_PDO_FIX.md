# PDO Extension Hatası - Canlı Ortam Düzeltmesi

## Sorun
```
Class "PDO" not found
```

Bu hata, sunucuda PHP PDO extension'ının yüklü olmadığını gösterir.

## Çözüm Adımları

### 1. PDO Extension'ını Yükleyin

#### Ubuntu/Debian (Apache/Nginx):
```bash
sudo apt-get update
sudo apt-get install php-pdo php-mysql
# veya PHP 8.x için:
sudo apt-get install php8.2-pdo php8.2-mysql
```

#### CentOS/RHEL:
```bash
sudo yum install php-pdo php-mysql
# veya PHP 8.x için:
sudo yum install php82-pdo php82-mysql
```

#### cPanel/WHM:
1. WHM → Software → Module Installers → PHP Pecl
2. PDO extension'ını yükleyin
3. Veya MultiPHP Manager'dan PHP versiyonunu seçip extension'ları kontrol edin

### 2. PHP-FPM'i Yeniden Başlatın

```bash
# Apache ile:
sudo systemctl restart apache2
# veya
sudo service apache2 restart

# Nginx + PHP-FPM ile:
sudo systemctl restart php8.2-fpm
# veya
sudo service php-fpm restart
```

### 3. Kontrol Edin

Sunucuda şu komutu çalıştırın:
```bash
php -m | grep -i pdo
```

Çıktıda `PDO` ve `pdo_mysql` görünmeli.

### 4. php.ini Dosyasını Kontrol Edin

`php.ini` dosyasında şu satırların uncomment edildiğinden emin olun:
```ini
extension=pdo
extension=pdo_mysql
```

### 5. Laravel Cache'i Temizleyin

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

## Hızlı Test

Sunucuda şu komutu çalıştırarak PDO'nun çalıştığını test edin:
```bash
php -r "echo class_exists('PDO') ? 'PDO OK' : 'PDO NOT FOUND';"
```

## Alternatif Çözüm (Paylaşımlı Hosting)

Eğer paylaşımlı hosting kullanıyorsanız:
1. Hosting sağlayıcınızla iletişime geçin
2. PDO extension'ının aktif olduğundan emin olun
3. PHP versiyonunuzun 8.1+ olduğundan emin olun

## Not

Bu hata sadece canlı ortamda görünüyorsa, muhtemelen:
- Sunucuda PDO extension'ı yüklü değil
- PHP-FPM yeniden başlatılmamış
- php.ini'de extension'lar devre dışı

Yerel ortamda çalışıyorsa, yerel PHP kurulumunuzda PDO zaten yüklü demektir.
