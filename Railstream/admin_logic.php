<?php
include('db_config.php');
session_start();

// Security: Prevent unauthorized execution
if (!isset($_SESSION['username']) || $_SESSION['username'] != 'admin') {
    die("Unauthorized Access Attempt.");
}

// ACTION: ADD TRAIN
if (isset($_POST['add_train'])) {
    $name = mysqli_real_escape_string($conn, $_POST['train_name']);
    $origin = mysqli_real_escape_string($conn, $_POST['origin']);
    $dest = mysqli_real_escape_string($conn, $_POST['destination']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $time = mysqli_real_escape_string($conn, $_POST['departure_time']);
    $date = mysqli_real_escape_string($conn, $_POST['travel_date']);

    $sql = "INSERT INTO trains (train_name, origin, destination, price, departure_time, travel_date) 
            VALUES ('$name', '$origin', '$dest', '$price', '$time', '$date')";
    
    if (mysqli_query($conn, $sql)) {
        header("Location: admin_dashboard.php?msg=added");
    }
}

// ACTION: DELETE TRAIN
if (isset($_GET['delete_train'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete_train']);
    mysqli_query($conn, "DELETE FROM trains WHERE id = '$id'");
    header("Location: admin_dashboard.php?msg=deleted");
}

// ACTION: MARK MESSAGE AS READ
if (isset($_GET['mark_read'])) {
    $mid = mysqli_real_escape_string($conn, $_GET['mark_read']);
    mysqli_query($conn, "UPDATE contact_messages SET status='Read' WHERE id = '$mid'");
    header("Location: admin_dashboard.php?msg=read");
}
?>