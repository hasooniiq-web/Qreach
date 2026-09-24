# Qreach — SmartTag

SmartTag is a vehicle communication platform: a vehicle owner attaches a SmartTag QR (and optionally NFC) sticker to their car, and anyone who scans it can reach the owner — without installing an app — for things like "your lights are on," "you're blocking me in," or a genuine emergency, while the owner's phone number stays private by default.

This repository contains: a Laravel 12 backend (customer/mobile REST API, the public no-login scan experience, and a fully Arabic RTL admin dashboard), and a Flutter customer mobile app covering login, vehicle management, and QR tag activation. See each directory's own README and `docs/ARCHITECTURE.md`'s status section for exactly what's real versus scaffolded.

## Repository layout

```
backend/     Laravel 12 application — API, admin dashboard, public scan pages
mobile/      Flutter customer app — login, vehicles, QR activation
docs/        Architecture, security, and operational documentation
```

## Requirements

- PHP 8.2+
- PostgreSQL 14+
- Node.js 18+ (for building dashboard assets)
- Composer 2

## Local setup

```bash
cd backend
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
# Configure DB_* in .env for a Postgres database you've already created
php artisan migrate
php artisan db:seed
php artisan serve
```

The seeder prints a one-time bootstrap admin password to the console — log in at `/admin/login` and you'll be forced to change it immediately.

For notification delivery to actually happen, also run a queue worker:

```bash
php artisan queue:work
```

### Mobile app

```bash
cd mobile
flutter pub get
flutter gen-l10n
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1  # 10.0.2.2 = host, from an Android emulator
```

See `mobile/README.md` for what's actually been verified to work (analyze/build/test all pass; not yet run on a physical device or emulator).

## Documentation

| Doc | Covers |
|---|---|
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | System components, request flow, tech stack |
| [docs/DATABASE.md](docs/DATABASE.md) | Schema, key design decisions |
| [docs/API.md](docs/API.md) | REST API reference |
| [docs/SECURITY.md](docs/SECURITY.md) | Security controls by category |
| [docs/THREAT_MODEL.md](docs/THREAT_MODEL.md) | Actors, assets, threats, mitigations |
| [docs/OWASP_MAPPING.md](docs/OWASP_MAPPING.md) | OWASP Top 10:2025 and API Security Top 10 mapping |
| [docs/QR_SECURITY.md](docs/QR_SECURITY.md) | Tag token design, activation concurrency safety |
| [docs/UI_UX_DESIGN_SYSTEM.md](docs/UI_UX_DESIGN_SYSTEM.md) | Brand, typography, component patterns |
| [docs/ARCHITECTURE_DECISIONS.md](docs/ARCHITECTURE_DECISIONS.md) | Ambiguities resolved and why |
| [docs/TESTING.md](docs/TESTING.md) | What's tested and how to run it |
| [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) | Production deployment checklist |

## Status

See the "Features completed / partial / not implemented" breakdown at the end of `docs/ARCHITECTURE.md` for an honest accounting of what's real versus what's scaffolded.
