# Cinema

Легальный гибридный онлайн-кинотеатр для стран СНГ: русскоязычный каталог, официальные варианты просмотра, трейлеры, избранное и продолжение просмотра. Проект не хранит фильмы и не обходит DRM, CORS или геоблокировки.

Стек: PHP 8.3+, Laravel 13, Sanctum, Filament 5, Vue 3, Vue Router, Pinia, Vite, MySQL/SQLite и Redis. Архитектура и решения описаны в [PLAN.md](PLAN.md), REST-контракт — в [openapi.yaml](openapi.yaml).

## Быстрый запуск (SQLite)

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
npm install
npm run build
php artisan serve
```

Откройте:

- приложение: <http://127.0.0.1:8000>
- админ-панель: <http://127.0.0.1:8000/admin>
- health check: <http://127.0.0.1:8000/up>
- sitemap: <http://127.0.0.1:8000/sitemap.xml>

Для разработки с Vite, очередью и логами:

```bash
composer run dev
```

## Docker (MySQL + Redis)

```bash
cp .env.example .env
php artisan key:generate
docker compose build
docker compose up -d
docker compose exec app php artisan migrate --seed
```

Приложение будет доступно на <http://localhost:8080>. Для остановки: `docker compose down`. Данные MySQL сохраняются в volume `mysql-data`.

## Demo-аккаунты

Пароль для всех: `password`.

- `admin@example.com` — администратор;
- `editor@example.com` — редактор;
- `user@example.com` — пользователь.

Пользователи с ролью `user` не имеют доступа к `/admin`.

## TMDB

Добавьте серверный ключ в `.env`:

```dotenv
TMDB_API_KEY=
TMDB_BASE_URL=https://api.themoviedb.org/3
```

Ключ не имеет префикса `VITE_` и не включается в клиентский bundle. Без ключа приложение работает на demo-данных. Импорт одной карточки из админки или команды:

```bash
php artisan tmdb:import movie 21028 --publish
```

Перед публичным запуском необходимо отдельно проверить правила атрибуции и коммерческого использования TMDB.

## Видеопровайдеры

Режимы: `DIRECT`, `EMBED`, `EXTERNAL`, `TRAILER`, `UNAVAILABLE`. Приоритет выбирается сервером в этом порядке. `DIRECT` и `EMBED` нельзя сохранить без `legal_basis`; iframe дополнительно требует allowlist:

```dotenv
VIDEO_EMBED_HOSTS=player.example.org,video.partner.example
```

Demo seed содержит только публичный тестовый MP4, внешнюю заглушку и trailer-заглушку. Они не являются production-интеграциями. Реальные ссылки добавляются только после юридической и технической проверки.

## Проверки

```bash
php artisan test
vendor/bin/pint --test
npm run build
composer audit
npm audit
```

## Ограничения MVP

- реальные видеопровайдеры и договоры не подключены;
- сброс/подтверждение email, семейные профили и социальный вход оставлены на следующий этап;
- server-rendered метатеги и sitemap реализованы, но полноценный SSR требует отдельной оценки;
- проверка HLS, fullscreen и управления пультом на TCL 65C6K выполняется вручную;
- production требует HTTPS, отдельные секреты, backup MySQL, мониторинг queue/logs и юридическую проверку для каждой страны.
