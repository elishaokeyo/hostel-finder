<?php
// On Wasmer the database details come from environment variables.
// On your own PC the values after the ?: are used instead.
function env_or(string $key, string $default): string
{
    $v = getenv($key);
    if ($v === false) {
        $v = $_SERVER[$key] ?? $_ENV[$key] ?? false;
    }
    return ($v !== false && $v !== '') ? (string)$v : $default;
}
define('DB_HOST', env_or('DB_HOST', 'localhost'));
define('DB_PORT', env_or('DB_PORT', '3306'));
define('DB_NAME', env_or('DB_NAME', 'hostel_finder'));
define('DB_USER', env_or('DB_USERNAME', 'root'));
define('DB_PASS', env_or('DB_PASSWORD', ''));

// Photo uploads
const UPLOAD_DIR = __DIR__ . '/uploads/hostels';
const UPLOAD_URL = 'uploads/hostels';
// Photo uploads
const UPLOAD_DIR = __DIR__ . '/uploads/hostels';
const UPLOAD_URL = 'uploads/hostels';


// Hours a visitor must wait before reviewing the same hostel again. Set to 0 while testing.
const REVIEW_COOLDOWN_HOURS = 24;

// true  = new reviews wait for admin approval before they appear.
// false = reviews appear immediately.
const REVIEWS_NEED_APPROVAL = true;

// Your WhatsApp number in international format: digits only, no + or spaces
// (country code first, then the number without a leading 0). Landlords use it on the
// login page to request an account. Leave empty to hide the request box.
const ADMIN_WHATSAPP = '254111389090';

// Add a column to an existing table only if it is missing (keeps old installs working).
function ensure_column(string $table, string $column, string $definition): void
{
    $st = db()->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $st->execute([$column]);
    if (!$st->fetch()) db()->exec("ALTER TABLE `$table` ADD COLUMN $definition");
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        // Tables and columns are created automatically, so no extra import is needed.
        if (env_or('RUN_MIGRATIONS', '1') === '1') {
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
    }
    return $pdo;
}

// Recalculate a hostel's average rating and review count from its APPROVED reviews.
function refresh_rating(int $hostelId): void
{
    db()->prepare("UPDATE hostels SET
        rating = COALESCE((SELECT ROUND(AVG(rating), 1) FROM reviews WHERE hostel_id = ? AND status = 'approved'), 0),
        reviews_count = (SELECT COUNT(*) FROM reviews WHERE hostel_id = ? AND status = 'approved')
        WHERE id = ?")->execute([$hostelId, $hostelId, $hostelId]);
}
