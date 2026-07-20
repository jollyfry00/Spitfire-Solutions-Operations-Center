<?php

if (!isset($page_title)) {
    $page_title = 'Dashboard';
}

$current_user = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';

?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="/css/application.css">
</head>
<body>

<div class="app-container">

<!-- TOP BAR -->
<div class="topbar">
    <span>Spitfire Operations Center</span>
    <span style="float:right;">
        <?php echo htmlspecialchars($current_user); ?> |
        <a href="/logout.php">Logout</a>
    </span>
</div>

<!-- SIDEBAR -->
<div class="sidebar">

    <h3>Navigation</h3>

    <ul>

        <li><a href="/dashboard/">Dashboard</a></li>

        <li><a href="/tickets/">Tickets</a></li>

        <li><a href="/assets/">Assets</a></li>

        <li><a href="/monitoring/">Monitoring</a></li>

        <li><a href="/users/">Users</a></li>

    </ul>

</div>

<!-- MAIN CONTENT -->
<div class="main-content">
