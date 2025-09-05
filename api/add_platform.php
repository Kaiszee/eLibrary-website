<?php
header('Content-Type: application/json');
require_once "db.php";

$type = trim($_POST['type'] ?? 'streaming');
$name = trim($_POST['name'] ?? '');
$url  = trim($_POST['url'] ?? '');

if (!in_array($type, ['streaming','webtoon']) || $name==='' || $url==='') {
  http_response_code(400); echo json_encode(["error"=>"Invalid type, name, or url"]); exit;
}

if (!isset($_FILES['icon_file']) || $_FILES['icon_file']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400); echo json_encode(["error"=>"Icon file required"]); exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($_FILES['icon_file']['tmp_name']);
$allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
if (!isset($allowed[$mime])) { http_response_code(400); echo json_encode(["error"=>"Only JPG, PNG, WEBP, GIF allowed."]); exit; }
if ($_FILES['icon_file']['size'] > 4*1024*1024) { http_response_code(400); echo json_encode(["error"=>"Icon too large (max 4MB)."]); exit; }

$uploadDirFs = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDirFs)) { @mkdir($uploadDirFs, 0775, true); }
$ext = $allowed[$mime];
$filename = 'icon_' . bin2hex(random_bytes(6)) . '_' . time() . '.' . $ext;
$destFs = $uploadDirFs . DIRECTORY_SEPARATOR . $filename;
if (!move_uploaded_file($_FILES['icon_file']['tmp_name'], $destFs)) { http_response_code(500); echo json_encode(["error"=>"Failed to save icon."]); exit; }
$iconPathForDb = 'uploads/' . $filename;

$stmt = $conn->prepare("INSERT INTO platforms (type, name, url, icon_path) VALUES (?,?,?,?)");
$stmt->bind_param("ssss", $type, $name, $url, $iconPathForDb);
$ok = $stmt->execute();

echo json_encode(["success"=>$ok, "id"=>$ok ? $conn->insert_id : null, "icon_path"=>$iconPathForDb]);
