<?php

require_once dirname(__FILE__) . '/../includes/auth.php';
require_once dirname(__FILE__) . '/../includes/database.php';

require_login();

$ticket = null;
$users = array();
$error_message = '';
$success_message = '';

$ticket_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$allowed_priorities = array(
    'Low',
    'Medium',
    'High',
    'Critical'
);

$allowed_statuses = array(
    'New',
    'Assigned',
    'In Progress',
    'Waiting',
    'Resolved',
    'Closed'
);

$allowed_categories = array(
    'Hardware',
    'Software',
    'Network',
    'Account Access',
    'Security',
    'Server',
    'Other'
);

if ($ticket_id <= 0) {
    $error_message = 'Invalid ticket ID.';
} else {

    try {

        $user_statement = $pdo->prepare(
            "SELECT
                id,
                username,
                first_name,
                last_name
             FROM users
             WHERE is_active = 1
             ORDER BY first_name, last_name, username"
        );

        $user_statement->execute();
        $users = $user_statement->fetchAll();

        $ticket_statement = $pdo->prepare(
            "SELECT
                id,
                ticket_number,
                requester_id,
                assigned_to,
                title,
                description,
                category,
                priority,
                status,
                created_at,
                updated_at,
                resolved_at,
                closed_at
             FROM tickets
             WHERE id = :id
             LIMIT 1"
        );

        $ticket_statement->execute(array(
            ':id' => $ticket_id
        ));

        $ticket = $ticket_statement->fetch();

        if (!$ticket) {
            $error_message = 'Ticket not found.';
        }

    } catch (PDOException $exception) {

        error_log(
            'Ticket edit load error: ' .
            $exception->getMessage()
        );

        $error_message = 'Unable to load the ticket.';
    }
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $ticket &&
    $error_message === ''
) {

    $title = isset($_POST['title'])
        ? trim($_POST['title'])
        : '';

    $description = isset($_POST['description'])
        ? trim($_POST['description'])
        : '';

    $category = isset($_POST['category'])
        ? trim($_POST['category'])
        : '';

    $priority = isset($_POST['priority'])
        ? trim($_POST['priority'])
        : '';

    $status = isset($_POST['status'])
        ? trim($_POST['status'])
        : '';

    $assigned_to = isset($_POST['assigned_to'])
        ? (int) $_POST['assigned_to']
        : 0;

    if ($title === '') {

        $error_message = 'Title is required.';

    } elseif (strlen($title) > 200) {

        $error_message = 'Title cannot exceed 200 characters.';

    } elseif ($description === '') {

        $error_message = 'Description is required.';

    } elseif (!in_array($category, $allowed_categories, true)) {

        $error_message = 'Please select a valid category.';

    } elseif (!in_array($priority, $allowed_priorities, true)) {

        $error_message = 'Please select a valid priority.';

    } elseif (!in_array($status, $allowed_statuses, true)) {

        $error_message = 'Please select a valid status.';

    } else {

        try {

            $assigned_value = null;

            if ($assigned_to > 0) {

                $check_user_statement = $pdo->prepare(
                    "SELECT id
                     FROM users
                     WHERE id = :id
                       AND is_active = 1
                     LIMIT 1"
                );

                $check_user_statement->execute(array(
                    ':id' => $assigned_to
                ));

                if (!$check_user_statement->fetch()) {
                    throw new Exception(
                        'The selected assigned user is invalid.'
                    );
                }

                $assigned_value = $assigned_to;
            }

            $resolved_at_sql = 'resolved_at';

            if ($status === 'Resolved' && empty($ticket['resolved_at'])) {
                $resolved_at_sql = 'NOW()';
            } elseif ($status !== 'Resolved' && $status !== 'Closed') {
                $resolved_at_sql = 'NULL';
            }

            $closed_at_sql = 'closed_at';

            if ($status === 'Closed' && empty($ticket['closed_at'])) {
                $closed_at_sql = 'NOW()';
            } elseif ($status !== 'Closed') {
                $closed_at_sql = 'NULL';
            }

            $update_statement = $pdo->prepare(
                "UPDATE tickets
                 SET
                    assigned_to = :assigned_to,
                    title = :title,
                    description = :description,
                    category = :category,
                    priority = :priority,
                    status = :status,
                    updated_at = NOW(),
                    resolved_at = " . $resolved_at_sql . ",
                    closed_at = " . $closed_at_sql . "
                 WHERE id = :id"
            );

            $update_statement->bindValue(
                ':assigned_to',
                $assigned_value,
                $assigned_value === null
                    ? PDO::PARAM_NULL
                    : PDO::PARAM_INT
            );

            $update_statement->bindValue(
                ':title',
                $title,
                PDO::PARAM_STR
            );

            $update_statement->bindValue(
                ':description',
                $description,
                PDO::PARAM_STR
            );

            $update_statement->bindValue(
                ':category',
                $category,
                PDO::PARAM_STR
            );

            $update_statement->bindValue(
                ':priority',
                $priority,
                PDO::PARAM_STR
            );

            $update_statement->bindValue(
                ':status',
                $status,
                PDO::PARAM_STR
            );

            $update_statement->bindValue(
                ':id',
                $ticket_id,
                PDO::PARAM_INT
            );

            $update_statement->execute();

            header(
                'Location: /tickets/view.php?id=' .
                $ticket_id .
                '&updated=1'
            );

            exit;

        } catch (Exception $exception) {

            error_log(
                'Ticket update error: ' .
                $exception->getMessage()
            );

            $error_message = 'Unable to update the ticket.';
        }
    }

    $ticket['title'] = $title;
    $ticket['description'] = $description;
    $ticket['category'] = $category;
    $ticket['priority'] = $priority;
    $ticket['status'] = $status;
    $ticket['assigned_to'] = $assigned_to;
}

