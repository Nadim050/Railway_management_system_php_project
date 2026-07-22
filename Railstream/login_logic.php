<?php
include('db_config.php');
session_start();

if (isset($_POST['login_btn'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $user_row = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user_row['id'];
        $_SESSION['username'] = $user_row['username'];

        // Admin goes to admin dashboard, users go to user dashboard directly
        if ($username === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: user_dashboard.php");
        }
        exit();
    } else {
        echo "<script>
                alert('Invalid Credentials! Please check your username and password.');
                window.location.href='login.php';
              </script>";
    }
}
?>
