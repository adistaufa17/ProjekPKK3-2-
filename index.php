<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: beranda.php");
} else {
    header("Location: login.php");
}
exit();
?>
