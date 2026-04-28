<?php
include '../includes/db.php';

$stmt = $conn->query("SELECT 1 as test");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);