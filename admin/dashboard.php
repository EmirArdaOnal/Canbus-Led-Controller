<?php
session_start();
if (!isset($_SESSION['logged_in'])) { header('Location: login.php'); exit; }
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Dashboard</title>
<style>
body{font-family:Arial;margin:20px} .slave{border:1px solid #ccc;padding:10px;margin:6px;display:inline-block;width:220px}
.modal{display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;padding:20px;border:1px solid #333}
</style>
</head>
<body>
<h2>Slave Yönetimi</h2>
<button onclick="load()">Yenile</button>
<button onclick="showAdd()">Add Slave</button>
<div id="list"></div>

<div id="modal" class="modal">
  <div id="mdetail"></div>
  <button id="btnPing">Ping</button>
  <button onclick="closeModal()">Close</button>
</div>

<div id="addModal" class="modal">
<form id="addForm" onsubmit="saveSlave(event)">
  Slave ID: <input id="s_id" required><br>
  Name: <input id="s_name"><br>
  Led Start: <input id="s_start" type="number" required><br>
  Led End: <input id="s_end" type="number" required><br>
  <button>Save</button>
</form>
<button onclick="closeAdd()">Close</button>
</div>

<script>
async function load(){
  const token = await getToken();
  const res = await fetch('/api/api_slaves.php?token=' + token);
  const arr = await res.json();
  const div = document.getElementById('list'); div.innerHTML='';
  arr.forEach(s => {
    const el = document.createElement('div'); el.className='slave';
    el.innerHTML = `<b>Slave ${s.slave_id}</b><br>${s.name}<br>LEDs: ${s.led_start} - ${s.led_end}<br>
      <button onclick="showDetail(${s.slave_id})">Open</button>`;
    div.appendChild(el);
  });
}

async function getToken(){
  const r = await fetch('/api/get_token_for_frontend.php');
  const j = await r.json();
  return j.token;
}

async function showDetail(id){
  const token = await getToken();
  const res = await fetch('/api/api_slave_detail.php?token=' + token + '&id=' + id);
  const s = await res.json();
  document.getElementById('mdetail').innerHTML = `<b>Slave ${s.slave_id}</b><br>${s.name}<br>LED ${s.led_start}-${s.led_end}`;
  document.getElementById('btnPing').onclick = async ()=> {
    await fetch('/api/api_slave_ping.php?id=' + id);
    alert('Ping saved');
  };
  document.getElementById('modal').style.display='block';
}
function closeModal(){ document.getElementById('modal').style.display='none'; }

function showAdd(){ document.getElementById('addModal').style.display='block'; }
function closeAdd(){ document.getElementById('addModal').style.display='none'; }

async function saveSlave(e){
  e.preventDefault();
  const token = await getToken();
  const payload = { slave_id: parseInt(document.getElementById('s_id').value), name: document.getElementById('s_name').value, led_start: parseInt(document.getElementById('s_start').value), led_end: parseInt(document.getElementById('s_end').value) };
  const res = await fetch('/api/api_set_slave.php?token=' + token, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
  const j = await res.json();
  closeAdd(); load();
}
load();
</script>
</body></html>
