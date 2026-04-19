<?php
$conn = new mysqli("localhost", "root", "", "memo_system");

if ($conn->connect_error) {
    die("連線失敗: " . $conn->connect_error);
}
?>