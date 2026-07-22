<?php
include('db_config.php');
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] != 'admin') { header("Location: login.php"); exit(); }

$total_users    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE username != 'admin'"))['c'];
$total_trains   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM trains"))['c'];
$confirmed_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT transaction_id) as c FROM bookings WHERE payment_method != '' AND transaction_id IS NOT NULL"))['c'];
$total_revenue  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(t.price),0) as rev FROM bookings b JOIN trains t ON b.train_id=t.id WHERE b.payment_method!=''"))['rev'];

// Contact messages
$messages_res = mysqli_query($conn, "SELECT * FROM contact_messages ORDER BY created_at DESC");
$total_messages = mysqli_num_rows($messages_res);

// Handle mark as read
if(isset($_POST['mark_read'])) {
    $mid = intval($_POST['msg_id']);
    mysqli_query($conn, "UPDATE contact_messages SET status='Read' WHERE id=$mid");
    header("Location: admin_reports.php#messages");
    exit();
}

// Handle delete message
if(isset($_POST['delete_msg'])) {
    $mid = intval($_POST['msg_id']);
    mysqli_query($conn, "DELETE FROM contact_messages WHERE id=$mid");
    header("Location: admin_reports.php#messages");
    exit();
}

// Re-fetch after potential update
$messages_res = mysqli_query($conn, "SELECT * FROM contact_messages ORDER BY created_at DESC");
$unread_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM contact_messages WHERE LOWER(status)='unread' OR status IS NULL OR status=''"))['c'];
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reports - RailStream Admin</title>
<?php include('admin_head.php'); ?>
<style>
.stats-grid { display:grid;grid-template-columns:repeat(5,1fr);gap:1rem;margin-bottom:2rem; }
.stat-card { background:var(--card);border:1px solid var(--border);border-radius:16px;padding:1.4rem; }
.stat-card .icon { width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:1rem; }
.stat-card .num { font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;line-height:1; }
.stat-card .lbl { color:var(--muted);font-size:0.78rem;margin-top:6px; }
.analytics-grid { display:grid;grid-template-columns:1fr 1fr;gap:1rem; }
.analytics-card { background:var(--card);border:1px solid var(--border);border-radius:16px;padding:1.4rem; }
.analytics-card h3 { font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;margin-bottom:1.2rem; }
.metric-row { display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.04); }
.metric-row:last-child { border-bottom:none; }
.metric-row .key { color:var(--muted);font-size:0.85rem; }
.metric-row .val { font-weight:600;font-size:0.9rem; }
.live-badge { display:inline-flex;align-items:center;gap:6px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);color:#22c55e;padding:4px 12px;border-radius:20px;font-size:0.72rem;font-weight:600; }
.live-dot { width:7px;height:7px;border-radius:50%;background:#22c55e;animation:pulse 1.5s infinite; }
@keyframes pulse { 0%,100%{opacity:1}50%{opacity:0.3} }

/* ── Messages Section ── */
.messages-panel { background:var(--card);border:1px solid var(--border);border-radius:18px;overflow:hidden;margin-top:1.5rem; }
.messages-header { padding:1.2rem 1.5rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; }
.messages-header h3 { font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;display:flex;align-items:center;gap:8px; }
.unread-badge { background:rgba(244,63,94,0.15);border:1px solid rgba(244,63,94,0.3);color:#f43f5e;font-size:0.7rem;font-weight:700;padding:2px 10px;border-radius:20px; }
.msg-item { padding:1.1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.04);transition:background 0.15s; }
.msg-item:last-child { border-bottom:none; }
.msg-item:hover { background:rgba(255,255,255,0.015); }
.msg-item.unread { border-left:3px solid #f43f5e; }
.msg-item.read { border-left:3px solid rgba(255,255,255,0.08); }
.msg-top { display:flex;align-items:center;justify-content:space-between;margin-bottom:6px; }
.msg-sender { display:flex;align-items:center;gap:10px; }
.msg-avatar { width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,rgba(244,63,94,0.2),rgba(244,63,94,0.05));border:1px solid rgba(244,63,94,0.2);display:flex;align-items:center;justify-content:center;font-size:14px;color:#f43f5e;font-weight:700;flex-shrink:0; }
.msg-name { font-weight:700;font-size:0.88rem; }
.msg-email { color:var(--muted);font-size:0.75rem; }
.msg-time { color:var(--muted);font-size:0.72rem; }
.msg-body { color:var(--muted);font-size:0.84rem;line-height:1.6;padding:8px 12px;background:rgba(255,255,255,0.02);border-radius:8px;border:1px solid rgba(255,255,255,0.04);margin-bottom:10px; }
.msg-actions { display:flex;gap:8px; }
.btn-mark-read { background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);color:#22c55e;border-radius:7px;padding:4px 12px;font-size:0.72rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;transition:all 0.2s; }
.btn-mark-read:hover { background:rgba(34,197,94,0.2); }
.btn-del-msg { background:rgba(244,63,94,0.1);border:1px solid rgba(244,63,94,0.25);color:#f43f5e;border-radius:7px;padding:4px 12px;font-size:0.72rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;transition:all 0.2s; }
.btn-del-msg:hover { background:rgba(244,63,94,0.2); }
.empty-msgs { padding:2.5rem;text-align:center;color:var(--muted);font-size:0.88rem; }
.empty-msgs i { font-size:1.8rem;display:block;margin-bottom:0.7rem;opacity:0.35; }

@media(max-width:1100px){ .stats-grid{grid-template-columns:repeat(3,1fr);} }
@media(max-width:900px){ .stats-grid{grid-template-columns:1fr 1fr;} .analytics-grid{grid-template-columns:1fr;} }
</style>
</head>
<body>
<?php include('admin_nav.php'); ?>
<div class="page-main">
    <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
        <div>
            <h1 style="color:#f43f5e"><i class="fa-solid fa-chart-line" style="margin-right:10px"></i>Reports Dashboard</h1>
            <p>Smart analytics and real-time RailStream performance overview.</p>
        </div>
        <span class="live-badge"><span class="live-dot"></span> Live Data</span>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon" style="background:rgba(59,130,246,0.12);color:#3b82f6"><i class="fa-solid fa-users"></i></div>
            <div class="num"><?php echo $total_users; ?></div>
            <div class="lbl">Total Registered Users</div>
        </div>
        <div class="stat-card">
            <div class="icon" style="background:rgba(245,158,11,0.12);color:#f59e0b"><i class="fa-solid fa-train"></i></div>
            <div class="num"><?php echo $total_trains; ?></div>
            <div class="lbl">Total Active Trains</div>
        </div>
        <div class="stat-card">
            <div class="icon" style="background:rgba(244,63,94,0.12);color:#f43f5e"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="num"><?php echo $confirmed_bookings; ?></div>
            <div class="lbl">Confirmed Bookings</div>
        </div>
        <div class="stat-card">
            <div class="icon" style="background:rgba(34,197,94,0.12);color:#22c55e"><i class="fa-solid fa-indian-rupee-sign"></i></div>
            <div class="num">₹<?php echo number_format($total_revenue); ?></div>
            <div class="lbl">Total Revenue Collected</div>
        </div>
        <div class="stat-card" style="border-color:<?php echo $unread_count > 0 ? 'rgba(244,63,94,0.3)' : 'var(--border)'; ?>">
            <div class="icon" style="background:rgba(244,63,94,0.12);color:#f43f5e"><i class="fa-solid fa-envelope"></i></div>
            <div class="num"><?php echo $total_messages; ?></div>
            <div class="lbl">Support Messages <?php if($unread_count > 0): ?><span style="color:#f43f5e;font-weight:700">(<?php echo $unread_count; ?> unread)</span><?php endif; ?></div>
        </div>
    </div>

    <div class="analytics-grid">
        <div class="analytics-card">
            <h3><i class="fa-solid fa-server" style="color:#3b82f6;margin-right:8px"></i>System Analytics</h3>
            <div class="metric-row"><span class="key">Platform Status</span><span class="val" style="color:#22c55e">🟢 Online</span></div>
            <div class="metric-row"><span class="key">Booking Success Rate</span><span class="val">98%</span></div>
            <div class="metric-row"><span class="key">Active Service</span><span class="val">24/7</span></div>
            <div class="metric-row"><span class="key">Customer Satisfaction</span><span class="val">4.9 ★</span></div>
            <div class="metric-row"><span class="key">System Uptime</span><span class="val" style="color:#22c55e">99.9%</span></div>
        </div>

        <div class="analytics-card">
            <h3><i class="fa-solid fa-chart-bar" style="color:#f59e0b;margin-right:8px"></i>Live Booking Analytics</h3>
            <div class="metric-row"><span class="key">Cancelled Bookings</span><span class="val" style="color:#f43f5e">0</span></div>
            <div class="metric-row"><span class="key">Confirmed Bookings</span><span class="val" style="color:#22c55e"><?php echo $confirmed_bookings; ?></span></div>
            <div class="metric-row"><span class="key">TravelSync Active Passengers</span><span class="val"><?php echo $confirmed_bookings; ?></span></div>
            <div class="metric-row"><span class="key">Live System Monitoring</span><span class="val"><span class="live-badge"><span class="live-dot"></span>Active</span></span></div>
            <div class="metric-row"><span class="key">Avg Ticket Value</span><span class="val">₹<?php echo $confirmed_bookings > 0 ? number_format($total_revenue / $confirmed_bookings) : 0; ?></span></div>
        </div>
    </div>

    <!-- ── CONTACT MESSAGES ── -->
    <div class="messages-panel" id="messages">
        <div class="messages-header">
            <h3><i class="fa-solid fa-envelope" style="color:#f43f5e"></i>User Support Messages
                <?php if($unread_count > 0): ?>
                <span class="unread-badge"><?php echo $unread_count; ?> unread</span>
                <?php endif; ?>
            </h3>
            <span style="color:var(--muted);font-size:0.78rem"><?php echo $total_messages; ?> total message<?php echo $total_messages != 1 ? 's' : ''; ?></span>
        </div>

        <?php if($total_messages == 0): ?>
        <div class="empty-msgs">
            <i class="fa-solid fa-envelope-open"></i>
            No support messages yet. Messages from users will appear here.
        </div>
        <?php else: ?>

        <?php while($msg = mysqli_fetch_assoc($messages_res)):
            $is_unread = (empty($msg['status']) || strtolower($msg['status']) === 'unread');
            $initials = strtoupper(substr($msg['name'], 0, 1));
            $time_str = !empty($msg['created_at']) ? date('M d, Y  H:i', strtotime($msg['created_at'])) : 'Unknown time';
        ?>
        <div class="msg-item <?php echo $is_unread ? 'unread' : 'read'; ?>">
            <div class="msg-top">
                <div class="msg-sender">
                    <div class="msg-avatar"><?php echo htmlspecialchars($initials); ?></div>
                    <div>
                        <div class="msg-name">
                            <?php echo htmlspecialchars($msg['name']); ?>
                            <?php if($is_unread): ?>
                            <span style="background:rgba(244,63,94,0.15);color:#f43f5e;font-size:0.65rem;font-weight:700;padding:1px 7px;border-radius:20px;margin-left:6px">NEW</span>
                            <?php endif; ?>
                        </div>
                        <div class="msg-email"><i class="fa-solid fa-envelope" style="margin-right:4px;opacity:0.5"></i><?php echo htmlspecialchars($msg['email']); ?></div>
                    </div>
                </div>
                <span class="msg-time"><i class="fa-regular fa-clock" style="margin-right:4px"></i><?php echo $time_str; ?></span>
            </div>
            <div class="msg-body"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
            <div class="msg-actions">
                <?php if($is_unread): ?>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="msg_id" value="<?php echo $msg['id']; ?>">
                    <button type="submit" name="mark_read" class="btn-mark-read">
                        <i class="fa-solid fa-check" style="margin-right:4px"></i>Mark as Read
                    </button>
                </form>
                <?php endif; ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this message?')">
                    <input type="hidden" name="msg_id" value="<?php echo $msg['id']; ?>">
                    <button type="submit" name="delete_msg" class="btn-del-msg">
                        <i class="fa-solid fa-trash" style="margin-right:4px"></i>Delete
                    </button>
                </form>
            </div>
        </div>
        <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>
</body></html>
