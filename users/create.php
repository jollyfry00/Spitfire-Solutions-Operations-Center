<?php


require_once dirname(__FILE__) . '/../includes/auth.php';
require_once dirname(__FILE__) . '/../includes/database.php';

require_login();

$error_message = '';
$validation_errors = array();
$roles = array();

$username = '';
$email = '';
$first_name = '';
$last_name = '';
$department = '';
$role_id = '';

/*
 * Load roles
 */
try {

    $stmt = $pdo->prepare(
        "SELECT id, name FROM roles ORDER BY name"
    );

    $stmt->execute();
    $roles = $stmt->fetchAll();

} catch (PDOException $e) {
    $error_message = 'Unable to load roles.';
}

/*
 * Handle POST
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $department = trim($_POST['department']);
    $role_id = (int) $_POST['role_id'];
    $password = $_POST['password'];

    if ($username === '') {
        $validation_errors[] = 'Username required.';
    }

    if ($email === '') {
        $validation_errors[] = 'Email required.';
    }

    if ($password === '') {
        $validation_errors[] = 'Password required.';
    }

    if (count($validation_errors) === 0) {

        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        try {

            $stmt = $pdo->prepare(
                "INSERT INTO users (
                    role_id,
                    username,
                    email,
                    password_hash,
                    first_name,
                    last_name,
                    department,
                    is_active,
                    created_at
                ) VALUES (
                    :role_id,
                    :username,
                    :email,
                    :password_hash,
                    :first_name,
                    :last_name,
                    :department,
                    1,
                    NOW()
                )"
            );

            $stmt->execute(array(
                ':role_id' => $role_id,
                ':username' => $username,
                ':email' => $email,
                ':password_hash' => $password_hash,
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':department' => $department
            ));

            header("Location: /users/index.php");
            exit;

        } catch (PDOException $e) {
            $error_message = 'User creation failed.';
        }
    }
}

$page_title = 'Create User';

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

<div class="panel">
<div class="panel-body">

<form method="post">

<div class="form-group">
<label>Username</label>
<input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>">
</div>

<div class="form-group">
<label>Email</label>
<input type="text" name="email" value="<?php echo htmlspecialchars($email); ?>">
</div>

<div class="form-group">
<label>Password</label>
<input type="password" name="password">
</div>

<div class="form-group">
<label>First Name</label>
<input type="text" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>">
</div>

<div class="form-group">
<label>Last Name</label>
<input type="text" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>">
</div>

<div class="form-group">
<label>Department</label>
<input type="text" name="department" value="<?php echo htmlspecialchars($department); ?>">
</div>

<div class="form-group">
<label>Role</label>
<select name="role_id">
<?php foreach ($roles as $role): ?>
<option value="<?php echo $role['id']; ?>">
<?php echo htmlspecialchars($role['name']); ?>
</option>
<?php endforeach; ?>
</select>
</div>

<button type="submit">Create User</button>

</form>

</div>
</div>

<?php include dirname(__FILE__) . '/../includes/layout-footer.php'; ?>
