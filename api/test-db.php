<?php
// Debug database connection
echo "<h2>🔍 Database Connection Test</h2>";

// Check if environment variables are set
$host = getenv('SUPABASE_HOST');
$dbname = getenv('SUPABASE_DB');
$username = getenv('SUPABASE_USER');
$password = getenv('SUPABASE_PASSWORD');
$port = getenv('SUPABASE_PORT');

echo "<h3>Environment Variables:</h3>";
echo "SUPABASE_HOST: " . ($host ?: '❌ NOT SET') . "<br>";
echo "SUPABASE_DB: " . ($dbname ?: '❌ NOT SET') . "<br>";
echo "SUPABASE_USER: " . ($username ?: '❌ NOT SET') . "<br>";
echo "SUPABASE_PASSWORD: " . ($password ? '✅ SET (hidden)' : '❌ NOT SET') . "<br>";
echo "SUPABASE_PORT: " . ($port ?: '❌ NOT SET') . "<br><br>";

if (!$password) {
    echo "❌ SUPABASE_PASSWORD is missing!<br>";
    echo "Please add it to Azure Environment Variables.";
    exit;
}

// Try to connect
try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname;",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Connected to Supabase successfully!<br><br>";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "📊 Users in database: " . $result['count'] . "<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM scholarship_programs");
    $result = $stmt->fetch();
    echo "📊 Scholarship programs: " . $result['count'] . "<br>";
    
} catch (PDOException $e) {
    echo "❌ Connection failed:<br>";
    echo "Error: " . $e->getMessage();
}
?>