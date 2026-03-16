# Property Operations System - Architecture Foundation (Step 1.5 Refinement)

## 1) Purpose and Scope

This document defines the **refined Phase 1 architecture baseline** before Phase 2 implementation.

It is intentionally limited to:
- schema alignment,
- module boundaries,
- service/repository responsibilities,
- DTO recommendations,
- and implementation sequencing.

It intentionally does **not** include full backend or frontend implementation.

---

## 2) Architecture Principles (MVP Practicality)

1. **Framework-less but structured** (PHP 8.1, PDO, Composer PSR-4).
2. **Layered architecture** with clear dependency direction:
   - `Http -> Application -> Domain`
   - `Infrastructure` implements persistence/integration concerns used by `Application`.
3. **No business logic in controllers**.
4. **Explicit module repositories over over-generic abstractions**.
5. **Use explicit rule tables** (not generic rule engines) for MVP clarity.
6. **Historical safety first** in schema behavior:
   - prefer `RESTRICT` / `SET NULL`, avoid destructive cascades.
7. **UUID-style `CHAR(36)` IDs** remain the project standard.
8. **Operational job readiness** for future scheduler/import/background workers.

---

## 3) Revised Project Structure (Practical, Job-Ready)

```text
.
├── composer.json
├── public/
│   └── index.php
├── config/
│   ├── app.php
│   ├── database.php
│   ├── logging.php
│   └── jobs.php                        # future job/CLI runtime config
├── database/
│   └── sql/
│       └── 001_initial_schema.sql
├── docs/
│   └── architecture.md
├── src/
│   ├── Core/
│   │   ├── Database/
│   │   ├── Exception/
│   │   ├── Http/
│   │   ├── Logging/
│   │   └── Support/
│   ├── Domain/
│   │   ├── Property/
│   │   ├── Booking/
│   │   ├── Cleaning/
│   │   ├── Inventory/
│   │   ├── Laundry/
│   │   ├── User/
│   │   └── Shared/
│   ├── Application/
│   │   ├── DTO/
│   │   ├── Contracts/
│   │   ├── Services/
│   │   ├── Validators/
│   │   └── Jobs/                       # job handlers/use-cases, no queue framework required
│   ├── Infrastructure/
│   │   ├── Persistence/MySql/Repository/
│   │   ├── Logging/
│   │   └── Cli/                        # future command runners/adapters
│   └── Http/
│       ├── Controller/
│       ├── Middleware/
│       ├── Request/
│       └── Response/
├── storage/
│   ├── cache/
│   └── logs/
└── tests/
    ├── Unit/
    └── Integration/
```

> Note: For MVP, keep repository implementations explicit per module (e.g. `MySqlBookingRepository`, `MySqlCleaningEventRepository`) and avoid broad generic repository inheritance trees.

---

## 4) Approved Phase 1 Schema Alignment

Source of truth: `database/sql/001_initial_schema.sql`.

### 4.1 Core Operational Tables
- Master setup:
  - `properties`, `rooms`, `bed_types`, `bathroom_types`, `item_catalog`
  - structural composition: `room_beds`, `room_bathrooms`
- Bookings:
  - `bookings`
- Cleaning:
  - `property_cleaning_settings`
  - `cleaning_events`
  - `cleaning_event_assignments`
  - `cleaning_event_requirements` (snapshot per event)
- Requirement rules:
  - `bed_type_item_rules`
  - `bathroom_type_item_rules`
  - `guest_item_rules`
- Inventory:
  - `inventory_locations`
  - `inventory_balances`
  - `inventory_transactions` (immutable ledger)
- Laundry:
  - `laundry_handovers`
  - `laundry_handover_items`
- Access identity foundation:
  - `users` (owner/manager/cleaner roles)

### 4.2 Business Rule Representation
- **Cleaning cadence settings** are explicit per property in `property_cleaning_settings`.
- **Linen/towel requirement rules** are explicit and readable via bed/bath/guest rule tables.
- **Requirement snapshots** are persisted in `cleaning_event_requirements` to preserve historical operational truth, even if rules later change.

### 4.3 Data Safety Policy (Delete/Update)
- Foreign keys are designed mainly with `ON DELETE RESTRICT` or `ON DELETE SET NULL`.
- This protects operational history (events, inventory movement, laundry records) from accidental hard deletes.

---

## 5) Domain Modeling (Explicit, Maintainable)

### 5.1 Core Entities
- `User` (role: owner/manager/cleaner)
- `Property`
- `Room`
- `BedType`
- `BathroomType`
- `ItemCatalogItem` (linen/towel)
- `Booking`
- `PropertyCleaningSettings`
- `CleaningEvent`
- `CleaningEventAssignment`
- `CleaningEventRequirement`
- `InventoryLocation`
- `InventoryBalance`
- `InventoryTransaction`
- `LaundryHandover`
- `LaundryHandoverItem`

### 5.2 Responsibility Notes
- `Booking` is the occupancy source of truth.
- `CleaningEvent` is the schedulable operational unit.
- `CleaningEventAssignment` links events to cleaners and tracks assignment lifecycle.
- Rule entities (`BedTypeItemRule`, `BathroomTypeItemRule`, `GuestItemRule`) are read-only inputs for requirement calculation.
- `InventoryTransaction` is immutable and auditable; balances are derived/projection state.

---

## 6) Repository Boundaries (Revised for MVP Clarity)

Prefer explicit repositories per module/table cluster:

