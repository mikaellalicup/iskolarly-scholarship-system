<?php
// Supabase PostgreSQL connection
$host = getenv('SUPABASE_HOST') ?: 'db.vmeyaztpkkszufnvuvvey.supabase.co';
$dbname = getenv('SUPABASE_DB') ?: 'postgres';
$username = getenv('SUPABASE_USER') ?: 'postgres';
$password = getenv('SUPABASE_PASSWORD') ?: '';
$port = getenv('SUPABASE_PORT') ?: 5432;

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname;",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 30,
        ]
    );
} catch (PDOException $e) {
    error_log("Supabase connection failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed. Please try again later.']);
    exit;
}
?>