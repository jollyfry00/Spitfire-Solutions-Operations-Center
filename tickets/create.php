<?php

require_once dirname(__FILE__) . '/../includes/auth.php';
require_once dirname(__FILE__) . '/../includes/database.php';

require_login();

$error_message = '';
$success_message = '';

$title = '';
$description = '';
$category = '';
$priority = 'Medium';

$allowed_priorities = array(
    'Low',
    'Medium',
    'High',
    'Critical'
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $priority = isset($_POST['priority']) ? trim($_POST['priority']) : 'Medium';

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
    } else {

        try {

            $requester_id = isset($_SESSION['user_id'])
                ? (int) $_SESSION['user_id']
                : 0;

            if ($requester_id <= 0) {
                throw new Exception('Unable to determine the logged-in user.');
            }

            $pdo->beginTransaction();

            $temporary_ticket_number = 'PENDING-' . uniqid();

            $statement = $pdo->prepare(
                "INSERT INTO tickets (
                    ticket_number,
                    requester_id,
                    assigned_to,
                    title,
                    description,
                    category,
                    priority,
                    status,
                    created_at,
                    updated_at
                ) VALUES (
                    :ticket_number,
                    :requester_id,
                    NULL,
                    :title,
                    :description,
                    :category,
                    :priority,
                    'New',
                    NOW(),
                    NOW()
                )"
            );

            $statement->execute(array(
                ':ticket_number' => $temporary_ticket_number,
                ':requester_id' => $requester_id,
                ':title' => $title,
                ':description' => $description,
                ':category' => $category,
                ':priority' => $priority
            ));

            $ticket_id = (int) $pdo->lastInsertId();

            $ticket_number = 'SPT-' . date('Ymd') . '-' . str_pad(
                $ticket_id,
                5,
                '0',
                STR_PAD_LEFT
            );

            $update_statement = $pdo->prepare(
                "UPDATE tickets
                 SET ticket_number = :ticket_number
                 WHERE id = :id"
            );

            $update_statement->execute(array(
                ':ticket_number' => $ticket_number,
                ':id' => $ticket_id
            ));

            $pdo->commit();

            header(
                'Location: /tickets/index.php?created=' .
                urlencode($ticket_number)
            );

            exit;

        } catch (Exception $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log(
                'Ticket creation error: ' .
                $exception->getMessage()
            );

            $error_message = 'Unable to create the ticket.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Create Ticket - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <link rel="stylesheet" href="/css/application.css">
</head>

<body>

<div class="container">

    <h1>Create New Ticket</h1>

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

    <?php if ($success_message !== ''): ?>

        <div class="success-message">
            <?php echo htmlspecialchars($success_message); ?>
        </div>

    <?php endif; ?>

    <form method="post" action="/tickets/create.php">

        <p>
            <label for="title"><strong>Title</strong></label><br>

            <input
                type="text"
                id="title"
                name="title"
                maxlength="200"
                value="<?php echo htmlspecialchars($title); ?>"
                required
                style="width: 100%;"
            >
        </p>

        <p>
            <label for="description"><strong>Description</strong></label><br>

            <textarea
                id="description"
                name="description"
                rows="8"
                required
                style="width: 100%;"
            ><?php echo htmlspecialchars($description); ?></textarea>
        </p>

        <p>
            <label for="category"><strong>Category</strong></label><br>

            <select
                id="category"
                name="category"
                required
            >
                <option value="">Select a category</option>

                <?php foreach ($allowed_categories as $category_option): ?>

                    <option
                        value="<?php echo htmlspecialchars($category_option); ?>"
                        <?php
                        if ($category === $category_option) {
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
            <label for="priority"><strong>Priority</strong></label><br>

            <select
                id="priority"
                name="priority"
                required
            >

                <?php foreach ($allowed_priorities as $priority_option): ?>

                    <option
                        value="<?php echo htmlspecialchars($priority_option); ?>"
                        <?php
                        if ($priority === $priority_option) {
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
            <button type="submit">
                Create Ticket
            </button>

            <a href="/tickets/index.php">
                Cancel
            </a>
        </p>

    </form>

</div>

</body>
</html>
