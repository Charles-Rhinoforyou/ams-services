<?php
/* ============================================================
   publier-realisations.php
   Écrit portfolio-data.js directement sur le serveur OVH.
   Protégé par le mot de passe admin (comparé en SHA-256).
   Le mot de passe en clair n'est jamais stocké ici.
   ============================================================ */

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

// Hash SHA-256 du mot de passe admin
$PW_HASH = 'ed785f8fe8f0ba0db1e832a9b7237d6e3739ad452f9f9fc10ee1c1f162519077';
$TARGET  = __DIR__ . '/portfolio-data.js';
$BACKUP  = __DIR__ . '/portfolio-data.backup.js';

// Seules les requêtes POST sont acceptées
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
  exit;
}

// Lecture du corps JSON
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Requête invalide']);
  exit;
}

// Authentification : mot de passe en clair (HTTPS) comparé au hash serveur
$pw = isset($body['password']) ? (string)$body['password'] : '';
if (!hash_equals($PW_HASH, hash('sha256', $pw))) {
  http_response_code(403);
  echo json_encode(['success' => false, 'error' => 'Mot de passe incorrect']);
  exit;
}

// Validation des données
if (!isset($body['data']) || !is_array($body['data'])) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Données manquantes ou invalides']);
  exit;
}

$data = $body['data'];
$data['lastUpdated'] = date('Y-m-d');

$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($json === false) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Encodage des données impossible']);
  exit;
}

$content = "// ============================================================\n"
         . "//  portfolio-data.js — Données du portfolio AMS'SERVICES\n"
         . "//  Publié en ligne via la page de gestion le " . date('d/m/Y à H:i') . "\n"
         . "// ============================================================\n"
         . "const PORTFOLIO_DATA = " . $json . ";\n";

// Sauvegarde de la version précédente (au cas où)
if (file_exists($TARGET)) {
  @copy($TARGET, $BACKUP);
}

// Écriture du fichier
if (@file_put_contents($TARGET, $content) === false) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => "Écriture impossible sur le serveur (droits du fichier portfolio-data.js ?)"]);
  exit;
}

echo json_encode(['success' => true, 'message' => 'Publié en ligne', 'date' => date('d/m/Y à H:i')]);
