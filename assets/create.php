<?php

require_once dirname(__FILE__) . '/../includes/auth.php';
require_once dirname(__FILE__) . '/../includes/database.php';

require_login();

$error_message = '';
$validation_errors = array();
$users = array();

$asset_tag = '';
$asset_type = '';
$hostname = '';
$manufacturer = '';
$model = '';
$serial_number = '';
$operating_system = '';
$ip_address = '';
$assigned_user_id = '';
$location = '';
$status = 'Active';

$allowed_statuses = array(
    'Active',
    'In Storage',
    'Repair',
    'Retired'
);

$common_asset_types = array(
    'Desktop',
    'Laptop',
    'Server',
    'Virtual Machine',
    'Switch',
    'Router',
    'Firewall',
    'Wireless Access Point',
    'Printer',
    'Mobile Device',
    'Storage Device',
    'Other'
);

/*
 * Load active users for the assignment dropdown.
 */
try {

    $user_statement = $pdo->prepare(
        "SELECT
            id,
            username,
            first_name,
            last_name,
            department
         FROM users
         WHERE is_active = 1
         ORDER BY last_name ASC, first_name ASC, username ASC"
    );

    $user_statement->execute();

    $users = $user_statement->fetchAll();

} catch (PDOException $exception) {

    error_log(
        'Asset create user query error: ' .
        $exception->getMessage()
    );

    $error_message = 'Unable to load the user assignment list.';
}

