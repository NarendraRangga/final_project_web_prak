<?php
$host = "localhost";
$user = "root";       
$pass = "";           
$dbname = "autofix_db"; // Make sure this matches your exact database name

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>