<?php
session_start();
if (!isset($_SESSION['logged_in'])) { http_response_code(403); echo json_encode(['error'=>'forbidden']); exit; }
$id = intval($_GET['id'] ?? 0);
$db = new PDO('sqlite:' . __DIR__ . '/../db/slaves.db');
$stmt = $db->prepare("INSERT INTO pinglog (slave_id, ts) VALUES (?, ?)");
$stmt->execute([$id, time()]);
file_put_contents(__DIR__ . '/../db/last_ping.json', json_encode(['slave'=>$id,'ts'=>time()]));
echo json_encode(['ok'=>true]);
?>