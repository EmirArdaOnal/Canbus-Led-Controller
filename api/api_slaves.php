<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/verify_hmac.php';

$token = $_GET['token'] ?? null;
if (!verify_hmac_token($token, SECRET)) { 
    http_response_code(403); 
    echo json_encode(['error'=>'invalid_token']); 
    exit; 
}

$db = new PDO('sqlite:' . DB_FILE);
$rows = $db->query("SELECT * FROM slaves ORDER BY slave_id")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows);
?>
