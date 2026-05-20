<?php
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container">
        <h1>Онлайн-резервування столиків у ресторані</h1>
        <p>
            Сервіс дозволяє клієнтам швидко обрати столик, дату, час та оформити бронювання онлайн.
        </p>

        <a href="/restaurant/reserve.php" class="btn">Забронювати столик</a>
        <a href="/restaurant/tables.php" class="btn btn-light">Переглянути столики</a>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2>Можливості сервісу</h2>

        <div class="cards">
            <div class="card">
                <h3>Перегляд столиків</h3>
                <p>
                    Користувач може переглянути доступні столики ресторану, їхню місткість та розташування.
                </p>
            </div>

            <div class="card">
                <h3>Онлайн-бронювання</h3>
                <p>
                    Клієнт заповнює форму, вказує дату, час, кількість гостей і створює бронювання.
                </p>
            </div>

            <div class="card">
                <h3>Адміністрування</h3>
                <p>
                    Адміністратор може переглядати заявки на бронювання та контролювати їхній статус.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="section contacts-section">
    <div class="container">
        <div class="contacts-layout">
            <div class="contacts-info">
                <span class="section-label">Наші контакти</span>
                <h2>Контакти та графік роботи</h2>
                <p class="contacts-text">
                    Ми працюємо щодня та приймаємо онлайн-бронювання столиків через сайт.
                    Оберіть зручну зону ресторану, дату та час відвідування.
                </p>

                <div class="contact-items">
                    <div class="contact-item">
                        <span>📍</span>
                        <div>
                            <h3>Адреса</h3>
                            <p>м. Запоріжжя, проспект Соборний, 100</p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <span>☎</span>
                        <div>
                            <h3>Телефон</h3>
                            <p>+380 99 000 00 00</p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <span>✉</span>
                        <div>
                            <h3>Email</h3>
                            <p>restreserve@gmail.com</p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <span>🕒</span>
                        <div>
                            <h3>Графік роботи</h3>
                            <p>Щодня з 10:00 до 22:00</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="contacts-side-card">
                <h3>RestReserve</h3>
                <p>
                    Затишний ресторан для зустрічей, сімейних вечерь та особливих подій.
                </p>

                <div class="work-time-box">
                    <strong>Години роботи</strong>
                    <span>10:00 — 22:00</span>
                </div>

                <a href="/restaurant/reserve.php" class="btn">
                    Забронювати столик
                </a>
            </div>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
