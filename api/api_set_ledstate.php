<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/verify_hmac.php';

$token = $_GET['token'] ?? null;
if (!verify_hmac_token($token, SECRET)) { http_response_code(403); echo json_encode(['error'=>'invalid_token']); exit; }

$raw = file_get_contents('php://input');
$json = json_decode($raw, true);
if (!is_array($json)) { http_response_code(400); echo json_encode(['error'=>'bad']); exit; }

$db = new PDO('sqlite:' . DB_FILE);
$stmt = $db->prepare("UPDATE ledstate SET json = ? WHERE id = 1");
$stmt->execute([json_encode($json, JSON_PRETTY_PRINT)]);
echo json_encode(['ok'=>true]);
?>
