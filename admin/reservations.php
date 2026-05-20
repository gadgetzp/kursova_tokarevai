<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$pdo = getDbConnection();

$dateFilter = $_GET['date'] ?? '';
$statusFilter = $_GET['status_id'] ?? '';
$searchFilter = trim($_GET['search'] ?? '');

$statusesStmt = $pdo->query("
    SELECT id, code, name
    FROM reservation_statuses
    ORDER BY id
");

$statuses = $statusesStmt->fetchAll();

$conditions = [];
$params = [];

if ($dateFilter !== '') {
    $conditions[] = 'r.reservation_date = :date_filter';
    $params['date_filter'] = $dateFilter;
}

if ($statusFilter !== '') {
    $conditions[] = 'r.status_id = :status_id';
    $params['status_id'] = (int)$statusFilter;
}

if ($searchFilter !== '') {
    $conditions[] = "(
        r.customer_name ILIKE :search_filter
        OR r.phone ILIKE :search_filter
        OR r.email ILIKE :search_filter
        OR rt.table_number ILIKE :search_filter
    )";
    $params['search_filter'] = '%' . $searchFilter . '%';
}

$whereSql = '';

if (!empty($conditions)) {
    $whereSql = 'WHERE ' . implode(' AND ', $conditions);
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
        r.created_at,
        rt.table_number,
        rt.seats,
        rz.name AS zone_name,
        rs.id AS status_id,
        rs.code AS status_code,
        rs.name AS status_name
    FROM reservations r
    JOIN restaurant_tables rt ON r.table_id = rt.id
    JOIN restaurant_zones rz ON rt.zone_id = rz.id
    JOIN reservation_statuses rs ON r.status_id = rs.id
    $whereSql
    ORDER BY r.reservation_date DESC, r.reservation_time DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="container">
        <h2>Адмін-панель: бронювання</h2>

        <div class="admin-top-actions">
            <p>
                Ви увійшли як адміністратор:
                <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Адміністратор') ?></strong>
            </p>

            <a href="/restaurant/admin/logout.php" class="btn small-btn">
                Вийти
            </a>
        </div>

        <form method="GET" class="admin-filter">
            <div class="filter-group">
                <label for="date">Дата</label>
                <input 
                    type="date" 
                    id="date" 
                    name="date"
                    value="<?= htmlspecialchars($dateFilter) ?>"
                >
            </div>

            <div class="filter-group">
                <label for="status_id">Статус</label>
                <select id="status_id" name="status_id">
                    <option value="">Усі статуси</option>

                    <?php foreach ($statuses as $status): ?>
                        <option 
                            value="<?= $status['id'] ?>"
                            <?= (string)$status['id'] === (string)$statusFilter ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($status['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group filter-search">
                <label for="search">Пошук</label>
                <input 
                    type="text" 
                    id="search" 
                    name="search"
                    placeholder="Ім’я, телефон, email або столик"
                    value="<?= htmlspecialchars($searchFilter) ?>"
                >
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn small-btn">
                    Застосувати
                </button>

                <a href="/restaurant/admin/reservations.php" class="btn small-btn btn-reset">
                    Скинути
                </a>
            </div>
        </form>

        <div class="admin-summary">
            <div class="summary-card">
                <span>Знайдено бронювань</span>
                <strong><?= count($reservations) ?></strong>
            </div>
        </div>

        <?php if (count($reservations) > 0): ?>
            <div class="admin-table-wrapper">
                <table class="table-list">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Клієнт</th>
                            <th>Телефон</th>
                            <th>Столик</th>
                            <th>Гості</th>
                            <th>Дата</th>
                            <th>Час</th>
                            <th>Тривалість</th>
                            <th>Статус</th>
                            <th>Змінити статус</th>
                            <th>Дія</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><?= htmlspecialchars($reservation['id']) ?></td>

                                <td>
                                    <strong><?= htmlspecialchars($reservation['customer_name']) ?></strong>

                                    <?php if (!empty($reservation['email'])): ?>
                                        <br>
                                        <small><?= htmlspecialchars($reservation['email']) ?></small>
                                    <?php endif; ?>

                                    <?php if (!empty($reservation['comment'])): ?>
                                        <br>
                                        <small>Коментар: <?= htmlspecialchars($reservation['comment']) ?></small>
                                    <?php endif; ?>
                                </td>

                                <td><?= htmlspecialchars($reservation['phone']) ?></td>

                                <td>
                                    №<?= htmlspecialchars($reservation['table_number']) ?>
                                    <br>
                                    <small>
                                        <?= htmlspecialchars($reservation['zone_name']) ?>,
                                        <?= htmlspecialchars($reservation['seats']) ?> місць
                                    </small>
                                </td>

                                <td><?= htmlspecialchars($reservation['guests']) ?></td>

                                <td><?= htmlspecialchars($reservation['reservation_date']) ?></td>

                                <td><?= htmlspecialchars(substr($reservation['reservation_time'], 0, 5)) ?></td>

                                <td>
                                    <?= htmlspecialchars($reservation['duration_hours'] ?? 2) ?> год.
                                </td>

                                <td>
                                    <span class="status-badge status-<?= htmlspecialchars($reservation['status_code']) ?>">
                                        <?= htmlspecialchars($reservation['status_name']) ?>
                                    </span>
                                </td>

                                <td>
                                    <form method="POST" action="/restaurant/admin/update_status.php" class="status-form">
                                        <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">

                                        <select name="status_id">
                                            <?php foreach ($statuses as $status): ?>
                                                <option 
                                                    value="<?= $status['id'] ?>"
                                                    <?= (int)$status['id'] === (int)$reservation['status_id'] ? 'selected' : '' ?>
                                                >
                                                    <?= htmlspecialchars($status['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <button type="submit" class="btn small-btn">
                                            Оновити
                                        </button>
                                    </form>
                                </td>

                                <td>
    <?php if ($reservation['status_code'] !== 'cancelled' && $reservation['status_code'] !== 'completed'): ?>
        <form 
            method="POST" 
            action="/restaurant/admin/cancel_reservation.php"
            onsubmit="return confirm('Скасувати це бронювання?');"
        >
            <input 
                type="hidden" 
                name="reservation_id" 
                value="<?= $reservation['id'] ?>"
            >

            <button type="submit" class="btn small-btn btn-cancel">
                Скасувати
            </button>
        </form>
    <?php else: ?>
        <span class="muted-text">—</span>
    <?php endif; ?>
</td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-admin-message">
                За обраними фільтрами бронювань не знайдено.
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
