<?php

require_once dirname(__FILE__) . '/../includes/auth.php';
require_once dirname(__FILE__) . '/../includes/database.php';

require_login();

$error_message = '';
$status_error = '';

$total_tickets = 0;
$open_tickets = 0;
$critical_tickets = 0;
$total_assets = 0;
$active_assets = 0;
$repair_assets = 0;
$active_users = 0;

$recent_tickets = array();
$recent_assets = array();

$server_status = array();

/*
 * Load dashboard database statistics.
 */
try {

    $ticket_statistics_statement = $pdo->prepare(
        "SELECT
            COUNT(*) AS total_tickets,

            SUM(
                CASE
                    WHEN status NOT IN ('Resolved', 'Closed')
                    THEN 1
                    ELSE 0
                END
            ) AS open_tickets,

            SUM(
                CASE
                    WHEN priority = 'Critical'
                    AND status NOT IN ('Resolved', 'Closed')
                    THEN 1
                    ELSE 0
                END
            ) AS critical_tickets

         FROM tickets"
    );

    $ticket_statistics_statement->execute();

    $ticket_statistics = $ticket_statistics_statement->fetch();

    if ($ticket_statistics) {
        $total_tickets = (int) $ticket_statistics['total_tickets'];
        $open_tickets = (int) $ticket_statistics['open_tickets'];
        $critical_tickets = (int) $ticket_statistics['critical_tickets'];
    }

    $asset_statistics_statement = $pdo->prepare(
        "SELECT
            COUNT(*) AS total_assets,

            SUM(
                CASE
                    WHEN status = 'Active'
                    THEN 1
                    ELSE 0
                END
            ) AS active_assets,

            SUM(
                CASE
                    WHEN status = 'Repair'
                    THEN 1
                    ELSE 0
                END
            ) AS repair_assets

         FROM assets"
    );

    $asset_statistics_statement->execute();

    $asset_statistics = $asset_statistics_statement->fetch();

    if ($asset_statistics) {
        $total_assets = (int) $asset_statistics['total_assets'];
        $active_assets = (int) $asset_statistics['active_assets'];
        $repair_assets = (int) $asset_statistics['repair_assets'];
    }

    $user_statistics_statement = $pdo->prepare(
        "SELECT COUNT(*) AS active_users
         FROM users
         WHERE is_active = 1"
    );

    $user_statistics_statement->execute();

    $user_statistics = $user_statistics_statement->fetch();

    if ($user_statistics) {
        $active_users = (int) $user_statistics['active_users'];
    }

    /*
     * Load recent tickets.
     */
    $recent_ticket_statement = $pdo->prepare(
        "SELECT
            id,
            ticket_number,
            title,
            status,
            priority,
            created_at
         FROM tickets
         ORDER BY created_at DESC
         LIMIT 5"
    );

    $recent_ticket_statement->execute();

    $recent_tickets = $recent_ticket_statement->fetchAll();

    /*
     * Load recent assets.
     */
    $recent_asset_statement = $pdo->prepare(
        "SELECT
            id,
            asset_tag,
            asset_type,
            hostname,
            status,
            created_at
         FROM assets
         ORDER BY created_at DESC
         LIMIT 5"
    );

    $recent_asset_statement->execute();

    $recent_assets = $recent_asset_statement->fetchAll();

} catch (PDOException $exception) {

    error_log(
        'Dashboard database error: ' .
        $exception->getMessage()
    );

    $error_message = 'Some dashboard information could not be loaded.';
}

/*
 * Load the server-status API.
 */
$status_url = 'http://127.0.0.1/status-api.php';
$status_json = false;

if (function_exists('curl_init')) {

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $status_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $status_json = curl_exec($curl);

    if ($status_json === false) {
        $status_error = curl_error($curl);
    }

    curl_close($curl);

} else {

    $status_json = @file_get_contents($status_url);

    if ($status_json === false) {
        $status_error = 'Unable to contact the local status API.';
    }
}

if ($status_json !== false && trim($status_json) !== '') {

    $decoded_status = json_decode($status_json, true);

    if (is_array($decoded_status)) {
        $server_status = $decoded_status;
    } else {
        $status_error = 'The status API returned invalid JSON.';
    }
}

function dashboard_status_class($status)
{
    switch ($status) {

        case 'New':
            return 'status-new';

        case 'Assigned':
            return 'status-assigned';

        case 'In Progress':
            return 'status-progress';

        case 'Waiting':
            return 'status-waiting';

        case 'Resolved':
            return 'status-resolved';

        case 'Closed':
            return 'status-closed';

        default:
            return '';
    }
}