/*
 * Process the asset form.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $asset_tag = isset($_POST['asset_tag'])
        ? trim($_POST['asset_tag'])
        : '';

    $asset_type = isset($_POST['asset_type'])
        ? trim($_POST['asset_type'])
        : '';

    $hostname = isset($_POST['hostname'])
        ? trim($_POST['hostname'])
        : '';

    $manufacturer = isset($_POST['manufacturer'])
        ? trim($_POST['manufacturer'])
        : '';

    $model = isset($_POST['model'])
        ? trim($_POST['model'])
        : '';

    $serial_number = isset($_POST['serial_number'])
        ? trim($_POST['serial_number'])
        : '';

    $operating_system = isset($_POST['operating_system'])
        ? trim($_POST['operating_system'])
        : '';

    $ip_address = isset($_POST['ip_address'])
        ? trim($_POST['ip_address'])
        : '';

    $assigned_user_id = isset($_POST['assigned_user_id'])
        ? trim($_POST['assigned_user_id'])
        : '';

    $location = isset($_POST['location'])
        ? trim($_POST['location'])
        : '';

    $status = isset($_POST['status'])
        ? trim($_POST['status'])
        : 'Active';

    /*
     * Required-field validation.
     */
    if ($asset_tag === '') {
        $validation_errors[] = 'Asset tag is required.';
    }

    if ($asset_type === '') {
        $validation_errors[] = 'Asset type is required.';
    }

    if (!in_array($status, $allowed_statuses, true)) {
        $validation_errors[] = 'A valid asset status is required.';
    }

    /*
     * Field-length validation.
     */
    if (strlen($asset_tag) > 50) {
        $validation_errors[] =
            'Asset tag cannot be longer than 50 characters.';
    }

    if (strlen($asset_type) > 75) {
        $validation_errors[] =
            'Asset type cannot be longer than 75 characters.';
    }

    if (strlen($hostname) > 100) {
        $validation_errors[] =
            'Hostname cannot be longer than 100 characters.';
    }

    if (strlen($manufacturer) > 100) {
        $validation_errors[] =
            'Manufacturer cannot be longer than 100 characters.';
    }

    if (strlen($model) > 100) {
        $validation_errors[] =
            'Model cannot be longer than 100 characters.';
    }

    if (strlen($serial_number) > 100) {
        $validation_errors[] =
            'Serial number cannot be longer than 100 characters.';
    }

    if (strlen($operating_system) > 150) {
        $validation_errors[] =
            'Operating system cannot be longer than 150 characters.';
    }

    if (strlen($ip_address) > 45) {
        $validation_errors[] =
            'IP address cannot be longer than 45 characters.';
    }

    if (strlen($location) > 150) {
        $validation_errors[] =
            'Location cannot be longer than 150 characters.';
    }

    /*
     * IP-address validation.
     */
    if (
        $ip_address !== '' &&
        filter_var($ip_address, FILTER_VALIDATE_IP) === false
    ) {
        $validation_errors[] =
            'Enter a valid IPv4 or IPv6 address.';
    }

    /*
     * Assignment validation.
     */
    $database_assigned_user_id = null;

    if ($assigned_user_id !== '') {

        $assigned_user_integer = (int) $assigned_user_id;

        if ($assigned_user_integer <= 0) {

            $validation_errors[] =
                'The selected assigned user is invalid.';

        } else {

            try {

                $assigned_user_statement = $pdo->prepare(
                    "SELECT id
                     FROM users
                     WHERE id = :id
                     AND is_active = 1
                     LIMIT 1"
                );

                $assigned_user_statement->execute(array(
                    ':id' => $assigned_user_integer
                ));

                if (!$assigned_user_statement->fetch()) {

                    $validation_errors[] =
                        'The selected assigned user does not exist or is inactive.';

                } else {

                    $database_assigned_user_id =
                        $assigned_user_integer;
                }

            } catch (PDOException $exception) {

                error_log(
                    'Asset assigned-user validation error: ' .
                    $exception->getMessage()
                );

                $validation_errors[] =
                    'Unable to validate the assigned user.';
            }
        }
    }

    /*
     * Check for duplicate asset tag.
     */
    if ($asset_tag !== '') {

        try {

            $duplicate_statement = $pdo->prepare(
                "SELECT id
                 FROM assets
                 WHERE asset_tag = :asset_tag
                 LIMIT 1"
            );

            $duplicate_statement->execute(array(
                ':asset_tag' => $asset_tag
            ));

            if ($duplicate_statement->fetch()) {
                $validation_errors[] =
                    'That asset tag is already being used.';
            }

        } catch (PDOException $exception) {

            error_log(
                'Asset duplicate check error: ' .
                $exception->getMessage()
            );

            $validation_errors[] =
                'Unable to validate the asset tag.';
        }
    }

    /*
     * Check for duplicate serial number when one is supplied.
     */
    if ($serial_number !== '') {

        try {

            $serial_statement = $pdo->prepare(
                "SELECT id
                 FROM assets
                 WHERE serial_number = :serial_number
                 LIMIT 1"
            );

            $serial_statement->execute(array(
                ':serial_number' => $serial_number
            ));

            if ($serial_statement->fetch()) {
                $validation_errors[] =
                    'That serial number is already assigned to another asset.';
            }

        } catch (PDOException $exception) {

            error_log(
                'Asset serial-number check error: ' .
                $exception->getMessage()
            );

            $validation_errors[] =
                'Unable to validate the serial number.';
        }
    }

    /*
     * Insert the asset after validation succeeds.
     */
    if (count($validation_errors) === 0) {

        try {

            $insert_statement = $pdo->prepare(
                "INSERT INTO assets (
                    asset_tag,
                    asset_type,
                    hostname,
                    manufacturer,
                    model,
                    serial_number,
                    operating_system,
                    ip_address,
                    assigned_user_id,
                    location,
                    status,
                    created_at,
                    updated_at
                ) VALUES (
                    :asset_tag,
                    :asset_type,
                    :hostname,
                    :manufacturer,
                    :model,
                    :serial_number,
                    :operating_system,
                    :ip_address,
                    :assigned_user_id,
                    :location,
                    :status,
                    NOW(),
                    NULL
                )"
            );

            if ($database_assigned_user_id === null) {

                $insert_statement->bindValue(
                    ':assigned_user_id',
                    null,
                    PDO::PARAM_NULL
                );

            } else {

                $insert_statement->bindValue(
                    ':assigned_user_id',
                    $database_assigned_user_id,
                    PDO::PARAM_INT
                );
            }

            $insert_statement->bindValue(
                ':asset_tag',
                $asset_tag,
                PDO::PARAM_STR
            );

            $insert_statement->bindValue(
                ':asset_type',
                $asset_type,
                PDO::PARAM_STR
            );

            $insert_statement->bindValue(
                ':hostname',
                $hostname !== '' ? $hostname : null,
                $hostname !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );

            $insert_statement->bindValue(
                ':manufacturer',
                $manufacturer !== '' ? $manufacturer : null,
                $manufacturer !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );

            $insert_statement->bindValue(
                ':model',
                $model !== '' ? $model : null,
                $model !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );

            $insert_statement->bindValue(
                ':serial_number',
                $serial_number !== '' ? $serial_number : null,
                $serial_number !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );

            $insert_statement->bindValue(
                ':operating_system',
                $operating_system !== ''
                    ? $operating_system
                    : null,
                $operating_system !== ''
                    ? PDO::PARAM_STR
                    : PDO::PARAM_NULL
            );

            $insert_statement->bindValue(
                ':ip_address',
                $ip_address !== '' ? $ip_address : null,
                $ip_address !== ''
                    ? PDO::PARAM_STR
                    : PDO::PARAM_NULL
            );

            $insert_statement->bindValue(
                ':location',
                $location !== '' ? $location : null,
                $location !== ''
                    ? PDO::PARAM_STR
                    : PDO::PARAM_NULL
            );

            $insert_statement->bindValue(
                ':status',
                $status,
                PDO::PARAM_STR
            );

            $insert_statement->execute();

            header(
                'Location: /assets/index.php?created=' .
                urlencode($asset_tag)
            );

            exit;

        } catch (PDOException $exception) {

            error_log(
                'Asset creation error: ' .
                $exception->getMessage()
            );

            if (
                isset($exception->errorInfo[1]) &&
                (int) $exception->errorInfo[1] === 1062
            ) {

                $validation_errors[] =
                    'The asset tag or another unique value already exists.';

            } else {

                $error_message =
                    'The asset could not be created.';
            }
        }
    }
}

