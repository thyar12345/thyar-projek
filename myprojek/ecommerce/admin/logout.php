<?php
session_start();
$_SESSION = array();
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}
session_destroy();
session_start();
$_SESSION['alert_type'] = 'success';
$_SESSION['alert_message'] = 'Anda telah logout. Sampai jumpa lagi!';
header("Location: login.php");
exit();
?>