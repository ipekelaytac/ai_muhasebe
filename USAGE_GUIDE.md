# Muhasebe Sistemi Kullanım Kılavuzu

Bu kılavuz, muhasebe sistemini kullanan kullanıcılar için hazırlanmıştır. Teknik bilgi gerektirmez ve günlük işlemlerinizi kolayca yapmanızı sağlar.

## İçindekiler

1. [Temel Kavramlar](#temel-kavramlar)
2. [Ana Ekranlar](#ana-ekranlar)
3. [Senaryolar](#senaryolar)
4. [Dönem Kilidi](#dönem-kilidi)
5. [Sık Yapılan Hatalar ve Çözümleri](#sık-yapılan-hatalar-ve-çözümleri)

---

## Temel Kavramlar

### Tahakkuk (Belge)
**Tahakkuk**, borç veya alacak doğuran kayıttır. Örneğin:
- **Tedarikçi faturası**: Size mal/hizmet satan birinden gelen fatura (borç)
- **Müşteri faturası**: Müşteriye sattığınız mal/hizmet için oluşturduğunuz fatura (alacak)
- **Maaş tahakkuku**: Çalışanlara ödemeniz gereken maaş (borç)

**Önemli**: Tahakkuk, henüz ödeme yapılmamış veya tahsilat alınmamış durumda olan borç/alacak kaydıdır.

### Ödeme / Tahsilat
**Ödeme**, kasadan veya bankadan para çıkışıdır. **Tahsilat** ise kasa veya bankaya para girişidir.

**Önemli**: Ödeme/Tahsilat kaydı, sadece para hareketini gösterir. Hangi borcu/alacağı kapattığını belirtmek için "Dağıtım" yapmanız gerekir.

### Dağıtım (Mahsup)
**Dağıtım**, bir ödemenin hangi tahakkuku kapattığını belirtme işlemidir. Örneğin:
- 1000 TL'lik bir ödeme yaptınız
- Bu ödeme, 500 TL'lik bir faturayı ve 500 TL'lik başka bir faturayı kapatıyor
- Dağıtım yaparak ödemenin hangi faturaları kapattığını sisteme bildirirsiniz

**Önemli**: Bir ödeme birden fazla tahakkuku kapatabilir (kısmi ödemeler).

### Cari
**Cari**, müşteri, tedarikçi veya çalışan gibi iş yaptığınız kişi/kurumdur. Her cari için borç/alacak durumunu görebilirsiniz.

### Dönem Kilidi
Bir dönem (ay) kilitlendiğinde, o döneme ait kayıtlar değiştirilemez. Hata düzeltmek için "ters kayıt" kullanılır.

---

## Ana Ekranlar

### 1. Cariler
**Ne işe yarar?** Müşteri, tedarikçi ve çalışanlarınızı yönetirsiniz.

**Ne zaman kullanılır?**
- Yeni müşteri/tedarikçi/çalışan eklerken
- Cari bilgilerini güncellerken
- Cari ekstresini görüntülerken

**Nasıl kullanılır?**
1. Sol menüden "Cariler" seçeneğine tıklayın
2. Yeni cari eklemek için "Yeni Cari" butonuna tıklayın
3. Cari bilgilerini doldurun (ad, tip, vergi no vb.)
4. Kaydet butonuna tıklayın

**Önemli**: Her cari için "Cari Ekstre" raporunu görüntüleyerek borç/alacak durumunu görebilirsiniz.

---

### 2. Tahakkuklar (Belgeler)
**Ne işe yarar?** Borç/alacak doğuran belgeleri oluşturur ve yönetirsiniz.

**Ne zaman kullanılır?**
- Tedarikçi faturası geldiğinde
- Müşteriye fatura keserken
- Çalışan maaşı tahakkuk ettirirken
- Gider/gelir tahakkuku oluştururken

**Nasıl kullanılır?**
1. Sol menüden "Tahakkuklar" seçeneğine tıklayın
2. "Yeni Tahakkuk" butonuna tıklayın
3. Belge tipini seçin (ör: Tedarikçi Faturası)
4. Cariyi seçin
5. Tarih ve tutarı girin
6. Açıklama ekleyin (isteğe bağlı)
7. Kaydet butonuna tıklayın

**Önemli**: 
- Belge tipi otomatik olarak borç/alacak yönünü belirler
- Belge oluşturulduktan sonra durumu "Beklemede" olur
- Ödeme yapıldığında durum "Kısmi" veya "Kapalı" olur

---

### 3. Ödeme / Tahsilat
**Ne işe yarar?** Kasa veya bankadan para giriş/çıkışını kaydedersiniz.

**Ne zaman kullanılır?**
- Müşteriden tahsilat aldığınızda
- Tedarikçiye ödeme yaptığınızda
- Çalışana maaş ödediğinizde
- Kasa/banka arası transfer yaptığınızda

**Nasıl kullanılır?**
1. Sol menüden "Ödeme / Tahsilat" seçeneğine tıklayın
2. "Yeni Ödeme/Tahsilat" butonuna tıklayın
3. Ödeme tipini seçin (Kasa Girişi, Banka Çıkışı vb.)
4. Cariyi seçin (isteğe bağlı - transfer için gerekmez)
5. Kasa veya banka hesabını seçin
6. Tarih ve tutarı girin
7. Kaydet butonuna tıklayın

**Önemli**: 
- Ödeme kaydedildikten sonra "Dağıtım" yaparak hangi belgeleri kapattığını belirtmeniz gerekir
- Dağıtım yapılmazsa ödeme "dağıtılmamış" kalır ve belgeler kapanmaz

---

### 4. Dağıtım (Mahsup)
**Ne işe yarar?** Ödemeleri belirli belgelere dağıtırsınız.

**Ne zaman kullanılır?**
- Bir ödeme yaptıktan sonra hangi faturaları kapattığını belirtmek için
- Kısmi ödemeleri dağıtmak için

**Nasıl kullanılır?**
1. Ödeme detay sayfasına gidin
2. "Dağıtım Yap" butonuna tıklayın
3. Dağıtılacak belgeleri seçin
4. Her belge için dağıtılacak tutarı girin
5. Toplam tutarın ödeme tutarına eşit olduğundan emin olun
6. Kaydet butonuna tıklayın

**Önemli**: 
- Dağıtım tutarı, belgenin kalan borcundan fazla olamaz
- Dağıtım tutarı, ödemenin kalan tutarından fazla olamaz
- Bir ödeme birden fazla belgeye dağıtılabilir

---

### 5. Çek / Senet
**Ne işe yarar?** Alınan ve verilen çekleri takip edersiniz.

**Ne zaman kullanılır?**
- Müşteriden çek aldığınızda
- Tedarikçiye çek verdiğinizde
- Çeki bankaya verdiğinizde
- Çek tahsil edildiğinde
- Çek karşılıksız çıktığında

**Nasıl kullanılır?**
1. Sol menüden "Çek / Senet" seçeneğine tıklayın
2. "Yeni Çek" butonuna tıklayın
3. Çek tipini seçin (Alınan/Verilen)
4. Cariyi seçin
5. Çek bilgilerini girin (numara, vade tarihi, tutar vb.)
6. Kaydet butonuna tıklayın

**Çek Durumları:**
- **Portföyde**: Çek sizde, henüz bankaya verilmedi
- **Bankaya Verildi**: Çek bankaya teslim edildi
- **Tahsil Edildi**: Çek tahsil edildi, ödeme kaydedildi
- **Karşılıksız**: Çek karşılıksız çıktı

---

### 6. Raporlar
**Ne işe yarar?** Muhasebe raporlarını görüntülersiniz.

**Raporlar:**
- **Kasa & Banka Bakiyeleri**: Güncel kasa ve banka hesap bakiyeleri
- **Alacak Yaşlandırma**: Alacakların vade durumuna göre yaşlandırma
- **Borç Yaşlandırma**: Borçların vade durumuna göre yaşlandırma
- **Çalışan Borçları Yaşlandırma**: Çalışan borçlarının durumu
- **Nakit Akış Tahmini**: Gelecekteki nakit akış tahmini (30/60/90 gün)
- **Cari Ekstre**: Belirli bir cari için borç/alacak hareketleri
- **Aylık Kâr/Zarar**: Aylık gelir-gider raporu

**Nasıl kullanılır?**
1. Sol menüden "Raporlar" seçeneğine tıklayın
2. İstediğiniz raporu seçin
3. Tarih aralığı veya diğer filtreleri ayarlayın
4. Raporu görüntüleyin

---

### 7. Dönem Kilidi
**Ne işe yarar?** Muhasebe dönemlerini (ayları) kilitlersiniz.

**Ne zaman kullanılır?**
- Bir ayın muhasebe işlemleri tamamlandığında
- O aya ait kayıtların değiştirilmesini engellemek için

**Nasıl kullanılır?**
1. Sol menüden "Dönem Kilit" seçeneğine tıklayın
2. Kilitlemek istediğiniz dönemi bulun
3. "Kilitle" butonuna tıklayın
4. Onaylayın

**Önemli**: 
- Kilitli dönemlerde belge, ödeme ve dağıtım işlemleri yapılamaz
- Hata düzeltmek için "ters kayıt" oluşturmanız gerekir
- Kilidi açmak için "Kilidi Aç" butonuna tıklayın

---

## Senaryolar

### Senaryo 1: Tedarikçi Faturası Gir → Kısmi Ödeme Yap → Dağıt

**Durum**: Bir tedarikçiden 1000 TL'lik fatura geldi. 600 TL ödeme yaptınız.

**Adımlar:**

1. **Tahakkuk Oluştur:**
   - "Tahakkuklar" → "Yeni Tahakkuk"
   - Tip: Tedarikçi Faturası
   - Cari: Tedarikçiyi seçin
   - Tarih: Fatura tarihi
   - Tutar: 1000 TL
   - Kaydet

2. **Ödeme Kaydet:**
   - "Ödeme / Tahsilat" → "Yeni Ödeme/Tahsilat"
   - Tip: Kasa Çıkışı veya Banka Çıkışı
   - Cari: Aynı tedarikçiyi seçin
   - Kasa/Banka: Ödeme yaptığınız kasa/banka
   - Tarih: Ödeme tarihi
   - Tutar: 600 TL
   - Kaydet

3. **Dağıtım Yap:**
   - Ödeme detay sayfasına gidin
   - "Dağıtım Yap" butonuna tıklayın
   - Faturayı seçin
   - Tutar: 600 TL
   - Kaydet

**Sonuç**: Fatura durumu "Kısmi" olur. Kalan borç: 400 TL.

---

### Senaryo 2: Müşteri Alacağı Gir → Tahsilat Al → Dağıt

**Durum**: Müşteriye 2000 TL'lik fatura kestiniz. Müşteri 2000 TL ödedi.

**Adımlar:**

1. **Tahakkuk Oluştur:**
   - "Tahakkuklar" → "Yeni Tahakkuk"
   - Tip: Müşteri Faturası
   - Cari: Müşteriyi seçin
   - Tarih: Fatura tarihi
   - Tutar: 2000 TL
   - Kaydet

2. **Tahsilat Kaydet:**
   - "Ödeme / Tahsilat" → "Yeni Ödeme/Tahsilat"
   - Tip: Kasa Girişi veya Banka Girişi
   - Cari: Aynı müşteriyi seçin
   - Kasa/Banka: Tahsilat aldığınız kasa/banka
   - Tarih: Tahsilat tarihi
   - Tutar: 2000 TL
   - Kaydet

3. **Dağıtım Yap:**
   - Tahsilat detay sayfasına gidin
   - "Dağıtım Yap" butonuna tıklayın
   - Faturayı seçin
   - Tutar: 2000 TL
   - Kaydet

**Sonuç**: Fatura durumu "Kapalı" olur.

---

### Senaryo 3: Personel Mesai Alacağı Oluştur → Öde

**Durum**: Bir çalışana 500 TL mesai ücreti borçlusunuz. Ödeme yaptınız.

**Adımlar:**

1. **Tahakkuk Oluştur:**
   - "Tahakkuklar" → "Yeni Tahakkuk"
   - Tip: Mesai Tahakkuku
   - Cari: Çalışanı seçin (tip: Personel)
   - Tarih: Mesai tarihi
   - Tutar: 500 TL
   - Kaydet

2. **Ödeme Kaydet:**
   - "Ödeme / Tahsilat" → "Yeni Ödeme/Tahsilat"
   - Tip: Kasa Çıkışı veya Banka Çıkışı
   - Cari: Aynı çalışanı seçin
   - Kasa/Banka: Ödeme yaptığınız kasa/banka
   - Tarih: Ödeme tarihi
   - Tutar: 500 TL
   - Kaydet

3. **Dağıtım Yap:**
   - Ödeme detay sayfasına gidin
   - "Dağıtım Yap" butonuna tıklayın
   - Mesai tahakkukunu seçin
   - Tutar: 500 TL
   - Kaydet

**Sonuç**: Mesai tahakkuku "Kapalı" olur.

---

### Senaryo 4: Personel Avans Ver → Maaşta Mahsup Et → Net Öde

**Durum**: Bir çalışana 1000 TL avans verdiniz. Maaş ödemesinde bu avansı mahsup etmek istiyorsunuz.

**Adımlar:**

1. **Avans Ver:**
   - Çalışanın cari detay sayfasına gidin
   - "Avanslar" butonuna tıklayın
   - "Avans Ver" butonuna tıklayın
   - Tarih: Avans tarihi
   - Tutar: 1000 TL
   - Ödeme Kaynağı: Kasa veya Banka
   - Kasa/Banka: Avans verdiğiniz kasa/banka
   - Kaydet

2. **Maaş Tahakkuku Oluştur:**
   - "Tahakkuklar" → "Yeni Tahakkuk"
   - Tip: Maaş Tahakkuku
   - Cari: Aynı çalışanı seçin
   - Tarih: Maaş tarihi
   - Tutar: 5000 TL (brüt maaş)
   - Kaydet

3. **Avans Mahsup Et:**
   - Maaş tahakkuku detay sayfasına gidin
   - "Avans Kesintileri" butonuna tıklayın
   - Açık avansları görüntüleyin
   - Avansı seçin ve tutarı girin: 1000 TL
   - "Mahsup Et" butonuna tıklayın

4. **Net Maaş Öde:**
   - "Ödeme / Tahsilat" → "Yeni Ödeme/Tahsilat"
   - Tip: Kasa Çıkışı veya Banka Çıkışı
   - Cari: Aynı çalışanı seçin
   - Kasa/Banka: Ödeme yaptığınız kasa/banka
   - Tarih: Ödeme tarihi
   - Tutar: 4000 TL (5000 - 1000)
   - Kaydet

5. **Dağıtım Yap:**
   - Ödeme detay sayfasına gidin
   - "Dağıtım Yap" butonuna tıklayın
   - Maaş tahakkukunu seçin
   - Tutar: 4000 TL
   - Kaydet

**Sonuç**: 
- Avans tahakkuku "Kapalı" olur
- Maaş tahakkuku "Kapalı" olur
- Çalışana net 4000 TL ödendi

---

### Senaryo 5: Çek Al → Portföyde Tut → Bankaya Ver → Tahsil Oldu

**Durum**: Müşteriden 5000 TL'lik çek aldınız. Çeki bankaya verdiniz ve tahsil edildi.

**Adımlar:**

1. **Çek Al:**
   - "Çek / Senet" → "Yeni Çek"
   - Tip: Alınan Çek
   - Cari: Müşteriyi seçin
   - Çek Numarası: Çek numarası
   - Vade Tarihi: Çek vade tarihi
   - Tutar: 5000 TL
   - Kaydet

   **Not**: Otomatik olarak bir "Alınan Çek" tahakkuku oluşturulur.

2. **Bankaya Ver:**
   - Çek detay sayfasına gidin
   - "Bankaya Ver" butonuna tıklayın
   - Banka hesabını seçin
   - Kaydet

   **Durum**: Çek durumu "Bankaya Verildi" olur.

3. **Tahsil Et:**
   - Çek detay sayfasına gidin
   - "Tahsil Et" butonuna tıklayın
   - Banka hesabını seçin
   - Kaydet

   **Sonuç**: 
   - Çek durumu "Tahsil Edildi" olur
   - Otomatik olarak bir banka girişi ödemesi oluşturulur
   - Çek tahakkuku otomatik olarak kapanır

---

## Dönem Kilidi

### Ne Zaman Kilitlenir?
Bir ayın muhasebe işlemleri tamamlandığında, o ayı kilitleyebilirsiniz. Örneğin:
- Ocak ayının tüm faturaları girildi
- Tüm ödemeler yapıldı
- Dağıtımlar tamamlandı
- Raporlar kontrol edildi
- Artık Ocak ayını kilitleyebilirsiniz

### Kilitli Dönemde Hata Düzeltme
Kilitli dönemde kayıt değiştirilemez. Hata düzeltmek için **ters kayıt** kullanılır:

**Örnek**: Yanlışlıkla 1000 TL yerine 2000 TL fatura girildi.

**Çözüm:**
1. Yeni bir tahakkuk oluşturun
2. Tip: Düzeltme (Borç/Alacak)
3. Tarih: Açık bir dönem tarihi
4. Tutar: -1000 TL (eksi tutar ile ters kayıt)
5. Açıklama: "Ocak ayı fatura düzeltmesi - Yanlış tutar"
6. Kaydet

**Önemli**: Ters kayıt, açık bir dönemde oluşturulmalıdır.

---

## Sık Yapılan Hatalar ve Çözümleri

### Hata 1: Ödeme yaptım ama fatura kapanmadı
**Sebep**: Dağıtım yapılmadı.

**Çözüm**: 
1. Ödeme detay sayfasına gidin
2. "Dağıtım Yap" butonuna tıklayın
3. Faturayı seçin ve tutarı girin
4. Kaydet

---

### Hata 2: Dağıtım tutarı belge tutarından fazla
**Sebep**: Belgenin kalan borcu, dağıtım tutarından az.

**Çözüm**: 
- Dağıtım tutarını, belgenin kalan borcuna eşit veya daha az yapın
- Belge detay sayfasında "Kalan Borç" tutarını kontrol edin

---

### Hata 3: Kilitli dönemde değişiklik yapamıyorum
**Sebep**: Dönem kilitli.

**Çözüm**: 
- Ters kayıt oluşturun (yukarıdaki "Dönem Kilidi" bölümüne bakın)
- Veya dönem kilidini açın (sadece yöneticiler)

---

### Hata 4: Çek tahsil edildi ama ödeme görünmüyor
**Sebep**: Çek tahsil işlemi sırasında hata oluştu.

**Çözüm**: 
1. Çek detay sayfasını kontrol edin
2. Durum "Tahsil Edildi" ise, ödeme otomatik oluşturulmuştur
3. "Ödeme / Tahsilat" sayfasından ödemeyi kontrol edin
4. Eğer ödeme yoksa, manuel olarak oluşturun ve çek tahakkukuna dağıtın

---

### Hata 5: Cari ekstresinde tutarsızlık var
**Sebep**: Dağıtımlar eksik veya yanlış yapılmış olabilir.

**Çözüm**: 
1. Cari ekstresini kontrol edin
2. Her belge için "Ödenen Tutar" ve "Kalan Borç" tutarlarını kontrol edin
3. Eksik dağıtımları tamamlayın
4. Yanlış dağıtımları iptal edin ve yeniden yapın

---

## İpuçları

1. **Her işlemden sonra kontrol edin**: Belge veya ödeme oluşturduktan sonra detay sayfasını kontrol edin.

2. **Dağıtım yapmayı unutmayın**: Ödeme yaptıktan sonra mutlaka dağıtım yapın.

3. **Dönem kilidini düzenli kullanın**: Her ay sonunda dönemi kilitleyin.

4. **Raporları düzenli kontrol edin**: Haftalık veya aylık raporları kontrol ederek tutarsızlıkları erken yakalayın.

5. **Açıklama ekleyin**: Her belge ve ödemeye açıklama ekleyin, ileride hatırlamanız kolaylaşır.

---

## Destek

Sorularınız veya sorunlarınız için sistem yöneticisine başvurun.
