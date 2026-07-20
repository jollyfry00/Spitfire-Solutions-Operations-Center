<?php

require_once dirname(__FILE__) . '/../includes/auth.php';
require_once dirname(__FILE__) . '/../includes/database.php';

require_login();

$assets = array();
$error_message = '';

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$status_filter = isset($_GET['status'])
    ? trim($_GET['status'])
    : '';

$type_filter = isset($_GET['asset_type'])
    ? trim($_GET['asset_type'])
    : '';

$allowed_statuses = array(
    'Active',
    'In Storage',
    'Repair',
    'Retired'
);

$asset_types = array();

$total_assets = 0;
$active_assets = 0;
$storage_assets = 0;
$repair_assets = 0;
$retired_assets = 0;

try {

    /*
     * Load asset statistics.
     */
    $statistics_statement = $pdo->prepare(
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
                    WHEN status = 'In Storage'
                    THEN 1
                    ELSE 0
                END
            ) AS storage_assets,

            SUM(
                CASE
                    WHEN status = 'Repair'
                    THEN 1
                    ELSE 0
                END
            ) AS repair_assets,

            SUM(
                CASE
                    WHEN status = 'Retired'
                    THEN 1
                    ELSE 0
                END
            ) AS retired_assets

         FROM assets"
    );

    $statistics_statement->execute();

    $statistics = $statistics_statement->fetch();

    if ($statistics) {
        $total_assets = (int) $statistics['total_assets'];
        $active_assets = (int) $statistics['active_assets'];
        $storage_assets = (int) $statistics['storage_assets'];
        $repair_assets = (int) $statistics['repair_assets'];
        $retired_assets = (int) $statistics['retired_assets'];
    }

    /*
     * Load unique asset types for the filter menu.
     */
    $type_statement = $pdo->prepare(
        "SELECT DISTINCT asset_type
         FROM assets
         WHERE asset_type IS NOT NULL
         AND asset_type <> ''
         ORDER BY asset_type ASC"
    );

    $type_statement->execute();

    $type_rows = $type_statement->fetchAll();

    foreach ($type_rows as $type_row) {
        $asset_types[] = $type_row['asset_type'];
    }

    /*
     * Build the filtered asset query.
     */
    $sql =
        "SELECT
            a.id,
            a.asset_tag,
            a.asset_type,
            a.hostname,
            a.manufacturer,
            a.model,
            a.serial_number,
            a.operating_system,
            a.ip_address,
            a.location,
            a.status,
            a.created_at,
            a.updated_at,
            u.username,
            u.first_name,
            u.last_name
         FROM assets a
         LEFT JOIN users u
            ON a.assigned_user_id = u.id
         WHERE 1 = 1";

    $parameters = array();

    if ($search !== '') {

        $sql .=
            " AND (
                a.asset_tag LIKE :search
                OR a.hostname LIKE :search
                OR a.serial_number LIKE :search
                OR a.manufacturer LIKE :search
                OR a.model LIKE :search
                OR a.operating_system LIKE :search
                OR a.ip_address LIKE :search
                OR a.location LIKE :search
                OR u.username LIKE :search
                OR u.first_name LIKE :search
                OR u.last_name LIKE :search
            )";

        $parameters[':search'] = '%' . $search . '%';
    }

    if (
        $status_filter !== '' &&
        in_array($status_filter, $allowed_statuses, true)
    ) {

        $sql .= " AND a.status = :status";

        $parameters[':status'] = $status_filter;
    }

    if ($type_filter !== '') {

        $sql .= " AND a.asset_type = :asset_type";

        $parameters[':asset_type'] = $type_filter;
    }

    $sql .=
        " ORDER BY
            CASE a.status
                WHEN 'Repair' THEN 1
                WHEN 'Active' THEN 2
                WHEN 'In Storage' THEN 3
                WHEN 'Retired' THEN 4
                ELSE 5
            END,
            a.asset_tag ASC";

    $statement = $pdo->prepare($sql);

    $statement->execute($parameters);

    $assets = $statement->fetchAll();

} catch (PDOException $exception) {

    error_log(
        'Asset list error: ' .
        $exception->getMessage()
    );

    $error_message = 'Unable to load assets.';
}

function asset_status_class($status)
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

function assigned_user_name($first_name, $last_name, $username)
{
    $full_name = trim($first_name . ' ' . $last_name);

    if ($full_name !== '') {
        return $full_name;
    }

    if ($username !== null && $username !== '') {
        return $username;
    }

    return 'Unassigned';
}

$page_title = 'Asset Management';

$page_description =
    'Manage servers, workstations, networking equipment, and inventory.';

$page_actions =
    '<a class="button" href="/assets/create.php">+ Add Asset</a>';

include dirname(__FILE__) . '/../includes/layout-header.php';

?>

<?php if (isset($_GET['created'])): ?>

    <div class="success-message">

        Asset

        <strong>
            <?php echo htmlspecialchars($_GET['created']); ?>
        </strong>

        was created successfully.

    </div>

<?php endif; ?>

<?php if (isset($_GET['updated'])): ?>

    <div class="success-message">
        Asset updated successfully.
    </div>

<?php endif; ?>

