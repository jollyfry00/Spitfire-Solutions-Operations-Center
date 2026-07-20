<?php


require_once dirname(__FILE__) . '/../includes/auth.php';
require_once dirname(__FILE__) . '/../includes/database.php';

require_login();
require_admin();
$error_message = '';
$user = null;

$user_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($user_id <= 0) {
    $error_message = 'Invalid user ID.';
}

/*
 * Load user
 */
if ($error_message === '') {

    try {

        $stmt = $pdo->prepare(
            "SELECT id, username FROM users WHERE id = :id LIMIT 1"
        );

        $stmt->execute(array(':id' => $user_id));

        $user = $stmt->fetch();

        if (!$user) {
            $error_message = 'User not found.';
        }

    } catch (PDOException $e) {
        $error_message = 'Error loading user.';
    }
}

/*
 * Prevent deleting yourself
 */
if ($user && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
    $error_message = 'You cannot delete your own account.';
}

/*
 * Handle delete
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user && $error_message === '') {

    try {

        $stmt = $pdo->prepare(
            "DELETE FROM users WHERE id = :id"
        );

        $stmt->execute(array(':id' => $user_id));

        header("Location: /users/index.php?deleted=1");
        exit;

    } catch (PDOException $e) {
        $error_message = 'Delete failed.';
    }
}

$page_title = 'Delete User';

$page_actions =
    '<a class="button button-secondary" href="/users/index.php">Cancel</a>';

include dirname(__FILE__) . '/../includes/layout-header.php';

?>

<?php if ($error_message): ?>
<div class="error-message"><?php echo $error_message; ?></div>
<?php endif; ?>

<?php if ($user && $error_message === ''): ?>

<div class="panel">
<div class="panel-body">

<h3>Confirm Delete</h3>

<p>
Are you sure you want to delete user:
<strong><?php echo htmlspecialchars($user['username']); ?></strong> ?
</p>

<form method="post">

<button class="button button-danger" type="submit">
Delete User
</button>

</form>

</div>
</div>

<?php endif; ?>

<?php include dirname(__FILE__) . '/../includes/layout-footer.php'; ?>
