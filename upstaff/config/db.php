<?php
$servername = "localhost";
$username = "root";   // or your DB username
$password = "";       // or your DB password
$dbname = "upstaff";  // your database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);


    
}