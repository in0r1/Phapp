<?php
$host = '127.0.0.1';
$db   = 'database';
$user = 'dbadmin';
$pass = 'StrongPassword123!';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
