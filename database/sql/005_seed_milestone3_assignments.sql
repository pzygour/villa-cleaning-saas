SET NAMES utf8mb4;
SET time_zone = '+00:00';

INSERT INTO users (id, name, email, role, password_hash, is_active, created_at, updated_at) VALUES
('80000000-0000-4000-8000-000000000001', 'Cleaner Ana', 'ana.cleaner@example.com', 'cleaner', NULL, 1, NOW(), NOW()),
('80000000-0000-4000-8000-000000000002', 'Cleaner Budi', 'budi.cleaner@example.com', 'cleaner', NULL, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), role = VALUES(role), is_active = VALUES(is_active), updated_at = NOW();

INSERT INTO cleaning_event_assignments (id, cleaning_event_id, user_id, assigned_at, assignment_status, created_at, updated_at)
SELECT '81000000-0000-4000-8000-000000000001', ce.id, '80000000-0000-4000-8000-000000000001', NOW(), 'assigned', NOW(), NOW()
FROM cleaning_events ce
WHERE ce.event_date = '2026-04-08' AND ce.property_id = '10000000-0000-4000-8000-000000000001'
LIMIT 1
ON DUPLICATE KEY UPDATE assignment_status = VALUES(assignment_status), updated_at = NOW();
