# Team coordination

Shared notes between the two of us (and our Claude sessions). **Read it after every `git pull`,
update it before every push.** Keep entries short; newest first in each log.

## Who does what

| Area | Owner | Branch |
|---|---|---|
| Steps 3–7: auth, catalogue, booking, client area, reviews | Hedi | `hedi` |
| `src/Service/` (price, availability, cancel/refund) | Hedi | `hedi` |
| Step 8: admin area + dashboard | Zeineb | `zeineb` |
| Step 9: fixtures (first) + README | Zeineb | `zeineb` |

## Status

| Step | Status |
|---|---|
| 1. Setup, DB config, base layout | ✅ done |
| 2. Enums, entities, migration | ✅ done |
| 3. Auth (register, login, roles) | ✅ done (branch `hedi`) |
| 4. Public catalogue | ✅ done (branch `hedi`), tested with the fixtures |
| 5. Booking flow | ⏳ next (Hedi) |
| 6. Client area | ⬜ |
| 7. Reviews | ⬜ |
| 8. Admin area | ⏳ in progress (Zeineb): categories ✅ · cars, users, reservations, payments, reviews, dashboard ⬜ |
| 9. Fixtures + README | ✅ fixtures done (Zeineb) · README ⬜ |

Update with ⏳ in progress / ✅ done.

## Rules
- **Entities / migrations:** announce here (Messages) before changing an entity. One migration at a time:
  pull, `make:migration`, push right away.
- **Own local DB each:** after every pull, run `php bin/console doctrine:migrations:migrate`
  (and `doctrine:fixtures:load` if the fixtures changed). Fixtures only reset your own DB.
- **Services:** business logic lives in `src/Service/`. Reuse, don't copy. New service → list it below.
- **Templates:** reuse `_macros.html.twig`, `_flashes.html.twig`, `car/_card.html.twig`,
  `admin/base.html.twig`, `account/_layout.html.twig`.
- Merge into `main` often; `security.yaml` is edited by both of us, expect small conflicts.

## Services and shared code (what exists, how to call it)
Format: `ClassName::method(args)` — what it does — owner.
- `ReservationRepository::findBookedCarIds(start, end)` — ids of cars with a non-cancelled reservation
  overlapping [start, end] (business rule 1, the only place this rule is written) — Hedi
- `CarRepository::search(array $filters)` — available cars matching the `CarSearchType` filters — Hedi
- `ReviewRepository::findAverageRatings()` — `[carId => average]` of approved reviews, one query — Hedi
- Routes: `app_car_index` (`/cars`), `app_car_show` (`/cars/{id}`). `car/_card.html.twig` takes `car` and optional `rating`.

## Decisions (differences from CLAUDE.md)
- `User.phone` and `User.driverLicenseNumber` are nullable (filled on the profile page).
- `Payment.method` is nullable: payment is created with the reservation, before card/cash is chosen.
- `Payment` owns the OneToOne (`payment.reservation_id`); `Reservation::setPayment()` syncs both sides, cascade persist.
- No shared DB: each of us runs a local MariaDB on 127.0.0.1 (Zeineb: XAMPP 10.4.32, Hedi: Debian 11.8).
  Set your own `serverVersion` in `.env.local`. The schema SQL is identical on both versions (checked).
- Registration asks first name, last name, email, password (min 6). No email verification, no terms checkbox;
  the user is logged in right after registering. Logout redirects to `app_home`.
- `security.yaml`: `role_hierarchy ROLE_ADMIN: ROLE_USER`; `access_control` has `^/admin` → ROLE_ADMIN and
  `^/account` → ROLE_USER. Routes outside those prefixes (`/cars/{id}/book`, `/cars/{id}/review`) use `#[IsGranted]`.
  Admin rules are already there — no need to add them again.
- Navbar links are plain URLs until their route exists, then switched to `path()`.
- Admin pages: controllers in `src/Controller/Admin/` with `#[IsGranted('ROLE_ADMIN')]` on the class, templates in
  `templates/admin/<entity>/` extending `admin/base.html.twig`. URLs are plural to match the sidebar
  (`/admin/categories`), route names are `admin_<entity>_<action>` (e.g. `admin_category_index`).
  No separate "show" page when the list already shows everything. Deletes are `POST /{id}/delete` with a CSRF token.
- A category that still has cars cannot be deleted (flash error), because `Car.category` is not nullable.

## Entity / migration log
- `Version20261125200000` — initial schema (all 6 entities) — Hedi — applied.

## Messages
Questions, heads-ups, requests for the other person. Remove when resolved.
- (Zeineb) Heads-up: I ran `doctrine:fixtures:load` on the shared DB (it empties all tables). It loads
  4 users (`admin@carrental.test` / `admin123` ROLE_ADMIN; `sara@`, `youssef@`, `lina@carrental.test` / `client123`),
  4 categories, 12 cars (6 with photos copied to `public/uploads/cars/`), 10 reservations in every status with
  their payments, and 4 reviews (2 approved, 2 pending).
  After pulling, run `composer install` (new dev package: doctrine/doctrine-fixtures-bundle).
