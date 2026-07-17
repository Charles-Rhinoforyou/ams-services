<?php
/* ============================================================
   api/_common.php — utilitaires partagés (jamais servi seul)
   Site AMS'SERVICES — couche analytics + SEO (OVH / PHP)
   ============================================================ */

// Empêche l'accès direct au fichier utilitaire
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === '_common.php') {
  http_response_code(404);
  exit;
}

/* Hash SHA-256 du mot de passe admin (le mot de passe en clair n'est jamais stocké) */
const AMS_PW_HASH = 'ed785f8fe8f0ba0db1e832a9b7237d6e3739ad452f9f9fc10ee1c1f162519077';

/* Dossier de données privé (hors indexation, protégé par .htaccess) */
function ams_data_dir() {
  $dir = dirname(__DIR__) . '/stats-data';
  if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
    // Double protection : Apache 2.4 + 2.2
    @file_put_contents($dir . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
    @file_put_contents($dir . '/index.html', '');
  }
  return $dir;
}

/* Sel aléatoire persistant (jamais exposé) pour hacher les IP */
function ams_salt() {
  $f = ams_data_dir() . '/salt.txt';
  if (!file_exists($f)) {
    @file_put_contents($f, bin2hex(random_bytes(32)));
  }
  return @file_get_contents($f) ?: 'ams-fallback-salt';
}

function ams_json($data, $code = 200) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  header('X-Robots-Tag: noindex, nofollow');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

/* Lit le corps JSON et exige le mot de passe admin (auth serveur) */
function ams_require_admin() {
  $raw  = file_get_contents('php://input');
  $body = json_decode($raw, true);
  $pw   = (is_array($body) && isset($body['password'])) ? (string)$body['password'] : '';
  if (!hash_equals(AMS_PW_HASH, hash('sha256', $pw))) {
    ams_json(['success' => false, 'error' => 'Non autorisé'], 403);
  }
  return is_array($body) ? $body : [];
}

function ams_client_ip() {
  return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/* Identifiant visiteur : hash JOURNALIER salé (impossible de suivre d'un jour à l'autre) */
function ams_visitor_hash() {
  $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
  return substr(hash('sha256', ams_client_ip() . '|' . $ua . '|' . ams_salt() . '|' . gmdate('Y-m-d')), 0, 24);
}

/* Type d'appareil déduit du User-Agent (jamais stocké en entier) */
function ams_device_type() {
  $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
  if (preg_match('/ipad|tablet|playbook|silk|kindle/', $ua)) return 'tablet';
  if (preg_match('/mobile|iphone|ipod|android|windows phone|blackberry|opera mini/', $ua)) return 'mobile';
  return 'desktop';
}

/* Config (rétention, seuil de confidentialité géo, etc.) */
function ams_config() {
  $f = ams_data_dir() . '/config.json';
  $def = ['retention_days' => 400, 'geo_threshold' => 20, 'exclude_admin' => true];
  if (is_file($f)) {
    $c = json_decode(@file_get_contents($f), true);
    if (is_array($c)) return array_merge($def, $c);
  }
  return $def;
}
function ams_save_config($c) {
  @file_put_contents(ams_data_dir() . '/config.json', json_encode($c, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
