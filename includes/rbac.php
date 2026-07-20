<?php

function current_user_role()
{
    return isset($_SESSION['role_id'])
        ? (int) $_SESSION['role_id']
        : 0;
}

function is_admin()
{
    return current_user_role() === 1;
}

function is_technician()
{
    return current_user_role() === 2;
}

function require_admin()
{
    if (!is_admin()) {
        header('Location: /dashboard/index.php');
        exit;
    }
}
