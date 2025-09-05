<?php
header('Content-Type: application/json');
require_once "db.php";

$type = isset($_GET['type']) ? trim($_GET['type']) : 'streaming';
if (!in_array($type, ['streaming','webtoon'])) $type = 'streaming';

$stmt = $conn->prepare("SELECT id, type, name, url, icon_path, created_at FROM platforms WHERE type=? ORDER BY created_at DESC");
$stmt->bind_param("s", $type);
$stmt->execute();
$res = $stmt->get_result();
echo json_encode($res->fetch_all(MYSQLI_ASSOC));
