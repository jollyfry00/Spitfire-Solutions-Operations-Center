<?php

require_once dirname(__FILE__) . '/../includes/auth.php';
require_login();

$page_title = 'Network Monitoring';

include dirname(__FILE__) . '/../includes/layout-header.php';

$connections = shell_exec("ss -tunap | grep ESTAB");
$listening = shell_exec("ss -lntp");

?>

<meta http-equiv="refresh" content="5">

<div class="panel">
<div class="panel-header"><h3>Live Connections</h3></div>
<div class="panel-body">
<pre><?php echo htmlspecialchars($connections); ?></pre>
</div>
</div>

<div class="panel">
<div class="panel-header"><h3>Listening Ports</h3></div>
<div class="panel-body">
<pre><?php echo htmlspecialchars($listening); ?></pre>
</div>
</div>

<div class="panel">
<div class="panel-header"><h3>Connection Log</h3></div>
<div class="panel-body">
<pre>
<?php
echo htmlspecialchars(shell_exec("tail -n 50 /var/www/html/monitoring/network.log"));
?>
</pre>
</div>
</div>

<?php include dirname(__FILE__) . '/../includes/layout-footer.php'; ?>
