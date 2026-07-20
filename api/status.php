<?php

header('Content-Type: application/json');

echo json_encode([
    'connections' => trim(shell_exec("ss -tunap | wc -l")),
    'cpu' => trim(shell_exec("top -bn1 | grep 'Cpu(s)'")),
    'memory' => trim(shell_exec("free -m | grep Mem"))
]);
