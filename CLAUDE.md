# Car Rental Website — Project Instructions

## Context
This is a school group project: a **simple car rental website** built with **Symfony**.
Keep the code simple, readable and easy to explain in a presentation. Prefer standard
Symfony tools (MakerBundle, Doctrine, Twig, Forms, Security) over extra libraries or
clever abstractions. No over-engineering.

## Tech stack
- PHP 8.2+, Symfony 7 (full web app skeleton: `symfony new --webapp`)
- Doctrine ORM + Doctrine Migrations
- Database: MySQL (connection in `.env.local`, never commit real credentials)
- Twig templates + Bootstrap 5 (via CDN) for styling
- Symfony Security for login/registration, Symfony Forms + Validator for all input

> Edit these lines if our setup is different (e.g. PostgreSQL, Symfony 6.4).

## Roles
- **Visitor** (not logged in): browse cars, search/filter, view car details and reviews, register, log in.
- **Client** (`ROLE_USER`): everything a visitor can do, plus edit profile, book a car,
  view/cancel own reservations, pay a reservation, view payment history, leave a review.
- **Admin** (`ROLE_ADMIN`): manage cars, categories, reservations, payments, reviews
  (moderation), users, and view a statistics dashboard.

## Data model (Doctrine entities)

### User
id, email (unique, login identifier), password (hashed), roles (json), firstName, lastName,
phone, driverLicenseNumber, createdAt
- Relations: OneToMany → Reservation, OneToMany → Review

### Category
id, name, description (nullable)
- Relations: OneToMany → Car

### Car
id, brand, model, year, plateNumber (unique), pricePerDay (decimal 10,2),
fuelType (enum), transmission (enum), seats, image (nullable, filename), isAvailable (bool)
- Relations: ManyToOne → Category, OneToMany → Reservation, OneToMany → Review

### Reservation
id, startDate (date), endDate (date), totalPrice (decimal 10,2), status (enum), createdAt
- Relations: ManyToOne → User, ManyToOne → Car, OneToOne → Payment (nullable)

### Payment
id, amount (decimal 10,2), method (enum), status (enum), paidAt (nullable datetime)
- Relations: OneToOne → Reservation

### Review
id, rating (int 1–5), comment (text), createdAt, isApproved (bool, default false)
- Relations: ManyToOne → User, ManyToOne → Car

### PHP backed enums (in `src/Enum/`)
- `ReservationStatus`: pending, confirmed, cancelled, completed
- `PaymentStatus`: pending, paid, refunded
- `PaymentMethod`: cash, card
- `FuelType`: petrol, diesel, electric, hybrid
- `Transmission`: manual, automatic

## Business rules
1. **Availability**: a car can be booked for [start, end] only if `isAvailable` is true AND
   there is no reservation for that car with status ≠ cancelled where
   `start_date <= :end AND end_date >= :start`. Put this query in `ReservationRepository`.
2. **Dates**: startDate must be today or later; endDate must be ≥ startDate.
3. **Price**: totalPrice = number of days × car.pricePerDay (count both start and end day).
   Compute it on the server when the reservation is created; never trust a price from the form.
4. **New reservation** starts as `pending`; a Payment with status `pending` is created with it.
5. **Payment**: no real payment gateway. "Pay by card" is simulated (marks the payment `paid`
   and the reservation `confirmed`). "Cash" stays `pending` until an admin validates it.
   Admin can refund a paid payment (→ `refunded`, reservation → `cancelled`).
6. **Cancel**: a client can cancel only their own reservation, only if status is `pending`
   or `confirmed` and startDate is in the future. A paid cancelled reservation's payment becomes `refunded`.
7. **Reviews**: a client can review a car only if they have a `completed` reservation for it,
   and only once per car. Reviews are hidden until an admin approves them.
8. **Ownership**: clients can only see and act on their own reservations, payments and profile.
   Always check this in the controller (or with a Voter), not just by hiding buttons.

## Pages / routes
Public:
- `/` home (featured cars), `/cars` list with filters (category, fuel, transmission, price range,
  dates), `/cars/{id}` details + approved reviews + average rating
- `/register`, `/login`, `/logout`

Client (`ROLE_USER`, under `/account`):
- `/account/profile` edit profile
- `/cars/{id}/book` booking form (dates → shows calculated price → confirm)
- `/account/reservations` list, with cancel and pay buttons
- `/account/payments` payment history
- `/cars/{id}/review` review form

Admin (`ROLE_ADMIN`, under `/admin`):
- CRUD for cars (with image upload to `public/uploads/cars`), categories, users
- Reservations list with filters + confirm / cancel / mark completed
- Payments list + validate cash payment / refund
- Reviews list + approve / delete
- `/admin` dashboard: number of cars, active reservations, revenue (sum of paid payments),
  most rented cars

Protect routes with `access_control` in `security.yaml` and `#[IsGranted]` on controllers.

## Conventions
- Use MakerBundle (`make:entity`, `make:user`, `make:registration-form`, `make:security:form-login`,
  `make:crud`, `make:migration`) whenever it fits, then adjust the generated code.
- Use PHP 8 attributes for routes, ORM mapping and validation.
- Validation constraints on entities (NotBlank, Email, Range for rating 1–5, Positive for prices, etc.).
- Put business logic (price calculation, availability, status changes) in small services in
  `src/Service/` or entity methods, not in Twig or giant controllers.
- CSRF protection on all forms and on action buttons (cancel, pay, approve, delete) — use POST for them.
- Flash messages for success/error feedback.
- One base layout `templates/base.html.twig` with a navbar that changes based on the user's role.
- Code and comments in English.

## Fixtures
Add DoctrineFixturesBundle with sample data: 1 admin (admin@carrental.test / admin123),
2–3 clients, 4 categories, ~12 cars, some reservations in every status, payments and reviews.
Document the test accounts in the README.

## Build order
1. Project setup, database config, base layout with Bootstrap
2. Enums + entities + migrations
3. Auth: User, registration, login, roles
4. Public catalogue: list, filters, details
5. Booking flow with availability check and price calculation
6. Client area: reservations, cancel, payment, payment history, profile
7. Reviews (create + display approved ones + average rating)
8. Admin area: CRUDs, moderation, payment validation, dashboard
9. Fixtures + README (setup steps, test accounts)

After each step: run migrations, check that the pages load, and tell me what was done
before moving to the next step.

## Don't
- Don't add an API, JavaScript framework, Docker, or real payment integration unless asked.
- Don't use EasyAdmin unless asked — admin pages use `make:crud` so we can explain the code.
- Don't commit `.env.local`, uploaded images, or `vendor/`.