function dashboard_priority_class($priority)
{
    switch ($priority) {

        case 'Critical':
            return 'priority-critical';

        case 'High':
            return 'priority-high';

        case 'Medium':
            return 'priority-medium';

        case 'Low':
            return 'priority-low';

        default:
            return '';
    }
}

function dashboard_asset_status_class($status)
{
    switch ($status) {

        case 'Active':
            return 'status-active';

        case 'In Storage':
            return 'status-storage';

        case 'Repair':
            return 'status-repair';

        case 'Retired':
            return 'status-retired';

        default:
            return '';
    }
}

function status_value($array, $key, $default_value)
{
    if (isset($array[$key]) && $array[$key] !== '') {
        return $array[$key];
    }

    return $default_value;
}

function status_nested_value($array, $section, $key, $default_value)
{
    if (
        isset($array[$section]) &&
        is_array($array[$section]) &&
        isset($array[$section][$key]) &&
        $array[$section][$key] !== ''
    ) {
        return $array[$section][$key];
    }

    return $default_value;
}

function percentage_width($value)
{
    $number = (float) $value;

    if ($number < 0) {
        $number = 0;
    }

    if ($number > 100) {
        $number = 100;
    }

    return $number;
}

$page_title = 'Operations Dashboard';

$page_description =
    'Live infrastructure health, service activity, tickets, assets, and system security.';

$page_actions =
    '<a class="button" href="/tickets/create.php">+ Create Ticket</a>';

include dirname(__FILE__) . '/../includes/layout-header.php';

?>

