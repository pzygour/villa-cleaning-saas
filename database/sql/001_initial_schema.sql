SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE users (
  id CHAR(36) PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  role ENUM('owner', 'manager', 'cleaner') NOT NULL,
  password_hash VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE properties (
  id CHAR(36) PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(160) NOT NULL,
  location_label VARCHAR(190) NOT NULL,
  property_type ENUM('villa', 'apartment', 'room', 'hotel_unit') NOT NULL,
  operational_notes TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_properties_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE rooms (
  id CHAR(36) PRIMARY KEY,
  property_id CHAR(36) NOT NULL,
  name VARCHAR(120) NOT NULL,
  room_type VARCHAR(80) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_rooms_property (property_id),
  CONSTRAINT fk_rooms_property
    FOREIGN KEY (property_id) REFERENCES properties(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bed_types (
  id CHAR(36) PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(80) NOT NULL,
  width_cm SMALLINT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE room_beds (
  id CHAR(36) PRIMARY KEY,
  room_id CHAR(36) NOT NULL,
  bed_type_id CHAR(36) NOT NULL,
  quantity INT NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_room_bed_type (room_id, bed_type_id),
  KEY idx_room_beds_room (room_id),
  KEY idx_room_beds_bed_type (bed_type_id),
  CONSTRAINT fk_room_beds_room
    FOREIGN KEY (room_id) REFERENCES rooms(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_room_beds_bed_type
    FOREIGN KEY (bed_type_id) REFERENCES bed_types(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bathroom_types (
  id CHAR(36) PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(80) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE room_bathrooms (
  id CHAR(36) PRIMARY KEY,
  room_id CHAR(36) NOT NULL,
  bathroom_type_id CHAR(36) NOT NULL,
  quantity INT NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_room_bathroom_type (room_id, bathroom_type_id),
  KEY idx_room_bathrooms_room (room_id),
  KEY idx_room_bathrooms_bathroom_type (bathroom_type_id),
  CONSTRAINT fk_room_bathrooms_room
    FOREIGN KEY (room_id) REFERENCES rooms(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_room_bathrooms_bathroom_type
    FOREIGN KEY (bathroom_type_id) REFERENCES bathroom_types(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bookings (
  id CHAR(36) PRIMARY KEY,
  property_id CHAR(36) NOT NULL,
  booking_reference VARCHAR(100) NULL,
  source_system VARCHAR(60) NOT NULL DEFAULT 'manual',
  arrival_date DATE NOT NULL,
  departure_date DATE NOT NULL,
  guest_count INT NOT NULL,
  notes TEXT NULL,
  status ENUM('confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'confirmed',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_bookings_property_dates (property_id, arrival_date, departure_date),
  KEY idx_bookings_reference (booking_reference),
  KEY idx_bookings_source_reference (source_system, booking_reference),
  CONSTRAINT fk_bookings_property
    FOREIGN KEY (property_id) REFERENCES properties(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE property_cleaning_settings (
  id CHAR(36) PRIMARY KEY,
  property_id CHAR(36) NOT NULL,
  mid_clean_every_days INT NOT NULL DEFAULT 4,
  min_nights_for_mid_clean INT NOT NULL DEFAULT 5,
  skip_mid_clean_last_n_days INT NOT NULL DEFAULT 3,
  merge_same_day_turnover TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_property_cleaning_settings_property (property_id),
  CONSTRAINT fk_property_cleaning_settings_property
    FOREIGN KEY (property_id) REFERENCES properties(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cleaning_events (
  id CHAR(36) PRIMARY KEY,
  property_id CHAR(36) NOT NULL,
  booking_id CHAR(36) NULL,
  event_date DATE NOT NULL,
  event_type ENUM('arrival', 'departure', 'departure_arrival', 'mid_stay') NOT NULL,
  status ENUM('planned', 'in_progress', 'done', 'skipped') NOT NULL DEFAULT 'planned',
  operational_notes TEXT NULL,
  generated_by VARCHAR(40) NOT NULL DEFAULT 'system',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_cleaning_events_date (event_date),
  KEY idx_cleaning_events_property_date (property_id, event_date),
  KEY idx_cleaning_events_booking (booking_id),
  CONSTRAINT fk_cleaning_events_property
    FOREIGN KEY (property_id) REFERENCES properties(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_cleaning_events_booking
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cleaning_event_assignments (
  id CHAR(36) PRIMARY KEY,
  cleaning_event_id CHAR(36) NOT NULL,
  user_id CHAR(36) NOT NULL,
  assigned_at DATETIME NOT NULL,
  assignment_status ENUM('assigned', 'accepted', 'completed', 'cancelled') NOT NULL DEFAULT 'assigned',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_cleaning_event_assignment (cleaning_event_id, user_id),
  KEY idx_cleaning_event_assignments_user (user_id),
  CONSTRAINT fk_cleaning_event_assignments_event
    FOREIGN KEY (cleaning_event_id) REFERENCES cleaning_events(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_cleaning_event_assignments_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE item_catalog (
  id CHAR(36) PRIMARY KEY,
  item_type ENUM('linen', 'towel') NOT NULL,
  code VARCHAR(60) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  unit VARCHAR(30) NOT NULL DEFAULT 'piece',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_item_catalog_type_active (item_type, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bed_type_item_rules (
  id CHAR(36) PRIMARY KEY,
  bed_type_id CHAR(36) NOT NULL,
  item_id CHAR(36) NOT NULL,
  trigger_type ENUM('arrival', 'departure', 'departure_arrival', 'mid_stay') NOT NULL,
  quantity_per_bed DECIMAL(10,2) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_bed_type_item_trigger (bed_type_id, item_id, trigger_type),
  KEY idx_bed_type_item_rules_bed_type (bed_type_id),
  CONSTRAINT fk_bed_type_item_rules_bed_type
    FOREIGN KEY (bed_type_id) REFERENCES bed_types(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_bed_type_item_rules_item
    FOREIGN KEY (item_id) REFERENCES item_catalog(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bathroom_type_item_rules (
  id CHAR(36) PRIMARY KEY,
  bathroom_type_id CHAR(36) NOT NULL,
  item_id CHAR(36) NOT NULL,
  trigger_type ENUM('arrival', 'departure', 'departure_arrival', 'mid_stay') NOT NULL,
  quantity_per_bathroom DECIMAL(10,2) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_bathroom_type_item_trigger (bathroom_type_id, item_id, trigger_type),
  KEY idx_bathroom_type_item_rules_bathroom_type (bathroom_type_id),
  CONSTRAINT fk_bathroom_type_item_rules_bathroom_type
    FOREIGN KEY (bathroom_type_id) REFERENCES bathroom_types(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_bathroom_type_item_rules_item
    FOREIGN KEY (item_id) REFERENCES item_catalog(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE guest_item_rules (
  id CHAR(36) PRIMARY KEY,
  property_id CHAR(36) NULL,
  item_id CHAR(36) NOT NULL,
  trigger_type ENUM('arrival', 'departure', 'departure_arrival', 'mid_stay') NOT NULL,
  quantity_per_guest DECIMAL(10,2) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_guest_item_rule (property_id, item_id, trigger_type),
  KEY idx_guest_item_rules_property (property_id),
  CONSTRAINT fk_guest_item_rules_property
    FOREIGN KEY (property_id) REFERENCES properties(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_guest_item_rules_item
    FOREIGN KEY (item_id) REFERENCES item_catalog(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cleaning_event_requirements (
  id CHAR(36) PRIMARY KEY,
  cleaning_event_id CHAR(36) NOT NULL,
  item_id CHAR(36) NOT NULL,
  required_quantity DECIMAL(10,2) NOT NULL,
  reserved_quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
  picked_quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_event_item (cleaning_event_id, item_id),
  KEY idx_cleaning_event_requirements_event (cleaning_event_id),
  KEY idx_cleaning_event_requirements_item (item_id),
  CONSTRAINT fk_cleaning_event_requirements_event
    FOREIGN KEY (cleaning_event_id) REFERENCES cleaning_events(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_cleaning_event_requirements_item
    FOREIGN KEY (item_id) REFERENCES item_catalog(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE inventory_locations (
  id CHAR(36) PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  location_type ENUM('warehouse', 'property', 'laundry_vendor', 'vehicle') NOT NULL,
  property_id CHAR(36) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_inventory_locations_property (property_id),
  KEY idx_inventory_locations_type_active (location_type, is_active),
  CONSTRAINT fk_inventory_locations_property
    FOREIGN KEY (property_id) REFERENCES properties(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE inventory_balances (
  id CHAR(36) PRIMARY KEY,
  location_id CHAR(36) NOT NULL,
  item_id CHAR(36) NOT NULL,
  on_hand_quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
  reserved_quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_location_item (location_id, item_id),
  KEY idx_inventory_balances_location (location_id),
  KEY idx_inventory_balances_item (item_id),
  CONSTRAINT fk_inventory_balances_location
    FOREIGN KEY (location_id) REFERENCES inventory_locations(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_inventory_balances_item
    FOREIGN KEY (item_id) REFERENCES item_catalog(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE inventory_transactions (
  id CHAR(36) PRIMARY KEY,
  item_id CHAR(36) NOT NULL,
  location_id CHAR(36) NOT NULL,
  transaction_type ENUM('in', 'out', 'adjustment', 'laundry_out', 'laundry_in', 'reserve', 'unreserve') NOT NULL,
  quantity DECIMAL(10,2) NOT NULL,
  reference_type VARCHAR(60) NULL,
  reference_id CHAR(36) NULL,
  transaction_date DATETIME NOT NULL,
  note VARCHAR(255) NULL,
  created_by_user_id CHAR(36) NULL,
  created_at DATETIME NOT NULL,
  KEY idx_inventory_transactions_item_date (item_id, transaction_date),
  KEY idx_inventory_transactions_location_date (location_id, transaction_date),
  KEY idx_inventory_transactions_reference (reference_type, reference_id),
  CONSTRAINT fk_inventory_transactions_item
    FOREIGN KEY (item_id) REFERENCES item_catalog(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_inventory_transactions_location
    FOREIGN KEY (location_id) REFERENCES inventory_locations(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_inventory_transactions_user
    FOREIGN KEY (created_by_user_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE laundry_handovers (
  id CHAR(36) PRIMARY KEY,
  property_id CHAR(36) NULL,
  from_location_id CHAR(36) NOT NULL,
  to_location_id CHAR(36) NOT NULL,
  handover_date DATETIME NOT NULL,
  expected_return_date DATETIME NULL,
  returned_date DATETIME NULL,
  status ENUM('pending', 'partially_returned', 'closed') NOT NULL DEFAULT 'pending',
  note VARCHAR(255) NULL,
  created_by_user_id CHAR(36) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_laundry_handovers_status_date (status, handover_date),
  CONSTRAINT fk_laundry_handovers_property
    FOREIGN KEY (property_id) REFERENCES properties(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_laundry_handovers_from_location
    FOREIGN KEY (from_location_id) REFERENCES inventory_locations(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_laundry_handovers_to_location
    FOREIGN KEY (to_location_id) REFERENCES inventory_locations(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_laundry_handovers_user
    FOREIGN KEY (created_by_user_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE laundry_handover_items (
  id CHAR(36) PRIMARY KEY,
  laundry_handover_id CHAR(36) NOT NULL,
  item_id CHAR(36) NOT NULL,
  quantity_sent DECIMAL(10,2) NOT NULL,
  quantity_returned DECIMAL(10,2) NOT NULL DEFAULT 0,
  status ENUM('pending', 'partially_returned', 'returned') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_handover_item (laundry_handover_id, item_id),
  KEY idx_laundry_handover_items_status (status),
  CONSTRAINT fk_laundry_handover_items_handover
    FOREIGN KEY (laundry_handover_id) REFERENCES laundry_handovers(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_laundry_handover_items_item
    FOREIGN KEY (item_id) REFERENCES item_catalog(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
