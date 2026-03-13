# Property Operations System Architecture Foundation

## 1) Proposed Project Architecture

- **Domain-first layered architecture** with strict dependency direction:
  - `Http -> Application -> Domain`
  - `Infrastructure` implements contracts defined in `Application`
  - `Core` contains cross-cutting concerns (DB, errors, logging, runtime)
- **Framework-less bootstrap** with PSR-4 and constructor injection-ready structure.
- **Module boundaries** align with business capabilities:
  - Property & setup
  - Booking
  - Cleaning schedule
  - Linen/towel requirement engine
  - Inventory
  - Laundry handover

## 2) Complete Folder and File Tree

```text
.
├── composer.json
├── config/
│   ├── app.php
│   ├── database.php
│   └── logging.php
├── database/
│   └── sql/
│       └── 001_initial_schema.sql
├── docs/
│   └── architecture.md
├── public/
│   └── index.php
├── src/
│   ├── Application/
│   │   ├── Contracts/
│   │   │   ├── BookingRepositoryInterface.php
│   │   │   ├── CleaningEventRepositoryInterface.php
│   │   │   ├── InventoryRepositoryInterface.php
│   │   │   ├── LaundryRepositoryInterface.php
│   │   │   └── PropertyRepositoryInterface.php
│   │   ├── DTO/
│   │   │   ├── CleaningEventRequirementDTO.php
│   │   │   ├── CreateBookingDTO.php
│   │   │   └── InventoryMovementDTO.php
│   │   ├── Services/
│   │   │   ├── BookingService.php
│   │   │   ├── CleaningScheduleService.php
│   │   │   ├── InventoryService.php
│   │   │   └── LaundryService.php
│   │   └── Validators/
│   │       └── BookingValidator.php
│   ├── Core/
│   │   ├── Database/
│   │   │   ├── ConnectionFactory.php
│   │   │   └── TransactionManager.php
│   │   ├── Exception/
│   │   │   ├── DomainException.php
│   │   │   └── ValidationException.php
│   │   ├── Http/
│   │   │   └── Kernel.php
│   │   └── Logging/
│   │       └── LoggerInterface.php
│   ├── Domain/
│   │   ├── Booking/
│   │   │   └── Booking.php
│   │   ├── Cleaning/
│   │   │   ├── CleaningEvent.php
│   │   │   └── CleaningEventType.php
│   │   ├── Common/
│   │   │   └── EntityId.php
│   │   ├── Inventory/
│   │   │   ├── InventoryTransaction.php
│   │   │   └── InventoryTransactionType.php
│   │   ├── Laundry/
│   │   │   └── LaundryHandover.php
│   │   ├── Property/
│   │   │   ├── Property.php
│   │   │   ├── PropertyType.php
│   │   │   └── Room.php
│   │   └── User/
│   │       ├── User.php
│   │       └── UserRole.php
│   ├── Http/
│   │   ├── Controller/
│   │   │   └── BookingController.php
│   │   ├── Middleware/
│   │   │   └── ExceptionHandlerMiddleware.php
│   │   ├── Request/
│   │   │   └── Request.php
│   │   └── Response/
│   │       ├── HtmlResponse.php
│   │       └── ResponseInterface.php
│   └── Infrastructure/
│       ├── Clock/
│       │   └── SystemClock.php
│       ├── Logging/
│       │   └── FileLogger.php
│       └── Persistence/
│           └── MySql/
│               └── Repository/
│                   └── AbstractPdoRepository.php
├── storage/
│   ├── cache/
│   └── logs/
└── tests/
    ├── Integration/
    ├── Unit/
    └── run.php
```

## 3) Database Schema Design

- SQL is in `database/sql/001_initial_schema.sql`.
- The schema uses **UUID-like `CHAR(36)` primary keys** for safer cross-module and future API synchronization.
- Key normalization decisions:
  - Static and reusable dimensions split into `bed_types`, `bathroom_types`, `item_catalog`.
  - Flexible rules encoded using scoped tables (`cleaning_rules`, `linen_rules`) with `scope_type + scope_id`.
  - Requirements persisted per cleaning event in `cleaning_event_requirements` to support snapshots and auditability.
  - Inventory is split into **balance table** (`inventory_balances`) + **immutable ledger** (`inventory_transactions`).
  - Laundry modeled as header/items (`laundry_handovers`, `laundry_handover_items`) and linked to inventory transactions through references.

## 4) Main Domain Entities and Responsibilities

- `Property`, `Room`: physical layout and operations context.
- `Booking`: occupancy timeline source for schedules.
- `CleaningEvent`: operational unit generated from booking rules.
- `InventoryTransaction`: immutable stock movement event.
- `LaundryHandover`: outbound/return lifecycle for laundry batches.
- `User` + `UserRole`: future RBAC anchor.

## 5) Repository Interfaces and Service Boundaries

### Repository contracts
- `PropertyRepositoryInterface`: property CRUD/read model.
- `BookingRepositoryInterface`: booking persistence + period queries.
- `CleaningEventRepositoryInterface`: schedule persistence/query.
- `InventoryRepositoryInterface`: stock state/movement write model.
- `LaundryRepositoryInterface`: handover workflow persistence.

### Application services
- `BookingService`: booking use-cases, validation, orchestration.
- `CleaningScheduleService`: rule-driven event generation.
- `InventoryService`: movement posting/reservations.
- `LaundryService`: handover + return and inventory linkage.

## 6) Key DTOs

- `CreateBookingDTO`: booking command payload.
- `CleaningEventRequirementDTO`: calculated linen/towel requirements per event.
- `InventoryMovementDTO`: typed stock movement command from UI or workflows.

## 7) Module Interaction Flow

1. Booking inserted/updated by manager.
2. `BookingService` persists booking through repository.
3. Scheduler calls `CleaningScheduleService` for date range/property.
4. `CleaningEvent` records generated and stored.
5. Requirement engine calculates linen/towel quantities and stores in `cleaning_event_requirements`.
6. Operations issue inventory picks; `InventoryService` writes `inventory_transactions` and updates balances.
7. Laundry handover created; `LaundryService` stores handover + items and emits `laundry_out` transactions.
8. Laundry returns post `laundry_in`; handover item statuses are updated.

## 8) Recommended Build Order (Next Phase)

1. **Property + Room setup + catalogs** (foundation data).
2. **Booking module** with validation and list/calendar range APIs.
3. **Cleaning event generator** implementing all business rules.
4. **Requirement calculator** for linen/towel needs and period totals.
5. **Inventory ledger and balance updater**.
6. **Laundry handover workflow** and return reconciliation.
7. **Dashboard read models** and optimized reporting queries.

## 9) SQL Deliverables

- Initial full schema: `database/sql/001_initial_schema.sql`.

## 10) Important Design Decisions and Tradeoffs

- **No framework now**: maximizes portability and low lock-in, but requires discipline on DI and routing conventions.
- **Scope-based rules (JSON payloads)**: extensible for owner-specific logic; tradeoff is stricter validation needs in app layer.
- **Ledger + balance inventory**: robust audit and performance for reads; tradeoff is transactional complexity.
- **Event requirement snapshot table**: stable operational printouts and history even if rules later change.
- **UUID-style IDs**: easy external system import support; tradeoff is larger indexes than integers.
