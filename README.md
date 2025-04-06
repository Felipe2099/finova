# Gelir Gider Takip Sistemi

Bu uygulama, kişisel ve kurumsal finansal yönetim için geliştirilmiş kapsamlı bir sistemdir.

## Özellikler

- Gelir ve gider takibi
- Çoklu para birimi desteği
- Hesap yönetimi (Banka, Kredi Kartı, Kripto, Sanal POS)
- Müşteri ve tedarikçi yönetimi
- Borç ve alacak takibi
- Kredi ve kredi kartı yönetimi
- Proje yönetimi
- Tasarruf ve yatırım planlaması
- Detaylı raporlama ve analiz

## Gereksinimler

- PHP 8.2 veya üzeri
- MySQL 5.7 veya üzeri
- Composer
- Node.js ve npm

## Kurulum

1. Projeyi klonlayın:
```bash
git clone https://github.com/mehmetmasa/gelir-gider.git
cd gelir-gider
```

2. Composer bağımlılıklarını yükleyin:
```bash
composer install
```

3. .env dosyasını oluşturun:
```bash
cp .env.example .env
```

4. Uygulama anahtarını oluşturun:
```bash
php artisan key:generate
```

5. Veritabanı ayarlarını yapın:
- .env dosyasında DB_DATABASE, DB_USERNAME ve DB_PASSWORD değerlerini güncelleyin

6. Veritabanı tablolarını oluşturun:
```bash
php artisan migrate
```

7. Örnek verileri yükleyin:
```bash
php artisan db:seed
```

8. NPM bağımlılıklarını yükleyin:
```bash
npm install
```

9. Frontend varlıklarını derleyin:
```bash
npm run dev
```

10. Uygulamayı çalıştırın:
```bash
php artisan serve
```

## Varsayılan Kullanıcı Bilgileri

- E-posta: admin@admin.com
- Şifre: admin123