- `UserRepositoryInterface`
- `PropertyRepositoryInterface`
- `RoomRepositoryInterface`
- `BedTypeRepositoryInterface`
- `BathroomTypeRepositoryInterface`
- `ItemCatalogRepositoryInterface`
- `BookingRepositoryInterface`
- `PropertyCleaningSettingsRepositoryInterface`
- `CleaningEventRepositoryInterface`
- `CleaningEventAssignmentRepositoryInterface`
- `CleaningEventRequirementRepositoryInterface`
- `BedTypeItemRuleRepositoryInterface`
- `BathroomTypeItemRuleRepositoryInterface`
- `GuestItemRuleRepositoryInterface`
- `InventoryLocationRepositoryInterface`
- `InventoryBalanceRepositoryInterface`
- `InventoryTransactionRepositoryInterface`
- `LaundryHandoverRepositoryInterface`

### Why this change
This avoids over-generic repository abstractions and keeps module ownership obvious for MVP contributors.

---

## 7) Application Service Boundaries (Revised)

- `PropertySetupService`
  - property/room setup, bed and bathroom composition
  - seed/maintain item catalog where needed
- `BookingService`
  - booking CRUD + validation
- `CleaningScheduleService`
  - generate `arrival`, `departure`, `departure_arrival`, `mid_stay`
  - obey property settings:
    - mid-clean every N days (default 4)
    - minimum nights for mid-clean (default 5)
    - skip mid-clean near departure (default 3 days)
    - merge same-day turnover flag
- `CleaningAssignmentService`
  - assign cleaner(s), update assignment status
- `RequirementCalculationService`
  - compute event requirements from bed/bath/guest rules
  - persist snapshots to `cleaning_event_requirements`
- `InventoryService`
  - maintain balances via immutable transactions
  - reservation flow (`reserve` / `unreserve`) and pick/out movements
- `LaundryService`
  - handover creation, returns, status reconciliation
  - coupled inventory transactions (`laundry_out`, `laundry_in`)

---

## 8) DTO Recommendations (Revised)

### 8.1 Setup + Booking
- `CreatePropertyDTO`, `UpdatePropertyDTO`
- `CreateRoomDTO`, `SetRoomBedsDTO`, `SetRoomBathroomsDTO`
- `CreateBookingDTO`, `UpdateBookingDTO`
- `UpsertPropertyCleaningSettingsDTO`

### 8.2 Scheduling + Assignment
- `GenerateCleaningEventsDTO` (property, date range, regenerate mode)
- `AssignCleanerToEventDTO`
- `UpdateCleaningAssignmentStatusDTO`

### 8.3 Requirements
- `UpsertBedTypeItemRuleDTO`
- `UpsertBathroomTypeItemRuleDTO`
- `UpsertGuestItemRuleDTO`
- `CalculateEventRequirementsDTO`

### 8.4 Inventory + Laundry
- `InventoryMovementDTO`
- `ReserveInventoryForEventDTO`
- `ReleaseInventoryReservationDTO`
- `CreateLaundryHandoverDTO`
- `RegisterLaundryReturnDTO`

### 8.5 Read/View DTOs (for server-rendered UI + Vue calendar)
- `CleaningCalendarEventViewDTO`
- `DailyRequirementSummaryDTO`
- `InventoryStockViewDTO`
- `LaundryHandoverStatusViewDTO`

---

## 9) Future CLI / Job Readiness

To support scheduled tasks and imports without framework lock-in:

- Add `Application/Jobs/` for job use-case handlers:
  - `GenerateDailyCleaningScheduleJob`
  - `RecalculateRequirementsJob`
  - `ImportBookingsJob` (future external source adapters)
- Add `Infrastructure/Cli/` for command entrypoints/runners.
- Keep jobs idempotent (safe re-run).
- Persist job run logs in file logging initially; DB job history table can be added in Phase 2+ when required.

---

## 10) Main Differences from Previous Architecture/Schema Baseline

1. **Removed generic rule modeling assumptions**:
   - no `cleaning_rules`
   - no generic scoped `linen_rules`
2. **Adopted explicit configuration/rule tables**:
   - `property_cleaning_settings`
   - `bed_type_item_rules`
   - `bathroom_type_item_rules`
   - `guest_item_rules`
3. **Added cleaner assignment model**:
   - `cleaning_event_assignments`
4. **Kept requirement snapshots per event**:
   - `cleaning_event_requirements` remains required
5. **MVP architecture simplified**:
   - fewer generic abstractions, explicit repository boundaries
6. **Inventory model clarified**:
   - balances + immutable transactions retained
   - reservation transaction types included
7. **Delete/update behavior documented for historical safety**.
8. **CLI/job-ready structure now explicitly recommended**.

---

## 11) Safest Phase 2 Milestone 1 Scope

Milestone 1 should prioritize correctness of operational flow over breadth:

1. **Setup module**
   - properties, rooms, bed/bath composition, item catalog
   - property cleaning settings CRUD
2. **Booking module (manual)**
   - create/edit/list bookings with validation
3. **Cleaning schedule generation**
   - implement required event logic and same-day turnover merge
4. **Requirement calculation + snapshots**
   - compute using explicit rule tables
   - store in `cleaning_event_requirements`
5. **Basic assignment workflow**
   - assign cleaner to event, update assignment status

### Milestone 1 intentionally excludes
- full inventory/laundry workflows (can start read-only visibility)
- external booking imports
- advanced analytics/dashboard aggregations
- full SPA/API redesign

This sequence reduces risk and validates the business-critical planning loop first: **Booking -> Cleaning Events -> Requirements -> Assignment**.
