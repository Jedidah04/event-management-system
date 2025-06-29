<?php session_start();
$host="localhost"; $user="root"; $pass=""; $db="event_management";
$conn=new mysqli($host,$user,$pass,$db) or die();
$uid=$_SESSION['user_id'];
$res=$conn->prepare("SELECT * FROM events WHERE user_id=? ORDER BY date");
$res->bind_param("i",$uid); $res->execute();
$events=$res->get_result(); $res->close(); $conn->close();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>My Events</title>
<style>
body{font-family:Arial;background:#f4f4f4;padding:20px;}
.container{max-width:800px;margin:auto;background:#fff;padding:20px;border-radius:8px;box-shadow:0 0 8px rgba(0,0,0,.1);}
h2{color:#007BFF;text-align:center;}
.event{border-bottom:1px solid #ddd;padding:10px 0;}
.btn{display:inline-block;padding:6px 12px;background:#007BFF;color:#fff;border-radius:4px;text-decoration:none;}
.btn-disabled{background:#6c757d;cursor:not-allowed;}
</style>
</head><body>
<div class="container">
  <h2>My Events</h2>
  <?php if($events->num_rows): while($r=$events->fetch_assoc()): ?>
    <div class="event">
      <div><?=htmlspecialchars($r['title'])?></div>
      <small><?=htmlspecialchars($r['date'])?></small><br>
      <?= $r['ticket_price']>0
         ? '<a href="pay.php?event_id='.$r['id'].'" class="btn">Pay ₦'.number_format($r['ticket_price'],2).'</a>'
         : '<span class="btn-disabled">Free</span>' ?>
    </div>
  <?php endwhile; else: ?><p>No events created.</p><?php endif; ?>
  <a href="dashboard.php">← Dashboard</a>
</div>
</body></html>
