<?php

header("Content-Type: application/json");
header("Cache-Control: no-cache, no-store, must-revalidate");

function runCommand($command)
{
    $result = shell_exec($command . " 2>/dev/null");
    return trim((string) $result);
}

function serviceStatus($service)
{
    $status = runCommand("systemctl is-active " . escapeshellarg($service));

    if ($status === "active") {
        return "active";
    }

    return "inactive";
}

function getMemoryInformation()
{
    $output = runCommand("free -m | awk '/^Mem:/ {print $2,$3,$7}'");
    $parts = preg_split('/\s+/', $output);

    $total = isset($parts[0]) ? (int) $parts[0] : 0;
    $used = isset($parts[1]) ? (int) $parts[1] : 0;
    $available = isset($parts[2]) ? (int) $parts[2] : 0;

    $percent = 0;

    if ($total > 0) {
        $percent = round(($used / $total) * 100, 1);
    }

    return array(
        "total_mb" => $total,
        "used_mb" => $used,
        "available_mb" => $available,
        "percent_used" => $percent
    );
}

function getDiskInformation()
{
    $output = runCommand("df -P / | awk 'NR==2 {print $2,$3,$4,$5}'");
    $parts = preg_split('/\s+/', $output);

    $totalKb = isset($parts[0]) ? (int) $parts[0] : 0;
    $usedKb = isset($parts[1]) ? (int) $parts[1] : 0;
    $freeKb = isset($parts[2]) ? (int) $parts[2] : 0;
    $percent = isset($parts[3])
        ? (int) str_replace("%", "", $parts[3])
        : 0;

    return array(
        "total_gb" => round($totalKb / 1048576, 1),
        "used_gb" => round($usedKb / 1048576, 1),
        "free_gb" => round($freeKb / 1048576, 1),
        "percent_used" => $percent
    );
}

function getCpuInformation()
{
    $load = sys_getloadavg();

    return array(
        "load_1" => isset($load[0]) ? round($load[0], 2) : 0,
        "load_5" => isset($load[1]) ? round($load[1], 2) : 0,
        "load_15" => isset($load[2]) ? round($load[2], 2) : 0,
        "cores" => (int) runCommand("nproc")
    );
}

function getPrimaryIp()
{
    $output = runCommand("hostname -I");
    $addresses = preg_split('/\s+/', $output);

    foreach ($addresses as $address) {
        if ($address !== "" && strpos($address, "127.") !== 0) {
            return $address;
        }
    }

    return "Unavailable";
}

$memory = getMemoryInformation();
$disk = getDiskInformation();
$cpu = getCpuInformation();

$response = array(
    "server" => array(
        "hostname" => gethostname(),
        "ip" => getPrimaryIp(),
        "operating_system" => runCommand("cat /etc/redhat-release"),
        "kernel" => runCommand("uname -r"),
        "uptime" => runCommand("uptime -p"),
        "time" => date("Y-m-d H:i:s")
    ),

    "cpu" => $cpu,
    "memory" => $memory,
    "disk" => $disk,

    "services" => array(
        "apache" => serviceStatus("httpd"),
        "ssh" => serviceStatus("sshd"),
        "firewalld" => serviceStatus("firewalld")
    ),

    "security" => array(
        "selinux" => runCommand("getenforce"),
        "failed_logins" => (int) runCommand(
            "lastb 2>/dev/null | grep -v '^$' | grep -v 'btmp begins' | wc -l"
        )
    ),

    "network" => array(
        "gateway" => runCommand(
            "ip route | awk '/default/ {print $3; exit}'"
        ),
        "connections" => (int) runCommand(
            "ss -tun | tail -n +2 | wc -l"
        ),
        "listening_ports" => (int) runCommand(
            "ss -lnt | tail -n +2 | wc -l"
        )
    )
);

echo json_encode($response, JSON_PRETTY_PRINT);
