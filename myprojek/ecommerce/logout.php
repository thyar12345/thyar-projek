<?php
require_once 'config.php';
require_once 'redirect.php';

// Destroy all session data
session_unset();
session_destroy();

// Set alert for login page
session_start();
setAlert('success', 'Anda telah logout. Sampai jumpa lagi!');

// Redirect to admin login page
red('login.php');

?>