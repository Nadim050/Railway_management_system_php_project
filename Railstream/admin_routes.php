<?php
include('db_config.php');
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] != 'admin') { header("Location: login.php"); exit(); }

// Handle Add Route
if (isset($_POST['add_route'])) {
    $train_id   = intval($_POST['bus_id']);
    $source     = mysqli_real_escape_string($conn, $_POST['source']);
    $dest       = mysqli_real_escape_string($conn, $_POST['destination']);
    $dep_time   = mysqli_real_escape_string($conn, $_POST['departure_time']);
    $stops_json = mysqli_real_escape_string($conn, $_POST['stops_json'] ?? '[]');
    // Update train record with route info (only columns that exist in schema)
    mysqli_query($conn, "UPDATE trains SET origin='$source', destination='$dest', departure_time='$dep_time', stops='$stops_json' WHERE id=$train_id");
    header("Location: admin_routes.php?success=1");
    exit();
}

// Handle Delete
if (isset($_POST['delete_route'])) {
    $origin = mysqli_real_escape_string($conn, $_POST['origin']);
    $destination = mysqli_real_escape_string($conn, $_POST['destination']);
    mysqli_query($conn, "DELETE FROM trains WHERE origin='$origin' AND destination='$destination'");
    header("Location: admin_routes.php");
    exit();
}

// Get trains for dropdown
$trains_res = mysqli_query($conn, "SELECT id, train_name FROM trains ORDER BY train_name ASC");

