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

/*
 * Load asset
 */
if ($error_message === '') {

    try {

        $stmt = $pdo->prepare(
            "SELECT id, asset_tag FROM assets WHERE id = :id LIMIT 1"
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
 * Handle delete
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $asset) {

    try {

        $stmt = $pdo->prepare(
            "DELETE FROM assets WHERE id = :id"
        );

        $stmt->execute(array(':id' => $asset_id));

        header("Location: /assets/index.php?deleted=1");
        exit;

    } catch (PDOException $e) {
        $error_message = 'Delete failed.';
    }
}

$page_title = 'Delete Asset';

$page_actions =
    '<a class="button button-secondary" href="/assets/view.php?id=' . $asset_id . '">Cancel</a>';

include dirname(__FILE__) . '/../includes/layout-header.php';

?>

<?php if ($error_message): ?>
<div class="error-message"><?php echo $error_message; ?></div>
<?php endif; ?>

<?php if ($asset): ?>

<div class="panel">
<div class="panel-body">

<h3>Confirm Delete</h3>

<p>
Are you sure you want to delete:
<strong><?php echo htmlspecialchars($asset['asset_tag']); ?></strong> ?
</p>

<form method="post">

<button class="button button-danger" type="submit">
Delete Asset
</button>

</form>

</div>
</div>

<?php endif; ?>

<?php include dirname(__FILE__) . '/../includes/layout-footer.php'; ?>
