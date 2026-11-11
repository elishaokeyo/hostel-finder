<?php

// Safe environment reader with fallback
function env(string $key, mixed $default = null): mixed
{
    $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return ($val !== false && $val !== null && $val !== '') ? $val : $default;
}

define('DB_HOST', env('DB_HOST', 'db.fr-roub1.bengt.wasmernet.com'));
define('DB_NAME', env('DB_NAME', 'hostel_finder'));
define('DB_USER', env('DB_USER', 'user_767b95e6'));
define('DB_PASS', env('DB_PASS', 'pw_GfN4Am3NR0uAGPrPtIG0rLQ3amxPlzMq'));
define('DB_PORT', env('DB_PORT', '3306'));

define('UPLOAD_DIR', __DIR__ . '/uploads/hostels');
define('UPLOAD_URL', 'uploads/hostels');
define('REVIEW_COOLDOWN_HOURS', 24);
define('REVIEWS_NEED_APPROVAL', true);
define('ADMIN_WHATSAPP', '254111389090');

function ensure_column(string $table, string $column, string $definition): void
{
    $st = db()->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $st->execute([$column]);
    if (!$st->fetch()) {
        db()->exec("ALTER TABLE `$table` ADD COLUMN $definition");
    }
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            DB_HOST,
            DB_PORT,
            DB_NAME
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // PHP 8.5+ Compatibility Check vs standard PHP 8.x
        if (class_exists(\Pdo\Mysql::class)) {
            // PHP 8.5+ namespaced syntax
            $options[\Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT] = false;
        } elseif (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
            // PHP 8.0 - 8.4 syntax
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        // Auto-create initial tables
        $pdo->exec("CREATE TABLE IF NOT EXISTS hostel_photos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hostel_id INT NOT NULL,
            filename VARCHAR(80) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (hostel_id) REFERENCES hostels(id) ON DELETE CASCADE
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hostel_id INT NOT NULL,
            reviewer_name VARCHAR(60) NOT NULL,
            rating TINYINT NOT NULL,
            comment VARCHAR(1000) NOT NULL DEFAULT '',
            ip_hash CHAR(64) NOT NULL,
            status ENUM('pending','approved') NOT NULL DEFAULT 'approved',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (hostel_id) REFERENCES hostels(id) ON DELETE CASCADE
        )");

        ensure_column('reviews', 'status', "status ENUM('pending','approved') NOT NULL DEFAULT 'approved'");
        ensure_column('hostels', 'owner_id', 'owner_id INT NULL');
    }

    return $pdo;
}

function refresh_rating(int $hostelId): void
{
    $stmt = db()->prepare("UPDATE hostels SET
        rating = COALESCE((SELECT ROUND(AVG(rating), 1) FROM reviews WHERE hostel_id = ? AND status = 'approved'), 0),
        reviews_count = (SELECT COUNT(*) FROM reviews WHERE hostel_id = ? AND status = 'approved')
        WHERE id = ?");

    $stmt->execute([$hostelId, $hostelId, $hostelId]);
}
