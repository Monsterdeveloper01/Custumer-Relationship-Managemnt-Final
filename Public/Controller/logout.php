<?php
// logout.php
session_start();
session_unset();
session_destroy();
header('Location: ../View/Login/login.php');
exit;
