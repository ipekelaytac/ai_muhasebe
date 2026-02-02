# Muhasebe Projesi İçin Gerekli PHP Extension'ları

## 🔴 KRİTİK - Mutlaka Aktif Olmalı (Proje Çalışmaz)

### 1. **pdo_mysql** ⚠️ ŞU AN AKTİF DEĞİL!
- **Durum**: Görüntüde işaretli değil
- **Neden**: MySQL veritabanı bağlantıları için (Laravel Eloquent)
- **Kullanım**: Tüm veritabanı işlemleri (documents, payments, parties, vb.)
- **Hata**: `Class "PDO" not found` hatası bu yüzden oluşuyor
- **Aksiyon**: ✅ **HEMEN AKTİF ET!**

### 2. **pdo** ✅ Aktif
- Durum: Zaten işaretli
- Neden: PDO base extension

### 3. **mbstring** ✅ Aktif
- Durum: Görüntüde işaretli
- Neden: Türkçe karakter desteği, string işlemleri
- Kullanım: Form validation, raporlar, Türkçe metinler

### 4. **xmlreader** ✅ Aktif
- Durum: Görüntüde işaretli
- Neden: Blade template compilation, XML işlemleri

### 5. **xmlwriter** ✅ Aktif
- Durum: Görüntüde işaretli
- Neden: XML oluşturma işlemleri

### 6. **dom** ✅ Aktif
- Durum: Görüntüde işaretli
- Neden: HTML/XML DOM işlemleri, Blade templates

## 🟡 ÖNERİLEN - Proje Özellikleri İçin

### 7. **fileinfo** ⚠️ Kontrol Et (Genelde Default)
- **Neden**: Dosya tipi tespiti (MIME type)
- **Kullanım**: Document attachments, file uploads
- **Aksiyon**: Genelde default olarak yüklü, kontrol edin

### 8. **openssl** ⚠️ Kontrol Et (Genelde Default)
- **Neden**: HTTPS, encryption, secure connections
- **Kullanım**: Login, API calls, secure data transfer
- **Aksiyon**: Genelde default olarak yüklü, kontrol edin

### 9. **json** ⚠️ Kontrol Et (Genelde Default)
- **Neden**: JSON encode/decode
- **Kullanım**: API responses, AJAX calls, config files
- **Aksiyon**: Genelde default olarak yüklü, kontrol edin

### 10. **ctype** ⚠️ Kontrol Et (Genelde Default)
- **Neden**: Character type checking
- **Kullanım**: Form validation, input sanitization
- **Aksiyon**: Genelde default olarak yüklü, kontrol edin

### 11. **tokenizer** ⚠️ Kontrol Et (Genelde Default)
- **Neden**: PHP token parsing
- **Kullanım**: Blade template compilation
- **Aksiyon**: Genelde default olarak yüklü, kontrol edin

### 12. **opcache** ⚠️ Aktif Değil (Production İçin Önerilir)
- **Neden**: PHP performans optimizasyonu
- **Fayda**: %30-50 daha hızlı sayfa yükleme
- **Kullanım**: Production ortamında mutlaka aktif olmalı
- **Aksiyon**: ✅ **Production için aktif et**

### 13. **zip** ⚠️ Aktif Değil (Opsiyonel ama Faydalı)
- **Neden**: ZIP dosya işlemleri
- **Kullanım**: 
  - Rapor export (Excel/CSV)
  - Backup işlemleri
  - Toplu dosya indirme
- **Aksiyon**: İhtiyaç varsa aktif et

### 14. **gd** ✅ Aktif
- Durum: Görüntüde işaretli
- Neden: Görsel işleme (logo, thumbnails, grafikler)
- Kullanım: Company logos, document previews

## ✅ Zaten Aktif Olanlar (İyi)

- `redis` - Cache ve session storage için
- `igbinary` - Binary serialization (Redis için)
- `phar` - PHP Archive
- `posix` - POSIX fonksiyonları
- `xsl` - XSLT işlemleri
- `xdebug` - Debugging (sadece development için)

## 📋 Projeye Özel Kullanım Senaryoları

### Veritabanı İşlemleri
- **pdo_mysql** → Tüm CRUD işlemleri (documents, payments, parties, vb.)

### Dosya İşlemleri
- **fileinfo** → Attachment MIME type tespiti
- **zip** → Rapor export, backup
- **gd** → Logo, görsel işleme

### Performans
- **opcache** → Production performans optimizasyonu
- **redis** → Cache ve session (zaten aktif)

### Türkçe Desteği
- **mbstring** → Türkçe karakter desteği (zaten aktif)

## 🎯 Hızlı Aksiyon Listesi

### Şimdi Yapılması Gerekenler:

1. ✅ **pdo_mysql** - HEMEN AKTİF ET (kritik!)
2. ✅ **opcache** - Production için aktif et
3. ⚠️ **zip** - Rapor export için aktif et (önerilir)
4. ⚠️ **fileinfo, openssl, json, ctype, tokenizer** - Kontrol et (genelde default)

### Kontrol Komutu (Sunucuda):

```bash
php -m | grep -E "pdo|mbstring|xml|fileinfo|openssl|json|ctype|tokenizer|opcache|zip|gd"
```

Çıktıda şunlar görünmeli:
- pdo ✅
- pdo_mysql ← **BU MUTLAKA OLMALI**
- mbstring ✅
- xmlreader ✅
- xmlwriter ✅
- dom ✅
- fileinfo (genelde default)
- openssl (genelde default)
- json (genelde default)
- ctype (genelde default)
- tokenizer (genelde default)
- opcache (önerilir)
- zip (opsiyonel ama faydalı)
- gd ✅

## ⚠️ Önemli Not

`pdo_mysql` aktif edildikten sonra:
1. PHP-FPM'i yeniden başlatın
2. Laravel cache'i temizleyin: `php artisan config:clear`
3. Sayfayı yenileyin ve test edin

## 📊 Öncelik Sırası

1. **pdo_mysql** - Hemen aktif et (proje çalışmaz)
2. **opcache** - Production performans için
3. **zip** - Rapor export için (opsiyonel)
4. Diğerleri - Genelde default olarak yüklü
