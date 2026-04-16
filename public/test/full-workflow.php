<?php

declare(strict_types=1);

use PDO;
use Throwable;

$devMode = true;
if (!$devMode) {
    http_response_code(403);
    exit('Forbidden');
}

/*
|--------------------------------------------------------------------------
| BOOTSTRAP / AUTOLOAD
|--------------------------------------------------------------------------
| Adjust these paths to match your project.
*/
require_once dirname(__DIR__, 2) . '/bootstrap.php';

/*
|--------------------------------------------------------------------------
| BOOTSTRAP / SERVICE WIRING
|--------------------------------------------------------------------------
| You must adapt this section to your project.
| The example below uses direct PDO + your existing services.
|--------------------------------------------------------------------------
*/

$config = [
    'db' => [
        'host' => 'localhost',
        'port' => '3306',
        'dbname' => 'villa_cleaning',
        'user' => 'villa_cleaning_root',
        'pass' => 'jR4!sA8*qV6^nK1$',
        'charset' => 'utf8mb4',
    ],
];

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $config['db']['host'],
    $config['db']['port'],
    $config['db']['dbname'],
    $config['db']['charset']
);

$pdo = new PDO(
    $dsn,
    $config['db']['user'],
    $config['db']['pass'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

/*
|--------------------------------------------------------------------------
| OPTIONAL:
| If your project already has a container/bootstrap/kernel, use that instead.
|--------------------------------------------------------------------------
|
| Example:
| $container = require dirname(__DIR__, 2) . '/bootstrap/app.php';
| $propertyService = $container->get(App\Application\Services\PropertyService::class);
|
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| SIMPLE HELPERS
|--------------------------------------------------------------------------
*/

function out(mixed $data): string
{
    return htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function uuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function testStateFile(): string
{
    return sys_get_temp_dir() . '/property_ops_test_state.json';
}

function loadState(): array
{
    $file = testStateFile();
    if (!is_file($file)) {
        return [];
    }

    $json = file_get_contents($file);
    $data = json_decode((string)$json, true);

    return is_array($data) ? $data : [];
}

function saveState(array $state): void
{
    file_put_contents(testStateFile(), json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function dbRow(PDO $pdo, string $sql, array $params = []): ?array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
}

function dbRows(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/*
|--------------------------------------------------------------------------
| ACTIONS
|--------------------------------------------------------------------------
|
| These actions are intentionally simple.
| They use direct SQL only for TESTING in browser.
| This is not production app code.
|--------------------------------------------------------------------------
*/

$action = $_POST['action'] ?? null;
$result = null;
$error = null;
$state = loadState();

try {
    if ($action === 'reset_state') {
        @unlink(testStateFile());
        $state = [];
        $result = ['message' => 'Test state reset'];
    }

    if ($action === 'create_property') {
        $propertyId = uuid();

        $stmt = $pdo->prepare("
            INSERT INTO properties
            (id, code, name, location_label, property_type, operational_notes, is_active, created_at, updated_at)
            VALUES
            (:id, :code, :name, :location_label, :property_type, :operational_notes, 1, NOW(), NOW())
        ");
        $stmt->execute([
            'id' => $propertyId,
            'code' => 'TEST-' . date('His'),
            'name' => 'Test Villa',
            'location_label' => 'Test Location',
            'property_type' => 'villa',
            'operational_notes' => 'Created from browser test',
        ]);

        $state['property_id'] = $propertyId;
        saveState($state);

        $result = ['property_id' => $propertyId];
    }

    if ($action === 'create_room') {
        if (empty($state['property_id'])) {
            throw new RuntimeException('Create property first');
        }

        $roomId = uuid();

        $stmt = $pdo->prepare("
            INSERT INTO rooms
            (id, property_id, name, room_type, sort_order, is_active, created_at, updated_at)
            VALUES
            (:id, :property_id, :name, :room_type, :sort_order, 1, NOW(), NOW())
        ");
        $stmt->execute([
            'id' => $roomId,
            'property_id' => $state['property_id'],
            'name' => 'Master Bedroom',
            'room_type' => 'bedroom',
            'sort_order' => 1,
        ]);

        $state['room_id'] = $roomId;
        saveState($state);

        $result = ['room_id' => $roomId];
    }

    if ($action === 'add_bed_bath_setup') {
        if (empty($state['room_id'])) {
            throw new RuntimeException('Create room first');
        }

        // Find or create one bed type
        $bedType = dbRow($pdo, "SELECT id FROM bed_types ORDER BY created_at ASC LIMIT 1");
        if (!$bedType) {
            $bedTypeId = uuid();
            $pdo->prepare("
                INSERT INTO bed_types (id, code, name, width_cm, is_active, created_at, updated_at)
                VALUES (:id, 'double', 'Double Bed', 160, 1, NOW(), NOW())
            ")->execute(['id' => $bedTypeId]);
        } else {
            $bedTypeId = $bedType['id'];
        }

        // Find or create one bathroom type
        $bathType = dbRow($pdo, "SELECT id FROM bathroom_types ORDER BY created_at ASC LIMIT 1");
        if (!$bathType) {
            $bathTypeId = uuid();
            $pdo->prepare("
                INSERT INTO bathroom_types (id, code, name, is_active, created_at, updated_at)
                VALUES (:id, 'standard', 'Standard Bathroom', 1, NOW(), NOW())
            ")->execute(['id' => $bathTypeId]);
        } else {
            $bathTypeId = $bathType['id'];
        }

        // Replace room bed config
        $pdo->prepare("DELETE FROM room_beds WHERE room_id = :room_id")->execute([
            'room_id' => $state['room_id']
        ]);
        $pdo->prepare("
            INSERT INTO room_beds (id, room_id, bed_type_id, quantity, created_at, updated_at)
            VALUES (:id, :room_id, :bed_type_id, 1, NOW(), NOW())
        ")->execute([
            'id' => uuid(),
            'room_id' => $state['room_id'],
            'bed_type_id' => $bedTypeId,
        ]);

        // Replace room bathroom config
        $pdo->prepare("DELETE FROM room_bathrooms WHERE room_id = :room_id")->execute([
            'room_id' => $state['room_id']
        ]);
        $pdo->prepare("
            INSERT INTO room_bathrooms (id, room_id, bathroom_type_id, quantity, created_at, updated_at)
            VALUES (:id, :room_id, :bathroom_type_id, 1, NOW(), NOW())
        ")->execute([
            'id' => uuid(),
            'room_id' => $state['room_id'],
            'bathroom_type_id' => $bathTypeId,
        ]);

        $result = [
            'room_id' => $state['room_id'],
            'bed_type_id' => $bedTypeId,
            'bathroom_type_id' => $bathTypeId,
        ];
    }

    if ($action === 'create_booking') {
        if (empty($state['property_id'])) {
            throw new RuntimeException('Create property first');
        }

        $bookingId = uuid();

        $stmt = $pdo->prepare("
            INSERT INTO bookings
            (id, property_id, booking_reference, source_system, arrival_date, departure_date, guest_count, notes, status, created_at, updated_at)
            VALUES
            (:id, :property_id, :booking_reference, 'manual', :arrival_date, :departure_date, :guest_count, :notes, 'confirmed', NOW(), NOW())
        ");
        $stmt->execute([
            'id' => $bookingId,
            'property_id' => $state['property_id'],
            'booking_reference' => 'BOOK-' . date('YmdHis'),
            'arrival_date' => date('Y-m-d', strtotime('+1 day')),
            'departure_date' => date('Y-m-d', strtotime('+7 day')),
            'guest_count' => 2,
            'notes' => 'Test booking',
        ]);

        $state['booking_id'] = $bookingId;
        saveState($state);

        $result = ['booking_id' => $bookingId];
    }

    if ($action === 'generate_cleaning_events') {
        if (empty($state['property_id'])) {
            throw new RuntimeException('Create property first');
        }

        // This is a browser test shortcut.
        // Ideally here you should call your real CleaningScheduleService.
        // Temporary simple HTTP-style redirect is avoided for simplicity.

        // Example placeholder: inspect current matching bookings
        $result = dbRows($pdo, "
            SELECT id, property_id, arrival_date, departure_date, guest_count, status
            FROM bookings
            WHERE property_id = :property_id
            ORDER BY arrival_date
        ", [
            'property_id' => $state['property_id']
        ]);

        $result = [
            'message' => 'Replace this block with your real CleaningScheduleService call',
            'bookings_found' => $result,
        ];
    }

    if ($action === 'show_state') {
        $result = [
            'state' => $state,
            'property' => !empty($state['property_id']) ? dbRow($pdo, "SELECT * FROM properties WHERE id = :id", ['id' => $state['property_id']]) : null,
            'room' => !empty($state['room_id']) ? dbRow($pdo, "SELECT * FROM rooms WHERE id = :id", ['id' => $state['room_id']]) : null,
            'booking' => !empty($state['booking_id']) ? dbRow($pdo, "SELECT * FROM bookings WHERE id = :id", ['id' => $state['booking_id']]) : null,
            'room_beds' => !empty($state['room_id']) ? dbRows($pdo, "SELECT * FROM room_beds WHERE room_id = :id", ['id' => $state['room_id']]) : [],
            'room_bathrooms' => !empty($state['room_id']) ? dbRows($pdo, "SELECT * FROM room_bathrooms WHERE room_id = :id", ['id' => $state['room_id']]) : [],
            'cleaning_events' => !empty($state['property_id']) ? dbRows($pdo, "SELECT * FROM cleaning_events WHERE property_id = :id ORDER BY event_date", ['id' => $state['property_id']]) : [],
            'requirements' => !empty($state['property_id']) ? dbRows($pdo, "
                SELECT cer.*
                FROM cleaning_event_requirements cer
                INNER JOIN cleaning_events ce ON ce.id = cer.cleaning_event_id
                WHERE ce.property_id = :property_id
                ORDER BY ce.event_date
            ", ['property_id' => $state['property_id']]) : [],
            'inventory_balances' => dbRows($pdo, "SELECT * FROM inventory_balances ORDER BY updated_at DESC LIMIT 20"),
            'inventory_transactions' => dbRows($pdo, "SELECT * FROM inventory_transactions ORDER BY created_at DESC LIMIT 20"),
            'laundry_handovers' => dbRows($pdo, "SELECT * FROM laundry_handovers ORDER BY created_at DESC LIMIT 20"),
            'laundry_handover_items' => dbRows($pdo, "SELECT * FROM laundry_handover_items ORDER BY created_at DESC LIMIT 20"),
        ];
    }
} catch (Throwable $e) {
    $error = [
        'type' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Full Workflow Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 24px;
            background: #f6f7fb;
            color: #222;
        }
        h1 {
            margin-bottom: 8px;
        }
        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }
        .card {
            background: #fff;
            padding: 18px;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            margin-bottom: 16px;
        }
        .actions {
            flex: 1 1 360px;
            min-width: 320px;
        }
        .output {
            flex: 2 1 720px;
            min-width: 360px;
        }
        form {
            margin-bottom: 10px;
        }
        button {
            display: inline-block;
            padding: 10px 14px;
            border: 0;
            border-radius: 6px;
            background: #1f6feb;
            color: white;
            cursor: pointer;
        }
        button:hover {
            background: #1558c0;
        }
        .danger {
            background: #c62828;
        }
        .danger:hover {
            background: #a91f1f;
        }
        pre {
            background: #111827;
            color: #e5e7eb;
            padding: 14px;
            border-radius: 8px;
            overflow: auto;
            font-size: 13px;
        }
        .note {
            font-size: 14px;
            color: #555;
        }
        .ok {
            color: #137333;
            font-weight: bold;
        }
        .err {
            color: #b3261e;
            font-weight: bold;
        }
        a {
            color: #1f6feb;
        }
    </style>
</head>
<body>
<h1>Full Workflow Test</h1>
<p class="note">
    This page is for local/dev manual testing only.
    Replace the placeholder service sections with your real service wiring.
</p>

<p><a href="index.php">← Back to Test Dashboard</a></p>

<div class="row">
    <div class="actions">
        <div class="card">
            <h2>Workflow Steps</h2>

            <form method="post">
                <input type="hidden" name="action" value="create_property">
                <button type="submit">1. Create Property</button>
            </form>

            <form method="post">
                <input type="hidden" name="action" value="create_room">
                <button type="submit">2. Create Room</button>
            </form>

            <form method="post">
                <input type="hidden" name="action" value="add_bed_bath_setup">
                <button type="submit">3. Add Bed/Bath Setup</button>
            </form>

            <form method="post">
                <input type="hidden" name="action" value="create_booking">
                <button type="submit">4. Create Booking</button>
            </form>

            <form method="post">
                <input type="hidden" name="action" value="generate_cleaning_events">
                <button type="submit">5. Generate Cleaning Events</button>
            </form>

            <form method="post">
                <input type="hidden" name="action" value="show_state">
                <button type="submit">Show Current State</button>
            </form>

            <form method="post" onsubmit="return confirm('Reset local test state file?');">
                <input type="hidden" name="action" value="reset_state">
                <button type="submit" class="danger">Reset Test State</button>
            </form>
        </div>

        <div class="card">
            <h2>Saved IDs</h2>
            <pre><?= out($state) ?></pre>
        </div>
    </div>

    <div class="output">
        <div class="card">
            <h2>Execution Result</h2>

            <?php if ($error !== null): ?>
                <p class="err">Error</p>
                <pre><?= out($error) ?></pre>
            <?php elseif ($result !== null): ?>
                <p class="ok">Success</p>
                <pre><?= out($result) ?></pre>
            <?php else: ?>
                <p>No action executed yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>