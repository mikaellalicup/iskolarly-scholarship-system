<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['first_name', 'last_name', 'email', 'username', 'password', 'date_of_birth', 
             'sex', 'contact_number', 'student_id', 'year_level', 'college', 'degree_program'];
foreach ($required as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        sendError("$field is required");
    }
}

// Sanitize inputs
$first_name = sanitize($data['first_name']);
$last_name = sanitize($data['last_name']);
$middle_name = isset($data['middle_name']) ? sanitize($data['middle_name']) : null;
$email = sanitize($data['email']);
$username = sanitize($data['username']);
$password = $data['password'];
$date_of_birth = $data['date_of_birth'];
$sex = $data['sex'];
$contact_number = $data['contact_number'];
$student_id = sanitize($data['student_id']);
$year_level = $data['year_level'];
$college = sanitize($data['college']);
$degree_program = sanitize($data['degree_program']);

// Validate email
if (!isValidEmail($email)) {
    sendError('Invalid email address');
}

// Validate phone
if (!isValidPhone($contact_number)) {
    sendError('Invalid phone number. Must be 11 digits starting with 09');
}

// Validate password strength
if (strlen($password) < 8) {
    sendError('Password must be at least 8 characters');
}
if (!preg_match('/[A-Z]/', $password)) {
    sendError('Password must contain at least one uppercase letter');
}
if (!preg_match('/[0-9]/', $password)) {
    sendError('Password must contain at least one number');
}
if (!preg_match('/[^A-Za-z0-9]/', $password)) {
    sendError('Password must contain at least one special character');
}

// Check if username exists
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    sendError('Username already taken');
}

// Check if email exists
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    sendError('Email already registered');
}

// Check if student ID exists
$stmt = $pdo->prepare("SELECT profile_id FROM student_profiles WHERE student_id = ?");
$stmt->execute([$student_id]);
if ($stmt->fetch()) {
    sendError('Student ID already registered');
}

try {
    // Begin transaction
    $pdo->beginTransaction();

    // Create user
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, user_type, status) VALUES (?, ?, ?, 'student', 'pending')");
    $stmt->execute([$username, $password_hash, $email]);
    $user_id = $pdo->lastInsertId();

    // Create student profile
    $stmt = $pdo->prepare("INSERT INTO student_profiles 
        (user_id, first_name, middle_name, last_name, date_of_birth, sex, contact_number, 
         permanent_address, student_id, year_level, college, degree_program) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $permanent_address = isset($data['permanent_address']) ? sanitize($data['permanent_address']) : '';
    
    $stmt->execute([
        $user_id,
        $first_name,
        $middle_name,
        $last_name,
        $date_of_birth,
        $sex,
        $contact_number,
        $permanent_address,
        $student_id,
        $year_level,
        $college,
        $degree_program
    ]);

    $pdo->commit();

    sendSuccess([
        'user_id' => $user_id,
        'username' => $username,
        'email' => $email
    ], 'Registration successful! Please verify your email.');

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Registration error: " . $e->getMessage());
    sendError('Registration failed. Please try again later.', 500);
}
?>