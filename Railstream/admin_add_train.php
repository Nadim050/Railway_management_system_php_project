<?php
include('db_config.php');
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] != 'admin') { header("Location: login.php"); exit(); }
$msg = '';
if (isset($_POST['add_train'])) {
    $name       = mysqli_real_escape_string($conn, $_POST['train_name']);
    $orig       = mysqli_real_escape_string($conn, $_POST['origin']);
    $dest       = mysqli_real_escape_string($conn, $_POST['destination']);
    $price      = mysqli_real_escape_string($conn, $_POST['price']);
    $seats      = mysqli_real_escape_string($conn, $_POST['seats']);
    $time       = mysqli_real_escape_string($conn, $_POST['departure_time']);
    $date_from  = mysqli_real_escape_string($conn, $_POST['date_from']);
    $date_to    = mysqli_real_escape_string($conn, $_POST['date_to'] ?? $date_from);
    $sql = "INSERT INTO trains (train_name, origin, destination, price, available_seats, departure_time, travel_date)
            VALUES ('$name','$orig','$dest','$price','$seats','$time','$date_from')";
    if (mysqli_query($conn, $sql)) { $msg = 'success'; } else { $msg = 'error'; }
}
$today = date('Y-m-d');
$trains = mysqli_query($conn, "SELECT * FROM trains ORDER BY travel_date ASC");
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Add Train - RailStream Admin</title>
<?php include('admin_head.php'); ?>
<style>
.add-train-layout { display:grid; grid-template-columns:1fr 380px; gap:24px; align-items:start; }
@media(max-width:1100px){ .add-train-layout { grid-template-columns:1fr; } }
.date-range-wrap { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.cal-label { display:block; color:var(--muted,#64748b); font-size:.75rem; text-transform:uppercase; letter-spacing:.06em; margin-bottom:6px; font-weight:600; }
.availability-note { background:rgba(59,130,246,0.08); border:1px solid rgba(59,130,246,0.2); border-radius:10px; padding:12px 16px; margin-bottom:18px; font-size:.83rem; color:#94a3b8; display:flex; align-items:flex-start; gap:10px; }
.availability-note i { color:#3b82f6; margin-top:2px; flex-shrink:0; }
.range-preview { background:rgba(34,197,94,0.07); border:1px solid rgba(34,197,94,0.18); border-radius:10px; padding:10px 16px; margin-top:10px; font-size:.83rem; color:#22c55e; display:none; }

/* Trains list panel */
.trains-panel { position:sticky; top:20px; }
.trains-panel-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
.trains-panel-header h2 { font-size:1rem; font-weight:700; color:#f1f5f9; margin:0; }
.trains-count { background:rgba(20,184,166,0.15); color:#14b8a6; font-size:.75rem; font-weight:700; padding:3px 10px; border-radius:20px; border:1px solid rgba(20,184,166,0.25); }
.train-list { display:flex; flex-direction:column; gap:8px; max-height:calc(100vh - 180px); overflow-y:auto; padding-right:4px; }
.train-list::-webkit-scrollbar { width:4px; }
.train-list::-webkit-scrollbar-thumb { background:rgba(100,116,139,0.4); border-radius:4px; }
.train-item { background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.07); border-radius:10px; padding:12px 14px; transition:border-color .2s; }
.train-item:hover { border-color:rgba(20,184,166,0.3); }
.train-item-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; }
.train-item-name { font-size:.88rem; font-weight:700; color:#f1f5f9; }
.train-item-price { color:#22c55e; font-size:.85rem; font-weight:700; }
.train-item-route { color:#94a3b8; font-size:.78rem; margin-bottom:6px; }
.train-item-meta { display:flex; align-items:center; gap:10px; }
.train-meta-pill { display:flex; align-items:center; gap:4px; font-size:.72rem; color:#64748b; }
.train-meta-pill i { font-size:.65rem; }
.seats-pill { background:rgba(34,197,94,0.1); color:#22c55e; padding:2px 8px; border-radius:12px; font-size:.7rem; font-weight:600; border:1px solid rgba(34,197,94,0.2); }
.seats-pill.low { background:rgba(245,158,11,0.1); color:#f59e0b; border-color:rgba(245,158,11,0.2); }
.no-trains { text-align:center; padding:40px 20px; color:#64748b; font-size:.85rem; }
.no-trains i { font-size:2rem; margin-bottom:10px; display:block; color:#334155; }
</style>
</head>
<body>
<?php include('admin_nav.php'); ?>
<div class="page-main">
    <div class="page-header">
        <h1><span style="color:#3b82f6">+</span> Add Train</h1>
        <p>Add a new train with schedule, pricing, and availability window.</p>
    </div>
    <?php if($msg==='success'): ?>
        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);color:#22c55e;padding:12px 18px;border-radius:10px;margin-bottom:20px;font-size:0.88rem;">
            <i class="fa-solid fa-circle-check mr-2"></i> Train added successfully! It is now available for booking every day within the selected date range.
        </div>
    <?php endif; ?>

    <div class="add-train-layout">
        <!-- LEFT: Form -->
        <div class="card">
            <form method="POST" style="display:flex;flex-direction:column;gap:18px;" onsubmit="return validateDates()">
                <div>
                    <label>Train Name</label>
                    <input type="text" name="train_name" class="input-field" placeholder="e.g. Hampi Express" required>
                </div>
                <div class="grid-2">
                    <div><label>Origin</label><input type="text" name="origin" class="input-field" placeholder="Departure city" required></div>
                    <div><label>Destination</label><input type="text" name="destination" class="input-field" placeholder="Arrival city" required></div>
                </div>
                <div class="grid-2">
                    <div><label>Price (₹)</label><input type="number" name="price" class="input-field" placeholder="e.g. 850" required></div>
                    <div><label>Total Seats</label><input type="number" name="seats" class="input-field" placeholder="e.g. 50" required></div>
                </div>
                <div>
                    <label>Departure Time</label>
                    <input type="time" name="departure_time" class="input-field" required style="max-width:200px">
                </div>

                <div>
                    <label style="font-size:.85rem;font-weight:700;color:var(--text,#f1f5f9);margin-bottom:8px;display:block">
                        <i class="fa-solid fa-calendar-range" style="color:#3b82f6;margin-right:6px"></i>Availability Period
                    </label>
                    <div class="availability-note">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>The train will be available for booking every day between the selected dates. Users can book any journey date within this window.</span>
                    </div>
                    <div class="date-range-wrap">
                        <div>
                            <span class="cal-label"><i class="fa-solid fa-calendar-day" style="margin-right:4px;color:#22c55e"></i>Available From</span>
                            <input type="date" name="date_from" id="dateFrom" class="input-field" min="<?php echo $today; ?>" required
                                   onchange="updateRangePreview()" style="color:var(--text,#f1f5f9)">
                        </div>
                        <div>
                            <span class="cal-label"><i class="fa-solid fa-calendar-check" style="margin-right:4px;color:#f59e0b"></i>Available Until</span>
                            <input type="date" name="date_to" id="dateTo" class="input-field" min="<?php echo $today; ?>" required
                                   onchange="updateRangePreview()" style="color:var(--text,#f1f5f9)">
                        </div>
                    </div>
                    <div class="range-preview" id="rangePreview">
                        <i class="fa-solid fa-circle-check" style="margin-right:6px"></i>
                        <span id="rangeText"></span>
                    </div>
                </div>

                <button type="submit" name="add_train" class="btn-primary" style="align-self:flex-start">
                    <i class="fa-solid fa-train"></i> Publish Train Schedule
                </button>
            </form>
        </div>

        <!-- RIGHT: Existing trains list -->
        <div class="trains-panel">
            <div class="trains-panel-header">
                <h2><i class="fa-solid fa-train-tram" style="color:#14b8a6;margin-right:8px"></i>Available Trains</h2>
                <span class="trains-count"><?php
                    $count_res = mysqli_query($conn, "SELECT COUNT(*) as c FROM trains");
                    $count_row = mysqli_fetch_assoc($count_res);
                    echo $count_row['c'];
                ?> trains</span>
            </div>
            <div class="train-list">
                <?php
                $trains = mysqli_query($conn, "SELECT * FROM trains ORDER BY travel_date ASC");
                $total = mysqli_num_rows($trains);
                if ($total == 0): ?>
                    <div class="no-trains">
                        <i class="fa-solid fa-train-tram"></i>
                        No trains added yet
                    </div>
                <?php else:
                    while($t = mysqli_fetch_assoc($trains)):
                        $lowSeats = $t['available_seats'] < 15;
                ?>
                    <div class="train-item">
                        <div class="train-item-top">
                            <span class="train-item-name"><?php echo htmlspecialchars($t['train_name']); ?></span>
                            <span class="train-item-price">₹<?php echo number_format($t['price']); ?></span>
                        </div>
                        <div class="train-item-route">
                            <i class="fa-solid fa-location-dot" style="margin-right:4px;font-size:.7rem"></i>
                            <?php echo htmlspecialchars($t['origin']); ?> → <?php echo htmlspecialchars($t['destination']); ?>
                        </div>
                        <div class="train-item-meta">
                            <span class="train-meta-pill">
                                <i class="fa-solid fa-clock"></i> <?php echo $t['departure_time']; ?>
                            </span>
                            <span class="train-meta-pill">
                                <i class="fa-solid fa-calendar"></i> <?php echo $t['travel_date']; ?>
                            </span>
                            <span class="seats-pill <?php echo $lowSeats ? 'low' : ''; ?>">
                                <?php echo $t['available_seats']; ?> seats
                            </span>
                        </div>
                    </div>
                <?php endwhile; endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
function updateRangePreview() {
    const from = document.getElementById('dateFrom').value;
    const to   = document.getElementById('dateTo').value;
    const preview = document.getElementById('rangePreview');
    const text    = document.getElementById('rangeText');
    if (from && to) {
        const f = new Date(from), t = new Date(to);
        const days = Math.round((t - f) / 86400000) + 1;
        if (days > 0) {
            text.textContent = `Train available every day from ${from} to ${to} — ${days} day${days>1?'s':''} total.`;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }
    } else {
        preview.style.display = 'none';
    }
}

function validateDates() {
    const from = document.getElementById('dateFrom').value;
    const to   = document.getElementById('dateTo').value;
    if (!from || !to) { alert('Please select both a start and end date.'); return false; }
    if (new Date(to) < new Date(from)) { alert('"Available Until" date must be on or after "Available From" date.'); return false; }
    return true;
}

document.getElementById('dateFrom').addEventListener('change', function() {
    document.getElementById('dateTo').min = this.value;
    updateRangePreview();
});
</script>
</body></html>
