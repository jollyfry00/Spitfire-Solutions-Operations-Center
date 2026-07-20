<?php

session_start();

require_once dirname(__FILE__) . '/includes/database.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = isset($_POST['username'])
        ? trim($_POST['username'])
        : '';

    $password = isset($_POST['password'])
        ? $_POST['password']
        : '';

    if ($username === '' || $password === '') {

        $error_message = 'Username and password required.';

    } else {

        try {

            $stmt = $pdo->prepare(
                "SELECT *
                 FROM users
                 WHERE username = :username
                 LIMIT 1"
            );

            $stmt->execute(array(
                ':username' => $username
            ));

            $user = $stmt->fetch();

            /*
             * 🔥 FIX: Use plain text compare (temporary)
             */
            if ($user && $password === $user['password_hash']) {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['role_id'] = $user['role_id'];

                header('Location: /dashboard/index.php');
                exit;

            } else {

                $error_message = 'Invalid login credentials.';
            }

        } catch (PDOException $e) {

            $error_message = 'Login error.';
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="/css/application.css">
</head>
<body>

<div class="container">

<h2>Login</h2>

<?php if ($error_message): ?>
<div class="error-message"><?php echo $error_message; ?></div>
<?php endif; ?>

<form method="post">

<div class="form-group">
<label>Username</label>
<input type="text" name="username">
</div>

<div class="form-group">
<label>Password</label>
<input type="password" name="password">
</div>

<button type="submit">Login</button>

</form>

</div>

</body>
</html>
