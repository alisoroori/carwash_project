// logout.php
<?php
session_start();
session_unset();
session_destroy();

// مسیر درست به صفحه اصلی
header("Location: ../index.php");
exit;
?>
