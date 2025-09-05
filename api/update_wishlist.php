<?php
header('Content-Type: application/json');
require_once "db.php";

$id = intval($_POST['id'] ?? 0);
$status = trim($_POST['status'] ?? "");
$last = trim($_POST['last_chapter'] ?? "");

if ($id <= 0) { http_response_code(400); echo json_encode(["error"=>"Invalid id"]); exit; }

$allowedStatus = ['completed','ongoing','reading','hiatus'];
if ($status && !in_array($status, $allowedStatus)) {
  http_response_code(400); echo json_encode(["error"=>"Invalid status"]); exit;
}

$fields = [];
$params = [];
$types  = "";

if ($status !== "") { $fields[] = "status=?"; $params[] = &$status; $types .= "s"; }
if ($last !== "")   { $fields[] = "last_chapter=?"; $params[] = &$last; $types .= "s"; }
if (empty($fields)) { echo json_encode(["success"=>true]); exit; }

$sql = "UPDATE wishlist SET ".implode(",", $fields)." WHERE id=?";
$params[] = &$id; $types .= "i";

$stmt = $conn->prepare($sql);
array_unshift($params, $types);
call_user_func_array([$stmt,'bind_param'], $params);
$ok = $stmt->execute();

echo json_encode(["success"=>$ok]);
