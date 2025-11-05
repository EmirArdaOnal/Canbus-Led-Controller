<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/verify_hmac.php';

$token = $_GET['token'] ?? null;
if (!verify_hmac_token($token, SECRET)) { http_response_code(403); echo json_encode(['error'=>'invalid_token']); exit; }

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || !isset($data['slave_id']) || !isset($data['led_start']) || !isset($data['led_end'])) { http_response_code(400); echo json_encode(['error'=>'bad']); exit; }

$db = new PDO('sqlite:' . DB_FILE);
$stmt = $db->prepare("REPLACE INTO slaves (slave_id, name, led_start, led_end) VALUES (?, ?, ?, ?)");
$stmt->execute([$data['slave_id'], $data['name'] ?? ("slave-".$data['slave_id']), $data['led_start'], $data['led_end']]);
$stmt2 = $db->prepare("INSERT INTO mapping_history (slave_id, led_start, led_end, ts) VALUES (?, ?, ?, ?)");
$stmt2->execute([$data['slave_id'], $data['led_start'], $data['led_end'], time()]);

echo json_encode(['ok'=>true]);
?>