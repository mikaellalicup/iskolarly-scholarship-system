<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

// Check authentication
if (!isLoggedIn()) {
    sendError('Unauthorized', 401);
}

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    sendError('No file uploaded or upload error');
}

$user_id = getCurrentUserId();

// Validate document type
$document_type = $_POST['document_type'] ?? '';
$allowed_types = ['grades', 'enrollment', 'income_proof', 'gov_id', 'id_photo', 'recommendation', 'award', 'other'];
if (!in_array($document_type, $allowed_types)) {
    sendError('Invalid document type');
}

// Validate file
$file = $_FILES['file'];
$file_name = $file['name'];
$file_tmp = $file['tmp_name'];
$file_size = $file['size'];
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

$allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
if (!in_array($file_ext, $allowed_extensions)) {
    sendError('Only PDF, JPG, and PNG files are allowed');
}

if ($file_size > 5 * 1024 * 1024) {
    sendError('File size must be less than 5MB');
}

// Generate unique filename
$new_filename = date('Ymd_His') . '_' . uniqid() . '.' . $file_ext;
$upload_dir = __DIR__ . '/../../uploads/documents/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$file_path = $upload_dir . $new_filename;

// Move uploaded file
if (!move_uploaded_file($file_tmp, $file_path)) {
    sendError('Failed to save file', 500);
}

try {
    // Get or create application
    $application_id = $_POST['application_id'] ?? null;
    
    if (!$application_id) {
        // Create a draft application if none exists
        $program_id = $_POST['program_id'] ?? 1;
        $school_year = $_POST['school_year'] ?? date('Y') . '-' . (date('Y') + 1);
        $semester = $_POST['semester'] ?? '1st Semester';
        
        $app_number = generateApplicationNumber();
        $stmt = $pdo->prepare("INSERT INTO applications 
            (user_id, program_id, application_number, school_year, semester, status) 
            VALUES (?, ?, ?, ?, ?, 'draft')");
        $stmt->execute([$user_id, $program_id, $app_number, $school_year, $semester]);
        $application_id = $pdo->lastInsertId();
    }

    // Save document record
    $relative_path = 'uploads/documents/' . $new_filename;
    $stmt = $pdo->prepare("INSERT INTO application_documents 
        (application_id, document_type, file_name, file_path, file_size, mime_type, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    
    $stmt->execute([
        $application_id,
        $document_type,
        $file_name,
        $relative_path,
        $file_size,
        $file['type']
    ]);

    $doc_id = $pdo->lastInsertId();

    sendSuccess([
        'document_id' => $doc_id,
        'application_id' => $application_id,
        'file_name' => $file_name,
        'file_path' => $relative_path
    ], 'Document uploaded successfully');

} catch (PDOException $e) {
    // Clean up file if database insert fails
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    error_log("Document upload error: " . $e->getMessage());
    sendError('Failed to save document record', 500);
}
?>