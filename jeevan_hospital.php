<?php
session_start();

// DB connection
$conn = new mysqli("localhost","root","","jeevan_hospital");
if($conn->connect_error){ die("DB Failed: ".$conn->connect_error); }

// Register
if(isset($_POST['register'])){
    $u = $_POST['username'];
    $p = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $s = $_POST['subscription'];
    $conn->query("INSERT INTO users(username,password,subscription) VALUES('$u','$p','$s')");
    echo "<p style='color:green'>Registered! Please login.</p>";
}

// Login
if(isset($_POST['login'])){
    $u = $_POST['username']; $p = $_POST['password'];
    $res=$conn->query("SELECT * FROM users WHERE username='$u'");
    if($res->num_rows){
        $row=$res->fetch_assoc();
        if(password_verify($p,$row['password'])){
            $_SESSION['uid']=$row['id'];
            $_SESSION['username']=$row['username'];
        } else echo "<p style='color:red'>Wrong password!</p>";
    } else echo "<p style='color:red'>User not found!</p>";
}

// Logout
if(isset($_GET['logout'])){ session_destroy(); header("Location: jeevan_hospital.php"); }

// Book Appointment
if(isset($_POST['book']) && isset($_SESSION['uid'])){
    $doc=$_POST['doctor']; $price=$_POST['price']; $date=$_POST['date']; $uid=$_SESSION['uid'];
    $conn->query("INSERT INTO appointments(user_id,doctor_name,price,appointment_date) VALUES('$uid','$doc','$price','$date')");
    echo "<p style='color:green'>Appointment booked!</p>";
}

// Send Chat
if(isset($_POST['send']) && isset($_SESSION['uid'])){
    $appt=$_POST['appt']; $msg=$_POST['msg']; $sender=$_SESSION['username'];
    $conn->query("INSERT INTO messages(appointment_id,sender,message) VALUES('$appt','$sender','$msg')");
    exit;
}

// Poll Chat
if(isset($_GET['poll']) && isset($_SESSION['uid'])){
    $appt=$_GET['appt'];
    $res=$conn->query("SELECT * FROM messages WHERE appointment_id=$appt ORDER BY id ASC");
    while($r=$res->fetch_assoc()){
        echo "<p><b>".$r['sender'].":</b> ".$r['message']." <span style='font-size:10px;color:gray'>".$r['created_at']."</span></p>";
    }
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Jeevan Hospital</title>
<style>
body{font-family:Arial;background:white;color:#b30000;text-align:center;}
h1{background:#b30000;color:white;padding:10px;}
form{padding:15px;margin:15px auto;width:300px;border-radius:10px;}
input,select{padding:8px;margin:5px;width:90%;}
button{background:#b30000;color:white;padding:8px;border:none;border-radius:8px;cursor:pointer;}
button:hover{background:#800000;}
.doctor{border:2px solid #b30000;padding:15px;margin:15px auto;width:300px;border-radius:10px;background:#fff;}
.doctor form{border:none;margin-top:10px;}
.chatbox{border:1px solid #b30000;height:200px;overflow:auto;margin:10px;padding:10px;text-align:left;}
</style>
<script>
function sendMsg(appt){
  let msg=document.getElementById("msg"+appt).value;
  if(msg.trim()=="") return;
  let x=new XMLHttpRequest();
  x.open("POST","");
  x.setRequestHeader("Content-type","application/x-www-form-urlencoded");
  x.send("send=1&appt="+appt+"&msg="+encodeURIComponent(msg));
  document.getElementById("msg"+appt).value="";
}
function poll(appt){
  let x=new XMLHttpRequest();
  x.onload=function(){ document.getElementById("chat"+appt).innerHTML=this.responseText; }
  x.open("GET","?poll=1&appt="+appt,true); x.send();
}
</script>
</head>
<body>
<h1>Jeevan Hospital</h1>

<?php if(!isset($_SESSION['uid'])): ?>
<form method="post" style="border:2px solid #b30000;">
  <h3>Register</h3>
  <input name="username" placeholder="Username" required>
  <input type="password" name="password" placeholder="Password" required>
  <label><input type="radio" name="subscription" value="Basic" required> Basic</label>
  <label><input type="radio" name="subscription" value="Premium" required> Premium</label><br>
  <button name="register">Register</button>
</form>

<form method="post" style="border:2px solid #b30000;">
  <h3>Login</h3>
  <input name="username" placeholder="Username" required>
  <input type="password" name="password" placeholder="Password" required>
  <button name="login">Login</button>
</form>

<?php else: ?>
<p>Welcome <b><?=$_SESSION['username']?></b> | <a href="?logout=1">Logout</a></p>

<h2>Doctors</h2>
<?php
$docs=[["Dr Aniruddh Bose",1000],["Dr Sailee Deshmukh",1500],["Dr Aarti Awasthi",1200],
       ["Dr Anurag Sen",1800],["Dr Mihir Gupta",2000],["Dr Ishaani Basu",2500]];
foreach($docs as $d){
  echo "<div class='doctor'>
        <b>$d[0]</b><br>Fee: Rs $d[1]
        <form method='post'>
            <input type='hidden' name='doctor' value='$d[0]'>
            <input type='hidden' name='price' value='$d[1]'>
            <input type='date' name='date' required><br>
            <button name='book'>Book</button>
        </form></div>";
}

// Show appointments
$res=$conn->query("SELECT * FROM appointments WHERE user_id=".$_SESSION['uid']);
if($res->num_rows){
  echo "<h2>My Appointments</h2>";
  while($r=$res->fetch_assoc()){
    echo "<div class='doctor'><b>".$r['doctor_name']."</b> on ".$r['appointment_date']."
          <div class='chatbox' id='chat".$r['id']."'></div>
          <input id='msg".$r['id']."' placeholder='Type a message'>
          <button onclick='sendMsg(".$r['id'].")' type='button'>Send</button>
          <script>setInterval(()=>poll(".$r['id']."),2000);</script>
          </div>";
  }
}
?>

<?php endif; ?>
</body>
</html>