<div class="asset-statistics">

    <div class="stat-card">

        <h2>
            <?php echo $total_assets; ?>
        </h2>

        <p>Total Assets</p>

    </div>

    <div class="stat-card">

        <h2>
            <?php echo $active_assets; ?>
        </h2>

        <p>Active</p>

    </div>

    <div class="stat-card">

        <h2>
            <?php echo $storage_assets; ?>
        </h2>

        <p>In Storage</p>

    </div>

    <div class="stat-card">

        <h2>
            <?php echo $repair_assets; ?>
        </h2>

        <p>In Repair</p>

    </div>

    <div class="stat-card">

        <h2>
            <?php echo $retired_assets; ?>
        </h2>

        <p>Retired</p>

    </div>

</div>

<div class="asset-filter">

    <form method="get" action="/assets/index.php">

        <label for="search">
            <strong>Search</strong>
        </label>

        <input
            type="text"
            id="search"
            name="search"
            value="<?php echo htmlspecialchars($search); ?>"
            placeholder="Tag, hostname, serial, IP, user"
        >

        <label for="asset_type">
            <strong>Type</strong>
        </label>

        <select id="asset_type" name="asset_type">

            <option value="">
                All Types
            </option>

            <?php foreach ($asset_types as $asset_type): ?>

                <option
                    value="<?php echo htmlspecialchars($asset_type); ?>"
                    <?php
                    if ($type_filter === $asset_type) {
                        echo 'selected';
                    }
                    ?>
                >
                    <?php echo htmlspecialchars($asset_type); ?>
                </option>

            <?php endforeach; ?>

        </select>

        <label for="status">
            <strong>Status</strong>
        </label>

        <select id="status" name="status">

            <option value="">
                All Statuses
            </option>

            <?php foreach ($allowed_statuses as $status_option): ?>

                <option
                    value="<?php echo htmlspecialchars($status_option); ?>"
                    <?php
                    if ($status_filter === $status_option) {
                        echo 'selected';
                    }
                    ?>
                >
                    <?php echo htmlspecialchars($status_option); ?>
                </option>

            <?php endforeach; ?>

        </select>

        <button type="submit">
            Apply Filters
        </button>

        <a
            class="button button-secondary"
            href="/assets/index.php"
        >
            Clear Filters
        </a>

    </form>

</div>

<?php if ($error_message !== ''): ?>

    <div class="error-message">
        <?php echo htmlspecialchars($error_message); ?>
    </div>

<?php else: ?>

    <div class="panel">

        <div class="panel-header">

            <h3>
                Asset Inventory
            </h3>

        </div>

        <div class="panel-body">

            <p>
                Showing
                <strong><?php echo count($assets); ?></strong>
                asset(s).
            </p>

            <div class="table-responsive">

                <table class="asset-table">

                    <thead>

                        <tr>
                            <th>Asset Tag</th>
                            <th>Type</th>
                            <th>Hostname</th>
                            <th>Manufacturer / Model</th>
                            <th>Operating System</th>
                            <th>IP Address</th>
                            <th>Assigned User</th>
                            <th>Location</th>
                            <th>Status</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if (count($assets) > 0): ?>

                        <?php foreach ($assets as $asset): ?>

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

                                    <?php if ($asset['serial_number']): ?>

                                        <br>

                                        <span class="muted-text">

                                            Serial:

                                            <?php
                                            echo htmlspecialchars(
                                                $asset['serial_number']
                                            );
                                            ?>

                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $asset['asset_type']
                                    );
                                    ?>
                                </td>

                                <td>

                                    <?php if ($asset['hostname']): ?>

                                        <?php
                                        echo htmlspecialchars(
                                            $asset['hostname']
                                        );
                                        ?>

                                    <?php else: ?>

                                        <span class="muted-text">
                                            Not assigned
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?php
                                    $manufacturer_model = trim(
                                        $asset['manufacturer'] .
                                        ' ' .
                                        $asset['model']
                                    );
                                    ?>

                                    <?php if ($manufacturer_model !== ''): ?>

                                        <?php
                                        echo htmlspecialchars(
                                            $manufacturer_model
                                        );
                                        ?>

                                    <?php else: ?>

                                        <span class="muted-text">
                                            Not specified
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?php if ($asset['operating_system']): ?>

                                        <?php
                                        echo htmlspecialchars(
                                            $asset['operating_system']
                                        );
                                        ?>

                                    <?php else: ?>

                                        <span class="muted-text">
                                            Not specified
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?php if ($asset['ip_address']): ?>

                                        <?php
                                        echo htmlspecialchars(
                                            $asset['ip_address']
                                        );
                                        ?>

                                    <?php else: ?>

                                        <span class="muted-text">
                                            No IP
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        assigned_user_name(
                                            $asset['first_name'],
                                            $asset['last_name'],
                                            $asset['username']
                                        )
                                    );
                                    ?>

                                </td>

                                <td>

                                    <?php if ($asset['location']): ?>

                                        <?php
                                        echo htmlspecialchars(
                                            $asset['location']
                                        );
                                        ?>

                                    <?php else: ?>

                                        <span class="muted-text">
                                            Not specified
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <span
                                        class="status-label <?php echo asset_status_class($asset['status']); ?>"
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

                            <td colspan="9">
                                No assets matched the selected filters.
                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

<?php endif; ?>

<?php

include dirname(__FILE__) . '/../includes/layout-footer.php';

?>
