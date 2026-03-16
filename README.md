# Villa Cleaning SaaS

Framework-less PHP 8.1 + MySQL backend for rental property operations.

## Milestone 1 (Phase 2) implemented scope

- Property module (CRUD-ready service/repository/controller path)
- Room module (CRUD-ready + room bed/bathroom assignments)
- Bed/Bathroom setup reads (active catalogs)
- Booking module (create/update/list/cancel + period queries)
- Property cleaning settings module (upsert/read)
- Cleaning schedule generation module with deterministic regeneration
  - event types: `arrival`, `departure`, `departure_arrival`, `mid_stay`
  - property settings override defaults
  - safe rerun strategy: delete system-generated events in range, then rebuild

## Run Milestone 1 locally

1. Install dependencies:
   ```bash
   composer install
   ```
2. Create database and apply schema:
   ```bash
   mysql -u root -p villa_cleaning < database/sql/001_initial_schema.sql
   ```
3. Optional sample data:
   ```bash
   mysql -u root -p villa_cleaning < database/sql/002_seed_milestone1_sample_data.sql
   ```
4. Configure DB env vars (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`).
5. Start PHP built-in server:
   ```bash
   php -S 0.0.0.0:8080 -t public
   ```

## Milestone 1 API endpoints (JSON)

- `GET /properties`
- `POST /properties`
- `PUT /properties/{id}`
- `DELETE /properties/{id}`
- `GET /rooms?property_id={propertyId}`
- `POST /rooms`
- `PUT /rooms/{id}`
- `PUT /rooms/{id}/beds`
- `PUT /rooms/{id}/bathrooms`
- `GET /setup/bed-types`
- `GET /setup/bathroom-types`
- `GET /bookings?property_id={propertyId}&from_date=YYYY-MM-DD&to_date=YYYY-MM-DD`
- `POST /bookings`
- `PUT /bookings/{id}`
- `POST /bookings/{id}/cancel`
- `GET /properties/{propertyId}/cleaning-settings`
- `PUT /properties/{propertyId}/cleaning-settings`
- `POST /properties/{propertyId}/cleaning-events/regenerate`
- `GET /properties/{propertyId}/cleaning-events?from_date=YYYY-MM-DD&to_date=YYYY-MM-DD`

## Remaining work for Milestone 2

- Cleaning event assignments workflows
- Linen/towel explicit rule CRUD and requirement calculation snapshot pipeline
- Inventory reservation/pick/adjustment workflows
- Laundry handover and returns workflows
- Role-aware authentication/authorization
- Operational dashboard/reporting read models
