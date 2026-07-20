<?php

require_once dirname(__FILE__) . '/../includes/auth.php';
require_once dirname(__FILE__) . '/../includes/database.php';

require_login();

$tickets = array();
$error_message = '';

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$status_filter = isset($_GET['status'])
    ? trim($_GET['status'])
    : '';

$priority_filter = isset($_GET['priority'])
    ? trim($_GET['priority'])
    : '';

$allowed_statuses = array(
    'New',
    'Assigned',
    'In Progress',
    'Waiting',
    'Resolved',
    'Closed'
);

$allowed_priorities = array(
    'Low',
    'Medium',
    'High',
    'Critical'
);

$total_tickets = 0;
$open_tickets = 0;
$critical_tickets = 0;
$resolved_tickets = 0;

try {

    /*
     * Load overall ticket statistics.
     */
    $statistics_statement = $pdo->prepare(
        "SELECT
            COUNT(*) AS total_tickets,

            SUM(
                CASE
                    WHEN status NOT IN ('Resolved', 'Closed')
                    THEN 1
                    ELSE 0
                END
            ) AS open_tickets,

            SUM(
                CASE
                    WHEN priority = 'Critical'
                    AND status NOT IN ('Resolved', 'Closed')
                    THEN 1
                    ELSE 0
                END
            ) AS critical_tickets,

            SUM(
                CASE
                    WHEN status IN ('Resolved', 'Closed')
                    THEN 1
                    ELSE 0
                END
            ) AS resolved_tickets

         FROM tickets"
    );

    $statistics_statement->execute();

    $statistics = $statistics_statement->fetch();

    if ($statistics) {
        $total_tickets = (int) $statistics['total_tickets'];
        $open_tickets = (int) $statistics['open_tickets'];
        $critical_tickets = (int) $statistics['critical_tickets'];
        $resolved_tickets = (int) $statistics['resolved_tickets'];
    }

    /*
     * Build the filtered ticket query.
     */
    $sql =
        "SELECT
            t.id,
            t.ticket_number,
            t.title,
            t.category,
            t.status,
            t.priority,
            t.created_at,
            t.updated_at,
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
         WHERE 1 = 1";

    $parameters = array();

    if ($search !== '') {

        $sql .=
            " AND (
                t.ticket_number LIKE :search
                OR t.title LIKE :search
                OR t.description LIKE :search
                OR requester.username LIKE :search
                OR requester.first_name LIKE :search
                OR requester.last_name LIKE :search
                OR assigned.username LIKE :search
                OR assigned.first_name LIKE :search
                OR assigned.last_name LIKE :search
            )";

        $parameters[':search'] = '%' . $search . '%';
    }

    if (
        $status_filter !== '' &&
        in_array($status_filter, $allowed_statuses, true)
    ) {

        $sql .= " AND t.status = :status";

        $parameters[':status'] = $status_filter;
    }

    if (
        $priority_filter !== '' &&
        in_array($priority_filter, $allowed_priorities, true)
    ) {

        $sql .= " AND t.priority = :priority";

        $parameters[':priority'] = $priority_filter;
    }

    $sql .=
        " ORDER BY
            CASE t.priority
                WHEN 'Critical' THEN 1
                WHEN 'High' THEN 2
                WHEN 'Medium' THEN 3
                WHEN 'Low' THEN 4
                ELSE 5
            END,
            t.created_at DESC";

    $statement = $pdo->prepare($sql);

    $statement->execute($parameters);

    $tickets = $statement->fetchAll();

} catch (PDOException $exception) {

    error_log(
        'Ticket list error: ' .
        $exception->getMessage()
    );

    $error_message = 'Unable to load tickets.';
}

function ticket_status_class($status)
{
    switch ($status) {

        case 'New':
            return 'status-new';

        case 'Assigned':
            return 'status-assigned';

        case 'In Progress':
            return 'status-progress';

        case 'Waiting':
            return 'status-waiting';

        case 'Resolved':
            return 'status-resolved';

        case 'Closed':
            return 'status-closed';

        default:
            return '';
    }
}

function ticket_priority_class($priority)
{
    switch ($priority) {

        case 'Critical':
            return 'priority-critical';

        case 'High':
            return 'priority-high';

        case 'Medium':
            return 'priority-medium';

        case 'Low':
            return 'priority-low';

        default:
            return '';
    }
}

function ticket_user_name($first_name, $last_name, $username)
{
    $full_name = trim($first_name . ' ' . $last_name);

    if ($full_name !== '') {
        return $full_name;
    }

    if ($username !== null && $username !== '') {
        return $username;
    }

    return 'Unassigned';
}

$page_title = 'Ticket Management';

$page_description =
    'Track incidents, service requests, assignments, priorities, and resolution status.';

$page_actions =
    '<a class="button" href="/tickets/create.php">+ Create Ticket</a>';

include dirname(__FILE__) . '/../includes/layout-header.php';

?>

<?php if (isset($_GET['created'])): ?>

    <div class="success-message">

        Ticket

        <strong>
            <?php echo htmlspecialchars($_GET['created']); ?>
        </strong>

        was created successfully.

    </div>

<?php endif; ?>

