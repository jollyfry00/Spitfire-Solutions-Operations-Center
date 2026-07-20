<?php

require_once dirname(__FILE__) . '/../includes/auth.php';
require_once dirname(__FILE__) . '/../includes/database.php';

require_login();

$ticket = null;
$notes = array();
$error_message = '';
$note_error_message = '';

$ticket_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($ticket_id <= 0) {
    $error_message = 'Invalid ticket ID.';
} else {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $note_text = isset($_POST['note'])
            ? trim($_POST['note'])
            : '';

        $is_internal = isset($_POST['is_internal'])
            ? 1
            : 0;

        $user_id = isset($_SESSION['user_id'])
            ? (int) $_SESSION['user_id']
            : 0;

        if ($note_text === '') {

            $note_error_message = 'Note text is required.';

        } elseif ($user_id <= 0) {

            $note_error_message = 'Unable to determine the logged-in user.';

        } else {

            try {

                $ticket_check_statement = $pdo->prepare(
                    "SELECT id
                     FROM tickets
                     WHERE id = :ticket_id
                     LIMIT 1"
                );

                $ticket_check_statement->execute(array(
                    ':ticket_id' => $ticket_id
                ));

                if (!$ticket_check_statement->fetch()) {
                    throw new Exception('Ticket not found.');
                }

                $note_statement = $pdo->prepare(
                    "INSERT INTO ticket_notes (
                        ticket_id,
                        user_id,
                        note,
                        is_internal,
                        created_at
                    ) VALUES (
                        :ticket_id,
                        :user_id,
                        :note,
                        :is_internal,
                        NOW()
                    )"
                );

                $note_statement->execute(array(
                    ':ticket_id' => $ticket_id,
                    ':user_id' => $user_id,
                    ':note' => $note_text,
                    ':is_internal' => $is_internal
                ));

                $update_statement = $pdo->prepare(
                    "UPDATE tickets
                     SET updated_at = NOW()
                     WHERE id = :ticket_id"
                );

                $update_statement->execute(array(
                    ':ticket_id' => $ticket_id
                ));

                header(
                    'Location: /tickets/view.php?id=' .
                    $ticket_id .
                    '&note_added=1'
                );

                exit;

            } catch (Exception $exception) {

                error_log(
                    'Ticket note creation error: ' .
                    $exception->getMessage()
                );

                $note_error_message = 'Unable to add the ticket note.';
            }
        }
    }

    try {

        $ticket_statement = $pdo->prepare(
            "SELECT
                t.id,
                t.ticket_number,
                t.title,
                t.description,
                t.category,
                t.priority,
                t.status,
                t.created_at,
                t.updated_at,
                t.resolved_at,
                t.closed_at,
                requester.username AS requester_username,
                requester.first_name AS requester_first_name,
                requester.last_name AS requester_last_name,
                assigned.username AS assigned_username,
                assigned.first_name AS assigned_first_name,
                assigned.last_name AS assigned_last_name
             FROM tickets t
             INNER JOIN users requester
                ON t.requester_id = requester.id
             LEFT JOIN users assigned
                ON t.assigned_to = assigned.id
             WHERE t.id = :id
             LIMIT 1"
        );

        $ticket_statement->execute(array(
            ':id' => $ticket_id
        ));

        $ticket = $ticket_statement->fetch();

        if (!$ticket) {

            $error_message = 'Ticket not found.';

        } else {

            $notes_statement = $pdo->prepare(
                "SELECT
                    n.id,
                    n.note,
                    n.is_internal,
                    n.created_at,
                    u.username,
                    u.first_name,
                    u.last_name
                 FROM ticket_notes n
                 INNER JOIN users u
                    ON n.user_id = u.id
                 WHERE n.ticket_id = :ticket_id
                 ORDER BY n.created_at ASC, n.id ASC"
            );

            $notes_statement->execute(array(
                ':ticket_id' => $ticket_id
            ));

            $notes = $notes_statement->fetchAll();
        }

    } catch (PDOException $exception) {

        error_log(
            'Ticket view error: ' .
            $exception->getMessage()
        );

        $error_message = 'Unable to load the ticket.';
    }
}

