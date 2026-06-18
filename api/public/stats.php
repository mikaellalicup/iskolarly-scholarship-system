<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");

try {
    // Get scholar count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM student_profiles WHERE scholar_status = 'active'");
    $scholars = $stmt->fetch();
    
    // Get total funds distributed
    $stmt = $pdo->query("SELECT SUM(amount_per_term) as total FROM scholarship_programs");
    $funds = $stmt->fetch();
    
    // Get approval rate
    $stmt = $pdo->query("SELECT 
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
        COUNT(*) as total 
        FROM applications");
    $rates = $stmt->fetch();
    $approval_rate = $rates['total'] > 0 ? round(($rates['approved'] / $rates['total']) * 100) : 0;
    
    // Get partner institutions count
    $stmt = $pdo->query("SELECT COUNT(DISTINCT college) as total FROM student_profiles");
    $institutions = $stmt->fetch();
    
    echo json_encode([
        'scholars' => number_format($scholars['total'] ?? 0),
        'funds' => '₱' . number_format($funds['total'] ?? 0),
        'satisfaction' => $approval_rate . '%',
        'institutions' => $institutions['total'] ?? 0
    ]);

} catch (PDOException $e) {
    error_log("Stats error: " . $e->getMessage());
    echo json_encode([
        'scholars' => '—',
        'funds' => '—',
        'satisfaction' => '—',
        'institutions' => '—'
    ]);
}
?>