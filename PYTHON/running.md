# CashTrack — Panduan Menjalankan Aplikasi

## Daftar Isi
1. [Menjalankan di Mac M1 (Lokal)](#1-menjalankan-di-mac-m1-lokal)
2. [Menjalankan di VPS Ubuntu 22.04](#2-menjalankan-di-vps-ubuntu-2204)
3. [Setup Telegram Webhook](#3-setup-telegram-webhook)
4. [Menjalankan Otomatis saat Reboot (systemd)](#4-menjalankan-otomatis-saat-reboot-systemd)

---

## 1. Menjalankan di Mac M1 (Lokal)

### Prasyarat
- Python 3.11+ → `brew install python@3.11`
- MySQL → `brew install mysql` kemudian `brew services start mysql`
- Git

### Langkah-langkah

**1. Masuk ke folder PYTHON**
```bash
cd /path/to/CashTrack/PYTHON
```

**2. Buat virtual environment**
```bash
python3 -m venv venv
source venv/bin/activate
```

**3. Install dependencies**
```bash
pip install -r requirements.txt
```

**4. Buat database MySQL**
```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS finance_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

**5. Sesuaikan file `.env`**

Buka file `.env` dan sesuaikan:
```env
APP_SECRET_KEY=ganti-ini-dengan-string-acak-yang-sangat-panjang-dan-aman

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=finance_dashboard
DB_USERNAME=root
DB_PASSWORD=         # kosong jika MySQL lokal tanpa password

TELEGRAM_BOT_TOKEN=isi_token_bot_kamu
TELEGRAM_WEBHOOK_URL=https://domain-kamu.com/api/telegram/webhook
TELEGRAM_ALLOWED_CHAT_ID=isi_chat_id_kamu
```

**6. Buat tabel dan admin user pertama kali**
```bash
python seed.py
```
Output: `Admin user created: admin@cashtrack.test / password`

**7. Jalankan aplikasi**
```bash
uvicorn app.main:app --reload --host 127.0.0.1 --port 8000
```

**8. Buka di browser**
```
http://localhost:8000
```
Login dengan: `admin@cashtrack.test` / `password`

---

### Tips Mac M1

- Kalau `pip install` gagal karena `bcrypt` atau `cryptography`, jalankan:
  ```bash
  pip install --upgrade pip setuptools wheel
  pip install -r requirements.txt
  ```
- Kalau MySQL tidak bisa konek, cek service-nya:
  ```bash
  brew services list
  brew services restart mysql
  ```
- Flag `--reload` membuat server auto-restart saat kode diubah — **jangan pakai di production**.

---

## 2. Menjalankan di VPS Ubuntu 22.04

### Prasyarat di VPS
- Python 3.11+ (Ubuntu 22.04 sudah punya Python 3.10, upgrade jika perlu)
- MySQL Server
- (Opsional) Nginx sebagai reverse proxy

### A. Persiapan di VPS

**1. SSH ke VPS**
```bash
ssh user@IP_VPS
```

**2. Install Python 3.11 dan pip**
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y python3.11 python3.11-venv python3.11-dev python3-pip build-essential
```

**3. Install MySQL Server**
```bash
sudo apt install -y mysql-server
sudo systemctl start mysql
sudo systemctl enable mysql

# Amankan instalasi MySQL
sudo mysql_secure_installation
```

**4. Buat database dan user MySQL**
```bash
sudo mysql -u root -p
```
Jalankan di dalam MySQL:
```sql
CREATE DATABASE finance_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cashtrack'@'localhost' IDENTIFIED BY 'password_kuat_di_sini';
GRANT ALL PRIVILEGES ON finance_dashboard.* TO 'cashtrack'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### B. Upload dan Setup Aplikasi

**5. Upload file ke VPS**

Opsi A — via Git:
```bash
git clone https://github.com/username/CashTrack.git /var/www/cashtrack
cd /var/www/cashtrack/PYTHON
```

Opsi B — via SCP dari Mac:
```bash
# Di Mac, upload folder PYTHON ke VPS
scp -r /path/to/CashTrack/PYTHON user@IP_VPS:/var/www/cashtrack
```

**6. Masuk ke folder dan buat virtualenv**
```bash
cd /var/www/cashtrack  # sesuaikan path
python3.11 -m venv venv
source venv/bin/activate
```

**7. Install dependencies**
```bash
pip install --upgrade pip
pip install -r requirements.txt
```

**8. Buat dan sesuaikan `.env`**
```bash
cp .env .env.backup  # backup dulu jika ada
nano .env
```

Isi `.env` untuk VPS:
```env
APP_SECRET_KEY=buat-string-panjang-acak-minimal-32-karakter-di-sini

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=finance_dashboard
DB_USERNAME=cashtrack
DB_PASSWORD=password_kuat_di_sini

TELEGRAM_BOT_TOKEN=token_bot_kamu
TELEGRAM_WEBHOOK_URL=https://domain-kamu.com/api/telegram/webhook
TELEGRAM_ALLOWED_CHAT_ID=chat_id_kamu
```

> **Tips buat APP_SECRET_KEY:**
> ```bash
> python3 -c "import secrets; print(secrets.token_hex(32))"
> ```

**9. Buat tabel dan admin user**
```bash
python seed.py
```

**10. Test jalankan dulu**
```bash
uvicorn app.main:app --host 0.0.0.0 --port 8000
```
Buka `http://IP_VPS:8000` di browser — kalau berhasil, lanjut ke langkah berikutnya.

Tekan `Ctrl+C` untuk stop.

---

### C. Setup Nginx sebagai Reverse Proxy

**11. Install Nginx**
```bash
sudo apt install -y nginx
```

**12. Buat konfigurasi Nginx**
```bash
sudo nano /etc/nginx/sites-available/cashtrack
```

Isi konfigurasi:
```nginx
server {
    listen 80;
    server_name domain-kamu.com www.domain-kamu.com;  # atau IP jika belum punya domain

    client_max_body_size 10M;

    location /static/ {
        alias /var/www/cashtrack/static/;
        expires 30d;
    }

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 60s;
    }
}
```

**13. Aktifkan konfigurasi**
```bash
sudo ln -s /etc/nginx/sites-available/cashtrack /etc/nginx/sites-enabled/
sudo nginx -t          # test konfigurasi
sudo systemctl reload nginx
```

---

## 3. Setup Telegram Webhook

Setelah aplikasi jalan di VPS dengan domain dan HTTPS:

```bash
# Set webhook ke Telegram
curl -X POST "https://api.telegram.org/bot<TOKEN>/setWebhook" \
     -d "url=https://domain-kamu.com/api/telegram/webhook"
```

Cek status webhook:
```bash
curl "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"
```

> Telegram **wajib HTTPS** untuk webhook. Gunakan Certbot untuk SSL gratis:
> ```bash
> sudo apt install -y certbot python3-certbot-nginx
> sudo certbot --nginx -d domain-kamu.com
> ```

---

## 4. Menjalankan Otomatis saat Reboot (systemd)

**Buat service file**
```bash
sudo nano /etc/systemd/system/cashtrack.service
```

Isi:
```ini
[Unit]
Description=CashTrack FastAPI App
After=network.target mysql.service

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/cashtrack
Environment="PATH=/var/www/cashtrack/venv/bin"
ExecStart=/var/www/cashtrack/venv/bin/uvicorn app.main:app --host 127.0.0.1 --port 8000 --workers 1
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

> Sesuaikan `WorkingDirectory` dan `ExecStart` path dengan lokasi file kamu.

**Aktifkan dan jalankan service**
```bash
sudo systemctl daemon-reload
sudo systemctl enable cashtrack
sudo systemctl start cashtrack

# Cek status
sudo systemctl status cashtrack
```

**Perintah berguna**
```bash
sudo systemctl stop cashtrack       # stop
sudo systemctl restart cashtrack    # restart
sudo journalctl -u cashtrack -f     # lihat log realtime
```

---

## Ringkasan Perintah Harian

| Tujuan | Perintah |
|--------|----------|
| Start di Mac (dev) | `source venv/bin/activate && uvicorn app.main:app --reload` |
| Start di VPS (manual) | `sudo systemctl start cashtrack` |
| Stop di VPS | `sudo systemctl stop cashtrack` |
| Restart setelah update kode | `sudo systemctl restart cashtrack` |
| Lihat log VPS | `sudo journalctl -u cashtrack -f` |
| Cek status | `sudo systemctl status cashtrack` |
