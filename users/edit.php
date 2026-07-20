<?php


require_once dirname(__FILE__) . '/../includes/auth.php';
require_once dirname(__FILE__) . '/../includes/database.php';

require_login();
require_admin();

$error_message = '';
$validation_errors = array();
$user = null;
$roles = array();

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
            "SELECT * FROM users WHERE id = :id LIMIT 1"
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
 * Load roles
 */
try {

    $stmt = $pdo->prepare("SELECT id, name FROM roles ORDER BY name");
    $stmt->execute();
    $roles = $stmt->fetchAll();

} catch (PDOException $e) {}

/*
 * Handle POST
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {

    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $department = trim($_POST['department']);
    $role_id = (int) $_POST['role_id'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password = trim($_POST['password']);

    if ($email === '') {
        $validation_errors[] = 'Email required.';
    }

    if (count($validation_errors) === 0) {

        try {

            if ($password !== '') {

                $password_hash = password_hash($password, PASSWORD_BCRYPT);

                $stmt = $pdo->prepare(
                    "UPDATE users SET
                        email = :email,
                        first_name = :first_name,
                        last_name = :last_name,
                        department = :department,
                        role_id = :role_id,
                        password_hash = :password_hash,
                        is_active = :is_active,
                        updated_at = NOW()
                     WHERE id = :id"
                );

                $stmt->execute(array(
                    ':email' => $email,
                    ':first_name' => $first_name,
                    ':last_name' => $last_name,
                    ':department' => $department,
                    ':role_id' => $role_id,
                    ':password_hash' => $password_hash,
                    ':is_active' => $is_active,
                    ':id' => $user_id
                ));

            } else {

                $stmt = $pdo->prepare(
                    "UPDATE users SET
                        email = :email,
                        first_name = :first_name,
                        last_name = :last_name,
                        department = :department,
                        role_id = :role_id,
                        is_active = :is_active,
                        updated_at = NOW()
                     WHERE id = :id"
                );

                $stmt->execute(array(
                    ':email' => $email,
                    ':first_name' => $first_name,
                    ':last_name' => $last_name,
                    ':department' => $department,
                    ':role_id' => $role_id,
                    ':is_active' => $is_active,
                    ':id' => $user_id
                ));
            }

            header("Location: /users/index.php");
            exit;

        } catch (PDOException $e) {
            $error_message = 'Update failed.';
        }
    }
}

$page_title = 'Edit User';

$page_actions =
    '<a class="button button-secondary" href="/users/index.php">Back</a>';

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

<?php if ($user): ?>

<div class="panel">
<div class="panel-body">

<form method="post">

<div class="form-group">
<label>Username</label>
<input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
</div>

<div class="form-group">
<label>Email</label>
<input type="text" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
</div>

<div class="form-group">
<label>New Password (leave blank to keep current)</label>
<input type="password" name="password">
</div>

<div class="form-group">
<label>First Name</label>
<input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>">
</div>

<div class="form-group">
<label>Last Name</label>
<input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>">
</div>

<div class="form-group">
<label>Department</label>
<input type="text" name="department" value="<?php echo htmlspecialchars($user['department']); ?>">
</div>

<div class="form-group">
<label>Role</label>
<select name="role_id">
<?php foreach ($roles as $role): ?>
<option value="<?php echo $role['id']; ?>" <?php if ($user['role_id']==$role['id']) echo 'selected'; ?>>
<?php echo htmlspecialchars($role['name']); ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="form-group">
<label>
<input type="checkbox" name="is_active" <?php if ($user['is_active']) echo 'checked'; ?>>
 Active Account
</label>
</div>

<button type="submit">Save Changes</button>

</form>

</div>
</div>

<?php endif; ?>

<?php include dirname(__FILE__) . '/../includes/layout-footer.php'; ?>
