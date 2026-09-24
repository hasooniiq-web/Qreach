# Qreach — SmartTag

SmartTag is a vehicle communication platform: a vehicle owner attaches a SmartTag QR (and optionally NFC) sticker to their car, and anyone who scans it can reach the owner — without installing an app — for things like "your lights are on," "you're blocking me in," or a genuine emergency, while the owner's phone number stays private by default.

This repository currently contains the **backend platform**: a Laravel 12 application serving the customer/mobile REST API, the public no-login scan experience, and a fully Arabic RTL admin dashboard. The Flutter mobile app is not yet started (see [Known Limitations](#known-limitations)).

## Repository layout

```
backend/     Laravel 12 application — API, admin dashboard, public scan pages
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
