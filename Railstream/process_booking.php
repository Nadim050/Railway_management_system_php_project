<?php
include('db_config.php');
session_start();

// Redirect if no booking data exists
if (!isset($_SESSION['booking_data'])) {
    header("Location: user_dashboard.php");
    exit();
}

$data     = $_SESSION['booking_data'];
$user_id  = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$method   = mysqli_real_escape_string($conn, $_GET['method'] ?? 'UPI');
$is_daily = isset($data['is_daily']) && $data['is_daily'] == '1';

// Support both single (legacy) and array passengers
$names   = isset($data['p_name'])   ? (array)$data['p_name']   : [];
$ages    = isset($data['p_age'])    ? (array)$data['p_age']    : [];
$genders = isset($data['p_gender']) ? (array)$data['p_gender'] : [];
$berths  = isset($data['p_berth'])  ? (array)$data['p_berth']  : [];
$num_passengers = count($names);

// Generate one Transaction ID for the whole booking group
$txn_id = "TXN" . rand(100000, 999999);
$first_booking_id = null;

if ($is_daily) {
    $daily_train_id = mysqli_real_escape_string($conn, $data['daily_train_id']);
    $journey_date   = mysqli_real_escape_string($conn, $data['journey_date'] ?? date('Y-m-d'));

    for ($i = 0; $i < $num_passengers; $i++) {
        $p_name   = mysqli_real_escape_string($conn, $names[$i]);
        $p_age    = mysqli_real_escape_string($conn, $ages[$i]);
        $p_gender = mysqli_real_escape_string($conn, $genders[$i]);
        $p_berth  = mysqli_real_escape_string($conn, $berths[$i]);
        $uid_val  = $user_id > 0 ? "'$user_id'" : 'NULL';

        $query = "INSERT INTO bookings (user_id, daily_train_id, journey_date, passenger_name, age, gender, berth_pref, payment_method, transaction_id)
                  VALUES ($uid_val, '$daily_train_id', '$journey_date', '$p_name', '$p_age', '$p_gender', '$p_berth', '$method', '$txn_id')";

        if (mysqli_query($conn, $query)) {
            if ($i === 0) $first_booking_id = mysqli_insert_id($conn);
        } else {
            echo "Error processing booking for passenger " . ($i+1) . ": " . mysqli_error($conn);
            exit();
        }
    }
    // No seat deduction for daily trains (they reset daily); optionally track if needed

} else {
    $train_id     = mysqli_real_escape_string($conn, $data['train_id']);
    $journey_date = mysqli_real_escape_string($conn, $data['journey_date'] ?? date('Y-m-d'));

    for ($i = 0; $i < $num_passengers; $i++) {
        $p_name   = mysqli_real_escape_string($conn, $names[$i]);
        $p_age    = mysqli_real_escape_string($conn, $ages[$i]);
        $p_gender = mysqli_real_escape_string($conn, $genders[$i]);
        $p_berth  = mysqli_real_escape_string($conn, $berths[$i]);
        $uid_val  = $user_id > 0 ? "'$user_id'" : 'NULL';

        $query = "INSERT INTO bookings (user_id, train_id, journey_date, passenger_name, age, gender, berth_pref, payment_method, transaction_id)
                  VALUES ($uid_val, '$train_id', '$journey_date', '$p_name', '$p_age', '$p_gender', '$p_berth', '$method', '$txn_id')";

        if (mysqli_query($conn, $query)) {
            if ($i === 0) $first_booking_id = mysqli_insert_id($conn);
        } else {
            echo "Error processing booking for passenger " . ($i+1) . ": " . mysqli_error($conn);
            exit();
        }
    }
    // Deduct seats equal to number of passengers
    mysqli_query($conn, "UPDATE trains SET available_seats = available_seats - $num_passengers WHERE id = '$train_id'");
}

// Store TXN ID in session for the receipt page
$_SESSION['last_txn_id'] = $txn_id;
unset($_SESSION['booking_data']);

// Redirect to receipt
header("Location: receipt.php");
exit();
?>
