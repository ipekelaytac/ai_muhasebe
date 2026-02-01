# Atölye Ön Muhasebe Sistemi - Teknik Dokümantasyon

## Genel Bakış

Bu sistem, "Tahakkuk (Accrual) + Nakit Hareketi (Payment) + Kapama (Allocation)" prensibine dayalı bir ön muhasebe sistemidir. Çanta üretim atölyesi için tasarlanmış olup; kasa/banka, alacak/borç, tedarikçi/müşteri/çalışan bakiyeleri, bordro/avans/mesai/yemek hakları, çek/senet ve raporlama işlemlerini kapsar.

## Temel Prensipler

### 1. Tahakkuk Önceliği (Accrual First)
- Her borç veya alacak önce bir **Belge (Document)** olarak kaydedilir
- Nakit hareketi olmadan da borç/alacak oluşabilir
- Fatura, bordro, mesai, avans vb. hepsi belge olarak kaydedilir

### 2. Nakit Hareketi (Cash Movement)
- Nakit hareketleri **Ödeme (Payment)** olarak kaydedilir
- Ödeme, belgelerden bağımsız olarak da kaydedilebilir
- Kasa veya banka hesabından giriş/çıkış şeklinde olur

### 3. Kapama/Dağıtım (Allocation)
- Ödeme, bir veya birden fazla belgeye **dağıtılır (allocate)**
- Kısmi ödeme desteklenir
- Fazla ödeme avans olarak kaydedilir

### 4. Bakiye Hesaplama
- **ASLA** tek bir "balance" alanı kaynak olarak kullanılmaz
- Bakiyeler her zaman hesaplanır:
  - Belge bakiyesi = toplam_tutar - sum(kapamalar)
  - Kasa bakiyesi = açılış + sum(girişler) - sum(çıkışlar)
  - Cari bakiyesi = sum(alacak belgeleri) - sum(borç belgeleri)

## Veritabanı Şeması

### Ana Tablolar

```
accounting_periods   -> Dönem yönetimi (ay kilitleme)
parties             -> Cari hesaplar (müşteri/tedarikçi/çalışan)
cashboxes           -> Kasalar
bank_accounts       -> Banka hesapları
expense_categories  -> Gider/Gelir kategorileri
documents           -> Belgeler/Tahakkuklar
document_lines      -> Belge kalemleri (opsiyonel)
payments            -> Ödemeler/Tahsilatlar
payment_allocations -> Kapamalar
cheques             -> Çekler
cheque_events       -> Çek olay geçmişi
document_attachments -> Dosya ekleri
audit_logs          -> Denetim kayıtları
number_sequences    -> Numara serileri
```

### Belge Türleri (Document Types)

| Tür | Açıklama | Yön |
|-----|----------|-----|
| supplier_invoice | Alım Faturası | payable |
| customer_invoice | Satış Faturası | receivable |
| expense_due | Gider Tahakkuku | payable |
| income_due | Gelir Tahakkuku | receivable |
| payroll_due | Maaş Tahakkuku | payable |
| overtime_due | Mesai Tahakkuku | payable |
| meal_due | Yemek Parası Tahakkuku | payable |
| advance_given | Verilen Avans | receivable |
| advance_received | Alınan Avans | payable |
| cheque_receivable | Alınan Çek | receivable |
| cheque_payable | Verilen Çek | payable |
| adjustment_debit | Borç Düzeltme | payable |
| adjustment_credit | Alacak Düzeltme | receivable |
| opening_balance | Açılış Bakiyesi | both |

### Ödeme Türleri (Payment Types)

| Tür | Açıklama | Yön |
|-----|----------|-----|
| cash_in | Kasa Girişi | in |
| cash_out | Kasa Çıkışı | out |
| bank_in | Banka Girişi | in |
| bank_out | Banka Çıkışı | out |
| bank_transfer | Havale/EFT | in/out |
| pos_in | POS Tahsilat | in |
| cheque_in | Çek Tahsilat | in |
| cheque_out | Çek Ödeme | out |
| transfer | Virman | in/out |

### Çek Durumları (Cheque Status)

| Durum | Açıklama |
|-------|----------|
| in_portfolio | Portföyde |
| endorsed | Ciro Edildi |
| deposited | Bankaya Verildi |
| collected | Tahsil Edildi |
| bounced | Karşılıksız |
| cancelled | İptal |
| paid | Ödendi (verilen çek) |
| pending_issue | Verilecek |

## API Endpoints

