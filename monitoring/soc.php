<?php

require_once dirname(__FILE__) . '/../includes/auth.php';
require_login();

$page_title = 'SOC Dashboard';

include dirname(__FILE__) . '/../includes/layout-header.php';

/* DATA */
$conn_count = shell_exec("ss -tunap | grep ESTAB | wc -l");
$connections = shell_exec("ss -tunap | grep ESTAB");
$failed_logins = shell_exec("grep 'Failed password' /var/log/secure 2>/dev/null | tail -n 10");
$ip_data = shell_exec("ss -tunap | awk '{print $5}' | cut -d: -f1 | sort | uniq -c | sort -nr | head -10");
$blocked = @file_get_contents('/var/www/html/monitoring/blocked_ips.txt');

/* CPU / MEM */
$cpu = shell_exec("top -bn1 | grep 'Cpu(s)'");
$mem = shell_exec("free -m | grep Mem");

/* ALERT LEVEL */
$count = (int)$conn_count;

function alert_level($count) {
    if ($count < 10) return "<span style='color:green'>NORMAL ($count)</span>";
    if ($count < 50) return "<span style='color:orange'>ELEVATED ($count)</span>";
    return "<span style='color:red'>HIGH ALERT ($count)</span>";
}

?>

<meta http-equiv="refresh" content="5">

<style>
.panel { margin-bottom:20px; }
.panel-header { font-weight:bold; }
pre { background:#111; color:#0f0; padding:10px; }
</style>

<div class="panel">
<div class="panel-header">🚨 System Status</div>
<div class="panel-body">
<?php echo alert_level($count); ?>
</div>
</div>

<div class="panel">
<div class="panel-header">🧠 CPU</div>
<pre><?php echo htmlspecialchars($cpu); ?></pre>
</div>

<div class="panel">
<div class="panel-header">💾 Memory</div>
<pre><?php echo htmlspecialchars($mem); ?></pre>
</div>

<div class="panel">
<div class="panel-header">🌐 Top IPs</div>
<pre><?php echo htmlspecialchars($ip_data); ?></pre>
</div>

<div class="panel">
<div class="panel-header">🔴 Failed Logins</div>
<pre><?php echo htmlspecialchars($failed_logins ?: 'None'); ?></pre>
</div>

<div class="panel">
<div class="panel-header">⛔ Blocked IPs</div>
<pre><?php echo htmlspecialchars($blocked ?: 'None'); ?></pre>
</div>

<div class="panel">
<div class="panel-header">🔌 Connections</div>
<pre><?php echo htmlspecialchars($connections); ?></pre>
</div>

<?php include dirname(__FILE__) . '/../includes/layout-footer.php'; ?>
