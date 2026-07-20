<?php

require_once dirname(__FILE__) . '/../includes/auth.php';
require_login();

$page_title = 'System Monitoring';

include dirname(__FILE__) . '/../includes/layout-header.php';

/*
 * Get system stats
 */
$uptime = shell_exec("uptime");
$memory = shell_exec("free -m");
$disk = shell_exec("df -h /");
$cpu = shell_exec("top -bn1 | grep 'Cpu(s)'");

?>

<div class="panel">
<div class="panel-header"><h3>Server Status</h3></div>
<div class="panel-body">

<h4>Uptime</h4>
<pre><?php echo htmlspecialchars($uptime); ?></pre>

<h4>CPU</h4>
<pre><?php echo htmlspecialchars($cpu); ?></pre>

<h4>Memory</h4>
<pre><?php echo htmlspecialchars($memory); ?></pre>

<h4>Disk</h4>
<pre><?php echo htmlspecialchars($disk); ?></pre>

</div>
</div>

<?php include dirname(__FILE__) . '/../includes/layout-footer.php'; ?>
