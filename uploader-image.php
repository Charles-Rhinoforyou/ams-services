<?php
/* ============================================================
   uploader-image.php
   Reçoit une image depuis la page de gestion et l'enregistre
   dans le dossier /images sur OVH. Renvoie le chemin relatif.
   Protégé par le mot de passe admin (comparé en SHA-256).
   ============================================================ */

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

$PW_HASH = 'ed785f8fe8f0ba0db1e832a9b7237d6e3739ad452f9f9fc10ee1c1f162519077';
$DIR     = __DIR__ . '/images';
$MAX     = 8 * 1024 * 1024; // 8 Mo

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
  exit;
}

// Authentification (champ POST multipart)
$pw = isset($_POST['password']) ? (string)$_POST['password'] : '';
if (!hash_equals($PW_HASH, hash('sha256', $pw))) {
  http_response_code(403);
  echo json_encode(['success' => false, 'error' => 'Mot de passe incorrect']);
  exit;
}

// Fichier présent ?
if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Aucun fichier reçu']);
  exit;
}

$f = $_FILES['image'];
if ($f['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => "Erreur d'upload (code {$f['error']})"]);
  exit;
}
if ($f['size'] > $MAX) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Image trop lourde (max 8 Mo)']);
  exit;
}

// Vérification du type réel (pas seulement l'extension)
$allowed = [
  'image/jpeg' => 'jpg',
  'image/png'  => 'png',
  'image/webp' => 'webp',
  'image/gif'  => 'gif',
];
$mime = '';
if (function_exists('finfo_open')) {
  $fi = finfo_open(FILEINFO_MIME_TYPE);
  $mime = finfo_file($fi, $f['tmp_name']);
  finfo_close($fi);
} else {
  $info = @getimagesize($f['tmp_name']);
  $mime = $info ? $info['mime'] : '';
}
if (!isset($allowed[$mime])) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Format non supporté (JPG, PNG, WEBP ou GIF uniquement)']);
  exit;
}
$ext = $allowed[$mime];

// Nom de fichier propre à partir du nom original
$base = pathinfo($f['name'], PATHINFO_FILENAME);
$base = strtolower($base);
$base = preg_replace('/[^a-z0-9]+/', '-', $base);
$base = trim($base, '-');
if ($base === '') { $base = 'image'; }
$base = substr($base, 0, 40);

// Créer le dossier si besoin
if (!is_dir($DIR)) {
  @mkdir($DIR, 0755, true);
}

// Éviter d'écraser un fichier existant
$name = $base . '.' . $ext;
$n = 1;
while (file_exists($DIR . '/' . $name)) {
  $name = $base . '-' . $n . '.' . $ext;
  $n++;
}

if (!move_uploaded_file($f['tmp_name'], $DIR . '/' . $name)) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => "Enregistrement impossible (droits du dossier images/ ?)"]);
  exit;
}
@chmod($DIR . '/' . $name, 0644);

echo json_encode(['success' => true, 'path' => 'images/' . $name]);
