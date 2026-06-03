# CashTrack — Finance Dashboard

Dashboard keuangan harian berbasis web dengan integrasi Telegram Bot. Dibangun dengan Laravel 11, Tailwind CSS, Alpine.js, dan Chart.js.

## Tech Stack

- **Backend**: Laravel 11 (PHP 8.2+)
- **Database**: MySQL 8
- **Frontend**: Blade + Tailwind CSS v4 + Alpine.js + Chart.js
- **Build**: Vite
- **Bot**: Telegram Bot API (webhook)
- **Auth**: Laravel built-in auth

## Fitur

- Dashboard dengan grafik bar & line (Chart.js)
- Kartu statistik dengan animasi count-up
- Filter bulan/tahun dinamis tanpa reload
- CRUD transaksi (pemasukan & pengeluaran)
- Riwayat transaksi dengan filter & pagination
- Export CSV
- Telegram Bot dengan state management (pemasukan, pengeluaran, ringkasan)
- Responsive (mobile, tablet, desktop)
- Dark mode

## Instalasi

### 1. Clone & install dependencies

```bash
git clone <repo-url> cashtrack
cd cashtrack
composer install
npm install
```

### 2. Setup environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` dan isi:
```env
DB_DATABASE=finance_dashboard
DB_USERNAME=root
DB_PASSWORD=your_password

TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_WEBHOOK_URL=https://yourdomain.com/api/telegram/webhook
TELEGRAM_ALLOWED_CHAT_ID=  # opsional
```

### 3. Setup database

```bash
# Buat database
mysql -u root -e "CREATE DATABASE finance_dashboard CHARACTER SET utf8mb4;"

# Jalankan migration
php artisan migrate

# Isi data dummy (30 transaksi + 1 user admin)
php artisan db:seed
```

### 4. Build assets

```bash
# Development
npm run dev

# Production
npm run build
```

### 5. Jalankan server

```bash
php artisan serve
```

Buka http://localhost:8000

### 6. Login

- Email: `admin@cashtrack.test`
- Password: `password`

### 7. Setup Telegram Webhook

Setelah deploy ke server dengan HTTPS:

```bash
php artisan start:bot
```

Command bot:

```bash
php artisan start:bot   # aktifkan webhook
php artisan stop:bot    # hapus webhook
php artisan status:bot  # cek status webhook
```

## Struktur File Utama

```
app/
├── Http/Controllers/
│   ├── DashboardController.php     # Dashboard + JSON API
│   ├── TransactionController.php   # CRUD + Export CSV
│   └── TelegramController.php      # Webhook handler
├── Models/
│   └── Transaction.php             # Model dengan scopes
└── Services/
    ├── TransactionService.php      # Logika statistik
    └── TelegramService.php         # Bot logic + state management

database/
├── migrations/
│   └── ..._create_transactions_table.php
└── seeders/
    ├── DatabaseSeeder.php
    └── TransactionSeeder.php       # 30 data dummy

resources/views/
├── layouts/app.blade.php           # Layout dengan sidebar
├── dashboard/index.blade.php       # Dashboard utama
├── transactions/
│   ├── index.blade.php             # Riwayat + filter
│   └── create.blade.php            # Form tambah
└── auth/login.blade.php            # Halaman login

routes/
├── web.php                         # Web routes (auth required)
└── api.php                         # Telegram webhook
```

## Format Telegram Bot

Kirim pesan ke bot:

```
nama,nominal,keterangan
```

Contoh:
```
Gaji bulanan,5000000,transfer BCA
Beli ayam geprek,15000,sarapan pagi
```

Commands: `/start`, dan tombol inline keyboard (💰 Pemasukan, 💸 Pengeluaran, 📊 Ringkasan, ❓ Bantuan)
