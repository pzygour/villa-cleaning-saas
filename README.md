# Villa Cleaning SaaS

Framework-less PHP 8.1 + MySQL backend for rental property operations.

## Milestone 1-3 implemented scope

- Milestone 1 foundations: properties, rooms, bookings, cleaning generation, cleaning settings
- Milestone 2 foundations: item catalog, requirement rules, requirement snapshot recalculation
- Milestone 3 operations:
  - cleaning event assignments (multi-cleaner support)
  - assignment status workflow (`assigned`, `accepted`, `completed`, `cancelled`)
  - operational schedule query endpoints (property/all/cleaner/day)
  - requirement totals query endpoints (event/day/property-range)
  - cleaner-facing assigned events query
  - active cleaner listing for assignment use

## Run Milestone 3 locally

1. Install dependencies:
   ```bash
   composer install
   ```
2. Apply base schema and migrations:
   ```bash
   mysql -u root -p villa_cleaning < database/sql/001_initial_schema.sql
   mysql -u root -p villa_cleaning < database/sql/003_cleaning_event_generation_guards.sql
   ```
3. Optional sample data:
   ```bash
   mysql -u root -p villa_cleaning < database/sql/002_seed_milestone1_sample_data.sql
   mysql -u root -p villa_cleaning < database/sql/004_seed_milestone2_rules_and_items.sql
   mysql -u root -p villa_cleaning < database/sql/005_seed_milestone3_assignments.sql
   ```
4. Configure DB env vars (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`).
5. Start server:
   ```bash
   php -S 0.0.0.0:8080 -t public
   ```

## API endpoints (JSON)

### Milestone 3 assignment + operations endpoints
- `GET /users/cleaners`
- `POST /cleaning-events/{eventId}/assignments` with `{ "user_ids": ["cleaner-uuid", "..."] }`
- `PATCH /cleaning-events/{eventId}/assignments/{userId}/status` with `{ "assignment_status": "accepted|completed|cancelled|assigned" }`
- `GET /schedule/property/{propertyId}?from_date=YYYY-MM-DD&to_date=YYYY-MM-DD`
- `GET /schedule/all?from_date=YYYY-MM-DD&to_date=YYYY-MM-DD`
- `GET /schedule/cleaner/{userId}?from_date=YYYY-MM-DD&to_date=YYYY-MM-DD`
- `GET /schedule/day?date=YYYY-MM-DD`
- `GET /cleaners/{userId}/operations?from_date=YYYY-MM-DD&to_date=YYYY-MM-DD`
- `GET /requirements/totals/events?event_ids=uuid1,uuid2`
- `GET /requirements/totals/day?date=YYYY-MM-DD&property_id={optionalPropertyId}`
- `GET /requirements/totals/property/{propertyId}?from_date=YYYY-MM-DD&to_date=YYYY-MM-DD`

## Remaining work for Milestone 4

- Inventory movement lifecycle endpoints (in/out/adjustment/reserve/unreserve)
- Laundry handover and returns workflows
- Role-aware authentication and permissions
- Audit/reporting enhancements and pagination/filtering hardening