function display_user_name($first_name, $last_name, $username)
{
    $full_name = trim($first_name . ' ' . $last_name);

    if ($full_name !== '') {
        return $full_name . ' (' . $username . ')';
    }

    return $username;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>
        View Ticket - <?php echo htmlspecialchars(APP_NAME); ?>
    </title>

    <link rel="stylesheet" href="/css/application.css">
</head>

<body>

<div class="container">

    <h1>Ticket Details</h1>

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

    <?php elseif ($ticket): ?>

        <?php if (isset($_GET['updated'])): ?>

            <div class="success-message">
                Ticket updated successfully.
            </div>

        <?php endif; ?>

        <?php if (isset($_GET['note_added'])): ?>

            <div class="success-message">
                Ticket note added successfully.
            </div>

        <?php endif; ?>

        <h2>
            <?php echo htmlspecialchars($ticket['ticket_number']); ?>
        </h2>

        <table border="1" cellpadding="8" cellspacing="0" width="100%">

            <tr>
                <th align="left" width="25%">Title</th>
                <td>
                    <?php echo htmlspecialchars($ticket['title']); ?>
                </td>
            </tr>

            <tr>
                <th align="left">Status</th>
                <td>
                    <?php echo htmlspecialchars($ticket['status']); ?>
                </td>
            </tr>

            <tr>
                <th align="left">Priority</th>
                <td>
                    <?php echo htmlspecialchars($ticket['priority']); ?>
                </td>
            </tr>

            <tr>
                <th align="left">Category</th>
                <td>
                    <?php echo htmlspecialchars($ticket['category']); ?>
                </td>
            </tr>

            <tr>
                <th align="left">Requester</th>
                <td>
                    <?php
                    echo htmlspecialchars(
                        display_user_name(
                            $ticket['requester_first_name'],
                            $ticket['requester_last_name'],
                            $ticket['requester_username']
                        )
                    );
                    ?>
                </td>
            </tr>

            <tr>
                <th align="left">Assigned To</th>
                <td>
                    <?php
                    if ($ticket['assigned_username']) {
                        echo htmlspecialchars(
                            display_user_name(
                                $ticket['assigned_first_name'],
                                $ticket['assigned_last_name'],
                                $ticket['assigned_username']
                            )
                        );
                    } else {
                        echo 'Unassigned';
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <th align="left">Created</th>
                <td>
                    <?php echo htmlspecialchars($ticket['created_at']); ?>
                </td>
            </tr>

            <tr>
                <th align="left">Last Updated</th>
                <td>
                    <?php
                    echo $ticket['updated_at']
                        ? htmlspecialchars($ticket['updated_at'])
                        : 'Not updated';
                    ?>
                </td>
            </tr>

            <tr>
                <th align="left">Resolved</th>
                <td>
                    <?php
                    echo $ticket['resolved_at']
                        ? htmlspecialchars($ticket['resolved_at'])
                        : 'Not resolved';
                    ?>
                </td>
            </tr>

            <tr>
                <th align="left">Closed</th>
                <td>
                    <?php
                    echo $ticket['closed_at']
                        ? htmlspecialchars($ticket['closed_at'])
                        : 'Not closed';
                    ?>
                </td>
            </tr>

        </table>

        <h3>Description</h3>

        <div
            style="
                border: 1px solid #cccccc;
                padding: 15px;
                min-height: 120px;
                background: #ffffff;
            "
        >
            <?php echo nl2br(htmlspecialchars($ticket['description'])); ?>
        </div>

        <p>
            <a href="/tickets/edit.php?id=<?php echo (int) $ticket['id']; ?>">
                Edit Ticket
            </a>
            |
            <a href="/tickets/index.php">
                Back to Ticket List
            </a>
        </p>

        <hr>

        <h3>Ticket Notes</h3>

        <?php if (count($notes) > 0): ?>

            <?php foreach ($notes as $note): ?>

                <div
                    style="
                        border: 1px solid #cccccc;
                        padding: 12px;
                        margin-bottom: 12px;
                        background: #ffffff;
                    "
                >

                    <p>
                        <strong>
                            <?php
                            echo htmlspecialchars(
                                display_user_name(
                                    $note['first_name'],
                                    $note['last_name'],
                                    $note['username']
                                )
                            );
                            ?>
                        </strong>

                        <br>

                        <small>
                            <?php echo htmlspecialchars($note['created_at']); ?>

                            <?php if ((int) $note['is_internal'] === 1): ?>
                                — Internal Note
                            <?php endif; ?>
                        </small>
                    </p>

                    <p>
                        <?php echo nl2br(htmlspecialchars($note['note'])); ?>
                    </p>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <p>No notes have been added to this ticket.</p>

        <?php endif; ?>

        <h3>Add Note</h3>

        <?php if ($note_error_message !== ''): ?>

            <div class="error-message">
                <?php echo htmlspecialchars($note_error_message); ?>
            </div>

        <?php endif; ?>

        <form
            method="post"
            action="/tickets/view.php?id=<?php echo (int) $ticket['id']; ?>"
        >

            <p>
                <label for="note">
                    <strong>Note</strong>
                </label>
                <br>

                <textarea
                    id="note"
                    name="note"
                    rows="6"
                    required
                    style="width: 100%;"
                ></textarea>
            </p>

            <p>
                <label>
                    <input
                        type="checkbox"
                        name="is_internal"
                        value="1"
                    >
                    Internal note
                </label>
            </p>

            <p>
                <button type="submit">
                    Add Note
                </button>
            </p>

        </form>

    <?php endif; ?>

</div>

</body>
</html>
