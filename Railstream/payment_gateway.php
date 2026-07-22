<?php
include('db_config.php');
session_start();

// If the user reached here via POST from the dashboard, store the data in the session
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION['booking_data'] = $_POST;
}

// Redirect if there is no data to process
if (!isset($_SESSION['booking_data'])) {
    header("Location: user_dashboard.php");
    exit();
}

$is_daily = isset($_SESSION['booking_data']['is_daily']) && $_SESSION['booking_data']['is_daily'] == '1';

if ($is_daily) {
    $daily_train_id = mysqli_real_escape_string($conn, $_SESSION['booking_data']['daily_train_id']);
    $res = mysqli_query($conn, "SELECT * FROM daily_trains WHERE id='$daily_train_id'");
    $train = mysqli_fetch_assoc($res);
    $journey_date = $_SESSION['booking_data']['journey_date'] ?? date('Y-m-d');
} else {
    $train_id = mysqli_real_escape_string($conn, $_SESSION['booking_data']['train_id']);
    $res = mysqli_query($conn, "SELECT * FROM trains WHERE id='$train_id'");
    $train = mysqli_fetch_assoc($res);
    // Use the user-selected journey date if provided, else fall back to today
    $journey_date = $_SESSION['booking_data']['journey_date'] ?? date('Y-m-d');
}

// Support both single (legacy) and array passengers
$names   = isset($_SESSION['booking_data']['p_name'])   ? (array)$_SESSION['booking_data']['p_name']   : [];
$ages    = isset($_SESSION['booking_data']['p_age'])    ? (array)$_SESSION['booking_data']['p_age']    : [];
$genders = isset($_SESSION['booking_data']['p_gender']) ? (array)$_SESSION['booking_data']['p_gender'] : [];
$berths  = isset($_SESSION['booking_data']['p_berth'])  ? (array)$_SESSION['booking_data']['p_berth']  : [];

