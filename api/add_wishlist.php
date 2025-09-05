<?php
header('Content-Type: application/json');
require_once "db.php";

$title  = trim($_POST['title'] ?? "");
$type   = trim($_POST['type'] ?? "");
$genre  = trim($_POST['genre'] ?? "");
$status = trim($_POST['status'] ?? "reading");
$site   = trim($_POST['site_url'] ?? "");
$prev   = trim($_POST['preview_url'] ?? "");
$psrc   = trim($_POST['preview_source'] ?? "");

$allowedType   = ['anime','manhwa','manhua','manga','movie'];

$allowedStatus = ['completed','ongoing','reading','hiatus'];
if ($title === "" || !in_array($type, $allowedType)) {
  http_response_code(400); echo json_encode(["error"=>"Title and valid type are required."]); exit;
}
if (!in_array($status, $allowedStatus)) $status = 'reading';

/* ----- Cover upload ----- */
if (!isset($_FILES['cover_file']) || $_FILES['cover_file']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400); echo json_encode(["error"=>"Cover image is required and must be a valid file."]); exit;
}
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($_FILES['cover_file']['tmp_name']);
$allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
if (!isset($allowed[$mime])) { http_response_code(400); echo json_encode(["error"=>"Only JPG, PNG, WEBP, GIF allowed."]); exit; }
if ($_FILES['cover_file']['size'] > 4*1024*1024) { http_response_code(400); echo json_encode(["error"=>"Image too large (max 4MB)."]); exit; }

$uploadDirFs = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDirFs)) { @mkdir($uploadDirFs, 0775, true); }
$ext = $allowed[$mime];
$filename = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
$destFs = $uploadDirFs . DIRECTORY_SEPARATOR . $filename;
if (!move_uploaded_file($_FILES['cover_file']['tmp_name'], $destFs)) { http_response_code(500); echo json_encode(["error"=>"Failed to save uploaded file."]); exit; }
$coverPathForDb = 'uploads/' . $filename;

/* ----- Insert wishlist ----- */
$stmt = $conn->prepare(
  "INSERT INTO wishlist (title, type, genre, status, site_url, cover_path, preview_url, preview_source)
   VALUES (?,?,?,?,?,?,?,?)"
);
$stmt->bind_param("ssssssss", $title, $type, $genre, $status, $site, $coverPathForDb, $prev, $psrc);
$ok = $stmt->execute();
if (!$ok) { http_response_code(500); echo json_encode(["error"=>"Failed to save wishlist"]); exit; }
$wid = $conn->insert_id;

/* ----- Insert multiple links ----- */
$labels = $_POST['source_label'] ?? [];
$urls   = $_POST['source_url']   ?? [];
$langs  = $_POST['source_lang']  ?? [];

if (!is_array($urls))   $urls = [];
if (!is_array($labels)) $labels = [];
if (!is_array($langs))  $langs = [];

if (count($urls) > 0) {
  $stmt2 = $conn->prepare("INSERT INTO wishlist_sources (wishlist_id, label, url, lang) VALUES (?,?,?,?)");
  for ($i = 0; $i < count($urls); $i++) {
    $u = trim($urls[$i] ?? "");
    if ($u === "") continue;
    $lb = trim($labels[$i] ?? "");
    $lg = trim($langs[$i] ?? "eng");
    if (!in_array($lg, ['id','kr','my','eng'])) $lg = 'eng';
    $stmt2->bind_param("isss", $wid, $lb, $u, $lg);
    $stmt2->execute();
  }
}

echo json_encode(["success"=>true, "id"=>$wid, "cover_path"=>$coverPathForDb]);
