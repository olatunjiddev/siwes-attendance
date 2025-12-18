<?php
// Kaywhytee Template Code

session_start();
function dbConnect(){
    $servername = "localhost";
    $username = "root";//DATABASE USERNAME
    $password = "";//DATABASE PASSWORD
    $database = "web_attendance";//DATABASE NAME
    $dns = "mysql:host=$servername;dbname=$database";

    try {
        $conn = new PDO($dns, $username, $password);
        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
    }
}
//return dbConnect()

$conn = dbConnect();