<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = getDbConnection();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Введіть email і пароль.';
    } else {
        $stmt = $pdo->prepare("
            SELECT id, name, email, password_hash, role
            FROM users
            WHERE email = :email
              AND role = 'admin'
            LIMIT 1
        ");

        $stmt->execute([
            'email' => $email
        ]);

        $admin = $stmt->fetch();

        if ($admin && ($password === $admin['password_hash'] || password_verify($password, $admin['password_hash']))) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];

            header('Location: /restaurant/admin/reservations.php');
            exit;
        } else {
            $error = 'Неправильний email або пароль.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="login-card">
            <h2>Вхід адміністратора</h2>
            <p>Увійдіть, щоб переглядати та керувати бронюваннями.</p>

            <?php if ($error !== ''): ?>
                <div class="message-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password"
                    >
                </div>

                <button type="submit" class="btn">
                    Увійти
                </button>
            </form>
        </div>
    </div>
</section>

<?php
?>
