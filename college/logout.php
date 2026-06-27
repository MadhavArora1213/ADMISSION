<?php
session_start();
unset($_SESSION['college_account_id'], $_SESSION['college_name']);
session_destroy();
header('Location: /ADMISSION/college/login.php?msg=logout');
exit;
