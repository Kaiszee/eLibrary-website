<?php
header('Content-Type: application/json; charset=utf-8');
require_once "db.php";

// Read filters
$q      = isset($_GET['q']) ? trim($_GET['q']) : "";
$type   = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : "";
$status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : "";

// Allow lists
$allowedTypes  = ['anime','manhwa','manhua','manga','movie'];
$allowedStatus = ['completed','ongoing','reading','hiatus'];

// Build WHERE + bind params
$where      = [];
$bindTypes  = "";   // e.g., "sss"
$bindValues = [];

// Search text
if ($q !== "") {
  $where[]     = "(title LIKE ? OR genre LIKE ?)";
  $bindTypes  .= "ss";
  $like = "%{$q}%";
  $bindValues[] = $like;
  $bindValues[] = $like;
}

// Type filter (only if valid)
if (in_array($type, $allowedTypes, true)) {
  $where[]     = "type = ?";
  $bindTypes  .= "s";
  $bindValues[] = $type;
}

// Status filter (only if valid)
if (in_array($status, $allowedStatus, true)) {
  $where[]     = "status = ?";
  $bindTypes  .= "s";
  $bindValues[] = $status;
}

// Final SQL
$sql = "SELECT * FROM wishlist";
if ($where) {
  $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY created_at DESC";

// Prepare & bind
$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(["error" => "Prepare failed: ".$conn->error]);
  exit;
}
if ($bindTypes !== "") {
  // bind_param via call_user_func_array needs references
  $params = [$bindTypes];
  foreach ($bindValues as $k => $v) { $params[] = &$bindValues[$k]; }
  call_user_func_array([$stmt, 'bind_param'], $params);
}

$stmt->execute();
$res  = $stmt->get_result();
$data = $res->fetch_all(MYSQLI_ASSOC);

// Attach multi-links (if you use wishlist_sources)
for ($i = 0; $i < count($data); $i++) {
  $wid = (int)$data[$i]['id'];
  $rs = $conn->query("SELECT id,label,url,lang FROM wishlist_sources WHERE wishlist_id=$wid ORDER BY id ASC");
  $data[$i]['sources'] = $rs ? $rs->fetch_all(MYSQLI_ASSOC) : [];
}

echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