<?php if (isset($_GET['updated'])): ?>

    <div class="success-message">
        Ticket updated successfully.
    </div>

<?php endif; ?>

<div class="ticket-statistics">

    <div class="stat-card">

        <h2>
            <?php echo $total_tickets; ?>
        </h2>

        <p>Total Tickets</p>

    </div>

    <div class="stat-card">

        <h2>
            <?php echo $open_tickets; ?>
        </h2>

        <p>Open Tickets</p>

    </div>

    <div class="stat-card">

        <h2>
            <?php echo $critical_tickets; ?>
        </h2>

        <p>Open Critical</p>

    </div>

    <div class="stat-card">

        <h2>
            <?php echo $resolved_tickets; ?>
        </h2>

        <p>Resolved or Closed</p>

    </div>

</div>

<div class="ticket-filter">

    <form method="get" action="/tickets/index.php">

        <label for="search">
            <strong>Search</strong>
        </label>

        <input
            type="text"
            id="search"
            name="search"
            value="<?php echo htmlspecialchars($search); ?>"
            placeholder="Number, title, requester, technician"
        >

        <label for="status">
            <strong>Status</strong>
        </label>

        <select id="status" name="status">

            <option value="">
                All Statuses
            </option>

            <?php foreach ($allowed_statuses as $status_option): ?>

                <option
                    value="<?php echo htmlspecialchars($status_option); ?>"
                    <?php
                    if ($status_filter === $status_option) {
                        echo 'selected';
                    }
                    ?>
                >
                    <?php echo htmlspecialchars($status_option); ?>
                </option>

            <?php endforeach; ?>

        </select>

        <label for="priority">
            <strong>Priority</strong>
        </label>

        <select id="priority" name="priority">

            <option value="">
                All Priorities
            </option>

            <?php foreach ($allowed_priorities as $priority_option): ?>

                <option
                    value="<?php echo htmlspecialchars($priority_option); ?>"
                    <?php
                    if ($priority_filter === $priority_option) {
                        echo 'selected';
                    }
                    ?>
                >
                    <?php echo htmlspecialchars($priority_option); ?>
                </option>

            <?php endforeach; ?>

        </select>

        <button type="submit">
            Apply Filters
        </button>

        <a
            class="button button-secondary"
            href="/tickets/index.php"
        >
            Clear Filters
        </a>

    </form>

</div>

<?php if ($error_message !== ''): ?>

    <div class="error-message">
        <?php echo htmlspecialchars($error_message); ?>
    </div>

<?php else: ?>

    <div class="panel">

        <div class="panel-header">

            <h3>
                Service Ticket Queue
            </h3>

        </div>

        <div class="panel-body">

            <p>
                Showing
                <strong><?php echo count($tickets); ?></strong>
                ticket(s).
            </p>

            <div class="table-responsive">

                <table>

                    <thead>

                        <tr>
                            <th>Ticket Number</th>
                            <th>Title</th>
                            <th>Requester</th>
                            <th>Assigned To</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Created</th>
                            <th>Updated</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if (count($tickets) > 0): ?>

                        <?php foreach ($tickets as $ticket): ?>

                            <tr>

                                <td>

                                    <a
                                        href="/tickets/view.php?id=<?php echo (int) $ticket['id']; ?>"
                                    >
                                        <?php
                                        echo htmlspecialchars(
                                            $ticket['ticket_number']
                                        );
                                        ?>
                                    </a>

                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $ticket['title']
                                    );
                                    ?>
                                </td>

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        ticket_user_name(
                                            $ticket['requester_first_name'],
                                            $ticket['requester_last_name'],
                                            $ticket['requester_username']
                                        )
                                    );
                                    ?>

                                </td>

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        ticket_user_name(
                                            $ticket['assigned_first_name'],
                                            $ticket['assigned_last_name'],
                                            $ticket['assigned_username']
                                        )
                                    );
                                    ?>

                                </td>

                                <td>

                                    <?php if ($ticket['category']): ?>

                                        <?php
                                        echo htmlspecialchars(
                                            $ticket['category']
                                        );
                                        ?>

                                    <?php else: ?>

                                        <span class="text-muted">
                                            Not specified
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <span
                                        class="status-label <?php echo ticket_status_class($ticket['status']); ?>"
                                    >
                                        <?php
                                        echo htmlspecialchars(
                                            $ticket['status']
                                        );
                                        ?>
                                    </span>

                                </td>

                                <td>

                                    <span
                                        class="priority-label <?php echo ticket_priority_class($ticket['priority']); ?>"
                                    >
                                        <?php
                                        echo htmlspecialchars(
                                            $ticket['priority']
                                        );
                                        ?>
                                    </span>

                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $ticket['created_at']
                                    );
                                    ?>
                                </td>

                                <td>

                                    <?php if ($ticket['updated_at']): ?>

                                        <?php
                                        echo htmlspecialchars(
                                            $ticket['updated_at']
                                        );
                                        ?>

                                    <?php else: ?>

                                        <span class="text-muted">
                                            Not updated
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="9">
                                No tickets matched the selected filters.
                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

<?php endif; ?>

<?php

include dirname(__FILE__) . '/../includes/layout-footer.php';

?>
