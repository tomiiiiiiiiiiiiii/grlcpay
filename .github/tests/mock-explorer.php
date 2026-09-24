<?php
$balance_file = getenv('GRLCPAY_MOCK_BALANCE_FILE');
if ($balance_file === false || $balance_file === '') {
    $balance_file = '/tmp/grlcpay-mock-balance';
}

$balance = '0';
if (is_file($balance_file)) {
    $value = trim((string)file_get_contents($balance_file));
    if (is_numeric($value)) {
        $balance = $value;
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('balance' => (float)$balance));
