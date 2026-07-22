<?php
include('db_config.php');
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] != 'admin') { header("Location: login.php"); exit(); }

// Handle cancel booking
if (isset($_POST['cancel_booking'])) {
    $tid = mysqli_real_escape_string($conn, $_POST['transaction_id']);
    mysqli_query($conn, "DELETE FROM bookings WHERE transaction_id='$tid'");
    header("Location: admin_bookings.php");
    exit();
}

$bookings = mysqli_query($conn, 
    "SELECT b.transaction_id,
            MIN(b.id) AS id,
            MAX(b.user_id) AS user_id,
            GROUP_CONCAT(b.passenger_name ORDER BY b.id SEPARATOR ', ') AS passenger_name,
            COUNT(b.id) AS num_passengers,
            MAX(b.berth_pref) AS berth_pref,
            MAX(b.payment_method) AS payment_method,
            MAX(b.booking_date) AS booking_date,
            MAX(b.age) AS age,
            MAX(b.gender) AS gender,
            t.train_name, t.origin, t.destination, t.price,
            (t.price * COUNT(b.id)) AS total_amount,
            u.username
     FROM bookings b
     LEFT JOIN trains t ON b.train_id = t.id 
     LEFT JOIN users u ON b.user_id = u.id
     GROUP BY b.transaction_id, t.id
     ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>All Bookings - RailStream Admin</title>
<?php include('admin_head.php'); ?>
<style>
.modal-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);z-index:200;align-items:center;justify-content:center; }
.modal-overlay.open { display:flex; }
.modal { background:#111827;border:1px solid rgba(255,255,255,0.1);border-radius:20px;padding:2rem;width:90%;max-width:580px;position:relative;animation:fadeUp 0.25s ease; }
@keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
.modal-close { position:absolute;top:16px;right:16px;background:none;border:none;color:#64748b;font-size:1.2rem;cursor:pointer; }
.modal-close:hover { color:#f1f5f9; }
.modal h2 { font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:700;margin-bottom:4px; }
.modal .subtitle { color:#64748b;font-size:0.8rem;margin-bottom:1.5rem; }
.detail-grid { display:grid;grid-template-columns:1fr 1fr;gap:12px; }
.detail-box { background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:12px 14px; }
.detail-box .dlabel { color:#3b82f6;font-size:0.7rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px; }
.detail-box .dval { font-size:0.9rem;font-weight:500; }
.row-btn { background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.2);color:#f59e0b;border-radius:7px;padding:5px 12px;font-size:0.75rem;font-weight:600;cursor:pointer;transition:all 0.2s; }
.row-btn:hover { background:rgba(245,158,11,0.2); }
</style>
</head>
<body>
<?php include('admin_nav.php'); ?>
<div class="page-main">
    <div class="page-header">
        <h1 style="color:#f59e0b"><i class="fa-solid fa-calendar-check" style="margin-right:10px"></i>All Bookings</h1>
        <p>Here you can monitor all passenger booking records.</p>
    </div>
    <div class="card" style="overflow-x:auto">
        <table>
            <thead><tr>
                <th>Booking ID</th><th>User</th><th>Passenger</th><th>Train</th><th>Route</th><th>Seats</th><th>Amount</th><th>Payment</th><th>Status</th><th>Details</th><th>Action</th>
            </tr></thead>
            <tbody>
            <?php while($b = mysqli_fetch_assoc($bookings)):
                $status = !empty($b['payment_method']) ? 'Confirmed' : 'Pending';
                $statusClass = $status === 'Confirmed' ? 'badge-green' : 'badge-amber';
            ?>
                <tr>
                    <td><span class="badge badge-blue">#<?php echo $b['id']; ?></span></td>
                    <td style="color:#94a3b8"><?php echo $b['username'] ?? '—'; ?></td>
                    <td style="font-weight:600"><?php echo $b['passenger_name'] ?? '—'; ?></td>
                    <td style="font-size:0.82rem"><?php echo $b['train_name'] ?? '—'; ?></td>
                    <td style="font-size:0.8rem;color:#94a3b8"><?php echo $b['origin'] ?? ''; ?> → <?php echo $b['destination'] ?? ''; ?></td>
                    <td><?php echo ($b['num_passengers'] > 1 ? $b['num_passengers'].' pax · ' : '') . ($b['berth_pref'] ?? '—'); ?></td>
                    <td style="color:#22c55e;font-weight:600">₹<?php echo number_format($b['total_amount'] ?? $b['price'] ?? 0); ?></td>
                    <td style="font-size:0.8rem"><?php echo $b['payment_method'] ?: '—'; ?></td>
                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                    <td>
                        <button class="row-btn" onclick='openModal(<?php echo json_encode($b); ?>)'>
                            <i class="fa-solid fa-eye"></i> View
                        </button>
                    </td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Cancel this booking? This cannot be undone.')">
                            <input type="hidden" name="transaction_id" value="<?php echo htmlspecialchars($b['transaction_id']); ?>">
                            <button type="submit" name="cancel_booking" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#ef4444;border-radius:7px;padding:5px 12px;font-size:0.75rem;font-weight:600;cursor:pointer;transition:all 0.2s" onmouseover="this.style.background='rgba(239,68,68,0.22)'" onmouseout="this.style.background='rgba(239,68,68,0.1)'">
                                <i class="fa-solid fa-ban"></i> Cancel
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modalOverlay" onclick="if(event.target===this)closeModal()">
    <div class="modal">
        <button class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
        <h2>Booking Details</h2>
        <p class="subtitle">Complete passenger and booking information</p>
        <div class="detail-grid" id="modalContent"></div>
    </div>
</div>
<script>
function openModal(b) {
    const fields = [
        ['Booking ID', '#' + b.id],
        ['User Name', b.username || '—'],
        ['Bus / Train Name', b.train_name || '—'],
        ['Route', (b.origin || '') + ' → ' + (b.destination || '')],
        ['Total Passengers', b.num_passengers || '1'],
        ['Passenger Name', b.passenger_name || '—'],
        ['Seat / Berth', b.berth_pref || '—'],
        ['Payment Method', b.payment_method || '—'],
        ['Status', b.payment_method ? 'Confirmed' : 'Pending'],
        ['Booking Date', b.booking_date || '—'],
        ['Transaction ID', b.transaction_id || '—'],
        ['Total Amount', '₹' + (parseFloat(b.total_amount||b.price)||0).toLocaleString('en-IN')],
    ];
    document.getElementById('modalContent').innerHTML = fields.map(([l,v]) =>
        `<div class="detail-box"><div class="dlabel">${l}</div><div class="dval">${v}</div></div>`
    ).join('');
    document.getElementById('modalOverlay').classList.add('open');
}
function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }
</script>
</body></html>