function format_user_name($user)
{
    $full_name = trim(
        $user['first_name'] . ' ' . $user['last_name']
    );

    if ($full_name !== '') {
        return $full_name . ' (' . $user['username'] . ')';
    }

    return $user['username'];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>
        Edit Ticket - <?php echo htmlspecialchars(APP_NAME); ?>
    </title>

    <link rel="stylesheet" href="/css/application.css">
</head>

<body>

<div class="container">

    <h1>Edit Ticket</h1>

    <p>
        <a href="/dashboard/index.php">Dashboard</a> |
        <a href="/tickets/index.php">Tickets</a> |
        <a href="/logout.php">Logout</a>
    </p>

    <hr>

    <?php if ($error_message !== ''): ?>

        <div class="error-message">
            <?php echo htmlspecialchars($error_message); ?>
        </div>

    <?php endif; ?>

    <?php if ($ticket): ?>

        <h2>
            <?php echo htmlspecialchars($ticket['ticket_number']); ?>
        </h2>

        <form
            method="post"
            action="/tickets/edit.php?id=<?php echo (int) $ticket['id']; ?>"
        >

            <p>
                <label for="title">
                    <strong>Title</strong>
                </label>
                <br>

                <input
                    type="text"
                    id="title"
                    name="title"
                    maxlength="200"
                    value="<?php echo htmlspecialchars($ticket['title']); ?>"
                    required
                    style="width: 100%;"
                >
            </p>

            <p>
                <label for="description">
                    <strong>Description</strong>
                </label>
                <br>

                <textarea
                    id="description"
                    name="description"
                    rows="8"
                    required
                    style="width: 100%;"
                ><?php echo htmlspecialchars($ticket['description']); ?></textarea>
            </p>

            <p>
                <label for="category">
                    <strong>Category</strong>
                </label>
                <br>

                <select
                    id="category"
                    name="category"
                    required
                >

                    <?php foreach ($allowed_categories as $category_option): ?>

                        <option
                            value="<?php echo htmlspecialchars($category_option); ?>"
                            <?php
                            if ($ticket['category'] === $category_option) {
                                echo 'selected';
                            }
                            ?>
                        >
                            <?php echo htmlspecialchars($category_option); ?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </p>

            <p>
                <label for="priority">
                    <strong>Priority</strong>
                </label>
                <br>

                <select
                    id="priority"
                    name="priority"
                    required
                >

                    <?php foreach ($allowed_priorities as $priority_option): ?>

                        <option
                            value="<?php echo htmlspecialchars($priority_option); ?>"
                            <?php
                            if ($ticket['priority'] === $priority_option) {
                                echo 'selected';
                            }
                            ?>
                        >
                            <?php echo htmlspecialchars($priority_option); ?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </p>

            <p>
                <label for="status">
                    <strong>Status</strong>
                </label>
                <br>

                <select
                    id="status"
                    name="status"
                    required
                >

                    <?php foreach ($allowed_statuses as $status_option): ?>

                        <option
                            value="<?php echo htmlspecialchars($status_option); ?>"
                            <?php
                            if ($ticket['status'] === $status_option) {
                                echo 'selected';
                            }
                            ?>
                        >
                            <?php echo htmlspecialchars($status_option); ?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </p>

            <p>
                <label for="assigned_to">
                    <strong>Assigned To</strong>
                </label>
                <br>

                <select
                    id="assigned_to"
                    name="assigned_to"
                >

                    <option value="0">
                        Unassigned
                    </option>

                    <?php foreach ($users as $user): ?>

                        <option
                            value="<?php echo (int) $user['id']; ?>"
                            <?php
                            if (
                                (int) $ticket['assigned_to'] ===
                                (int) $user['id']
                            ) {
                                echo 'selected';
                            }
                            ?>
                        >
                            <?php
                            echo htmlspecialchars(
                                format_user_name($user)
                            );
                            ?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </p>

            <p>
                <button type="submit">
                    Save Changes
                </button>

                <a
                    href="/tickets/view.php?id=<?php echo (int) $ticket['id']; ?>"
                >
                    Cancel
                </a>
            </p>

        </form>

    <?php endif; ?>

</div>

</body>
</html>
