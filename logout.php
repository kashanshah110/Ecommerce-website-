<?php
/**
 * Naeem Electronic - Logout
 * Handle user logout
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Destroy session
session_unset();
session_destroy();

// Redirect to home
redirect('index.php');
