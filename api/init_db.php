<?php
$dbfile = __DIR__ . '/../db/slaves.db';
if (!file_exists(dirname($dbfile))) mkdir(dirname($dbfile), 0755, true);
$db = new PDO('sqlite:' . $dbfile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS slaves (
  slave_id INTEGER PRIMARY KEY,
  name TEXT,
  led_start INTEGER,
  led_end INTEGER
)");
$db->exec("CREATE TABLE IF NOT EXISTS ledstate (id INTEGER PRIMARY KEY, json TEXT)");
$r = $db->query("SELECT COUNT(*) as c FROM ledstate")->fetch(PDO::FETCH_ASSOC);
if ($r['c'] == 0) {
  $default = json_encode(['leds' => array_fill(0,200,0), 'color' => [255,255,255], 'brightness' => 255]);
  $stmt = $db->prepare("INSERT INTO ledstate (json) VALUES (?)");
  $stmt->execute([$default]);
}
$db->exec("CREATE TABLE IF NOT EXISTS pinglog (id INTEGER PRIMARY KEY AUTOINCREMENT, slave_id INTEGER, ts INTEGER)");
$db->exec("CREATE TABLE IF NOT EXISTS mapping_history (id INTEGER PRIMARY KEY AUTOINCREMENT, slave_id INTEGER, led_start INTEGER, led_end INTEGER, ts INTEGER)");
echo "DB initialized.\n";
?>