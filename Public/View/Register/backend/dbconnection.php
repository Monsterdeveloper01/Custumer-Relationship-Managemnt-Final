<?php 
$serverName = "localhost";
$username = "root";
$password = "";
$dbName = "tespartner";

$conn = mysqli_connect($serverName, $username, $password, $dbName);

if($conn->connect_error){
    die("Connection failed: " . mysqli_connect_error());
}

?>