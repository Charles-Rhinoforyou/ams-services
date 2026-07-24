<?php
/* ============================================================
   api/geoip-update.php — Gestion de la base GeoLite2 (PROTÉGÉ)
   Actions : status | set-key | update | test
   La clé de licence MaxMind reste côté serveur (dossier privé),
   jamais renvoyée en clair au navigateur.
   ============================================================ */

require __DIR__ . '/_common.php';
require __DIR__ . '/geoip.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  ams_json(['success' => false, 'error' => 'Méthode non autorisée'], 405);
}
$body   = ams_require_admin();
$action = isset($body['action']) ? (string)$body['action'] : 'status';

function ams_geo_dir() {
  $d = ams_data_dir() . '/geoip';
  if (!is_dir($d)) @mkdir($d, 0755, true);
  return $d;
}
function ams_geo_key_file() { return ams_geo_dir() . '/license.txt'; }
function ams_geo_key() { $f = ams_geo_key_file(); return is_file($f) ? trim((string)@file_get_contents($f)) : ''; }

/* ---------- STATUS ---------- */
if ($action === 'status') {
  $p = ams_geoip_db_path();
  $key = ams_geo_key();
  ams_json([
    'success'  => true,
    'hasKey'   => $key !== '',
    'keyMask'  => $key === '' ? '' : substr($key, 0, 3) . str_repeat('•', max(0, strlen($key) - 6)) . substr($key, -3),
    'hasDb'    => is_file($p),
    'dbSize'   => is_file($p) ? filesize($p) : 0,
    'dbDate'   => is_file($p) ? gmdate('Y-m-d H:i', filemtime($p)) : '',
  ]);
}

/* ---------- SET KEY ---------- */
if ($action === 'set-key') {
  $key = trim((string)($body['key'] ?? ''));
  if ($key !== '' && !preg_match('/^[A-Za-z0-9_\-]{8,80}$/', $key)) {
    ams_json(['success' => false, 'error' => 'Format de clé inattendu'], 400);
  }
  if ($key === '') { @unlink(ams_geo_key_file()); ams_json(['success' => true, 'cleared' => true]); }
  if (@file_put_contents(ams_geo_key_file(), $key) === false) {
    ams_json(['success' => false, 'error' => "Écriture impossible (droits du dossier stats-data/geoip ?)"], 500);
  }
  @chmod(ams_geo_key_file(), 0600);
  ams_json(['success' => true]);
}

/* ---------- TEST (valide le lecteur pur-PHP) ---------- */
if ($action === 'test') {
  if (!ams_geoip_available()) ams_json(['success' => false, 'error' => 'Base GeoLite2 absente'], 400);
  $mine = ams_geo_lookup(ams_client_ip());
  $ref  = ams_geo_lookup('8.8.8.8');
  ams_json(['success' => true, 'self' => $mine, 'reference' => $ref]);
}

/* ---------- UPDATE (téléchargement + extraction en streaming) ---------- */
if ($action !== 'update') ams_json(['success' => false, 'error' => 'Action inconnue'], 400);

$key = ams_geo_key();
if ($key === '') ams_json(['success' => false, 'error' => 'Clé de licence MaxMind non enregistrée'], 400);

@set_time_limit(0);
@ini_set('memory_limit', '256M');

$url = 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key='
     . rawurlencode($key) . '&suffix=tar.gz';
$tmp = ams_geo_dir() . '/download.tar.gz';

/* Téléchargement (cURL, sinon flux) */
$ok = false; $httpCode = 0;
if (function_exists('curl_init')) {
  $fp = @fopen($tmp, 'wb');
  if ($fp) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_FILE => $fp, CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_TIMEOUT => 300, CURLOPT_CONNECTTIMEOUT => 20,
      CURLOPT_USERAGENT => 'AMS-Services-GeoIP-Updater',
    ]);
    $ok = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch); fclose($fp);
    if (!$ok || $httpCode !== 200) {
      @unlink($tmp);
      ams_json(['success' => false, 'error' => "Téléchargement échoué (HTTP $httpCode) " . ($err ?: '') . ' — clé invalide ?'], 502);
    }
  }
} else {
  $src = @fopen($url, 'rb');
  if ($src) { $dst = @fopen($tmp, 'wb'); if ($dst) { stream_copy_to_stream($src, $dst); fclose($dst); $ok = true; } fclose($src); }
  if (!$ok) ams_json(['success' => false, 'error' => 'Téléchargement impossible (cURL et allow_url_fopen indisponibles)'], 502);
}

/* Extraction : gzip + tar en streaming (aucune dépendance) */
$gz = @gzopen($tmp, 'rb');
if (!$gz) { @unlink($tmp); ams_json(['success' => false, 'error' => 'Archive illisible'], 500); }

$target  = ams_geoip_db_path();
$tmpMmdb = $target . '.new';
$found   = false;

while (!gzeof($gz)) {
  $header = gzread($gz, 512);
  if ($header === false || strlen($header) < 512) break;
  if (trim($header) === '') continue;                   // bloc vide = fin
  $name = trim(substr($header, 0, 100));
  $sizeOct = trim(substr($header, 124, 12));
  $size = $sizeOct === '' ? 0 : intval(octdec(preg_replace('/[^0-7]/', '', $sizeOct)));
  $isMmdb = (substr($name, -5) === '.mmdb');

  if ($isMmdb && $size > 0) {
    $out = @fopen($tmpMmdb, 'wb');
    if (!$out) { gzclose($gz); @unlink($tmp); ams_json(['success' => false, 'error' => "Écriture impossible dans stats-data/geoip"], 500); }
    $remaining = $size;
    while ($remaining > 0) {
      $chunk = gzread($gz, min(262144, $remaining));
      if ($chunk === false || $chunk === '') break;
      fwrite($out, $chunk);
      $remaining -= strlen($chunk);
    }
    fclose($out);
    $found = true;
    // consomme le padding jusqu'au multiple de 512
    $pad = (512 - ($size % 512)) % 512;
    if ($pad > 0) gzread($gz, $pad);
    break;
  }
  // saute le contenu du fichier courant + padding
  $skip = $size + ((512 - ($size % 512)) % 512);
  while ($skip > 0) { $c = gzread($gz, min(262144, $skip)); if ($c === false || $c === '') break; $skip -= strlen($c); }
}
gzclose($gz);
@unlink($tmp);

if (!$found || !is_file($tmpMmdb) || filesize($tmpMmdb) < 100000) {
  @unlink($tmpMmdb);
  ams_json(['success' => false, 'error' => 'Base .mmdb introuvable dans l\'archive'], 500);
}

/* Validation avant remplacement : le lecteur doit savoir lire la base */
try {
  $probe = (new AmsMmdb($tmpMmdb))->get('8.8.8.8');
  if (!is_array($probe)) throw new RuntimeException('lecture invalide');
} catch (Throwable $e) {
  @unlink($tmpMmdb);
  ams_json(['success' => false, 'error' => 'Base téléchargée illisible par le lecteur PHP : ' . $e->getMessage()], 500);
}

@unlink($target);
if (!@rename($tmpMmdb, $target)) {
  @unlink($tmpMmdb);
  ams_json(['success' => false, 'error' => 'Installation de la base impossible'], 500);
}
@chmod($target, 0644);

ams_json(['success' => true, 'size' => filesize($target), 'date' => gmdate('Y-m-d H:i')]);
