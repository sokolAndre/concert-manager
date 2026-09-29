# Concert Manager

Concert Manager is a Laravel-based application and Telegram bot for managing
music projects, concerts, meetings, rehearsals, setlists, payments and reminders.

The system is intended to provide both project-specific schedules and a unified
personal calendar across all music projects.

## Core concepts

### Project

Represents a band, duo, solo project or other musical project.

Examples:
- Rocky Shore Melodies
- ANDEMIC DUO
- Solo

A project may contain:
- members
- events
- setlists
- Telegram chats
- default reminder settings

Projects can be active or inactive.

---

### Event

A common calendar entity belonging to a project.

Event types:
- concert
- meeting
- rehearsal
- other

Common fields:
- project
- type
- title
- description
- start date/time
- end date/time
- location / venue
- organizer/contact
- status
- notes

Possible statuses:
- planned
- confirmed
- cancelled
- completed

Concerts, meetings and rehearsals should not be stored in completely separate
calendar systems. They share the Event entity so that all events can be easily
displayed in one unified calendar.

Concert-specific information may be stored separately from the common Event
data.

---

## Concerts

Concert events may additionally contain:

- fee
- currency
- payment information
- setlist
- venue
- organizer/contact
- performance notes

Examples include:
- regular venue concerts
- weddings
- corporate events
- festivals
- private events

---

## Payments

The application should track whether concert fees have actually been received.

Payment state should not be represented only by a boolean `paid` flag.

Possible states:
- unpaid
- partially paid
- paid

Individual payments should be stored separately.

Example:

Concert fee: €300

Payments:
- €70 advance
- €230 final payment

This allows the application to calculate:
- total fee
- amount received
- amount outstanding
- unpaid concerts
- monthly income
- payment history

---

## Songs

Songs should be reusable entities.

Possible fields:
- title
- artist
- key
- duration
- notes

Songs may be used in many different setlists.

---

## Setlists

A project can have reusable performance programs / setlists.

A setlist contains an ordered list of songs.

Examples:
- Standard 2x45
- Wedding 2h
- Acoustic 3h
- Short festival set

A concert can reference a setlist.

The system should eventually support concert-specific variations of a setlist,
including requested or mandatory songs.

The order of songs must be preserved.

---

## Venues and contacts

Events may contain information about:

- venue
- organizer
- contact person
- phone
- Telegram
- WhatsApp
- email
- notes

This should make it possible to understand not only where an event happens but
also who is responsible for it and how to contact them.

---

## Unified calendar

One of the primary features is a unified personal schedule containing events
from all projects.

Required views include:

- today
- upcoming events
- week
- month
- individual project schedule

Example:

October 2026

01 — ANDEMIC DUO — Concert — Velvet
06 — Rocky Shore Melodies — Meeting
10 — Rocky Shore Melodies — Concert — Praça do Povo
12 — ANDEMIC DUO — Concert — Velvet
23 — Rocky Shore Melodies — Wedding — Nini Design

The monthly view should make it easy to see all commitments regardless of
project.

---

## Telegram integration

Telegram is the primary user interface for quick interaction with the system.

The bot should eventually support:

- viewing upcoming events
- viewing today's schedule
- weekly schedule
- monthly schedule
- project-specific schedule
- adding events
- editing events
- cancelling events
- viewing unpaid concerts
- viewing concert details
- viewing setlists
- reminders

Commands and conversational interaction can be designed later.

---

## Telegram chats

Projects may be associated with one or more Telegram chats.

Examples:
- project group chat
- personal direct messages
- organizational chats

Telegram chat information should be stored separately rather than hard-coded
into events.

This allows the same event to generate notifications for different recipients.

---

## Reminders

Events can have multiple reminders.

Examples:

Concert:
- 3 days before → project group
- 1 day before → project group
- 3 hours before → personal DM

Meeting:
- 1 day before → personal DM
- 1 hour before → personal DM

Reminder configuration should allow:
- different offsets
- different Telegram destinations
- multiple reminders for one event

---

## Initial domain model

The approximate domain structure is:

Project
├── Members
├── Events
│   ├── Concert details
│   │   ├── Payments
│   │   └── Setlist
│   └── Reminders
├── Setlists
│   └── Songs
└── Telegram chats

Additional related entities:
- Venue
- Contact / Organizer

The exact database schema should be designed from these domain requirements
rather than treating this document as a fixed database specification.

---

## Technology

Backend:
- PHP 8.4
- Laravel 13
- PostgreSQL 17

Infrastructure:
- Docker
- Docker Compose
- Nginx
- GitHub

Development:
- Windows
- Docker Desktop / WSL2
- PhpStorm
- TortoiseGit

Production:
- Ubuntu 24.04 LTS VPS
- Docker
- Nginx
- PostgreSQL

---

## Development principles

- Keep calendar concepts unified through Event.
- Keep project-specific information associated with Project.
- Do not store secrets in Git.
- `.env` is environment-specific and must remain untracked.
- `.env.example` documents required environment variables.
- PostgreSQL should not be publicly exposed.
- Production and development should use the same Docker-based architecture
  wherever practical.
- Telegram IDs and bot tokens must not be hard-coded.
- Financial history should be preserved as individual payments.
- Setlists and songs should be reusable rather than stored as plain text.
- Database design should allow the application to grow without overengineering
  the first version.

---

## MVP

The first usable version should support:

1. Projects
2. Events
3. Concert-specific details
4. Unified schedule
5. Project-specific schedule
6. Concert fees and payment tracking
7. Songs and setlists
8. Telegram chats
9. Event reminders
10. Telegram bot access to the main functions

Advanced analytics, complex permissions, public web interfaces and other
features can be added later.
