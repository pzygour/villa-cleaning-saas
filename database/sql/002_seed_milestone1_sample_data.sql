SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Sample IDs are fixed for local development/demo
INSERT INTO properties (id, code, name, location_label, property_type, operational_notes, is_active, created_at, updated_at) VALUES
('10000000-0000-4000-8000-000000000001', 'VL-001', 'Sunset Villa', 'Seminyak', 'villa', 'Priority turnovers before 14:00', 1, NOW(), NOW()),
('10000000-0000-4000-8000-000000000002', 'AP-101', 'City Apartment 101', 'Canggu', 'apartment', NULL, 1, NOW(), NOW());

INSERT INTO bed_types (id, code, name, width_cm, is_active, created_at, updated_at) VALUES
('20000000-0000-4000-8000-000000000001', 'KING', 'King Bed', 180, 1, NOW(), NOW()),
('20000000-0000-4000-8000-000000000002', 'QUEEN', 'Queen Bed', 160, 1, NOW(), NOW()),
('20000000-0000-4000-8000-000000000003', 'TWIN', 'Twin Bed', 100, 1, NOW(), NOW());

INSERT INTO bathroom_types (id, code, name, is_active, created_at, updated_at) VALUES
('30000000-0000-4000-8000-000000000001', 'ENSUITE', 'Ensuite Bathroom', 1, NOW(), NOW()),
('30000000-0000-4000-8000-000000000002', 'SHARED', 'Shared Bathroom', 1, NOW(), NOW());

INSERT INTO rooms (id, property_id, name, room_type, sort_order, is_active, created_at, updated_at) VALUES
('40000000-0000-4000-8000-000000000001', '10000000-0000-4000-8000-000000000001', 'Master Bedroom', 'bedroom', 1, 1, NOW(), NOW()),
('40000000-0000-4000-8000-000000000002', '10000000-0000-4000-8000-000000000001', 'Guest Bedroom', 'bedroom', 2, 1, NOW(), NOW()),
('40000000-0000-4000-8000-000000000003', '10000000-0000-4000-8000-000000000002', 'Main Room', 'studio', 1, 1, NOW(), NOW());

INSERT INTO room_beds (id, room_id, bed_type_id, quantity, created_at, updated_at) VALUES
('41000000-0000-4000-8000-000000000001', '40000000-0000-4000-8000-000000000001', '20000000-0000-4000-8000-000000000001', 1, NOW(), NOW()),
('41000000-0000-4000-8000-000000000002', '40000000-0000-4000-8000-000000000002', '20000000-0000-4000-8000-000000000002', 1, NOW(), NOW()),
('41000000-0000-4000-8000-000000000003', '40000000-0000-4000-8000-000000000003', '20000000-0000-4000-8000-000000000003', 2, NOW(), NOW());

INSERT INTO room_bathrooms (id, room_id, bathroom_type_id, quantity, created_at, updated_at) VALUES
('42000000-0000-4000-8000-000000000001', '40000000-0000-4000-8000-000000000001', '30000000-0000-4000-8000-000000000001', 1, NOW(), NOW()),
('42000000-0000-4000-8000-000000000002', '40000000-0000-4000-8000-000000000002', '30000000-0000-4000-8000-000000000002', 1, NOW(), NOW()),
('42000000-0000-4000-8000-000000000003', '40000000-0000-4000-8000-000000000003', '30000000-0000-4000-8000-000000000001', 1, NOW(), NOW());

INSERT INTO property_cleaning_settings (id, property_id, mid_clean_every_days, min_nights_for_mid_clean, skip_mid_clean_last_n_days, merge_same_day_turnover, created_at, updated_at) VALUES
('50000000-0000-4000-8000-000000000001', '10000000-0000-4000-8000-000000000001', 4, 5, 3, 1, NOW(), NOW()),
('50000000-0000-4000-8000-000000000002', '10000000-0000-4000-8000-000000000002', 4, 5, 3, 1, NOW(), NOW());

INSERT INTO bookings (id, property_id, booking_reference, source_system, arrival_date, departure_date, guest_count, notes, status, created_at, updated_at) VALUES
('60000000-0000-4000-8000-000000000001', '10000000-0000-4000-8000-000000000001', 'BK-1001', 'manual', '2026-04-01', '2026-04-08', 4, 'Family stay', 'confirmed', NOW(), NOW()),
('60000000-0000-4000-8000-000000000002', '10000000-0000-4000-8000-000000000001', 'BK-1002', 'manual', '2026-04-08', '2026-04-11', 2, NULL, 'confirmed', NOW(), NOW()),
('60000000-0000-4000-8000-000000000003', '10000000-0000-4000-8000-000000000002', 'BK-2001', 'manual', '2026-04-03', '2026-04-09', 2, 'Late check-in', 'confirmed', NOW(), NOW());
