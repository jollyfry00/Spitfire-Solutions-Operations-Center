<?php

require_once dirname(__FILE__) . '/includes/auth.php';

/*
 * If logged in → go to dashboard
 */
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard/index.php');
    exit;
}

/*
 * Otherwise → go to login
 */
header('Location: /login.php');
exit;
