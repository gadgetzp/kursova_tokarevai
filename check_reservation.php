<?php
require_once __DIR__ . '/config/db.php';

$pdo = getDbConnection();

$reservation = null;
$error = '';

$reservationId = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservationId = trim($_POST['reservation_id'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $phoneForCheck = preg_replace('/[\s\-\(\)]/', '', $phone);

    if ($reservationId === '') {
        $error = 'Вкажіть номер бронювання.';
    } elseif (!ctype_digit($reservationId)) {
        $error = 'Номер бронювання має містити тільки цифри.';
    } elseif ($phoneForCheck === '') {
        $error = 'Вкажіть номер телефону.';
    } elseif (!preg_match('/^(\+380\d{9}|0\d{9})$/', $phoneForCheck)) {
        $error = 'Телефон має бути у форматі +380XXXXXXXXX або 0XXXXXXXXX.';
    } else {
        $phoneVariants = [$phoneForCheck];

        if (str_starts_with($phoneForCheck, '+380')) {
            $phoneVariants[] = '0' . substr($phoneForCheck, 4);
        }

        if (str_starts_with($phoneForCheck, '0')) {
            $phoneVariants[] = '+38' . $phoneForCheck;
        }

        $sql = "
            SELECT 
                r.id,
                r.customer_name,
                r.phone,
                r.email,
                r.guests,
                r.reservation_date,
                r.reservation_time,
                r.duration_hours,
                r.comment,
                rt.table_number,
                rt.seats,
                rz.name AS zone_name,
                rs.name AS status_name,
                rs.code AS status_code
            FROM reservations r
            JOIN restaurant_tables rt ON r.table_id = rt.id
            JOIN restaurant_zones rz ON rt.zone_id = rz.id
            JOIN reservation_statuses rs ON r.status_id = rs.id
            WHERE r.id = :reservation_id
              AND regexp_replace(r.phone, '[\\s\\-\\(\\)]', '', 'g') IN (:phone_1, :phone_2)
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'reservation_id' => (int)$reservationId,
            'phone_1' => $phoneVariants[0],
            'phone_2' => $phoneVariants[1] ?? $phoneVariants[0]
        ]);

        $reservation = $stmt->fetch();

        if (!$reservation) {
            $error = 'Бронювання не знайдено. Перевірте номер заявки та телефон.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="check-reservation-layout">
            <div class="check-reservation-card">
                <span class="section-label">Статус заявки</span>

                <h2>Перевірити бронювання</h2>

                <p class="check-reservation-text">
                    Введіть номер бронювання та номер телефону, який було вказано під час оформлення заявки.
                </p>

                <?php if ($error !== ''): ?>
                    <div class="message-error">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="reservation_id">Номер бронювання</label>
                        <input 
                            type="text"
                            id="reservation_id"
                            name="reservation_id"
                            placeholder="Наприклад: 15"
                            value="<?= htmlspecialchars($reservationId) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="phone">Телефон</label>
                        <input 
                            type="text"
                            id="phone"
                            name="phone"
                            placeholder="+380990000000 або 0990000000"
                            value="<?= htmlspecialchars($phone) ?>"
                        >
                    </div>

                    <button type="submit" class="btn">
                        Перевірити
                    </button>
                </form>
            </div>

            <?php if ($reservation): ?>
                <div class="reservation-result-card">
                    <h3>Бронювання №<?= htmlspecialchars($reservation['id']) ?></h3>

                    <div class="reservation-status-line">
                        <span class="status-badge status-<?= htmlspecialchars($reservation['status_code']) ?>">
                            <?= htmlspecialchars($reservation['status_name']) ?>
                        </span>
                    </div>

                    <div class="reservation-details">
                        <div>
                            <span>Клієнт</span>
                            <strong><?= htmlspecialchars($reservation['customer_name']) ?></strong>
                        </div>

                        <div>
                            <span>Телефон</span>
                            <strong><?= htmlspecialchars($reservation['phone']) ?></strong>
                        </div>

                        <div>
                            <span>Столик</span>
                            <strong>
                                №<?= htmlspecialchars($reservation['table_number']) ?>,
                                <?= htmlspecialchars($reservation['zone_name']) ?>
                            </strong>
                        </div>

                        <div>
                            <span>Кількість місць</span>
                            <strong><?= htmlspecialchars($reservation['seats']) ?></strong>
                        </div>

                        <div>
                            <span>Кількість гостей</span>
                            <strong><?= htmlspecialchars($reservation['guests']) ?></strong>
                        </div>

                        <div>
                            <span>Дата</span>
                            <strong><?= htmlspecialchars($reservation['reservation_date']) ?></strong>
                        </div>

                        <div>
                            <span>Час</span>
                            <strong><?= htmlspecialchars(substr($reservation['reservation_time'], 0, 5)) ?></strong>
                        </div>

                        <div>
                            <span>Тривалість</span>
                            <strong><?= htmlspecialchars($reservation['duration_hours']) ?> год.</strong>
                        </div>
                    </div>

                    <?php if (!empty($reservation['comment'])): ?>
                        <div class="reservation-comment">
                            <span>Коментар:</span>
                            <p><?= htmlspecialchars($reservation['comment']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
