# TurnLog API — Claude Code Guide

## Project context
TurnLog is a Laravel API for cataloguing vinyl (LP) collections. It sits in
front of the **Discogs API**: users search releases or scan a barcode, and add
matches to their own collection. The client is **iOS now, Android later**, so
this API is the single contract both apps consume. It replaces an earlier
Supabase backend — we now own the whole backend to keep it tailored and to
showcase the architecture. **This repo is a portfolio piece: code quality and
clear design matter as much as features.**

## Architecture — three strictly separated layers

1. **Discogs client layer** — one class owning all Discogs HTTP calls: token,
   retries, rate limiting. Hidden behind an interface (`DiscogsClient`) so tests
   bind a fake. Nothing else in the app knows Discogs exists.
2. **Domain / service layer** — plain service classes (`CollectionService`,
   `ReleaseImporter`, …) holding the actual behaviour: look up a barcode, cache
   the release locally, add it to a user's shelf. This is where the interesting
   logic lives.
3. **HTTP layer** — controllers only: take a validated request, call a service,
   return a resource. No logic here.

Request flow:
`Route → FormRequest (validate) → Controller (delegate) → Service (work) → API Resource (shape output)`

## Data
- Own tables: `users`, `collections`, `collection_items`.
- Local `releases` cache: Discogs ID, title, artist, year, cover URL, plus a
  `raw` JSON column. Store just enough to render a shelf without re-hitting
  Discogs — don't mirror their whole catalogue.
- Postgres (or MySQL). Redis for caching Discogs responses and rate limiting.

## Conventions
- `declare(strict_types=1)` in every file; param + return types everywhere.
- Validation → Form Requests. Output → API Resources. **Never return raw
  Eloquent models.**
- Business logic → service classes. Controllers and models stay thin.
- Schema changes → migrations only.
- Tests → **Pest**. Every feature slice ships with feature tests; services get
  unit tests against a faked Discogs client.
- Follow PSR-12; run **Pint** before calling work done.

## Guardrails
- No business logic or DB queries in controllers.
- No new Composer packages without asking first — explain why it's needed.
- Don't invent Discogs endpoints; confirm against their docs.
- Build **one vertical slice at a time** (request + service + resource + tests),
  not broad scaffolding. Stop and let me review each slice before moving on.