<style>

    .dashboard-grid {
        display: table;
        width: calc(100% + 20px);
        margin: 0 -10px 20px -10px;
        table-layout: fixed;
        border-spacing: 10px;
    }

    .dashboard-column {
        display: table-cell;
        width: 50%;
        vertical-align: top;
    }

    .metric-bar {
        width: 100%;
        height: 12px;
        margin-top: 8px;
        overflow: hidden;
        background: #e4e9ee;
        border-radius: 6px;
    }

    .metric-bar-fill {
        height: 12px;
        background: #1769aa;
        border-radius: 6px;
    }

    .health-grid {
        display: table;
        width: 100%;
        table-layout: fixed;
        border-spacing: 10px;
    }

    .health-item {
        display: table-cell;
        padding: 14px;
        background: #f7f9fb;
        border: 1px solid #d5dde6;
        border-radius: 5px;
        vertical-align: top;
    }

    .health-item strong {
        display: block;
        margin-bottom: 5px;
        color: #637487;
        font-size: 12px;
        text-transform: uppercase;
    }

    .health-value {
        color: #1f2d3d;
        font-size: 18px;
        font-weight: bold;
    }

    .health-online {
        color: #27743d;
    }

    .health-warning {
        color: #a25c12;
    }

    .health-danger {
        color: #b52b27;
    }

    .service-list {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .service-list li {
        padding: 9px 0;
        border-bottom: 1px solid #e1e6eb;
    }

    .service-list li:last-child {
        border-bottom: 0;
    }

    .service-state {
        float: right;
        font-weight: bold;
    }

    @media screen and (max-width: 1000px) {

        .dashboard-grid,
        .dashboard-column,
        .health-grid,
        .health-item {
            display: block;
            width: 100%;
            margin: 0;
        }

        .dashboard-column,
        .health-item {
            margin-bottom: 12px;
        }

    }

</style>

<?php if ($error_message !== ''): ?>

    <div class="warning-message">
        <?php echo htmlspecialchars($error_message); ?>
    </div>

<?php endif; ?>

<div class="stat-grid">

    <div class="stat-card">

        <div class="stat-number">
            <?php echo $open_tickets; ?>
        </div>

        <div class="stat-label">
            Open Tickets
        </div>

    </div>

    <div class="stat-card">

        <div class="stat-number">
            <?php echo $critical_tickets; ?>
        </div>

        <div class="stat-label">
            Open Critical
        </div>

    </div>

    <div class="stat-card">

        <div class="stat-number">
            <?php echo $total_assets; ?>
        </div>

        <div class="stat-label">
            Managed Assets
        </div>

    </div>

    <div class="stat-card">

        <div class="stat-number">
            <?php echo $repair_assets; ?>
        </div>

        <div class="stat-label">
            Assets in Repair
        </div>

    </div>

    <div class="stat-card">

        <div class="stat-number">
            <?php echo $active_users; ?>
        </div>

        <div class="stat-label">
            Active Users
        </div>

    </div>

</div>

<div class="panel">

    <div class="panel-header">
        <h3>Server Health</h3>
    </div>

    <div class="panel-body">

        <?php if (count($server_status) === 0): ?>

            <div class="warning-message">

                Server status information is unavailable.

                <?php if ($status_error !== ''): ?>

                    <br>

                    <?php echo htmlspecialchars($status_error); ?>

                <?php endif; ?>

            </div>

        <?php else: ?>

            <?php

            $hostname = status_nested_value(
                $server_status,
                'server',
                'hostname',
                status_value($server_status, 'hostname', 'Unknown')
            );

            $server_ip = status_nested_value(
                $server_status,
                'server',
                'ip',
                status_value($server_status, 'ip', 'Unknown')
            );

            $operating_system = status_nested_value(
                $server_status,
                'server',
                'operating_system',
                status_value($server_status, 'operating_system', 'Unknown')
            );

            $kernel = status_nested_value(
                $server_status,
                'server',
                'kernel',
                status_value($server_status, 'kernel', 'Unknown')
            );

            $uptime = status_nested_value(
                $server_status,
                'server',
                'uptime',
                status_value($server_status, 'uptime', 'Unknown')
            );

            ?>

            <div class="health-grid">

                <div class="health-item">
                    <strong>Server Status</strong>
                    <div class="health-value health-online">
                        Online
                    </div>
                </div>

                <div class="health-item">
                    <strong>Hostname</strong>
                    <div class="health-value">
                        <?php echo htmlspecialchars($hostname); ?>
                    </div>
                </div>

                <div class="health-item">
                    <strong>IP Address</strong>
                    <div class="health-value">
                        <?php echo htmlspecialchars($server_ip); ?>
                    </div>
                </div>

                <div class="health-item">
                    <strong>Uptime</strong>
                    <div class="health-value">
                        <?php echo htmlspecialchars($uptime); ?>
                    </div>
                </div>

            </div>

            <div class="health-grid">

                <div class="health-item">
                    <strong>Operating System</strong>
                    <div>
                        <?php echo htmlspecialchars($operating_system); ?>
                    </div>
                </div>

                <div class="health-item">
                    <strong>Kernel</strong>
                    <div>
                        <?php echo htmlspecialchars($kernel); ?>
                    </div>
                </div>

            </div>

        <?php endif; ?>

    </div>

</div>

<?php if (count($server_status) > 0): ?>

    <?php

    $cpu_load = status_nested_value(
        $server_status,
        'cpu',
        'load_1',
        0
    );

    $memory_percent = status_nested_value(
        $server_status,
        'memory',
        'percent_used',
        0
    );

    $disk_percent = status_nested_value(
        $server_status,
        'disk',
        'percent_used',
        0
    );

    $memory_used = status_nested_value(
        $server_status,
        'memory',
        'used_mb',
        'Unknown'
    );

    $memory_total = status_nested_value(
        $server_status,
        'memory',
        'total_mb',
        'Unknown'
    );

    $disk_used = status_nested_value(
        $server_status,
        'disk',
        'used_gb',
        'Unknown'
    );

    $disk_total = status_nested_value(
        $server_status,
        'disk',
        'total_gb',
        'Unknown'
    );

    ?>

    <div class="dashboard-grid">

        <div class="dashboard-column">

            <div class="panel">

                <div class="panel-header">
                    <h3>Resource Utilization</h3>
                </div>

                <div class="panel-body">

                    <p>
                        <strong>CPU Load</strong>
                        <span class="text-muted">
                            — 1-minute average
                        </span>
                    </p>

                    <div class="health-value">
                        <?php echo htmlspecialchars($cpu_load); ?>
                    </div>

                    <hr>

                    <p>
                        <strong>Memory Usage</strong>
                    </p>

                    <div>
                        <?php echo htmlspecialchars($memory_used); ?> MB used
                        of
                        <?php echo htmlspecialchars($memory_total); ?> MB
                    </div>

                    <div class="metric-bar">

                        <div
                            class="metric-bar-fill"
                            style="width: <?php echo percentage_width($memory_percent); ?>%;"
                        ></div>

                    </div>

                    <p class="text-muted">
                        <?php echo htmlspecialchars($memory_percent); ?>% used
                    </p>

                    <hr>

                    <p>
                        <strong>Disk Usage</strong>
                    </p>

                    <div>
                        <?php echo htmlspecialchars($disk_used); ?> GB used
                        of
                        <?php echo htmlspecialchars($disk_total); ?> GB
                    </div>

                    <div class="metric-bar">

                        <div
                            class="metric-bar-fill"
                            style="width: <?php echo percentage_width($disk_percent); ?>%;"
                        ></div>

                    </div>

                    <p class="text-muted">
                        <?php echo htmlspecialchars($disk_percent); ?>% used
                    </p>

                </div>

            </div>

        </div>

        <div class="dashboard-column">

            <div class="panel">

                <div class="panel-header">
                    <h3>Service Status</h3>
                </div>

                <div class="panel-body">

                    <?php

                    $services = isset($server_status['services']) &&
                        is_array($server_status['services'])
                        ? $server_status['services']
                        : array();

                    ?>

                    <?php if (count($services) > 0): ?>

                        <ul class="service-list">

                            <?php foreach ($services as $service_name => $service_state): ?>

                                <li>

                                    <?php
                                    echo htmlspecialchars(
                                        strtoupper($service_name)
                                    );
                                    ?>

                                    <span
                                        class="service-state <?php
                                        echo $service_state === 'active'
                                            ? 'health-online'
                                            : 'health-danger';
                                        ?>"
                                    >
                                        <?php
                                        echo htmlspecialchars(
                                            ucfirst($service_state)
                                        );
                                        ?>
                                    </span>

                                </li>

                            <?php endforeach; ?>

                        </ul>

                    <?php else: ?>

                        <p class="text-muted">
                            No service information was returned.
                        </p>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

<?php endif; ?>

<div class="dashboard-grid">

    <div class="dashboard-column">

        <div class="panel">

            <div class="panel-header">
                <h3>Recent Tickets</h3>
            </div>

            <div class="panel-body">

                <div class="table-responsive">

                    <table>

                        <thead>

                            <tr>
                                <th>Ticket</th>
                                <th>Status</th>
                                <th>Priority</th>
                            </tr>

                        </thead>

                        <tbody>

                        <?php if (count($recent_tickets) > 0): ?>

                            <?php foreach ($recent_tickets as $ticket): ?>

                                <tr>

                                    <td>

                                        <a
                                            href="/tickets/view.php?id=<?php echo (int) $ticket['id']; ?>"
                                        >
                                            <?php
                                            echo htmlspecialchars(
                                                $ticket['ticket_number']
                                            );
                                            ?>
                                        </a>

                                        <br>

                                        <span class="text-muted">
                                            <?php
                                            echo htmlspecialchars(
                                                $ticket['title']
                                            );
                                            ?>
                                        </span>

                                    </td>

                                    <td>

                                        <span
                                            class="status-label <?php echo dashboard_status_class($ticket['status']); ?>"
                                        >
                                            <?php
                                            echo htmlspecialchars(
                                                $ticket['status']
                                            );
                                            ?>
                                        </span>

                                    </td>

                                    <td>

                                        <span
                                            class="priority-label <?php echo dashboard_priority_class($ticket['priority']); ?>"
                                        >
                                            <?php
                                            echo htmlspecialchars(
                                                $ticket['priority']
                                            );
                                            ?>
                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="3">
                                    No tickets have been created.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

            <div class="panel-footer">

                <a href="/tickets/index.php">
                    View all tickets
                </a>

            </div>

        </div>

    </div>

    <div class="dashboard-column">

        <div class="panel">

            <div class="panel-header">
                <h3>Recent Assets</h3>
            </div>

            <div class="panel-body">

                <div class="table-responsive">

                    <table>

                        <thead>

                            <tr>
                                <th>Asset</th>
                                <th>Type</th>
                                <th>Status</th>
                            </tr>

                        </thead>

                        <tbody>

                        <?php if (count($recent_assets) > 0): ?>

                            <?php foreach ($recent_assets as $asset): ?>

                                <tr>

                                    <td>

                                        <a
                                            href="/assets/view.php?id=<?php echo (int) $asset['id']; ?>"
                                        >
                                            <?php
                                            echo htmlspecialchars(
                                                $asset['asset_tag']
                                            );
                                            ?>
                                        </a>

                                        <br>

                                        <span class="text-muted">

                                            <?php if ($asset['hostname']): ?>

                                                <?php
                                                echo htmlspecialchars(
                                                    $asset['hostname']
                                                );
                                                ?>

                                            <?php else: ?>

                                                No hostname

                                            <?php endif; ?>

                                        </span>

                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $asset['asset_type']
                                        );
                                        ?>
                                    </td>

                                    <td>

                                        <span
                                            class="status-label <?php echo dashboard_asset_status_class($asset['status']); ?>"
                                        >
                                            <?php
                                            echo htmlspecialchars(
                                                $asset['status']
                                            );
                                            ?>
                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="3">
                                    No assets have been added.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

            <div class="panel-footer">

                <a href="/assets/index.php">
                    View all assets
                </a>

            </div>

        </div>

    </div>

</div>

<?php

include dirname(__FILE__) . '/../includes/layout-footer.php';

?>
