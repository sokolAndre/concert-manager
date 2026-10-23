# Concert Manager

**Concert Manager** is an event and performance management application for musicians, bands, and small ensembles, built with Laravel, PostgreSQL, and the Telegram Bot API.

The goal is to keep concert schedules, rehearsals, venues, setlists, reminders, and performance finances in one place—across multiple musical projects.

> **Project status:** Active development. The Telegram calendar and core event workflows are working; some financial and setlist features are currently available at the backend/domain level only.

## Features

### Available through Telegram

- **Unified calendar** across multiple musical projects
- **Schedule commands** for today, this week, this month, and upcoming events
- **Interactive monthly navigation** with previous/next month buttons, updating the same message
- **Paginated upcoming events** with previous/next navigation
- **Guided event creation** using conversation state and inline buttons
- **Event details and management:** view, cancel, restore, and permanently delete with confirmation
- **Event categories:** concerts, rehearsals, meetings, and other activities

### Implemented in the backend

- **Concert finances:** agreed fees, multiple payments, outstanding balances, and payment status
- **Setlists:** song catalog, ordered sets, reusable templates, and concert-specific copies
- **Venues and contacts:** structured information associated with events
- **Reminders:** scheduled notifications to Telegram destinations, with Laravel scheduler integration
- **Project-to-chat associations** for private and group Telegram destinations

## Tech Stack

| Component | Technology |
| --- | --- |
| Backend | PHP 8.4, Laravel 13 |
| Database | PostgreSQL 17 |
| Bot | Telegram Bot API (long polling, inline keyboards) |
| Web server | Nginx, PHP-FPM |
| Infrastructure | Docker Compose, Ubuntu 24.04 VPS |
| Scheduling | Laravel Artisan commands and scheduler |

## Architecture

The application models multiple **Projects**, each containing **Events**. Concerts can have additional **Concert Details**, including fees and **Payments**. **Setlists** organize songs into ordered sets and can be copied for individual concerts. **Reminders** connect events to Telegram destinations and scheduled notification times.

The Telegram interface uses database-backed conversation state for multi-step actions and callback queries for interactive navigation.

**Time zones:** Dates are stored in UTC and presented in a configurable local timezone (`Europe/Lisbon` by default).

## Running Locally

The project uses Docker Compose. You will need Docker and Docker Compose installed.

```bash
git clone https://github.com/sokolAndre/concert-manager.git
cd concert-manager
cp .env.example .env
```

Set the database credentials and `TELEGRAM_BOT_TOKEN` in `.env`. Then run:

```bash
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

To run the Telegram bot during development:

```bash
docker compose exec app php artisan telegram:poll
```

**Important:** Run only one polling process for a bot token at a time. Multiple concurrent `getUpdates` consumers cause Telegram API `409 Conflict` errors.

> Local setup may require environment-specific permissions or configuration. Never commit your `.env` file or Telegram bot token.

## Deployment Status

A Docker-based deployment baseline is configured on an Ubuntu VPS, including PostgreSQL, Nginx, and scheduled reminder execution. Persistent production Telegram polling is planned; the bot is currently operated through a development polling process.

## Roadmap

- [x] Multi-project event model
- [x] Telegram event creation and event cards
- [x] Cancel, restore, and delete workflows
- [x] Interactive monthly calendar
- [x] Pagination for upcoming events
- [x] Backend models for payments, setlists, and reminders
- [ ] Edit event fields through Telegram
- [ ] Telegram workflows for payments and setlists
- [ ] Persistent production polling worker
- [ ] Automated tests and improved operational resilience
- [ ] Web administration interface

## Contributing and License

The project is under active development. Contribution guidelines and an open-source license have not yet been added. Until a license is published, the repository's source code should not be assumed to grant reuse or redistribution rights.
