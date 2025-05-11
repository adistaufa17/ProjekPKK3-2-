<?php
session_start();
require_once 'reset_booking.php';


if (isset($_SESSION['user_id'])) {
    header("Location: beranda.php");
} else {
    header("Location: login.php");
}
exit();
?>