### Cari Hesaplar (Parties)
```
GET    /api/accounting/parties         - Liste
POST   /api/accounting/parties         - Yeni oluştur
GET    /api/accounting/parties/{id}    - Detay
PUT    /api/accounting/parties/{id}    - Güncelle
DELETE /api/accounting/parties/{id}    - Pasif yap
```

### Belgeler (Documents)
```
GET    /api/accounting/documents           - Liste
POST   /api/accounting/documents           - Yeni oluştur (tahakkuk)
GET    /api/accounting/documents/{id}      - Detay
PUT    /api/accounting/documents/{id}      - Güncelle
POST   /api/accounting/documents/{id}/cancel   - İptal
POST   /api/accounting/documents/{id}/reverse  - Ters kayıt
```

### Ödemeler (Payments)
```
GET    /api/accounting/payments           - Liste
POST   /api/accounting/payments           - Yeni oluştur
GET    /api/accounting/payments/{id}      - Detay
PUT    /api/accounting/payments/{id}      - Güncelle
POST   /api/accounting/payments/{id}/cancel   - İptal
POST   /api/accounting/payments/{id}/reverse  - Ters kayıt
```

### Kapamalar (Allocations)
```
POST   /api/accounting/allocations/payment/{id}          - Dağıt
POST   /api/accounting/allocations/payment/{id}/auto     - Otomatik dağıt
GET    /api/accounting/allocations/payment/{id}/suggestions  - Öneriler
POST   /api/accounting/allocations/payment/{id}/overpayment  - Fazla ödeme
POST   /api/accounting/allocations/{id}/cancel           - İptal
```

### Çekler (Cheques)
```
GET    /api/accounting/cheques           - Liste
POST   /api/accounting/cheques/receive   - Çek al
POST   /api/accounting/cheques/issue     - Çek ver
GET    /api/accounting/cheques/{id}      - Detay
POST   /api/accounting/cheques/{id}/deposit   - Bankaya ver
POST   /api/accounting/cheques/{id}/collect   - Tahsil et
POST   /api/accounting/cheques/{id}/bounce    - Karşılıksız
POST   /api/accounting/cheques/{id}/endorse   - Ciro et
POST   /api/accounting/cheques/{id}/pay       - Öde (verilen çek)
POST   /api/accounting/cheques/{id}/cancel    - İptal
```

### Dönemler (Periods)
```
GET    /api/accounting/periods        - Liste
GET    /api/accounting/periods/open   - Açık dönemler
POST   /api/accounting/periods/lock   - Kilitle
POST   /api/accounting/periods/unlock - Kilit aç
POST   /api/accounting/periods/close  - Kapat
```

### Raporlar (Reports)
```
GET    /api/accounting/reports/cash-bank-balance     - Kasa/Banka bakiyesi
GET    /api/accounting/reports/payables-aging        - Borç yaşlandırma
GET    /api/accounting/reports/receivables-aging     - Alacak yaşlandırma
GET    /api/accounting/reports/employee-dues-aging   - Çalışan hakları yaşlandırma
GET    /api/accounting/reports/cashflow-forecast     - Nakit akış tahmini
GET    /api/accounting/reports/party-statement/{id}  - Cari ekstre
GET    /api/accounting/reports/monthly-pnl           - Aylık kar/zarar
GET    /api/accounting/reports/top-suppliers         - En çok alım yapılan tedarikçiler
GET    /api/accounting/reports/top-customers         - En çok satış yapılan müşteriler
```

## Servisler (Services)

### PeriodService
Dönem yönetimi. Kilitleme, kilit açma, dönem kontrolü.

### PartyService
Cari hesap yönetimi. Müşteri/tedarikçi/çalışan CRUD.

### DocumentService
Belge (tahakkuk) yönetimi. Oluşturma, güncelleme, iptal, ters kayıt.

### PaymentService
Ödeme yönetimi. Oluşturma, güncelleme, iptal, ters kayıt.

### AllocationService
Kapama yönetimi. Dağıtım, otomatik dağıtım, fazla ödeme, iptal.

### ChequeService
Çek yönetimi. Alma, verme, bankaya verme, tahsil, karşılıksız, ciro.

### ReportService
Raporlama. Bakiyeler, yaşlandırma, nakit akışı, ekstre, kar/zarar.

## Kurulum

### 1. Migration
```bash
php artisan migrate
```

### 2. Seeder
```bash
php artisan db:seed --class=AccountingSeeder
```

### 3. Mevcut Verileri Taşıma
```bash
# Önce test modunda çalıştır
php artisan accounting:migrate --company=1 --dry-run

# Sonra gerçek taşıma
php artisan accounting:migrate --company=1
```

