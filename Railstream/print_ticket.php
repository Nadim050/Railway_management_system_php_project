<?php
include('db_config.php');
session_start();

if (!isset($_GET['id'])) { die("Access Denied."); }

$booking_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch booking details including train info
$query = "SELECT b.*, t.train_name, t.origin, t.destination, t.price, t.departure_time, 
                 COALESCE(b.journey_date, t.travel_date) AS travel_date
          FROM bookings b 
          JOIN trains t ON b.train_id = t.id 
          WHERE b.id = '$booking_id'";
$res = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($res);

if (!$data) { die("Ticket not found."); }
?>

<!DOCTYPE html>
<html>
<head>
    <title>RailStream Ticket - #<?php echo $booking_id; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* This ensures the buttons don't show up on the actual PDF/Printed paper */
        @media print {
            .no-print { display: none; }
            body { background: white; padding: 0; }
            .ticket-container { border: 1px solid #ccc; box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body class="bg-gray-200 py-10">

    <div class="max-w-2xl mx-auto bg-white p-10 shadow-xl rounded-xl ticket-container">
        <div class="flex justify-between items-center border-b-2 border-dashed pb-6 mb-6">
            <div>
                <h1 class="text-3xl font-black text-blue-600">RAILSTREAM</h1>
                <p class="text-gray-500 text-sm">Official E-Ticket</p>
            </div>
            <div class="text-right">
                <p class="font-mono text-lg uppercase"><?php echo $data['transaction_id']; ?></p>
                <p class="text-xs text-gray-400">Payment Verified</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-8 mb-8">
            <div>
                <label class="text-xs text-gray-400 uppercase">Passenger Name</label>
                <p class="font-bold text-lg"><?php echo $data['passenger_name']; ?></p>
            </div>
            <div>
                <label class="text-xs text-gray-400 uppercase">Train Name</label>
                <p class="font-bold text-lg"><?php echo $data['train_name']; ?></p>
            </div>
            <div>
                <label class="text-xs text-gray-400 uppercase">Ticket Price</label>
                <p class="font-bold text-lg text-green-600">₹<?php echo $data['price']; ?></p>
            </div>
            <div>
                <label class="text-xs text-gray-400 uppercase">Coach / Berth</label>
                <p class="font-semibold">SL / <?php echo $data['berth_pref']; ?></p>
            </div>
            <div>
                <label class="text-xs text-gray-400 uppercase">From</label>
                <p class="font-semibold"><?php echo $data['origin']; ?></p>
            </div>
            <div>
                <label class="text-xs text-gray-400 uppercase">To</label>
                <p class="font-semibold"><?php echo $data['destination']; ?></p>
            </div>
            <div>
                <label class="text-xs text-gray-400 uppercase">Date</label>
                <p class="font-semibold"><?php echo $data['travel_date']; ?></p>
            </div>
            <div>
                <label class="text-xs text-gray-400 uppercase">Departure</label>
                <p class="font-semibold"><?php echo $data['departure_time']; ?></p>
            </div>
        </div>

        <div class="bg-gray-50 p-4 rounded text-center text-xs text-gray-500 italic">
            This is a computer-generated ticket. Please carry a valid Photo ID proof for verification.
        </div>

        <div class="mt-10 flex gap-4 no-print flex-wrap">
            <button onclick="window.print()" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-blue-700 transition">
                <i class="fa-solid fa-print mr-2"></i>Save as PDF / Print
            </button>
            <a href="javascript:history.back()" class="bg-gray-200 text-gray-700 px-8 py-3 rounded-lg font-bold hover:bg-gray-300 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i>Go Back
            </a>
            <a href="user_dashboard.php" class="bg-slate-700 text-white px-8 py-3 rounded-lg font-bold hover:bg-slate-600 transition">
                <i class="fa-solid fa-house mr-2"></i>Return to Home
            </a>
        </div>
    </div>

</body>
</html>