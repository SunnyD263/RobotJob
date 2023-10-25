<?php
$_SESSION['Platform'] = $_SERVER['HTTP_USER_AGENT'];
{
    echo "<nav>"; 
    echo    "<ul>";
    echo        "<li>";
    echo            "<a href='/robotjob/index.php'>Overview</a>";
    echo        "</li>";
    echo        "<li>";
    echo            "<a href='/robotjob/addjob.php'>Add job</a>";
    echo        "</li>";
    echo    "</ul>";
    echo "</nav>";
}
?>