function create_asset_user_name(
    $first_name,
    $last_name,
    $username,
    $department
) {
    $full_name = trim($first_name . ' ' . $last_name);

    if ($full_name === '') {
        $full_name = $username;
    }

    $display_name = $full_name . ' (' . $username . ')';

    if ($department !== null && trim($department) !== '') {
        $display_name .= ' — ' . $department;
    }

    return $display_name;
}

$page_title = 'Add New Asset';

$page_description =
    'Register a server, workstation, network device, or other managed asset.';

$page_actions =
    '<a class="button button-secondary" href="/assets/index.php">' .
    'Back to Assets</a>';

include dirname(__FILE__) . '/../includes/layout-header.php';

?>

<?php if ($error_message !== ''): ?>

    <div class="error-message">
        <?php echo htmlspecialchars($error_message); ?>
    </div>

<?php endif; ?>

<?php if (count($validation_errors) > 0): ?>

    <div class="error-message">

        <strong>
            The asset could not be created:
        </strong>

        <ul>

            <?php foreach ($validation_errors as $validation_error): ?>

                <li>
                    <?php echo htmlspecialchars($validation_error); ?>
                </li>

            <?php endforeach; ?>

        </ul>

    </div>

<?php endif; ?>

<div class="panel">

    <div class="panel-header">
        <h3>Asset Information</h3>
    </div>

    <div class="panel-body">

        <form method="post" action="/assets/create.php">

            <div class="form-row">

                <div class="form-column">

                    <div class="form-group">

                        <label for="asset_tag">
                            Asset Tag *
                        </label>

                        <input
                            type="text"
                            id="asset_tag"
                            name="asset_tag"
                            maxlength="50"
                            value="<?php echo htmlspecialchars($asset_tag); ?>"
                            placeholder="SPT-LAP-0001"
                            required
                        >

                        <span class="help-text">
                            Enter a unique inventory identifier.
                        </span>

                    </div>

                </div>

                <div class="form-column">

                    <div class="form-group">

                        <label for="asset_type">
                            Asset Type *
                        </label>

                        <select
                            id="asset_type"
                            name="asset_type"
                            required
                        >

                            <option value="">
                                Select an asset type
                            </option>

                            <?php foreach ($common_asset_types as $type_option): ?>

                                <option
                                    value="<?php echo htmlspecialchars($type_option); ?>"
                                    <?php
                                    if ($asset_type === $type_option) {
                                        echo 'selected';
                                    }
                                    ?>
                                >
                                    <?php echo htmlspecialchars($type_option); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>

            </div>

            <div class="form-row">

                <div class="form-column">

                    <div class="form-group">

                        <label for="hostname">
                            Hostname
                        </label>

                        <input
                            type="text"
                            id="hostname"
                            name="hostname"
                            maxlength="100"
                            value="<?php echo htmlspecialchars($hostname); ?>"
                            placeholder="rhel-web-01"
                        >

                    </div>

                </div>

                <div class="form-column">

                    <div class="form-group">

                        <label for="status">
                            Status *
                        </label>

                        <select
                            id="status"
                            name="status"
                            required
                        >

                            <?php foreach ($allowed_statuses as $status_option): ?>

                                <option
                                    value="<?php echo htmlspecialchars($status_option); ?>"
                                    <?php
                                    if ($status === $status_option) {
                                        echo 'selected';
                                    }
                                    ?>
                                >
                                    <?php echo htmlspecialchars($status_option); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>

            </div>

            <div class="form-row">

                <div class="form-column">

                    <div class="form-group">

                        <label for="manufacturer">
                            Manufacturer
                        </label>

                        <input
                            type="text"
                            id="manufacturer"
                            name="manufacturer"
                            maxlength="100"
                            value="<?php echo htmlspecialchars($manufacturer); ?>"
                            placeholder="Dell, HP, Lenovo, Cisco"
                        >

                    </div>

                </div>

                <div class="form-column">

                    <div class="form-group">

                        <label for="model">
                            Model
                        </label>

                        <input
                            type="text"
                            id="model"
                            name="model"
                            maxlength="100"
                            value="<?php echo htmlspecialchars($model); ?>"
                            placeholder="PowerEdge R740"
                        >

                    </div>

                </div>

            </div>

            <div class="form-row">

                <div class="form-column">

                    <div class="form-group">

                        <label for="serial_number">
                            Serial Number
                        </label>

                        <input
                            type="text"
                            id="serial_number"
                            name="serial_number"
                            maxlength="100"
                            value="<?php echo htmlspecialchars($serial_number); ?>"
                            placeholder="Manufacturer serial number"
                        >

                    </div>

                </div>

                <div class="form-column">

                    <div class="form-group">

                        <label for="operating_system">
                            Operating System
                        </label>

                        <input
                            type="text"
                            id="operating_system"
                            name="operating_system"
                            maxlength="150"
                            value="<?php echo htmlspecialchars($operating_system); ?>"
                            placeholder="Red Hat Enterprise Linux 7.5"
                        >

                    </div>

                </div>

            </div>

            <div class="form-row">

                <div class="form-column">

                    <div class="form-group">

                        <label for="ip_address">
                            IP Address
                        </label>

                        <input
                            type="text"
                            id="ip_address"
                            name="ip_address"
                            maxlength="45"
                            value="<?php echo htmlspecialchars($ip_address); ?>"
                            placeholder="192.168.56.123"
                        >

                        <span class="help-text">
                            IPv4 and IPv6 addresses are supported.
                        </span>

                    </div>

                </div>

                <div class="form-column">

                    <div class="form-group">

                        <label for="location">
                            Location
                        </label>

                        <input
                            type="text"
                            id="location"
                            name="location"
                            maxlength="150"
                            value="<?php echo htmlspecialchars($location); ?>"
                            placeholder="Home Lab Rack, Server Room, Office"
                        >

                    </div>

                </div>

            </div>

            <div class="form-group">

                <label for="assigned_user_id">
                    Assigned User
                </label>

                <select
                    id="assigned_user_id"
                    name="assigned_user_id"
                >

                    <option value="">
                        Unassigned
                    </option>

                    <?php foreach ($users as $user): ?>

                        <option
                            value="<?php echo (int) $user['id']; ?>"
                            <?php
                            if (
                                $assigned_user_id !== '' &&
                                (int) $assigned_user_id === (int) $user['id']
                            ) {
                                echo 'selected';
                            }
                            ?>
                        >
                            <?php
                            echo htmlspecialchars(
                                create_asset_user_name(
                                    $user['first_name'],
                                    $user['last_name'],
                                    $user['username'],
                                    $user['department']
                                )
                            );
                            ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <span class="help-text">
                    Only active user accounts are displayed.
                </span>

            </div>

            <hr>

            <button type="submit">
                Create Asset
            </button>

            <a
                class="button button-secondary"
                href="/assets/index.php"
            >
                Cancel
            </a>

        </form>

    </div>

</div>

<?php

include dirname(__FILE__) . '/../includes/layout-footer.php';

?>
