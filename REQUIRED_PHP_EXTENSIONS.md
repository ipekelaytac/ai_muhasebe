# Laravel Projesi İçin Gerekli PHP Extension'ları

## 🔴 KRİTİK - Mutlaka Aktif Olmalı

### 1. **pdo_mysql** ⚠️ ŞU AN AKTİF DEĞİL!
- **Durum**: Görüntüde işaretli değil
- **Neden Gerekli**: Laravel MySQL veritabanı bağlantıları için
- **Hata**: `Class "PDO" not found` hatası bu yüzden oluşuyor
- **Aksiyon**: ✅ **HEMEN AKTİF ET!**

### 2. **pdo** ✅ Aktif
- Durum: Zaten işaretli
- Neden: PDO base extension

### 3. **mbstring** ✅ Aktif
- Durum: Görüntüde işaretli
- Neden: Çok baytlı string işlemleri için

### 4. **xmlreader** ✅ Aktif
- Durum: Görüntüde işaretli
- Neden: XML işlemleri için

### 5. **xmlwriter** ✅ Aktif
- Durum: Görüntüde işaretli
- Neden: XML yazma işlemleri için

## 🟡 ÖNERİLEN - Production İçin

### 6. **opcache** ⚠️ Aktif Değil (Önerilir)
- **Neden**: PHP performans optimizasyonu
- **Fayda**: %30-50 daha hızlı sayfa yükleme
- **Aksiyon**: Production ortamında mutlaka aktif et

### 7. **intl** ⚠️ Aktif Değil (Opsiyonel)
- **Neden**: Uluslararasılaştırma (i18n) desteği
- **Fayda**: Tarih/sayı formatlama, çoklu dil desteği
- **Aksiyon**: Eğer çoklu dil kullanacaksanız aktif edin

### 8. **zip** ⚠️ Aktif Değil (Opsiyonel)
- **Neden**: ZIP dosya işlemleri
- **Fayda**: Dosya indirme/yükleme, backup işlemleri
- **Aksiyon**: İhtiyaç varsa aktif edin

## ✅ Zaten Aktif Olanlar (İyi)

- `dom` - DOM işlemleri
- `gd` - Görsel işleme
- `igbinary` - Binary serialization
- `phar` - PHP Archive
- `posix` - POSIX fonksiyonları
- `redis` - Redis cache desteği
- `xdebug` - Debugging (sadece development için)
- `xsl` - XSLT işlemleri

## 📋 Hızlı Aksiyon Listesi

### Şimdi Yapılması Gerekenler:

1. ✅ **pdo_mysql** - HEMEN AKTİF ET (kritik!)
2. ✅ **opcache** - Production için aktif et
3. ⚠️ **intl** - İhtiyaç varsa aktif et
4. ⚠️ **zip** - İhtiyaç varsa aktif et

### Kontrol Komutu (Sunucuda):

```bash
php -m | grep -E "pdo|mbstring|xml|opcache|intl|zip"
```

Çıktıda şunlar görünmeli:
- pdo
- pdo_mysql ← **BU MUTLAKA OLMALI**
- mbstring
- xmlreader
- xmlwriter
- opcache (önerilir)
- intl (opsiyonel)
- zip (opsiyonel)

## ⚠️ Önemli Not

`pdo_mysql` aktif edildikten sonra:
1. PHP-FPM'i yeniden başlatın
2. Laravel cache'i temizleyin: `php artisan config:clear`
