<?php
require_once __DIR__ . '/config/db.php';

date_default_timezone_set('Europe/Kyiv');

$pdo = getDbConnection();

$errors = [];
$successMessage = '';
$createdReservationId = null;

$selectedTableId = isset($_GET['table_id']) ? (int)$_GET['table_id'] : 0;

$customerName = '';
$phone = '';
$email = '';
$guests = 1;
$reservationDate = '';
$reservationTime = '';
$durationHours = 2;
$comment = '';

$restaurantOpenTime = '10:00';
$restaurantCloseTime = '22:00';
$today = date('Y-m-d');

$tablesSql = "
    SELECT 
        rt.id,
        rt.table_number,
        rt.seats,
        rz.name AS zone_name
    FROM restaurant_tables rt
    JOIN restaurant_zones rz ON rt.zone_id = rz.id
    WHERE rt.is_active = TRUE
    ORDER BY CAST(regexp_replace(rt.table_number, '\\D', '', 'g') AS INTEGER) ASC
";

$tablesStmt = $pdo->query($tablesSql);
$tables = $tablesStmt->fetchAll();

$selectedTableData = null;

foreach ($tables as $table) {
    if ((int)$table['id'] === $selectedTableId) {
        $selectedTableData = $table;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $guests = (int)($_POST['guests'] ?? 0);
    $selectedTableId = (int)($_POST['table_id'] ?? 0);
    $reservationDate = $_POST['reservation_date'] ?? '';
    $reservationTime = $_POST['reservation_time'] ?? '';
    $durationHours = (int)($_POST['duration_hours'] ?? 2);
    $comment = trim($_POST['comment'] ?? '');

    if ($customerName === '') {
        $errors[] = 'Вкажіть ім’я.';
    }

    $phoneForCheck = preg_replace('/[\s\-\(\)]/', '', $phone);

    if ($phoneForCheck === '') {
        $errors[] = 'Вкажіть телефон.';
    } elseif (!preg_match('/^(\+380\d{9}|0\d{9})$/', $phoneForCheck)) {
        $errors[] = 'Телефон має бути у форматі +380XXXXXXXXX або 0XXXXXXXXX.';
    } else {
        $phone = $phoneForCheck;
    }

    if ($email === '') {
        $errors[] = 'Вкажіть email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некоректний формат email.';
    }

    if ($guests <= 0) {
        $errors[] = 'Кількість гостей має бути більшою за 0.';
    }

    if ($selectedTableId <= 0) {
        $errors[] = 'Оберіть столик на схемі залу.';
    }

    if ($reservationDate === '') {
        $errors[] = 'Оберіть дату бронювання.';
    }

    if ($reservationTime === '') {
        $errors[] = 'Оберіть час бронювання.';
    }

    if ($durationHours < 1 || $durationHours > 4) {
        $errors[] = 'Оберіть коректну тривалість бронювання.';
    }

    if ($reservationDate !== '' && $reservationTime !== '') {
        $reservationDateTime = new DateTime($reservationDate . ' ' . $reservationTime);
        $currentDateTime = new DateTime();

        if ($reservationDateTime < $currentDateTime) {
            $errors[] = 'Не можна створити бронювання на минулу дату або час.';
        }
    }

    if ($reservationDate !== '' && $reservationTime !== '' && $durationHours >= 1 && $durationHours <= 4) {
        $startDateTime = new DateTime($reservationDate . ' ' . $reservationTime);
        $endDateTime = clone $startDateTime;
        $endDateTime->modify('+' . $durationHours . ' hours');

        $openDateTime = new DateTime($reservationDate . ' ' . $restaurantOpenTime);
        $closeDateTime = new DateTime($reservationDate . ' ' . $restaurantCloseTime);

        if ($startDateTime < $openDateTime) {
            $errors[] = 'Ресторан працює з ' . $restaurantOpenTime . '. Оберіть пізніший час.';
        }

        if ($endDateTime > $closeDateTime) {
            $errors[] = 'Бронювання має завершитися до ' . $restaurantCloseTime . '. Зменшіть тривалість або оберіть раніший час.';
        }
    }

    $selectedTableData = null;

    foreach ($tables as $table) {
        if ((int)$table['id'] === $selectedTableId) {
            $selectedTableData = $table;
            break;
        }
    }

    if (!$selectedTableData && $selectedTableId > 0) {
        $errors[] = 'Обраний столик не знайдено.';
    }

    if ($selectedTableData && $guests > (int)$selectedTableData['seats']) {
        $errors[] = 'Кількість гостей перевищує кількість місць за цим столиком.';
    }

    if (empty($errors)) {
        $conflictSql = "
            WITH requested AS (
                SELECT
                    CAST(:reservation_date AS date) + CAST(:reservation_time AS time) AS requested_start,
                    CAST(:reservation_date AS date) + CAST(:reservation_time AS time) 
                        + (CAST(:duration_hours AS integer) * INTERVAL '1 hour') AS requested_end
            )
            SELECT COUNT(*)
            FROM reservations r
            JOIN reservation_statuses rs ON r.status_id = rs.id
            CROSS JOIN requested req
            WHERE r.table_id = :table_id
              AND rs.code IN ('new', 'confirmed')
              AND (r.reservation_date + r.reservation_time) < req.requested_end
              AND (
                    (r.reservation_date + r.reservation_time) 
                    + (r.duration_hours * INTERVAL '1 hour')
                  ) > req.requested_start
        ";

        $conflictStmt = $pdo->prepare($conflictSql);
        $conflictStmt->execute([
            'table_id' => $selectedTableId,
            'reservation_date' => $reservationDate,
            'reservation_time' => $reservationTime,
            'duration_hours' => $durationHours
        ]);

        $conflictsCount = (int)$conflictStmt->fetchColumn();

        if ($conflictsCount > 0) {
            $errors[] = 'Цей столик уже заброньований на обраний проміжок часу.';
        }
    }

    if (empty($errors)) {
        $insertSql = "
            INSERT INTO reservations (
                user_id,
                table_id,
                status_id,
                customer_name,
                phone,
                email,
                guests,
                reservation_date,
                reservation_time,
                duration_hours,
                comment
            )
            VALUES (
                NULL,
                :table_id,
                (SELECT id FROM reservation_statuses WHERE code = 'new' LIMIT 1),
                :customer_name,
                :phone,
                :email,
                :guests,
                :reservation_date,
                :reservation_time,
                :duration_hours,
                :comment
            )
            RETURNING id
        ";

        $insertStmt = $pdo->prepare($insertSql);

        $insertStmt->execute([
            'table_id' => $selectedTableId,
            'customer_name' => $customerName,
            'phone' => $phone,
            'email' => $email,
            'guests' => $guests,
            'reservation_date' => $reservationDate,
            'reservation_time' => $reservationTime,
            'duration_hours' => $durationHours,
            'comment' => $comment !== '' ? $comment : null
        ]);

        $createdReservationId = $insertStmt->fetchColumn();

        $successMessage = 'Бронювання успішно створено! Номер бронювання: ' . $createdReservationId;

        $customerName = '';
        $phone = '';
        $email = '';
        $guests = 1;
        $reservationDate = '';
        $reservationTime = '';
        $durationHours = 2;
        $comment = '';
        $selectedTableId = 0;
        $selectedTableData = null;
    }
}

$showBookingFields = !empty($errors);

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container">
        <h2>Бронювання столика</h2>

        <div class="reservation-layout">
            <div class="map-card">
                <h3>Схема залу ресторану</h3>
                <p class="map-note">
                    Натисніть на потрібний столик на схемі, після цього оберіть дату, час і перевірте доступність.
                </p>

                <div class="map-scroll">
                    <div class="restaurant-layout">
                        <div class="layout-zone zone-main">Основна зала</div>
                        <div class="layout-zone zone-window">Біля вікна</div>
                        <div class="layout-zone zone-bar">Біля бару</div>
                        <div class="layout-zone zone-vip">VIP-зона</div>
                        <div class="layout-zone zone-terrace">Тераса</div>

                        <?php foreach ($tables as $table): ?>
                            <?php
                                $mapClass = 'map-' . strtolower($table['table_number']);
                                $selectedClass = ((int)$table['id'] === $selectedTableId) ? 'selected' : '';
                            ?>

                            <button
                                type="button"
                                class="map-table <?= $mapClass ?> <?= $selectedClass ?>"
                                data-id="<?= htmlspecialchars($table['id']) ?>"
                                data-number="<?= htmlspecialchars($table['table_number']) ?>"
                                data-seats="<?= htmlspecialchars($table['seats']) ?>"
                                data-zone="<?= htmlspecialchars($table['zone_name']) ?>"
                            >
                                <?= htmlspecialchars($table['table_number']) ?>
                                <small><?= htmlspecialchars($table['seats']) ?> місць</small>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="selected-table-box" id="selectedTableBox">
                    <?php if ($selectedTableData): ?>
                        <strong>Обраний столик:</strong>
                        №<?= htmlspecialchars($selectedTableData['table_number']) ?>,
                        <?= htmlspecialchars($selectedTableData['seats']) ?> місць,
                        зона: <?= htmlspecialchars($selectedTableData['zone_name']) ?>
                    <?php else: ?>
                        <strong>Обраний столик:</strong> ще не вибрано
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-card">
                <h3>1. Перевірка доступності</h3>

                <?php if (!empty($successMessage)): ?>
                    <div class="message-success">
                        <?= htmlspecialchars($successMessage) ?>
                        <br>
                        <a href="/restaurant/check_reservation.php">
                            Перевірити статус бронювання
                        </a>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="message-error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" id="bookingForm">
                    <input type="hidden" name="table_id" id="table_id" value="<?= htmlspecialchars($selectedTableId) ?>">

                    <div class="form-group">
                        <label for="reservation_date">Дата</label>
                        <input
                            type="date"
                            id="reservation_date"
                            name="reservation_date"
                            min="<?= htmlspecialchars($today) ?>"
                            value="<?= htmlspecialchars($reservationDate) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="reservation_time">Час</label>
                        <input
                            type="time"
                            id="reservation_time"
                            name="reservation_time"
                            min="<?= htmlspecialchars($restaurantOpenTime) ?>"
                            max="<?= htmlspecialchars($restaurantCloseTime) ?>"
                            step="1800"
                            value="<?= htmlspecialchars($reservationTime) ?>"
                        >
                        <small class="form-hint">
                            Ресторан працює з <?= htmlspecialchars($restaurantOpenTime) ?> до <?= htmlspecialchars($restaurantCloseTime) ?>.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="duration_hours">Тривалість бронювання</label>
                        <select id="duration_hours" name="duration_hours">
                            <option value="1" <?= $durationHours === 1 ? 'selected' : '' ?>>1 година</option>
                            <option value="2" <?= $durationHours === 2 ? 'selected' : '' ?>>2 години</option>
                            <option value="3" <?= $durationHours === 3 ? 'selected' : '' ?>>3 години</option>
                            <option value="4" <?= $durationHours === 4 ? 'selected' : '' ?>>4 години</option>
                        </select>
                    </div>

                    <button type="button" class="btn check-btn" id="checkAvailabilityBtn">
                        Перевірити доступність
                    </button>

                    <div id="availabilityMessage" class="availability-message info">
                        Спочатку оберіть столик на схемі, дату та час.
                    </div>

                    <div id="bookingFields" class="booking-fields <?= $showBookingFields ? '' : 'is-hidden' ?>">
                        <div class="form-divider"></div>

                        <h3>2. Дані для бронювання</h3>

                        <div class="form-group">
                            <label for="customer_name">Ім’я</label>
                            <input
                                type="text"
                                id="customer_name"
                                name="customer_name"
                                value="<?= htmlspecialchars($customerName) ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="phone">Телефон</label>
                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                placeholder="+380990000000 або 0990000000"
                                title="Введіть телефон у форматі +380XXXXXXXXX або 0XXXXXXXXX"
                                value="<?= htmlspecialchars($phone) ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="example@gmail.com"
                                value="<?= htmlspecialchars($email) ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="guests">Кількість гостей</label>
                            <input
                                type="number"
                                id="guests"
                                name="guests"
                                min="1"
                                value="<?= htmlspecialchars((string)$guests) ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="comment">Коментар</label>
                            <textarea
                                id="comment"
                                name="comment"
                                rows="4"
                            ><?= htmlspecialchars($comment) ?></textarea>
                        </div>

                        <button type="submit" class="btn">
                            Підтвердити бронювання
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
    const tableButtons = document.querySelectorAll('.map-table');
    const tableInput = document.getElementById('table_id');
    const selectedTableBox = document.getElementById('selectedTableBox');

    const dateInput = document.getElementById('reservation_date');
    const timeInput = document.getElementById('reservation_time');
    const durationInput = document.getElementById('duration_hours');

    const restaurantOpenTime = '<?= $restaurantOpenTime ?>';
    const restaurantCloseTime = '<?= $restaurantCloseTime ?>';

    const checkBtn = document.getElementById('checkAvailabilityBtn');
    const availabilityMessage = document.getElementById('availabilityMessage');
    const bookingFields = document.getElementById('bookingFields');
    const bookingForm = document.getElementById('bookingForm');

    let availabilityChecked = <?= $showBookingFields ? 'true' : 'false' ?>;

    function hideBookingForm() {
        bookingFields.classList.add('is-hidden');
        availabilityChecked = false;
    }

    function showMessage(type, text) {
        availabilityMessage.className = 'availability-message ' + type;
        availabilityMessage.textContent = text;
    }

    function selectTable(button) {
        if (button.classList.contains('occupied')) {
            showMessage('error', 'Цей столик уже зайнятий на обрану дату і час.');
            return;
        }

        tableButtons.forEach(btn => btn.classList.remove('selected'));
        button.classList.add('selected');

        const id = button.dataset.id;
        const number = button.dataset.number;
        const seats = button.dataset.seats;
        const zone = button.dataset.zone;

        tableInput.value = id;

        selectedTableBox.innerHTML =
            '<strong>Обраний столик:</strong> №' + number +
            ', ' + seats + ' місць, зона: ' + zone;

        hideBookingForm();
        showMessage('info', 'Тепер оберіть дату, час і натисніть “Перевірити доступність”.');
    }

    function markOccupiedTables(occupiedIds) {
        tableButtons.forEach(button => {
            button.classList.remove('occupied');

            if (occupiedIds.includes(button.dataset.id)) {
                button.classList.add('occupied');
            }
        });
    }

    function validateNotPast(date, time) {
        const selectedDateTime = new Date(date + 'T' + time);
        const currentDateTime = new Date();

        if (selectedDateTime < currentDateTime) {
            showMessage('error', 'Не можна створити бронювання на минулу дату або час.');
            return false;
        }

        return true;
    }

    function validateWorkingHours(date, time, duration) {
        const start = new Date(date + 'T' + time);
        const end = new Date(start.getTime() + duration * 60 * 60 * 1000);

        const open = new Date(date + 'T' + restaurantOpenTime);
        const close = new Date(date + 'T' + restaurantCloseTime);

        if (start < open) {
            showMessage('error', 'Ресторан працює з ' + restaurantOpenTime + '. Оберіть пізніший час.');
            return false;
        }

        if (end > close) {
            showMessage(
                'error',
                'Бронювання має завершитися до ' + restaurantCloseTime + '. Зменшіть тривалість або оберіть раніший час.'
            );
            return false;
        }

        return true;
    }

    function checkAvailability() {
        const selectedTableId = tableInput.value;
        const date = dateInput.value;
        const time = timeInput.value;
        const duration = durationInput.value;

        hideBookingForm();

        if (!selectedTableId) {
            showMessage('error', 'Спочатку оберіть столик на схемі.');
            return;
        }

        if (!date || !time) {
            showMessage('error', 'Оберіть дату та час бронювання.');
            return;
        }

        if (!validateNotPast(date, time)) {
            return;
        }

        if (!validateWorkingHours(date, time, Number(duration))) {
            return;
        }

        fetch(
            '/restaurant/check_availability.php?date=' + encodeURIComponent(date) +
            '&time=' + encodeURIComponent(time) +
            '&duration=' + encodeURIComponent(duration)
        )
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    showMessage('error', 'Не вдалося перевірити доступність.');
                    return;
                }

                const occupiedIds = data.occupied_tables.map(String);

                markOccupiedTables(occupiedIds);

                if (occupiedIds.includes(selectedTableId)) {
                    tableInput.value = '';

                    tableButtons.forEach(btn => btn.classList.remove('selected'));

                    selectedTableBox.innerHTML =
                        '<strong>Обраний столик:</strong> ще не вибрано';

                    showMessage('error', 'Обраний столик уже зайнятий. Оберіть інший столик.');
                    return;
                }

                bookingFields.classList.remove('is-hidden');
                availabilityChecked = true;

                showMessage('success', 'Столик вільний. Тепер можна заповнити форму бронювання.');
            })
            .catch(() => {
                showMessage('error', 'Помилка перевірки доступності.');
            });
    }

    tableButtons.forEach(button => {
        button.addEventListener('click', function () {
            selectTable(this);
        });
    });

    dateInput.addEventListener('change', function () {
        hideBookingForm();
        showMessage('info', 'Після зміни дати потрібно повторно перевірити доступність.');
    });

    timeInput.addEventListener('change', function () {
        hideBookingForm();
        showMessage('info', 'Після зміни часу потрібно повторно перевірити доступність.');
    });

    durationInput.addEventListener('change', function () {
        hideBookingForm();
        showMessage('info', 'Після зміни тривалості потрібно повторно перевірити доступність.');
    });

    checkBtn.addEventListener('click', checkAvailability);

    bookingForm.addEventListener('submit', function (event) {
        if (!availabilityChecked) {
            event.preventDefault();
            showMessage('error', 'Спочатку перевірте доступність столика.');
        }
    });
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
