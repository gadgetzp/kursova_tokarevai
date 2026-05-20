<?php
require_once __DIR__ . '/config/db.php';

$pdo = getDbConnection();

$sql = "
    SELECT 
        rt.id,
        rt.table_number,
        rt.seats,
        rz.name AS zone_name,
        rz.description AS zone_description
    FROM restaurant_tables rt
    JOIN restaurant_zones rz ON rt.zone_id = rz.id
    WHERE rt.is_active = TRUE
    ORDER BY 
        rz.name ASC,
        CAST(regexp_replace(rt.table_number, '\D', '', 'g') AS INTEGER) ASC
";

$stmt = $pdo->query($sql);
$tables = $stmt->fetchAll();

$zones = [];

foreach ($tables as $table) {
    $zoneName = $table['zone_name'];

    if (!isset($zones[$zoneName])) {
        $zones[$zoneName] = [
            'description' => $table['zone_description'],
            'tables' => []
        ];
    }

    $zones[$zoneName]['tables'][] = $table;
}

$zoneImages = [
    'Основна зала' => '/restaurant/assets/images/zones/main-hall.png',
    'Біля вікна'   => '/restaurant/assets/images/zones/window-zone.png',
    'Біля бару'    => '/restaurant/assets/images/zones/bar-zone.png',
    'VIP-зона'     => '/restaurant/assets/images/zones/vip-zone.png',
    'Тераса'       => '/restaurant/assets/images/zones/terrace-zone.png'
];

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="page-heading">
            <h2>Столики та зони ресторану</h2>
            <p>
                На цій сторінці можна переглянути основні зони ресторану,
                їхній вигляд, опис та доступні столики для бронювання.
            </p>
        </div>

        <?php if (count($zones) > 0): ?>
            <div class="zones-grid">
                <?php foreach ($zones as $zoneName => $zoneData): ?>
                    <?php
                        $imageUrl = $zoneImages[$zoneName] ?? 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4';
                    ?>

                    <article class="zone-card">
                        <div 
                            class="zone-image"
                            style="background-image: url('<?= htmlspecialchars($imageUrl) ?>');"
                        >
                            <div class="zone-image-overlay">
                                <h3><?= htmlspecialchars($zoneName) ?></h3>
                            </div>
                        </div>

                        <div class="zone-content">
                            <p class="zone-description">
                                <?= htmlspecialchars($zoneData['description'] ?? 'Зона ресторану для комфортного відпочинку гостей.') ?>
                            </p>

                            <div class="zone-tables">
                                <h4>Доступні столики:</h4>

                                <div class="zone-table-list">
                                    <?php foreach ($zoneData['tables'] as $table): ?>
                                        <div class="zone-table-item">
                                            <div>
                                                <strong>Столик №<?= htmlspecialchars($table['table_number']) ?></strong>
                                                <span><?= htmlspecialchars($table['seats']) ?> місць</span>
                                            </div>

                                            <a 
                                                href="/restaurant/reserve.php?table_id=<?= $table['id'] ?>" 
                                                class="btn table-btn"
                                            >
                                                Забронювати
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Наразі активних столиків немає.</p>
        <?php endif; ?>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
