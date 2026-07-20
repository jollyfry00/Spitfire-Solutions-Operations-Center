<?php

require_once dirname(__FILE__) . '/../includes/auth.php';
require_once dirname(__FILE__) . '/../includes/database.php';

require_login();

$error_message = '';
$validation_errors = array();
$asset = null;
$users = array();

$asset_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($asset_id <= 0) {
    $error_message = 'Invalid asset ID.';
}

/*
 * Load asset
 */
if ($error_message === '') {

    try {

        $stmt = $pdo->prepare(
            "SELECT * FROM assets WHERE id = :id LIMIT 1"
        );

        $stmt->execute(array(':id' => $asset_id));

        $asset = $stmt->fetch();

        if (!$asset) {
            $error_message = 'Asset not found.';
        }

    } catch (PDOException $e) {
        $error_message = 'Error loading asset.';
    }
}

/*
 * Load users
 */
try {

    $stmt = $pdo->prepare(
        "SELECT id, username, first_name, last_name
         FROM users
         WHERE is_active = 1
         ORDER BY username"
    );

    $stmt->execute();
    $users = $stmt->fetchAll();

} catch (PDOException $e) {}

/*
 * Handle POST
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $asset) {

    $asset_tag = trim($_POST['asset_tag']);
    $asset_type = trim($_POST['asset_type']);
    $hostname = trim($_POST['hostname']);
    $ip_address = trim($_POST['ip_address']);
    $status = trim($_POST['status']);
    $assigned_user_id = $_POST['assigned_user_id'] !== ''
        ? (int) $_POST['assigned_user_id']
        : null;

    if ($asset_tag === '') {
        $validation_errors[] = 'Asset tag required.';
    }

    if ($asset_type === '') {
        $validation_errors[] = 'Asset type required.';
    }

    if (count($validation_errors) === 0) {

        try {

            $stmt = $pdo->prepare(
                "UPDATE assets SET
                    asset_tag = :asset_tag,
                    asset_type = :asset_type,
                    hostname = :hostname,
                    ip_address = :ip_address,
                    assigned_user_id = :assigned_user_id,
                    status = :status,
                    updated_at = NOW()
                 WHERE id = :id"
            );

            $stmt->bindValue(':asset_tag', $asset_tag);
            $stmt->bindValue(':asset_type', $asset_type);
            $stmt->bindValue(':hostname', $hostname ?: null);
            $stmt->bindValue(':ip_address', $ip_address ?: null);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':id', $asset_id, PDO::PARAM_INT);

            if ($assigned_user_id === null) {
                $stmt->bindValue(':assigned_user_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':assigned_user_id', $assigned_user_id, PDO::PARAM_INT);
            }

            $stmt->execute();

            header("Location: /assets/view.php?id=" . $asset_id);
            exit;

        } catch (PDOException $e) {
            $error_message = 'Update failed.';
        }
    }
}

$page_title = 'Edit Asset';

$page_actions =
    '<a class="button button-secondary" href="/assets/view.php?id=' . $asset_id . '">Cancel</a>';

include dirname(__FILE__) . '/../includes/layout-header.php';

?>

<?php if ($error_message): ?>
<div class="error-message"><?php echo $error_message; ?></div>
<?php endif; ?>

<?php if (count($validation_errors) > 0): ?>
<div class="error-message">
    <?php foreach ($validation_errors as $e): ?>
        <div><?php echo htmlspecialchars($e); ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($asset): ?>

<div class="panel">
<div class="panel-body">

<form method="post">

<div class="form-group">
<label>Asset Tag</label>
<input type="text" name="asset_tag" value="<?php echo htmlspecialchars($asset['asset_tag']); ?>">
</div>

<div class="form-group">
<label>Type</label>
<input type="text" name="asset_type" value="<?php echo htmlspecialchars($asset['asset_type']); ?>">
</div>

<div class="form-group">
<label>Hostname</label>
<input type="text" name="hostname" value="<?php echo htmlspecialchars($asset['hostname']); ?>">
</div>

<div class="form-group">
<label>IP Address</label>
<input type="text" name="ip_address" value="<?php echo htmlspecialchars($asset['ip_address']); ?>">
</div>

<div class="form-group">
<label>Status</label>
<select name="status">
<?php foreach (array('Active','In Storage','Repair','Retired') as $s): ?>
<option value="<?php echo $s; ?>" <?php if ($asset['status']==$s) echo 'selected'; ?>>
<?php echo $s; ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="form-group">
<label>Assigned User</label>
<select name="assigned_user_id">
<option value="">Unassigned</option>
<?php foreach ($users as $u): ?>
<option value="<?php echo $u['id']; ?>" <?php if ($asset['assigned_user_id']==$u['id']) echo 'selected'; ?>>
<?php echo htmlspecialchars($u['username']); ?>
</option>
<?php endforeach; ?>
</select>
</div>

<button type="submit">Save Changes</button>

</form>

</div>
</div>

<?php endif; ?>

<?php include dirname(__FILE__) . '/../includes/layout-footer.php'; ?>