// Get routes
$routes = mysqli_query($conn, "SELECT DISTINCT t.origin, t.destination, COUNT(*) as train_count, MIN(t.price) as min_price, MAX(t.price) as max_price, MIN(t.departure_time) as dep_time, MIN(t.train_name) as route_rep, MIN(t.stops) as stops FROM trains t GROUP BY t.origin, t.destination ORDER BY t.origin ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manage Routes - RailStream Admin</title>
<?php include('admin_head.php'); ?>
<style>
/* ── Page layout ── */
.routes-layout { display: grid; grid-template-columns: 420px 1fr; gap: 1.5rem; align-items: start; }
@media(max-width: 1050px) { .routes-layout { grid-template-columns: 1fr; } }

/* ── Form panel ── */
.form-panel {
    background: var(--card); border: 1px solid var(--border);
    border-radius: 18px; padding: 1.8rem; position: sticky; top: 76px;
}
.form-panel h2 {
    font-family: 'Syne', sans-serif; font-size: 1.4rem; font-weight: 800;
    margin-bottom: 4px; color: #06b6d4;
    display: flex; align-items: center; gap: 10px;
}
.form-panel .sub { color: var(--muted); font-size: 0.82rem; margin-bottom: 1.6rem; }

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
.form-full { margin-bottom: 12px; }

.field-group { display: flex; flex-direction: column; gap: 5px; }
.field-label {
    font-size: 0.7rem; font-weight: 700; letter-spacing: 0.08em;
    text-transform: uppercase; color: var(--muted);
}
.field-input {
    background: rgba(255,255,255,0.04); border: 1px solid var(--border);
    border-radius: 9px; padding: 10px 13px;
    color: var(--text); font-size: 0.86rem;
    font-family: 'DM Sans', sans-serif; outline: none;
    transition: border-color 0.2s; width: 100%;
}
.field-input:focus { border-color: #06b6d4; box-shadow: 0 0 0 3px rgba(6,182,212,0.08); }
.field-input::placeholder { color: var(--muted); }
select.field-input { cursor: pointer; }
select.field-input option { background: #1a1f2e; color: #e2e8f0; }

/* View Route button */
.btn-view {
    background: rgba(6,182,212,0.1); border: 1px solid rgba(6,182,212,0.25);
    color: #06b6d4; border-radius: 8px; padding: 5px 12px;
    font-size: 0.75rem; font-weight: 600; cursor: pointer;
    transition: all 0.2s; font-family: 'DM Sans', sans-serif;
    text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
    margin-right: 6px;
}
.btn-view:hover { background: rgba(6,182,212,0.22); }
.action-cell { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }

/* Stops section */
.stops-section {
    background: rgba(255,255,255,0.02); border: 1px solid var(--border);
    border-radius: 10px; padding: 14px; margin-bottom: 14px;
}
.stops-section h4 {
    font-size: 0.72rem; font-weight: 700; letter-spacing: 0.08em;
    text-transform: uppercase; color: var(--muted); margin-bottom: 10px;
}
.stop-input-row { display: flex; gap: 8px; margin-bottom: 8px; }
.stop-input-row input { flex: 1; }
.btn-add-stop {
    background: rgba(6,182,212,0.12); border: 1px solid rgba(6,182,212,0.3);
    color: #06b6d4; border-radius: 8px; padding: 0 14px;
    font-size: 0.8rem; font-weight: 600; cursor: pointer;
    white-space: nowrap; transition: all 0.2s;
    font-family: 'DM Sans', sans-serif;
}
.btn-add-stop:hover { background: rgba(6,182,212,0.22); }
.stops-list { display: flex; flex-direction: column; gap: 5px; }
.stop-item {
    display: flex; align-items: center; justify-content: space-between;
    background: rgba(6,182,212,0.06); border: 1px solid rgba(6,182,212,0.15);
    border-radius: 7px; padding: 7px 11px; font-size: 0.82rem;
}
.stop-item .stop-name { color: #06b6d4; font-weight: 600; }
.stop-item .remove-stop {
    background: none; border: none; color: #f43f5e;
    cursor: pointer; font-size: 12px; padding: 2px 6px;
    border-radius: 5px; transition: background 0.2s;
}
.stop-item .remove-stop:hover { background: rgba(244,63,94,0.12); }

.btn-add-route {
    width: 100%; background: linear-gradient(135deg, #06b6d4, #0891b2);
    color: #fff; border: none; border-radius: 11px;
    padding: 13px; font-size: 0.95rem; font-weight: 700;
    cursor: pointer; font-family: 'DM Sans', sans-serif;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: all 0.2s; box-shadow: 0 0 24px rgba(6,182,212,0.25);
    margin-top: 4px;
}
.btn-add-route:hover { opacity: 0.9; box-shadow: 0 0 36px rgba(6,182,212,0.4); }

/* ── Routes table panel ── */
.table-panel {
    background: var(--card); border: 1px solid var(--border);
    border-radius: 18px; overflow: hidden;
}
.table-panel-header {
    padding: 1.3rem 1.5rem; border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
}
.table-panel-header h3 {
    font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1rem;
    display: flex; align-items: center; gap: 8px;
}
.table-panel-header .count-badge {
    background: rgba(6,182,212,0.12); border: 1px solid rgba(6,182,212,0.25);
    color: #06b6d4; font-size: 0.72rem; font-weight: 700;
    padding: 2px 10px; border-radius: 20px;
}

/* ── Table styles ── */
.route-table { width: 100%; border-collapse: collapse; }
.route-table thead th {
    color: var(--muted); font-size: 0.7rem; text-transform: uppercase;
    letter-spacing: 0.06em; padding: 10px 16px 10px;
    text-align: left; background: rgba(255,255,255,0.02);
    border-bottom: 1px solid var(--border);
}
.route-table tbody tr {
    border-bottom: 1px solid rgba(255,255,255,0.04);
    transition: background 0.15s;
}
.route-table tbody tr:last-child { border-bottom: none; }
.route-table tbody tr:hover { background: rgba(6,182,212,0.03); }
.route-table td { padding: 13px 16px; font-size: 0.84rem; vertical-align: middle; }

.route-from-to {
    display: flex; align-items: center; gap: 8px;
    font-family: 'Syne', sans-serif; font-weight: 700; font-size: 0.9rem;
}
.route-arrow { color: #06b6d4; font-size: 0.75rem; }
.route-count { color: var(--muted); font-size: 0.75rem; margin-top: 2px; }
.price-range { color: #06b6d4; font-weight: 700; font-size: 0.88rem; }
.dep-time { color: var(--muted); font-size: 0.82rem; }

.btn-del {
    background: rgba(244,63,94,0.1); border: 1px solid rgba(244,63,94,0.25);
    color: #f43f5e; border-radius: 8px; padding: 5px 12px;
    font-size: 0.75rem; font-weight: 600; cursor: pointer;
    transition: all 0.2s; font-family: 'DM Sans', sans-serif;
}
.btn-del:hover { background: rgba(244,63,94,0.22); }

.empty-state {
    padding: 3rem; text-align: center; color: var(--muted);
}
.empty-state i { font-size: 2rem; margin-bottom: 0.8rem; display: block; opacity: 0.4; }

.success-banner {
    background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.25);
    color: #22c55e; border-radius: 12px; padding: 12px 18px;
    margin-bottom: 1.2rem; font-size: 0.85rem; font-weight: 600;
    display: flex; align-items: center; gap: 8px;
}
</style>
</head>
<body>
<?php include('admin_nav.php'); ?>
<div class="page-main">
    <div class="page-header">
        <h1 style="color:#06b6d4"><i class="fa-solid fa-route" style="margin-right:10px"></i>Manage Routes</h1>
        <p>Add, organize and manage platform routes and journey paths.</p>
    </div>

    <?php if(isset($_GET['success'])): ?>
    <div class="success-banner"><i class="fa-solid fa-circle-check"></i> Route added and train updated successfully!</div>
    <?php endif; ?>

    <div class="routes-layout">
        <!-- LEFT: Form -->
        <div class="form-panel">
            <h2><i class="fa-solid fa-train" style="font-size:1.1rem"></i>Add New Route</h2>
            <p class="sub">Configure and add a new route to the system.</p>

            <form method="POST" id="routeForm">
                <!-- Select Train -->
                <div class="form-full">
                    <div class="field-group">
                        <label class="field-label">Select Train</label>
                        <select name="bus_id" class="field-input" required>
                            <option value="">— Select Train —</option>
                            <?php
                            mysqli_data_seek($trains_res, 0);
                            while($t = mysqli_fetch_assoc($trains_res)):
                            ?>
                            <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['train_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- Route Name -->
                <div class="form-full">
                    <div class="field-group">
                        <label class="field-label">Route Name</label>
                        <input type="text" name="route_name" class="field-input" placeholder="Bangalore Express" required>
                    </div>
                </div>

                <!-- Source & Destination -->
                <div class="form-row">
                    <div class="field-group">
                        <label class="field-label">Source</label>
                        <input type="text" name="source" class="field-input" placeholder="Bangalore" required>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Destination</label>
                        <input type="text" name="destination" class="field-input" placeholder="Mumbai" required>
                    </div>
                </div>

                <!-- Departure & Arrival -->
                <div class="form-row">
                    <div class="field-group">
                        <label class="field-label">Departure Time</label>
                        <input type="time" name="departure_time" class="field-input" required>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Arrival Time</label>
                        <input type="time" name="arrival_time" class="field-input">
                    </div>
                </div>

                <!-- Duration -->
                <div class="form-full">
                    <div class="field-group">
                        <label class="field-label">Duration</label>
                        <input type="text" name="duration" class="field-input" placeholder="8 Hours">
                    </div>
                </div>

                <!-- Stops -->
                <div class="stops-section">
                    <h4><i class="fa-solid fa-map-pin" style="margin-right:6px;color:#06b6d4"></i>Add Stops &amp; Time</h4>
                    <div class="stop-input-row">
                        <input type="text" id="stopName" class="field-input" placeholder="Enter Stop Name">
                        <button type="button" class="btn-add-stop" onclick="addStop()">
                            <i class="fa-solid fa-plus" style="margin-right:5px"></i>Add
                        </button>
                    </div>
                    <div class="stops-list" id="stopsList"></div>
                    <input type="hidden" name="stops_json" id="stopsJson" value="[]">
                </div>

                <button type="submit" name="add_route" class="btn-add-route">
                    <i class="fa-solid fa-circle-plus"></i> Add Route
                </button>
            </form>
        </div>

        <!-- RIGHT: Routes Table -->
        <div class="table-panel">
            <div class="table-panel-header">
                <h3><i class="fa-solid fa-table-list" style="color:#06b6d4"></i>All Routes</h3>
                <?php
                $cnt_res = mysqli_query($conn, "SELECT COUNT(DISTINCT CONCAT(origin,destination)) as c FROM trains");
                $cnt = mysqli_fetch_assoc($cnt_res)['c'];
                ?>
                <span class="count-badge"><?php echo $cnt; ?> routes</span>
            </div>
            <table class="route-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Route</th>
                        <th>Departure</th>
                        <th>Trains</th>
                        <th>Price Range</th>
                        <th style="min-width:160px">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                mysqli_data_seek($routes, 0);
                while($r = mysqli_fetch_assoc($routes)):
                ?>
                <tr>
                    <td style="color:var(--muted)"><?php echo $i++; ?></td>
                    <td>
                        <div class="route-from-to">
                            <?php echo htmlspecialchars($r['origin']); ?>
                            <span class="route-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                            <?php echo htmlspecialchars($r['destination']); ?>
                        </div>
                        <div class="route-count"><?php echo $r['train_count']; ?> train(s)</div>
                    </td>
                    <td class="dep-time">
                        <?php echo $r['dep_time'] ? htmlspecialchars($r['dep_time']) : '—'; ?>
                    </td>
                    <td>
                        <span style="background:rgba(6,182,212,0.1);border:1px solid rgba(6,182,212,0.2);color:#06b6d4;font-size:0.75rem;font-weight:700;padding:3px 10px;border-radius:20px">
                            <?php echo $r['train_count']; ?>
                        </span>
                    </td>
                    <td class="price-range">
                        ₹<?php echo number_format($r['min_price']); ?> – ₹<?php echo number_format($r['max_price']); ?>
                    </td>
                    <td>
                        <div class="action-cell">
                            <a href="#" class="btn-view" onclick="viewRoute('<?php echo htmlspecialchars($r['origin']); ?>','<?php echo htmlspecialchars($r['destination']); ?>','<?php echo $r['dep_time']; ?>','<?php echo $r['train_count']; ?>','<?php echo $r['min_price']; ?>','<?php echo $r['max_price']; ?>','<?php echo htmlspecialchars(addslashes($r['stops'] ?? '[]')); ?>')">
                                <i class="fa-solid fa-eye"></i>View
                            </a>
                            <form method="POST" onsubmit="return confirm('Delete all trains on this route?')" style="display:inline">
                                <input type="hidden" name="origin" value="<?php echo htmlspecialchars($r['origin']); ?>">
                                <input type="hidden" name="destination" value="<?php echo htmlspecialchars($r['destination']); ?>">
                                <button type="submit" name="delete_route" class="btn-del">
                                    <i class="fa-solid fa-trash" style="margin-right:4px"></i>Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if($cnt == 0): ?>
                <tr><td colspan="6">
                    <div class="empty-state">
                        <i class="fa-solid fa-route"></i>
                        No routes configured yet. Add your first route.
                    </div>
                </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- View Route Modal -->
<div id="viewRouteModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#1a1f2e;border:1px solid rgba(6,182,212,0.3);border-radius:18px;padding:2rem;min-width:360px;max-width:500px;width:90%;position:relative">
    <button onclick="document.getElementById('viewRouteModal').style.display='none'" style="position:absolute;top:14px;right:16px;background:none;border:none;color:var(--muted);font-size:1.2rem;cursor:pointer"><i class="fa-solid fa-xmark"></i></button>
    <h3 style="font-family:'Syne',sans-serif;color:#06b6d4;font-size:1.2rem;margin-bottom:4px"><i class="fa-solid fa-route" style="margin-right:8px"></i>Route Details</h3>
    <p style="color:var(--muted);font-size:.8rem;margin-bottom:1.4rem">Full information about this route</p>
    <div id="viewRouteContent"></div>
  </div>
</div>

<script>
function viewRoute(origin, dest, depTime, trainCount, minPrice, maxPrice, stopsJson) {
  document.getElementById('viewRouteContent').innerHTML = `
    <div style="display:grid;gap:10px">
      <div style="background:rgba(6,182,212,0.06);border:1px solid rgba(6,182,212,0.15);border-radius:10px;padding:14px">
        <div style="font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Route</div>
        <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:1.1rem">${origin} <span style="color:#06b6d4">→</span> ${dest}</div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div style="background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:10px;padding:12px">
          <div style="font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px">Departure</div>
          <div style="font-weight:700;color:#e2e8f0">${depTime || '—'}</div>
        </div>
        <div style="background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:10px;padding:12px">
          <div style="font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px">Trains</div>
          <div style="font-weight:700;color:#e2e8f0">${trainCount} train(s)</div>
        </div>
        <div style="background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:10px;padding:12px">
          <div style="font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px">Min Price</div>
          <div style="font-weight:700;color:#22c55e">₹${Number(minPrice).toLocaleString('en-IN')}</div>
        </div>
        <div style="background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:10px;padding:12px">
          <div style="font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px">Max Price</div>
          <div style="font-weight:700;color:#f59e0b">₹${Number(maxPrice).toLocaleString('en-IN')}</div>
        </div>
      </div>
      <div style="background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:10px;padding:12px">
        <div style="font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">Intermediate Stops</div>
        ${buildStopsHtml(stopsJson)}
      </div>
    </div>
  `;
  document.getElementById('viewRouteModal').style.display = 'flex';
}

function buildStopsHtml(stopsJson) {
  try {
    const s = JSON.parse(stopsJson || '[]');
    if (s.length > 0) {
      return s.map(st => `<span style="display:inline-flex;align-items:center;gap:5px;background:rgba(6,182,212,0.08);border:1px solid rgba(6,182,212,0.2);border-radius:6px;padding:4px 10px;font-size:.78rem;color:#06b6d4;margin:3px 3px 0 0"><i class="fa-solid fa-location-dot" style="font-size:.65rem"></i>${st}</span>`).join('');
    }
    return '<span style="color:var(--muted);font-size:.8rem">No intermediate stops added</span>';
  } catch(e) { return '<span style="color:var(--muted);font-size:.8rem">—</span>'; }
}

let stops = [];

function addStop() {
    const inp = document.getElementById('stopName');
    const name = inp.value.trim();
    if (!name) return;
    stops.push(name);
    inp.value = '';
    renderStops();
}

function removeStop(idx) {
    stops.splice(idx, 1);
    renderStops();
}

function renderStops() {
    const list = document.getElementById('stopsList');
    list.innerHTML = stops.map((s, i) => `
        <div class="stop-item">
            <span class="stop-name"><i class="fa-solid fa-location-dot" style="margin-right:6px;opacity:0.6"></i>${s}</span>
            <button type="button" class="remove-stop" onclick="removeStop(${i})"><i class="fa-solid fa-xmark"></i></button>
        </div>
    `).join('');
    document.getElementById('stopsJson').value = JSON.stringify(stops);
}

// Allow Enter in stop input
document.getElementById('stopName').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); addStop(); }
});
</script>
</body>
</html>
