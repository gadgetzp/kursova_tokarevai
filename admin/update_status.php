<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservationId = (int)($_POST['reservation_id'] ?? 0);
    $statusId = (int)($_POST['status_id'] ?? 0);

    if ($reservationId > 0 && $statusId > 0) {
        $sql = "
            UPDATE reservations
            SET status_id = :status_id
            WHERE id = :reservation_id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'status_id' => $statusId,
            'reservation_id' => $reservationId
        ]);
    }
}

header('Location: /restaurant/admin/reservations.php');
exit;
