<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/verify_hmac.php';

$token = $_GET['token'] ?? null;
if (!verify_hmac_token($token, SECRET)) { http_response_code(403); echo json_encode(['error'=>'invalid_token']); exit; }

$db = new PDO('sqlite:' . DB_FILE);
$row = $db->query("SELECT json FROM ledstate WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
if ($row) {
    echo $row['json'];
} else {
    echo '[]';
}
?>
