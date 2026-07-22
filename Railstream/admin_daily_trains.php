<?php
include('db_config.php');
session_start();

if (!isset($_SESSION['username']) || $_SESSION['username'] != 'admin') {
    header("Location: login.php");
    exit();
}

$success = '';
$error = '';

// ── ADD DAILY TRAIN ──
if (isset($_POST['add_train'])) {
    $name    = mysqli_real_escape_string($conn, trim($_POST['train_name']));
    $number  = mysqli_real_escape_string($conn, trim($_POST['train_number']));
    $origin  = mysqli_real_escape_string($conn, trim($_POST['origin']));
    $dest    = mysqli_real_escape_string($conn, trim($_POST['destination']));
    $dep     = mysqli_real_escape_string($conn, trim($_POST['departure_time']));
    $arr     = mysqli_real_escape_string($conn, trim($_POST['arrival_time']));
    $seats   = intval($_POST['available_seats']);
    $price   = floatval($_POST['price']);
    $stops   = mysqli_real_escape_string($conn, trim($_POST['stops']));
    $type    = mysqli_real_escape_string($conn, trim($_POST['train_type']));

    if ($name && $origin && $dest && $dep && $seats > 0 && $price > 0) {
        $q = "INSERT INTO daily_trains (train_name, train_number, origin, destination, departure_time, arrival_time, available_seats, price, stops, train_type)
              VALUES ('$name','$number','$origin','$dest','$dep','$arr','$seats','$price','$stops','$type')";
        if (mysqli_query($conn, $q)) {
            $success = "Daily train '$name' added successfully!";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// ── EDIT DAILY TRAIN ──
if (isset($_POST['edit_train'])) {
    $id     = intval($_POST['edit_id']);
    $name   = mysqli_real_escape_string($conn, trim($_POST['train_name']));
    $number = mysqli_real_escape_string($conn, trim($_POST['train_number']));
    $origin = mysqli_real_escape_string($conn, trim($_POST['origin']));
    $dest   = mysqli_real_escape_string($conn, trim($_POST['destination']));
    $dep    = mysqli_real_escape_string($conn, trim($_POST['departure_time']));
    $arr    = mysqli_real_escape_string($conn, trim($_POST['arrival_time']));
    $seats  = intval($_POST['available_seats']);
    $price  = floatval($_POST['price']);
    $stops  = mysqli_real_escape_string($conn, trim($_POST['stops']));
    $type   = mysqli_real_escape_string($conn, trim($_POST['train_type']));
    $active = isset($_POST['is_active']) ? 1 : 0;

    $q = "UPDATE daily_trains SET train_name='$name', train_number='$number', origin='$origin', destination='$dest',
          departure_time='$dep', arrival_time='$arr', available_seats='$seats', price='$price',
          stops='$stops', train_type='$type', is_active='$active' WHERE id='$id'";
    if (mysqli_query($conn, $q)) {
        $success = "Train updated successfully!";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

// ── DELETE DAILY TRAIN ──
if (isset($_GET['delete']) && intval($_GET['delete']) > 0) {
    $del_id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM daily_trains WHERE id='$del_id'");
    $success = "Train deleted.";
}

// ── TOGGLE ACTIVE ──
if (isset($_GET['toggle']) && intval($_GET['toggle']) > 0) {
    $tog_id = intval($_GET['toggle']);
    mysqli_query($conn, "UPDATE daily_trains SET is_active = NOT is_active WHERE id='$tog_id'");
    $success = "Train status toggled.";
}

// Fetch all daily trains
$trains_res = mysqli_query($conn, "SELECT * FROM daily_trains ORDER BY departure_time ASC");
$daily_trains = [];
while ($dt = mysqli_fetch_assoc($trains_res)) { $daily_trains[] = $dt; }

// Fetch editing train if edit mode
$edit_train = null;
if (isset($_GET['edit']) && intval($_GET['edit']) > 0) {
    $eid = intval($_GET['edit']);
    $edit_res = mysqli_query($conn, "SELECT * FROM daily_trains WHERE id='$eid'");
    $edit_train = mysqli_fetch_assoc($edit_res);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daily Trains — RailStream Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--bg:#07090f;--surface:#0d1117;--card:#111827;--border:rgba(255,255,255,0.07);--blue:#3b82f6;--green:#22c55e;--amber:#f59e0b;--red:#f43f5e;--purple:#a855f7;--cyan:#06b6d4;--text:#f1f5f9;--muted:#64748b;--muted2:#94a3b8}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;min-height:100vh}
body::before{content:'';position:fixed;inset:0;z-index:0;background:radial-gradient(ellipse 80% 50% at 10% 0%,rgba(37,99,235,.1) 0%,transparent 60%),radial-gradient(ellipse 60% 40% at 90% 100%,rgba(6,182,212,.07) 0%,transparent 60%);pointer-events:none}

nav{position:sticky;top:0;z-index:100;background:rgba(13,17,23,.9);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:0 2rem;height:64px;display:flex;align-items:center;justify-content:space-between}
.nav-logo{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--text)}
.logo-icon{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--blue),#1d4ed8);display:flex;align-items:center;justify-content:center;font-size:15px;color:#fff}
.logo-txt{font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:800}
.badge{background:rgba(59,130,246,.15);border:1px solid rgba(59,130,246,.3);color:var(--blue);font-size:.62rem;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:uppercase;letter-spacing:.05em}
.nav-right{display:flex;align-items:center;gap:12px}
.nav-right a{color:var(--muted);text-decoration:none;font-size:.83rem;display:flex;align-items:center;gap:5px;padding:6px 12px;border-radius:8px;transition:all .2s}
.nav-right a:hover{color:var(--text);background:rgba(255,255,255,.05)}
.btn-logout{background:rgba(244,63,94,.1)!important;border:1px solid rgba(244,63,94,.2);color:#f43f5e!important}
.btn-logout:hover{background:rgba(244,63,94,.18)!important}

.main{position:relative;z-index:1;max-width:1300px;margin:0 auto;padding:2.5rem 2rem}

.page-title{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:4px}
.page-title span{color:var(--cyan)}
.page-sub{color:var(--muted2);font-size:.85rem;margin-bottom:2rem}

.alert{padding:12px 16px;border-radius:10px;font-size:.85rem;font-weight:600;margin-bottom:1.5rem;display:flex;align-items:center;gap:8px}
.alert-success{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);color:var(--green)}
.alert-error{background:rgba(244,63,94,.1);border:1px solid rgba(244,63,94,.25);color:var(--red)}

.layout{display:grid;grid-template-columns:1fr 420px;gap:2rem;align-items:start}

.panel{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:1.8rem}
.panel-title{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;margin-bottom:1.5rem;display:flex;align-items:center;gap:8px;color:var(--text)}
.panel-title i{color:var(--cyan)}

.field{margin-bottom:1rem}
.field label{display:block;color:var(--muted);font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px;font-weight:600}
.field input,.field select{width:100%;background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:9px;padding:10px 13px;color:var(--text);font-size:.85rem;outline:none;transition:border-color .2s;font-family:'DM Sans',sans-serif;appearance:none}
.field select{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;padding-right:34px}
.field select option{background:#0d1117}
.field input:focus,.field select:focus{border-color:var(--blue)}
.field input::placeholder{color:var(--muted)}
.fgrid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.fgrid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}

.btn-submit{width:100%;background:linear-gradient(135deg,var(--blue),#2563eb);color:#fff;border:none;border-radius:10px;padding:12px;font-size:.9rem;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:.5rem;transition:opacity .2s}
.btn-submit:hover{opacity:.88}
.btn-edit-submit{background:linear-gradient(135deg,var(--amber),#d97706)}

table{width:100%;border-collapse:collapse}
th{color:var(--muted);font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;font-weight:600;padding:10px 14px;text-align:left;border-bottom:1px solid var(--border)}
td{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.04);font-size:.84rem;vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:rgba(255,255,255,.02)}

.badge-type{display:inline-block;padding:2px 9px;border-radius:6px;font-size:.65rem;font-weight:700}
.status-active{background:rgba(34,197,94,.12);color:var(--green);border:1px solid rgba(34,197,94,.25)}
.status-inactive{background:rgba(100,116,139,.12);color:var(--muted2);border:1px solid rgba(100,116,139,.2)}

.action-btns{display:flex;gap:6px}
.abtn{display:inline-flex;align-items:center;gap:4px;padding:5px 11px;border-radius:7px;font-size:.73rem;font-weight:600;text-decoration:none;cursor:pointer;border:1px solid transparent;transition:all .2s;background:none;font-family:'DM Sans',sans-serif}
.abtn-edit{color:var(--amber);border-color:rgba(245,158,11,.25);background:rgba(245,158,11,.08)}
.abtn-edit:hover{background:rgba(245,158,11,.15)}
.abtn-toggle{color:var(--cyan);border-color:rgba(6,182,212,.25);background:rgba(6,182,212,.08)}
.abtn-toggle:hover{background:rgba(6,182,212,.15)}
.abtn-del{color:var(--red);border-color:rgba(244,63,94,.25);background:rgba(244,63,94,.08)}
.abtn-del:hover{background:rgba(244,63,94,.15)}

.back-link{display:inline-flex;align-items:center;gap:6px;color:var(--muted2);font-size:.82rem;text-decoration:none;margin-bottom:1.2rem;padding:6px 12px;border:1px solid var(--border);border-radius:8px;transition:all .2s}
.back-link:hover{color:var(--text);border-color:rgba(255,255,255,.15)}

.empty-row td{text-align:center;color:var(--muted);padding:2rem}

.tog-wrap{display:flex;align-items:center;gap:8px;font-size:.83rem}
.tog-wrap input[type=checkbox]{width:16px;height:16px;accent-color:var(--green);cursor:pointer}

@media(max-width:900px){.layout{grid-template-columns:1fr}}
</style>
</head>
<body>
<nav>
  <a href="admin_dashboard.php" class="nav-logo">
    <div class="logo-icon"><i class="fa-solid fa-train-subway" style="color:#fff"></i></div>
    <span class="logo-txt">RailStream</span>
    <span class="badge">Admin</span>
  </a>
  <div class="nav-right">
    <a href="admin_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a href="admin_manage_trains.php"><i class="fa-solid fa-train-tram"></i> Trains</a>
    <a href="admin_bookings.php"><i class="fa-solid fa-ticket"></i> Bookings</a>
    <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
  </div>
</nav>

<div class="main">
  <a href="admin_dashboard.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>

  <div class="page-title">Daily <span>Running Trains</span></div>
  <p class="page-sub">Manage trains that run every day. These are shown on user dashboard and are bookable for any date.</p>

  <?php if($success): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
  <?php if($error):   ?><div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

  <div class="layout">

    <!-- LEFT: List -->
    <div>
      <div class="panel">
        <div class="panel-title"><i class="fa-solid fa-calendar-day"></i> All Daily Trains (<?php echo count($daily_trains); ?>)</div>
        <div style="overflow-x:auto">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Train</th>
                <th>Route</th>
                <th>Departs</th>
                <th>Price</th>
                <th>Seats</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if(empty($daily_trains)): ?>
              <tr class="empty-row"><td colspan="8"><i class="fa-solid fa-train" style="font-size:1.4rem;opacity:.3;display:block;margin-bottom:6px"></i>No daily trains yet. Add one →</td></tr>
            <?php else: foreach($daily_trains as $dt): ?>
              <tr>
                <td style="color:var(--muted)"><?php echo $dt['id']; ?></td>
                <td>
                  <div style="font-weight:700;color:var(--text)"><?php echo htmlspecialchars($dt['train_name']); ?></div>
                  <?php if($dt['train_number']): ?><div style="color:var(--muted);font-size:.72rem">#<?php echo htmlspecialchars($dt['train_number']); ?></div><?php endif; ?>
                  <span class="badge-type" style="background:rgba(59,130,246,.1);color:var(--blue);border:1px solid rgba(59,130,246,.2);margin-top:3px"><?php echo htmlspecialchars($dt['train_type']); ?></span>
                </td>
                <td>
                  <div style="font-size:.82rem"><?php echo htmlspecialchars($dt['origin']); ?></div>
                  <div style="color:var(--muted);font-size:.75rem">→ <?php echo htmlspecialchars($dt['destination']); ?></div>
                </td>
                <td>
                  <div style="font-weight:600;color:var(--cyan)"><?php echo substr($dt['departure_time'],0,5); ?></div>
                  <?php if($dt['arrival_time']): ?><div style="color:var(--muted);font-size:.72rem">Arr <?php echo substr($dt['arrival_time'],0,5); ?></div><?php endif; ?>
                </td>
                <td style="color:var(--green);font-weight:700">₹<?php echo number_format($dt['price']); ?></td>
                <td style="color:var(--muted2)"><?php echo $dt['available_seats']; ?></td>
                <td>
                  <?php if($dt['is_active']): ?>
                    <span class="badge-type status-active">Active</span>
                  <?php else: ?>
                    <span class="badge-type status-inactive">Inactive</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="action-btns">
                    <a href="?edit=<?php echo $dt['id']; ?>" class="abtn abtn-edit"><i class="fa-solid fa-pen"></i> Edit</a>
                    <a href="?toggle=<?php echo $dt['id']; ?>" class="abtn abtn-toggle" onclick="return confirm('Toggle this train status?')"><i class="fa-solid fa-power-off"></i></a>
                    <a href="?delete=<?php echo $dt['id']; ?>" class="abtn abtn-del" onclick="return confirm('Delete this daily train? This cannot be undone.')"><i class="fa-solid fa-trash"></i></a>
                  </div>
                </td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- RIGHT: Add / Edit Form -->
    <div>
      <?php if($edit_train): ?>
      <div class="panel" style="border-color:rgba(245,158,11,.2)">
        <div class="panel-title" style="color:var(--amber)"><i class="fa-solid fa-pen"></i> Edit Daily Train</div>
        <form method="POST">
          <input type="hidden" name="edit_id" value="<?php echo $edit_train['id']; ?>">
          <div class="field">
            <label>Train Name *</label>
            <input type="text" name="train_name" value="<?php echo htmlspecialchars($edit_train['train_name']); ?>" required>
          </div>
          <div class="fgrid">
            <div class="field">
              <label>Train Number</label>
              <input type="text" name="train_number" value="<?php echo htmlspecialchars($edit_train['train_number'] ?? ''); ?>" placeholder="e.g. 12951">
            </div>
            <div class="field">
              <label>Train Type *</label>
              <select name="train_type">
                <?php foreach(['Express','Rajdhani','Shatabdi','Superfast','Garib Rath','Jan Shatabdi','Mail','Passenger'] as $t): ?>
                <option value="<?php echo $t; ?>" <?php echo ($edit_train['train_type']==$t)?'selected':''; ?>><?php echo $t; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="fgrid">
            <div class="field">
              <label>Origin *</label>
              <input type="text" name="origin" value="<?php echo htmlspecialchars($edit_train['origin']); ?>" required>
            </div>
            <div class="field">
              <label>Destination *</label>
              <input type="text" name="destination" value="<?php echo htmlspecialchars($edit_train['destination']); ?>" required>
            </div>
          </div>
          <div class="fgrid">
            <div class="field">
              <label>Departure Time *</label>
              <input type="time" name="departure_time" value="<?php echo $edit_train['departure_time']; ?>" required>
            </div>
            <div class="field">
              <label>Arrival Time</label>
              <input type="time" name="arrival_time" value="<?php echo $edit_train['arrival_time'] ?? ''; ?>">
            </div>
          </div>
          <div class="fgrid">
            <div class="field">
              <label>Daily Seats *</label>
              <input type="number" name="available_seats" value="<?php echo $edit_train['available_seats']; ?>" min="1" required>
            </div>
            <div class="field">
              <label>Price (₹) *</label>
              <input type="number" name="price" step="0.01" value="<?php echo $edit_train['price']; ?>" min="1" required>
            </div>
          </div>
          <div class="field">
            <label>Stops (comma separated)</label>
            <input type="text" name="stops" value="<?php echo htmlspecialchars($edit_train['stops'] ?? ''); ?>" placeholder="Station A, Station B, Station C">
          </div>
          <div class="tog-wrap field">
            <input type="checkbox" name="is_active" id="ea" <?php echo $edit_train['is_active'] ? 'checked' : ''; ?>>
            <label for="ea" style="color:var(--text);font-size:.85rem;text-transform:none;letter-spacing:0">Active (visible to users)</label>
          </div>
          <button type="submit" name="edit_train" class="btn-submit btn-edit-submit">
            <i class="fa-solid fa-floppy-disk"></i> Save Changes
          </button>
          <a href="admin_daily_trains.php" style="display:block;text-align:center;margin-top:10px;color:var(--muted);font-size:.8rem;text-decoration:none">Cancel edit</a>
        </form>
      </div>

      <?php else: ?>
      <div class="panel" style="border-color:rgba(59,130,246,.18)">
        <div class="panel-title"><i class="fa-solid fa-circle-plus"></i> Add Daily Train</div>
        <form method="POST">
          <div class="field">
            <label>Train Name *</label>
            <input type="text" name="train_name" placeholder="e.g. Rajdhani Express" required>
          </div>
          <div class="fgrid">
            <div class="field">
              <label>Train Number</label>
              <input type="text" name="train_number" placeholder="e.g. 12951">
            </div>
            <div class="field">
              <label>Train Type *</label>
              <select name="train_type">
                <option value="Express" selected>Express</option>
                <option>Rajdhani</option>
                <option>Shatabdi</option>
                <option>Superfast</option>
                <option>Garib Rath</option>
                <option>Jan Shatabdi</option>
                <option>Mail</option>
                <option>Passenger</option>
              </select>
            </div>
          </div>
          <div class="fgrid">
            <div class="field">
              <label>Origin *</label>
              <input type="text" name="origin" placeholder="From city" required>
            </div>
            <div class="field">
              <label>Destination *</label>
              <input type="text" name="destination" placeholder="To city" required>
            </div>
          </div>
          <div class="fgrid">
            <div class="field">
              <label>Departure Time *</label>
              <input type="time" name="departure_time" required>
            </div>
            <div class="field">
              <label>Arrival Time</label>
              <input type="time" name="arrival_time">
            </div>
          </div>
          <div class="fgrid">
            <div class="field">
              <label>Daily Seats *</label>
              <input type="number" name="available_seats" value="100" min="1" required>
            </div>
            <div class="field">
              <label>Price (₹) *</label>
              <input type="number" name="price" step="0.01" placeholder="e.g. 850" min="1" required>
            </div>
          </div>
          <div class="field">
            <label>Stops (comma separated)</label>
            <input type="text" name="stops" placeholder="Station A, Station B, Station C">
          </div>
          <button type="submit" name="add_train" class="btn-submit">
            <i class="fa-solid fa-circle-plus"></i> Add Daily Train
          </button>
        </form>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>
</body>
</html>
