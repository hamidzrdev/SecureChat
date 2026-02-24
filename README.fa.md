
<p align="center">
  <img src="docs/logo.png" width="180" alt="Secure Chat Logo"/>
</p>

<h1 align="center">Secure Chat</h1>

<p align="center">
  🇬🇧 <a href="README.md">View English Version</a>
</p>

---

Secure Chat یک برنامه چت بلادرنگ مبتنی بر Laravel و WebSocket (Laravel Reverb) است.

صمیمانه امیدوارم مردم سرزمینم هیچ‌گاه نیاز به استفاده از چنین برنامه‌هایی نداشته باشند؛  
اما اگر روزی به آن نیاز پیدا کردند، تلاش من این بوده است که در حد توان خودم بهترین و شایسته‌ترین ابزار ممکن را در اختیارشان قرار دهم.

---

## 🎬 پیش‌نمایش

<p align="center">
  <img src="docs/movie.gif" width="700"/>
</p>

---

## 📸 اسکرین‌شات‌ها

<p align="center">
  <img src="docs/1.png" width="250"/>
  <img src="docs/2.png" width="250"/>
  <img src="docs/3.png" width="250"/>
</p>

---

## 💬 امکانات

- چت عمومی (Public Room)
- چت خصوصی یک‌به‌یک
- چت خصوصی رمزدار مبتنی بر Passphrase
- ارسال پیام بلادرنگ با WebSocket (Laravel Reverb)
- نمایش کاربران آنلاین
- ارسال تصویر با اعتبارسنجی سمت سرور
- حذف خودکار پیام‌ها بر اساس TTL
- پردازش صف‌ها (Queue Workers)
- اجرای Jobهای زمان‌بندی‌شده (Scheduler)
- فعال/غیرفعال‌سازی قابلیت‌ها از طریق فایل config

---

## ⚙️ پیش‌نیازها

- PHP 8.3+
- MySQL
- Laravel Reverb
- Composer
- Node.js & NPM

---

## 🛠 نصب (Manual - Linux)

```bash
composer install

cp .env.example .env
php artisan key:generate

php artisan migrate
php artisan chat:setup

npm install
npm run build

php artisan optimize:clear
```

---

## 🚀 اجرای برنامه

### اجرای اپلیکیشن (Port 8000)

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

دسترسی از طریق:

http://localhost:8000

---

### اجرای Queue Worker

```bash
php artisan queue:work
```

---

### اجرای Scheduler

```bash
php artisan schedule:work
```

---

### اجرای WebSocket Server (Reverb – Port 8080)

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

در فایل `.env`:

REVERB_PORT=8080

---

## 🐳 اجرای Docker

```bash
docker build -t secure-chat .
docker run -d -p 8000:8000 -p 8080:8080 secure-chat
```

یا:

```bash
docker-compose up -d
```