$num_passengers = count($names);
$total_price    = $train['price'] * $num_passengers;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Summary & Payment - RailStream</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-900 text-white min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-xl space-y-5">

    <!-- Header -->
    <div class="text-center mb-2">
      <h1 class="text-2xl font-black tracking-tight">Rail<span class="text-blue-400">Stream</span></h1>
      <p class="text-slate-400 text-sm mt-1 uppercase tracking-widest font-semibold">Booking Summary</p>
    </div>

    <!-- Train Details Card -->
    <div class="bg-slate-800 rounded-2xl p-5 border border-white/10">
      <p class="text-xs text-slate-400 uppercase font-bold mb-3"><i class="fa-solid fa-train mr-2 text-blue-400"></i>Journey Details</p>
      <div class="flex justify-between items-center mb-3">
        <div>
          <p class="font-bold text-lg"><?php echo htmlspecialchars($train['train_name']); ?></p>
          <p class="text-slate-400 text-sm">
            <?php echo htmlspecialchars($train['origin']); ?> 
            <i class="fa-solid fa-arrow-right mx-1 text-blue-400"></i> 
            <?php echo htmlspecialchars($train['destination']); ?>
          </p>
        </div>
        <div class="text-right">
          <p class="text-xs text-slate-400">Departure</p>
          <p class="font-semibold"><?php echo htmlspecialchars($journey_date); ?></p>
          <p class="text-blue-400 font-mono"><?php echo substr($train['departure_time'],0,5); ?></p>
        </div>
      </div>
    </div>

    <!-- Passenger List Card -->
    <div class="bg-slate-800 rounded-2xl p-5 border border-white/10">
      <p class="text-xs text-slate-400 uppercase font-bold mb-3">
        <i class="fa-solid fa-users mr-2 text-blue-400"></i>
        <?php echo $num_passengers; ?> Passenger<?php echo $num_passengers>1?'s':''; ?>
      </p>
      <div class="space-y-3">
        <?php for($i=0;$i<$num_passengers;$i++): ?>
        <div class="flex items-center justify-between bg-slate-700/50 rounded-xl px-4 py-3">
          <div class="flex items-center gap-3">
            <div class="bg-blue-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold"><?php echo $i+1; ?></div>
            <div>
              <p class="font-semibold"><?php echo htmlspecialchars($names[$i]); ?></p>
              <p class="text-xs text-slate-400">Age <?php echo htmlspecialchars($ages[$i]); ?> &bull; <?php echo htmlspecialchars($genders[$i]); ?></p>
            </div>
          </div>
          <div class="text-right">
            <p class="text-xs text-slate-400">Berth</p>
            <p class="text-sm font-medium text-blue-300"><?php echo htmlspecialchars($berths[$i]); ?></p>
          </div>
        </div>
        <?php endfor; ?>
      </div>
    </div>

    <!-- Price Breakdown Card -->
    <div class="bg-slate-800 rounded-2xl p-5 border border-white/10">
      <p class="text-xs text-slate-400 uppercase font-bold mb-3"><i class="fa-solid fa-receipt mr-2 text-blue-400"></i>Fare Breakdown</p>
      <div class="flex justify-between text-sm mb-2">
        <span class="text-slate-400">Ticket price × <?php echo $num_passengers; ?></span>
        <span>₹<?php echo number_format($train['price']); ?> × <?php echo $num_passengers; ?></span>
      </div>
      <div class="border-t border-slate-600 mt-3 pt-3 flex justify-between items-center">
        <span class="font-bold text-lg">Total Amount</span>
        <span class="text-3xl font-black text-green-400">₹<?php echo number_format($total_price); ?></span>
      </div>
    </div>

    <!-- Payment Methods -->
    <div class="bg-slate-800 rounded-2xl p-5 border border-white/10">
      <p class="text-xs text-slate-400 uppercase font-bold mb-4"><i class="fa-solid fa-shield-halved mr-2 text-blue-400"></i>Choose Payment Method</p>
      <div class="space-y-3">
        <a href="process_booking.php?method=GPay" class="flex bg-white text-black py-4 rounded-xl font-bold hover:bg-gray-100 transition items-center justify-center gap-3">
          <!-- Google Pay inline SVG wordmark -->
          <svg height="20" viewBox="0 0 65 26" xmlns="http://www.w3.org/2000/svg" aria-label="Google Pay">
            <path d="M31.08 12.7v7.44h-2.35V2.4h6.24a5.63 5.63 0 014.01 1.57 5.1 5.1 0 011.67 3.87 5.1 5.1 0 01-1.67 3.9 5.56 5.56 0 01-4.01 1.56h-3.89zm0-8.1v5.9h3.93a3.1 3.1 0 002.3-.96 3.2 3.2 0 000-4.56 3.06 3.06 0 00-2.3-.97l-3.93.6z" fill="#5F6368"/>
            <path d="M43.97 7.79c1.72 0 3.08.46 4.07 1.38.99.92 1.49 2.18 1.49 3.78v7.63h-2.24v-1.72h-.1a4.46 4.46 0 01-3.87 2.12 4.98 4.98 0 01-3.42-1.2 3.85 3.85 0 01-1.36-3.03c0-1.28.48-2.3 1.45-3.04.97-.74 2.26-1.12 3.87-1.12 1.38 0 2.51.25 3.4.76v-.53c0-.8-.32-1.48-.96-2.04a3.34 3.34 0 00-2.27-.84c-1.31 0-2.35.55-3.1 1.66l-2.06-1.3c1.13-1.64 2.81-2.47 5.1-2.47zm-3.07 9.05c0 .6.26 1.1.78 1.5.52.39 1.13.59 1.82.59 1 0 1.88-.37 2.65-1.1.77-.74 1.16-1.6 1.16-2.6-.73-.58-1.75-.87-3.06-.87-.95 0-1.74.23-2.37.69-.63.46-.98 1.02-.98 1.79z" fill="#5F6368"/>
            <path d="M62.4 8.18L55.46 24.5h-2.42l2.6-5.62-4.6-10.7h2.55l3.32 8.2h.05l3.23-8.2z" fill="#5F6368"/>
            <path d="M20.7 11.26c0-.73-.07-1.43-.18-2.1H10.6v3.97h5.68a4.86 4.86 0 01-2.1 3.19v2.64h3.39c1.99-1.83 3.13-4.53 3.13-7.7z" fill="#4285F4"/>
            <path d="M10.6 21.4c2.84 0 5.22-.94 6.97-2.54l-3.4-2.64c-.94.63-2.15 1-3.57 1-2.74 0-5.07-1.85-5.9-4.35H1.2v2.73A10.53 10.53 0 0010.6 21.4z" fill="#34A853"/>
            <path d="M4.7 12.87a6.3 6.3 0 010-4.02V6.12H1.2a10.53 10.53 0 000 9.48l3.5-2.73z" fill="#FBBC04"/>
            <path d="M10.6 4.5a5.7 5.7 0 014.03 1.58l3-3A10.12 10.12 0 0010.6.28 10.53 10.53 0 001.2 6.12l3.5 2.73A6.27 6.27 0 0110.6 4.5z" fill="#EA4335"/>
          </svg>
          Pay ₹<?php echo number_format($total_price); ?> with Google Pay
        </a>
        <a href="process_booking.php?method=PhonePe" class="flex bg-purple-600 text-white py-4 rounded-xl font-bold hover:bg-purple-700 transition items-center justify-center gap-3">
          <i class="fa-solid fa-mobile-screen-button"></i>
          Pay ₹<?php echo number_format($total_price); ?> with PhonePe
        </a>
        <a href="process_booking.php?method=UPI" class="flex bg-blue-600 text-white py-4 rounded-xl font-bold hover:bg-blue-700 transition items-center justify-center gap-3">
          <i class="fa-solid fa-building-columns"></i>
          Pay ₹<?php echo number_format($total_price); ?> via UPI / NetBanking
        </a>
      </div>
    </div>

    <div class="flex gap-3 justify-center flex-wrap">
      <a href="javascript:history.back()" class="flex items-center gap-2 text-slate-400 hover:text-white text-sm transition border border-slate-700 rounded-xl px-5 py-3 hover:border-slate-500">
        <i class="fa-solid fa-arrow-left"></i> Go Back
      </a>
      <a href="user_dashboard.php" class="flex items-center gap-2 text-slate-400 hover:text-white text-sm transition border border-slate-700 rounded-xl px-5 py-3 hover:border-slate-500">
        <i class="fa-solid fa-house"></i> Return to Home
      </a>
    </div>

  </div>
</body>
</html>
