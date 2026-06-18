<?php
require_once __DIR__ . '/../config/helpers.php';

startSecureSession();
session_destroy();

sendSuccess(null, 'Logged out successfully');
?>