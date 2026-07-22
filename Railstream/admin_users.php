<?php
include('db_config.php');
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] != 'admin') { header("Location: login.php"); exit(); }
$users = mysqli_query($conn, "SELECT u.*, COUNT(b.id) as booking_count FROM users u LEFT JOIN bookings b ON u.id = b.user_id WHERE u.username != 'admin' GROUP BY u.id ORDER BY u.id DESC");
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE username != 'admin'"))['c'];
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Users - RailStream Admin</title>
<?php include('admin_head.php'); ?>
<style>
.top-stats { display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:2rem; }
.top-stat { background:var(--card);border:1px solid var(--border);border-radius:14px;padding:1.2rem 1.4rem; }
.top-stat .num { font-family:'Syne',sans-serif;font-size:2rem;font-weight:800; }
.top-stat .lbl { color:var(--muted);font-size:0.78rem;margin-top:4px; }
.avatar { width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#06b6d4);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.9rem; }
@media(max-width:768px){ .top-stats{grid-template-columns:1fr 1fr;} }
</style>
</head>
<body>
<?php include('admin_nav.php'); ?>
<div class="page-main">
    <div class="page-header">
        <h1 style="color:#22c55e"><i class="fa-solid fa-users-gear" style="margin-right:10px"></i>Registered Users</h1>
        <p>Manage and monitor all RailStream platform users.</p>
    </div>

    <div class="top-stats">
        <div class="top-stat">
            <div class="num"><?php echo $total_users; ?></div>
            <div class="lbl">Total Registered Users</div>
        </div>
        <div class="top-stat">
            <div class="num">24/7</div>
            <div class="lbl">Active User Access</div>
        </div>
        <div class="top-stat">
            <div class="num">100%</div>
            <div class="lbl">Secure User Database</div>
        </div>
    </div>

    <div class="card" style="overflow-x:auto">
        <table>
            <thead><tr>
                <th>User ID</th><th>Profile</th><th>Full Name</th><th>Email Address</th><th>Bookings</th><th>Status</th>
            </tr></thead>
            <tbody>
            <?php while($u = mysqli_fetch_assoc($users)): ?>
                <tr>
                    <td><span class="badge badge-blue"><?php echo $u['id']; ?></span></td>
                    <td><div class="avatar"><?php echo strtoupper(substr($u['username'],0,1)); ?></div></td>
                    <td style="font-weight:600"><?php echo $u['username']; ?></td>
                    <td style="color:#94a3b8"><?php echo $u['email'] ?: '—'; ?></td>
                    <td><span class="badge badge-amber"><?php echo $u['booking_count']; ?> bookings</span></td>
                    <td><span class="badge badge-green">Active</span></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body></html>
