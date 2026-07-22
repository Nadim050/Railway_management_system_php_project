<?php
include('db_config.php');
session_start();

// Security: Must be logged in to book
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please login to book tickets!'); window.location.href='login.php';</script>";
    exit();
}

if (isset($_POST['train_id'])) {
    $train_id = mysqli_real_escape_string($conn, $_POST['train_id']);
    $user_id = $_SESSION['user_id'];
    
    // Simulate a Digital Payment Transaction ID
    // In a real app, this would come from a Payment Gateway API
    $transaction_id = "TXN-" . strtoupper(bin2hex(random_bytes(4)));

    // Insert into the bookings table
    // Note: If you haven't added the transaction_id column yet, 
    // run: ALTER TABLE bookings ADD COLUMN transaction_id VARCHAR(50);
    $sql = "INSERT INTO bookings (user_id, train_id, transaction_id) VALUES ('$user_id', '$train_id', '$transaction_id')";

    if (mysqli_query($conn, $sql)) {
        $booking_id = mysqli_insert_id($conn);
        
        // Success feedback and redirect to the print page
        echo "<script>
            alert('Payment Successful! Transaction ID: $transaction_id');
            window.location.href='print_ticket.php?id=$booking_id';
        </script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>