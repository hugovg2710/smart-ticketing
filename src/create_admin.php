<?php
/**
 * Crea (o informa de que ya existe) el usuario administrador.
 *
 * Uso (desde la raíz del proyecto):
 *   docker compose exec app php src/create_admin.php
 *
 * Los datos se leen de .env (ADMIN_NAME, ADMIN_EMAIL, ADMIN_PASSWORD).
 * Si ADMIN_PASSWORD está vacío se genera una contraseña aleatoria que se muestra una sola vez.
 * Solo puede ejecutarse desde la línea de comandos (CLI).
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\Database;

Dotenv::createImmutable(__DIR__ . '/../')->safeLoad();

$adminName     = $_ENV['ADMIN_NAME'] ?? 'Administrador';
$adminEmail    = $_ENV['ADMIN_EMAIL'] ?? '';
$adminPassword = $_ENV['ADMIN_PASSWORD'] ?? '';
$generated     = false;

if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Error: define un ADMIN_EMAIL válido en .env\n");
    exit(1);
}

if ($adminPassword === '') {
    $adminPassword = bin2hex(random_bytes(8));
    $generated = true;
} elseif (strlen($adminPassword) < 8) {
    fwrite(STDERR, "Error: ADMIN_PASSWORD debe tener al menos 8 caracteres.\n");
    exit(1);
}

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare('SELECT id FROM users WHERE email = :email');
    $stmt->execute(['email' => $adminEmail]);
    if ($stmt->fetch()) {
        echo "El usuario {$adminEmail} ya existe. No se ha modificado.\n";
        exit(0);
    }

    $stmt = $db->prepare(
        'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)'
    );
    $stmt->execute([
        'name'          => $adminName,
        'email'         => $adminEmail,
        'password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
        'role'          => 'admin',
    ]);

    echo "Administrador creado: {$adminEmail}\n";
    if ($generated) {
        echo "Contraseña generada (guárdala, no se volverá a mostrar): {$adminPassword}\n";
    }
} catch (Exception $e) {
    fwrite(STDERR, 'Error al conectar o insertar en la BBDD: ' . $e->getMessage() . "\n");
    exit(1);
}
