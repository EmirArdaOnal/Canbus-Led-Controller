<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/verify_hmac.php';

$token = $_GET['token'] ?? null;
if (!verify_hmac_token($token, SECRET)) { http_response_code(403); echo json_encode(['error'=>'invalid_token']); exit; }
$id = intval($_GET['id'] ?? 0);
$db = new PDO('sqlite:' . DB_FILE);
$stmt = $db->prepare("SELECT * FROM slaves WHERE slave_id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { http_response_code(404); echo json_encode(['error'=>'not_found']); exit; }
echo json_encode($row);
?>
