<?php

require_once dirname(__FILE__) . '/../includes/auth.php';
require_once dirname(__FILE__) . '/../includes/database.php';

require_login();
require_admin();
$users = array();
$error_message = '';

try {

    $stmt = $pdo->prepare(
        "SELECT
            u.id,
            u.username,
            u.email,
            u.first_name,
            u.last_name,
            u.department,
            u.is_active,
            r.name AS role_name
         FROM users u
         LEFT JOIN roles r ON u.role_id = r.id
         ORDER BY u.username ASC"
    );

    $stmt->execute();
    $users = $stmt->fetchAll();

} catch (PDOException $e) {
    $error_message = 'Unable to load users.';
}

$page_title = 'User Management';

$page_actions =
    '<a class="button" href="/users/create.php">+ Add User</a>';

include dirname(__FILE__) . '/../includes/layout-header.php';

?>

<?php if ($error_message): ?>
<div class="error-message"><?php echo $error_message; ?></div>
<?php endif; ?>

<div class="panel">
<div class="panel-header">
<h3>Users</h3>
</div>

<div class="panel-body">

<table>

<thead>
<tr>
<th>Username</th>
<th>Name</th>
<th>Email</th>
<th>Department</th>
<th>Role</th>
<th>Status</th>
<th>Actions</th>
</tr>
</thead>

<tbody>

<?php if (count($users) > 0): ?>

<?php foreach ($users as $user): ?>

<tr>

<td><?php echo htmlspecialchars($user['username']); ?></td>

<td>
<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
</td>

<td><?php echo htmlspecialchars($user['email']); ?></td>

<td><?php echo htmlspecialchars($user['department']); ?></td>

<td><?php echo htmlspecialchars($user['role_name']); ?></td>

<td>
<?php echo $user['is_active'] ? 'Active' : 'Disabled'; ?>
</td>

<td>

<a href="/users/edit.php?id=<?php echo $user['id']; ?>">Edit</a> |
<a href="/users/delete.php?id=<?php echo $user['id']; ?>">Delete</a>

</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>
<td colspan="7">No users found.</td>
</tr>

<?php endif; ?>

</tbody>

</table>

</div>
</div>

<?php include dirname(__FILE__) . '/../includes/layout-footer.php'; ?>

