<?php
// Adjust to your XAMPP/phpMyAdmin creds
$host = "localhost";
$user = "root";
$pass = ""; // default XAMPP
$db   = "elibrary";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  http_response_code(500);
  echo json_encode(["error" => "DB connection failed"]);
  exit;
}
$conn->set_charset("utf8mb4");
?>
