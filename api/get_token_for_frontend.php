<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/verify_hmac.php';
session_start();
if (!isset($_SESSION['logged_in'])) { http_response_code(403); echo json_encode(['error'=>'login']); exit; }

$token = generate_hmac_token(SECRET);
echo json_encode(['token'=>$token]);
?>
