<?php
session_start();
session_unset();
session_destroy();
header("Location: customer_login.php");
exit();
?>