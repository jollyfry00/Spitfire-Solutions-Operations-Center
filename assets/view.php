<?php

require_once dirname(__FILE__) . '/../includes/auth.php';
require_once dirname(__FILE__) . '/../includes/database.php';

require_login();

$error_message = '';
$asset = null;

$asset_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($asset_id <= 0) {
    $error_message = 'Invalid asset ID.';
}

if ($error_message === '') {

    try {

        $statement = $pdo->prepare(
            "SELECT
                a.*,
                u.username,
                u.first_name,
                u.last_name,
                u.department
             FROM assets a
             LEFT JOIN users u
                ON a.assigned_user_id = u.id
             WHERE a.id = :id
             LIMIT 1"
        );

        $statement->execute(array(
            ':id' => $asset_id
        ));

        $asset = $statement->fetch();

        if (!$asset) {
            $error_message = 'Asset not found.';
        }

    } catch (PDOException $exception) {

        error_log('Asset view error: ' . $exception->getMessage());
        $error_message = 'Unable to load asset.';
    }
}

function user_display($asset)
{
    $name = trim($asset['first_name'] . ' ' . $asset['last_name']);

    if ($name !== '') {
        return $name;
    }

    if (!empty($asset['username'])) {
        return $asset['username'];
    }

    return 'Unassigned';
}

function status_class($status)
{
    switch ($status) {
        case 'Active': return 'status-active';
        case 'In Storage': return 'status-storage';
        case 'Repair': return 'status-repair';
        case 'Retired': return 'status-retired';
        default: return '';
    }
}

$page_title = 'Asset Details';

$page_description = 'View detailed information about this asset.';

$page_actions =
    '<a class="button" href="/assets/edit.php?id=' . $asset_id . '">Edit</a> ' .
    '<a class="button button-secondary" href="/assets/index.php">Back</a>';

include dirname(__FILE__) . '/../includes/layout-header.php';

?>

<?php if ($error_message !== ''): ?>

    <div class="error-message">
        <?php echo htmlspecialchars($error_message); ?>
    </div>

<?php else: ?>

<div class="panel">

    <div class="panel-header">
        <h3><?php echo htmlspecialchars($asset['asset_tag']); ?></h3>
    </div>

    <div class="panel-body">

        <table>

            <tr>
                <th>Asset Tag</th>
                <td><?php echo htmlspecialchars($asset['asset_tag']); ?></td>
            </tr>

            <tr>
                <th>Type</th>
                <td><?php echo htmlspecialchars($asset['asset_type']); ?></td>
            </tr>

            <tr>
                <th>Status</th>
                <td>
                    <span class="status-label <?php echo status_class($asset['status']); ?>">
                        <?php echo htmlspecialchars($asset['status']); ?>
                    </span>
                </td>
            </tr>

            <tr>
                <th>Hostname</th>
                <td><?php echo htmlspecialchars($asset['hostname']); ?></td>
            </tr>

            <tr>
                <th>IP Address</th>
                <td><?php echo htmlspecialchars($asset['ip_address']); ?></td>
            </tr>

            <tr>
                <th>Operating System</th>
                <td><?php echo htmlspecialchars($asset['operating_system']); ?></td>
            </tr>

            <tr>
                <th>Manufacturer</th>
                <td><?php echo htmlspecialchars($asset['manufacturer']); ?></td>
            </tr>

            <tr>
                <th>Model</th>
                <td><?php echo htmlspecialchars($asset['model']); ?></td>
            </tr>

            <tr>
                <th>Serial Number</th>
                <td><?php echo htmlspecialchars($asset['serial_number']); ?></td>
            </tr>

            <tr>
                <th>Location</th>
                <td><?php echo htmlspecialchars($asset['location']); ?></td>
            </tr>

            <tr>
                <th>Assigned User</th>
                <td><?php echo htmlspecialchars(user_display($asset)); ?></td>
            </tr>

            <tr>
                <th>Department</th>
                <td><?php echo htmlspecialchars($asset['department']); ?></td>
            </tr>

            <tr>
                <th>Created</th>
                <td><?php echo htmlspecialchars($asset['created_at']); ?></td>
            </tr>

            <tr>
                <th>Last Updated</th>
                <td>
                    <?php
                    echo $asset['updated_at']
                        ? htmlspecialchars($asset['updated_at'])
                        : 'Never';
                    ?>
                </td>
            </tr>

        </table>

    </div>

</div>

<?php endif; ?>

<?php include dirname(__FILE__) . '/../includes/layout-footer.php'; ?>
