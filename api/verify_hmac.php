<?php
function generate_hmac_token($secret, $time = null) {
    if ($time === null) $time = floor(time() / 60);
    return hash_hmac('sha256', (string)$time, $secret);
}
function verify_hmac_token($token, $secret) {
    if (!$token) return false;
    $now = floor(time() / 60);
    $expected = generate_hmac_token($secret, $now);
    if (hash_equals($expected, $token)) return true;
    $expected2 = generate_hmac_token($secret, $now-1);
    return hash_equals($expected2, $token);
}
?>
