<?php
/**
 * Eyok Store - Authentication Middleware
 * Include at the top of every protected page
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . (str_contains($_SERVER['PHP_SELF'], '/modules/') ? '../../auth/login.php' : '../auth/login.php'));
    exit;
}

/**
 * Check if current user is admin
 * @return bool
 */
function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if current user is operator
 * @return bool
 */
function isOperator(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'operator';
}

/**
 * Require admin access, redirect if not admin
 */
function requireAdmin(): void {
    if (!isAdmin()) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Access denied. Admin privileges required.'];
        header('Location: ' . (str_contains($_SERVER['PHP_SELF'], '/modules/') ? '../dashboard/index.php' : 'modules/dashboard/index.php'));
        exit;
    }
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * @param string $token
 * @return bool
 */
function validateCSRF(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
