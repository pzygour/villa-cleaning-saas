# Property Operations System Architecture Foundation (Phase 1 - Approved Schema)

## 1) Proposed Project Architecture

- **Domain-first layered architecture** with strict dependency flow:
  - `Http -> Application -> Domain`
  - `Infrastructure` implements interfaces defined by `Application`
  - `Core` provides reusable runtime concerns (DB, logging, exceptions, transaction handling)
- **Framework-less PHP 8.1** with PSR-4 autoloading and strict typing.
- **Business modules** remain separated by responsibility:
  - Property setup (property/room/bed/bath models)
  - Booking lifecycle
  - Cleaning schedule generation and assignment
  - Linen/towel requirement rules + event requirement snapshots
  - Inventory ledger + balance management
  - Laundry handover and returns

## 2) Folder and File Tree (Current Foundation)

```text
.
├── composer.json
├── config/
├── database/sql/001_initial_schema.sql
├── docs/architecture.md
├── public/index.php
├── src/
│   ├── Core/
│   ├── Domain/
│   ├── Application/
│   ├── Infrastructure/
│   └── Http/
├── storage/
└── tests/
```

> The tree above is intentionally concise here. The concrete baseline classes/interfaces already committed in `src/` remain valid for this design phase.

## 3) Phase 1 Approved Database Schema Design

The approved schema is in: `database/sql/001_initial_schema.sql`.

### Design highlights

- Uses UUID-style `CHAR(36)` IDs consistently across all entities.
- Adds **soft-operational activity flags** (`is_active`) on key master tables (`properties`, `rooms`, `bed_types`, `bathroom_types`, `item_catalog`, `inventory_locations`).
- Replaces generic cleaning/linen rule scopes with **explicit rule tables**:
  - `bed_type_item_rules`
  - `bathroom_type_item_rules`
  - `guest_item_rules`
- Introduces **property-level cleaning policy table**:
  - `property_cleaning_settings` for configurable mid-clean cadence and turnover behavior.
- Adds **cleaner assignment capability** at event level:
  - `cleaning_event_assignments`.
- Strengthens indexing and FK behavior (`ON DELETE`/`ON UPDATE`) for operational safety and query performance.
- Extends inventory ledger transaction vocabulary with reservation lifecycle:
  - `reserve`, `unreserve` in `inventory_transactions.transaction_type`.

## 4) Main Domain Responsibilities (Aligned to Approved Schema)

- `Property`, `Room`, `RoomBed`, `RoomBathroom`: structural setup for each rental unit.
- `Booking`: occupancy timeline and source for cleaning generation.
- `PropertyCleaningSettings`: per-property schedule policy defaults.
- `CleaningEvent`: generated operational workload.
- `CleaningEventAssignment`: user assignment + workflow state.
- `ItemCatalog`: canonical linen/towel item definitions.
- `BedTypeItemRule`, `BathroomTypeItemRule`, `GuestItemRule`: deterministic requirement rules.
- `CleaningEventRequirement`: immutable-ish per-event demand snapshot.
- `InventoryBalance` + `InventoryTransaction`: current stock + audit ledger.
- `LaundryHandover` + `LaundryHandoverItem`: outbound/return lifecycle and reconciliation.

## 5) Repository and Service Boundary Recommendations (Revised)

### Repository interfaces (recommended set)

- `PropertyRepositoryInterface`
- `RoomRepositoryInterface`
- `BedBathroomCatalogRepositoryInterface`
- `BookingRepositoryInterface`
- `PropertyCleaningSettingsRepositoryInterface`
- `CleaningEventRepositoryInterface`
- `CleaningEventAssignmentRepositoryInterface`
- `ItemCatalogRepositoryInterface`
- `RequirementRuleRepositoryInterface` (bed/bath/guest rule access)
- `CleaningRequirementRepositoryInterface`
- `InventoryRepositoryInterface`
- `InventoryTransactionRepositoryInterface`
- `LaundryRepositoryInterface`

### Application service boundaries (recommended)

- `PropertySetupService`: property + room + bed/bath composition workflows.
- `BookingService`: booking CRUD and validation.
- `CleaningScheduleService`: generation of arrival/departure/mid-stay/turnover events using `property_cleaning_settings`.
- `CleaningAssignmentService`: assign/unassign cleaners and update assignment status.
- `RequirementCalculationService`: compute linen/towel quantities from explicit rule tables.
- `InventoryService`: on-hand/reserved stock workflows + ledger writes.
- `LaundryService`: handover creation, return reconciliation, and inventory coupling.

## 6) Module Interaction (Updated)

1. Owner/manager configures property, rooms, bed/bath composition, and cleaning settings.
2. Manager creates bookings.
3. Scheduler reads bookings + property cleaning settings, writes cleaning events.
4. Assignment workflow allocates cleaners to events.
5. Requirement engine reads bed/bath/guest rules and writes per-event requirements.
6. Inventory reservation/picking is posted to `inventory_transactions` and projected to `inventory_balances`.
7. Laundry handovers create `laundry_out` transactions; returns create `laundry_in` and close pending handover items.

## 7) Main Differences vs Previous Schema

1. **Removed generic rules**:
   - Dropped `cleaning_rules` and `linen_rules`.
   - Introduced explicit rules: `property_cleaning_settings`, `bed_type_item_rules`, `bathroom_type_item_rules`, `guest_item_rules`.
2. **Added operations-level assignment table**:
   - New `cleaning_event_assignments` for cleaner allocation and assignment statuses.
3. **Adjusted inventory transaction model**:
   - Added `reserve` and `unreserve` transaction types.
   - Added composite reference index `idx_inventory_transactions_reference`.
4. **Booking reference strategy changed**:
   - `bookings.booking_reference` is now nullable and no longer constrained unique per property.
   - Added indexes for standalone and source+reference lookup.
5. **Property shape simplified**:
   - Removed aggregate counts (`beds_count`, `bathrooms_count`) from `properties`; those are derivable from room composition tables.
6. **FK behavior and indexing hardened**:
   - Broad adoption of explicit `ON DELETE/ON UPDATE` actions and more targeted secondary indexes.

## 8) Next Build Priority (No Full Backend Yet)

1. Implement setup module persistence (properties/rooms/bed-bath composition + cleaning settings).
2. Implement booking persistence and validations.
3. Implement cleaning event generation with approved property-level settings.
4. Implement assignment and requirement calculation read/write pipelines.
5. Implement inventory reservation + pick + laundry movements.
6. Add module-level unit/integration tests around services and repositories.

## 9) Scope Note

This phase remains architecture and schema foundation only. Full business workflows, full backend endpoints, and frontend implementation are intentionally deferred.
