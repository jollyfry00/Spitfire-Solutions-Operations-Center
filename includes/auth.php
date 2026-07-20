<?php

session_start();

/*
 * Require login
 */
function require_login()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

/*
 * Get role
 */
function current_user_role()
{
    return isset($_SESSION['role_id'])
        ? (int) $_SESSION['role_id']
        : 0;
}

/*
 * Admin check
 */
function require_admin()
{
    if (current_user_role() !== 1) {
        header('Location: /dashboard/index.php');
        exit;
    }
}
