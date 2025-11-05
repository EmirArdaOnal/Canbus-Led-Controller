<?php
session_start();
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $u = $_POST['username']; $p = $_POST['password'];
  if ($u === 'admin' && $p === '1234') {
    $_SESSION['logged_in'] = true;
    header('Location: dashboard.php'); exit;
  } else $err = "Yanlış kullanıcı";
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Login</title></head><body>
<h2>Login</h2>
<form method="post">
  <input name="username" placeholder="user"><br>
  <input type="password" name="password" placeholder="pass"><br>
  <button>Login</button>
</form>
<?php if(isset($err)) echo "<p style='color:red;'>$err</p>"; ?>
</body></html>