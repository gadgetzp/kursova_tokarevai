<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Онлайн-резервування столиків</title>
    <link rel="stylesheet" href="/restaurant/assets/style.css">
</head>
<body>

<header class="header">
    <div class="container header-content">
        <a href="/restaurant/index.php" class="logo">RestReserve</a>

        <nav class="nav">
            <a href="/restaurant/index.php">Головна</a>
            <a href="/restaurant/tables.php">Столики</a>
            <a href="/restaurant/reserve.php">Бронювання</a>
                <a href="/restaurant/check_reservation.php">Перевірити бронювання</a>
            <a href="/restaurant/admin/reservations.php">Адмін</a>
        </nav>
    </div>
</header>

<main>
