<?php
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDbConnection();

$date = $_GET['date'] ?? '';
$time = $_GET['time'] ?? '';
$durationHours = (int)($_GET['duration'] ?? 2);

if ($date === '' || $time === '' || $durationHours <= 0) {
    echo json_encode([
        'success' => false,
        'occupied_tables' => []
    ]);
    exit;
}

$sql = "
    WITH requested AS (
        SELECT
            CAST(:reservation_date AS date) + CAST(:reservation_time AS time) AS requested_start,
            CAST(:reservation_date AS date) + CAST(:reservation_time AS time) 
                + (CAST(:duration_hours AS integer) * INTERVAL '1 hour') AS requested_end
    )
    SELECT r.table_id
    FROM reservations r
    JOIN reservation_statuses rs ON r.status_id = rs.id
    CROSS JOIN requested req
    WHERE rs.code IN ('new', 'confirmed')
      AND (r.reservation_date + r.reservation_time) < req.requested_end
      AND (
            (r.reservation_date + r.reservation_time) 
            + (r.duration_hours * INTERVAL '1 hour')
          ) > req.requested_start
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'reservation_date' => $date,
    'reservation_time' => $time,
    'duration_hours' => $durationHours
]);

$occupiedTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode([
    'success' => true,
    'occupied_tables' => $occupiedTables
]);
