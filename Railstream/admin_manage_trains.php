<?php
include('db_config.php');
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] != 'admin') { header("Location: login.php"); exit(); }
if (isset($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    mysqli_query($conn, "DELETE FROM trains WHERE id='$id'");
    header("Location: admin_manage_trains.php"); exit();
}
$trains = mysqli_query($conn, "SELECT * FROM trains ORDER BY travel_date ASC");
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Manage Trains - RailStream Admin</title>
<?php include('admin_head.php'); ?>
</head>
<body>
<?php include('admin_nav.php'); ?>
<div class="page-main">
    <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
        <div>
            <h1 style="color:#14b8a6"><i class="fa-solid fa-train-tram" style="font-size:1.6rem;margin-right:10px"></i>Manage Trains</h1>
            <p>Edit trains, update timings, and manage seat availability.</p>
        </div>
        <a href="admin_add_train.php" class="btn-primary"><i class="fa-solid fa-plus"></i> Add New Train</a>
    </div>
    <div class="card" style="overflow-x:auto">
        <table>
            <thead><tr>
                <th>ID</th><th>Train Name</th><th>Route</th><th>Time</th><th>Seats</th><th>Price</th><th>Action</th>
            </tr></thead>
            <tbody>
            <?php while($row = mysqli_fetch_assoc($trains)): ?>
                <tr>
                    <td><span class="badge badge-blue">#<?php echo $row['id']; ?></span></td>
                    <td style="font-weight:600"><?php echo $row['train_name']; ?></td>
                    <td style="color:#94a3b8"><?php echo $row['origin']; ?> → <?php echo $row['destination']; ?></td>
                    <td style="font-family:monospace;font-size:0.8rem"><?php echo $row['departure_time']; ?></td>
                    <td><span class="badge badge-green"><?php echo $row['available_seats']; ?></span></td>
                    <td style="color:#22c55e;font-weight:600">₹<?php echo number_format($row['price']); ?></td>
                    <td>
                        <a href="admin_manage_trains.php?delete=<?php echo $row['id']; ?>" class="btn-danger" onclick="return confirm('Delete this train?')">
                            <i class="fa-solid fa-trash"></i> Delete
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body></html>
