<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservationId = (int)($_POST['reservation_id'] ?? 0);

    if ($reservationId > 0) {
        $sql = "
            UPDATE reservations
            SET status_id = (
                SELECT id 
                FROM reservation_statuses 
                WHERE code = 'cancelled' 
                LIMIT 1
            )
            WHERE id = :reservation_id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'reservation_id' => $reservationId
        ]);
    }
}

$redirectUrl = $_SERVER['HTTP_REFERER'] ?? '/restaurant/admin/reservations.php';

header('Location: ' . $redirectUrl);
exit;
