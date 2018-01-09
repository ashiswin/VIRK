<?php
    // Clear cache, return to login page, ez pz
    session_start();
    session_unset();
    session_destroy();

    header("Location: ../login.php");
?>