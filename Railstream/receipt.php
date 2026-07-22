<?php
include('db_config.php');
session_start();

// Support both session (after fresh booking) and GET param (from My Bookings)
if (isset($_GET['txn']) && !empty($_GET['txn'])) {
    $txn_id = mysqli_real_escape_string($conn, $_GET['txn']);
} elseif (isset($_SESSION['last_txn_id'])) {
    $txn_id = $_SESSION['last_txn_id'];
} else {
    header("Location: user_dashboard.php");
    exit();
}

// First try regular train bookings
$query = "SELECT b.*, t.train_name, t.origin, t.destination, t.departure_time, 
                 COALESCE(b.journey_date, t.travel_date) AS travel_date, t.price
          FROM bookings b
          JOIN trains t ON b.train_id = t.id
          WHERE b.transaction_id = '$txn_id'
          ORDER BY b.id ASC";

$result  = mysqli_query($conn, $query);
$tickets = [];
while ($row = mysqli_fetch_assoc($result)) { $tickets[] = $row; }

// If not found, try daily train bookings
if (empty($tickets)) {
    $query2 = "SELECT b.*, dt.train_name, dt.origin, dt.destination, dt.departure_time, b.journey_date AS travel_date, dt.price
               FROM bookings b
               JOIN daily_trains dt ON b.daily_train_id = dt.id
               WHERE b.transaction_id = '$txn_id'
               ORDER BY b.id ASC";
    $result2 = mysqli_query($conn, $query2);
    while ($row = mysqli_fetch_assoc($result2)) { $tickets[] = $row; }
}

if (empty($tickets)) {
    header("Location: user_dashboard.php");
    exit();
}

$first = $tickets[0];
$num   = count($tickets);
$total = $first['price'] * $num;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Boarding Pass - RailStream</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-slate-900 text-white flex items-center justify-center min-h-screen p-4">

    <div class="max-w-2xl w-full bg-white text-slate-900 rounded-3xl overflow-hidden shadow-2xl">

        <!-- Ticket Header -->
        <div class="bg-blue-600 p-6 text-white flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-black tracking-tighter">Rail<span class="text-blue-200">Stream</span></h1>
                <p class="text-xs opacity-80 uppercase font-bold">Official Boarding Pass</p>
            </div>
            <div class="text-right">
                <p class="text-xs opacity-80 uppercase font-bold">Transaction ID</p>
                <p class="font-mono font-bold"><?php echo htmlspecialchars($txn_id); ?></p>
                <p class="text-xs opacity-70 mt-1"><?php echo $num; ?> Passenger<?php echo $num>1?'s':''; ?></p>
            </div>
        </div>

        <!-- Route Banner -->
        <div class="p-8">
            <div class="flex justify-between items-center mb-8 border-b border-slate-100 pb-8">
                <div>
                    <p class="text-3xl font-bold text-blue-600"><?php echo htmlspecialchars($first['origin']); ?></p>
                    <p class="text-slate-400 text-sm font-medium">Departure Station</p>
                </div>
                <div class="text-slate-300 text-center">
                    <i class="fa-solid fa-train text-2xl"></i>
                    <div class="border-t-2 border-dashed border-slate-200 w-16 mt-2 mx-auto"></div>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-bold text-blue-600"><?php echo htmlspecialchars($first['destination']); ?></p>
                    <p class="text-slate-400 text-sm font-medium">Arrival Station</p>
                </div>
            </div>

            <!-- Train Info Row -->
            <div class="grid grid-cols-2 gap-y-4 gap-x-4 mb-6">
                <div>
                    <p class="text-xs text-slate-400 uppercase font-bold">Train Name</p>
                    <p class="font-bold text-lg"><?php echo htmlspecialchars($first['train_name']); ?></p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase font-bold">Date &amp; Time</p>
                    <p class="font-bold"><?php echo $first['travel_date']; ?> @ <?php echo substr($first['departure_time'],0,5); ?></p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase font-bold">Payment Method</p>
                    <p class="font-bold"><?php echo htmlspecialchars($first['payment_method']); ?></p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase font-bold">Total Amount Paid</p>
                    <p class="font-bold text-green-600 text-xl">₹<?php echo number_format($total); ?></p>
                </div>
            </div>

            <!-- Passenger List -->
            <div class="mb-8">
                <p class="text-xs text-slate-400 uppercase font-bold mb-3">
                    <i class="fa-solid fa-users mr-1"></i> Passengers (<?php echo $num; ?>)
                </p>
                <div class="rounded-xl overflow-hidden border border-slate-200">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-100">
                            <tr>
                                <th class="text-left p-3 text-slate-500 font-semibold">#</th>
                                <th class="text-left p-3 text-slate-500 font-semibold">Name</th>
                                <th class="text-left p-3 text-slate-500 font-semibold">Age</th>
                                <th class="text-left p-3 text-slate-500 font-semibold">Gender</th>
                                <th class="text-left p-3 text-slate-500 font-semibold">Berth</th>
                                <th class="text-right p-3 text-slate-500 font-semibold">Fare</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($tickets as $i => $t): ?>
                            <tr class="border-t border-slate-100 <?php echo $i%2?'bg-slate-50':''; ?>">
                                <td class="p-3 text-slate-400"><?php echo $i+1; ?></td>
                                <td class="p-3 font-semibold"><?php echo htmlspecialchars($t['passenger_name']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($t['age']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($t['gender']); ?></td>
                                <td class="p-3 text-blue-600"><?php echo htmlspecialchars($t['berth_pref']); ?></td>
                                <td class="p-3 text-right font-medium">₹<?php echo number_format($t['price']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="border-t-2 border-slate-300 bg-green-50">
                                <td colspan="5" class="p-3 font-bold text-right">Total</td>
                                <td class="p-3 text-right font-black text-green-700 text-lg">₹<?php echo number_format($total); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 no-print flex-wrap">
                <button onclick="window.print()" class="flex-1 bg-slate-900 text-white py-4 rounded-xl font-bold hover:bg-slate-800 transition flex items-center justify-center gap-2" style="min-width:160px">
                    <i class="fa-solid fa-print"></i> Print Ticket
                </button>
                <a href="user_dashboard.php#bookings" onclick="sessionStorage.setItem('gotoView','bookings')" class="flex-1 bg-slate-700 text-white py-4 rounded-xl font-bold text-center hover:bg-slate-600 transition flex items-center justify-center gap-2" style="min-width:160px">
                    <i class="fa-solid fa-ticket"></i> My Bookings
                </a>
                <a href="user_dashboard.php" class="flex-1 bg-blue-600 text-white py-4 rounded-xl font-bold text-center hover:bg-blue-700 transition flex items-center justify-center gap-2" style="min-width:160px">
                    <i class="fa-solid fa-house"></i> Return to Home
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-slate-50 border-t border-dashed border-slate-200 p-4 text-center">
            <p class="text-[10px] text-slate-400 font-mono tracking-widest uppercase">
                * Please carry a valid ID proof during travel *
            </p>
        </div>
    </div>

    <style>
        @media print {
            .no-print { display: none; }
            body { background: white; }
        }
    </style>
</body>
</html>
