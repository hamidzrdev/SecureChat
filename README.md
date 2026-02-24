# Secure Chat (Laravel 12 + Livewire 4)

Secure Chat is a Laravel 12 + Livewire 4 application with:
- public and private chat
- Reverb (realtime WebSocket)
- queue workers
- scheduler jobs
- image upload support

## Requirements

### Docker (recommended)
- Docker Desktop (or Docker Engine + Docker Compose plugin)

### Local (without Docker)
- PHP 8.3+
- Composer 2+
- Node.js 20+ and npm
- MySQL 8+
- Redis 7+

## Docker Setup (recommended)

This is the primary and stable setup for this project.

1. Clone and enter project directory:

```bash
git clone <repo-url>
cd schatb
```

2. Create `.env` if it does not exist:

```bash
cp .env.example .env
```

3. Make sure DB values in `.env` are compatible with Docker services:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=schat
DB_USERNAME=schatb
DB_PASSWORD=secret
```

4. Start services:

```bash
docker compose up -d --build
```

5. Endpoints:
- App: `http://localhost:8000`
- Reverb: `http://localhost:8080`
- MySQL: `localhost:3306`
- Redis: `localhost:6379`

### Services started by Docker Compose
- `app` (Laravel HTTP server)
- `queue` (`php artisan queue:work`)
- `reverb` (`php artisan reverb:start`)
- `scheduler` (`schedule:run` every 60 seconds)
- `mysql`
- `redis`

### Useful Docker commands

```bash
docker compose ps
docker compose logs -f app
docker compose logs -f queue
docker compose logs -f scheduler
docker compose logs -f reverb
docker compose logs -f mysql
docker compose down
```

## Local Setup (without Docker)

1. Install dependencies:

```bash
composer install
npm install
```

2. Create and configure `.env`:

```bash
cp .env.example .env
php artisan key:generate
```

3. Configure local database and redis:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=schat
DB_USERNAME=<your_mysql_user>
DB_PASSWORD=<your_mysql_password>

REDIS_HOST=127.0.0.1
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
```

4. Run migrations:

```bash
php artisan migrate
```

5. Start app services:

```bash
composer dev
php artisan schedule:work
```

`composer dev` runs `serve + queue + reverb + vite`.  
Run `schedule:work` in a separate terminal.

## Testing and Code Style

```bash
php artisan test --compact
vendor/bin/pint --dirty
```

## Troubleshooting

### HTTP 500 on login or chat
- Check `.env` and make sure there is no leading space before `DB_*` keys.
- For Docker, verify:
  - `DB_HOST=mysql`
  - `DB_USERNAME=schatb`
  - `DB_PASSWORD=secret`

Then run:

```bash
docker compose up -d --force-recreate app queue reverb scheduler
docker compose exec app php artisan optimize:clear
```

### Vite manifest error
If you see `Unable to locate file in Vite manifest`:

```bash
npm run build
```

For development mode:

```bash
npm run dev
```

### Clear Laravel caches

```bash
php artisan optimize:clear
```

## Notes

No special seed is required for first login.  
Open the Login page and create a new `Chat ID`.