## Kullanım Senaryoları

### Senaryo 1: Tedarikçiden Fatura Geldi
```php
// 1. Belge oluştur (tahakkuk)
$document = $documentService->createDocument([
    'company_id' => 1,
    'type' => 'supplier_invoice',
    'party_id' => $supplierId,
    'document_date' => '2026-02-01',
    'due_date' => '2026-03-01',
    'total_amount' => 10000.00,
    'description' => 'Deri malzeme faturası',
]);

// 2. Ödeme yap (nakit veya banka)
$payment = $paymentService->createPayment([
    'company_id' => 1,
    'type' => 'bank_out',
    'party_id' => $supplierId,
    'bank_account_id' => $bankAccountId,
    'payment_date' => '2026-02-15',
    'amount' => 10000.00,
]);

// 3. Kapama yap
$allocationService->allocate($payment, [
    ['document_id' => $document->id, 'amount' => 10000.00],
]);
```

### Senaryo 2: Kısmi Ödeme
```php
// İlk ödeme: 4000 TL
$payment1 = $paymentService->createPayment([...]);
$allocationService->allocate($payment1, [
    ['document_id' => $document->id, 'amount' => 4000.00],
]);
// Belge durumu: partial, kalan: 6000

// İkinci ödeme: 6000 TL
$payment2 = $paymentService->createPayment([...]);
$allocationService->allocate($payment2, [
    ['document_id' => $document->id, 'amount' => 6000.00],
]);
// Belge durumu: settled
```

### Senaryo 3: Çek ile Ödeme Alma
```php
// 1. Çek al
$cheque = $chequeService->receiveCheque([
    'company_id' => 1,
    'party_id' => $customerId,
    'issue_date' => '2026-02-01',
    'due_date' => '2026-04-01',
    'amount' => 15000.00,
    'bank_name' => 'İş Bankası',
]);
// Otomatik olarak cheque_receivable belgesi oluşur

// 2. Bankaya ver
$chequeService->depositCheque($cheque, $bankAccountId);

// 3. Tahsil et (vadede)
$chequeService->collectCheque($cheque);
// Otomatik olarak bank_in ödeme oluşur ve belge kapatılır
```

### Senaryo 4: Mesai Tahakkuku
```php
// Mesai belgesi oluştur
$document = $documentService->createDocument([
    'company_id' => 1,
    'type' => 'overtime_due',
    'party_id' => $employeePartyId,
    'document_date' => '2026-01-31',
    'due_date' => '2026-02-05',
    'total_amount' => 500.00,
    'description' => 'Ocak 2026 mesai: 10 saat x 50 TL',
]);

// Ödeme yap (maaşla birlikte veya ayrı)
$payment = $paymentService->createPayment([...]);
$allocationService->allocate($payment, [...]);
```

## Dönem Kilitleme

```php
// Ocak 2026'yı kilitle
$periodService->lockPeriod(1, 2026, 1, 'Ay sonu kapanış');

// Artık Ocak 2026'ya belge/ödeme eklenemez
// Düzeltme yapmak için:
// 1. Kilit aç (unlock) - özel yetki gerekir
// veya
// 2. Şubat 2026'da ters kayıt oluştur (reverseDocument)
```

## İzinler (Permissions)

| İzin | Açıklama |
|------|----------|
| documents.view | Belgeleri görüntüleme |
| documents.create | Belge oluşturma |
| documents.update | Belge güncelleme |
| documents.delete | Belge silme |
| documents.reverse | Belge iptali/ters kayıt |
| payments.* | Ödeme işlemleri |
| allocations.* | Kapama işlemleri |
| parties.* | Cari hesap işlemleri |
| cheques.* | Çek işlemleri |
| reports.* | Rapor görüntüleme |
| periods.lock | Dönem kilitleme |
| periods.unlock | Dönem kilit açma |
| admin.* | Yönetim işlemleri |

## Test

```bash
# Tüm accounting testlerini çalıştır
php artisan test --filter=Accounting

# Belirli bir test
php artisan test --filter=AllocationServiceTest
```

## Notlar

1. **Silme yerine durum değişikliği**: Finansal kayıtlar silinmez, iptal/ters kayıt yapılır
2. **Audit log**: Tüm değişiklikler audit_logs tablosuna kaydedilir
3. **Transaction safety**: Tüm servis metodları DB transaction içinde çalışır
4. **Period locking**: Kilitli dönemlerde değişiklik yapılamaz
5. **Number sequences**: Thread-safe numara üretimi (lockForUpdate)
