<?php
/**
 * API: List Applications
 * 
 * GET /api/applications/list
 * 
 * Returns applications based on user role:
 * - Students: Returns their own applications
 * - Admins: Returns all applications (with optional filters)
 * 
 * Query Parameters:
 * - status: Filter by status (draft, submitted, under_review, approved, rejected, on_hold)
 * - program_id: Filter by scholarship program
 * - school_year: Filter by school year
 * - search: Search by applicant name or application number
 * - limit: Number of records to return (default: 50)
 * - offset: Pagination offset (default: 0)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

// Check authentication
if (!isLoggedIn()) {
    sendError('Unauthorized. Please log in first.', 401);
}

$user_id = getCurrentUserId();
$user_type = $_SESSION['user_type'];
$user_role = $_SESSION['user_type'] ?? 'student';

// Get query parameters for filtering
$status = isset($_GET['status']) ? sanitize($_GET['status']) : null;
$program_id = isset($_GET['program_id']) ? (int)$_GET['program_id'] : null;
$school_year = isset($_GET['school_year']) ? sanitize($_GET['school_year']) : null;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Validate limit and offset
$limit = min($limit, 100); // Max 100 records per request
$offset = max(0, $offset);

try {
    // Build the base query
    $sql = "SELECT 
                a.application_id,
                a.application_number,
                a.status,
                a.school_year,
                a.semester,
                a.remarks,
                a.submitted_at,
                a.created_at,
                a.updated_at,
                sp.program_id,
                sp.program_name,
                sp.program_code,
                sp.scholarship_type,
                sp.amount_per_term,
                sp.deadline_date,
                u.user_id,
                u.username,
                u.email,
                u.user_type,
                CONCAT(s.first_name, ' ', COALESCE(s.middle_name || ' ', ''), s.last_name) AS applicant_name,
                s.student_id,
                s.year_level,
                s.college,
                s.degree_program,
                s.gpa
            FROM applications a
            INNER JOIN scholarship_programs sp ON a.program_id = sp.program_id
            INNER JOIN users u ON a.user_id = u.user_id
            LEFT JOIN student_profiles s ON u.user_id = s.user_id
            WHERE 1=1";

    $params = [];
    $param_index = 1;

    // Filter by user if student
    if ($user_type === 'student') {
        $sql .= " AND a.user_id = $" . $param_index;
        $params[] = $user_id;
        $param_index++;
    }

    // Filter by status
    if ($status) {
        $valid_statuses = ['draft', 'submitted', 'under_review', 'approved', 'rejected', 'on_hold'];
        if (in_array($status, $valid_statuses)) {
            $sql .= " AND a.status = $" . $param_index;
            $params[] = $status;
            $param_index++;
        }
    }

    // Filter by program
    if ($program_id) {
        $sql .= " AND a.program_id = $" . $param_index;
        $params[] = $program_id;
        $param_index++;
    }

    // Filter by school year
    if ($school_year) {
        $sql .= " AND a.school_year = $" . $param_index;
        $params[] = $school_year;
        $param_index++;
    }

    // Search filter
    if ($search) {
        $sql .= " AND (
            a.application_number ILIKE $" . $param_index . 
            " OR CONCAT(s.first_name, ' ', COALESCE(s.middle_name || ' ', ''), s.last_name) ILIKE $" . $param_index . 
            " OR s.student_id ILIKE $" . $param_index . 
            " OR u.email ILIKE $" . $param_index . 
            " OR sp.program_name ILIKE $" . $param_index .
            ")";
        $search_param = '%' . $search . '%';
        $params[] = $search_param;
        $param_index++;
    }

    // Order by most recent first
    $sql .= " ORDER BY a.created_at DESC";

    // Add pagination
    $sql .= " LIMIT $" . $param_index . " OFFSET $" . ($param_index + 1);
    $params[] = $limit;
    $params[] = $offset;

    // Execute main query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $applications = $stmt->fetchAll();

    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as total FROM applications a";
    $count_params = [];

    // Rebuild WHERE clause for count
    $where_clauses = [];
    $count_index = 1;

    if ($user_type === 'student') {
        $where_clauses[] = "a.user_id = $" . $count_index;
        $count_params[] = $user_id;
        $count_index++;
    }

    if ($status && in_array($status, ['draft', 'submitted', 'under_review', 'approved', 'rejected', 'on_hold'])) {
        $where_clauses[] = "a.status = $" . $count_index;
        $count_params[] = $status;
        $count_index++;
    }

    if ($program_id) {
        $where_clauses[] = "a.program_id = $" . $count_index;
        $count_params[] = $program_id;
        $count_index++;
    }

    if ($school_year) {
        $where_clauses[] = "a.school_year = $" . $count_index;
        $count_params[] = $school_year;
        $count_index++;
    }

    if ($search) {
        $where_clauses[] = "(
            a.application_number ILIKE $" . $count_index . 
            " OR EXISTS (SELECT 1 FROM users u WHERE a.user_id = u.user_id AND (u.username ILIKE $" . $count_index . " OR u.email ILIKE $" . $count_index . "))" .
            " OR EXISTS (SELECT 1 FROM student_profiles s WHERE a.user_id = s.user_id AND (CONCAT(s.first_name, ' ', COALESCE(s.middle_name || ' ', ''), s.last_name) ILIKE $" . $count_index . " OR s.student_id ILIKE $" . $count_index . "))" .
            ")";
        $count_params[] = '%' . $search . '%';
        $count_index++;
    }

    if (!empty($where_clauses)) {
        $count_sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $stmt_count = $pdo->prepare($count_sql);
    $stmt_count->execute($count_params);
    $total_count = $stmt_count->fetch();
    $total = (int)($total_count['total'] ?? 0);

    // Get status counts for dashboard stats
    $status_counts = [];
    if ($user_type !== 'student') {
        $status_sql = "SELECT status, COUNT(*) as count FROM applications";
        if ($user_type === 'student') {
            $status_sql .= " WHERE user_id = " . $user_id;
        }
        $status_sql .= " GROUP BY status";
        $stmt_status = $pdo->query($status_sql);
        while ($row = $stmt_status->fetch()) {
            $status_counts[$row['status']] = (int)$row['count'];
        }
    }

    // Get available school years for filter
    $school_years_sql = "SELECT DISTINCT school_year FROM applications ORDER BY school_year DESC";
    $school_years = $pdo->query($school_years_sql)->fetchAll();

    // Get available programs for filter
    $programs_sql = "SELECT program_id, program_name FROM scholarship_programs WHERE is_active = true ORDER BY program_name";
    $programs = $pdo->query($programs_sql)->fetchAll();

    // Calculate additional stats
    $total_applications = $total;
    $submitted_count = 0;
    $pending_count = 0;
    $approved_count = 0;
    $rejected_count = 0;

    foreach ($applications as $app) {
        switch ($app['status']) {
            case 'submitted':
            case 'under_review':
            case 'on_hold':
                $pending_count++;
                break;
            case 'approved':
                $approved_count++;
                break;
            case 'rejected':
                $rejected_count++;
                break;
        }
        if ($app['status'] !== 'draft') {
            $submitted_count++;
        }
    }

    // Return success response
    sendSuccess([
        'applications' => $applications,
        'pagination' => [
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'pages' => ceil($total / $limit)
        ],
        'filters' => [
            'status' => $status,
            'program_id' => $program_id,
            'school_year' => $school_year,
            'search' => $search
        ],
        'stats' => [
            'total' => $total_applications,
            'submitted' => $submitted_count,
            'pending' => $pending_count,
            'approved' => $approved_count,
            'rejected' => $rejected_count,
            'status_breakdown' => $status_counts
        ],
        'filter_options' => [
            'school_years' => $school_years,
            'programs' => $programs
        ]
    ]);

} catch (PDOException $e) {
    error_log("Applications list error: " . $e->getMessage());
    sendError('Failed to fetch applications. Please try again later.', 500);
}
?>