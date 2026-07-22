<?php 
include('db_config.php'); 
session_start(); 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Bookings - RailStream</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="hero-bg text-white font-sans" style="background-size:cover;background-position:center;background-attachment:fixed;">

    <nav class="p-6 flex justify-between items-center border-b border-white/10">
        <div class="flex items-center gap-2 text-2xl font-bold">
            <div class="bg-white p-2 rounded-lg text-blue-600 shadow-lg">
                <i class="fa-solid fa-train-subway"></i>
            </div>
            <span>RailStream</span>
        </div>
        <div class="flex items-center gap-3">
            <a href="user_dashboard.php" class="text-gray-400 hover:text-white transition text-sm flex items-center gap-2 border border-white/10 rounded-lg px-4 py-2 hover:border-white/25">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
            <a href="user_dashboard.php" class="text-gray-400 hover:text-white transition text-sm flex items-center gap-2 border border-white/10 rounded-lg px-4 py-2 hover:border-white/25">
                <i class="fa-solid fa-house"></i> Home
            </a>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto py-12 px-4">
        <h2 class="text-3xl font-bold mb-8">Your Journey History</h2>

        <?php
        $user_id = $_SESSION['user_id'];
        
        $sql = "SELECT b.transaction_id,
                        MIN(b.id) AS b_id,
                        GROUP_CONCAT(b.passenger_name ORDER BY b.id SEPARATOR ', ') AS passenger_names,
                        COUNT(b.id) AS num_passengers,
                        MAX(b.payment_method) AS payment_method,
                        t.train_name, t.origin, t.destination, t.departure_time,
                        COALESCE(MAX(b.journey_date), t.travel_date) AS travel_date,
                        t.price,
                        (t.price * COUNT(b.id)) AS total_amount
                FROM bookings b
                JOIN trains t ON b.train_id = t.id 
                WHERE b.user_id = '$user_id' 
                GROUP BY b.transaction_id, t.id
                ORDER BY b_id DESC";
                
        $result = mysqli_query($conn, $sql);

        if($result && mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                ?>
                <div class="bg-white/5 border border-white/10 p-6 rounded-2xl mb-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-blue-400"><?php echo htmlspecialchars($row['train_name']); ?>
                          <?php if($row['num_passengers'] > 1): ?>
                            <span class="text-xs bg-blue-900/40 text-blue-300 px-2 py-1 rounded-full ml-2"><?php echo $row['num_passengers']; ?> passengers</span>
                          <?php endif; ?>
                        </h3>
                        <p class="text-sm text-gray-400"><?php echo htmlspecialchars($row['origin']); ?> to <?php echo htmlspecialchars($row['destination']); ?></p>
                        <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($row['passenger_names']); ?></p>
                        <p class="text-xs text-gray-500 mt-1">TXN: <?php echo htmlspecialchars($row['transaction_id']); ?> | Date: <?php echo $row['travel_date']; ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-green-400">₹<?php echo number_format($row['total_amount']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($row['payment_method'] ?? ''); ?></p>
                        <a href="receipt.php?txn=<?php echo urlencode($row['transaction_id']); ?>" class="bg-blue-600 px-4 py-2 rounded-lg text-sm font-bold inline-block mt-2">View / Print</a>
                    </div>
                </div>
                <?php
            }
        } else {
            echo "<p class='text-gray-500 text-center'>No bookings found. Try booking a train from the home page.</p>";
        }
        ?>
    </div>
</body>
</html>