<?php
session_start();
session_unset();
session_destroy();
<<<<<<< HEAD
header("Location: index.php");
=======
header("Location: login.php");
>>>>>>> 8f96bcf12d7dea38956dcbf9c98a6cb92f5358f6
exit();
?>