<?php
/**
 * Database bootstrap.
 * Connects to MySQL via PDO. Credentials come from config.php (which
 * itself reads environment variables, falling back to local defaults).
 *
 * On first run this will create the database and tables automatically
 * if the configured user has permission to do so. In most hosting
 * environments the database itself will already exist and only the
 * `CREATE TABLE IF NOT EXISTS` statements will actually do anything —
 * that's fine, both paths are handled below.
 */

function get_db(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';

    $dsnBase = sprintf(
        'mysql:host=%s;port=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['charset']
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        // Connect without selecting a database first, so we can create
        // it if it doesn't exist yet.
        $bootstrap = new PDO($dsnBase, $config['user'], $config['pass'], $options);
        $bootstrap->exec(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            str_replace('`', '', $config['name'])
        ));
    } catch (PDOException $e) {
        throw new RuntimeException(
            'Could not connect to MySQL. Check config.php / your DB_* environment variables. ' .
            'Original error: ' . $e->getMessage()
        );
    }

    $dsn = $dsnBase . ';dbname=' . $config['name'];
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS users (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name          VARCHAR(120) NOT NULL,
            email         VARCHAR(190) NOT NULL,
            phone         VARCHAR(30) NULL,
            password_hash VARCHAR(255) NOT NULL,
            role          VARCHAR(20) NOT NULL DEFAULT "user",
            created_at    DATETIME NOT NULL,
            UNIQUE KEY uq_users_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');

    // Foreign keys (orders -> users, bookings -> users/orders) only work
    // between InnoDB tables. If `users` already existed from an older
    // run on a different engine (MyISAM is the classic culprit — some
    // MySQL/MariaDB configs default to it), creating those FKs below
    // fails with "errno 150: Foreign key constraint is incorrectly
    // formed". Self-heal that here instead of leaving people to debug
    // a cryptic error.
    $usersEngine = $pdo->query("
        SELECT ENGINE FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
    ")->fetch();
    if ($usersEngine && strtoupper((string) $usersEngine['ENGINE']) !== 'INNODB') {
        $pdo->exec('ALTER TABLE users ENGINE=InnoDB');
    }

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS orders (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id     INT UNSIGNED NOT NULL,
            total       DECIMAL(10,2) NOT NULL,
            status      VARCHAR(20) NOT NULL DEFAULT "pending",
            payment_ref VARCHAR(60) NULL,
            visit_date  DATE NULL,
            created_at  DATETIME NOT NULL,
            KEY idx_orders_user (user_id),
            CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS bookings (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id     INT UNSIGNED NOT NULL,
            order_id    INT UNSIGNED NULL,
            ticket_id   VARCHAR(40) NOT NULL,
            ticket_name VARCHAR(160) NOT NULL,
            category    VARCHAR(40) NOT NULL,
            qty         INT UNSIGNED NOT NULL,
            price       DECIMAL(10,2) NOT NULL,
            status      VARCHAR(20) NOT NULL DEFAULT "upcoming",
            created_at  DATETIME NOT NULL,
            KEY idx_bookings_user (user_id),
            KEY idx_bookings_order (order_id),
            CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_bookings_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS messages (
            id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name       VARCHAR(120) NOT NULL,
            email      VARCHAR(190) NOT NULL,
            subject    VARCHAR(120) NOT NULL,
            message    TEXT NOT NULL,
            is_read    TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');

    // A couple of older installs may have been created before the
    // `role` / `is_read` columns existed — add them if missing so
    // upgrades don't require a manual migration step.
    $usersCols = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetchAll();
    if (empty($usersCols)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT "user"');
    }
    $msgCols = $pdo->query("SHOW COLUMNS FROM messages LIKE 'is_read'")->fetchAll();
    if (empty($msgCols)) {
        $pdo->exec('ALTER TABLE messages ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0');
    }
    $bookingCols = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'order_id'")->fetchAll();
    if (empty($bookingCols)) {
        $pdo->exec('ALTER TABLE bookings ADD COLUMN order_id INT UNSIGNED NULL AFTER user_id');
        $pdo->exec('ALTER TABLE bookings ADD KEY idx_bookings_order (order_id)');
        $pdo->exec('ALTER TABLE bookings ADD CONSTRAINT fk_bookings_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL');
    }
    $orderVisitDateCols = $pdo->query("SHOW COLUMNS FROM orders LIKE 'visit_date'")->fetchAll();
    if (empty($orderVisitDateCols)) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN visit_date DATE NULL AFTER payment_ref');
    }

    // Seed one default admin account so the admin panel is reachable
    // out of the box. Change or delete this in a real deployment.
    $adminCount = $pdo->query("SELECT COUNT(*) AS c FROM users WHERE role = 'admin'")->fetch();
    if ((int) $adminCount['c'] === 0) {
        $seed = $pdo->prepare('
            INSERT INTO users (name, email, phone, password_hash, role, created_at)
            VALUES (:name, :email, NULL, :password_hash, "admin", :created_at)
        ');
        try {
            $seed->execute([
                ':name' => 'Park Admin',
                ':email' => 'admin@ticketstothrill.example',
                ':password_hash' => password_hash('Admin@123', PASSWORD_DEFAULT),
                ':created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $e) {
            // Email already taken by a non-admin row — skip seeding
            // rather than fail the whole request.
        }
    }

    return $pdo;
}
